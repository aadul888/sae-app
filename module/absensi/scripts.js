"use strict";
// Global variables
let currentDateTime = null;
let calendarCenterOffset = 0;

// Initialize when document is ready
$(document).ready(function () {
  setTimeout(function () {
    initializeAbsensi();
    initializeAttendanceCalendar();
    startClock();
    adjustLayout();
    initializeFullscreen();
    $(window).on("resize", function () {
      adjustLayout();
      renderAttendanceCalendarWindow();
    });

    let lastRFID = "";
    let isScanning = false;
    const rfidInput = document.getElementById("rfid-input");
    const safeFocusRfidInput = function () {
      if (!rfidInput || isScanning || $(".modal.show").length) return;
      if (document.activeElement === rfidInput) return;
      rfidInput.focus({ preventScroll: true });
    };

    // Keep RFID input ready without stealing focus from normal page interaction.
    $(document).on("click", function (e) {
      var tag = (e.target.tagName || "").toLowerCase();
      if (tag === "input" || tag === "textarea" || tag === "select" || tag === "button" || tag === "a" || tag === "label") return;
      if ($(e.target).closest(".modal, .dropdown-menu, .fullscreen-controls, .calendar-frame, .table-responsive, .absensi-history-head").length) return;
      if ($(e.target).closest(".absensi-camera-card").length) {
        safeFocusRfidInput();
      }
    });

    safeFocusRfidInput();

    // Also re-focus periodically in case focus is lost (e.g. after alert/modal)
    setInterval(function () {
      if (!isScanning && !$(".modal.show").length && document.activeElement !== rfidInput) {
        safeFocusRfidInput();
      }
    }, 1500);

    // Event RFID scan dummy
    $("#rfid-input").on("input", async function () {
      const rfid = $(this).val().trim();
      console.log("RFID input:", rfid, "length:", rfid.length, "focused:", document.activeElement === this);
      if (isScanning) return; // Prevent double scan/modal
      // Minimal 10 karakter sesuai format RFID database, dan tidak sama dengan scan sebelumnya
      if (rfid.length === 10 && rfid !== lastRFID) {
        lastRFID = rfid;
        isScanning = true;
        // Add card tap animation
        addCardTapAnimation();
        updateStatus("scanning", "Memproses scan...");

        // --- Capture photo from camera ---
        let photoBase64 = "";
        try {
          photoBase64 = await capturePhotoBase64();
        } catch (err) {
          console.warn("Gagal capture foto:", err);
        }

        // --- Get GPS location ---
        let latitude = 0;
        let longitude = 0;
        try {
          updateStatus("scanning", "Mengambil lokasi GPS...");
          const position = await getCurrentLocation();
          latitude = position.coords.latitude;
          longitude = position.coords.longitude;
          console.log("GPS Location:", latitude, longitude);
          updateStatus("scanning", "Lokasi GPS berhasil, memproses absensi...");
        } catch (err) {
          console.warn("Gagal mendapatkan lokasi GPS:", err);
          // Tetap lanjutkan absensi meski GPS gagal
          updateStatus(
            "scanning",
            "GPS tidak tersedia, melanjutkan absensi..."
          );
        }

        $.ajax({
          url: "./module/absensi/proses.php",
          method: "POST",
          dataType: "text",
          data: {
            action: "scan_rfid",
            rfid: rfid,
            foto_capture: photoBase64,
            latitude: latitude,
            longitude: longitude,
          },
          success: function (res) {
            if (
              typeof res === "string" &&
              res.trim().toLowerCase().startsWith("<!doctype")
            ) {
              showErrorModal(
                "Server error: file proses.php tidak ditemukan atau salah path"
              );
              updateStatus(
                "error",
                "Server error: file proses.php tidak ditemukan atau salah path"
              );
              return;
            }
            var cleanRes = String(res || "").trim();
            if (cleanRes.startsWith("success/")) {
              var parts = cleanRes.split("/");
              var nisn = parts[1] || "000123456";
              var nama = parts[2] || "Abdul Azis";
              var nama_kelas = parts[3] || "-";
              var waktu = parts[4] || "19:10:04";
              var avatar = parts[5]
                ? "./content/avatar/" + parts[5]
                : "./content/avatar/" + nisn + ".png";
              var tanggal = new Date().toLocaleDateString("id-ID");
              showSuccessModal({
                nisn: nisn,
                nama: nama,
                kelas: nama_kelas,
                waktu: waktu,
                tanggal: tanggal,
                foto: avatar,
              });
              updateStatus("success", "Absensi berhasil!");
            } else {
              showErrorModal(cleanRes);
              updateStatus("error", cleanRes);
            }
          },
          error: function (xhr) {
            showErrorModal("Terjadi kesalahan koneksi");
            updateStatus("error", "Terjadi kesalahan koneksi");
          },
          complete: function () {
            $("#rfid-input").val("");
            lastRFID = "";
            isScanning = false;
            setTimeout(function () {
              safeFocusRfidInput();
            }, 300);
          },
        });
      }
    });

    // Tombol refresh riwayat absensi
    $("#refresh-history").on("click", function () {
      refreshAbsensiHistory();
    });

    // Initialize Camera
    initializeCamera();

    // Auto-start camera when page loads
    setTimeout(function () {
      startCamera();
    }, 1000);

    // Request GPS permission on page load
    setTimeout(function () {
      requestGPSPermission();
    }, 2000);

    // Otomatis tutup absensi setiap hari jam 23:59
    scheduleCloseAttendance();
    // Finalisasi data lintas-hari saat halaman dibuka.
    finalizeRolloverAttendance();
  }, 100);
});

