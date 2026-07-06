// Home Dashboard Scripts
document.addEventListener("DOMContentLoaded", function () {
  // Suppress Chart.js warnings
  if (typeof Chart !== "undefined") {
    if (Chart.defaults.global) {
      Chart.defaults.global.legend.display = false;
    } else if (Chart.defaults.plugins) {
      Chart.defaults.plugins.legend.display = false;
    }
  }

  // Initialize page loader
  initializePageLoader();
  // Initialize charts
  initializeCharts();

  // Initialize animations and interactions
  initializeAnimations();

  // Initialize performance monitoring
  initializePerformanceMonitoring();
});

// Page loader initialization
function initializePageLoader() {
  const pageLoader = document.getElementById("pageLoader");
  const mainContent = document.getElementById("panel");

  if (pageLoader) {
    setTimeout(function () {
      pageLoader.classList.add("hide");
      if (mainContent) {
        mainContent.classList.add("loaded");
      }
    }, 500);
  }
}

// Enhanced Clock functionality
function initializeClock() {
  function updateClock() {
    const now = new Date();
    const months = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];

    // Format date
    const day = String(now.getDate()).padStart(2, "0");
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    const dateString = `${day} ${month} ${year}`;

    // Format time
    const hours = String(now.getHours()).padStart(2, "0");
    const minutes = String(now.getMinutes()).padStart(2, "0");
    const timeString = `${hours}:${minutes} WIB`;

    // Update DOM elements (use #currentDateJS so we don't overwrite academic year text)
    const dateElement = document.getElementById("currentDateJS");
    const timeElement = document.getElementById("currentTime");

    if (dateElement) dateElement.textContent = dateString;
    if (timeElement) timeElement.textContent = timeString;
  }

  // Initialize clock immediately and update every second
  updateClock();
  setInterval(updateClock, 1000);
}

