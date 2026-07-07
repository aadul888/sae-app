"use strict";

// Global variables for charts
let chartKualitas, chartJurusan;
let currentFilters = { jurusan: "", kelas: "" };

// Small helpers
function escapeHtml(str) {
  if (!str && str !== 0) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function titleCase(s) {
  if (!s) return "";
  return s
    .toLowerCase()
    .split(/\s+/)
    .map(function (word) {
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(" ");
}

// Get the correct base path based on the current location
function getBasePath() {
  const currentPath = window.location.pathname;
  if (currentPath.includes("/module/realtime/")) {
    // Standalone mode
    return "./proses.php";
  } else {
    // Integrated mode
    return "./module/realtime/proses.php";
  }
}

// Initialize the publikasi kualitas data page
$(document).ready(function () {
  // Initialized

  // Load initial data
  loadStatistics();
  loadChartData();
  loadTopClasses();

  // Set up auto-refresh every 5 minutes
  setInterval(function () {
    refreshData();
  }, 300000); // 5 minutes

  // Add body class for styling
  $("body").addClass("publikasi-kualitas-page");
});

// filters removed

// Load overall statistics
function loadStatistics() {
  const params = new URLSearchParams(currentFilters).toString();

  $.ajax({
    url: `${getBasePath()}?action=get_statistics&${params}`,
    type: "GET",
    dataType: "json",
    success: function (data) {
      // statistics loaded

      // Update statistics cards
      $("#totalSiswa").text(data.total_siswa || 0);
      // 'Konfirmasi' card should show konfirmasi-only count (Sesuai + Belum Sesuai)
      $("#dataValid").text(data.data_konfirmasi || 0);
      // 'Berkas Valid' card shows distinct users with valid berkas
      $("#dataReview").text(data.data_review || 0);
      // Kualitas uses the union (konfirmasi OR berkas valid)
      $("#persenKualitas").text((data.persen_kualitas || 0) + "%");

      // Add animation effect
      $(".stats-number").addClass("animate-counter");
    },
    error: function (xhr, status, error) {
      console.error("Error loading statistics:", error);
      // Show placeholder data on error
      $("#totalSiswa").text("-");
      $("#dataValid").text("-");
      $("#dataReview").text("-");
      $("#persenKualitas").text("-");
    },
  });
}

// Load chart data
function loadChartData() {
  const params = new URLSearchParams(currentFilters).toString();

  // Load pie chart data
  $.ajax({
    url: `${getBasePath()}?action=get_chart_data&${params}`,
    type: "GET",
    dataType: "json",
    success: function (data) {
      updateKualitasChart(data);
    },
    error: function (xhr, status, error) {
      console.error("Error loading chart data:", error);
    },
  });

  // Load jurusan chart data
  $.ajax({
    url: getBasePath() + "?action=get_jurusan_chart",
    type: "GET",
    dataType: "json",
    success: function (data) {
      updateJurusanChart(data);
    },
    error: function (xhr, status, error) {
      console.error("Error loading jurusan chart data:", error);
    },
  });
}

// Update kualitas pie chart
function updateKualitasChart(data) {
  const ctx = document.getElementById("chartKualitas");
  if (!ctx) return;

  // Destroy existing chart
  if (chartKualitas) {
    chartKualitas.destroy();
  }

  chartKualitas = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: data.labels || ["Valid", "Review", "Tidak Valid"],
      datasets: [
        {
          data: data.data || [0, 0, 0],
          backgroundColor: data.colors || ["#28a745", "#ffc107", "#dc3545"],
          borderWidth: 2,
          borderColor: "#fff",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "60%",
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            padding: 20,
            usePointStyle: true,
            font: {
              size: 16,
              weight: "bold",
            },
            color: "#1a202c",
          },
        },
        tooltip: {
          titleFont: {
            size: 16,
            weight: "bold",
          },
          bodyFont: {
            size: 14,
            weight: "normal",
          },
          backgroundColor: "rgba(255, 255, 255, 0.95)",
          titleColor: "#1a202c",
          bodyColor: "#1a202c",
          borderColor: "#e9ecef",
          borderWidth: 2,
          callbacks: {
            label: function (context) {
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage =
                total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
              return `${context.label}: ${context.parsed} (${percentage}%)`;
            },
          },
        },
      },
      animation: {
        animateRotate: true,
        duration: 1000,
      },
    },
  });
}

