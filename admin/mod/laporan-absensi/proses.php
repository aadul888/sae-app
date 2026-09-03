<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once('../../../library/function.php');
  require_once '../../login/user.php';

  $modul_id = 17;
  include __DIR__ . '/../check_role.php';

  function check_access($type)
  {
    global $data_role;
    if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
      echo json_encode(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.']);
      exit;
    }
  }

  if (!function_exists('lap_finalize_absensi_lintas_hari')) {
    function lap_finalize_absensi_lintas_hari($connection)
    {
      $today = date('Y-m-d');
      $hari_map = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
      ];

      $stmt = $connection->prepare("SELECT DISTINCT tanggal FROM absensi WHERE tanggal < ? AND jam_masuk IS NOT NULL AND (jam_pulang IS NULL OR jam_pulang='' OR jam_pulang='00:00:00') AND status_masuk IN ('Tepat Waktu','Terlambat')");
      if (!$stmt) {
        return;
      }
      $stmt->bind_param('s', $today);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($result && ($row = $result->fetch_assoc())) {
        $tanggal = $row['tanggal'];
        $hari_en = date('l', strtotime($tanggal));
        $hari = isset($hari_map[$hari_en]) ? $hari_map[$hari_en] : $hari_en;
        $jam_pulang = '23:59:00';

        $jadwal_stmt = $connection->prepare("SELECT waktu_selesai FROM jadwal WHERE hari=? AND status='Y' LIMIT 1");
        if ($jadwal_stmt) {
          $jadwal_stmt->bind_param('s', $hari);
          $jadwal_stmt->execute();
          $jadwal_result = $jadwal_stmt->get_result();
          if ($jadwal_result && $jadwal_result->num_rows > 0) {
            $jadwal_row = $jadwal_result->fetch_assoc();
            if (!empty($jadwal_row['waktu_selesai'])) {
              $jam_pulang = $jadwal_row['waktu_selesai'];
            }
          }
          $jadwal_stmt->close();
        }

        $update_stmt = $connection->prepare("UPDATE absensi SET jam_pulang=?, status_pulang='Pulang Cepat', kehadiran=CASE WHEN kehadiran IS NULL OR kehadiran='' OR LOWER(kehadiran)='hadir' THEN 'Lupa absen pulang' ELSE kehadiran END, updated_at=NOW() WHERE tanggal=? AND jam_masuk IS NOT NULL AND (jam_pulang IS NULL OR jam_pulang='' OR jam_pulang='00:00:00') AND status_masuk IN ('Tepat Waktu','Terlambat')");
        if ($update_stmt) {
          $update_stmt->bind_param('ss', $jam_pulang, $tanggal);
          $update_stmt->execute();
          $update_stmt->close();
        }
      }
      $stmt->close();
    }
  }

  lap_finalize_absensi_lintas_hari($connection);

  switch (@$_GET['action']) {
    case 'filtering':
      check_access('lihat');

      $tanggal = !empty($_POST['tanggal']) ? date('Y-m-d', strtotime($_POST['tanggal'])) : date('Y-m-d');
      $query_absen = "
        SELECT a.id, u.nisn, u.nama_lengkap, k.nama_kelas, 
               a.foto_masuk, a.jam_masuk, a.foto_pulang, a.jam_pulang,
               a.metode, a.approval_status, a.kehadiran, a.status_masuk, a.status_pulang
        FROM absensi a
        LEFT JOIN user u ON a.user_id = u.user_id
        LEFT JOIN kelas k ON u.kelas = k.kelas_id
        WHERE a.tanggal='$tanggal'
        ORDER BY a.id ASC
      ";
      $result_absen = $connection->query($query_absen);
      echo '
        <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
          <thead class="thead-light">
            <tr>
              <th class="text-center" width="5">No</th>
              <th>NISN</th>
              <th>Nama</th>
              <th>Kelas</th>
              <th class="text-center">Metode</th>
              <th class="text-center">Foto Masuk</th>
              <th>Jam Masuk</th>
              <th class="text-center">Foto Pulang</th>
              <th>Jam Pulang</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>';
      $no = 0;
      if ($result_absen && $result_absen->num_rows > 0) {
        while ($row = $result_absen->fetch_assoc()) {
          $no++;
            $foto_masuk_rel = '../content/capture/' . $row['foto_masuk'];
            $foto_masuk_full = __DIR__ . '/../../../content/capture/' . $row['foto_masuk'];
            $foto_masuk_html = (!empty($row['foto_masuk']) && file_exists($foto_masuk_full))
              ? '<img src="' . $foto_masuk_rel . '" style="width:40px;height:40px;border-radius:6px;background:#f5f5f5;">'
              : '<img src="../content/thumbnail.jpg" style="width:40px;height:40px;border-radius:6px;background:#f5f5f5;">';
            // Foto Pulang
            $foto_pulang_rel = '../content/capture/' . $row['foto_pulang'];
            $foto_pulang_full = __DIR__ . '/../../../content/capture/' . $row['foto_pulang'];
            $foto_pulang_html = (!empty($row['foto_pulang']) && file_exists($foto_pulang_full))
              ? '<img src="' . $foto_pulang_rel . '" style="width:40px;height:40px;border-radius:6px;background:#f5f5f5;">'
              : '<img src="../content/thumbnail.jpg" style="width:40px;height:40px;border-radius:6px;background:#f5f5f5;">';
            // Metode badge
            $metode = $row['metode'] ?? 'rfid';
            $approval = $row['approval_status'] ?? '';
            if ($metode === 'manual') {
                $metode_html = '<span class="badge badge-warning" title="Absensi Manual"><i class="fas fa-hand-paper"></i> Manual</span>';
                if ($approval === 'pending') {
                    $metode_html .= '<br><span class="badge badge-sm badge-info mt-1"><i class="fas fa-clock"></i> Pending</span>';
                }
            } else {
                $metode_html = '<span class="badge badge-primary" title="Absensi RFID"><i class="fas fa-id-card"></i> RFID</span>';
            }
          echo '<tr>
            <td class="text-center">' . $no . '</td>
            <td>' . htmlspecialchars($row['nisn']) . '</td>
            <td>' . htmlspecialchars($row['nama_lengkap']) . '</td>
            <td>' . htmlspecialchars($row['nama_kelas']) . '</td>
            <td class="text-center">' . $metode_html . '</td>
            <td class="text-center">' . $foto_masuk_html . '</td>
            <td>' . htmlspecialchars($row['jam_masuk']) . '</td>
            <td class="text-center">' . $foto_pulang_html . '</td>
            <td>' . htmlspecialchars($row['jam_pulang']) . '</td>
            <td class="text-center">
              <button class="btn btn-sm btn-primary btn-edit" data-id="' . $row['id'] . '" title="Edit"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-danger btn-delete" data-id="' . $row['id'] . '" title="Hapus"><i class="fas fa-trash"></i></button>';
          if ($metode === 'manual' && $approval === 'pending') {
            echo '
              <button class="btn btn-sm btn-success btn-approve-manual" data-id="' . $row['id'] . '" title="Setujui"><i class="fas fa-check"></i></button>
              <button class="btn btn-sm btn-warning btn-reject-manual" data-id="' . $row['id'] . '" title="Tolak"><i class="fas fa-times"></i></button>';
          }
          echo '</td>
          </tr>';
        }
      } else {
        echo '<tr><td colspan="10" class="text-center">Tidak ada data absensi hari ini.</td></tr>';
      }
      echo '</tbody></table>';
      exit;

    case 'edit-form':
      check_access('modifikasi');
      $id = $_POST['id'];
      $stmt = $connection->prepare("SELECT a.*, u.nama_lengkap, u.nisn FROM absensi a LEFT JOIN user u ON a.user_id = u.user_id WHERE a.id=?");
      $stmt->bind_param('s', $id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();

        // DEBUG: tampilkan data hasil query sebagai komentar HTML
        echo "<!-- DEBUG absensi data: " . htmlspecialchars(json_encode($data)) . " -->\n";

        $jam_masuk = htmlspecialchars($data['jam_masuk'] ?? '');
        $jam_pulang = htmlspecialchars($data['jam_pulang'] ?? '');
        $kehadiran = $data['kehadiran'] ?? '';
        $status_masuk = $data['status_masuk'] ?? '';
        $status_pulang = $data['status_pulang'] ?? '';

        echo '
      <form id="form-edit-data">
        <input type="hidden" name="id" value="' . $data['id'] . '">
        <div class="row mb-2">
          <div class="col-md-6">
            <label>Nama</label>
            <input type="text" class="form-control" value="' . htmlspecialchars($data['nama_lengkap']) . '" readonly>
          </div>
          <div class="col-md-6">
            <label>NISN</label>
            <input type="text" class="form-control" value="' . htmlspecialchars($data['nisn']) . '" readonly>
          </div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6">
            <label>Tanggal</label>
            <input type="date" class="form-control" name="tanggal" value="' . htmlspecialchars($data['tanggal']) . '">
          </div>
        </div>
        <div class="row mb-2">
          <div class="col-md-4">
            <label>Jam Masuk</label>
            <input type="text" class="form-control" name="jam_masuk" value="' . $jam_masuk . '">
          </div>
          <div class="col-md-4">
            <label>Jam Pulang</label>
            <input type="text" class="form-control" name="jam_pulang" value="' . $jam_pulang . '">
          </div>
          <div class="col-md-4">
            <label>Kehadiran</label>
            <select class="form-control" name="kehadiran">
              <option value=""' . ($kehadiran == '' ? ' selected' : '') . '>-- Pilih --</option>
              <option value="Hadir"' . ($kehadiran == 'Hadir' ? ' selected' : '') . '>Hadir</option>
              <option value="Izin"' . ($kehadiran == 'Izin' ? ' selected' : '') . '>Izin</option>
              <option value="Sakit"' . ($kehadiran == 'Sakit' ? ' selected' : '') . '>Sakit</option>
              <option value="Alpa"' . ($kehadiran == 'Alpa' ? ' selected' : '') . '>Alpa</option>
            </select>
          </div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6">
            <label>Status Masuk</label>
            <select class="form-control" name="status_masuk">
              <option value=""' . ($status_masuk == '' ? ' selected' : '') . '>-- Pilih --</option>
              <option value="Tepat Waktu"' . ($status_masuk == 'Tepat Waktu' ? ' selected' : '') . '>Tepat Waktu</option>
              <option value="Terlambat"' . ($status_masuk == 'Terlambat' ? ' selected' : '') . '>Terlambat</option>
            </select>
          </div>
          <div class="col-md-6">
            <label>Status Pulang</label>
            <select class="form-control" name="status_pulang">
              <option value=""' . ($status_pulang == '' ? ' selected' : '') . '>-- Pilih --</option>
              <option value="Tepat Waktu"' . ($status_pulang == 'Tepat Waktu' ? ' selected' : '') . '>Tepat Waktu</option>
              <option value="Pulang Awal"' . ($status_pulang == 'Pulang Awal' ? ' selected' : '') . '>Pulang Awal</option>
              <option value="Pulang Cepat"' . ($status_pulang == 'Pulang Cepat' ? ' selected' : '') . '>Pulang Cepat</option>
              <option value="Pulang"' . ($status_pulang == 'Pulang' ? ' selected' : '') . '>Pulang</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-save"><i class="far fa-save"></i> Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>';
      } else {
        echo 'Data tidak ditemukan.';
      }
      break;

    case 'update':
      check_access('modifikasi');
      $id = intval($_POST['id']);
      $tanggal = $_POST['tanggal'];
      $jam_masuk = $_POST['jam_masuk'];
      $status_masuk = $_POST['status_masuk'];
      $jam_pulang = $_POST['jam_pulang'];
      $status_pulang = $_POST['status_pulang'];
      $kehadiran = $_POST['kehadiran'];

      // Konversi jam ke format HH:MM:SS jika hanya HH:MM
      if (!empty($jam_masuk) && preg_match('/^\d{2}:\d{2}$/', $jam_masuk)) {
        $jam_masuk .= ':00';
      }
      if (!empty($jam_pulang) && preg_match('/^\d{2}:\d{2}$/', $jam_pulang)) {
        $jam_pulang .= ':00';
      }

      // Update data ke database using prepared statement
      $jam_masuk = ($jam_masuk === '' || $jam_masuk === null) ? null : $jam_masuk;
      $jam_pulang = ($jam_pulang === '' || $jam_pulang === null) ? null : $jam_pulang;
      $status_masuk = ($status_masuk === '' || $status_masuk === null) ? null : $status_masuk;
      $status_pulang = ($status_pulang === '' || $status_pulang === null) ? null : $status_pulang;
      $kehadiran = ($kehadiran === '' || $kehadiran === null) ? null : $kehadiran;
      $stmt_upd = $connection->prepare("UPDATE absensi SET tanggal=?, jam_masuk=?, status_masuk=?, jam_pulang=?, status_pulang=?, kehadiran=?, updated_at=NOW() WHERE id=?");
      if ($stmt_upd) {
        $stmt_upd->bind_param('ssssssi', $tanggal, $jam_masuk, $status_masuk, $jam_pulang, $status_pulang, $kehadiran, $id);
        if ($stmt_upd->execute()) {
          $stmt_upd->close();
          echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui.']);
        } else {
          echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data']);
          $stmt_upd->close();
        }
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data']);
      }
      exit;
      break;

    case 'delete':
      check_access('hapus');
      $id = $_POST['id'];

      // Cek apakah ID valid dan angka
      if (!is_numeric($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
        exit;
      }

      // Ambil data absen untuk cek foto yang mungkin perlu dihapus
      $stmt_foto = $connection->prepare("SELECT foto_masuk, foto_pulang FROM absensi WHERE id=?");
      $stmt_foto->bind_param('s', $id);
      $stmt_foto->execute();
      $query_foto = $stmt_foto->get_result();
      if ($query_foto && $query_foto->num_rows > 0) {
        $foto = $query_foto->fetch_assoc();
        $foto_masuk_path = __DIR__ . '/../../../content/capture/' . $foto['foto_masuk'];
        $foto_pulang_path = __DIR__ . '/../../../content/capture/' . $foto['foto_pulang'];
        if (!empty($foto['foto_masuk']) && file_exists($foto_masuk_path)) unlink($foto_masuk_path);
        if (!empty($foto['foto_pulang']) && file_exists($foto_pulang_path)) unlink($foto_pulang_path);
      }
      $stmt_foto->close();

      // Hapus data dari database
      $stmt_del = $connection->prepare("DELETE FROM absensi WHERE id=?");
      $stmt_del->bind_param('s', $id);
      $delete = $stmt_del->execute();
      if ($delete) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data.']);
      }
      break;

    case 'approve_manual':
      check_access('modifikasi');
      $id = intval($_POST['id']);
      $admin_id = intval($_SESSION['admin_id'] ?? $ptk_id ?? 0);
      $st = $connection->prepare("UPDATE absensi SET approval_status='approved', approved_by=?, approved_at=NOW(), updated_at=NOW() WHERE id=? AND approval_status='pending'");
      if ($st) {
        $st->bind_param('ii', $admin_id, $id);
        $st->execute();
        $q = $st->affected_rows > 0;
        $st->close();
      } else { $q = false; }
      if ($q) {
        echo json_encode(['status' => 'success', 'message' => 'Absensi manual disetujui.']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyetujui.']);
      }
      break;

    case 'reject_manual':
      check_access('modifikasi');
      $id = intval($_POST['id']);
      $admin_id = intval($_SESSION['admin_id'] ?? $ptk_id ?? 0);
      $st = $connection->prepare("UPDATE absensi SET approval_status='rejected', approved_by=?, approved_at=NOW(), updated_at=NOW() WHERE id=? AND approval_status='pending'");
      if ($st) {
        $st->bind_param('ii', $admin_id, $id);
        $st->execute();
        $q = $st->affected_rows > 0;
        $st->close();
      } else { $q = false; }
      if ($q) {
        echo json_encode(['status' => 'success', 'message' => 'Absensi manual ditolak.']);
      } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menolak.']);
      }
      break;
  }
}
