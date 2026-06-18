<?php
/**
 * Halaman Survey Kepuasan Pelayanan (publik). Diisi tamu setelah check-out.
 */
$is_standalone = !isset($connection) || empty($site_name);
if (!isset($connection)) {
    include_once '../../library/config.php';
    include_once '../../library/function.php';
}

// ---- AJAX submit ----
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (($_POST['action'] ?? '') === 'submit')) {
    header('Content-Type: application/json');
    $gid = trim((string)($_POST['guest_id'] ?? ''));
    $rating = max(0, min(5, intval($_POST['rating'] ?? 0)));
    $pelayanan = max(0, min(5, intval($_POST['pelayanan'] ?? 0)));
    $kecepatan = max(0, min(5, intval($_POST['kecepatan'] ?? 0)));
    $kenyamanan = max(0, min(5, intval($_POST['kenyamanan'] ?? 0)));
    $komentar = trim((string)($_POST['komentar'] ?? ''));

    if ($gid === '') { echo json_encode(['status' => 'error', 'message' => 'Guest ID kosong']); exit; }
    if ($rating < 1) { echo json_encode(['status' => 'error', 'message' => 'Beri rating keseluruhan minimal 1 bintang']); exit; }

    $g = $connection->query("SELECT id FROM buku_tamu WHERE guest_id='" . $connection->real_escape_string($gid) . "' LIMIT 1");
    if (!$g || !$g->num_rows) { echo json_encode(['status' => 'error', 'message' => 'Data tamu tidak ditemukan']); exit; }
    $guest_table_id = intval($g->fetch_row()[0]);

    $stmt = $connection->prepare(
        "INSERT INTO buku_tamu_survey (guest_table_id, guest_id, rating, pelayanan, kecepatan, kenyamanan, komentar)
         VALUES (?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE rating=VALUES(rating), pelayanan=VALUES(pelayanan), kecepatan=VALUES(kecepatan),
           kenyamanan=VALUES(kenyamanan), komentar=VALUES(komentar)"
    );
    $stmt->bind_param('isiiiis', $guest_table_id, $gid, $rating, $pelayanan, $kecepatan, $kenyamanan, $komentar);
    if (!$stmt->execute()) { echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan survey']); exit; }
    $stmt->close();

    @$connection->query("UPDATE buku_tamu SET survey_done=1 WHERE id=$guest_table_id");
    if ($log = $connection->prepare("INSERT INTO buku_tamu_log (guest_table_id, guest_id, activity, ip_address, user_agent) VALUES (?,?,?,?,?)")) {
        $act = 'SURVEY';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $log->bind_param('issss', $guest_table_id, $gid, $act, $ip, $ua);
        $log->execute();
        $log->close();
    }
    echo json_encode(['status' => 'success', 'message' => 'Terima kasih! Survey Anda telah kami terima.']);
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
    <title>Survey Kepuasan | <?php echo htmlspecialchars($site); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body{background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);min-height:100vh}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card-sv{background:#fff;border-radius:20px;box-shadow:0 20px 40px rgba(0,0,0,.15);max-width:480px;width:100%;overflow:hidden}
        .head{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:24px 20px;text-align:center}
        .head img{width:64px;height:64px;border-radius:50%;background:#fff;padding:8px;border:3px solid rgba(255,255,255,.3)}
        .body{padding:24px 22px}
        .stars{font-size:30px;color:#e5e7eb;cursor:pointer}
        .stars i{transition:transform .1s}
        .stars i:hover{transform:scale(1.15)}
        .stars i.on{color:#f59e0b}
        .rate-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
        .btn-lg2{padding:13px;border-radius:12px;font-weight:600;width:100%}
    </style>
</head>
<body>
<?php endif; ?>
<div class="wrap">
  <div class="card-sv">
    <div class="head">
      <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" onerror="this.style.display='none'">
      <h4 class="mt-3 mb-1">Survey Kepuasan</h4>
      <div class="opacity-75 small">Bantu kami meningkatkan pelayanan</div>
    </div>
    <div class="body">
      <div id="svAlert"></div>
      <div id="svForm">
        <input type="hidden" id="guestId" value="<?php echo htmlspecialchars($prefill, ENT_QUOTES); ?>">
        <div class="text-center mb-3">
          <div class="fw-semibold mb-1">Penilaian Keseluruhan</div>
          <div class="stars" data-target="rating">
            <i class="far fa-star" data-v="1"></i><i class="far fa-star" data-v="2"></i><i class="far fa-star" data-v="3"></i><i class="far fa-star" data-v="4"></i><i class="far fa-star" data-v="5"></i>
          </div>
        </div>
        <hr>
        <div class="rate-row"><span>Pelayanan</span><div class="stars" data-target="pelayanan"><i class="far fa-star" data-v="1"></i><i class="far fa-star" data-v="2"></i><i class="far fa-star" data-v="3"></i><i class="far fa-star" data-v="4"></i><i class="far fa-star" data-v="5"></i></div></div>
        <div class="rate-row"><span>Kecepatan</span><div class="stars" data-target="kecepatan"><i class="far fa-star" data-v="1"></i><i class="far fa-star" data-v="2"></i><i class="far fa-star" data-v="3"></i><i class="far fa-star" data-v="4"></i><i class="far fa-star" data-v="5"></i></div></div>
        <div class="rate-row"><span>Kenyamanan</span><div class="stars" data-target="kenyamanan"><i class="far fa-star" data-v="1"></i><i class="far fa-star" data-v="2"></i><i class="far fa-star" data-v="3"></i><i class="far fa-star" data-v="4"></i><i class="far fa-star" data-v="5"></i></div></div>
        <div class="mb-3 mt-2">
          <label class="form-label fw-semibold">Komentar / Saran</label>
          <textarea id="komentar" class="form-control" rows="3" placeholder="Opsional"></textarea>
        </div>
        <input type="hidden" id="rating" value="0"><input type="hidden" id="pelayanan" value="0"><input type="hidden" id="kecepatan" value="0"><input type="hidden" id="kenyamanan" value="0">
        <button class="btn btn-warning btn-lg2" id="btnSubmit"><i class="fas fa-paper-plane me-2"></i>Kirim Survey</button>
      </div>
      <div id="svDone" style="display:none" class="text-center">
        <div class="mb-3"><i class="fas fa-heart text-danger" style="font-size:64px"></i></div>
        <h5>Terima Kasih!</h5>
        <p class="text-muted" id="svDoneMsg"></p>
        <a href="./form" class="btn btn-light btn-lg2">Selesai</a>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  function alertBox(t,m){ document.getElementById('svAlert').innerHTML='<div class="alert alert-'+t+'">'+m+'</div>'; }
  document.querySelectorAll('.stars').forEach(function(group){
    var target=group.getAttribute('data-target');
    group.querySelectorAll('i').forEach(function(star){
      star.addEventListener('click',function(){
        var v=parseInt(star.getAttribute('data-v'),10);
        document.getElementById(target).value=v;
        group.querySelectorAll('i').forEach(function(s){
          var sv=parseInt(s.getAttribute('data-v'),10);
          s.classList.toggle('fas',sv<=v); s.classList.toggle('far',sv>v); s.classList.toggle('on',sv<=v);
        });
      });
    });
  });
  document.getElementById('btnSubmit').addEventListener('click',function(){
    var gid=document.getElementById('guestId').value.trim();
    if(!gid){ alertBox('warning','ID Tamu tidak ditemukan. Buka halaman ini dari proses check-out.'); return; }
    if(parseInt(document.getElementById('rating').value,10)<1){ alertBox('warning','Beri penilaian keseluruhan minimal 1 bintang.'); return; }
    var btn=this; btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...';
    var fd=new FormData();
    fd.append('action','submit'); fd.append('guest_id',gid);
    ['rating','pelayanan','kecepatan','kenyamanan'].forEach(function(k){ fd.append(k,document.getElementById(k).value); });
    fd.append('komentar',document.getElementById('komentar').value);
    fetch('survey.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
      if(res.status==='success'){
        document.getElementById('svForm').style.display='none';
        document.getElementById('svDone').style.display='block';
        document.getElementById('svDoneMsg').textContent=res.message;
      } else { btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane me-2"></i>Kirim Survey'; alertBox('danger',res.message||'Gagal mengirim survey.'); }
    }).catch(function(){ btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane me-2"></i>Kirim Survey'; alertBox('danger','Terjadi kesalahan jaringan.'); });
  });
})();
</script>
<?php if ($is_standalone): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