// Update jurusan bar chart
function updateJurusanChart(data) {
  const ctx = document.getElementById("chartJurusan");
  if (!ctx) return;

  // Destroy existing chart
  if (chartJurusan) {
    chartJurusan.destroy();
  }

  chartJurusan = new Chart(ctx, {
    type: "bar",
    data: {
      labels: data.labels || [],
      datasets: [
        {
          label: "Kualitas Data (%)",
          data: data.data || [],
          backgroundColor: data.colors || [
            "#007bff",
            "#28a745",
            "#ffc107",
            "#dc3545",
            "#6f42c1",
            "#20c997",
          ],
          borderColor: data.colors || [
            "#007bff",
            "#28a745",
            "#ffc107",
            "#dc3545",
            "#6f42c1",
            "#20c997",
          ],
          borderWidth: 1,
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          ticks: {
            callback: function (value) {
              return value + "%";
            },
            font: {
              size: 14,
              weight: "bold",
            },
            color: "#1a202c",
          },
          grid: {
            color: "rgba(0, 0, 0, 0.1)",
          },
        },
        x: {
          ticks: {
            maxRotation: 0,
            minRotation: 0,
            font: {
              size: 14,
              weight: "bold",
            },
            color: "#1a202c",
          },
          grid: {
            color: "rgba(0, 0, 0, 0.1)",
          },
        },
      },
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          titleFont: {
            size: 16,
            weight: "bold",
          },
          bodyFont: {
            size: 14,
            weight: "normal",
          },
          backgroundColor: "rgba(255, 255, 255, 0.95)",
          titleColor: "#1a202c",
          bodyColor: "#1a202c",
          borderColor: "#e9ecef",
          borderWidth: 2,
          callbacks: {
            label: function (context) {
              return `Kualitas: ${context.parsed.y}%`;
            },
          },
        },
      },
      animation: {
        duration: 1000,
        easing: "easeInOutQuart",
      },
    },
  });
}

// Load top performing classes
function loadTopClasses() {
  $.ajax({
    url: getBasePath() + "?action=get_top_classes",
    type: "GET",
    dataType: "json",
    success: function (data) {
      updateTopClassesTable(data);
    },
    error: function (xhr, status, error) {
      console.error("Error loading top classes:", error);
      console.error("XHR:", xhr);
      $("#topClassesTable").html(
        '<tr><td colspan="7" class="text-center text-muted py-3"><i class="fas fa-exclamation-triangle me-2"></i>Error memuat data</td></tr>'
      );
    },
  });
}

// Update top classes table
function updateTopClassesTable(data) {
  const tbody = $("#topClassesTable");
  tbody.empty();

  if (data && data.length > 0) {
    data.forEach(function (item) {
      const badgeClass =
        item.persen_kualitas >= 80
          ? "bg-success"
          : item.persen_kualitas >= 60
          ? "bg-warning"
          : "bg-danger";
      // format jurusan for neat display and truncate with CSS
      const jurusanDisplay = titleCase(item.nama_jurusan || "");
      const jurusanTitle = escapeHtml(item.nama_jurusan || "");

      const row = `
          <tr>
            <td>${item.no}</td>
            <td style="text-align:left"><strong>${escapeHtml(
              item.nama_kelas
            )}</strong></td>
            <td style="text-align:left"><div class="nama-jurusan-cell" title="${jurusanTitle}">${escapeHtml(
        jurusanDisplay
      )}</div></td>
            <td class="text-center">${item.total_siswa}</td>
            <td class="text-center">${item.valid_siswa}</td>
            <td class="text-center">
              <span class="badge ${badgeClass}">${item.persen_kualitas}%</span>
            </td>
            <td class="text-center">
              <button class="btn btn-outline-primary btn-sm" onclick="showClassDetail('${escapeHtml(
                item.nama_kelas
              )}', '${escapeHtml(item.nama_kelas)}')">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
        `;
      tbody.append(row);
    });
  } else {
    tbody.html(
      '<tr><td colspan="7" class="text-center text-muted py-3"><i class="fas fa-info-circle me-2"></i>Belum ada data</td></tr>'
    );
  }
}