// ===============================================
// ===============================================
// CLOSE ATTENDANCE SCHEDULE (AUTO UPDATE STATUS PULANG)
// ===============================================
function scheduleCloseAttendance() {
  // Hitung waktu menuju 23:59:00
  const now = new Date();
  const target = new Date(
    now.getFullYear(),
    now.getMonth(),
    now.getDate(),
    23,
    59,
    0,
    0
  );
  let msToTarget = target.getTime() - now.getTime();
  if (msToTarget < 0) {
    // Jika sudah lewat, jadwalkan untuk besok
    target.setDate(target.getDate() + 1);
    msToTarget = target.getTime() - now.getTime();
  }
  setTimeout(function () {
    closeAttendanceAuto();
    finalizeRolloverAttendance();
    // Jadwalkan lagi untuk hari berikutnya
    scheduleCloseAttendance();
  }, msToTarget);
}

function getLocalDateString() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return year + "-" + month + "-" + day;
}

function closeAttendanceAuto() {
  // Kirim request ke backend untuk menutup absensi dan update status pulang
  $.ajax({
    url: "./module/absensi/proses.php?action=close_attendance",
    method: "POST",
    dataType: "text",
    data: {
      date: getLocalDateString(),
      close_time: "23:59:00",
    },
    success: function (res) {
      console.log("Absensi pulang otomatis:", res);
      refreshAbsensiHistory();
    },
    error: function (xhr) {
      console.warn("Gagal update absensi pulang otomatis");
    },
  });
}

function finalizeRolloverAttendance() {
  $.ajax({
    url: "./module/absensi/proses.php?action=finalize_rollover",
    method: "POST",
    dataType: "text",
    success: function () {
      refreshAbsensiHistory();
    },
  });
}
// INITIALIZATION
// ===============================================
function initializeAbsensi() {
  // Fokus input (dummy, tidak dipakai)
  $("#rfid-input").val("");
  setTimeout(function () {
    $("#rfid-input").focus();
  }, 200);

  $("body").addClass("absensi-page");
  updateStatus("ready", "Siap membaca kartu RFID");
  adjustLayout();
}

