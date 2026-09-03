"use strict";

// Global variables
let isPrinting = false;
let filterChangeTimeout;

// Initialize components on document ready
$(document).ready(function () {
  initializePage();
  initializeFilters();
  initializeEventListeners();
  initializeNotificationPrompt();
});

function initializePage() {
  $(".load-data").html(
    '<div class="alert alert-info text-center"><i class="fas fa-info-circle"></i> Silahkan pilih kelas untuk menampilkan absensi</div>'
  );
}

// Notification prompt helpers
function initializeNotificationPrompt() {
  tryAutoRequestNotification();
  // Create visible banner so user can manually enable if auto request is blocked
  createNotificationBanner();
}

function createNotificationBanner() {
  // don't show if Notifications not supported
  if (!("Notification" in window)) return;
  if (
    Notification.permission === "granted" ||
    Notification.permission === "denied"
  )
    return;

  const existing = document.querySelector(".sae-notification-banner");
  if (existing) return;

  const container = document.createElement("div");
  container.className = "sae-notification-banner";
  container.innerHTML = `
    <div class="sae-notification-inner">
      <div class="sae-notification-text">Dapatkan pemberitahuan penting dari situs ini.</div>
      <div class="sae-notification-actions">
        <button class="btn btn-sm btn-primary sae-notify-allow">Aktifkan Notifikasi</button>
        <button class="btn btn-sm btn-outline-secondary sae-notify-close">Tutup</button>
      </div>
    </div>
  `;

  const style = document.createElement("style");
  style.innerHTML = `
    .sae-notification-banner{position:fixed;right:20px;bottom:20px;z-index:2147483647;background:#fff;border:1px solid #e6edf3;padding:10px 12px;border-radius:8px;box-shadow:0 6px 18px rgba(31,45,61,.12);font-size:13px;display:flex;align-items:center}
    .sae-notification-inner{display:flex;gap:10px;align-items:center}
    .sae-notification-text{margin-right:6px}
    .sae-notification-actions .btn{margin-left:6px}
  `;

  document.head.appendChild(style);
  document.body.appendChild(container);

  container
    .querySelector(".sae-notify-close")
    .addEventListener("click", function () {
      container.remove();
    });

  container
    .querySelector(".sae-notify-allow")
    .addEventListener("click", function () {
      requestNotificationPermission()
        .then((result) => {
          if (result === "granted") {
            try {
              new Notification("Notifikasi diaktifkan", {
                body: "Anda akan menerima pemberitahuan penting.",
              });
            } catch (e) {}
          } else if (result === "denied") {
            alert(
              "Anda telah menolak notifikasi. Untuk mengaktifkan kembali, ubah izin situs pada browser."
            );
          }
          container.remove();
        })
        .catch((err) => {
          console.error("requestNotificationPermission error", err);
          alert("Gagal meminta izin notifikasi");
          container.remove();
        });
    });
}

function tryAutoRequestNotification() {
  // Attempt to request permission after short delay (may be blocked by browser)
  if (!("Notification" in window)) return;
  if (Notification.permission !== "default") return; // only if not yet decided

  // Check secure origin
  const isSecure =
    location.protocol === "https:" ||
    location.hostname === "localhost" ||
    location.hostname === "127.0.0.1";
  if (!isSecure) {
    console.info("Notifikasi memerlukan HTTPS. Saat ini bukan origin aman.");
    return;
  }

  // Try after a brief delay; many browsers still allow prompt on load but some require gesture
  setTimeout(() => {
    requestNotificationPermission()
      .then((result) => {
        if (result === "granted") {
          try {
            new Notification("Notifikasi diaktifkan", {
              body: "Anda akan menerima pemberitahuan penting.",
            });
          } catch (e) {}
        }
      })
      .catch((err) => {
        console.debug(
          "Auto requestNotificationPermission blocked or failed",
          err
        );
      });
  }, 800);
}

function requestNotificationPermission() {
  return new Promise((resolve, reject) => {
    if (!("Notification" in window))
      return reject(new Error("Notifications not supported"));
    try {
      const permissionResult = Notification.requestPermission(function (
        result
      ) {
        resolve(result);
      });
      if (permissionResult && permissionResult.then) {
        permissionResult.then(resolve).catch(reject);
      }
    } catch (e) {
      reject(e);
    }
  });
}

function initializeFilters() {
  // Set default values if not set
  if (!$(".bulan").val()) {
    $(".bulan").val(new Date().getMonth() + 1);
  }
  if (!$(".tahun").val()) {
    $(".tahun").val(new Date().getFullYear());
  }
}