// Apply filter function
// applyFilter removed (filters are no longer present)

// Refresh all data
function refreshData() {
  console.log("Refreshing all data...");

  // Show loading state
  showLoadingState();

  // Reload all data
  loadStatistics();
  loadChartData();
  loadTopClasses();

  // Update last update time
  updateLastUpdateTime();

  // Show success message briefly
  showRefreshSuccess();
}

// Show loading state
function showLoadingState() {
  $(".stats-number").text("...");
  $("#topClassesTable").html(
    '<tr><td colspan="6" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i>Memuat data...</td></tr>'
  );
}

// Update last update time
function updateLastUpdateTime() {
  const now = new Date();
  const timeString =
    now.toLocaleString("id-ID", {
      day: "2-digit",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    }) + " WIB";

  $("#lastUpdate").text(timeString);
}

// Show refresh success message
function showRefreshSuccess() {
  const refreshBtn = $('button[onclick="refreshData()"]');
  const originalHtml = refreshBtn.html();

  refreshBtn.html('<i class="fas fa-check me-1"></i>Berhasil');
  refreshBtn.removeClass("btn-outline-primary").addClass("btn-success");

  setTimeout(function () {
    refreshBtn.html(originalHtml);
    refreshBtn.removeClass("btn-success").addClass("btn-outline-primary");
  }, 2000);
}

// Show detail data for selected class from filter
// showDetailForSelectedClass removed (filters not present)

// Show class detail data
function showClassDetail(kelasIdentifier, kelasName) {
  // store current class identifier for refresh actions
  try {
    window._currentClassIdentifier = kelasIdentifier;
    window._currentClassName = kelasName;
  } catch (e) {}
  // Prepare modal loading state
  // set header placeholders (detail will fill actual names)
  $("#modalClassName").text(kelasName || "");
  $("#modalJurusanName").text("");
  // remove loading placeholder row (keep table empty until data arrives)
  $("#modalDetailTableBody").html("");
  $("#modalDetailTotalSiswa").text("-");
  $("#modalDetailDataValid").text("-");
  $("#modalDetailPersenKualitas").text("-");
  // show modal
  if (typeof bootstrap !== "undefined") {
    var modalEl = document.getElementById("classDetailModal");
    var modal = new bootstrap.Modal(modalEl, {});
    modal.show();
  }

  // Determine if we're using kelas_id or kelas_nama
  let apiUrl = getBasePath() + "?action=get_class_detail";
  if (isNumeric(kelasIdentifier)) {
    apiUrl += `&kelas_id=${kelasIdentifier}`;
  } else {
    apiUrl += `&kelas_nama=${encodeURIComponent(kelasIdentifier)}`;
  }

  // Load class detail data
  $.ajax({
    url: apiUrl,
    type: "GET",
    dataType: "json",
    success: function (data) {
      updateClassDetailTable(data);
    },
    error: function (xhr, status, error) {
      console.error("Error loading class detail:", error);
      $("#detailTableBody").html(
        '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-exclamation-triangle me-2"></i>Error memuat data</td></tr>'
      );
    },
  });
}

// Helper function to check if string is numeric
function isNumeric(str) {
  return /^\d+$/.test(str);
}

// Filter kelas based on selected jurusan
// filterKelasByJurusan removed (filters not present)

