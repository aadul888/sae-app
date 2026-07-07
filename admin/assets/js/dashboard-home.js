/* Dashboard Home JavaScript Enhancements */

$(document).ready(function () {
  // Animate statistics numbers on page load
  animateStats();

  // Load table data
  loadTableData();

  // Auto refresh statistics every 5 minutes
  setInterval(function () {
    refreshStats();
  }, 300000);

  // Real-time clock update
  updateClock();
  setInterval(updateClock, 1000);
});

// Animate statistics numbers
function animateStats() {
  $(".card-stats .h2").each(function () {
    var $this = $(this);
    var countTo = parseInt($this.text());

    $({ countNum: 0 }).animate(
      {
        countNum: countTo,
      },
      {
        duration: 2000,
        easing: "swing",
        step: function () {
          $this.text(Math.floor(this.countNum));
        },
        complete: function () {
          $this.text(this.countNum);
        },
      }
    );
  });
}

// Load table data with loading effect
function loadTableData() {
  // Show loading animation
  $(".load-table").html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Memuat data statistik...</p>
        </div>
    `);

  // Simulate data loading (replace with actual AJAX call)
  setTimeout(function () {
    $(".load-table").html(`
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-primary">
                                <i class="fas fa-chart-line mr-2"></i>
                                Grafik Kehadiran Mingguan
                            </h5>
                            <canvas id="attendanceChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-success">
                                <i class="fas fa-users mr-2"></i>
                                Status Siswa Hari Ini
                            </h5>
                            <div class="progress-wrapper">
                                <div class="progress-info">
                                    <div class="progress-label">
                                        <span>Hadir</span>
                                    </div>
                                    <div class="progress-percentage">
                                        <span>85%</span>
                                    </div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 85%"></div>
                                </div>
                            </div>
                            <div class="progress-wrapper mt-3">
                                <div class="progress-info">
                                    <div class="progress-label">
                                        <span>Sakit</span>
                                    </div>
                                    <div class="progress-percentage">
                                        <span>10%</span>
                                    </div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 10%"></div>
                                </div>
                            </div>
                            <div class="progress-wrapper mt-3">
                                <div class="progress-info">
                                    <div class="progress-label">
                                        <span>Alpha</span>
                                    </div>
                                    <div class="progress-percentage">
                                        <span>5%</span>
                                    </div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 5%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="fas fa-clock mr-2"></i>
                                Aktivitas Terbaru
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline timeline-one-side">
                                <div class="timeline-block">
                                    <span class="timeline-step">
                                        <i class="fas fa-check text-success"></i>
                                    </span>
                                    <div class="timeline-content">
                                        <small class="text-muted">2 menit yang lalu</small>
                                        <h6>Siswa baru melakukan absensi</h6>
                                        <p class="text-sm mb-0">Ahmad Fauzi (XII IPA 1) melakukan absensi masuk</p>
                                    </div>
                                </div>
                                <div class="timeline-block">
                                    <span class="timeline-step">
                                        <i class="fas fa-user-plus text-info"></i>
                                    </span>
                                    <div class="timeline-content">
                                        <small class="text-muted">1 jam yang lalu</small>
                                        <h6>Data siswa baru ditambahkan</h6>
                                        <p class="text-sm mb-0">Siti Nurhaliza ditambahkan ke kelas XI IPS 2</p>
                                    </div>
                                </div>
                                <div class="timeline-block">
                                    <span class="timeline-step">
                                        <i class="fas fa-edit text-warning"></i>
                                    </span>
                                    <div class="timeline-content">
                                        <small class="text-muted">3 jam yang lalu</small>
                                        <h6>Jadwal kelas diperbarui</h6>
                                        <p class="text-sm mb-0">Jadwal mata pelajaran Matematika untuk kelas X diubah</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);

    // Initialize chart if Chart.js is available
    if (typeof Chart !== "undefined") {
      initAttendanceChart();
    }
  }, 2000);
}

// Initialize attendance chart
function initAttendanceChart() {
  var ctx = document.getElementById("attendanceChart");
  if (ctx) {
    new Chart(ctx.getContext("2d"), {
      type: "line",
      data: {
        labels: ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"],
        datasets: [
          {
            label: "Kehadiran (%)",
            data: [85, 90, 78, 92, 88, 85],
            borderColor: "#5e72e4",
            backgroundColor: "rgba(94, 114, 228, 0.1)",
            borderWidth: 2,
            fill: true,
            tension: 0.4,
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
          },
        },
        plugins: {
          legend: {
            display: false,
          },
        },
      },
    });
  }
}

// Refresh statistics
function refreshStats() {
  // Add subtle animation to indicate refresh
  $(".card-stats").addClass("refreshing");

  // Simulate AJAX call to refresh data
  setTimeout(function () {
    $(".card-stats").removeClass("refreshing");

    // Show toast notification
    showToast("Data statistik telah diperbarui", "success");
  }, 1000);
}

// Real-time clock update
function updateClock() {
  var now = new Date();
  var timeString = now.toLocaleTimeString("id-ID");

  // Update time in info card if exists
  $(".card-body").find('span:contains("Jam Server")').next().text(timeString);
}

// Show toast notification
function showToast(message, type = "info") {
  var toast = $(`
        <div class="toast" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
            <div class="toast-header bg-${type} text-white">
                <strong class="mr-auto">Notifikasi</strong>
                <button type="button" class="btn-close btn-close-white" data-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `);

  $("body").append(toast);
  toast.toast("show");

  // Auto remove after 5 seconds
  setTimeout(function () {
    toast.remove();
  }, 5000);
}

// Add refresh animation CSS
$("<style>")
  .prop("type", "text/css")
  .html(
    `
        .card-stats.refreshing {
            animation: pulse 1s ease-in-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .timeline {
            position: relative;
            padding: 0;
        }
        
        .timeline-block {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 3rem;
        }
        
        .timeline-step {
            position: absolute;
            left: 0;
            top: 0;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .timeline-block:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 2rem;
            bottom: -2rem;
            width: 2px;
            background: #dee2e6;
        }
        
        .timeline-content h6 {
            margin-bottom: 0.5rem;
            color: #32325d;
        }
        
        .progress-wrapper {
            margin-bottom: 1rem;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
    `
  )
  .appendTo("head");
