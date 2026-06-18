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

$where = ' WHERE p.pembelajaran_id IS NOT NULL ';
if (!empty($_GET['sSearch'])) {
  $s = $gaSql['link']->real_escape_string($_GET['sSearch']);
  $where .= " AND (p.pembelajaran_id LIKE '%$s%' OR p.nama_mata_pelajaran LIKE '%$s%' OR p.mata_pelajaran_id_str LIKE '%$s%' OR rb.nama LIKE '%$s%' OR g.nama LIKE '%$s%')";
}

if (!empty($_GET['rombel'])) {
  $f = $gaSql['link']->real_escape_string($_GET['rombel']);
  $where .= " AND rb.nama = '$f'";
}
if (!empty($_GET['guru'])) {
  $f = $gaSql['link']->real_escape_string($_GET['guru']);
  $where .= " AND g.nama = '$f'";
}
if (!empty($_GET['status_kurikulum'])) {
  $f = $gaSql['link']->real_escape_string($_GET['status_kurikulum']);
  $where .= " AND p.status_di_kurikulum_str = '$f'";
}

$query = "
  SELECT SQL_CALC_FOUND_ROWS
    p.pembelajaran_id,
    p.nama_mata_pelajaran,
    p.mata_pelajaran_id_str,
    p.jam_mengajar_per_minggu,
    p.status_di_kurikulum_str,
    p.updated_at,
    rb.nama AS nama_rombel,
    g.nama AS nama_guru
  FROM sync_pembelajaran p
  LEFT JOIN sync_rombongan_belajar rb ON rb.rombongan_belajar_id = p.rombongan_belajar_id
  LEFT JOIN sync_gtk g ON g.ptk_id = p.ptk_id
  $where
  ORDER BY rb.nama ASC, p.nama_mata_pelajaran ASC
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

$ct = $gaSql['link']->query("SELECT COUNT(*) FROM sync_pembelajaran p LEFT JOIN sync_rombongan_belajar rb ON rb.rombongan_belajar_id = p.rombongan_belajar_id LEFT JOIN sync_gtk g ON g.ptk_id = p.ptk_id $where");
$row_ct = $ct ? $ct->fetch_row() : array(0);
$total = intval($row_ct[0]);

$stats_q = $gaSql['link']->query("SELECT SUM(COALESCE(p.jam_mengajar_per_minggu, 0)) AS total_jam, COUNT(DISTINCT NULLIF(COALESCE(p.nama_mata_pelajaran, ''), '')) AS total_mapel, COUNT(DISTINCT NULLIF(COALESCE(p.rombongan_belajar_id, ''), '')) AS total_rombel, COUNT(DISTINCT NULLIF(COALESCE(p.ptk_id, ''), '')) AS total_guru FROM sync_pembelajaran p LEFT JOIN sync_rombongan_belajar rb ON rb.rombongan_belajar_id = p.rombongan_belajar_id LEFT JOIN sync_gtk g ON g.ptk_id = p.ptk_id $where");
$stats = $stats_q ? $stats_q->fetch_assoc() : array('total_jam' => 0, 'total_mapel' => 0, 'total_rombel' => 0, 'total_guru' => 0);

$output = array(
  'iTotalRecords' => $total,
  'iTotalDisplayRecords' => $filtered,
  'aaData' => array(),
  'stats' => array(
    'total_jam'   => intval($stats['total_jam']),
    'total_mapel' => intval($stats['total_mapel']),
    'total_rombel'=> intval($stats['total_rombel']),
    'total_guru'  => intval($stats['total_guru'])
  )
);

$no = $start;
while ($r = $result->fetch_assoc()) {
  $no++;
  $mapel = !empty($r['nama_mata_pelajaran']) ? $r['nama_mata_pelajaran'] : ($r['mata_pelajaran_id_str'] ?? '-');
  $rombel = !empty($r['nama_rombel']) ? $r['nama_rombel'] : '-';
  $guru = !empty($r['nama_guru']) ? $r['nama_guru'] : '-';
  $jam = !empty($r['jam_mengajar_per_minggu']) ? $r['jam_mengajar_per_minggu'] : '-';
  $status = !empty($r['status_di_kurikulum_str']) ? $r['status_di_kurikulum_str'] : '-';
  $upd = !empty($r['updated_at']) ? date('d-m-Y H:i', strtotime($r['updated_at'])) : '-';

  $output['aaData'][] = array(
    '<div class="text-center">' . $no . '</div>',
    htmlspecialchars($r['pembelajaran_id']),
    '<b>' . htmlspecialchars($mapel) . '</b>',
    htmlspecialchars($rombel),
    htmlspecialchars($guru),
    '<div class="text-center">' . htmlspecialchars($jam) . '</div>',
    htmlspecialchars($status),
    htmlspecialchars($upd)
  );
}

echo json_encode($output);
