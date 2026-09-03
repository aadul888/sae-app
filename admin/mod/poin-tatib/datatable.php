<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../../library/config.php');
require_once('../../../library/function.php');

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    echo json_encode(["draw"=>0,"recordsTotal"=>0,"recordsFiltered"=>0,"data"=>[]]);
    exit;
}

$draw   = intval($_POST['draw'] ?? 0);
$start  = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

$pasal_id_f  = intval($_POST['pasal_id'] ?? 0);
$kategori_f  = $_POST['kategori'] ?? '';
$aktif_f     = $_POST['aktif'] ?? '';

$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($search !== '') {
    $where .= " AND (pa.jenis_pelanggaran LIKE ? OR pa.kode_ayat LIKE ? OR pp.nama_pasal LIKE ?)";
    $s = "%$search%";
    $params[] = &$s; $params[] = &$s; $params[] = &$s;
    $types .= 'sss';
}
if ($pasal_id_f > 0) {
    $where .= " AND pa.pasal_id = ?";
    $params[] = &$pasal_id_f;
    $types .= 'i';
}
if (in_array($kategori_f, ['Ringan','Sedang','Berat','Sangat Berat'])) {
    $where .= " AND pa.kategori = ?";
    $params[] = &$kategori_f;
    $types .= 's';
}
if (in_array($aktif_f, ['Y','N'])) {
    $where .= " AND pa.aktif = ?";
    $params[] = &$aktif_f;
    $types .= 's';
}

// Total unfiltered
$total = 0;
$qt = $connection->query("SELECT COUNT(*) c FROM poin_ayat");
if ($qt) $total = intval($qt->fetch_assoc()['c']);

// Total filtered
$sql_count = "SELECT COUNT(*) c FROM poin_ayat pa JOIN poin_pasal pp ON pa.pasal_id=pp.pasal_id $where";
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
$order_col_idx = intval($_POST['order'][0]['column'] ?? 2);
$order_dir = (strtolower($_POST['order'][0]['dir'] ?? 'asc') === 'asc') ? 'ASC' : 'DESC';
$order_cols = [
    0 => 'pa.ayat_id',
    1 => 'pa.kode_ayat',
    2 => 'pp.urutan',
    3 => 'pa.jenis_pelanggaran',
    4 => 'pa.kategori',
    5 => 'pa.poin',
    6 => 'pa.aktif',
];
$order_by = isset($order_cols[$order_col_idx]) ? $order_cols[$order_col_idx] : 'pp.urutan';

$sql = "SELECT pa.ayat_id, pa.pasal_id, pa.kode_ayat, pa.jenis_pelanggaran, pa.deskripsi,
               pa.kategori, pa.poin, pa.urutan AS ayat_urutan, pa.aktif,
               pp.kode_pasal, pp.nama_pasal, pp.deskripsi AS pasal_deskripsi,
               pp.urutan AS pasal_urutan, pp.aktif AS pasal_aktif
        FROM poin_ayat pa
        JOIN poin_pasal pp ON pa.pasal_id = pp.pasal_id
        $where
        ORDER BY $order_by $order_dir, pa.urutan ASC
        LIMIT ? OFFSET ?";

$data = [];
$params_data = $params;
$params_data[] = &$length;
$params_data[] = &$start;
$types_data = $types . 'ii';

$stmt = $connection->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types_data, ...$params_data);
    $stmt->execute();
    $res = $stmt->get_result();
    $no = $start + 1;
    while ($r = $res->fetch_assoc()) {
        $kat_badge = 'success';
        if ($r['kategori'] === 'Sedang')        $kat_badge = 'warning';
        elseif ($r['kategori'] === 'Berat')     $kat_badge = 'danger';
        elseif ($r['kategori'] === 'Sangat Berat') $kat_badge = 'dark';

        $status_html = $r['aktif'] === 'Y'
            ? '<span class="badge badge-success">Aktif</span>'
            : '<span class="badge badge-secondary">Nonaktif</span>';

        $pasal_html = '<small class="font-weight-bold">'.htmlspecialchars($r['kode_pasal']).'</small><br>'
                    . '<small class="text-muted">'.htmlspecialchars($r['nama_pasal']).'</small>';

        $jenis_html = htmlspecialchars($r['jenis_pelanggaran']);
        if (!empty($r['deskripsi'])) $jenis_html .= '<br><small class="text-muted">'.htmlspecialchars($r['deskripsi']).'</small>';

        $aksi  = '<a href="javascript:void(0)" class="table-action table-action-primary btn-edit-ayat btn-tooltip"'
               . ' data-id="'.intval($r['ayat_id']).'"'
               . ' data-pasal="'.intval($r['pasal_id']).'"'
               . ' data-kode="'.htmlspecialchars($r['kode_ayat'], ENT_QUOTES).'"'
               . ' data-jenis="'.htmlspecialchars($r['jenis_pelanggaran'], ENT_QUOTES).'"'
               . ' data-deskripsi="'.htmlspecialchars($r['deskripsi'] ?? '', ENT_QUOTES).'"'
               . ' data-kategori="'.htmlspecialchars($r['kategori'], ENT_QUOTES).'"'
               . ' data-poin="'.intval($r['poin']).'"'
               . ' data-urutan="'.intval($r['ayat_urutan']).'"'
               . ' data-aktif="'.htmlspecialchars($r['aktif'], ENT_QUOTES).'"'
               . ' data-toggle="tooltip" title="Edit Ayat"><i class="fas fa-edit"></i></a>';

        $aksi .= '<a href="javascript:void(0)" class="table-action table-action-warning btn-edit-pasal btn-tooltip"'
               . ' data-id="'.intval($r['pasal_id']).'"'
               . ' data-kode="'.htmlspecialchars($r['kode_pasal'], ENT_QUOTES).'"'
               . ' data-nama="'.htmlspecialchars($r['nama_pasal'], ENT_QUOTES).'"'
               . ' data-deskripsi="'.htmlspecialchars($r['pasal_deskripsi'] ?? '', ENT_QUOTES).'"'
               . ' data-urutan="'.intval($r['pasal_urutan']).'"'
               . ' data-aktif="'.htmlspecialchars($r['pasal_aktif'], ENT_QUOTES).'"'
               . ' data-toggle="tooltip" title="Edit Pasal"><i class="fas fa-book"></i></a>';

        $aksi .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-hapus-ayat btn-tooltip"'
               . ' data-id="'.intval($r['ayat_id']).'"'
               . ' data-toggle="tooltip" title="Hapus Ayat"><i class="fas fa-trash"></i></a>';

        $data[] = [
            $no++,
            '<small class="font-weight-bold">'.htmlspecialchars($r['kode_ayat']).'</small>',
            $pasal_html,
            $jenis_html,
            '<span class="badge badge-'.$kat_badge.'">'.htmlspecialchars($r['kategori']).'</span>',
            '<strong class="text-danger">'.$r['poin'].'</strong>',
            $status_html,
            '<div class="text-center">'.$aksi.'</div>',
        ];
    }
    $stmt->close();
}

echo json_encode([
    "draw"            => $draw,
    "recordsTotal"    => $total,
    "recordsFiltered" => $filtered,
    "data"            => $data,
]);