function initializeEventListeners() {
  // Filter change handlers with debouncing
  $(document).on("change", ".kelas, .bulan, .tahun", handleFilterChange);
  $(document).on("keypress", ".kelas, .bulan, .tahun", handleKeyPress);
  $(document).on("click", ".print-btn", handlePrintClick);
  $(document).on("keydown", handleKeyboardShortcuts);

  // Print event listeners
  window.addEventListener("beforeprint", () =>
    console.log("Before print event fired")
  );
  window.addEventListener("afterprint", () => {
    console.log("After print event fired");
    isPrinting = false;
  });
}

// Event handlers
function handleFilterChange() {
  clearTimeout(filterChangeTimeout);
  filterChangeTimeout = setTimeout(loadPreview, 300);
}

function handleKeyPress(e) {
  if (e.which === 13) {
    // Enter key
    e.preventDefault();
    loadPreview();
  }
}

function handlePrintClick(e) {
  e.preventDefault();
  e.stopPropagation();

  const validation = validatePrintData();
  if (!validation.valid) {
    alert(validation.message);
    return false;
  }

  printAbsensi();
}

function handleKeyboardShortcuts(event) {
  if ((event.ctrlKey || event.metaKey) && event.key === "p") {
    const attendanceContainer = $(".load-data .attendance-preview-container");
    if (attendanceContainer.length && attendanceContainer.is(":visible")) {
      event.preventDefault();

      const validation = validatePrintData();
      if (validation.valid) {
        if (confirm("Print absensi dengan shortcut Ctrl+P?")) {
          printAbsensi();
        }
      } else {
        alert(validation.message);
      }
    }
  }
}

function loadPreview() {
  const filters = getSelectedFilters();

  if (!filters.kelas) {
    showMessage("info", "Silahkan pilih kelas untuk menampilkan absensi");
    return;
  }

  if (!filters.bulan || !filters.tahun) {
    showMessage("warning", "Bulan dan Tahun harus dipilih");
    return;
  }

  showLoadingState(filters);

  console.log("Loading data with:", filters);

  $.ajax({
    type: "POST",
    url: "./mod/cetak-absensi/proses.php?action=preview",
    data: {
      kelas: filters.kelas,
      bulan: filters.bulan,
      tahun: filters.tahun,
    },
    cache: false,
    timeout: 30000,
    success: handleLoadSuccess,
    error: handleLoadError,
  });
}

function showMessage(type, message) {
  const icons = {
    info: "fas fa-info-circle",
    warning: "fas fa-exclamation-triangle",
    danger: "fas fa-exclamation-circle",
  };

  $(".load-data").html(
    `<div class="alert alert-${type} text-center"><i class="${icons[type]}"></i> ${message}</div>`
  );
}

function showLoadingState(filters) {
  $(".load-data").html(`
        <div class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <br><br>
            <h5>Memuat data absensi...</h5>
            <p class="text-muted">Kelas: <strong>${filters.kelasText}</strong> | 
               Periode: <strong>${filters.bulanText} ${filters.tahun}</strong></p>
            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    `);
}

function handleLoadSuccess(data) {
  console.log("Data received successfully");

  if (data && data.includes("attendance-preview-container")) {
    $(".load-data").html(data);
    scrollToResults();
  } else if (data && data.includes("alert-warning")) {
    $(".load-data").html(data);
  } else {
    showMessage("warning", "Data absensi kosong atau tidak valid");
  }
}

function handleLoadError(xhr, status, error) {
  console.error("AJAX Error:", { status, error, response: xhr.responseText });

  const errorMessages = {
    timeout: "Request timeout. Silahkan coba lagi.",
    parsererror: "Error parsing data dari server.",
    500: "Error server internal. Silahkan hubungi administrator.",
    404: "File proses tidak ditemukan.",
  };

  const errorMessage =
    errorMessages[status] ||
    errorMessages[xhr.status] ||
    "Terjadi kesalahan saat memuat data.";

  $(".load-data").html(`
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-circle"></i> ${errorMessage}
            <br><small class="text-muted">Error: ${error}</small>
            <br><button class="btn btn-sm btn-outline-danger mt-2" onclick="loadPreview()">
                <i class="fas fa-redo"></i> Coba Lagi
            </button>
        </div>
    `);
}

function scrollToResults() {
  setTimeout(() => {
    $("html, body").animate(
      {
        scrollTop: $(".load-data").offset().top - 100,
      },
      500
    );
  }, 100);
}