// Enhanced Chart initialization
function initializeCharts() {
  function sanitizePercent(rawValue) {
    let value = parseFloat(rawValue);
    if (!Number.isFinite(value)) {
      value = 0;
    }
    if (value < 0) value = 0;
    if (value > 100) value = 100;
    return value;
  }

  function drawDonutFallback(canvas, percent, color) {
    if (!canvas || typeof canvas.getContext !== "function") {
      return;
    }

    const ctx = canvas.getContext("2d");
    if (!ctx) {
      return;
    }

    const width = canvas.width || 80;
    const height = canvas.height || 80;
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(centerX, centerY) - 4;
    const lineWidth = 12;
    const startAngle = -Math.PI / 2;
    const endAngle = startAngle + (Math.PI * 2 * percent) / 100;

    ctx.clearRect(0, 0, width, height);

    // Background ring
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
    ctx.strokeStyle = "rgba(224, 224, 224, 0.35)";
    ctx.lineWidth = lineWidth;
    ctx.lineCap = "round";
    ctx.stroke();

    // Progress ring
    if (percent > 0) {
      ctx.beginPath();
      ctx.arc(centerX, centerY, radius, startAngle, endAngle);
      ctx.strokeStyle = color;
      ctx.lineWidth = lineWidth;
      ctx.lineCap = "round";
      ctx.stroke();
    }
  }

  const ctxKehadiran = document.getElementById("chartKehadiran");
  const ctxPoint = document.getElementById("chartPoint");

  // If Chart.js unavailable, draw canvas fallback so chart area is never empty.
  if (typeof Chart === "undefined") {
    const hadirPercentFallback = sanitizePercent(
      ctxKehadiran ? ctxKehadiran.getAttribute("data-persen") : 0
    );
    const poinPercentFallback = sanitizePercent(
      ctxPoint ? ctxPoint.getAttribute("data-persen") : 0
    );
    const poinColorFallback =
      poinPercentFallback >= 100
        ? "rgba(229, 57, 53, 1)"
        : poinPercentFallback >= 57
          ? "rgba(255, 152, 0, 1)"
          : "rgba(67, 160, 71, 1)";

    drawDonutFallback(ctxKehadiran, hadirPercentFallback, "rgba(15, 118, 110, 1)");
    drawDonutFallback(ctxPoint, poinPercentFallback, poinColorFallback);
    return;
  }

  // Chart configuration
  const chartOptions = {
    cutoutPercentage: 75,
    legend: { display: false },
    tooltips: { enabled: false },
    responsive: false,
    maintainAspectRatio: false,
    animation: {
      animateRotate: true,
      duration: 2000,
      easing: "easeInOutQuart",
    },
  };

  // Attendance Chart (Kehadiran)
  if (ctxKehadiran) {
    try {
      const hadirPersen = sanitizePercent(ctxKehadiran.getAttribute("data-persen"));
      new Chart(ctxKehadiran, {
        type: "doughnut",
        data: {
          datasets: [
            {
              data: [hadirPersen, 100 - hadirPersen],
              backgroundColor: [
                "rgba(15, 118, 110, 1)",
                "rgba(224, 224, 224, 0.3)",
              ],
              borderWidth: 0,
              borderRadius: 8,
            },
          ],
        },
        options: chartOptions,
      });
    } catch (error) {
      drawDonutFallback(ctxKehadiran, sanitizePercent(ctxKehadiran.getAttribute("data-persen")), "rgba(15, 118, 110, 1)");
    }
  }

  // Point Chart
  if (ctxPoint) {
    try {
      const poinPersen = sanitizePercent(ctxPoint.getAttribute("data-persen"));
      const poinColor =
        poinPersen >= 100
          ? "rgba(229, 57, 53, 1)"
          : poinPersen >= 57
            ? "rgba(255, 152, 0, 1)"
            : "rgba(67, 160, 71, 1)";
      new Chart(ctxPoint, {
        type: "doughnut",
        data: {
          datasets: [
            {
              data: [poinPersen, 100 - poinPersen],
              backgroundColor: [
                poinColor,
                "rgba(224, 224, 224, 0.3)",
              ],
              borderWidth: 0,
              borderRadius: 8,
            },
          ],
        },
        options: chartOptions,
      });
    } catch (error) {
      const poinPercentFallback = sanitizePercent(ctxPoint.getAttribute("data-persen"));
      const poinColorFallback =
        poinPercentFallback >= 100
          ? "rgba(229, 57, 53, 1)"
          : poinPercentFallback >= 57
            ? "rgba(255, 152, 0, 1)"
            : "rgba(67, 160, 71, 1)";
      drawDonutFallback(ctxPoint, poinPercentFallback, poinColorFallback);
    }
  }
}

// Animations and interactions
function initializeAnimations() {
  // Menu interactions
  const menuItems = document.querySelectorAll(".menu-item");
  menuItems.forEach((item) => {
    item.addEventListener("mouseenter", function () {
      this.style.transform = "scale(1.05)";
      this.style.transition = "transform 0.3s ease";
    });

    item.addEventListener("mouseleave", function () {
      this.style.transform = "scale(1)";
    });
  });

  // Stats card animations with Intersection Observer
  const statsCards = document.querySelectorAll(".stats-card");
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -100px 0px",
  };

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1";
        entry.target.style.transform = "translateY(0)";
      }
    });
  }, observerOptions);

  statsCards.forEach((card) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(20px)";
    card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    observer.observe(card);
  });

  // Notification badge pulse animation
  const badges = document.querySelectorAll(".notification-badge");
  badges.forEach((badge) => {
    badge.style.animation = "pulse 2s infinite";
  });

  // Table row hover effects
  const tableRows = document.querySelectorAll(".attendance-table tbody tr");
  tableRows.forEach((row) => {
    row.addEventListener("mouseenter", function () {
      this.style.backgroundColor = "rgba(0, 123, 255, 0.05)";
      this.style.transform = "scale(1.005)";
      this.style.transition = "all 0.3s ease";
    });

    row.addEventListener("mouseleave", function () {
      this.style.backgroundColor = "";
      this.style.transform = "scale(1)";
    });
  });

  // Welcome header animation on load
  const welcomeHeader = document.querySelector(".welcome-header");
  if (welcomeHeader) {
    welcomeHeader.style.opacity = "0";
    welcomeHeader.style.transform = "translateY(-20px)";

    setTimeout(() => {
      welcomeHeader.style.transition = "opacity 0.8s ease, transform 0.8s ease";
      welcomeHeader.style.opacity = "1";
      welcomeHeader.style.transform = "translateY(0)";
    }, 100);
  }

  // Smooth scrolling for internal links
  const internalLinks = document.querySelectorAll('a[href^="#"]');
  internalLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // Enhanced dropdown interactions (if using jQuery)
  if (typeof $ !== "undefined") {
    $(".dropdown-toggle").on("show.bs.dropdown", function () {
      $(this).find(".avatar img").css("transform", "scale(1.1)");
    });

    $(".dropdown-toggle").on("hide.bs.dropdown", function () {
      $(this).find(".avatar img").css("transform", "scale(1)");
    });
  }
}