// Update class detail table
function updateClassDetailTable(data) {
  const tbody = $("#detailTableBody");
  tbody.empty();

  // Update stats
  if (data.stats) {
    $("#detailTotalSiswa").text(data.stats.total_siswa || 0);
    $("#detailDataValid").text(data.stats.data_valid || 0);
    $("#detailPersenKualitas").text((data.stats.persen_kualitas || 0) + "%");
  }

  // Also update modal stats (if modal present)
  if (data.stats) {
    $("#modalDetailTotalSiswa").text(data.stats.total_siswa || 0);
    $("#modalDetailDataValid").text(data.stats.data_valid || 0);
    $("#modalDetailPersenKualitas").text(
      (data.stats.persen_kualitas || 0) + "%"
    );
  }

  // Update class name with jurusan info
  if (data.kelas_info) {
    $("#selectedClassName").text(
      "- " +
        data.kelas_info.nama_kelas +
        " (" +
        data.kelas_info.nama_jurusan +
        ")"
    );
  }

  // Also update modal title
  if (data.kelas_info) {
    $("#modalClassName").text(data.kelas_info.nama_kelas || "");
    $("#modalJurusanName").text(data.kelas_info.nama_jurusan || "");
  }

  // Populate table
  if (data.siswa && data.siswa.length > 0) {
    data.siswa.forEach(function (siswa, index) {
      // Determine identitas text from user.konfirmasi if available, fallback to status_identitas
      var identitasText =
        siswa.konfirmasi || siswa.status_identitas || "Belum Sesuai";
      // Determine badge class: prefer provided badge, otherwise map from text
      var identitasBadge =
        siswa.badge_identitas ||
        (/(sesuai)/i.test(identitasText) ? "success" : "danger");

      const row = `
            <tr>
              <td>${index + 1}</td>
              <td><code>${siswa.nisn_sensor}</code></td>
              <td><strong>${siswa.nama_sensor}</strong></td>
            <td class="text-center">
              <span class="badge bg-${identitasBadge}">${identitasText}</span>
            </td>
              <td class="text-center">
                <span class="badge bg-${siswa.badge_berkas}">${
        siswa.status_berkas
      }</span>
              </td>
            </tr>
          `;
      tbody.append(row);
      // also append to modal table body if exists
      const modalTbody = $("#modalDetailTableBody");
      if (modalTbody.length) {
        modalTbody.append(row);
      }
    });
  } else {
    tbody.html(
      '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-info-circle me-2"></i>Tidak ada data siswa</td></tr>'
    );
    if ($("#modalDetailTableBody").length) {
      $("#modalDetailTableBody").html(
        '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-info-circle me-2"></i>Tidak ada data siswa</td></tr>'
      );
    }
  }
}

// Hide detail data section
function hideDetailData() {
  $("#detailDataSection").hide();
}

// Refresh detail data
function refreshDetailData() {
  // Use last opened class identifier (stored by showClassDetail)
  try {
    var id = window._currentClassIdentifier || null;
    var name = window._currentClassName || null;
    if (id) {
      showClassDetail(id, name || id);
    } else {
      // nothing to refresh
      if (typeof swal !== "undefined") {
        swal({
          title: "Perhatian",
          text: "Tidak ada kelas yang dipilih.",
          icon: "info",
          timer: 2000,
        });
      }
    }
  } catch (e) {
    // safe fallback: do nothing
  }
}

// Enhanced form interactions (keep existing functionality)
$(".module-home-form-control")
  .on("focus", function () {
    $(this).parent().addClass("focused");
  })
  .on("blur", function () {
    $(this).parent().removeClass("focused");
  });

// Auto-format NISN input (keep existing functionality)
$('input[name="nisn"]').on("input", function () {
  this.value = this.value.replace(/\D/g, "");
  if (this.value.length > 10) {
    this.value = this.value.slice(0, 10);
  }
});

