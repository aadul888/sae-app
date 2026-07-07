<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}

require_once '../../../library/config.php';
include '../../../library/function.php';

$gaSql = array(
  'user' => DB_USER,
  'password' => DB_PASSWD,
  'db' => DB_NAME,
  'server' => DB_HOST
);
$gaSql['link'] = new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

if ($gaSql['link']->connect_error) {
  echo json_encode(array(
    'iTotalRecords' => 0,
    'iTotalDisplayRecords' => 0,
    'aaData' => array()
  ));
  exit;
}

$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
$start = isset($_GET['start']) ? intval($_GET['start']) : (isset($_GET['iDisplayStart']) ? intval($_GET['iDisplayStart']) : 0);
$length = isset($_GET['length']) ? intval($_GET['length']) : (isset($_GET['iDisplayLength']) ? intval($_GET['iDisplayLength']) : 25);

$limit = '';
if ($length > 0) {
  $limit = ' LIMIT ' . max(0, $start) . ', ' . $length;
}

$baseWhere = " WHERE g.nama IS NOT NULL AND g.nama != '' AND EXISTS (SELECT 1 FROM admin a WHERE TRIM(COALESCE(a.ptk_id, '')) = TRIM(COALESCE(g.ptk_id, '')) AND UPPER(TRIM(COALESCE(a.active, 'N'))) = 'Y')";
$where = $baseWhere;

$sSearch = '';
if (isset($_GET['search']) && is_array($_GET['search']) && isset($_GET['search']['value'])) {
  $sSearch = trim((string)$_GET['search']['value']);
} elseif (isset($_GET['sSearch'])) {
  $sSearch = trim((string)$_GET['sSearch']);
}

if ($sSearch !== '') {
  $s = $gaSql['link']->real_escape_string($sSearch);
  $where .= " AND (g.nama LIKE '%$s%' OR a.gelar_depan LIKE '%$s%' OR a.gelar_belakang LIKE '%$s%' OR g.jenis_ptk_id_str LIKE '%$s%' OR g.status_kepegawaian_id_str LIKE '%$s%' OR g.jabatan_ptk_id_str LIKE '%$s%' OR g.nuptk LIKE '%$s%' OR g.nik LIKE '%$s%' OR g.nip LIKE '%$s%')";
}

if (!empty($_GET['jenis_ptk'])) {
  $f = $gaSql['link']->real_escape_string($_GET['jenis_ptk']);
  if ($f === 'guru') {
    $where .= " AND LOWER(COALESCE(g.jenis_ptk_id_str, '')) LIKE '%guru%'";
  } elseif ($f === 'tenaga_kependidikan') {
    $where .= " AND (LOWER(COALESCE(g.jenis_ptk_id_str, '')) LIKE '%kependidikan%' OR LOWER(COALESCE(g.jenis_ptk_id_str, '')) LIKE '%tata usaha%' OR LOWER(COALESCE(g.jenis_ptk_id_str, '')) LIKE '%tu%')";
  }
}
if (!empty($_GET['status_kepegawaian'])) {
  $f = $gaSql['link']->real_escape_string($_GET['status_kepegawaian']);
  $where .= " AND g.status_kepegawaian_id_str = '$f'";
}
if (!empty($_GET['jabatan_ptk'])) {
  $f = $gaSql['link']->real_escape_string($_GET['jabatan_ptk']);
  $where .= " AND g.jabatan_ptk_id_str = '$f'";
}

$query = "
  SELECT SQL_CALC_FOUND_ROWS
    g.ptk_id,
    g.nama,
    g.jenis_ptk_id_str,
    g.status_kepegawaian_id_str,
    g.jabatan_ptk_id_str,
    g.nip,
    g.nuptk,
    g.nik,
    g.updated_at,
    a.admin_id,
    a.gelar_depan,
    a.gelar_belakang
  FROM sync_gtk g
  LEFT JOIN admin a ON TRIM(COALESCE(a.ptk_id, '')) = TRIM(COALESCE(g.ptk_id, ''))
  $where
  ORDER BY g.nama ASC
  $limit
";

$result = $gaSql['link']->query($query);
if (!$result) {
  echo json_encode(array(
    'iTotalRecords' => 0,
    'iTotalDisplayRecords' => 0,
    'aaData' => array()
  ));
  exit;
}

$fr = $gaSql['link']->query('SELECT FOUND_ROWS()');
$row_fr = $fr ? $fr->fetch_row() : array(0);
$filtered = intval($row_fr[0]);