// Utility functions
function validatePrintData() {
  const container = $(".load-data .attendance-preview-container");
  if (!container.length) {
    return { valid: false, message: "Tidak ada data absensi untuk dicetak." };
  }

  const table = container.find(".attendance-table");
  if (!table.length) {
    return { valid: false, message: "Tabel absensi tidak ditemukan." };
  }

  const studentRows = table.find("tbody tr");
  if (!studentRows.length) {
    return { valid: false, message: "Tidak ada data siswa untuk dicetak." };
  }

  const headers = table.find("thead th");
  if (headers.length < 5) {
    return { valid: false, message: "Header tabel tidak lengkap." };
  }

  return { valid: true, message: "Data valid untuk dicetak." };
}

function getSelectedFilters() {
  return {
    kelas: $(".kelas").val(),
    kelasText: $(".kelas option:selected").text(),
    bulan: $(".bulan").val(),
    bulanText: $(".bulan option:selected").text(),
    tahun: $(".tahun").val(),
  };
}

// Fungsi print absensi yang diperbaiki
function printAbsensi() {
  // Prevent multiple calls
  if (isPrinting) {
    console.log("Print already in progress, skipping...");
    return false;
  }

  isPrinting = true;
  console.log("Starting print process...");

  // Cek apakah ada data yang sudah di-load
  const loadDataContainer = $(".load-data");
  if (
    !loadDataContainer.length ||
    loadDataContainer.find(".attendance-preview-container").length === 0
  ) {
    alert(
      "Tidak ada data absensi untuk dicetak. Silahkan pilih kelas terlebih dahulu."
    );
    isPrinting = false;
    return false;
  }

  // Get konten absensi dari container yang sudah di-generate
  const attendanceContainer = $(".load-data .attendance-preview-container");
  const printContent = attendanceContainer.find(".print-content");

  if (!printContent.length) {
    console.error("Konten print tidak ditemukan");
    alert("Konten absensi tidak valid untuk dicetak.");
    isPrinting = false;
    return false;
  }

  // Ambil semua elemen yang diperlukan untuk print
  const printHeader = printContent.find(".print-header").html();
  const reportTitle = printContent.find(".report-title").html();
  const reportPeriod = printContent.find(".report-period").html() || "";
  const attendanceTable = printContent.find(".attendance-table")[0].outerHTML;
  const bottomSection = printContent.find(".bottom-section").html();

  // Get info untuk title window
  const kelasInfo =
    $(".report-info-bottom .info-card-value strong").eq(0).text() ||
    $(".info-card-value strong").eq(0).text() ||
    "Kelas";
  // extract plain text from reportPeriod (strip html)
  const periodeInfo = reportPeriod
    ? reportPeriod
        .replace(/<[^>]*>/g, "")
        .replace(/Periode:\s*/i, "")
        .trim()
    : "";
  const printTitle = `Daftar Hadir Peserta Didik - ${kelasInfo}${
    periodeInfo ? " - " + periodeInfo : ""
  }`;

  // Hitung jumlah kolom tanggal untuk optimasi ukuran
  const dateColumns =
    printContent.find(".attendance-table thead tr:last-child th").length - 7; // Minus kolom tetap (No, NISN, Nama, L/P, S, I, A)
  const studentRows = printContent.find(".attendance-table tbody tr").length;

  // Default setting untuk A4 landscape dengan auto-fit
  const paperSize = "A4";
  let fontSize = 8;
  let pageMargin = "10mm 8mm";

  // Auto-adjust berdasarkan content density
  if (dateColumns > 30 || studentRows > 35) {
    fontSize = 6;
  } else if (dateColumns > 28 || studentRows > 30) {
    fontSize = 7;
  }

  // New approach: capture the generated preview as an image using html2canvas
  const targetElement = attendanceContainer.find(".print-content")[0];

  // Open a print window synchronously (must be done during the user gesture)
  // so that browsers don't block it as a popup when we write to it later.
  const printWindow = window.open(
    "",
    "_blank",
    "width=1200,height=800,scrollbars=yes"
  );
  if (!printWindow) {
    alert("Popup diblokir! Silahkan izinkan popup untuk mencetak.");
    isPrinting = false;
    return false;
  }

  function loadHtml2Canvas() {
    return new Promise((resolve, reject) => {
      if (typeof html2canvas !== "undefined") return resolve(html2canvas);
      if (document.querySelector("script[data-html2canvas]")) {
        const checker = setInterval(() => {
          if (typeof html2canvas !== "undefined") {
            clearInterval(checker);
            resolve(html2canvas);
          }
        }, 100);
        setTimeout(() => {
          clearInterval(checker);
          reject(new Error("Timeout loading html2canvas"));
        }, 8000);
        return;
      }
      const script = document.createElement("script");
      script.src =
        "https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js";
      script.setAttribute("data-html2canvas", "1");
      script.onload = () => {
        if (typeof html2canvas !== "undefined") return resolve(html2canvas);
        reject(new Error("html2canvas not available after load"));
      };
      script.onerror = () => reject(new Error("Failed to load html2canvas"));
      document.head.appendChild(script);
    });
  }

  // Try to capture the print content as an image
  loadHtml2Canvas()
    .then(() => {
      // Skala canvas agar tidak terlalu besar, supaya muat satu halaman
      const scale = 1; // pakai 1 agar tidak terlalu besar
      return html2canvas(targetElement, {
        scale: scale,
        useCORS: true,
        backgroundColor: "#ffffff",
      });
    })
    .then((canvas) => {
      const dataUrl = canvas.toDataURL("image/png");

      // CSS agar gambar fit ke satu halaman landscape
      const imageHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${printTitle}</title>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    @page { size: 21.6cm 33cm landscape; margin: 0.5mm 0.5mm; }
                    html, body { height: 100vh !important; width: 100vw !important; margin: 0 !important; padding: 0 !important; }
                    body { background: #fff; display: flex; align-items: center; justify-content: center; }
                    .print-image {
                        width: 100vw !important;
                        max-width: 100vw !important;
                        height: auto !important;
                        max-height: 100vh !important;
                        object-fit: contain !important;
                        page-break-inside: avoid !important;
                        display: block;
                    }
                    @media print {
                        html, body { height: 100vh !important; width: 100vw !important; margin: 0 !important; padding: 0 !important; }
                        .print-image {
                            width: 100vw !important;
                            max-width: 100vw !important;
                            height: auto !important;
                            max-height: 100vh !important;
                            object-fit: contain !important;
                            page-break-inside: avoid !important;
                            display: block;
                        }
                    }
                </style>
            </head>
            <body>
                <img class="print-image" src="${dataUrl}" alt="Absensi" />
            </body>
            </html>
        `;

      printWindow.document.write(imageHTML);
      printWindow.document.close();

      printWindow.onload = function () {
        printWindow.focus();
        setTimeout(() => {
          printWindow.print();
          setTimeout(() => {
            isPrinting = false;
          }, 800);
        }, 400);
      };
    })
    .catch((err) => {
      console.error(
        "html2canvas capture failed, falling back to HTML print. Error:",
        err
      );
      // Fallback: build the original HTML and print (previous behavior)
      // reuse the already-opened printWindow created earlier

      const printHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>${printTitle}</title>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                ${printCSS}
            </head>
            <body>
                <div class="print-header">
                    ${printHeader}
                </div>

                <div class="report-header-wrapper">
                    <div class="report-title">${reportTitle}</div>
                    <div class="report-period">${reportPeriod}</div>
                </div>

                ${attendanceTable}
                
                <div class="bottom-section">
                    ${bottomSection}
                </div>
            </body>
            </html>
        `;

      printWindow.document.write(printHTML);
      printWindow.document.close();

      printWindow.onload = function () {
        printWindow.focus();
        setTimeout(() => {
          printWindow.print();
          setTimeout(() => {
            isPrinting = false;
          }, 1000);
        }, 500);
      };
    });

  // Handle jika window gagal di-load
  setTimeout(() => {
    if (isPrinting) {
      console.log("Print process timeout, resetting...");
      isPrinting = false;
    }
  }, 10000);
}

// Event listener untuk print shortcut
$(document).on("keydown", function (event) {
  if ((event.ctrlKey || event.metaKey) && event.key === "p") {
    // Cek apakah focus pada area absensi
    const attendanceContainer = $(".load-data .attendance-preview-container");
    if (attendanceContainer.length && attendanceContainer.is(":visible")) {
      event.preventDefault();

      // Validasi data sebelum print dengan shortcut
      const validation = validatePrintData();
      if (validation.valid) {
        if (confirm("Print absensi dengan shortcut Ctrl+P?")) {
          printAbsensi();
        }
      } else {
        alert(validation.message);
      }
    }
  }
});

// Print event listeners
window.addEventListener("beforeprint", function () {
  console.log("Before print event fired");
});
