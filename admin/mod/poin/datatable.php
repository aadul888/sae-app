<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../../library/config.php');
require_once('../../../library/function.php');

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    echo json_encode(["draw"=>0,"recordsTotal"=>0,"recordsFiltered"=>0,"data"=>[]]);
    exit;
}

$draw = intval($_POST['draw'] ?? 0);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';
$kelas_id = intval($_POST['kelas_id'] ?? 0);
$status_filter = $_POST['status'] ?? '';
$dari = $_POST['dari'] ?? '';
$sampai = $_POST['sampai'] ?? '';

$where = "WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (u.nama_lengkap LIKE ? OR u.nisn LIKE ? OR pa.jenis_pelanggaran LIKE ?)";
    $s = "%$search%";
    $params[] = &$s; $params[] = &$s; $params[] = &$s;
    $types .= 'sss';
}
if ($kelas_id > 0) {
    $where .= " AND pp.kelas_id = ?";
    $params[] = &$kelas_id;
    $types .= 'i';
}
if (in_array($status_filter, ['Aktif','Disanggah','Dikurangi','Dihapus'])) {
    $where .= " AND pp.status = ?";
    $params[] = &$status_filter;
    $types .= 's';
}
if ($dari !== '') {
    $where .= " AND pp.tanggal_kejadian >= ?";
    $params[] = &$dari;
    $types .= 's';
}
if ($sampai !== '') {
    $where .= " AND pp.tanggal_kejadian <= ?";
    $params[] = &$sampai;
    $types .= 's';
}

// Total without filter
$total = 0;
$qt = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran");
if ($qt) $total = intval($qt->fetch_assoc()['c']);

// Total with filter
$sql_count = "SELECT COUNT(*) c FROM poin_pelanggaran pp
  JOIN user u ON pp.user_id=u.user_id
  JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id
  $where";
$filtered = $total;
if (count($params) > 0) {
    $stmt = $connection->prepare($sql_count);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $filtered = intval($res->fetch_assoc()['c']);
    $stmt->close();
} else {
    $qf = $connection->query($sql_count);
    if ($qf) $filtered = intval($qf->fetch_assoc()['c']);
}

// Order
$order_col_idx = intval($_POST['order'][0]['column'] ?? 5);
$order_dir = (strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';
$order_cols = ['pp.pelanggaran_id','u.nama_lengkap','k.nama_kelas','pa.jenis_pelanggaran','pp.poin_diberikan','pp.tanggal_kejadian','pp.status'];
$order_by = isset($order_cols[$order_col_idx]) ? $order_cols[$order_col_idx] : 'pp.tanggal_kejadian';

$sql = "SELECT pp.*, u.nama_lengkap, u.nisn, k.nama_kelas, pa.jenis_pelanggaran, pa.kategori, ps.nama_pasal, ps.kode_pasal
  FROM poin_pelanggaran pp
  JOIN user u ON pp.user_id=u.user_id
  LEFT JOIN kelas k ON pp.kelas_id=k.kelas_id
  JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id
  JOIN poin_pasal ps ON pa.pasal_id=ps.pasal_id
  $where
  ORDER BY $order_by $order_dir
  LIMIT ?, ?";

$params2 = $params;
$types2 = $types . 'ii';
$params2[] = &$start;
$params2[] = &$length;

$data = [];
$stmt = $connection->prepare($sql);
if ($stmt) {
    if ($types2 !== 'ii') {
        $stmt->bind_param($types2, ...$params2);
    } else {
        $stmt->bind_param('ii', $start, $length);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $no = $start + 1;
    while ($row = $result->fetch_assoc()) {
        $status_badge = 'success';
        if ($row['status'] == 'Disanggah') $status_badge = 'info';
        elseif ($row['status'] == 'Dikurangi') $status_badge = 'warning';
        elseif ($row['status'] == 'Dihapus') $status_badge = 'secondary';

        $kat_badge = 'success';
        if ($row['kategori'] == 'Sedang') $kat_badge = 'warning';
        elseif ($row['kategori'] == 'Berat') $kat_badge = 'danger';
        elseif ($row['kategori'] == 'Sangat Berat') $kat_badge = 'dark';

        $repeat_icon = '';
        if ($row['is_pengulangan'] == 'Y') $repeat_icon = '<i class="fas fa-redo text-danger ml-1" title="Pengulangan ke-'.$row['jumlah_pengulangan'].'"></i>';

        $aksi = '<a href="javascript:void(0)" class="table-action table-action-primary btn-detail-pel btn-tooltip" data-id="'.$row['pelanggaran_id'].'" data-toggle="tooltip" title="Detail"><i class="fas fa-eye"></i></a>';
        $aksi .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-hapus-pel btn-tooltip" data-id="'.$row['pelanggaran_id'].'" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a>';

        $data[] = [
            $no++,
            '<strong>'.htmlspecialchars($row['nama_lengkap']).'</strong><br><small class="text-muted">'.$row['nisn'].'</small>',
            htmlspecialchars($row['nama_kelas'] ?? '-'),
            '<span class="badge badge-'.$kat_badge.' mr-1">'.$row['kategori'].'</span>'.htmlspecialchars($row['jenis_pelanggaran']).$repeat_icon.'<br><small class="text-muted">'.$row['kode_pasal'].'</small>',
            '<span class="badge badge-danger" style="font-size:14px">'.$row['poin_diberikan'].'</span>',
            date('d/m/Y', strtotime($row['tanggal_kejadian'])),
            '<span class="badge badge-'.$status_badge.'">'.$row['status'].'</span>',
            $aksi
        ];
    }
    $stmt->close();
}

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $total,
    "recordsFiltered" => $filtered,
    "data" => $data
]);
