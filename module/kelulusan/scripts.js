"use strict";

(function () {
  var page = document.querySelector(".kelulusan-page");
  if (!page) return;

  var isOpen = page.getAttribute("data-open") === "Y";
  var countdownNode = document.getElementById("kelulusan-countdown");
  var countdownNote = document.getElementById("kelulusan-countdown-note");

  function formatTime(ms) {
    if (ms <= 0) return "00:00:00";
    var total = Math.floor(ms / 1000);
    var d = Math.floor(total / 86400);
    total = total % 86400;
    var h = Math.floor(total / 3600);
    total = total % 3600;
    var m = Math.floor(total / 60);
    var s = total % 60;
    if (d > 0) return d + " hari " + String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0") + ":" + String(s).padStart(2, "0");
    return String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0") + ":" + String(s).padStart(2, "0");
  }

  function setCountdownSegments(diff) {
    var total = Math.max(0, Math.floor(diff / 1000));
    var days = Math.floor(total / 86400);
    var rem = total % 86400;
    var hours = Math.floor(rem / 3600);
    rem = rem % 3600;
    var minutes = Math.floor(rem / 60);
    var seconds = rem % 60;

    var dNode = document.getElementById("timer-days");
    var hNode = document.getElementById("timer-hours");
    var mNode = document.getElementById("timer-minutes");
    var sNode = document.getElementById("timer-seconds");
    if (dNode) dNode.textContent = String(days).padStart(2, "0");
    if (hNode) hNode.textContent = String(hours).padStart(2, "0");
    if (mNode) mNode.textContent = String(minutes).padStart(2, "0");
    if (sNode) sNode.textContent = String(seconds).padStart(2, "0");
  }

  if (!isOpen && countdownNode) {
    var targetRaw = countdownNode.getAttribute("data-target");
    if (targetRaw) {
      var target = new Date(targetRaw.replace(" ", "T")).getTime();
      var timer = setInterval(function () {
        var diff = target - Date.now();
        setCountdownSegments(diff);
        if (countdownNote) {
          countdownNote.textContent = diff > 0 ? "Rilis dalam " + formatTime(diff) : "Rilis sedang diproses oleh admin.";
        }
        if (diff <= 0) {
          clearInterval(timer);
        }
      }, 1000);
      setCountdownSegments(target - Date.now());
    } else if (countdownNote) {
      countdownNote.textContent = "Jadwal rilis belum ditentukan oleh admin.";
    }
  }

  var form = document.getElementById("kelulusan-form");
  if (!form) return;
  var nisnInput = document.getElementById("nisn");
  var loadingOverlay = document.getElementById("kelulusan-loading");
  var loadingStage = document.getElementById("loading-stage");
  var loadingTimer = null;
  var loadingMessages = [
    "Memverifikasi data peserta didik...",
    "Mencocokkan NISN dan tanggal lahir...",
    "Menyiapkan amplop kelulusan...",
    "Menahan napas... hasil hampir terbuka...",
    "Satu momen penentu sedang diproses..."
  ];

  function startLoading() {
    if (!loadingOverlay) return;
    var idx = 0;
    loadingOverlay.classList.remove("d-none");
    loadingOverlay.setAttribute("aria-hidden", "false");
    if (loadingStage) {
      loadingStage.textContent = loadingMessages[0];
    }
    loadingTimer = setInterval(function () {
      idx = (idx + 1) % loadingMessages.length;
      if (loadingStage) {
        loadingStage.textContent = loadingMessages[idx];
      }
    }, 1200);
  }

  function stopLoading() {
    if (loadingTimer) {
      clearInterval(loadingTimer);
      loadingTimer = null;
    }
    if (!loadingOverlay) return;
    loadingOverlay.classList.add("d-none");
    loadingOverlay.setAttribute("aria-hidden", "true");
  }

  if (nisnInput) {
    nisnInput.addEventListener("input", function () {
      this.value = this.value.replace(/[^0-9]/g, "").slice(0, 10);
    });
  }

  var tanggalInput = document.getElementById("tanggal_lahir");
  if (tanggalInput) {
    tanggalInput.addEventListener("input", function () {
      this.value = this.value.replace(/[^0-9]/g, "").slice(0, 8);
    });
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    if (nisnInput) {
      var nisnVal = (nisnInput.value || "").trim();
      if (!/^\d{10}$/.test(nisnVal)) {
        swal({
          title: "Format NISN Salah",
          text: "NISN harus tepat 10 digit angka.",
          icon: "warning"
        });
        return;
      }
    }

    if (tanggalInput) {
      var dateVal = (tanggalInput.value || "").trim();
      if (!/^\d{8}$/.test(dateVal)) {
        swal({
          title: "Format Tanggal Salah",
          text: "Gunakan format tanggal lahir: ddmmyyyy (contoh: 31012007).",
          icon: "warning"
        });
        return;
      }
    }

    var submitBtn = form.querySelector("button[type='submit']");
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Memproses...';
    startLoading();

    $.ajax({
      url: "./module/kelulusan/proses.php?action=cek",
      type: "POST",
      dataType: "json",
      data: $(form).serialize(),
      success: function (res) {
        if (!res || res.status !== "success") {
          stopLoading();
          swal({
            title: "Validasi Gagal",
            text: res && res.message ? res.message : "Terjadi kesalahan.",
            icon: "error"
          });
          return;
        }

        setTimeout(function () {
          stopLoading();

          var data = res.data || {};
          if (data.redirect_url) {
            window.location.href = data.redirect_url;
            return;
          }

          swal({ title: "Gagal", text: "Halaman hasil tidak ditemukan.", icon: "error" });
        }, 3400);
      },
      error: function () {
        stopLoading();
        swal({ title: "Error", text: "Tidak bisa terhubung ke server.", icon: "error" });
      },
      complete: function () {
        submitBtn.disabled = !isOpen;
        submitBtn.innerHTML = '<i class="fas fa-envelope-open-text mr-1"></i> Buka Amplop Kelulusan';
      }
    });
  });
})();