// ===============================================
// ATTENDANCE CALENDAR (AUTO LAST 7 DAYS)
// ===============================================
function initializeAttendanceCalendar() {
  const days = Array.isArray(window.absensiCalendarDays) ? window.absensiCalendarDays : [];
  const $window = $("#calendar-window");
  if (!$window.length) return;

  if (!days.length) {
    $window.html('<div class="calendar-empty">Data kalender belum tersedia.</div>');
    return;
  }

  calendarCenterOffset = 0;
  renderAttendanceCalendarWindow();

  $("#calendarPrev").off("click").on("click", function () {
    shiftAttendanceCalendar(-1);
  });
  $("#calendarNext").off("click").on("click", function () {
    shiftAttendanceCalendar(1);
  });
}

function renderAttendanceCalendarWindow() {
  const days = Array.isArray(window.absensiCalendarDays) ? window.absensiCalendarDays : [];
  const $window = $("#calendar-window");
  if (!$window.length || !days.length) return;

  const todayIndexRaw = days.findIndex((d) => !!d.is_today);
  const baseIndex = todayIndexRaw >= 0 ? todayIndexRaw : 0;
  const visibleCount = getCalendarVisibleCount();
  const maxCenter = days.length - 1;
  const centerIndex = Math.min(maxCenter, Math.max(0, baseIndex + calendarCenterOffset));
  const half = Math.floor(visibleCount / 2);

  let html = '<div class="calendar-track">';
  for (let i = -half; i <= half; i++) {
    const index = centerIndex + i;
    if (index < 0 || index >= days.length) {
      html += '<div class="calendar-day-card is-empty" aria-hidden="true"></div>';
      continue;
    }
    const d = days[index];
    const parts = String(d.date || "").split("-");
    const dayNum = parts.length === 3 ? parts[2] : "-";
    const monthNum = parts.length === 3 ? parts[1] : "-";
    const monthLabel = getMonthLabel(monthNum);
    const statusType = String(d.status_type || "off");
    const statusText = d.status_label || (statusType === "active" ? "Jadwal Aktif" : "Libur");
    const reasonText = d.status_note || (statusType === "active" ? "Hari Efektif" : "Hari Libur");

    const classes = ["calendar-day-card"];
    classes.push("is-" + statusType);
    if (d.is_today) classes.push("is-today");
    if (index === centerIndex) classes.push("is-active-date");

    html +=
      '<div class="' + classes.join(" ") + '" data-day-index="' + index + '">' +
      '<div class="calendar-day-name">' + escapeHtml(d.day_short || "-") + '</div>' +
      '<div class="calendar-day-date">' + escapeHtml(dayNum) + '</div>' +
      '<div class="calendar-day-month">' + escapeHtml(monthLabel) + '</div>' +
      '<div class="calendar-day-status">' + escapeHtml(statusText) + '</div>' +
      '<div class="calendar-day-reason" title="' + escapeHtml(reasonText) + '">' + escapeHtml(reasonText) + '</div>' +
      '</div>';
  }
  html += "</div>";

  $window.html(html);
}

function shiftAttendanceCalendar(direction) {
  calendarCenterOffset += direction > 0 ? 1 : -1;
  renderAttendanceCalendarWindow();
}

function getCalendarVisibleCount() {
  const width = $(window).width();
  if (width <= 560) return 5;
  if (width <= 992) return 7;
  return 9;
}

function getMonthLabel(monthNum) {
  const map = {
    "01": "Jan",
    "02": "Feb",
    "03": "Mar",
    "04": "Apr",
    "05": "Mei",
    "06": "Jun",
    "07": "Jul",
    "08": "Agu",
    "09": "Sep",
    "10": "Okt",
    "11": "Nov",
    "12": "Des",
  };
  return map[monthNum] || "-";
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// ===============================================
// CLOCK FUNCTIONS
// ===============================================
function startClock() {
  updateClock();
  setInterval(updateClock, 1000);
}

function updateClock() {
  const now = new Date();
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  };
  const formattedDate = now.toLocaleDateString("id-ID", options);
  const formattedTime = now.toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
  const dayName = now.toLocaleDateString("id-ID", { weekday: "long" });

  $("#current-date").text(formattedDate);
  $("#current-time").text(formattedTime);
  $("#current-day").text(dayName);

  currentDateTime = now;
}

