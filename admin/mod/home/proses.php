<?php
// Attempt to avoid session_start warnings on hosts where session.save_path isn't writable.
// If current save_path isn't writable, set it to sys_get_temp_dir() when possible.
$current_save_path = ini_get('session.save_path');
if (empty($current_save_path) || !is_dir($current_save_path) || !is_writable($current_save_path)) {
  // Prefer system temp if writable
  $fallback = sys_get_temp_dir();
  if (is_dir($fallback) && is_writable($fallback)) {
    ini_set('session.save_path', $fallback);
  } else {
    // Try to create a project-local tmp_sessions directory as a fallback
    $project_root = realpath(__DIR__ . '/../../../');
    if ($project_root) {
      $local_tmp = $project_root . DIRECTORY_SEPARATOR . 'tmp_sessions';
      if (!is_dir($local_tmp)) {
        // attempt to create with restrictive permissions
        @mkdir($local_tmp, 0700, true);
      }
      if (is_dir($local_tmp) && is_writable($local_tmp)) {
        ini_set('session.save_path', $local_tmp);
      }
    }
  }
}
// Start session quietly; if it fails, log it to server error log but avoid showing notices to users.
if (session_status() === PHP_SESSION_NONE) {
  if (!@session_start()) {
    if (function_exists('debug_log')) {
      debug_log('Warning: session_start failed in ' . __FILE__ . ' - save_path=' . ini_get('session.save_path'));
    }
  }
}

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}

require_once '../../../library/config.php';
include('../../../library/function.php');
require_once '../../login/user.php';

