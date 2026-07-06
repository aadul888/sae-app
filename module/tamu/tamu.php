<?php
/**
 * MODULE: BUKU TAMU — integrated mode only / single entry point.
 *   ?mod=tamu              → dashboard (SAE layout)
 *   ?mod=tamu&page=form    → registrasi tamu
 *   ?mod=tamu&page=checkout→ check-out
 *   ?mod=tamu&page=survey  → survey kepuasan
 *   ?mod=tamu&page=qr      → QR code generator (image)
 *   ?mod=tamu&page=proses  → AJAX handler → proses.php
 */
if (empty($connection)) { header('location:./'); exit; }
date_default_timezone_set('Asia/Jakarta');

$page = $_GET['page'] ?? 'dashboard';
$base = $base_url ?? './';

/* ================================================
 * AJAX HANDLER (proses.php)
 * ================================================ */
if ($page === 'proses') {
    require_once __DIR__ . '/proses.php';
    exit;
}

/* ================================================
 * QR GENERATOR (image/png)
 * ================================================ */
if ($page === 'qr') {
    $data = trim((string)($_GET['data'] ?? ''));
    if ($data === '' || strlen($data) > 600) { http_response_code(400); exit; }
    require_once __DIR__ . '/../../library/phpqrcode/phpqrcode.php';
    header('Content-Type: image/png');
    QRcode::png($data, null, QR_ECLEVEL_M, 5, 2);
    exit;
}

/* ================================================
 * FORM REGISTRASI
 * ================================================ */