// ===============================================
// STATUS INDICATOR (DUMMY)
// ===============================================
function updateStatus(type, message) {
  const $indicator = $("#status-indicator");
  const icons = {
    ready: "fas fa-clock",
    scanning: "fas fa-spinner fa-spin",
    success: "fas fa-check-circle",
    error: "fas fa-exclamation-circle",
  };
  $indicator.find("i").attr("class", icons[type] || icons.ready);
  $indicator.find("span").text(message);
}

// ===============================================
// LAYOUT
// ===============================================
function adjustLayout() {
  const screenWidth = $(window).width();
  const screenHeight = $(window).height();

  $("body").removeClass("large-screen medium-screen small-screen");
  if (screenWidth >= 1920) {
    $("body").addClass("large-screen");
  } else if (screenWidth >= 1366) {
    $("body").addClass("medium-screen");
  } else {
    $("body").addClass("small-screen");
  }
}

// ===============================================
// MODAL HANDLING
// ===============================================
function showSuccessModal(data) {
  // Update modal content
  $("#modal-foto")
    .attr("src", data.foto || "./content/avatar/avatar.jpg")
    .show();
  $("#modal-nama").text(data.nama || "Abdul Azis");
  $("#modal-nisn").text(data.nisn || "000123456");
  $("#modal-kelas").text(data.kelas || "4");
  $("#modal-tanggal").text(
    data.tanggal || new Date().toLocaleDateString("id-ID")
  );
  $("#modal-waktu").text(data.waktu || "19:10:04");
  var modal = new bootstrap.Modal(document.getElementById("successModal"));
  modal.show();
  $(".hero-rfid-showcase").addClass("scan-success");
  setTimeout(function () {
    $(".hero-rfid-showcase").removeClass("scan-success");
  }, 2000);
  var snd = document.getElementById("sound-success");
  if (snd && soundEnabled) {
    snd.currentTime = 0;
    snd.play().catch(err => console.log("Audio play error:", err));
  }
  setTimeout(function () {
    modal.hide();
    refreshAbsensiHistory();
    setTimeout(function () {
      $("#rfid-input").focus();
    }, 300);
  }, 3000);
}

function showErrorModal(message) {
  $("#modal-error-message").text(message || "Terjadi kesalahan");
  var modal = new bootstrap.Modal(document.getElementById("errorModal"));
  modal.show();
  $(".hero-rfid-showcase").addClass("scan-error");
  setTimeout(function () {
    $(".hero-rfid-showcase").removeClass("scan-error");
  }, 2000);
  var snd = document.getElementById("sound-error");
  if (snd && soundEnabled) {
    snd.currentTime = 0;
    snd.play().catch(err => console.log("Audio play error:", err));
  }
  setTimeout(function () {
    modal.hide();
    refreshAbsensiHistory();
    setTimeout(function () {
      $("#rfid-input").focus();
    }, 300);
  }, 3000);
}

// ===============================================
// ERROR HANDLING
// ===============================================
window.addEventListener("error", function (e) {
  console.error("JavaScript Error:", e.error);
});

// ===============================================
// FULLSCREEN FUNCTIONALITY
// ===============================================
let soundEnabled = true;

