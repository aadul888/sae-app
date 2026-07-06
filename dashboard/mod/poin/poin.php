<?php
if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    header('location:../');
    exit();
} else {
    if (isset($_COOKIE['siswa'])) {
        $user_id = intval(convert('decrypt', $_COOKIE['siswa']));

        // Get semester aktif
        $semester_aktif_id = 0;
        $semester_aktif_nama = '';
        $qsem = $connection->query("SELECT semester_id, nama_semester FROM poin_semester WHERE is_aktif='Y' LIMIT 1");
        if ($qsem && $qsem->num_rows > 0) {
            $rsem = $qsem->fetch_assoc();
            $semester_aktif_id = intval($rsem['semester_id']);
            $semester_aktif_nama = $rsem['nama_semester'];
        }

        // Total poin aktif (semester ini saja)
        $total_poin = 0;
        $qt = $connection->query("SELECT COALESCE(SUM(poin_diberikan),0) AS total FROM poin_pelanggaran WHERE user_id=$user_id AND status='Aktif'" . ($semester_aktif_id > 0 ? " AND semester_id=$semester_aktif_id" : ""));
        if ($qt) $total_poin = intval($qt->fetch_assoc()['total']);

        // Jumlah pelanggaran (semester ini)
        $jml_pelanggaran = 0;
        $qj = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE user_id=$user_id AND status='Aktif'" . ($semester_aktif_id > 0 ? " AND semester_id=$semester_aktif_id" : ""));
        if ($qj) $jml_pelanggaran = intval($qj->fetch_assoc()['c']);

        // Jumlah sanggahan (semua semester)
        $jml_sanggah = 0;
        $qs = $connection->query("SELECT COUNT(*) c FROM poin_sanggah WHERE user_id=$user_id");
        if ($qs) $jml_sanggah = intval($qs->fetch_assoc()['c']);

        // Notif belum dibaca
        $notif_baru = 0;
        $qn = $connection->query("SELECT COUNT(*) c FROM poin_pelanggaran WHERE user_id=$user_id AND notif_dibaca='N' AND status='Aktif'");
        if ($qn) $notif_baru = intval($qn->fetch_assoc()['c']);

        // Tandai notif sudah dibaca
        if ($notif_baru > 0) {
            $connection->query("UPDATE poin_pelanggaran SET notif_dibaca='Y' WHERE user_id=$user_id AND notif_dibaca='N'");
        }

        // Daftar pelanggaran semester ini
        $pelanggaran_list = [];
        $qp = $connection->query("SELECT pp.*, pa.jenis_pelanggaran, pa.kategori, pa.kode_ayat, ps.kode_pasal, ps.nama_pasal
            FROM poin_pelanggaran pp
            JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id
            JOIN poin_pasal ps ON pa.pasal_id=ps.pasal_id
            WHERE pp.user_id=$user_id AND pp.status IN ('Aktif','Dikurangi')" . ($semester_aktif_id > 0 ? " AND pp.semester_id=$semester_aktif_id" : "") . "
            ORDER BY pp.tanggal_kejadian DESC");
        if ($qp) while ($rp = $qp->fetch_assoc()) $pelanggaran_list[] = $rp;

        // Daftar pelanggaran semester sebelumnya (yang belum selesai)
        $pelanggaran_lama = [];
        if ($semester_aktif_id > 0) {
            $qpl = $connection->query("SELECT pp.*, pa.jenis_pelanggaran, pa.kategori, pa.kode_ayat, ps.kode_pasal, ps.nama_pasal,
                sem.nama_semester
                FROM poin_pelanggaran pp
                JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id
                JOIN poin_pasal ps ON pa.pasal_id=ps.pasal_id
                LEFT JOIN poin_semester sem ON pp.semester_id=sem.semester_id
                WHERE pp.user_id=$user_id AND pp.status IN ('Aktif','Dikurangi') AND pp.semester_id != $semester_aktif_id
                ORDER BY pp.tanggal_kejadian DESC");
            if ($qpl) while ($rpl = $qpl->fetch_assoc()) $pelanggaran_lama[] = $rpl;
        }

        // Daftar sanggahan (semua semester)
        $sanggah_list = [];
        $qsg = $connection->query("SELECT s.*, pa.jenis_pelanggaran, pp.poin_diberikan AS poin_pel
            FROM poin_sanggah s
            JOIN poin_pelanggaran pp ON s.pelanggaran_id=pp.pelanggaran_id
            JOIN poin_ayat pa ON pp.ayat_id=pa.ayat_id
            WHERE s.user_id=$user_id
            ORDER BY s.created_at DESC");
        if ($qsg) while ($rs = $qsg->fetch_assoc()) $sanggah_list[] = $rs;

        $poin_cls = 'primary';
        if ($total_poin >= 100) $poin_cls = 'danger';
        elseif ($total_poin >= 70) $poin_cls = 'warning';
?>
        <div class="home-dashboard-container">
            <div class="container" style="max-width:900px;margin:40px auto 0 auto;">

                <!-- Warning for 70+ -->
                <?php if ($total_poin >= 100) { ?>
                <div class="alert alert-danger shadow-sm mb-4" style="border-radius:12px;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-3x mr-3"></i>
                        <div>
                            <h5 class="font-weight-bold mb-1 text-white">PERINGATAN!</h5>
                            <p class="mb-0">Total poin pelanggaran kamu sudah mencapai <strong><?= $total_poin ?></strong> (≥100 poin). Orang tua/wali kamu akan dipanggil ke sekolah untuk pembinaan lebih lanjut.</p>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-4">
                        <div class="card shadow-sm border-0" style="border-radius:12px;">
                            <div class="card-body text-center py-3">
                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-<?= $poin_cls ?>" style="width:50px;height:50px;">
                                    <i class="fas fa-star text-white"></i>
                                </div>
                                <h3 class="font-weight-bold text-<?= $poin_cls ?> mb-0"><?= $total_poin ?></h3>
                                <small class="text-muted">Total Poin</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card shadow-sm border-0" style="border-radius:12px;">
                            <div class="card-body text-center py-3">
                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-info" style="width:50px;height:50px;">
                                    <i class="fas fa-list text-white"></i>
                                </div>
                                <h3 class="font-weight-bold mb-0"><?= $jml_pelanggaran ?></h3>
                                <small class="text-muted">Pelanggaran</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card shadow-sm border-0" style="border-radius:12px;">
                            <div class="card-body text-center py-3">
                                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-warning" style="width:50px;height:50px;">
                                    <i class="fas fa-hand-paper text-white"></i>
                                </div>
                                <h3 class="font-weight-bold mb-0"><?= $jml_sanggah ?></h3>
                                <small class="text-muted">Sanggahan</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius:12px;">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="font-weight-bold">Akumulasi Poin <?php if ($semester_aktif_nama) echo '<span class="badge badge-primary ml-1" style="font-size:10px;">'.htmlspecialchars($semester_aktif_nama).'</span>'; ?></small>
                            <small class="font-weight-bold text-<?= $poin_cls ?>"><?= $total_poin ?> / 100</small>
                        </div>
                        <div class="progress" style="height:10px;border-radius:5px;">
                            <div class="progress-bar bg-<?= $poin_cls ?>" style="width:<?= min(100, round($total_poin/100*100)) ?>%"></div>
                        </div>
                        <?php if ($total_poin < 100) { ?>
                        <small class="text-muted mt-1 d-block">Sisa <?= 100 - $total_poin ?> poin sebelum pemanggilan orang tua</small>
                        <?php } ?>
                    </div>
                </div>

                <!-- Pelanggaran semester sebelumnya yang belum selesai -->
                <?php if (count($pelanggaran_lama) > 0) { ?>
                <div class="card shadow-sm border-0 mb-4" style="border-radius:12px;border-left:4px solid #ff9800 !important;">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center mb-2" data-toggle="collapse" data-target="#collapse-lama" style="cursor:pointer;">
                            <div>
                                <i class="fas fa-history text-warning mr-1"></i>
                                <strong class="text-warning">Pelanggaran Semester Sebelumnya</strong>
                                <span class="badge badge-warning ml-1"><?= count($pelanggaran_lama) ?></span>
                            </div>
                            <i class="fas fa-chevron-down text-muted"></i>
                        </div>
                        <small class="text-muted d-block mb-2">Pelanggaran berikut dari semester sebelumnya masih aktif dan perlu diselesaikan.</small>
                        <div class="collapse" id="collapse-lama">
                            <?php foreach ($pelanggaran_lama as $pl) {
                                $kat_cls_l = 'success';
                                if ($pl['kategori']=='Sedang') $kat_cls_l='warning'; elseif ($pl['kategori']=='Berat') $kat_cls_l='danger'; elseif ($pl['kategori']=='Sangat Berat') $kat_cls_l='dark';
                                $is_dikurangi_l = $pl['status'] == 'Dikurangi';
                            ?>
                            <div class="border rounded p-2 mb-2" style="border-radius:8px !important;<?= $is_dikurangi_l ? 'opacity:0.7' : '' ?>">
                                <div class="d-flex align-items-start">
                                    <div class="mr-2 text-center" style="min-width:40px">
                                        <span class="badge badge-<?= $kat_cls_l ?>" style="font-size:14px;padding:5px 8px;"><?= $pl['poin_diberikan'] ?></span>
                                    </div>
                                    <div class="flex-fill">
                                        <small class="font-weight-bold d-block"><?= htmlspecialchars($pl['jenis_pelanggaran']) ?></small>
                                        <small class="text-muted">
                                            <?= date('d M Y', strtotime($pl['tanggal_kejadian'])) ?>
                                            | <span class="badge badge-secondary" style="font-size:9px;"><?= htmlspecialchars($pl['nama_semester'] ?? 'Semester lalu') ?></span>
                                            <?php if ($is_dikurangi_l) echo ' | <span class="badge badge-info" style="font-size:9px;">Dikurangi</span>'; ?>
                                        </small>
                                    </div>
                                    <?php if (!$is_dikurangi_l) { ?>
                                    <button class="btn btn-sm btn-outline-warning btn-sanggah" data-id="<?= $pl['pelanggaran_id'] ?>" data-jenis="<?= htmlspecialchars($pl['jenis_pelanggaran']) ?>" data-poin="<?= $pl['poin_diberikan'] ?>" style="font-size:11px;padding:2px 8px;">
                                        <i class="fas fa-hand-paper"></i>
                                    </button>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <!-- Tabs -->
                <ul class="nav nav-pills mb-3 justify-content-center" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-riwayat"><i class="fas fa-list mr-1"></i>Riwayat</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-sanggah"><i class="fas fa-hand-paper mr-1"></i>Sanggahan</a></li>
                </ul>
                <div class="tab-content">

                    <!-- Tab Riwayat -->
                    <div class="tab-pane fade show active" id="tab-riwayat">
                        <?php if (count($pelanggaran_list) > 0) {
                            foreach ($pelanggaran_list as $p) {
                                $kat_cls = 'success';
                                if ($p['kategori']=='Sedang') $kat_cls='warning'; elseif ($p['kategori']=='Berat') $kat_cls='danger'; elseif ($p['kategori']=='Sangat Berat') $kat_cls='dark';
                                $is_dikurangi = $p['status'] == 'Dikurangi';
                        ?>
                        <div class="card shadow-sm border-0 mb-3" style="border-radius:12px;<?= $is_dikurangi ? 'opacity:0.7' : '' ?>">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-start">
                                    <div class="mr-3 text-center" style="min-width:50px">
                                        <span class="badge badge-<?= $kat_cls ?>" style="font-size:18px;padding:8px 12px;"><?= $p['poin_diberikan'] ?></span>
                                        <br><small class="text-muted"><?= $p['kategori'] ?></small>
                                    </div>
                                    <div class="flex-fill">
                                        <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($p['jenis_pelanggaran']) ?></h6>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-book mr-1"></i><?= htmlspecialchars($p['kode_pasal'].' - '.$p['kode_ayat']) ?>
                                            | <i class="fas fa-calendar mr-1"></i><?= date('d M Y', strtotime($p['tanggal_kejadian'])) ?>
                                            <?php if ($p['is_pengulangan'] == 'Y') echo ' | <span class="badge badge-warning badge-sm">Pengulangan ke-'.$p['jumlah_pengulangan'].'</span>'; ?>
                                            <?php if ($is_dikurangi) echo ' | <span class="badge badge-info">Dikurangi</span>'; ?>
                                        </small>
                                        <?php if ($p['keterangan']) echo '<small class="text-muted d-block mt-1"><i class="fas fa-comment mr-1"></i>'.htmlspecialchars($p['keterangan']).'</small>'; ?>
                                    </div>
                                    <?php if (!$is_dikurangi) { ?>
                                    <div>
                                        <button class="btn btn-sm btn-outline-warning btn-sanggah" data-id="<?= $p['pelanggaran_id'] ?>" data-jenis="<?= htmlspecialchars($p['jenis_pelanggaran']) ?>" data-poin="<?= $p['poin_diberikan'] ?>">
                                            <i class="fas fa-hand-paper mr-1"></i>Sanggah
                                        </button>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php }
                        } else { ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-muted">Tidak ada pelanggaran</h5>
                            <p class="text-muted">Kamu belum memiliki catatan pelanggaran. Pertahankan!</p>
                        </div>
                        <?php } ?>
                    </div>

                    <!-- Tab Sanggahan -->
                    <div class="tab-pane fade" id="tab-sanggah">
                        <?php if (count($sanggah_list) > 0) {
                            foreach ($sanggah_list as $sg) {
                                $sg_cls = 'secondary';
                                switch($sg['status']){ case 'Menunggu': $sg_cls='warning'; break; case 'Disetujui': $sg_cls='success'; break; case 'Ditolak': $sg_cls='danger'; break; case 'Selesai': $sg_cls='info'; break; }
                        ?>
                        <div class="card shadow-sm border-0 mb-3" style="border-radius:12px;">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="font-weight-bold mb-1"><?= htmlspecialchars($sg['jenis_pelanggaran']) ?></h6>
                                        <small class="text-muted"><i class="fas fa-calendar mr-1"></i>Diajukan: <?= date('d M Y', strtotime($sg['tanggal_pengajuan'])) ?></small>
                                        <p class="mb-1 mt-1"><small><?= htmlspecialchars($sg['alasan']) ?></small></p>
                                        <?php if ($sg['catatan_admin']) echo '<small class="text-muted"><i class="fas fa-reply mr-1"></i>Admin: '.htmlspecialchars($sg['catatan_admin']).'</small>'; ?>
                                        <?php if ($sg['kesepakatan']) echo '<br><small class="text-primary"><i class="fas fa-handshake mr-1"></i>Syarat: '.htmlspecialchars($sg['kesepakatan']).'</small>'; ?>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-<?= $sg_cls ?>"><?= $sg['status'] ?></span>
                                        <?php if ($sg['poin_dikurangi'] > 0) echo '<br><small class="text-success font-weight-bold">-'.$sg['poin_dikurangi'].' poin</small>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php }
                        } else { ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada sanggahan</h5>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="text-center mt-3 mb-4">
                    <a href="./?mod=tata-tertib" class="btn btn-outline-primary btn-sm"><i class="fas fa-gavel mr-1"></i>Lihat Tata Tertib</a>
                </div>
            </div>
        </div>

        <!-- Modal Sanggahan -->
        <div class="modal fade" id="modal-sanggah-siswa" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px;">
                    <form id="form-sanggah-siswa" method="POST" action="./mod/poin/proses-sanggah.php">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title font-weight-bold"><i class="fas fa-hand-paper text-warning mr-2"></i>Ajukan Sanggahan</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="pelanggaran_id" id="sanggah-pel-id">
                            <div class="alert alert-light py-2" id="sanggah-pel-info"></div>
                            <div class="form-group">
                                <label class="font-weight-bold">Jenis Sanggahan</label>
                                <select class="form-control" name="jenis_sanggah" required>
                                    <option value="Pengurangan">Pengurangan Poin</option>
                                    <option value="Penghapusan">Penghapusan Poin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">Alasan</label>
                                <textarea class="form-control" name="alasan" rows="3" required placeholder="Jelaskan alasan kamu mengajukan sanggahan..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane mr-1"></i>Kirim Sanggahan</button>
                            <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        $(document).ready(function() {
            $(".btn-sanggah").on("click", function() {
                var id = $(this).data("id");
                var jenis = $(this).data("jenis");
                var poin = $(this).data("poin");
                $("#sanggah-pel-id").val(id);
                $("#sanggah-pel-info").html("<strong>" + jenis + "</strong> - " + poin + " poin");
                $("#modal-sanggah-siswa").modal("show");
            });

            $("#form-sanggah-siswa").on("submit", function(e) {
                e.preventDefault();
                var btn = $(this).find('button[type="submit"]');
                btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Mengirim...');
                $.ajax({
                    url: "./mod/poin/proses-sanggah.php",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function(res) {
                        if (res.status === "success") {
                            swal({ title: "Berhasil!", text: res.message, icon: "success" }).then(function() { location.reload(); });
                        } else {
                            swal("Gagal", res.message, "error");
                            btn.prop("disabled", false).html('<i class="fas fa-paper-plane mr-1"></i>Kirim Sanggahan');
                        }
                    },
                    error: function() {
                        swal("Error", "Terjadi kesalahan", "error");
                        btn.prop("disabled", false).html('<i class="fas fa-paper-plane mr-1"></i>Kirim Sanggahan');
                    }
                });
            });
        });
        </script>
<?php
    } else {
        echo '<div class="container mt-5">
            <div class="alert alert-warning text-center">
              <h4><i class="fas fa-exclamation-triangle"></i> Akses Ditolak</h4>
              <p>Silakan login untuk mengakses dashboard.</p>
              <a href="../" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login Sekarang</a>
            </div>
          </div>';
    }
}
?>
