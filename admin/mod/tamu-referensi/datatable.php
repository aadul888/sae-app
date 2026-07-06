<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
  $gaSql['link']->set_charset('utf8');

  $jenis = ($_POST['jenis'] ?? $_GET['jenis'] ?? 'instansi') === 'tujuan' ? 'tujuan' : 'instansi';

  $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
  $start = intval($_POST['start'] ?? 0);
  $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
  $sLimit = ($length != -1) ? "LIMIT $start, $length" : "";
  $search = isset($_POST['search']['value']) ? $gaSql['link']->real_escape_string($_POST['search']['value']) : '';

  if ($jenis === 'instansi') {
    $table = 'tamu_instansi';
    $aColumns = ['id', 'nama', 'jenis', 'telepon', 'alamat', 'active'];
    $sWhere = $search !== '' ? "WHERE (nama LIKE '%$search%' OR jenis LIKE '%$search%' OR telepon LIKE '%$search%' OR alamat LIKE '%$search%')" : '';
  } else {
    $table = 'tamu_tujuan';
    $aColumns = ['id', 'nama', 'keterangan', 'active'];
    $sWhere = $search !== '' ? "WHERE (nama LIKE '%$search%' OR keterangan LIKE '%$search%')" : '';
  }

  $sOrder = "ORDER BY nama ASC";
  if (isset($_POST['order'][0]['column'])) {
    $col_idx = intval($_POST['order'][0]['column']);
    $col_dir = (($_POST['order'][0]['dir'] ?? 'asc') === 'asc') ? 'ASC' : 'DESC';
    if (isset($aColumns[$col_idx])) {
      $sOrder = "ORDER BY " . $aColumns[$col_idx] . " " . $col_dir;
    }
  }

  $cols = implode(', ', $aColumns);
  $rResult = $gaSql['link']->query("SELECT SQL_CALC_FOUND_ROWS $cols FROM `$table` $sWhere $sOrder $sLimit");
  $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
  $iTotal = $gaSql['link']->query("SELECT COUNT(id) FROM `$table`")->fetch_row()[0];

  $output = [
    "draw" => $draw,
    "recordsTotal" => intval($iTotal),
    "recordsFiltered" => intval($iFilteredTotal),
    "data" => []
  ];

  $no = $start + 1;
  while ($aRow = $rResult->fetch_assoc()) {
    $badge = ($aRow['active'] == 'Y')
      ? '<span class="badge badge-success">Aktif</span>'
      : '<span class="badge badge-danger">Tidak Aktif</span>';
    $row = [];
    $row[] = '<div class="text-center">' . $no++ . '</div>';

    if ($jenis === 'instansi') {
      $row[] = '<div class="font-weight-bold">' . htmlspecialchars($aRow['nama']) . '</div>';
      $row[] = '<div class="text-center">' . htmlspecialchars($aRow['jenis'] ?: '-') . '</div>';
      $row[] = '<div class="text-center">' . htmlspecialchars($aRow['telepon'] ?: '-') . '</div>';
      $row[] = '<div>' . htmlspecialchars($aRow['alamat'] ?: '-') . '</div>';
      $row[] = '<div class="text-center">' . $badge . '</div>';
      $actions = '<div class="text-center">';
      $actions .= '<a href="javascript:void(0)" class="table-action table-action-warning btn-edit-instansi" '
        . 'data-id="' . $aRow['id'] . '" '
        . 'data-nama="' . htmlspecialchars($aRow['nama'], ENT_QUOTES) . '" '
        . 'data-jenis="' . htmlspecialchars($aRow['jenis'] ?? '', ENT_QUOTES) . '" '
        . 'data-telepon="' . htmlspecialchars($aRow['telepon'] ?? '', ENT_QUOTES) . '" '
        . 'data-alamat="' . htmlspecialchars($aRow['alamat'] ?? '', ENT_QUOTES) . '" '
        . 'data-active="' . $aRow['active'] . '" title="Edit"><i class="fas fa-edit"></i></a>';
      $actions .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-delete-instansi" data-id="' . $aRow['id'] . '" title="Hapus"><i class="fas fa-trash"></i></a>';
      $actions .= '</div>';
      $row[] = $actions;
    } else {
      $row[] = '<div class="font-weight-bold">' . htmlspecialchars($aRow['nama']) . '</div>';
      $row[] = '<div>' . htmlspecialchars($aRow['keterangan'] ?: '-') . '</div>';
      $row[] = '<div class="text-center">' . $badge . '</div>';
      $actions = '<div class="text-center">';
      $actions .= '<a href="javascript:void(0)" class="table-action table-action-warning btn-edit-tujuan" '
        . 'data-id="' . $aRow['id'] . '" '
        . 'data-nama="' . htmlspecialchars($aRow['nama'], ENT_QUOTES) . '" '
        . 'data-keterangan="' . htmlspecialchars($aRow['keterangan'] ?? '', ENT_QUOTES) . '" '
        . 'data-active="' . $aRow['active'] . '" title="Edit"><i class="fas fa-edit"></i></a>';
      $actions .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-delete-tujuan" data-id="' . $aRow['id'] . '" title="Hapus"><i class="fas fa-trash"></i></a>';
      $actions .= '</div>';
      $row[] = $actions;
    }
    $output['data'][] = $row;
  }

  header('Content-Type: application/json');
  echo json_encode($output);
}