function initializeFullscreen() {
  $("#fullscreen-btn").on("click", function () {
    enterFullscreen();
  });

  $("#exit-fullscreen-btn").on("click", function () {
    exitFullscreen();
  });
  
  // Sound toggle button
  $("#toggle-sound").on("click", function () {
    soundEnabled = !soundEnabled;
    const icon = soundEnabled ? "fa-volume-up" : "fa-volume-mute";
    $(this).find("i").removeClass("fa-volume-up fa-volume-mute").addClass(icon);
    localStorage.setItem('absensiSoundEnabled', soundEnabled ? '1' : '0');
    showNotification(soundEnabled ? 'Suara diaktifkan' : 'Suara dinonaktifkan');
  });
  
  // Restore sound preference
  const savedSound = localStorage.getItem('absensiSoundEnabled');
  if (savedSound !== null) {
    soundEnabled = savedSound === '1';
    const icon = soundEnabled ? "fa-volume-up" : "fa-volume-mute";
    $("#toggle-sound").find("i").removeClass("fa-volume-up fa-volume-mute").addClass(icon);
  }
  
  // Help panel toggle
  $("#toggle-help").on("click", function () {
    $("#help-panel").slideToggle(300);
  });
  
  // Close help on escape or click outside
  $(document).on("keydown", function (e) {
    if (e.key === "Escape") {
      if ($("#help-panel").is(":visible")) {
        $("#help-panel").slideUp(300);
      } else if (isFullscreen()) {
        exitFullscreen();
      }
    }
    // Keyboard shortcuts
    if (e.key === "?" || (e.shiftKey && e.key === "?")) {
      e.preventDefault();
      $("#help-panel").slideToggle(300);
    }
    if ((e.ctrlKey || e.metaKey) && e.key === "m") {
      e.preventDefault();
      $("#toggle-sound").click();
    }
    if (e.key === "r" || e.key === "R") {
      e.preventDefault();
      refreshAbsensiHistory();
      showNotification('Memuat ulang history...');
    }
    if ((e.ctrlKey || e.metaKey) && e.key === "l") {
      e.preventDefault();
      enterFullscreen();
    }
  });

  // Listen for fullscreen changes
  $(document).on(
    "fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange",
    function () {
      if (isFullscreen()) {
        $("#fullscreen-btn").hide();
        $("#exit-fullscreen-btn").show();
        // Fokuskan kembali input RFID saat masuk fullscreen
        setTimeout(function () {
          $("#rfid-input").focus();
        }, 300);
      } else {
        $("#fullscreen-btn").show();
        $("#exit-fullscreen-btn").hide();
        // Fokuskan kembali input RFID saat keluar fullscreen
        setTimeout(function () {
          $("#rfid-input").focus();
        }, 300);
      }
    }
  );

  // ESC key to exit fullscreen
  $(document).on("keydown", function (e) {
    if (e.key === "Escape" && isFullscreen()) {
      exitFullscreen();
    }
  });
}

// Notification helper
function showNotification(message) {
  const $toast = $('<div class="alert alert-info alert-dismissible fade show" role="alert">' +
    message +
    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
    '</div>');
  $("#alert-container").append($toast);
  setTimeout(() => $toast.fadeOut(300, function() { $(this).remove(); }), 3000);
}

function enterFullscreen() {
  const elem = document.documentElement;

  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) {
    elem.webkitRequestFullscreen();
  } else if (elem.mozRequestFullScreen) {
    elem.mozRequestFullScreen();
  } else if (elem.msRequestFullscreen) {
    elem.msRequestFullscreen();
  }
}

function exitFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.webkitExitFullscreen) {
    document.webkitExitFullscreen();
  } else if (document.mozCancelFullScreen) {
    document.mozCancelFullScreen();
  } else if (document.msExitFullscreen) {
    document.msExitFullscreen();
  }
}

function isFullscreen() {
  return !!(
    document.fullscreenElement ||
    document.webkitFullscreenElement ||
    document.mozFullScreenElement ||
    document.msFullscreenElement
  );
}

// ===============================================
// CARD TAP ANIMATION
// ===============================================
function addCardTapAnimation() {
  const $heroAnim = $(".hero-rfid-animation");
  const $showcase = $(".hero-rfid-showcase");
  if (!$heroAnim.length || !$showcase.length) return;

  $heroAnim.addClass("is-scan");
  $showcase.addClass("is-scanning");

  setTimeout(function () {
    $heroAnim.removeClass("is-scan");
    $showcase.removeClass("is-scanning");
  }, 1200);
}

