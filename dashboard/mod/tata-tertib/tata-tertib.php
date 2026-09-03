<?php
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
} else {
    if (isset($_COOKIE['siswa'])) {
        // Ambil data pasal & ayat dari database
        $pasal_list = [];
        $qp = $connection->query("SELECT * FROM poin_pasal WHERE aktif='Y' ORDER BY urutan ASC, pasal_id ASC");
        if ($qp) while ($rp = $qp->fetch_assoc()) {
            $rp['ayat'] = [];
            $pasal_list[$rp['pasal_id']] = $rp;
        }
        $qa = $connection->query("SELECT * FROM poin_ayat WHERE aktif='Y' ORDER BY urutan ASC, ayat_id ASC");
        if ($qa) while ($ra = $qa->fetch_assoc()) {
            if (isset($pasal_list[$ra['pasal_id']])) {
                $pasal_list[$ra['pasal_id']]['ayat'][] = $ra;
            }
        }
?>
        <div class="home-dashboard-container">
            <div class="container tata-tertib-section" style="max-width:900px;margin:40px auto 0 auto;background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.07);padding:32px 16px 24px 16px;">
                <h2 class="mb-4 text-center font-weight-bold"><i class="fas fa-gavel text-primary mr-2"></i>Tata Tertib & Poin Pelanggaran</h2>

                <!-- Ringkasan kategori -->
                <div class="row text-center mb-4">
                    <div class="col-3"><div class="p-2 rounded" style="background:#d4edda"><small class="font-weight-bold text-success">Ringan</small><br><small>10-20 Poin</small></div></div>
                    <div class="col-3"><div class="p-2 rounded" style="background:#fff3cd"><small class="font-weight-bold text-warning">Sedang</small><br><small>25-30 Poin</small></div></div>
                    <div class="col-3"><div class="p-2 rounded" style="background:#f8d7da"><small class="font-weight-bold text-danger">Berat</small><br><small>50-75 Poin</small></div></div>
                    <div class="col-3"><div class="p-2 rounded" style="background:#343a40;color:#fff"><small class="font-weight-bold">S. Berat</small><br><small>100 Poin</small></div></div>
                </div>

                <?php
                $no = 1;
                foreach ($pasal_list as $pasal) {
                    if (count($pasal['ayat']) == 0) continue;
                ?>
                <div class="mb-4">
                    <h5 class="font-weight-bold text-primary mb-3">
                        <span class="badge badge-primary mr-2"><?= htmlspecialchars($pasal['kode_pasal']) ?></span>
                        <?= htmlspecialchars($pasal['nama_pasal']) ?>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="thead-light">
                                <tr class="text-center">
                                    <th style="width:40px">No</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th style="width:100px">Kategori</th>
                                    <th style="width:80px">Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pasal['ayat'] as $ayat) {
                                    $kat_cls = 'success';
                                    if ($ayat['kategori'] == 'Sedang') $kat_cls = 'warning';
                                    elseif ($ayat['kategori'] == 'Berat') $kat_cls = 'danger';
                                    elseif ($ayat['kategori'] == 'Sangat Berat') $kat_cls = 'dark';
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td>
                                        <?= htmlspecialchars($ayat['jenis_pelanggaran']) ?>
                                        <?php if ($ayat['deskripsi']) echo '<br><small class="text-muted">'.htmlspecialchars($ayat['deskripsi']).'</small>'; ?>
                                    </td>
                                    <td class="text-center"><span class="badge badge-<?= $kat_cls ?>"><?= htmlspecialchars($ayat['kategori']) ?></span></td>
                                    <td class="text-center"><strong class="text-danger"><?= $ayat['poin'] ?></strong></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php } ?>

                <div class="mt-4 p-3 rounded" style="background:#f8f9fe">
                    <h5 class="font-weight-bold"><i class="fas fa-info-circle text-primary mr-1"></i>Keterangan:</h5>
                    <ol style="font-size:15px;">
                        <li>Jumlah poin berlaku jika Wali Kelas melimpahkan suatu kasus kepada Kepala Sekolah melalui Wakasek Bidang Kesiswaan dan poin diakumulasikan selama satu Tahun Pelajaran.</li>
                        <li>Jika jumlah poin mencapai <strong class="text-danger">70</strong> maka siswa akan dipanggil Orang Tua atau Walinya dan diproses lebih lanjut oleh BK dan Kepala Sekolah.</li>
                        <li>Jika jumlah poin berulang maka siswa akan diproses lebih lanjut oleh BK, Wali Kelas dan Kepala Sekolah.</li>
                        <li>Jika Peserta Didik melakukan pelanggaran yang sama, poin pelanggaran akan diakumulasikan.</li>
                        <li>Sanksi pelanggaran dapat berupa tindakan langsung, skorsing, atau dikeluarkan dari Sekolah.</li>
                        <li>Siswa dapat mengajukan <strong>sanggahan</strong> atas poin pelanggaran melalui menu <a href="./?mod=poin">Poin Pelanggaran</a>.</li>
                    </ol>
                </div>
            </div>
        </div>
<?php
    } else {
        echo '<div class="container mt-5">
            <div class="alert alert-warning text-center">
              <h4><i class="fas fa-exclamation-triangle"></i> Akses Ditolak</h4>
              <p>Silakan login untuk mengakses dashboard.</p>
              <a href="../" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login Sekarang
              </a>
            </div>
          </div>';
    }
}
?>