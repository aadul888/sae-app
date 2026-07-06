<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $aColumns = ['m.mapel_id', 'm.kode_mapel', 'm.nama_mapel', 'a.fullname', 'm.aktif'];
  $sIndexColumn = "m.mapel_id";

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  $sLimit = "";
  if (isset($_POST['start']) && $_POST['length'] != -1) {
    $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
  }

  $sOrder = "";
  if (isset($_POST['order'])) {
    $col_idx = intval($_POST['order'][0]['column']);
    $col_dir = ($_POST['order'][0]['dir'] === 'asc') ? 'ASC' : 'DESC';
    if (isset($aColumns[$col_idx])) {
      $sOrder = "ORDER BY " . $aColumns[$col_idx] . " " . $col_dir;
    }
  }
  if (empty($sOrder)) $sOrder = "ORDER BY m.mapel_id DESC";

  $sWhere = "";
  if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
    $search = $gaSql['link']->real_escape_string($_POST['search']['value']);
    $sWhere = "WHERE (m.nama_mapel LIKE '%$search%' OR m.kode_mapel LIKE '%$search%' OR a.fullname LIKE '%$search%')";
  }

  $sQuery = "
    SELECT SQL_CALC_FOUND_ROWS
      m.mapel_id, m.kode_mapel, m.nama_mapel, m.aktif,
      a.fullname, a.gelar_depan, a.gelar_belakang, a.admin_id as guru_id
    FROM agenda_mapel m
    LEFT JOIN admin a ON m.guru_id = a.admin_id
    $sWhere $sOrder $sLimit
  ";

  $rResult = $gaSql['link']->query($sQuery);
  $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
  $iTotal = $gaSql['link']->query("SELECT COUNT(mapel_id) FROM agenda_mapel")->fetch_row()[0];

  $output = [
    "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
    "recordsTotal" => $iTotal,
    "recordsFiltered" => $iFilteredTotal,
    "data" => []
  ];

  $no = intval($_POST['start'] ?? 0) + 1;

  while ($aRow = $rResult->fetch_assoc()) {
    $row = [];
    $nama_guru = $aRow['fullname']
        ? trim(($aRow['gelar_depan'] ? $aRow['gelar_depan'] . ' ' : '') . $aRow['fullname'] . ($aRow['gelar_belakang'] ? ', ' . $aRow['gelar_belakang'] : ''))
        : '<em class="text-muted">Tanpa Guru</em>';
    $badge = $aRow['aktif'] == 'Y' ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Tidak Aktif</span>';

    $row[] = '<div class="text-center">' . $no++ . '</div>';
    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['kode_mapel'] ?: '-') . '</div>';
    $row[] = '<div class="text-center font-weight-bold">' . htmlspecialchars($aRow['nama_mapel']) . '</div>';
    $row[] = '<div class="text-center">' . ($aRow['fullname'] ? htmlspecialchars(trim(($aRow['gelar_depan'] ? $aRow['gelar_depan'] . ' ' : '') . $aRow['fullname'] . ($aRow['gelar_belakang'] ? ', ' . $aRow['gelar_belakang'] : ''))) : '<em class="text-muted">Tanpa Guru</em>') . '</div>';
    $row[] = '<div class="text-center">' . $badge . '</div>';

    $actions = '<div class="text-center">';
    $actions .= '<a href="javascript:void(0)" class="table-action table-action-warning btn-edit btn-tooltip" data-id="' . $aRow['mapel_id'] . '" data-kode="' . htmlspecialchars($aRow['kode_mapel'] ?? '') . '" data-nama="' . htmlspecialchars($aRow['nama_mapel']) . '" data-guru="' . $aRow['guru_id'] . '" data-aktif="' . $aRow['aktif'] . '" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></a>';
    $actions .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-delete btn-tooltip" data-id="' . $aRow['mapel_id'] . '" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a>';
    $actions .= '</div>';
    $row[] = $actions;

    $output['data'][] = $row;
  }

  echo json_encode($output);
}
