<?php
session_start();
$admin_id = $_SESSION['admin_id'] ?? '';
$level_id = $_SESSION['level'] ?? '';

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $aColumns = ['e_izin.id', 'user.nisn', 'user.nama_lengkap', 'kelas.nama_kelas', 'e_izin.jenis_izin', 'e_izin.tanggal', 'e_izin.status_izin', 'e_izin.status_izin_wali', 'e_izin.konfirmasi', 'e_izin.time_keluar', 'e_izin.time_kembali', 'e_izin.time_pulang'];
  $sIndexColumn = "e_izin.id";
  $sTable = "e_izin";

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  $sLimit = "";
  if (isset($_POST['start']) && $_POST['length'] != -1) {
    $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
  }

  $sOrder = "ORDER BY e_izin.id DESC";

  $sWhere = "";

  // Filter berdasarkan kelas: deteksi wali kelas atau gunakan parameter eksplisit
  $kelas_id = '';
  if (isset($current_user) && (isset($current_user['ptk_id']) || isset($current_user['admin_id']))) {
    $ptk_id = isset($current_user['ptk_id']) ? $current_user['ptk_id'] : '';
    $admin_id_user = isset($current_user['admin_id']) ? $current_user['admin_id'] : '';

    if (!empty($ptk_id)) {
      $q_wali = $gaSql['link']->query("SELECT kelas_id FROM kelas WHERE wali_kelas_ptk_id='" . mysqli_real_escape_string($gaSql['link'], $ptk_id) . "' LIMIT 1");
      if ($q_wali && $r_w = $q_wali->fetch_assoc()) {
        $kelas_id = $r_w['kelas_id'];
      }
    }

    // fallback: jika tersimpan sebagai admin_id di tabel kelas
    if ($kelas_id === '' && !empty($admin_id_user)) {
      $q_wali2 = $gaSql['link']->query("SELECT kelas_id FROM kelas WHERE wali_kelas_admin_id='" . mysqli_real_escape_string($gaSql['link'], $admin_id_user) . "' LIMIT 1");
      if ($q_wali2 && $r2 = $q_wali2->fetch_assoc()) {
        $kelas_id = $r2['kelas_id'];
      }
    }
  }

  // Override eksplisit dari client (POST memiliki prioritas)
  if ((isset($_POST['kelas']) && $_POST['kelas'] != '') || (isset($_GET['kelas']) && $_GET['kelas'] != '')) {
    $req_kelas = isset($_POST['kelas']) ? mysqli_real_escape_string($gaSql['link'], $_POST['kelas']) : mysqli_real_escape_string($gaSql['link'], $_GET['kelas']);
    if (!empty($req_kelas)) {
      $kelas_id = $req_kelas;
    }
  }

  // Terapkan filter kelas jika ada
  if ($kelas_id != '') {
    if ($sWhere == "") {
      $sWhere = "WHERE user.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
    } else {
      $sWhere .= " AND user.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
    }
  }

  $sQuery = "
    SELECT SQL_CALC_FOUND_ROWS 
      e_izin.id,
      user.nisn,
      user.nama_lengkap,
      kelas.nama_kelas,
      e_izin.jenis_izin,
      e_izin.tanggal,
      e_izin.status_izin,
      e_izin.status_izin_wali,
      e_izin.konfirmasi,
      e_izin.time_keluar,
      e_izin.time_kembali,
      e_izin.time_pulang,
      e_izin.alasan_penolakan,
      e_izin.alasan_penolakan_wali
    FROM e_izin
    LEFT JOIN user ON e_izin.user_id = user.user_id
    LEFT JOIN kelas ON user.kelas = kelas.kelas_id
    $sWhere
    $sOrder
    $sLimit
  ";

  $rResult = $gaSql['link']->query($sQuery);

  $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
  $iTotal = $gaSql['link']->query("SELECT COUNT($sIndexColumn) FROM $sTable")->fetch_row()[0];

  $output = [
    "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
    "recordsTotal" => $iTotal,
    "recordsFiltered" => $iFilteredTotal,
    "data" => []
  ];

  $no = $_POST['start'] + 1;

  while ($aRow = $rResult->fetch_assoc()) {
    $badge = ($aRow['status_izin'] == 'Disetujui') ? 'success' : (($aRow['status_izin'] == 'Ditolak') ? 'danger' : 'warning');
    $badge_wali = ($aRow['status_izin_wali'] == 'Disetujui') ? 'success' : (($aRow['status_izin_wali'] == 'Ditolak') ? 'danger' : 'warning');
    $row = [];

    $row[] = '<div class="text-center">' . $no++ . '</div>';

    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['nisn']) . '</div>';
    $row[] = '<div class="text-center font-weight-bold">' . htmlspecialchars($aRow['nama_lengkap']) . '</div>';
    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['nama_kelas']) . '</div>';
    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['jenis_izin']) . '</div>';
    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['tanggal']) . '</div>';
    $row[] = '<div class="text-center"><span class="badge badge-' . $badge . '">' . $aRow['status_izin'] . '</span></div>';
    $row[] = '<div class="text-center"><span class="badge badge-' . $badge_wali . '">' . $aRow['status_izin_wali'] . '</span></div>';

    // Konfirmasi: single badge (no times/duration)
    $konf = isset($aRow['konfirmasi']) ? trim($aRow['konfirmasi']) : '';
    $konf_label = $konf === '' ? 'Belum' : htmlspecialchars(ucfirst($konf));
    $konf_badge = 'secondary';
    if (stripos($konf, 'keluar') !== false) $konf_badge = 'info';
    elseif (stripos($konf, 'kembali') !== false) $konf_badge = 'success';
    elseif (stripos($konf, 'pulang') !== false) $konf_badge = 'dark';
    $row[] = '<div class="text-center">' . ($konf === '' ? '-' : '<span class="badge badge-' . $konf_badge . '">' . $konf_label . '</span>') . '</div>';

    $row[] = '
    <div class="text-center">
      <a href="javascript:void(0)" class="btn-view-detail btn-tooltip mx-1" data-id="' . $aRow['id'] . '" title="Lihat Detail">
        <i class="fas fa-search text-info"></i>
      </a>' .
      (($aRow['status_izin'] == 'Ditolak' && !empty($aRow['alasan_penolakan'])) ? '
      <a href="javascript:void(0)" class="btn-edit-catatan btn-tooltip mx-1" data-id="' . $aRow['id'] . '" data-catatan="' . htmlspecialchars($aRow['alasan_penolakan']) . '" title="Lihat Catatan">
        <i class="fas fa-comment text-warning"></i>
      </a>' : '') .
      (($aRow['status_izin_wali'] == 'Ditolak' && !empty($aRow['alasan_penolakan_wali'])) ? '
      <a href="javascript:void(0)" class="btn-view-catatan btn-tooltip mx-1" data-catatan="' . htmlspecialchars($aRow['alasan_penolakan_wali']) . '" title="Catatan Wali">
        <i class="fas fa-user text-warning"></i>
      </a>' : '') .
      '<a href="javascript:void(0)" class="btn-delete btn-tooltip mx-1" data-id="' . $aRow['id'] . '" title="Hapus">
        <i class="fas fa-trash text-danger"></i>
      </a>
    </div>';

    $output['data'][] = $row;
  }


  echo json_encode($output);
}
