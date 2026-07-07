"use strict";

// Konfigurasi validasi nomor KKS / KIP (dapat disesuaikan)
const KKS_MIN = 6;
const KKS_MAX = 16;
const KIP_MIN = 6;
const KIP_MAX = 16;

$(document).ready(function () {
  
  // Cegah alert Bootstrap dari auto-dismiss dan binding close
  $('.alert').off('click', '[data-dismiss="alert"]');
  $('.alert').find('[data-dismiss="alert"]').remove();
  $('.alert').removeClass('alert-dismissible');
  
  // Tambahkan CSS untuk memastikan alert tidak bisa dihilangkan
  $('<style>')
    .prop('type', 'text/css')
    .html(`
      .persistent-alert {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        position: relative !important;
        z-index: 1000 !important;
      }
      .persistent-alert .close,
      .persistent-alert .btn-close,
      .persistent-alert [data-dismiss="alert"],
      .persistent-alert [data-bs-dismiss="alert"] {
        display: none !important;
      }
      .alert.fade {
        opacity: 1 !important;
      }
      .alert.show {
        display: block !important;
      }
      .persistent-alert .btn {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
      }
      .persistent-alert .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      }
    `)
    .appendTo('head');
  
  // Override Bootstrap alert methods yang menyembunyikan alert
  if (window.bootstrap && window.bootstrap.Alert) {
    const originalAlert = window.bootstrap.Alert;
    window.bootstrap.Alert.prototype.close = function() {
      if (this._element && $(this._element).hasClass('persistent-alert')) {
        return; // Jangan close persistent alert
      }
      return originalAlert.prototype.close.call(this);
    };
  }
  
  // Override jQuery hide methods untuk persistent alerts
  const originalHide = $.fn.hide;
  const originalFadeOut = $.fn.fadeOut;
  
  $.fn.hide = function() {
    if (this.hasClass('persistent-alert') || this.hasClass('alert')) {
      return this; // Jangan hide alert
    }
    return originalHide.apply(this, arguments);
  };
  
  $.fn.fadeOut = function() {
    if (this.hasClass('persistent-alert') || this.hasClass('alert')) {
      return this; // Jangan fadeOut alert
    }
    return originalFadeOut.apply(this, arguments);
  };
  
  // Fungsi untuk mempertahankan alert box
  function preserveAlerts() {
    $('.alert, .persistent-alert').each(function() {
      $(this).off('closed.bs.alert close.bs.alert hide.bs.alert hidden.bs.alert');
      $(this).find('button.close, .btn-close, [data-dismiss="alert"], [data-bs-dismiss="alert"]').remove();
      $(this).show().css({
        'display': 'block',
        'opacity': '1',
        'visibility': 'visible'
      });
    });
  }
  
  // Jalankan preserve alerts segera
  preserveAlerts();
  
  // Set interval yang lebih cepat untuk memastikan alert tetap terlihat
  setInterval(preserveAlerts, 100); // setiap 100ms
  
  // Tambahkan MutationObserver untuk monitor perubahan DOM
  if (window.MutationObserver) {
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' || mutation.type === 'childList') {
          preserveAlerts();
        }
      });
    });
    
    // Monitor seluruh document untuk perubahan
    observer.observe(document.body, {
      attributes: true,
      childList: true,
      subtree: true,
      attributeFilter: ['style', 'class']
    });
  }
  
  // Tambahkan event handler untuk tombol kembali ke beranda
  $(document).on('click', '.persistent-alert a[href="./"]', function(e) {
    e.preventDefault();
    
    const button = $(this);
    const alertType = button.closest('.alert').hasClass('alert-success') ? 'success' : 
                     button.closest('.alert').hasClass('alert-danger') ? 'error' : 'info';
    
    let confirmMessage = '';
    let confirmTitle = 'Kembali ke Beranda?';
    
    if (alertType === 'success') {
      confirmTitle = 'Usulan Berhasil Dikirim!';
      confirmMessage = 'Usulan PIP Anda telah berhasil dikirim dan sedang menunggu verifikasi. Anda akan kembali ke beranda.';
    } else if (alertType === 'error') {
      confirmTitle = 'Kembali ke Beranda?';
      confirmMessage = 'Apakah Anda yakin ingin kembali ke beranda? Pastikan Anda telah mencatat pesan error untuk diperbaiki nanti.';
    } else {
      confirmTitle = 'Kembali ke Beranda?';
      confirmMessage = 'Apakah Anda yakin ingin kembali ke beranda?';
    }
    
    if (hasSwal()) {
      Swal.fire({
        title: confirmTitle,
        html: confirmMessage,
        icon: alertType === 'success' ? 'success' : alertType === 'error' ? 'warning' : 'info',
        showCancelButton: alertType !== 'success',
        confirmButtonText: 'Ya, Kembali ke Beranda',
        cancelButtonText: 'Tetap di Sini',
        confirmButtonColor: alertType === 'success' ? '#28a745' : '#007bff',
        customClass: {
          popup: 'swal-custom-popup',
          confirmButton: 'btn btn-' + (alertType === 'success' ? 'success' : 'primary'),
          cancelButton: 'btn btn-secondary'
        }
      }).then((result) => {
        if (result.isConfirmed || alertType === 'success') {
          // Redirect with smooth transition
          $('body').fadeOut(300, function() {
            window.location.href = './';
          });
        }
      });
    } else {
      const confirmed = alertType === 'success' || confirm(confirmTitle + '\n\n' + confirmMessage);
      if (confirmed) {
        window.location.href = './';
      }
    }
  });

  // Kunci form jika sudah pernah mengajukan pada semester ini (saat halaman dimuat)
  var form = document.getElementById("formUsulanPip");
  if (form) {
    checkSemesterLimit().then(function (resp) {
      if (!resp.allowed) {
        if (resp.status === 'approved') {
          // Usulan sudah disetujui
          if (hasSwal()) {
            Swal.fire({ 
              title: "Usulan Disetujui", 
              html: "Selamat! Usulan PIP Anda telah disetujui dan sedang dalam proses. Silakan tunggu informasi selanjutnya dari sekolah.", 
              icon: "success", 
              confirmButtonText: "OK" 
            });
          } else if (hasOldSwal()) {
            swal("Usulan Disetujui", "Selamat! Usulan PIP Anda telah disetujui dan sedang dalam proses.", "success");
          } else {
            alert("Usulan Disetujui\nSelamat! Usulan PIP Anda telah disetujui dan sedang dalam proses.");
          }
        } else {
          // Usulan masih pending
          if (hasSwal()) {
            Swal.fire({ 
              title: "Silahkan Tunggu", 
              html: "Usulan sudah terkirim sebelumnya dan sedang dalam proses Verval dan Input.", 
              icon: "warning", 
              confirmButtonText: "OK" 
            });
          } else if (hasOldSwal()) {
            swal("Silahkan Tunggu", "Usulan sudah terkirim dan sedang dalam proses Verval dan Input.", "warning");
          } else {
            alert("Silahkan Tunggu\nUsulan sudah terkirim dan sedang dalam proses Verval dan Input.");
          }
        }
        $(form).find('input, select, textarea, button').prop('disabled', true);
      } else if (resp.rejected_usulan) {
        // Jika ada usulan yang ditolak, tampilkan pesan tapi biarkan form terbuka
        var rejectedData = resp.rejected_usulan;
        
        // Delay sedikit agar elemen HTML catatan sudah ter-render
        setTimeout(function() {
          if (hasSwal()) {
            Swal.fire({ 
              title: "Usulan Ditolak", 
              html: "Usulan terakhir Anda ditolak. Cek Catatan. Silakan perbaiki data dan ajukan ulang.", 
              icon: "error", 
              confirmButtonText: "OK" 
            });
          } else if (hasOldSwal()) {
            swal("Usulan Ditolak", "Usulan terakhir Anda ditolak. Cek Catatan. Silakan perbaiki data dan ajukan ulang.", "error");
          } else {
            alert("Usulan Ditolak\nUsulan terakhir Anda ditolak. Cek Catatan. Silakan perbaiki data dan ajukan ulang.");
          }
        }, 500); // delay 500ms
        // Form tetap terbuka untuk usulan ulang
      }
    });
  }
  // Helper untuk alert/confirm (Swal preferred, fallback to swal or native)
  function hasSwal() { return typeof Swal !== "undefined" && Swal.fire; }
  function hasOldSwal() { return typeof swal !== "undefined"; }

  function alertInfo(title, text, cb) {
    if (hasSwal()) {
      Swal.fire({ title: title, html: text, icon: "info", confirmButtonText: "OK" }).then(cb || function() {});
    } else if (hasOldSwal()) {
      swal(title, text, "info").then(cb || function(){});
    } else {
      alert(title + "\n" + (text || ""));
      if (cb) cb();
    }
  }

  function alertError(title, text) {
    if (hasSwal()) {
      Swal.fire({ title: title, html: text, icon: "error", confirmButtonText: "OK" });
    } else if (hasOldSwal()) {
      swal(title, text, "error");
    } else {
      alert(title + "\n" + (text || ""));
    }
  }

  function confirmAction(opts) {
    if (hasSwal()) {
      return Swal.fire({
        title: opts.title || "Konfirmasi",
        html: opts.html || "",
        icon: opts.icon || "warning",
        showCancelButton: true,
        confirmButtonText: opts.confirmText || "Ya",
        cancelButtonText: opts.cancelText || "Batal"
      });
    }
    if (hasOldSwal()) {
      return swal({
        title: opts.title || "Konfirmasi",
        text: opts.plainText || opts.html || "",
        icon: opts.icon || "warning",
        buttons: [opts.cancelText || "Batal", opts.confirmText || "Ya"]
      });
    }
    return new Promise(function (resolve) {
      var ok = confirm((opts.title ? opts.title + "\n\n" : "") + (opts.plainText || opts.html || ""));
      resolve({ value: !!ok });
    });
  }

  // Cek apakah user sudah mengajukan pada semester ini
  function checkSemesterLimit() {
    return new Promise(function (resolve) {
      $.ajax({
        url: "mod/usulan-pip/proses.php?action=check_semester",
        type: "GET",
        dataType: "json",
        timeout: 5000,
        success: function (resp) {
          // mengharapkan { allowed: true|false, message: "..." }
          if (resp && typeof resp.allowed !== "undefined") {
            resolve(resp);
          } else {
            // respon tidak terduga => izinkan lanjut sebagai fallback
            resolve({ allowed: true });
          }
        },
        error: function () {
          // jika gagal cek (timeout / 500), fallback ke izinkan submit
          // (atau ubah ke resolve({allowed:false, message:'Tidak dapat verifikasi'}); jika ingin blokir)
          resolve({ allowed: true });
        }
      });
    });
  }

  // Tangkap form usulan PIP
  var $formUsulan = $("form[action='mod/usulan-pip/proses.php?action=add_usulan']");
  if (!$formUsulan.length) return;

  $formUsulan.on("submit", function (e) {
    e.preventDefault();
    var form = this;

    // Ambil nilai
    var penghasilanAyah = $(form).find('[name="penghasilan_ayah"]').val() || "";
    var penghasilanIbu = $(form).find('[name="penghasilan_ibu"]').val() || "";
    var penerimaKps = $(form).find('[name="penerima_kps"]:checked').val() || "N";
    var nomorKks = $(form).find('[name="nomor_kks"]').val() || "";
    var punyaKip = $(form).find('[name="punya_kip"]:checked').val() || "N";
    var nomorKip = $(form).find('[name="nomor_kip"]').val() || "";
    var keterangan = $(form).find('[name="keterangan"]').val() || "";

    // Validasi awal (client-side)
    var errors = [];
    if ($(form).find('[name="penghasilan_ayah"]').length && penghasilanAyah === "") {
      errors.push("Pilih penghasilan ayah.");
    }
    if ($(form).find('[name="penghasilan_ibu"]').length && penghasilanIbu === "") {
      errors.push("Pilih penghasilan ibu.");
    }
    if (penerimaKps === "Y" && !nomorKks) {
      errors.push("Nomor KKS wajib diisi jika memilih penerima KPS.");
    }
    if (punyaKip === "Y" && !nomorKip) {
      errors.push("Nomor KIP wajib diisi jika memilih punya KIP.");
    }
    if (errors.length) {
      confirmAction({
        title: "Konfirmasi Data Usulan",
        html: errors.join(". ") + '. Usulan hanya dapat dilakukan 1x per semester. Pastikan data sudah benar sebelum mengajukan.',
        confirmText: "Lanjutkan",
        cancelText: "Kembali",
        icon: "warning"
      }).then(function (result) {
        var confirmed = false;
        if (typeof result === "object" && ("value" in result)) confirmed = !!result.value;
        else confirmed = !!result;
        if (!confirmed) return;
        // submit form secara normal
        form.submit();
      });
      return;
    }

    // Periksa batas semester sebelum konfirmasi
    checkSemesterLimit().then(function (resp) {
      if (!resp.allowed) {
        if (resp.status === 'approved') {
          alertError("Tidak dapat mengajukan", "Usulan PIP Anda telah disetujui dan sedang dalam proses.");
        } else {
          alertError("Tidak dapat mengajukan", "Usulan sudah terkirim sebelumnya dan sedang dalam proses Verval dan Input.");
        }
        $(form).find('input, select, textarea, button').prop('disabled', true);
        return;
      }

      // Konfirmasi data dan peringatan 1x per semester
      var pesanKonfirmasi = 'Pastikan data yang Anda isi sudah benar. Usulan hanya dapat dilakukan 1x per semester. Jika Anda sudah pernah mengajukan pada semester ini, usulan baru tidak akan diproses.';

      confirmAction({
        title: "Ajukan Usulan PIP?",
        html: pesanKonfirmasi,
        confirmText: "Ya, Ajukan",
        cancelText: "Batal",
        icon: "warning"
      }).then(function (result) {
        var confirmed = false;
        if (typeof result === "object" && ("value" in result)) confirmed = !!result.value;
        else confirmed = !!result;
        if (!confirmed) return;

        // Submit form via AJAX
        var submitBtn = $(form).find('button[type="submit"]');
        var originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Mengirim...');

        $.ajax({
          url: form.action,
          type: 'POST',
          data: $(form).serialize(),
          dataType: 'text',
          success: function (data, status, xhr) {
            // Cek apakah response mengandung 'success'
            if (typeof data === 'string' && data.indexOf('success') !== -1) {
              alertInfo("Berhasil!", "Usulan PIP berhasil dikirim. Status: Menunggu verifikasi.", function() {
                // Auto reload setelah 2.5 detik untuk menampilkan status terbaru
                var countdown = 2.5;
                var reloadTimer = setInterval(function() {
                  if (hasSwal()) {
                    Swal.fire({
                      title: "Memuat Ulang Halaman",
                      html: "Halaman akan dimuat ulang dalam " + countdown.toFixed(1) + " detik untuk menampilkan status terbaru...",
                      icon: "info",
                      timer: 500,
                      showConfirmButton: false,
                      allowOutsideClick: false
                    });
                  }
                  countdown -= 0.5;
                  
                  if (countdown <= 0) {
                    clearInterval(reloadTimer);
                    // Reload halaman untuk menampilkan status form terbaru
                    window.location.reload();
                  }
                }, 500);
              });
              // Kunci form setelah berhasil
              $(form).find('input, select, textarea, button').prop('disabled', true);
            } else {
              alertError("Gagal mengajukan", "Terjadi kesalahan atau data sudah pernah diajukan.");
              $(form).find('input, select, textarea, button').prop('disabled', true);
              submitBtn.prop('disabled', false).text(originalText);
            }
          },
          error: function (xhr, status, err) {
            alertError("Gagal mengajukan", "Terjadi kesalahan pada server. Silakan coba lagi.");
            submitBtn.prop('disabled', false).text(originalText);
          }
        });
      });
    });
  });
});


