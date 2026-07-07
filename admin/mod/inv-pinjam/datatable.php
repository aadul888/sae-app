<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $sTable = "inv_pinjam";
  $sIndexColumn = "inv_pinjam.pinjam_id";

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  $sLimit = "";
  if (isset($_POST['start']) && $_POST['length'] != -1) {
    $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
  }

  $sOrder = "ORDER BY inv_pinjam.pinjam_id DESC";
  $sWhere = "";

  // Filter status
  if (!empty($_POST['status_filter'])) {
    $status = mysqli_real_escape_string($gaSql['link'], $_POST['status_filter']);
    $sWhere = "WHERE inv_pinjam.status = '$status'";
  }

  $sQuery = "
    SELECT SQL_CALC_FOUND_ROWS 
      inv_pinjam.pinjam_id,
      COALESCE(user.nama_lengkap, inv_pinjam.nama_peminjam) AS nama_lengkap,
      user.nisn,
      kelas.nama_kelas,
      inv_barang.nama_barang,
      inv_pinjam.jumlah_pinjam,
      inv_pinjam.tanggal_pinjam,
      inv_pinjam.tanggal_kembali,
      inv_pinjam.tanggal_dikembalikan,
      inv_pinjam.status,
      inv_pinjam.keterangan
    FROM inv_pinjam
    LEFT JOIN user ON inv_pinjam.user_id = user.user_id
    LEFT JOIN kelas ON inv_pinjam.kelas_id = kelas.kelas_id
    LEFT JOIN inv_barang ON inv_pinjam.barang_id = inv_barang.barang_id
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
    $status_badge = 'warning';
    $status_icon = 'hand-holding';
    if ($aRow['status'] === 'Dikembalikan') { $status_badge = 'success'; $status_icon = 'check-circle'; }
    elseif ($aRow['status'] === 'Terlambat') { $status_badge = 'danger'; $status_icon = 'exclamation-triangle'; }
    elseif ($aRow['status'] === 'Hilang') { $status_badge = 'dark'; $status_icon = 'ban'; }

    $tgl_kembali = '-';
    if (!empty($aRow['tanggal_dikembalikan'])) {
      $tgl_kembali = $aRow['tanggal_dikembalikan'];
    } elseif (!empty($aRow['tanggal_kembali'])) {
      $tgl_kembali = '<small class="text-muted">' . $aRow['tanggal_kembali'] . ' <em>(rencana)</em></small>';
    }

    $aksi_btn = '<a href="javascript:void(0)" class="table-action table-action-info btn-detail-pinjam btn-tooltip" data-id="' . $aRow['pinjam_id'] . '" data-toggle="tooltip" title="Detail"><i class="fas fa-search"></i></a>';
    if ($aRow['status'] === 'Dipinjam') {
      $aksi_btn .= '<a href="javascript:void(0)" class="table-action table-action-success btn-kembalikan btn-tooltip" data-id="' . $aRow['pinjam_id'] . '" data-keterangan="' . htmlspecialchars($aRow['keterangan'] ?? '') . '" data-toggle="tooltip" title="Kembalikan"><i class="fas fa-undo"></i></a>';
    }
    $aksi_btn .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-hapus-pinjam btn-tooltip" data-id="' . $aRow['pinjam_id'] . '" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a>';

    $row = [];
    $row[] = '<div class="text-center">' . $no++ . '</div>';
    $row[] = '<div>' . htmlspecialchars($aRow['nama_lengkap'] ?? '-') . '<br><small class="text-muted">' . htmlspecialchars($aRow['nisn'] ?? '') . '</small></div>';
    $row[] = '<div class="text-center"><span class="badge badge-primary">' . htmlspecialchars($aRow['nama_kelas'] ?? '-') . '</span></div>';
    $row[] = '<div>' . htmlspecialchars($aRow['nama_barang'] ?? '-') . '</div>';
    $row[] = '<div class="text-center">' . intval($aRow['jumlah_pinjam']) . '</div>';
    $row[] = '<div class="text-center"><small>' . $aRow['tanggal_pinjam'] . '</small></div>';
    $row[] = '<div class="text-center">' . $tgl_kembali . '</div>';
    $row[] = '<div class="text-center"><span class="badge badge-' . $status_badge . '"><i class="fas fa-' . $status_icon . ' mr-1"></i>' . htmlspecialchars($aRow['status']) . '</span></div>';
    $row[] = '<div class="text-center">' . $aksi_btn . '</div>';

    $output['data'][] = $row;
  }

  echo json_encode($output);
}
