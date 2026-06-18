<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $aColumns = ['inv_kelas.inv_id', 'kelas.nama_kelas', 'inv_barang.nama_barang', 'inv_kelas.jumlah', 'inv_kelas.kondisi', 'inv_kelas.keterangan', 'user.nama_lengkap', 'inv_kelas.tanggal_input'];
  $sIndexColumn = "inv_kelas.inv_id";
  $sTable = "inv_kelas";

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  $sLimit = "";
  if (isset($_POST['start']) && $_POST['length'] != -1) {
    $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
  }

  $sOrder = "ORDER BY inv_kelas.inv_id DESC";

  $sWhere = "";

  // Filter kelas
  if (!empty($_POST['kelas']) || !empty($_GET['kelas'])) {
    $req_kelas = !empty($_POST['kelas']) ? mysqli_real_escape_string($gaSql['link'], $_POST['kelas']) : mysqli_real_escape_string($gaSql['link'], $_GET['kelas']);
    if (!empty($req_kelas)) {
      $sWhere = "WHERE inv_kelas.kelas_id = '" . $req_kelas . "'";
    }
  }

  // Filter kondisi
  if (!empty($_POST['kondisi'])) {
    $kondisi = mysqli_real_escape_string($gaSql['link'], $_POST['kondisi']);
    if ($sWhere == "") {
      $sWhere = "WHERE inv_kelas.kondisi = '$kondisi'";
    } else {
      $sWhere .= " AND inv_kelas.kondisi = '$kondisi'";
    }
  }

  $sQuery = "
    SELECT SQL_CALC_FOUND_ROWS 
      inv_kelas.inv_id,
      kelas.nama_kelas,
      inv_barang.nama_barang,
      inv_barang.kode_barang,
      inv_kategori.nama_kategori,
      inv_kelas.jumlah,
      inv_kelas.kondisi,
      inv_kelas.keterangan,
      inv_kelas.foto,
      inv_kelas.tahun_ajaran,
      user.nama_lengkap,
      user.nisn,
      inv_kelas.tanggal_input
    FROM inv_kelas
    LEFT JOIN kelas ON inv_kelas.kelas_id = kelas.kelas_id
    LEFT JOIN inv_barang ON inv_kelas.barang_id = inv_barang.barang_id
    LEFT JOIN inv_kategori ON inv_barang.kategori_id = inv_kategori.kategori_id
    LEFT JOIN user ON inv_kelas.user_id = user.user_id
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
    $kondisi_badge = 'success';
    if ($aRow['kondisi'] === 'Rusak Ringan') $kondisi_badge = 'warning';
    elseif ($aRow['kondisi'] === 'Rusak Berat') $kondisi_badge = 'danger';
    elseif ($aRow['kondisi'] === 'Hilang') $kondisi_badge = 'dark';

    $row = [];
    $row[] = '<div class="text-center">' . $no++ . '</div>';
    $row[] = '<div class="text-center"><span class="badge badge-primary">' . htmlspecialchars($aRow['nama_kelas'] ?? '-') . '</span></div>';
    $row[] = '<div><strong>' . htmlspecialchars($aRow['nama_barang']) . '</strong><br><small class="text-muted">' . htmlspecialchars($aRow['nama_kategori'] ?? '') . '</small></div>';
    $row[] = '<div class="text-center">' . intval($aRow['jumlah']) . '</div>';
    $row[] = '<div class="text-center"><span class="badge badge-' . $kondisi_badge . '">' . htmlspecialchars($aRow['kondisi']) . '</span></div>';
    $row[] = '<div>' . htmlspecialchars(mb_strimwidth($aRow['keterangan'] ?? '-', 0, 50, '...')) . '</div>';
    $row[] = '<div class="text-center"><small>' . htmlspecialchars($aRow['nama_lengkap'] ?? '-') . '</small></div>';
    $row[] = '<div class="text-center"><small>' . htmlspecialchars($aRow['tanggal_input']) . '</small></div>';
    $row[] = '<div class="text-center"><a href="javascript:void(0)" class="table-action table-action-info btn-detail-inv btn-tooltip" data-id="' . $aRow['inv_id'] . '" data-toggle="tooltip" title="Detail"><i class="fas fa-search"></i></a><a href="javascript:void(0)" class="table-action table-action-delete btn-hapus-inv btn-tooltip" data-id="' . $aRow['inv_id'] . '" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a></div>';

    $output['data'][] = $row;
  }

  echo json_encode($output);
}
