/**
 * Buku Tamu — scripts.js
 * Handles: dashboard (detail), form (QR/camera/register), checkout, survey.
 * Uses window.TAMU_PAGE and window.MODULE_BASE set by tamu.php
 */
(function () {
  var page = window.TAMU_PAGE || 'dashboard';
  var MB = window.MODULE_BASE || 'tamu/';

  /* ───────── FAB ───────── */
  var fc = document.getElementById('fabContainer');
  var fm = document.getElementById('fabMain');
  if (fc && fm) {
    fm.addEventListener('click', function (e) {
      e.stopPropagation();
      fc.classList.toggle('open');
      fm.setAttribute('aria-expanded', fc.classList.contains('open'));
    });
    document.addEventListener('click', function (e) {
      if (!fc.contains(e.target)) { fc.classList.remove('open'); fm.setAttribute('aria-expanded', 'false'); }
    });
  }

  /* ============================================================== */
  /*  DASHBOARD — detail tamu                                       */
  /* ============================================================== */
  if (page === 'dashboard') {
    var btnDetail = document.querySelectorAll('.btn-detail');
    btnDetail.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = this.getAttribute('data-id');
        var body = document.getElementById('detailBody');
        body.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Memuat...</div>';
        $('#modalDetail').modal('show');
        fetch(MB + '?page=proses&action=get_guest&id=' + id)
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (!d.status || d.status !== 'success') { body.innerHTML = '<div class="text-danger text-center py-4">Gagal</div>'; return; }
            var g = d.data;
            var fot = g.foto
              ? '<img src="content/tamu/' + encodeURIComponent(g.foto) + '" class="rounded-circle mb-2" style="width:80px;height:80px;object-fit:cover;cursor:pointer" onclick="document.getElementById(\'fotoImg\').src=this.src;$(\'#modalFoto\').modal(\'show\')" onerror="this.style.display=\'none\'">'
              : '<div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:80px;height:80px"><i class="fas fa-user text-white"></i></div>';
            var sb = g.status === 'Aktif' ? 'success' : (g.status === 'Selesai' ? 'secondary' : 'danger');
            body.innerHTML = '<div class="text-center mb-3">' + fot + '<h5 class="mb-1">' + esc(g.nama) + '</h5><p class="text-muted mb-0">' + esc(g.instansi) + '</p></div><table class="table table-sm"><tr><th width="120">Guest ID</th><td><code>' + esc(g.guest_id) + '</code></td></tr><tr><th>Telepon</th><td>' + esc(g.telepon || '-') + '</td></tr><tr><th>Keperluan</th><td><span class="badge badge-primary">' + esc(g.keperluan) + '</span></td></tr><tr><th>Keterangan</th><td>' + esc(g.keterangan || '-') + '</td></tr><tr><th>Tanggal</th><td>' + esc(g.tanggal_kunjungan) + '</td></tr><tr><th>Masuk</th><td>' + esc(g.waktu_masuk || '-') + '</td></tr><tr><th>Keluar</th><td>' + esc(g.waktu_keluar || '-') + '</td></tr><tr><th>Status</th><td><span class="badge badge-' + sb + '">' + esc(g.status) + '</span></td></tr></table>';
          })
          .catch(function () { body.innerHTML = '<div class="text-danger text-center py-4">Kesalahan jaringan</div>'; });
      });
    });
  }

  /* ============================================================== */
  /*  FORM — QR, form, camera, submit                               */
  /* ============================================================== */
  if (page === 'form') {
    var step = 1;

    function goStep(n) {
      document.querySelectorAll('.step-box').forEach(function (el) { el.classList.remove('active'); });
      document.getElementById('s' + n).classList.add('active');
      for (var i = 1; i <= 3; i++) {
        var d = document.getElementById('dot' + i);
        d.classList.remove('active', 'done', 'pending');
        d.classList.add(i < n ? 'done' : (i === n ? 'active' : 'pending'));
      }
      step = n;
      document.getElementById('progFill').style.width = ((n - 1) / 2 * 100) + '%';
      if (n !== 1 && qr) { try { qr.stop(); } catch (e) { } }
      if (n !== 3 && camStream) { stopCam(); }
    }

    function alertBox(id, type, msg) {
      document.getElementById(id).innerHTML = '<div class="alert-box alert-' + type + '">' + msg + '</div>';
    }

    var qr = null, camStream = null, captured = null, guestData = {};

    // --- Step 1: QR ---
    document.getElementById('startScan').addEventListener('click', function () {
      var c = document.getElementById('qrContainer');
      c.innerHTML = '<div id="qrReader" style="width:100%;height:100%"></div>';
      qr = new Html5Qrcode('qrReader');
      qr.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 200, height: 200 } },
        function (txt) {
          try { var o = JSON.parse(txt); if (o.nama) document.getElementById('fNama').value = o.nama; if (o.instansi) document.getElementById('fInstansi').value = o.instansi; } catch (e) { document.getElementById('fNama').value = txt; }
          alertBox('mfAlert', 'success', 'QR dipindai!');
          setTimeout(function () { goStep(2); }, 1200);
        },
        function () { }
      );
      this.style.display = 'none';
    });
    document.getElementById('skipScan').addEventListener('click', function () { goStep(2); });

    // --- Step 2: Form ---
    document.getElementById('backScan').addEventListener('click', function () { goStep(1); });
    document.getElementById('toPhoto').addEventListener('click', function () {
      var valid = document.getElementById('fNama').value.trim() && document.getElementById('fInstansi').value.trim() && document.getElementById('fKeperluan').value;
      if (!valid) { alertBox('mfAlert', 'warning', 'Lengkapi field wajib (*)'); return; }
      guestData = { nama: document.getElementById('fNama').value.trim(), instansi: document.getElementById('fInstansi').value.trim(), telepon: document.getElementById('fTelp').value.trim(), keperluan: document.getElementById('fKeperluan').value, keterangan: document.getElementById('fKet').value.trim() };
      goStep(3);
    });

    // --- Step 3: Camera ---
    document.getElementById('backForm').addEventListener('click', function () { goStep(2); });
    document.getElementById('startCam').addEventListener('click', function () {
      navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } }).then(function (s) {
        camStream = s;
        var v = document.getElementById('video');
        v.srcObject = s;
        v.style.display = 'block';
        document.getElementById('camPlace').style.display = 'none';
        document.getElementById('startCam').style.display = 'none';
        document.getElementById('captureBtn').style.display = 'inline-block';
      }).catch(function () { alertBox('mfAlert', 'danger', 'Akses kamera ditolak'); });
    });
    document.getElementById('captureBtn').addEventListener('click', function () {
      var v = document.getElementById('video'), c = document.getElementById('canvas');
      c.width = v.videoWidth; c.height = v.videoHeight;
      c.getContext('2d').drawImage(v, 0, 0);
      captured = c.toDataURL('image/jpeg', 0.8);
      v.style.display = 'none'; c.style.display = 'block';
      document.getElementById('captureBtn').style.display = 'none';
      document.getElementById('retakeBtn').style.display = 'inline-block';
      document.getElementById('submitBtn').disabled = false;
    });
    document.getElementById('retakeBtn').addEventListener('click', function () {
      document.getElementById('video').style.display = 'block';
      document.getElementById('canvas').style.display = 'none';
      document.getElementById('captureBtn').style.display = 'inline-block';
      document.getElementById('retakeBtn').style.display = 'none';
      document.getElementById('submitBtn').disabled = true;
      captured = null;
    });
    document.getElementById('submitBtn').addEventListener('click', function () {
      if (!captured) { alertBox('mfAlert', 'warning', 'Ambil foto terlebih dahulu'); return; }
      document.querySelectorAll('.step-box').forEach(function (e) { e.style.display = 'none'; });
      document.getElementById('mfLoad').style.display = 'block';
      var fd = new FormData();
      fd.append('action', 'simpan_tamu');
      Object.keys(guestData).forEach(function (k) { fd.append(k, guestData[k]); });
      var blob = (function (d) { var a = d.split(','), m = a[0].match(/:(.*?);/)[1], b = atob(a[1]), u = new Uint8Array(b.length); for (var i = 0; i < b.length; i++) u[i] = b.charCodeAt(i); return new Blob([u], { type: m }); })(captured);
      fd.append('foto', blob, 'selfie_' + Date.now() + '.jpg');
      fetch(MB + '?page=proses', { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
        document.getElementById('mfLoad').style.display = 'none';
        if (res.status !== 'success') { alertBox('mfAlert', 'danger', res.message || 'Gagal'); document.getElementById('s3').style.display = 'block'; return; }
        var qrB = res.qr_url ? '<div class="my-3"><img src="' + res.qr_url + '" style="width:180px;height:180px;border:1px solid #e5e7eb;border-radius:12px;padding:6px"><div class="small text-muted mt-2"><i class="fas fa-qrcode me-1"></i>Simpan QR ini. Scan saat <strong>keluar</strong> untuk check-out & survey.</div></div>' : '';
        document.querySelector('.mf-body').innerHTML = '<div class="text-center py-4"><div class="mb-3"><i class="fas fa-check-circle text-success" style="font-size:60px"></i></div><h4 class="text-success mb-2">Pendaftaran Berhasil!</h4><p class="text-muted mb-2">Terima kasih <strong>' + esc(guestData.nama) + '</strong><br>ID Tamu: <strong>' + res.guest_id + '</strong></p>' + qrB + '<button class="btn btn-primary w-100" onclick="location.reload()"><i class="fas fa-plus me-2"></i>Daftar Baru</button></div>';
        document.getElementById('progFill').style.width = '100%';
        for (var i = 1; i <= 3; i++) { var d = document.getElementById('dot' + i); d.classList.remove('active', 'pending'); d.classList.add('done'); }
      }).catch(function () { document.getElementById('mfLoad').style.display = 'none'; alertBox('mfAlert', 'danger', 'Kesalahan jaringan'); document.getElementById('s3').style.display = 'block'; });
    });

    function stopCam() {
      if (camStream) { camStream.getTracks().forEach(function (t) { t.stop(); }); camStream = null; }
      var v = document.getElementById('video'); if (v) v.style.display = 'none';
      var p = document.getElementById('camPlace'); if (p) p.style.display = 'block';
      document.getElementById('startCam').style.display = 'inline-block';
      document.getElementById('captureBtn').style.display = 'none';
    }
  }

  /* ============================================================== */
  /*  CHECKOUT                                                      */
  /* ============================================================== */
  if (page === 'checkout') {
    var scanner = null;
    function coAlert(t, m) { document.getElementById('coAlert').innerHTML = '<div class="alert-box alert-' + t + '">' + m + '</div>'; }
    function doCheckout(gid) {
      if (!gid) { coAlert('warning', 'Masukkan ID Tamu'); return; }
      var btn = document.getElementById('coBtn');
      btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
      var fd = new FormData(); fd.append('action', 'checkout'); fd.append('guest_id', gid);
      fetch(MB + '?page=proses', { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Check-out';
        if (res.status === 'success' || res.status === 'done') {
          document.getElementById('coForm').style.display = 'none';
          document.getElementById('coDone').style.display = 'block';
          document.getElementById('coDoneTitle').textContent = res.status === 'success' ? 'Check-out Berhasil' : 'Sudah Check-out';
          document.getElementById('coDoneMsg').textContent = res.message + (res.nama ? ' (' + res.nama + ')' : '');
          document.getElementById('coToSurvey').href = MB + '?page=survey&id=' + encodeURIComponent(res.guest_id);
        } else { coAlert('danger', res.message || 'Gagal'); }
      }).catch(function () { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>Check-out'; coAlert('danger', 'Kesalahan jaringan'); });
    }
    document.getElementById('coBtn').addEventListener('click', function () { doCheckout(document.getElementById('coGuestId').value.trim()); });
    document.getElementById('coScanBtn').addEventListener('click', function () {
      var w = document.getElementById('coReader');
      if (w.style.display === 'none') {
        w.style.display = 'block';
        scanner = new Html5Qrcode('reader');
        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, function (txt) {
          var gid = txt; var m = txt.match(/[?&]id=([^&]+)/);
          if (m) gid = decodeURIComponent(m[1]);
          document.getElementById('coGuestId').value = gid;
          scanner.stop().then(function () { w.style.display = 'none'; });
          doCheckout(gid);
        }).catch(function () { coAlert('danger', 'Akses kamera gagal'); });
      } else {
        if (scanner) { try { scanner.stop(); } catch (e) { } }
        w.style.display = 'none';
      }
    });
    if (window.coPrefill) { setTimeout(function () { doCheckout(window.coPrefill); }, 300); }
  }

  /* ============================================================== */
  /*  SURVEY                                                        */
  /* ============================================================== */
  if (page === 'survey') {
    function svAlert(t, m) { document.getElementById('svAlert').innerHTML = '<div class="alert-box alert-' + t + '">' + m + '</div>'; }
    document.querySelectorAll('.sv-stars').forEach(function (g) {
      var tgt = g.getAttribute('data-target');
      g.querySelectorAll('i').forEach(function (s) {
        s.addEventListener('click', function () {
          var v = parseInt(this.getAttribute('data-v'), 10);
          document.getElementById(tgt).value = v;
          g.querySelectorAll('i').forEach(function (s2) {
            var sv = parseInt(s2.getAttribute('data-v'), 10);
            s2.className = sv <= v ? 'fas fa-star on' : 'far fa-star';
          });
        });
      });
    });
    document.getElementById('svBtn').addEventListener('click', function () {
      var gid = document.getElementById('svGuestId').value.trim();
      if (!gid) { svAlert('warning', 'ID Tamu tidak ditemukan'); return; }
      if (parseInt(document.getElementById('rating').value, 10) < 1) { svAlert('warning', 'Beri rating minimal 1 bintang'); return; }
      var btn = this; btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...';
      var fd = new FormData();
      fd.append('action', 'submit_survey');
      fd.append('guest_id', gid);
      ['rating', 'pelayanan', 'kecepatan', 'kenyamanan'].forEach(function (k) { fd.append(k, document.getElementById(k).value); });
      fd.append('komentar', document.getElementById('komentar').value);
      fetch(MB + '?page=proses', { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
        if (res.status === 'success') {
          document.getElementById('svForm').style.display = 'none';
          document.getElementById('svDone').style.display = 'block';
          document.getElementById('svDoneMsg').textContent = res.message;
        } else { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Survey'; svAlert('danger', res.message || 'Gagal'); }
      }).catch(function () { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Survey'; svAlert('danger', 'Kesalahan jaringan'); });
    });
  }

  /* ───────── Helpers ───────── */
  function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
})();