// ===============================================
// CAMERA FUNCTIONALITY
// ===============================================
let cameraStream = null;
let cameraActive = false;

function initializeCamera() {
  // Camera auto-starts, but allow clicking placeholder to retry
  $("#camera-placeholder").on("click", function () {
    if (!cameraActive) {
      startCamera();
    }
  });
}

async function startCamera() {
  try {
    const constraints = {
      video: {
        width: { ideal: 640 },
        height: { ideal: 480 },
        facingMode: "user", // Front camera
      },
    };

    cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
    const video = document.getElementById("camera-video");
    const placeholder = document.getElementById("camera-placeholder");

    if (!video) { console.warn("Camera video element not found"); return; }
    video.srcObject = cameraStream;
    video.style.display = "block";
    if (placeholder) placeholder.style.display = "none";

    $(".camera-container").addClass("camera-active");
    $("#camera-icon").removeClass("fa-video").addClass("fa-video-slash");
    $("#capture-photo").prop("disabled", false);

    cameraActive = true;

    console.log("Camera started successfully");
  } catch (error) {
    console.error("Error accessing camera:", error);
    showCameraError("Tidak dapat mengakses kamera: " + error.message);
  }
}

function stopCamera() {
  if (cameraStream) {
    cameraStream.getTracks().forEach((track) => track.stop());
    cameraStream = null;
  }

  const video = document.getElementById("camera-video");
  const placeholder = document.getElementById("camera-placeholder");

  if (video) video.style.display = "none";
  if (placeholder) placeholder.style.display = "flex";

  $(".camera-container").removeClass("camera-active");
  $("#camera-icon").removeClass("fa-video-slash").addClass("fa-video");
  $("#capture-photo").prop("disabled", true);

  cameraActive = false;

  console.log("Camera stopped");
}

function capturePhoto() {
  if (!cameraActive || !cameraStream) {
    showCameraError("Kamera tidak aktif");
    return;
  }

  const video = document.getElementById("camera-video");
  const canvas = document.getElementById("camera-canvas");
  const context = canvas.getContext("2d");

  // Crop to 2:1 landscape ratio from center of video
  var vw = video.videoWidth, vh = video.videoHeight;
  var cropW, cropH, sx, sy;
  if (vw / vh >= 2) {
    cropH = vh; cropW = vh * 2; sx = (vw - cropW) / 2; sy = 0;
  } else {
    cropW = vw; cropH = vw / 2; sx = 0; sy = (vh - cropH) / 2;
  }
  canvas.width = cropW;
  canvas.height = cropH;
  context.drawImage(video, sx, sy, cropW, cropH, 0, 0, cropW, cropH);

  // Add capture effect
  $(".camera-container").addClass("capture-flash");
  setTimeout(() => {
    $(".camera-container").removeClass("capture-flash");
  }, 200);
  // Optional: Show success message
  updateStatus("success", "Foto berhasil diambil!");
  setTimeout(() => {
    updateStatus("ready", "Siap membaca kartu RFID");
  }, 2000);
  // Return blob or base64 if needed
}

// Helper: Capture photo and return base64 string
async function capturePhotoBase64() {
  return new Promise((resolve, reject) => {
    if (!cameraActive || !cameraStream) {
      reject("Kamera tidak aktif");
      return;
    }
    const video = document.getElementById("camera-video");
    const canvas = document.getElementById("camera-canvas");
    const context = canvas.getContext("2d");
    // Crop to 2:1 landscape ratio from center of video
    var vw = video.videoWidth, vh = video.videoHeight;
    var cropW, cropH, sx, sy;
    if (vw / vh >= 2) {
      cropH = vh; cropW = vh * 2; sx = (vw - cropW) / 2; sy = 0;
    } else {
      cropW = vw; cropH = vw / 2; sx = 0; sy = (vh - cropH) / 2;
    }
    canvas.width = cropW;
    canvas.height = cropH;
    context.drawImage(video, sx, sy, cropW, cropH, 0, 0, cropW, cropH);
    // Add capture effect
    $(".camera-container").addClass("capture-flash");
    setTimeout(() => {
      $(".camera-container").removeClass("capture-flash");
    }, 200);
    try {
      const dataUrl = canvas.toDataURL("image/jpeg", 0.8); // base64
      resolve(dataUrl);
    } catch (err) {
      reject(err);
    }
  });
}

