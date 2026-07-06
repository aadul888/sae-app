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
if ($search !== '') { $where .= " AND (u.nama_lengkap LIKE '%".mysqli_real_escape_string($connection,$search)."%' OR pa.jenis_pelanggaran LIKE '%".mysqli_real_escape_string($connection,$search)."%')"; }
if (in_array($status_filter, ['Menunggu','Disetujui','Ditolak','Selesai'])) { $where .= " AND ps.status='".mysqli_real_escape_string($connection,$status_filter)."'"; }

$total = 0;
$qt = $connection->query("SELECT COUNT(*) c FROM poin_sanggah"); if ($qt) $total = intval($qt->fetch_assoc()['c']);

$sql_count = "SELECT COUNT(*) c FROM poin_sanggah ps JOIN user u ON ps.user_id=u.user_id JOIN poin_pelanggaran pp ON ps.pelanggaran_id=pp.pelanggaran_id JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id $where";
$filtered = $total;
$qf = $connection->query($sql_count); if ($qf) $filtered = intval($qf->fetch_assoc()['c']);

$order_dir = (strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

$sql = "SELECT ps.*, u.nama_lengkap, u.nisn, pa.jenis_pelanggaran, pp.poin_diberikan, pp.tanggal_kejadian AS tgl_pelanggaran
  FROM poin_sanggah ps
  JOIN user u ON ps.user_id=u.user_id
  JOIN poin_pelanggaran pp ON ps.pelanggaran_id=pp.pelanggaran_id
  JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id
  $where
  ORDER BY FIELD(ps.status,'Menunggu','Disetujui','Selesai','Ditolak'), ps.created_at $order_dir
  LIMIT $start, $length";

$data = [];
$q = $connection->query($sql);
$no = $start + 1;
if ($q) while ($row = $q->fetch_assoc()) {
    $status_badge = 'secondary';
    switch ($row['status']) { case 'Menunggu': $status_badge='warning'; break; case 'Disetujui': $status_badge='success'; break; case 'Ditolak': $status_badge='danger'; break; case 'Selesai': $status_badge='info'; break; }

    $aksi = '<a href="javascript:void(0)" class="btn-proses-sanggah mx-1" data-id="'.$row['sanggah_id'].'" title="Proses"><i class="fas fa-gavel text-primary"></i></a>';
    if ($row['status'] === 'Disetujui') {
        $aksi .= '<a href="javascript:void(0)" class="btn-selesai-sanggah mx-1" data-id="'.$row['sanggah_id'].'" title="Tandai Selesai"><i class="fas fa-check-double text-success"></i></a>';
    }

    $data[] = [
        $no++,
        '<strong>'.htmlspecialchars($row['nama_lengkap']).'</strong><br><small class="text-muted">'.$row['nisn'].'</small>',
        htmlspecialchars($row['jenis_pelanggaran']).'<br><small class="text-muted">Poin: '.$row['poin_diberikan'].' | '.$row['tgl_pelanggaran'].'</small>',
        '<span class="badge badge-light">'.$row['jenis_sanggah'].'</span>',
        '<small>'.htmlspecialchars(mb_strimwidth($row['alasan']??'',0,80,'...')).'</small>',
        '<span class="badge badge-'.$status_badge.'">'.$row['status'].'</span>'.($row['poin_dikurangi']>0?'<br><small class="text-success">-'.$row['poin_dikurangi'].' poin</small>':''),
        date('d/m/Y', strtotime($row['tanggal_pengajuan'])),
        $aksi
    ];
}

echo json_encode(["draw"=>$draw,"recordsTotal"=>$total,"recordsFiltered"=>$filtered,"data"=>$data]);
