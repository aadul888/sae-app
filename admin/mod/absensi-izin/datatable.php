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

  $modul_id = 10;
  include __DIR__ . '/../check_role.php';
  if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
    echo json_encode([
      "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
      "recordsTotal" => 0,
      "recordsFiltered" => 0,
      "data" => [],
      "statusStat" => [
        "total" => 0,
        "menunggu" => 0,
        "disetujui" => 0,
        "ditolak" => 0
      ]
    ]);
    exit;
  }

  $aColumns = ['izin.id', 'user.nisn', 'user.nama_lengkap', 'kelas.nama_kelas', 'izin.jenis_izin', 'izin.tanggal_mulai', 'izin.tanggal_selesai', 'izin.status_izin'];
  $sIndexColumn = "izin.id";
  $sTable = "izin";

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  $is_siswa = ($level_id == 2);
  $is_pembimbing = ($level_id == 4);
  $user_id = $_SESSION['siswa_id'] ?? '';

  $status_filter = isset($_POST['filter_status']) ? trim($_POST['filter_status']) : '';

  // LIMIT
  $sLimit = "";
  if (isset($_POST['start']) && $_POST['length'] != -1) {
    $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
  }

  // ORDER
  $sOrder = "ORDER BY izin.id DESC";

  // WHERE
  $where_parts = [];
  if ($status_filter !== '') {
    $where_parts[] = "izin.status_izin='" . $gaSql['link']->real_escape_string($status_filter) . "'";
  }

  $sWhere = '';
  if (!empty($where_parts)) {
    $sWhere = 'WHERE ' . implode(' AND ', $where_parts);
  }


  // Query utama
  $sQuery = "
    SELECT SQL_CALC_FOUND_ROWS 
      izin.id,
      user.nisn,
      user.nama_lengkap,
      kelas.nama_kelas,
      izin.jenis_izin,
      izin.tanggal_mulai,
      izin.tanggal_selesai,
      izin.status_izin,
      izin.alasan_penolakan
    FROM izin
    LEFT JOIN user ON izin.user_id = user.user_id
    LEFT JOIN kelas ON user.kelas = kelas.kelas_id
    $sWhere
    $sOrder
    $sLimit
  ";

  $rResult = $gaSql['link']->query($sQuery);

  // TOTALS
  $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
  $iTotal = $gaSql['link']->query("SELECT COUNT($sIndexColumn) FROM $sTable")->fetch_row()[0];

  $output = [
    "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
    "recordsTotal" => $iTotal,
    "recordsFiltered" => $iFilteredTotal,
    "data" => []
  ];

  $statsWhere = $sWhere;
  $statsQuery = "
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN izin.status_izin='Menunggu' THEN 1 ELSE 0 END) AS menunggu,
      SUM(CASE WHEN izin.status_izin='Disetujui' THEN 1 ELSE 0 END) AS disetujui,
      SUM(CASE WHEN izin.status_izin='Ditolak' THEN 1 ELSE 0 END) AS ditolak
    FROM izin
    LEFT JOIN user ON izin.user_id = user.user_id
    LEFT JOIN kelas ON user.kelas = kelas.kelas_id
    $statsWhere
  ";
  $statsResult = $gaSql['link']->query($statsQuery);
  $stats = [
    "total" => 0,
    "menunggu" => 0,
    "disetujui" => 0,
    "ditolak" => 0
  ];
  if ($statsResult && $rowStats = $statsResult->fetch_assoc()) {
    $stats['total'] = isset($rowStats['total']) ? (int)$rowStats['total'] : 0;
    $stats['menunggu'] = isset($rowStats['menunggu']) ? (int)$rowStats['menunggu'] : 0;
    $stats['disetujui'] = isset($rowStats['disetujui']) ? (int)$rowStats['disetujui'] : 0;
    $stats['ditolak'] = isset($rowStats['ditolak']) ? (int)$rowStats['ditolak'] : 0;
  }
  $output['statusStat'] = $stats;

  $no = $_POST['start'] + 1;

  while ($aRow = $rResult->fetch_assoc()) {
    $badge = ($aRow['status_izin'] == 'Disetujui') ? 'success' : (($aRow['status_izin'] == 'Ditolak') ? 'danger' : 'warning');
    $row = [];

    $row[] = '<div class="text-center">' . $no++ . '</div>';

    if ($is_siswa) {
      // ✅ Hanya 4 kolom sesuai untuk siswa
      $row[] = '<div class="text-center">' . htmlspecialchars($aRow['jenis_izin']) . '</div>';
      $row[] = '<div class="text-center">' . htmlspecialchars($aRow['tanggal_mulai']) . ' s.d ' . htmlspecialchars($aRow['tanggal_selesai']) . '</div>';
      $row[] = '<div class="text-center"><span class="badge badge-' . $badge . '">' . $aRow['status_izin'] . '</span></div>';
    } else {
      // Kolom untuk admin tetap
      $row[] = '<div class="text-center">' . htmlspecialchars($aRow['nisn']) . '</div>';
      $row[] = '<div class="text-center font-weight-bold">' . htmlspecialchars($aRow['nama_lengkap']) . '</div>';
      $row[] = '<div class="text-center">' . htmlspecialchars($aRow['nama_kelas']) . '</div>';
      $row[] = '<div class="text-center">' . htmlspecialchars($aRow['jenis_izin']) . '</div>';
      $row[] = '<div class="text-center">' . htmlspecialchars($aRow['tanggal_mulai']) . ' s.d ' . htmlspecialchars($aRow['tanggal_selesai']) . '</div>';
      $row[] = '<div class="text-center"><span class="badge badge-' . $badge . '">' . $aRow['status_izin'] . '</span></div>';
      $row[] = '
      <div class="text-center">
        <a href="javascript:void(0)" class="table-action table-action-info btn-view-detail btn-tooltip" data-id="' . $aRow['id'] . '" data-toggle="tooltip" title="Lihat Detail"><i class="fas fa-search"></i></a>' .
        (($aRow['status_izin'] == 'Ditolak' && !empty($aRow['alasan_penolakan'])) ? '
        <a href="javascript:void(0)" class="table-action table-action-warning btn-edit-catatan btn-tooltip" data-id="' . $aRow['id'] . '" data-catatan="' . htmlspecialchars($aRow['alasan_penolakan']) . '" data-toggle="tooltip" title="Lihat Catatan"><i class="fas fa-comment"></i></a>' : '') . '
        <a href="javascript:void(0)" class="table-action table-action-delete btn-delete btn-tooltip" data-id="' . $aRow['id'] . '" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a>
      </div>';
    }

    $output['data'][] = $row;
  }


  echo json_encode($output);
}
