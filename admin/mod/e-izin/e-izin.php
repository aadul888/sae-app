<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 23;
  include __DIR__ . '/../check_role.php';

  // Load current user (if available) so we can detect wali kelas for class-based filtering
  if (file_exists(__DIR__ . '/../../login/user.php')) {
    require_once __DIR__ . '/../../login/user.php';
  }

  // compute summary counts from e_izin table (server-side) so no extra endpoint needed
  // Apply optional kelas filter: detect wali via current_user or accept explicit kelas param
  $kelas_id = '';
  if (isset($current_user) && (isset($current_user['ptk_id']) || isset($current_user['admin_id']))) {
    $ptk_id = isset($current_user['ptk_id']) ? $current_user['ptk_id'] : '';
    $admin_id_user = isset($current_user['admin_id']) ? $current_user['admin_id'] : '';
    if (!empty($ptk_id)) {
      $q_wali = $connection->query("SELECT kelas_id FROM kelas WHERE wali_kelas_ptk_id='" . $connection->real_escape_string($ptk_id) . "' LIMIT 1");
      if ($q_wali && $rw = $q_wali->fetch_assoc()) {
        $kelas_id = $rw['kelas_id'];
      }
    }
    if ($kelas_id === '' && !empty($admin_id_user)) {
      $q_wali2 = $connection->query("SELECT kelas_id FROM kelas WHERE wali_kelas_admin_id='" . $connection->real_escape_string($admin_id_user) . "' LIMIT 1");
      if ($q_wali2 && $r2 = $q_wali2->fetch_assoc()) {
        $kelas_id = $r2['kelas_id'];
      }
    }
  }

  // Explicit override from client (GET/POST)
  if ((isset($_POST['kelas']) && $_POST['kelas'] != '') || (isset($_GET['kelas']) && $_GET['kelas'] != '')) {
    $req_kelas = isset($_POST['kelas']) ? $_POST['kelas'] : $_GET['kelas'];
    if ($req_kelas !== '') $kelas_id = $connection->real_escape_string($req_kelas);
  }

  // normalize to integer if numeric
  if ($kelas_id !== '') {
    $kelas_id = intval($kelas_id);
  }
  $cnt_total = 0;
  $cnt_approved = 0;
  $cnt_rejected = 0;
  $cnt_pending = 0;
  $cnt_petugas = 0;
  $cnt_wali = 0;
  if (isset($connection)) {
    // Build queries with optional kelas filtering (join user when kelas filter present)
    if (empty($kelas_id)) {
      $q = "SELECT COUNT(*) AS cnt FROM e_izin";
    } else {
      $k = intval($kelas_id);
      $q = "SELECT COUNT(*) AS cnt FROM e_izin INNER JOIN user ON e_izin.user_id = user.user_id WHERE user.kelas='" . $k . "'";
    }
    if ($r = $connection->query($q)) {
      $row = $r->fetch_assoc();
      $cnt_total = intval($row['cnt']);
    }

    if (empty($kelas_id)) {
      $q = "SELECT COUNT(*) AS cnt FROM e_izin WHERE LOWER(COALESCE(status_izin,'')) = 'disetujui'";
    } else {
      $k = intval($kelas_id);
      $q = "SELECT COUNT(*) AS cnt FROM e_izin INNER JOIN user ON e_izin.user_id = user.user_id WHERE LOWER(COALESCE(e_izin.status_izin,'')) = 'disetujui' AND user.kelas='" . $k . "'";
    }
    if ($r = $connection->query($q)) {
      $row = $r->fetch_assoc();
      $cnt_approved = intval($row['cnt']);
    }

    if (empty($kelas_id)) {
      $q = "SELECT COUNT(*) AS cnt FROM e_izin WHERE LOWER(COALESCE(status_izin,'')) = 'ditolak'";
    } else {
      $k = intval($kelas_id);
      $q = "SELECT COUNT(*) AS cnt FROM e_izin INNER JOIN user ON e_izin.user_id = user.user_id WHERE LOWER(COALESCE(e_izin.status_izin,'')) = 'ditolak' AND user.kelas='" . $k . "'";
    }
    if ($r = $connection->query($q)) {
      $row = $r->fetch_assoc();
      $cnt_rejected = intval($row['cnt']);
    }

    if (empty($kelas_id)) {
      $q = "SELECT COUNT(*) AS cnt FROM e_izin WHERE LOWER(COALESCE(status_izin,'')) NOT IN ('disetujui','ditolak')";
    } else {
      $k = intval($kelas_id);
      $q = "SELECT COUNT(*) AS cnt FROM e_izin INNER JOIN user ON e_izin.user_id = user.user_id WHERE LOWER(COALESCE(e_izin.status_izin,'')) NOT IN ('disetujui','ditolak') AND user.kelas='" . $k . "'";
    }
    if ($r = $connection->query($q)) {
      $row = $r->fetch_assoc();
      $cnt_pending = intval($row['cnt']);
    }

    if (empty($kelas_id)) {
      $q = "SELECT COUNT(DISTINCT token_security) AS cnt FROM e_izin WHERE token_security IS NOT NULL AND TRIM(token_security) <> ''";
    } else {
      $k = intval($kelas_id);
      $q = "SELECT COUNT(DISTINCT e_izin.token_security) AS cnt FROM e_izin INNER JOIN user ON e_izin.user_id = user.user_id WHERE e_izin.token_security IS NOT NULL AND TRIM(e_izin.token_security) <> '' AND user.kelas='" . $k . "'";
    }
    if ($r = $connection->query($q)) {
      $row = $r->fetch_assoc();
      $cnt_petugas = intval($row['cnt']);
    }

    if (empty($kelas_id)) {
      $q = "SELECT COUNT(DISTINCT token_wali) AS cnt FROM e_izin WHERE token_wali IS NOT NULL AND TRIM(token_wali) <> ''";
    } else {
      $k = intval($kelas_id);
      $q = "SELECT COUNT(DISTINCT e_izin.token_wali) AS cnt FROM e_izin INNER JOIN user ON e_izin.user_id = user.user_id WHERE e_izin.token_wali IS NOT NULL AND TRIM(e_izin.token_wali) <> '' AND user.kelas='" . $k . "'";
    }
    if ($r = $connection->query($q)) {
      $row = $r->fetch_assoc();
      $cnt_wali = intval($row['cnt']);
    }
  }

  switch (@$_GET['op']) {
    default:
      echo '
<!-- Header -->
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6 user-module-page">
  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats module-stats-grid" id="eizin-stat-row">
              <div class="user-stat-card user-stat-total">
                <div class="info">
                  <span class="label">Total Pengajuan</span>
                  <span class="value" id="totalEizin">' . intval($cnt_total) . '</span>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
              </div>

              <div class="user-stat-card user-stat-berkas-valid">
                <div class="info">
                  <span class="label">Disetujui</span>
                  <span class="value text-success" id="eizinApproved">' . intval($cnt_approved) . '</span>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
              </div>

              <div class="user-stat-card user-stat-belum-sesuai">
                <div class="info">
                  <span class="label">Ditolak</span>
                  <span class="value text-danger" id="eizinRejected">' . intval($cnt_rejected) . '</span>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
              </div>

              <div class="user-stat-card user-stat-belum">
                <div class="info">
                  <span class="label">Menunggu</span>
                  <span class="value text-warning" id="eizinPending">' . intval($cnt_pending) . '</span>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
              </div>

              <div class="user-stat-card user-stat-identitas">
                <div class="info">
                  <span class="label">Petugas Piket</span>
                  <span class="value" id="eizinPiket">' . ($cnt_petugas > 0 ? intval($cnt_petugas) : '-') . '</span>
                </div>
                <div class="icon"><i class="fas fa-user-shield"></i></div>
              </div>

              <div class="user-stat-card user-stat-wali">
                <div class="info">
                  <span class="label">Wali Kelas</span>
                  <span class="value" id="eizinWali">' . ($cnt_wali > 0 ? intval($cnt_wali) : '-') . '</span>
                </div>
                <div class="icon"><i class="fas fa-user-tie"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="card user-table-panel module-table-card pb-2">
    <div class="card-header py-3 px-3 module-table-header">
      <div class="module-header-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Daftar Usulan e-Izin Siswa</h4>
          <small class="text-muted">Kelola data pengajuan izin siswa</small>
        </div>
      </div>
    </div>
    <div class="table-responsive">';
      if ($data_role['lihat'] == 'Y') {
        echo '
          <table class="table align-items-center table-flush table-striped datatable" width="auto">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="4">No</th>
                <th class="text-center" width="5">NISN</th>
                <th class="text-center">Nama Lengkap</th>
                <th class="text-center">Kelas</th>
                <th class="text-center">Jenis Izin</th>
                <th class="text-center">Tanggal</th>
                <th class="text-center">Petugas Piket</th>
                <th class="text-center">Wali Kelas</th>
                <th class="text-center">Konfirmasi</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>';
      } else {
        hak_akses();
      }
      echo '
    </div>
  </div>
</div>';

      echo '
<!-- Modal Detail Izin -->
<div class="modal fade" id="modal-detail" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailModalLabel">Detail Izin</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="detail-content"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-edit-catatan" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-edit-catatan">
        <div class="modal-header">
          <h5 class="modal-title">Edit Catatan Penolakan</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-id">
          <textarea class="form-control" id="edit-catatan" rows="4" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>';
      break;
  }
}