// Enhanced form submission with better error handling (keep existing functionality)
$(".form-nisn").submit(function (e) {
  e.preventDefault();
  const nisn = $(this).find('input[name="nisn"]').val();

  if (nisn.length < 10) {
    if (typeof swal !== "undefined") {
      swal({
        title: "Perhatian!",
        text: "NISN harus 10 digit angka",
        icon: "warning",
        timer: 2500,
      });
    } else {
      alert("NISN harus 10 digit angka");
    }
    return;
  }

  loading();

  $.ajax({
    url: getBasePath() + "?action=cari",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
      var results = data.split("/");
      var success = results[0];
      var nisn = results[1];

      if (success == "success") {
        $(".form-nisn").trigger("reset");
        if (typeof swal !== "undefined") {
          swal({
            title: "Berhasil!",
            text: "Data ditemukan, mengalihkan...",
            icon: "success",
            timer: 2000,
          });
        } else {
          alert("Data ditemukan!");
        }
        window.setTimeout((window.location.href = "./nisn/" + nisn + ""), 2500);
      } else {
        if (typeof swal !== "undefined") {
          swal({
            title: "Data Tidak Ditemukan!",
            text: data,
            icon: "error",
            timer: 3000,
          });
        } else {
          alert("Data Tidak Ditemukan!\n" + data);
        }
      }
    },
    error: function () {
      if (typeof swal !== "undefined") {
        swal({
          title: "Kesalahan!",
          text: "Terjadi kesalahan server",
          icon: "error",
          timer: 2500,
        });
      } else {
        alert("Terjadi kesalahan server");
      }
    },
  });
});

// Modern loading function with better UX (keep existing functionality)
function loading() {
  const submitBtn = $(".btn-primary[type='submit']");
  submitBtn.prop("disabled", true);
  submitBtn.html(
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...'
  );

  window.setTimeout(function () {
    submitBtn.prop("disabled", false);
    submitBtn.html('<i class="fas fa-search me-2"></i> Periksa');
  }, 2000);
}

// Add responsive behavior
function handleResize() {
  const width = $(window).width();
  if (width < 768) {
    $(".module-home-container").addClass("mobile-layout");
  } else {
    $(".module-home-container").removeClass("mobile-layout");
  }
}

$(window).on("resize", handleResize);
handleResize(); // Initial call

// Defensive cleanup for modal issues
(function () {
  try {
    if (
      document &&
      document.body &&
      document.body.classList &&
      document.body.classList.contains("modal-open")
    ) {
      var visibleModal = document.querySelector(".modal.show");
      if (!visibleModal) {
        document.body.classList.remove("modal-open");
        if (document.body.style && document.body.style.overflow === "hidden") {
          document.body.style.overflow = "";
        }
      }
    }
  } catch (e) {
    if (window.console && console.error)
      console.error("modal-open cleanup error", e);
  }
})();

// Ensure modal close buttons always work (Bootstrap 5 or fallback)
$(document).on(
  "click",
  '#classDetailModal [data-bs-dismiss="modal"], #classDetailModal .btn-close',
  function (e) {
    try {
      var modalEl = document.getElementById("classDetailModal");
      if (!modalEl) return;

      // DOM-only safe hide (avoid calling bootstrap APIs which may differ by version)
      try {
        // remove visible classes/attributes
        modalEl.classList.remove("show");
        modalEl.style.display = "none";
        modalEl.setAttribute("aria-hidden", "true");
        modalEl.removeAttribute("aria-modal");
        modalEl.removeAttribute("role");
      } catch (e) {
        // ignore
      }

      // Remove backdrop(s) and restore body
      try {
        var backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach(function (b) {
          if (b && b.parentNode) b.parentNode.removeChild(b);
        });
      } catch (e) {
        // ignore
      }

      try {
        document.body.classList.remove("modal-open");
        if (document.body && document.body.style)
          document.body.style.overflow = "";
      } catch (e) {
        // ignore
      }
    } catch (err) {
      if (window.console && console.error)
        console.error("modal close fallback error", err);
    }
  }
);