switch (@$_GET['action']) {
  case 'table':

    // Set default values
    $total_hadir = 0;
    $total_siswa_aktif = 0;
    $persentase_hadir = 0;

    try {
      // Query statistik dengan error handling
      if (isset($connection) && $connection) {
        // Ambil data statistik terbaru dari tabel `statistik` (join ke user)
        // Jika admin login adalah guru (level_id = 3) dan tugas_tambahan mengandung '4'
        // (wali_kelas), batasi ke siswa yang ada di kelas yang menjadi tanggung jawab wali tersebut.
        $query_statistik = "SELECT s.statistik_id, s.user_id, s.jumlah, s.date, s.time, u.nisn, u.nama_lengkap, u.status
      FROM statistik s
      LEFT JOIN user u ON s.user_id = u.user_id
      ";

        // detect current admin level / tugas from included login/user.php
        $restrict_by_kelas = false;
        $wali_kelas_ids = array();
        if (isset($current_user) && is_array($current_user)) {
          $current_level = isset($current_user['level_id']) ? intval($current_user['level_id']) : 0;
          $tugas_val = '';
          if (!empty($current_user['tugas_tambahan'])) {
            $tugas_val = $current_user['tugas_tambahan'];
          }

          if ($current_level === 3 && $tugas_val !== '' && (strpos($tugas_val, '4') !== false || $tugas_val === '4')) {
            // Gather kelas ids associated with this wali (try user.kelas and kelas table)
            $admin_id = isset($current_user['admin_id']) ? intval($current_user['admin_id']) : 0;

            // 1) from user table where wali_kelas numeric stored
            if ($admin_id > 0) {
              $q_u_sql = "SELECT DISTINCT kelas FROM user WHERE wali_kelas = " . $admin_id . " AND kelas IS NOT NULL AND kelas <> ''";
              $q_u = $connection->query($q_u_sql);
              if ($q_u && $q_u->num_rows > 0) {
                while ($r = $q_u->fetch_assoc()) {
                  if (isset($r['kelas']) && $r['kelas'] !== '') {
                    $wali_kelas_ids[] = intval($r['kelas']);
                  }
                }
              }
            }

            // 2) from kelas table where wali fields may reference admin id or name
            $q_ck_sql = "SELECT kelas_id FROM kelas WHERE 1=0";
            if (isset($current_user['admin_id']) && $current_user['admin_id'] !== '') {
              $q_ck_sql .= " OR nama_wali_kelas='" . $connection->real_escape_string((string)$current_user['admin_id']) . "'";
              $q_ck_sql .= " OR wali_kelas_nama='" . $connection->real_escape_string((string)$current_user['admin_id']) . "'";
            }
            if (!empty($current_user['nama_lengkap'])) {
              $q_ck_sql .= " OR wali_kelas_nama LIKE '%" . $connection->real_escape_string($current_user['nama_lengkap']) . "%'";
              $q_ck_sql .= " OR nama_wali_kelas LIKE '%" . $connection->real_escape_string($current_user['nama_lengkap']) . "%'";
            }
            $q_ck = $connection->query($q_ck_sql);
            if ($q_ck && $q_ck->num_rows > 0) {
              while ($r = $q_ck->fetch_assoc()) {
                if (isset($r['kelas_id']) && $r['kelas_id'] !== '') {
                  $wali_kelas_ids[] = intval($r['kelas_id']);
                }
              }
            }

            $wali_kelas_ids = array_values(array_unique($wali_kelas_ids));
            if (count($wali_kelas_ids) > 0) {
              $restrict_by_kelas = true;
            }
          }
        }

        if ($restrict_by_kelas) {
          $in = implode(',', array_map('intval', $wali_kelas_ids));
          $query_statistik .= " WHERE u.kelas IN (" . $in . ")";
        }

        $query_statistik .= " ORDER BY s.date DESC, s.time DESC LIMIT 36";
        $result_statistik = $connection->query($query_statistik);

        // Query tambahan untuk data absensi hari ini (gunakan kolom 'tanggal' dan 'kehadiran')
        $query_absensi_today = "SELECT COUNT(*) as total_hadir FROM absensi WHERE DATE(tanggal) = CURDATE() AND kehadiran = 'Hadir'";
        $result_absensi_today = $connection->query($query_absensi_today);
        $total_hadir = $result_absensi_today ? $result_absensi_today->fetch_assoc()['total_hadir'] : 0;

        $query_total_siswa = "SELECT COUNT(*) as total FROM user WHERE status = '1'";
        $result_total_siswa = $connection->query($query_total_siswa);
        $total_siswa_aktif = $result_total_siswa ? $result_total_siswa->fetch_assoc()['total'] : 0;

        $persentase_hadir = $total_siswa_aktif > 0 ? round(($total_hadir / $total_siswa_aktif) * 100, 1) : 0;
      }
    } catch (Exception $e) {
      // Keep default values
      $result_statistik = false;
    }

    // Tampilkan statistik dasar
    echo '
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-gradient-primary text-white">
        <h5 class="mb-0 text-white">
          <i class="fas fa-chart-bar mr-2"></i>
          Statistik Kehadiran Hari Ini
        </h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-md-3">
            <div class="border-right">
              <h3 class="text-success mb-1">' . $total_hadir . '</h3>
              <p class="text-muted mb-0">Siswa Hadir</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="border-right">
              <h3 class="text-primary mb-1">' . $total_siswa_aktif . '</h3>
              <p class="text-muted mb-0">Total Siswa</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="border-right">
              <h3 class="text-info mb-1">' . $persentase_hadir . '%</h3>
              <p class="text-muted mb-0">Persentase Hadir</p>
            </div>
          </div>
          <div class="col-md-3">
            <h3 class="text-warning mb-1">' . date('H:i') . '</h3>
            <p class="text-muted mb-0">Jam Update</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>';

    if ($result_statistik && $result_statistik->num_rows > 0) {
      echo '
<div class="card border-0 shadow-sm">
  <div class="card-header bg-transparent">
    <h5 class="mb-0">
      <i class="fas fa-list mr-2"></i>
      Data Statistik Siswa Terbaru
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive" style="max-height:520px; overflow-y:auto;">
      <table class="table align-items-center table-flush table-hover mb-0">
        <thead class="thead-light">
          <tr>
            <th scope="col" class="border-0">NISN</th>
            <th scope="col" class="border-0">Nama Siswa</th>
            <th scope="col" class="border-0">Tanggal</th>
            <th scope="col" class="border-0">Status</th>
            <th scope="col" class="border-0">Aktivitas</th>
          </tr>
        </thead>
        <tbody>';
      $no = 1;
      while ($data = $result_statistik->fetch_assoc()) {
        // Status siswa berdasarkan kolom user.status
        $status_raw = isset($data['status']) ? trim($data['status']) : '';
        $status_lower = strtolower($status_raw);
        if ($status_lower === '1' || $status_lower === 'aktif') {
          $status_badge = '<span class="badge badge-success">Aktif</span>';
        } else {
          $status_badge = '<span class="badge badge-danger">Tidak Aktif</span>';
        }

        // Format tanggal/time dan aktivitas (jumlah)
        $tanggal_display = isset($data['date']) ? tanggal_ind($data['date']) : '-';
        $time_display = isset($data['time']) ? htmlspecialchars($data['time']) : '-';
        $jumlah_display = isset($data['jumlah']) ? htmlspecialchars($data['jumlah']) : '0';

        echo '
    <tr>
      <td class="border-0">
        <span class="font-weight-bold">' . strip_tags($data['nisn']) . '</span>
      </td>
      <td class="border-0">
        <div class="media align-items-center">
          <div class="avatar rounded-circle mr-3 bg-gradient-primary">
            <span class="text-white font-weight-bold">' . substr(strip_tags($data['nama_lengkap']), 0, 1) . '</span>
          </div>
          <div class="media-body">
            <span class="font-weight-bold">' . strip_tags($data['nama_lengkap']) . '</span>
          </div>
        </div>
      </td>
      <td class="border-0">
        <span class="text-muted">' . $tanggal_display . '</span>
      </td>
      <td class="border-0">
        ' . $status_badge . '
      </td>
      <td class="border-0">
        <span class="badge badge-info">' . $jumlah_display . '</span>
        <small class="text-muted ms-2">' . $time_display . '</small>
      </td>
    </tr>';
        $no++;
      }
      echo ' 
        </tbody>
      </table>
    </div>
  </div>
</div>';
    } else {
      echo '
  <div class="text-center py-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Belum Ada Data Statistik</h5>
        <p class="text-muted mb-0">Saat ini belum ada data statistik siswa untuk hari ini</p>
        <div class="mt-3">
          <a href="user" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-2"></i>
            Kelola Data Siswa
          </a>
        </div>
      </div>
    </div>
  </div>';
    }

    break;
}
