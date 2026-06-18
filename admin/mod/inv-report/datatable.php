<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $sTable = "inv_laporan";
  $sIndexColumn = "inv_laporan.laporan_id";

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  $sLimit = "";
  if (isset($_POST['start']) && $_POST['length'] != -1) {
    $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
  }

  $sOrder = "ORDER BY inv_laporan.laporan_id DESC";
  $sWhere = "";

  // Filter kelas
  if (!empty($_POST['kelas'])) {
    $kelas = mysqli_real_escape_string($gaSql['link'], $_POST['kelas']);
    $sWhere = "WHERE inv_laporan.kelas_id = '$kelas'";
  }

  // Filter status
  if (!empty($_POST['status_filter'])) {
    $status = mysqli_real_escape_string($gaSql['link'], $_POST['status_filter']);
    if ($sWhere == "") {
      $sWhere = "WHERE inv_laporan.status = '$status'";
    } else {
      $sWhere .= " AND inv_laporan.status = '$status'";
    }
  }

  $sQuery = "
    SELECT SQL_CALC_FOUND_ROWS 
      inv_laporan.laporan_id,
      kelas.nama_kelas,
      inv_laporan.jenis_laporan,
      inv_laporan.deskripsi,
      inv_laporan.prioritas,
      inv_laporan.status,
      inv_laporan.catatan_admin,
      inv_barang.nama_barang,
      user.nama_lengkap,
      inv_laporan.tanggal_laporan
    FROM inv_laporan
    LEFT JOIN kelas ON inv_laporan.kelas_id = kelas.kelas_id
    LEFT JOIN inv_barang ON inv_laporan.barang_id = inv_barang.barang_id
    LEFT JOIN user ON inv_laporan.user_id = user.user_id
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

  $no = intval($_POST['start'] ?? 0) + 1;

  while ($aRow = $rResult->fetch_assoc()) {
    $jenis_badge = 'danger';
    if ($aRow['jenis_laporan'] === 'Kehilangan') $jenis_badge = 'dark';
    elseif ($aRow['jenis_laporan'] === 'Kebutuhan') $jenis_badge = 'info';

    $prioritas_badge = 'secondary';
    if ($aRow['prioritas'] === 'Sedang') $prioritas_badge = 'info';
    elseif ($aRow['prioritas'] === 'Tinggi') $prioritas_badge = 'warning';
    elseif ($aRow['prioritas'] === 'Urgent') $prioritas_badge = 'danger';

    $status_badge = 'warning';
    if ($aRow['status'] === 'Diproses') $status_badge = 'info';
    elseif ($aRow['status'] === 'Selesai') $status_badge = 'success';
    elseif ($aRow['status'] === 'Ditolak') $status_badge = 'danger';

    $row = [];
    $row[] = '<div class="text-center">' . $no++ . '</div>';
    $row[] = '<div class="text-center"><span class="badge badge-primary">' . htmlspecialchars($aRow['nama_kelas'] ?? '-') . '</span></div>';
    $row[] = '<div class="text-center"><span class="badge badge-' . $jenis_badge . '">' . htmlspecialchars($aRow['jenis_laporan']) . '</span>' . (!empty($aRow['nama_barang']) ? '<br><small class="text-muted">' . htmlspecialchars($aRow['nama_barang']) . '</small>' : '') . '</div>';
    $row[] = '<div>' . htmlspecialchars(mb_strimwidth($aRow['deskripsi'], 0, 60, '...')) . '</div>';
    $row[] = '<div class="text-center"><span class="badge badge-' . $prioritas_badge . '">' . htmlspecialchars($aRow['prioritas']) . '</span></div>';
    $row[] = '<div class="text-center"><span class="badge badge-' . $status_badge . '">' . htmlspecialchars($aRow['status']) . '</span></div>';
    $row[] = '<div class="text-center"><small>' . htmlspecialchars($aRow['nama_lengkap'] ?? '-') . '</small></div>';
    $row[] = '<div class="text-center"><small>' . htmlspecialchars($aRow['tanggal_laporan']) . '</small></div>';
    $row[] = '<div class="text-center"><a href="javascript:void(0)" class="table-action table-action-info btn-detail-laporan btn-tooltip" data-id="' . $aRow['laporan_id'] . '" data-toggle="tooltip" title="Detail"><i class="fas fa-search"></i></a><a href="javascript:void(0)" class="table-action table-action-primary btn-proses-laporan btn-tooltip" data-id="' . $aRow['laporan_id'] . '" data-status="' . htmlspecialchars($aRow['status']) . '" data-catatan="' . htmlspecialchars($aRow['catatan_admin'] ?? '') . '" data-toggle="tooltip" title="Proses"><i class="fas fa-cog"></i></a><a href="javascript:void(0)" class="table-action table-action-delete btn-hapus-laporan btn-tooltip" data-id="' . $aRow['laporan_id'] . '" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a></div>';

    $output['data'][] = $row;
  }

  echo json_encode($output);
}