$ctTotal = $gaSql['link']->query("SELECT COUNT(*) FROM sync_gtk g $baseWhere");
$rowTotal = $ctTotal ? $ctTotal->fetch_row() : array(0);
$total = intval($rowTotal[0]);

$stats_q = $gaSql['link']->query("SELECT COUNT(*) AS total, COUNT(DISTINCT NULLIF(COALESCE(g.jenis_ptk_id_str, ''), '')) AS jenis, COUNT(DISTINCT NULLIF(COALESCE(g.status_kepegawaian_id_str, ''), '')) AS kepegawaian FROM sync_gtk g $where");
$stats = $stats_q ? $stats_q->fetch_assoc() : array('total' => 0, 'jenis' => 0, 'kepegawaian' => 0);

$output = array(
  'draw' => $draw,
  'recordsTotal' => $total,
  'recordsFiltered' => $filtered,
  'data' => array(),
  'iTotalRecords' => $total,
  'iTotalDisplayRecords' => $filtered,
  'aaData' => array(),
  'stats' => array(
    'total' => intval($stats['total']),
    'jenis' => intval($stats['jenis']),
    'kepegawaian' => intval($stats['kepegawaian'])
  )
);

$no = $start;
while ($r = $result->fetch_assoc()) {
  $no++;
  $id_parts = [];
  if (!empty($r['nik'])) {
    $nik = htmlspecialchars($r['nik'], ENT_QUOTES, 'UTF-8');
    $id_parts[] = '<div class="guru-id-item"><small class="text-muted">NIK:</small> <a href="#" class="copy-id-value" data-copy="' . $nik . '" title="Klik untuk copy NIK">' . $nik . '</a></div>';
  }
  if (!empty($r['nuptk'])) {
    $nuptk = htmlspecialchars($r['nuptk'], ENT_QUOTES, 'UTF-8');
    $id_parts[] = '<div class="guru-id-item"><small class="text-muted">NUPTK:</small> <a href="#" class="copy-id-value" data-copy="' . $nuptk . '" title="Klik untuk copy NUPTK">' . $nuptk . '</a></div>';
  }
  if (!empty($r['nip'])) {
    $nip = htmlspecialchars($r['nip'], ENT_QUOTES, 'UTF-8');
    $id_parts[] = '<div class="guru-id-item"><small class="text-muted">NIP:</small> <a href="#" class="copy-id-value" data-copy="' . $nip . '" title="Klik untuk copy NIP">' . $nip . '</a></div>';
  }
  if (empty($id_parts) && !empty($r['ptk_id'])) {
    $ptk = htmlspecialchars($r['ptk_id'], ENT_QUOTES, 'UTF-8');
    $id_parts[] = '<div class="guru-id-item"><small class="text-muted">PTK ID:</small> <a href="#" class="copy-id-value" data-copy="' . $ptk . '" title="Klik untuk copy PTK ID">' . $ptk . '</a></div>';
  }
  if (empty($id_parts)) $id_parts[] = '<span class="text-muted">-</span>';
  $id_col = '<div class="guru-id-stack">' . implode('', $id_parts) . '</div>';

  // Build nama with gelar
  $gelar_d = trim($r['gelar_depan'] ?? '');
  $gelar_b = trim($r['gelar_belakang'] ?? '');
  $nama_display = htmlspecialchars($r['nama']);
  if ($gelar_d) $nama_display = htmlspecialchars($gelar_d) . ' ' . $nama_display;
  if ($gelar_b) $nama_display .= ', ' . htmlspecialchars($gelar_b);

  $admin_id = intval($r['admin_id'] ?? 0);
  $edit_btn = $admin_id ? '<button class="btn btn-sm btn-outline-primary btn-edit-gelar ml-2" data-admin-id="' . $admin_id . '" data-gelar-depan="' . htmlspecialchars($gelar_d) . '" data-gelar-belakang="' . htmlspecialchars($gelar_b) . '" title="Edit gelar"><i class="fas fa-edit"></i></button>' : '';

  $row = array(
    '<div class="text-center">' . $no . '</div>',
    $id_col,
    '<div class="d-flex align-items-center"><b>' . $nama_display . '</b>' . $edit_btn . '</div>',
    htmlspecialchars($r['jenis_ptk_id_str'] ?? '-'),
    htmlspecialchars($r['status_kepegawaian_id_str'] ?? '-'),
    htmlspecialchars($r['jabatan_ptk_id_str'] ?? '-')
  );
  $output['aaData'][] = $row;
  $output['data'][] = $row;
}

echo json_encode($output);
