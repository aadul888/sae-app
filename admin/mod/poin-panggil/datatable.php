<?php
header('Content-Type: application/json; charset=utf-8');
require_once('../../../library/config.php');
require_once('../../../library/function.php');

if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    echo json_encode(["draw"=>0,"recordsTotal"=>0,"recordsFiltered"=>0,"data"=>[]]); exit;
}

$draw = intval($_POST['draw'] ?? 0);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';
$status_filter = $_POST['status'] ?? '';

$where = "WHERE 1=1";
if ($search !== '') { $where .= " AND (u.nama_lengkap LIKE '%".mysqli_real_escape_string($connection,$search)."%' OR u.nisn LIKE '%".mysqli_real_escape_string($connection,$search)."%')"; }
if (in_array($status_filter, ['Menunggu','Hadir','Tidak Hadir','Selesai'])) { $where .= " AND pc.status='".mysqli_real_escape_string($connection,$status_filter)."'"; }

$total = 0;
$qt = $connection->query("SELECT COUNT(*) c FROM poin_panggil"); if ($qt) $total = intval($qt->fetch_assoc()['c']);

$sql_count = "SELECT COUNT(*) c FROM poin_panggil pc JOIN user u ON pc.user_id=u.user_id $where";
$filtered = $total;
$qf = $connection->query($sql_count); if ($qf) $filtered = intval($qf->fetch_assoc()['c']);

$sql = "SELECT pc.*, u.nama_lengkap, u.nisn, u.telp_ortu, k.nama_kelas
  FROM poin_panggil pc
  JOIN user u ON pc.user_id=u.user_id
  LEFT JOIN kelas k ON pc.kelas_id=k.kelas_id
  $where
  ORDER BY FIELD(pc.status,'Menunggu','Hadir','Tidak Hadir','Selesai'), pc.created_at DESC
  LIMIT $start, $length";

$data = [];
$q = $connection->query($sql);
$no = $start + 1;
if ($q) while ($row = $q->fetch_assoc()) {
    $status_badge = 'secondary';
    switch ($row['status']) { case 'Menunggu': $status_badge='warning'; break; case 'Hadir': $status_badge='success'; break; case 'Tidak Hadir': $status_badge='danger'; break; case 'Selesai': $status_badge='info'; break; }

    $aksi = '';
    if ($row['status'] === 'Menunggu') $aksi .= '<a href="javascript:void(0)" class="btn-hasil mx-1" data-id="'.$row['panggil_id'].'" title="Isi Hasil"><i class="fas fa-clipboard-check text-primary"></i></a>';
    if ($row['status'] === 'Hadir') $aksi .= '<a href="javascript:void(0)" class="btn-selesai-panggil mx-1" data-id="'.$row['panggil_id'].'" title="Selesai"><i class="fas fa-check-double text-success"></i></a>';

    $data[] = [
        $no++,
        '<strong>'.htmlspecialchars($row['nama_lengkap']).'</strong><br><small class="text-muted">'.$row['nisn'].' | Ortu: '.htmlspecialchars($row['telp_ortu']??'-').'</small>',
        htmlspecialchars($row['nama_kelas']??'-'),
        '<span class="badge badge-danger" style="font-size:14px">'.$row['total_poin'].'</span>',
        '<span class="badge badge-light">'.$row['jenis_panggilan'].'</span>',
        date('d/m/Y', strtotime($row['tanggal_panggil'])),
        '<span class="badge badge-'.$status_badge.'">'.$row['status'].'</span>',
        $aksi
    ];
}

echo json_encode(["draw"=>$draw,"recordsTotal"=>$total,"recordsFiltered"=>$filtered,"data"=>$data]);