// Performance monitoring
function initializePerformanceMonitoring() {
  if ("performance" in window) {
    window.addEventListener("load", function () {
      setTimeout(function () {
        try {
          const perfData = performance.getEntriesByType("navigation")[0];
          const loadTime = perfData.loadEventEnd - perfData.loadEventStart;

          // Performance monitoring completed
        } catch (error) {
          // Performance monitoring failed
        }
      }, 0);
    });
  }
}

// Login button functionality (legacy support)
function loading_login() {
  const btnLogin = document.querySelector(".btn-login");
  if (btnLogin) {
    btnLogin.disabled = true;
    btnLogin.innerHTML =
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';

    setTimeout(function () {
      btnLogin.disabled = false;
      btnLogin.innerHTML = "Login";
    }, 2000);
  }
}

// Utility functions
function showAlert(message, type = "info") {
  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
  alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;

  const container = document.querySelector(".home-dashboard-container");
  if (container) {
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-hide after 5 seconds
    setTimeout(() => {
      alertDiv.remove();
    }, 5000);
  }
}

// Export functions for global access
window.loading_login = loading_login;
window.showAlert = showAlert;

// CSS Animations via JavaScript (fallback)
if (!document.querySelector("#dashboard-animations")) {
  const style = document.createElement("style");
  style.id = "dashboard-animations";
  style.textContent = `
    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
        opacity: 1;
      }
      50% {
        transform: scale(1.1);
        opacity: 0.8;
      }
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .animate-fade-in-up {
      animation: fadeInUp 0.6s ease forwards;
    }
    
    .page-loader.hide {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: all 0.5s ease;
    }
    
    .main-content {
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .main-content.loaded {
      opacity: 1;
    }
  `;
  document.head.appendChild(style);
}

// Pembaharuan modal handler (moved from inline script in home.php)
(function () {
  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var d = new Date();
      d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + d.toUTCString();
    }
    document.cookie =
      name + "=" + encodeURIComponent(value) + expires + "; path=/";
  }

  function deleteCookie(name) {
    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
  }

  function initPembaharuan() {
    // If inline handler already managed the modal, do nothing to avoid duplicate show/backdrop
    if (window.__pembaharuan_handled_by_inline) return;
    var modal = document.getElementById("modalPembaharuan");
    if (!modal) return;

    var latest = modal.getAttribute("data-latest") || "";

    // Always remove any lingering last_seen cookie to avoid accidental hides
    deleteCookie("last_seen_pembaharuan");

    if (typeof $ !== "undefined" && $("#modalPembaharuan").length) {
      // Mark as handled so other initializers won't also try to show it
      try {
        window.__pembaharuan_handled_by_inline = true;
      } catch (err) {}
      $("#modalPembaharuan").modal("show");

      $("#hidePembaharuan")
        .off("click.pemb")
        .on("click.pemb", function () {
          var expires = new Date();
          expires.setTime(expires.getTime() + 30 * 24 * 60 * 60 * 1000);
          if (latest) {
            // store v2 format for consistency
            setCookie("hide_pembaharuan", "v2:" + latest, 30);
          } else {
            setCookie("hide_pembaharuan", "v2:1", 30);
          }
          $("#modalPembaharuan").modal("hide");
        });

      $('.btn-primary[data-dismiss="modal"]')
        .off("click.pemb")
        .on("click.pemb", function () {
          $("#modalPembaharuan").modal("hide");
        });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPembaharuan);
  } else {
    initPembaharuan();
  }
})();
