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

$limit = '';
$start = isset($_GET['iDisplayStart']) ? intval($_GET['iDisplayStart']) : 0;
$length = isset($_GET['iDisplayLength']) ? intval($_GET['iDisplayLength']) : 25;
if ($length > 0) {
  $limit = ' LIMIT ' . $start . ', ' . $length;
}

$where = " WHERE g.nama IS NOT NULL AND g.nama != '' AND COALESCE(a.active, 'N') = 'Y'";
if (!empty($_GET['sSearch'])) {
  $s = $gaSql['link']->real_escape_string($_GET['sSearch']);
  $where .= " AND (g.nama LIKE '%$s%' OR g.jenis_ptk_id_str LIKE '%$s%' OR g.status_kepegawaian_id_str LIKE '%$s%' OR g.jabatan_ptk_id_str LIKE '%$s%' OR g.nuptk LIKE '%$s%' OR g.nik LIKE '%$s%' OR g.nip LIKE '%$s%')";
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
    g.updated_at
  FROM sync_gtk g
  LEFT JOIN admin a ON a.ptk_id = g.ptk_id
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

$ct = $gaSql['link']->query("SELECT COUNT(*) FROM sync_gtk g LEFT JOIN admin a ON a.ptk_id = g.ptk_id $where");
$row_ct = $ct ? $ct->fetch_row() : array(0);
$total = intval($row_ct[0]);

$stats_q = $gaSql['link']->query("SELECT COUNT(*) AS total, COUNT(DISTINCT NULLIF(COALESCE(g.jenis_ptk_id_str, ''), '')) AS jenis, COUNT(DISTINCT NULLIF(COALESCE(g.status_kepegawaian_id_str, ''), '')) AS kepegawaian FROM sync_gtk g LEFT JOIN admin a ON a.ptk_id = g.ptk_id $where");
$stats = $stats_q ? $stats_q->fetch_assoc() : array('total' => 0, 'jenis' => 0, 'kepegawaian' => 0);

$output = array(
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
  if (!empty($r['nik'])) $id_parts[] = '<div><small class="text-muted">NIK:</small> <strong>' . htmlspecialchars($r['nik']) . '</strong></div>';
  if (!empty($r['nuptk'])) $id_parts[] = '<div><small class="text-muted">NUPTK:</small> <strong>' . htmlspecialchars($r['nuptk']) . '</strong></div>';
  if (!empty($r['nip'])) $id_parts[] = '<div><small class="text-muted">NIP:</small> <strong>' . htmlspecialchars($r['nip']) . '</strong></div>';
  if (empty($id_parts)) $id_parts[] = '<span class="text-muted">-</span>';
  $id_col = implode('', $id_parts);

  $output['aaData'][] = array(
    '<div class="text-center">' . $no . '</div>',
    $id_col,
    '<b>' . htmlspecialchars($r['nama']) . '</b>',
    htmlspecialchars($r['jenis_ptk_id_str'] ?? '-'),
    htmlspecialchars($r['status_kepegawaian_id_str'] ?? '-'),
    htmlspecialchars($r['jabatan_ptk_id_str'] ?? '-')
  );
}

echo json_encode($output);