function showCameraError(message) {
  console.error("Camera error:", message);
  updateStatus("error", message);
  setTimeout(() => {
    updateStatus("ready", "Siap membaca kartu RFID");
  }, 3000);
}

// ===============================================
// GPS LOCATION FUNCTIONALITY
// ===============================================
function requestGPSPermission() {
  if (!navigator.geolocation) {
    console.warn("Geolocation tidak didukung oleh browser ini");
    return;
  }

  // Cek permission status
  if (navigator.permissions) {
    navigator.permissions
      .query({ name: "geolocation" })
      .then(function (result) {
        if (result.state === "prompt") {
          // Request permission dengan timeout singkat
          navigator.geolocation.getCurrentPosition(
            (position) => {
              console.log(
                "GPS permission granted, location:",
                position.coords.latitude,
                position.coords.longitude
              );
              updateStatus("ready", "GPS siap, siap membaca kartu RFID");
            },
            (error) => {
              console.log("GPS permission denied or failed:", error.message);
              updateStatus(
                "ready",
                "GPS tidak tersedia, siap membaca kartu RFID"
              );
            },
            { timeout: 5000, enableHighAccuracy: false }
          );
        } else if (result.state === "granted") {
          updateStatus("ready", "GPS aktif, siap membaca kartu RFID");
        } else {
          updateStatus("ready", "GPS tidak diizinkan, siap membaca kartu RFID");
        }
      });
  } else {
    // Fallback untuk browser yang tidak mendukung permissions API
    updateStatus("ready", "Siap membaca kartu RFID");
  }
}

function getCurrentLocation() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error("Geolocation tidak didukung oleh browser ini"));
      return;
    }

    const options = {
      enableHighAccuracy: true,
      timeout: 10000, // 10 detik timeout
      maximumAge: 60000, // Cache selama 1 menit
    };

    navigator.geolocation.getCurrentPosition(
      (position) => {
        resolve(position);
      },
      (error) => {
        let errorMessage = "Gagal mendapatkan lokasi GPS";
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage =
              "Akses lokasi ditolak. Silakan izinkan akses lokasi di browser.";
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage = "Informasi lokasi tidak tersedia.";
            break;
          case error.TIMEOUT:
            errorMessage = "Timeout saat mengambil lokasi.";
            break;
        }
        reject(new Error(errorMessage));
      },
      options
    );
  });
}

// ===============================================
// REFRESH ABSENSI HISTORY
// ===============================================
function refreshAbsensiHistory() {
  // Tampilkan loading di tabel
  $("#absensi-history-tbody").html(
    '<tr><td colspan="5" class="table-loading-message"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>'
  );
  // Ambil ulang data tbody dari absensi.php (hanya bagian tbody)
  $.ajax({
    url: window.location.pathname,
    type: "GET",
    dataType: "html",
    success: function (res) {
      // Ambil isi <tbody id="absensi-history-tbody">...</tbody>
      var tbody = $(res).find("#absensi-history-tbody").html();
      if (tbody) {
        $("#absensi-history-tbody").html(tbody);
      } else {
        $("#absensi-history-tbody").html(
          '<tr><td colspan="5" class="table-error-message"><i class="fas fa-exclamation-circle"></i> Tidak dapat mengambil data</td></tr>'
        );
      }
    },
    error: function () {
      $("#absensi-history-tbody").html(
        '<tr><td colspan="5" class="table-error-message"><i class="fas fa-exclamation-circle"></i> Gagal koneksi server</td></tr>'
      );
    },
  });
}