if ($page === 'form'): ?>
<style>
:root{--primary:#2563eb;--primary-dark:#1d4ed8;--success:#059669;--warning:#d97706;--danger:#dc2626}
.mf-wrap{max-width:540px;margin:0 auto;padding:20px 0}
.mf-card{background:#fff;border-radius:20px;box-shadow:0 8px 30px rgba(0,0,0,.08);overflow:hidden}
.mf-head{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;padding:24px 20px;text-align:center}
.mf-head img{width:72px;height:72px;border-radius:50%;background:#fff;padding:8px;border:3px solid rgba(255,255,255,.3)}
/* progress */
.mf-progress{background:#fff;padding:16px 20px 0;border-bottom:1px solid #e5e7eb}
.mf-steps{display:flex;justify-content:space-between;position:relative;margin-bottom:16px}
.mf-steps::before{content:'';position:absolute;top:20px;left:20px;right:20px;height:2px;background:#e5e7eb;z-index:1}
.mf-fill{height:100%;background:var(--success);transition:width .5s}
.mf-step{display:flex;flex-direction:column;align-items:center;flex:1;position:relative;z-index:2}
.mf-dot{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;margin-bottom:6px;transition:.3s}
.mf-dot.active{background:var(--primary);transform:scale(1.1)}
.mf-dot.done{background:var(--success)}
.mf-dot.pending{background:#e5e7eb;color:#666}
.mf-stplabel{font-size:11px;color:#333;font-weight:500}
/* body */
.mf-body{padding:20px 22px}
.step-box{display:none}.step-box.active{display:block;animation:fadeUp .4s}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.scan-frame{width:220px;height:220px;border:3px dashed var(--primary);border-radius:12px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;background:#f3f4f6}
.scan-frame i{font-size:64px;color:var(--primary);opacity:.5}
#reader{width:100%;height:100%}
.form-group{margin-bottom:16px}
.form-group label{font-weight:600;color:#333;margin-bottom:6px;display:block;font-size:14px}
.form-group .form-control{border:2px solid #e5e7eb;border-radius:10px;padding:10px 14px;font-size:15px;width:100%}
.form-group .form-control:focus{border-color:var(--primary);outline:none;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.cam-box{width:260px;height:320px;border:3px solid var(--primary);border-radius:12px;margin:0 auto 16px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
.cam-box video,.cam-box canvas{width:100%;height:100%;object-fit:cover}
.cam-placeholder i{font-size:48px;color:var(--primary)}
.mf-loading{display:none;text-align:center;padding:30px}
.alert-box{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px}
.alert-success{background:#d1fae5;color:#065f46}
.alert-danger{background:#fee2e2;color:#991b1b}
.alert-warning{background:#fef3c7;color:#92400e}
.alert-info{background:#dbeafe;color:#1e40af}
@media(max-width:576px){.mf-wrap{padding:10px 0}.mf-body{padding:16px}.scan-frame{width:100%}.cam-box{width:100%}}
</style>
<div class="module-home-container"><div class="module-home-content">
<div class="mf-wrap">
<div class="mf-card">
  <div class="mf-head">
    <img src="<?php echo $base; ?>content/<?php echo $site_logo??'logoweb1.png'; ?>" alt="Logo" onerror="this.style.display='none'">
    <h4 class="mt-2 mb-1">Buku Tamu Digital</h4>
    <div class="opacity-75 small"><?php echo htmlspecialchars($site_name??'Smart Apps Education'); ?></div>
  </div>
  <div class="mf-progress">
    <div class="mf-steps">
      <div class="mf-steps-line"><div class="mf-fill" id="progFill" style="width:0%"></div></div>
      <div class="mf-step"><div class="mf-dot active" id="dot1"><i class="fas fa-qrcode"></i></div><span class="mf-stplabel">Scan QR</span></div>
      <div class="mf-step"><div class="mf-dot pending" id="dot2"><i class="fas fa-edit"></i></div><span class="mf-stplabel">Isi Data</span></div>
      <div class="mf-step"><div class="mf-dot pending" id="dot3"><i class="fas fa-camera"></i></div><span class="mf-stplabel">Foto</span></div>
    </div>
  </div>
  <div class="mf-body">
    <div id="mfAlert"></div>
    <!-- Step 1: QR -->
    <div class="step-box active" id="s1">
      <div class="text-center">
        <div class="scan-frame" id="qrContainer"><i class="fas fa-qrcode"></i></div>
        <p class="text-muted mb-3"><strong>Pindai QR Code</strong><br>Arahkan kamera ke QR</p>
        <button class="btn btn-primary w-100 mb-2" id="startScan"><i class="fas fa-camera me-2"></i>Mulai Scan</button>
        <button class="btn btn-outline-secondary w-100" id="skipScan"><i class="fas fa-forward me-2"></i>Lewati</button>
      </div>
    </div>
    <!-- Step 2: Form -->
    <div class="step-box" id="s2">
      <form id="guestForm" autocomplete="off">
        <div class="form-group"><label>Nama Lengkap <span class="text-danger">*</span></label><input type="text" class="form-control" id="fNama" required></div>
        <div class="form-group"><label>Asal Instansi <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="fInstansi" list="instansiList" placeholder="Ketik / pilih" required>
          <datalist id="instansiList"><?php
            $ri = $connection->query("SELECT nama FROM tamu_instansi WHERE active='Y' ORDER BY nama ASC LIMIT 300");
            if ($ri) while ($i = $ri->fetch_assoc()) echo '<option value="'.htmlspecialchars($i['nama'],ENT_QUOTES).'">';
          ?></datalist>
        </div>
        <div class="form-group"><label>No. Telepon</label><input type="tel" class="form-control" id="fTelp"></div>
        <div class="form-group"><label>Keperluan <span class="text-danger">*</span></label>
          <select class="form-control" id="fKeperluan" required>
            <option value="">Pilih...</option>
            <?php
            $rt = $connection->query("SELECT nama FROM tamu_tujuan WHERE active='Y' ORDER BY nama ASC");
            $has = false;
            if ($rt && $rt->num_rows) { while ($t = $rt->fetch_assoc()) { echo '<option value="'.htmlspecialchars($t['nama'],ENT_QUOTES).'">'.htmlspecialchars($t['nama']).'</option>'; $has=true; } }
            if (!$has) foreach (['Rapat/Meeting','Konsultasi','Kunjungan Kerja','Penelitian','Magang/PKL','Wawancara','Lainnya'] as $o) echo '<option value="'.htmlspecialchars($o,ENT_QUOTES).'">'.htmlspecialchars($o).'</option>';
            ?>
          </select>
        </div>
        <div class="form-group"><label>Keterangan</label><textarea class="form-control" id="fKet" rows="2" placeholder="Detail keperluan..."></textarea></div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary flex-fill" id="backScan"><i class="fas fa-arrow-left me-2"></i>Kembali</button>
          <button type="button" class="btn btn-primary flex-fill" id="toPhoto">Lanjut <i class="fas fa-arrow-right ms-2"></i></button>
        </div>
      </form>
    </div>
    <!-- Step 3: Foto -->
    <div class="step-box" id="s3">
      <div class="text-center">
        <div class="cam-box" id="camBox">
          <div class="cam-placeholder" id="camPlace"><i class="fas fa-camera"></i></div>
          <video id="video" style="display:none" autoplay playsinline></video>
          <canvas id="canvas" style="display:none"></canvas>
        </div>
        <div id="photoActions">
          <button class="btn btn-primary w-100 mb-2" id="startCam"><i class="fas fa-video me-2"></i>Aktifkan Kamera</button>
          <button class="btn btn-success w-100 mb-2" id="captureBtn" style="display:none"><i class="fas fa-camera me-2"></i>Ambil Foto</button>
          <button class="btn btn-outline-secondary w-100 mb-2" id="retakeBtn" style="display:none"><i class="fas fa-redo me-2"></i>Ulangi</button>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary flex-fill" id="backForm"><i class="fas fa-arrow-left me-2"></i>Kembali</button>
          <button type="button" class="btn btn-success flex-fill" id="submitBtn" disabled><i class="fas fa-check me-2"></i>Selesai</button>
        </div>
      </div>
    </div>
    <!-- Loading -->
    <div class="mf-loading" id="mfLoad"><div class="spinner-border text-primary mb-2"></div><p>Menyimpan data...</p></div>
  </div>
</div></div>
</div></div>
<script>window.TAMU_PAGE='form';window.MODULE_BASE=<?php echo json_encode(rtrim($base,'/').'/tamu/'); ?>;</script>
<?php

/* ================================================
 * CHECKOUT
 * ================================================ */
elseif ($page === 'checkout'):
$prefill = trim((string)($_GET['id'] ?? ''));
$site = htmlspecialchars($site_name??'Smart Apps Education');
$logo = $base.'content/'.($site_logo??'logoweb1.png');
?>
<style>
.co-wrap{min-height:60vh;display:flex;align-items:center;justify-content:center;padding:20px}
.co-card{background:#fff;border-radius:20px;box-shadow:0 8px 30px rgba(0,0,0,.08);max-width:480px;width:100%;overflow:hidden}
.co-head{background:linear-gradient(135deg,#0ea5e9,#2563eb);color:#fff;padding:24px 20px;text-align:center}
.co-head img{width:64px;height:64px;border-radius:50%;background:#fff;padding:6px;border:3px solid rgba(255,255,255,.3)}
.co-body{padding:24px 22px}
</style>
<div class="module-home-container"><div class="module-home-content">
<div class="co-wrap">
  <div class="co-card">
    <div class="co-head">
      <img src="<?php echo $logo; ?>" alt="Logo" onerror="this.style.display='none'">
      <h4 class="mt-2 mb-1">Check-out Tamu</h4>
      <div class="opacity-75 small"><?php echo $site; ?></div>
    </div>
    <div class="co-body">
      <div id="coAlert"></div>
      <div id="coForm">
        <p class="text-muted">Pindai QR atau masukkan ID Tamu untuk check-out.</p>
        <div class="mb-3"><label class="fw-semibold small">ID Tamu</label>
          <input type="text" id="coGuestId" class="form-control" placeholder="GUEST-YYYYMMDD-XXXX" value="<?php echo htmlspecialchars($prefill,ENT_QUOTES); ?>">
        </div>
        <button class="btn btn-primary w-100 mb-2" id="coBtn"><i class="fas fa-sign-out-alt me-2"></i>Check-out</button>
        <button class="btn btn-outline-secondary w-100" id="coScanBtn"><i class="fas fa-qrcode me-2"></i>Scan QR</button>
        <div id="coReader" class="mt-3" style="display:none"><div id="reader"></div></div>
      </div>
      <div id="coDone" style="display:none" class="text-center py-3">
        <div><i class="fas fa-check-circle text-success" style="font-size:48px"></i></div>
        <h5 class="mt-2" id="coDoneTitle">Berhasil</h5>
        <p class="text-muted" id="coDoneMsg"></p>
        <a href="#" id="coToSurvey" class="btn btn-warning w-100 mb-2"><i class="fas fa-star me-2"></i>Isi Survey</a>
        <a href="<?php echo $base; ?>tamu/form" class="btn btn-light w-100">Selesai</a>
      </div>
    </div>
  </div>
</div>
</div></div>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>window.TAMU_PAGE='checkout';window.MODULE_BASE=<?php echo json_encode(rtrim($base,'/').'/tamu/'); ?>;window.coPrefill=<?php echo json_encode($prefill); ?>;</script>
<?php

/* ================================================
 * SURVEY
 * ================================================ */
elseif ($page === 'survey'):
$prefill = trim((string)($_GET['id'] ?? ''));
$site = htmlspecialchars($site_name??'Smart Apps Education');
$logo = $base.'content/'.($site_logo??'logoweb1.png');
?>
<style>
.sv-wrap{min-height:60vh;display:flex;align-items:center;justify-content:center;padding:20px}
.sv-card{background:#fff;border-radius:20px;box-shadow:0 8px 30px rgba(0,0,0,.08);max-width:480px;width:100%;overflow:hidden}
.sv-head{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:20px;text-align:center}
.sv-head img{width:56px;height:56px;border-radius:50%;background:#fff;padding:6px;border:3px solid rgba(255,255,255,.3)}
.sv-body{padding:22px}
.sv-stars{font-size:28px;color:#e5e7eb;cursor:pointer;display:inline-flex;gap:2px}
.sv-stars i.on{color:#f59e0b}
</style>
<div class="module-home-container"><div class="module-home-content">
<div class="sv-wrap">
  <div class="sv-card">
    <div class="sv-head">
      <img src="<?php echo $logo; ?>" alt="Logo" onerror="this.style.display='none'">
      <h4 class="mt-2 mb-1">Survey Kepuasan</h4>
      <div class="opacity-75 small">Bantu kami meningkatkan pelayanan</div>
    </div>
    <div class="sv-body">
      <div id="svAlert"></div>
      <div id="svForm">
        <input type="hidden" id="svGuestId" value="<?php echo htmlspecialchars($prefill,ENT_QUOTES); ?>">
        <div class="text-center mb-3">
          <div class="fw-semibold mb-1">Penilaian Keseluruhan</div>
          <div class="sv-stars" data-target="rating"><?php for($i=1;$i<=5;$i++) echo '<i class="far fa-star" data-v="'.$i.'"></i>'; ?></div>
        </div>
        <hr>
        <?php foreach (['pelayanan'=>'Pelayanan','kecepatan'=>'Kecepatan','kenyamanan'=>'Kenyamanan'] as $k => $l): ?>
        <div class="d-flex justify-content-between align-items-center mb-2"><span><?php echo $l; ?></span><div class="sv-stars" data-target="<?php echo $k; ?>"><?php for($i=1;$i<=5;$i++) echo '<i class="far fa-star" data-v="'.$i.'"></i>'; ?></div></div>
        <?php endforeach; ?>
        <?php foreach(['rating','pelayanan','kecepatan','kenyamanan'] as $k): ?><input type="hidden" id="<?php echo $k; ?>" value="0"><?php endforeach; ?>
        <div class="mb-3 mt-3"><label class="fw-semibold small">Komentar / Saran</label><textarea id="komentar" class="form-control" rows="3" placeholder="Opsional"></textarea></div>
        <button class="btn btn-warning w-100" id="svBtn"><i class="fas fa-paper-plane me-2"></i>Kirim Survey</button>
      </div>
      <div id="svDone" style="display:none" class="text-center py-3">
        <div><i class="fas fa-heart text-danger" style="font-size:48px"></i></div>
        <h5 class="mt-2">Terima Kasih!</h5>
        <p class="text-muted" id="svDoneMsg"></p>
      </div>
    </div>
  </div>
</div>
</div></div>
<script>window.TAMU_PAGE='survey';window.MODULE_BASE=<?php echo json_encode(rtrim($base,'/').'/tamu/'); ?>;</script>
<?php

/* ================================================
 * DASHBOARD (default)
 * ================================================ */
else:
$wa_number = '62'.ltrim(preg_replace('/[^0-9]/','',$site_phone??'08151800116'),'0');
$stats = [];
foreach (['hari'=>"COUNT(*) FROM buku_tamu WHERE tanggal_kunjungan = CURDATE()",
           'minggu'=>"COUNT(*) FROM buku_tamu WHERE YEARWEEK(tanggal_kunjungan,1)=YEARWEEK(CURDATE(),1)",
           'bulan'=>"COUNT(*) FROM buku_tamu WHERE YEAR(tanggal_kunjungan)=YEAR(CURDATE()) AND MONTH(tanggal_kunjungan)=MONTH(CURDATE())",
           'aktif'=>"COUNT(*) FROM buku_tamu WHERE status='Aktif'"] as $k=>$q) {
    $r = $connection->query("SELECT $q"); $stats[$k] = $r ? intval($r->fetch_row()[0]) : 0;
}
$recent = [];
$rg = $connection->query("SELECT * FROM buku_tamu ORDER BY created_at DESC LIMIT 10");
if ($rg) { while ($rw = $rg->fetch_assoc()) $recent[] = $rw; }
?>
<div class="sae-landing">
<section class="sae-hero" aria-label="Hero Buku Tamu">
  <div class="sae-hero-bg"></div>
  <div class="sae-hero-inner">
    <div class="sae-hero-copy">
      <span class="sae-hero-kicker"><i class="fas fa-circle"></i> Layanan Buku Tamu</span>
      <h1 class="sae-hero-title">Buku Tamu <span class="sae-hero-accent">Digital</span></h1>
      <p class="sae-hero-subtitle">Pencatatan kunjungan tamu secara digital — QR check-in, selfie, check-out, dan survey.</p>
      <div class="sae-tech-strip">
        <span class="sae-tech-badge"><i class="fas fa-qrcode"></i> QR Code</span>
        <span class="sae-tech-badge"><i class="fas fa-sign-in-alt"></i> Check-in</span>
        <span class="sae-tech-badge"><i class="fas fa-sign-out-alt"></i> Check-out</span>
        <span class="sae-tech-badge"><i class="fas fa-camera"></i> Selfie</span>
        <span class="sae-tech-badge"><i class="fas fa-star"></i> Survey</span>
      </div>
    </div>
    <div class="sae-hero-right">
      <div class="sae-nisn-panel">
        <div class="sae-nisn-panel-head"><h6><i class="fas fa-door-open me-2"></i>Sedang Berkunjung</h6></div>
        <div class="sae-nisn-panel-body text-center">
          <div class="display-3 fw-bold text-primary"><?php echo $stats['aktif']; ?></div>
          <p class="text-muted mb-3">tamu aktif saat ini</p>
          <a href="<?php echo $base; ?>tamu/form" class="btn btn-primary w-100"><i class="fas fa-user-plus me-2"></i>Registrasi Tamu Baru</a>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="sae-kpi-strip">
  <?php foreach ([['blue','fa-calendar-day','hari','Hari Ini'],['teal','fa-calendar-week','minggu','Minggu Ini'],['green','fa-calendar-alt','bulan','Bulan Ini'],['orange','fa-user-clock','aktif','Sedang Aktif']] as $k): ?>
  <div class="sae-kpi-card">
    <span class="sae-kpi-icon <?php echo $k[0]; ?>"><i class="fas <?php echo $k[1]; ?>"></i></span>
    <div><div class="sae-kpi-value"><?php echo number_format($stats[$k[2]]); ?></div><p class="sae-kpi-label"><?php echo $k[3]; ?></p></div>
  </div>
  <?php endforeach; ?>
</section>
<section class="glass-card card">
  <div class="card-body">
    <div class="home-insight-head"><h5><i class="fas fa-history me-2"></i>Tamu Terbaru</h5><p>10 kunjungan terbaru.</p></div>
    <div class="table-responsive">
      <table class="module-home-table table table-sm" id="tamuTable">
        <thead><tr><th>#</th><th>Guest ID</th><th>Nama / Instansi</th><th>Keperluan</th><th>Tanggal</th><th>Jam</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
        <tbody><?php if (!$recent): ?><tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox me-1"></i>Belum ada tamu</td></tr>
          <?php else: $no=1; foreach ($recent as $g): $sc=['Aktif'=>'success','Selesai'=>'secondary','Batal'=>'danger']; ?>
            <tr>
              <td><?php echo $no++; ?></td>
              <td><code><?php echo htmlspecialchars($g['guest_id']); ?></code></td>
              <td><strong><?php echo htmlspecialchars($g['nama']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($g['instansi']); ?></small></td>
              <td><span class="badge badge-primary"><?php echo htmlspecialchars($g['keperluan']); ?></span></td>
              <td><small><?php echo date('d/m/Y',strtotime($g['tanggal_kunjungan'])); ?></small></td>
              <td><small><?php echo substr($g['waktu_masuk'],0,5); ?></small></td>
              <td><span class="badge badge-<?php echo $sc[$g['status']]??'secondary'; ?>"><?php echo $g['status']; ?></span></td>
              <td class="text-center"><a href="javascript:void(0)" class="btn-detail" data-id="<?php echo $g['id']; ?>"><i class="fas fa-eye"></i></a></td>
            </tr>
          <?php endforeach; endif; ?></tbody>
      </table>
    </div>
  </div>
</section>
</div>
<!-- Modals -->
<div class="modal fade" id="modalDetail" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary"><h5 class="modal-title text-white"><i class="fas fa-id-card me-2"></i>Detail Tamu</h5><button class="close text-white" data-dismiss="modal">&times;</button></div><div class="modal-body" id="detailBody"><div class="text-center py-4 text-muted">Memuat...</div></div></div></div></div>
<div class="modal fade" id="modalFoto" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Foto Tamu</h5><button class="close" data-dismiss="modal">&times;</button></div><div class="modal-body text-center"><img src="" alt="Foto" class="img-fluid rounded" id="fotoImg" style="max-height:400px"></div></div></div></div>
<!-- FAB -->
<div class="fab-container" id="fabContainer" role="navigation">
  <button class="fab-main pulse" id="fabMain" type="button" title="Menu" aria-expanded="false"><i class="fas fa-plus"></i></button>
  <div class="fab-items" role="menu">
    <a href="<?php echo $base; ?>home" class="fab-item"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="<?php echo $base; ?>tamu/form" class="fab-item"><i class="fas fa-user-plus"></i><span>Registrasi</span></a>
    <a href="https://wa.me/<?php echo $wa_number; ?>" target="_blank" class="fab-item"><i class="fab fa-whatsapp"></i><span>WhatsApp</span></a>
    <a href="<?php echo $base; ?>login" class="fab-item"><i class="fas fa-sign-in-alt"></i><span>Login</span></a>
  </div>
</div>
<script>window.TAMU_PAGE='dashboard';window.MODULE_BASE=<?php echo json_encode(rtrim($base,'/').'/tamu/'); ?>;</script>
<?php endif; ?>
