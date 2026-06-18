<?php
/**
 * Halaman Check-out Buku Tamu (publik).
 * Tamu memindai QR (yang berisi URL ini) saat keluar, lalu diarahkan ke survey.
 */
$is_standalone = !isset($connection) || empty($site_name);
if (!isset($connection)) {
    include_once '../../library/config.php';
    include_once '../../library/function.php';
}
header('X-Content-Type-Options: nosniff');

// ---- AJAX action: proses checkout ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (($_POST['action'] ?? '') === 'checkout')) {
    header('Content-Type: application/json');
    $gid = trim((string)($_POST['guest_id'] ?? ''));
    if ($gid === '') { echo json_encode(['status' => 'error', 'message' => 'Guest ID kosong']); exit; }
    $stmt = $connection->prepare("SELECT id, nama, status FROM buku_tamu WHERE guest_id=? LIMIT 1");
    $stmt->bind_param('s', $gid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) { echo json_encode(['status' => 'error', 'message' => 'Data tamu tidak ditemukan']); exit; }
    if ($row['status'] !== 'Aktif') {
        echo json_encode(['status' => 'done', 'message' => 'Anda sudah melakukan check-out sebelumnya.', 'guest_id' => $gid, 'nama' => $row['nama']]);
        exit;
    }
    $connection->query("UPDATE buku_tamu SET status='Selesai', waktu_keluar=CURTIME() WHERE id=" . intval($row['id']));
    // log
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    if ($log = $connection->prepare("INSERT INTO buku_tamu_log (guest_table_id, guest_id, activity, ip_address, user_agent) VALUES (?,?,?,?,?)")) {
        $act = 'CHECKOUT';
        $log->bind_param('issss', $row['id'], $gid, $act, $ip, $ua);
        $log->execute();
        $log->close();
    }
    echo json_encode(['status' => 'success', 'message' => 'Check-out berhasil. Terima kasih atas kunjungan Anda.', 'guest_id' => $gid, 'nama' => $row['nama']]);
    exit;
}

$prefill = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
$site = isset($site_name) ? $site_name : 'SMK Negeri 1 Pagelaran';
$logo = isset($site_logo) ? './content/' . $site_logo : './content/logoweb1.png';
?>
<?php if ($is_standalone): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-out Tamu | <?php echo htmlspecialchars($site); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body{background:linear-gradient(135deg,#0ea5e9 0%,#2563eb 100%);min-height:100vh}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card-co{background:#fff;border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,.15);max-width:480px;width:100%;overflow:hidden}
        .head{background:linear-gradient(135deg,#0ea5e9,#2563eb);color:#fff;padding:26px 20px;text-align:center}
        .head img{width:72px;height:72px;border-radius:50%;background:#fff;padding:8px;border:3px solid rgba(255,255,255,.3)}
        .body{padding:26px 22px}
        .btn-lg2{padding:13px;border-radius:12px;font-weight:600;width:100%}
        #reader{width:100%}
    </style>
</head>
<body>
<?php endif; ?>
<div class="wrap">
  <div class="card-co">
    <div class="head">
      <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" onerror="this.style.display='none'">
      <h4 class="mt-3 mb-1">Check-out Tamu</h4>
      <div class="opacity-75 small"><?php echo htmlspecialchars($site); ?></div>
    </div>
    <div class="body">
      <div id="coAlert"></div>

      <div id="coForm">
        <p class="text-muted">Pindai QR yang Anda terima saat datang, atau masukkan ID Tamu untuk check-out.</p>
        <div class="mb-3">
          <label class="form-label fw-semibold">ID Tamu</label>
          <input type="text" id="guestId" class="form-control form-control-lg" placeholder="GUEST-YYYYMMDD-XXXX" value="<?php echo htmlspecialchars($prefill, ENT_QUOTES); ?>">
        </div>
        <button class="btn btn-primary btn-lg2 mb-2" id="btnCheckout"><i class="fas fa-sign-out-alt me-2"></i>Check-out Sekarang</button>
        <button class="btn btn-outline-secondary btn-lg2" id="btnScan"><i class="fas fa-qrcode me-2"></i>Scan QR</button>
        <div id="readerWrap" class="mt-3" style="display:none"><div id="reader"></div></div>
      </div>

      <div id="coDone" style="display:none" class="text-center">
        <div class="mb-3"><i class="fas fa-check-circle text-success" style="font-size:64px"></i></div>
        <h5 id="doneTitle">Check-out Berhasil</h5>
        <p class="text-muted" id="doneMsg"></p>
        <a href="#" id="toSurvey" class="btn btn-warning btn-lg2 mb-2"><i class="fas fa-star me-2"></i>Isi Survey Kepuasan</a>
        <a href="./form" class="btn btn-light btn-lg2">Selesai</a>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
  var prefill = <?php echo json_encode($prefill); ?>;
  function alertBox(type, msg){ document.getElementById('coAlert').innerHTML='<div class="alert alert-'+type+'">'+msg+'</div>'; }

  function doCheckout(gid){
    if(!gid){ alertBox('warning','Masukkan ID Tamu terlebih dahulu.'); return; }
    var btn=document.getElementById('btnCheckout'); btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
    var fd=new FormData(); fd.append('action','checkout'); fd.append('guest_id',gid);
    fetch('checkout.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
      btn.disabled=false; btn.innerHTML='<i class="fas fa-sign-out-alt me-2"></i>Check-out Sekarang';
      if(res.status==='success' || res.status==='done'){
        document.getElementById('coForm').style.display='none';
        document.getElementById('coDone').style.display='block';
        document.getElementById('doneTitle').textContent = res.status==='success' ? 'Check-out Berhasil' : 'Sudah Check-out';
        document.getElementById('doneMsg').textContent = res.message + (res.nama? ' ('+res.nama+')' : '');
        document.getElementById('toSurvey').href = './survey.php?id=' + encodeURIComponent(res.guest_id);
      } else {
        alertBox('danger', res.message || 'Gagal melakukan check-out.');
      }
    }).catch(function(){ btn.disabled=false; btn.innerHTML='<i class="fas fa-sign-out-alt me-2"></i>Check-out Sekarang'; alertBox('danger','Terjadi kesalahan jaringan.'); });
  }

  document.getElementById('btnCheckout').addEventListener('click',function(){ doCheckout(document.getElementById('guestId').value.trim()); });

  // Scanner
  var scanner=null;
  document.getElementById('btnScan').addEventListener('click',function(){
    var wrap=document.getElementById('readerWrap');
    if(wrap.style.display==='none'){
      wrap.style.display='block';
      scanner=new Html5Qrcode('reader');
      scanner.start({facingMode:'environment'},{fps:10,qrbox:220},function(text){
        // QR berisi URL checkout.php?id=GUEST-...; ekstrak id-nya
        var gid=text;
        var m=text.match(/[?&]id=([^&]+)/);
        if(m){ gid=decodeURIComponent(m[1]); }
        document.getElementById('guestId').value=gid;
        scanner.stop().then(function(){ wrap.style.display='none'; });
        doCheckout(gid);
      }).catch(function(){ alertBox('danger','Tidak dapat mengakses kamera.'); });
    } else {
      if(scanner){ scanner.stop().catch(function(){}); }
      wrap.style.display='none';
    }
  });

  // Auto-checkout jika datang dari QR (ada ?id=)
  if(prefill){ doCheckout(prefill); }
})();
</script>
<?php if ($is_standalone): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