function toggleKPSForm(show) {
  const kpsForm = document.getElementById("kps_form");
  const nomor_kks = document.getElementById("nomor_kks");
  if (show) {
    kpsForm.style.display = "block";
    if (nomor_kks) nomor_kks.setAttribute("required", "required");
  } else {
    kpsForm.style.display = "none";
    if (nomor_kks) { nomor_kks.removeAttribute("required"); nomor_kks.value = ""; }
  }
}
function toggleKIPForm(show) {
  const kipForm = document.getElementById("kip_form");
  const nomor_kip = document.getElementById("nomor_kip");
  if (show) {
    kipForm.style.display = "block";
    if (nomor_kip) nomor_kip.setAttribute("required", "required");
  } else {
    kipForm.style.display = "none";
    if (nomor_kip) { nomor_kip.removeAttribute("required"); nomor_kip.value = ""; }
  }
}
// Nomor KIP: uppercase, hanya huruf/angka, panjang dapat disesuaikan
document.addEventListener("DOMContentLoaded", function() {
  var kipInput = document.getElementById("nomor_kip");
  if (kipInput) {
    // set maxlength sesuai konfigurasi
    kipInput.setAttribute('maxlength', KIP_MAX);
    kipInput.addEventListener("input", function() {
      let val = kipInput.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
      kipInput.value = val.slice(0, KIP_MAX);
    });
  }
  var kksInput = document.getElementById("nomor_kks");
  if (kksInput) {
    // set maxlength sesuai konfigurasi
    kksInput.setAttribute('maxlength', KKS_MAX);
    kksInput.addEventListener("input", function() {
      let val = kksInput.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
      kksInput.value = val.slice(0, KKS_MAX);
    });
  }
  // Client-side validation sebelum submit
  var form = document.getElementById("formUsulanPip");
  if (form) {
    form.addEventListener("submit", function(e) {
      // KKS
      var nomor_kks = document.getElementById("nomor_kks");
      if (nomor_kks && nomor_kks.required) {
        var reKks = new RegExp('^[A-Z0-9]{' + KKS_MIN + ',' + KKS_MAX + '}$');
        if (!reKks.test(nomor_kks.value)) {
          alert("Nomor KKS harus berupa huruf/angka kapital dengan panjang " + KKS_MIN + " hingga " + KKS_MAX + " karakter.");
          nomor_kks.focus();
          e.preventDefault();
          return false;
        }
      }
      // KIP
      var nomor_kip = document.getElementById("nomor_kip");
      if (nomor_kip && nomor_kip.required) {
        var reKip = new RegExp('^[A-Z0-9]{' + KIP_MIN + ',' + KIP_MAX + '}$');
        if (!reKip.test(nomor_kip.value)) {
          alert("Nomor KIP harus berupa huruf/angka kapital dengan panjang " + KIP_MIN + " hingga " + KIP_MAX + " karakter.");
          nomor_kip.focus();
          e.preventDefault();
          return false;
        }
      }
    });
  }
});
function openPreviewImage(url) {
  var overlay = document.getElementById('previewOverlay');
  var img = document.getElementById('previewImage');
  var pdf = document.getElementById('previewPdf');
  pdf.style.display = 'none';
  pdf.src = '';
  img.src = url;
  img.style.display = 'block';
  overlay.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function openPreviewPdf(url) {
  var overlay = document.getElementById('previewOverlay');
  var img = document.getElementById('previewImage');
  var pdf = document.getElementById('previewPdf');
  img.style.display = 'none';
  img.src = '';
  pdf.src = url;
  pdf.style.display = 'block';
  overlay.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closePreviewModal() {
  var overlay = document.getElementById('previewOverlay');
  var img = document.getElementById('previewImage');
  var pdf = document.getElementById('previewPdf');
  overlay.style.display = 'none';
  img.src = '';
  pdf.src = '';
  pdf.style.display = 'none';
  img.style.display = 'none';
  document.body.style.overflow = '';
}

(function(){
  try {
    var form = document.getElementById('formUsulanPip');
    if (!form) return;
    // hanya jalankan jika form diberi tanda terkunci untuk status pending/disetujui
    if (form.getAttribute('data-locked') !== '1') return;

    // Disable semua elemen interaktif
    var els = form.querySelectorAll('input, select, textarea, button');
    Array.prototype.forEach.call(els, function(el){
      el.disabled = true;
    });

    // JANGAN sembunyikan preview/catatan untuk mempertahankan visibilitas catatan
    // var kps = document.getElementById('kps_form');
    // var kip = document.getElementById('kip_form');
    // if (kps) kps.style.display = 'none';
    // if (kip) kip.style.display = 'none';

    // Pastikan submit dicegah (double-safety)
    form.addEventListener('submit', function(e){
      e.preventDefault();
      return false;
    }, true);
  } catch(e) {
    // silent
  }
})();

// Function untuk toggle form wali
function toggleFormWali() {
  var select = document.getElementById('tempatTinggalSelect');
  var waliSection = document.getElementById('formWaliSection');
  if (select && waliSection) {
    waliSection.style.display = (select.value === 'Wali') ? 'block' : 'none';
  }
}

// Auto-toggle forms berdasarkan data yang sudah tersimpan
function autoToggleSavedForms() {
  // Auto-toggle form wali berdasarkan pilihan tempat tinggal
  toggleFormWali();
  
  // Auto-toggle KPS form jika radio "Ya" terpilih
  var kpsYa = document.getElementById('kps_ya');
  if (kpsYa && kpsYa.checked) {
    toggleKPSForm(true);
  }
  
  // Auto-toggle KIP form jika radio "Ya" terpilih
  var kipYa = document.getElementById('kip_ya');
  if (kipYa && kipYa.checked) {
    toggleKIPForm(true);
  }
}

// Tambahkan event listener untuk auto-toggle saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
  // Delay sedikit untuk memastikan semua element sudah ter-render
  setTimeout(function() {
    autoToggleSavedForms();
  }, 100);
});
