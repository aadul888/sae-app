"use strict";

// Modern loading function with better UX
function loading() {
  const submitBtn = $(".btn-primary[type='submit']");
  submitBtn.prop("disabled", true);
  // Modern loading spinner
  submitBtn.html(
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...'
  );
  window.setTimeout(function () {
    submitBtn.prop("disabled", false);
    submitBtn.html('<i class="fas fa-search me-2"></i> Cek Data');
  }, 2000);
}
// Enhanced form submission with better error handling
$(".form-nisn").submit(function (e) {
  e.preventDefault();
  const nisn = $(this).find('input[name="nisn"]').val();
  // Validate NISN length
  if (nisn.length < 10) {
    swal({
      title: "Perhatian!",
      text: "NISN harus 10 digit angka",
      icon: "warning",
      timer: 2500,
    });
    return;
  }
  loading();
  $.ajax({
    url: "./module/home/proses.php?action=cari",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      var results = data.split("/");
      var success = results[0];
      var nisn = results[1];
      if (success == "success") {
        $(".form-nisn").trigger("reset");
        swal({
          title: "Berhasil!",
          text: "Data ditemukan, mengalihkan...",
          icon: "success",
          timer: 2000,
        });
        window.setTimeout((window.location.href = "./nisn/" + nisn + ""), 2500);
      } else {
        swal({
          title: "Data Tidak Ditemukan!",
          text: data,
          icon: "error",
          timer: 3000,
        });
      }
    },
    error: function () {
      swal({
        title: "Kesalahan!",
        text: "Terjadi kesalahan server",
        icon: "error",
        timer: 2500,
      });
    },
    complete: function () {
      $(".loading").hide();
    },
  });
});
// Add static interactions
$(document).ready(function () {
  // Add loading class to body for styling
  $("body").addClass("home-page");
  // Enhanced form interactions
  $(".module-home-form-control")
    .on("focus", function () {
      $(this).parent().addClass("focused");
    })
    .on("blur", function () {
      $(this).parent().removeClass("focused");
    });
  // Auto-format NISN input
  $('input[name="nisn"]').on("input", function () {
    // Remove non-numeric characters
    this.value = this.value.replace(/\D/g, "");
    // Limit to 10 digits
    if (this.value.length > 10) {
      this.value = this.value.slice(0, 10);
    }
  });
  // Add hover effects to table rows
  $(".module-home-table tbody tr").hover(
    function () {
      $(this).addClass("table-row-hover");
    },
    function () {
      $(this).removeClass("table-row-hover");
    }
  );
  // Add responsive behavior - only for mobile
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
});
// Floating Action Button Functionality
$(document).ready(function () {
  // Initialize FAB functionality
  initFloatingActionButton();
});

function initFloatingActionButton() {
  const fabContainer = $("#fabContainer");
  const fabMain = $("#fabMain");
  let isOpen = false;
  let interactionCount = 0;

  // Ensure elements exist
  if (!fabContainer.length || !fabMain.length) {
    return;
  }

  // Toggle FAB menu
  fabMain.on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();

    isOpen = !isOpen;
    interactionCount++;

    if (isOpen) {
      fabContainer.addClass("open");
      fabMain.removeClass("pulse");

      // Track analytics (optional)
      if (typeof gtag !== "undefined") {
        gtag("event", "fab_opened", {
          event_category: "engagement",
          event_label: "floating_action_button",
        });
      }
    } else {
      closeFAB();
    }
  });

  // Close FAB function
  function closeFAB() {
    isOpen = false;
    fabContainer.removeClass("open");

    // Re-add pulse after delay if user hasn't interacted much
    if (interactionCount < 3) {
      setTimeout(() => {
        if (!isOpen && !fabMain.is(":hover")) {
          fabMain.addClass("pulse");
        }
      }, 2000);
    }
  }

  // Close FAB menu when clicking outside
  $(document).on("click", function (e) {
    if (
      isOpen &&
      !fabContainer.is(e.target) &&
      fabContainer.has(e.target).length === 0
    ) {
      closeFAB();
    }
  });

  // Close FAB when pressing Escape key
  $(document).on("keydown", function (e) {
    if (e.key === "Escape" && isOpen) {
      closeFAB();
    }
  });

  // Add special effects for realtime link
  $(".fab-item.realtime-item")
    .on("mouseenter", function () {
      const icon = $(this).find("i");
      icon.addClass("fa-spin");

      // Add temporary glow effect
      $(this).css("box-shadow", "0 10px 30px rgba(102, 126, 234, 0.6)");
    })
    .on("mouseleave", function () {
      const icon = $(this).find("i");
      icon.removeClass("fa-spin");

      // Remove glow effect
      $(this).css("box-shadow", "");
    });

  // Add special effects for WhatsApp item
  $(".fab-item.whatsapp-item")
    .on("mouseenter", function () {
      const icon = $(this).find("i");
      icon.addClass("fa-bounce");
    })
    .on("mouseleave", function () {
      const icon = $(this).find("i");
      icon.removeClass("fa-bounce");
    });

  // Add special effects for microsite item
  $(".fab-item.microsite-item")
    .on("mouseenter", function () {
      const icon = $(this).find("i");
      icon.addClass("fa-pulse");
    })
    .on("mouseleave", function () {
      const icon = $(this).find("i");
      icon.removeClass("fa-pulse");
    });

  // Add special effects for email item
  $(".fab-item.email-item")
    .on("mouseenter", function () {
      const icon = $(this).find("i");
      icon.addClass("fa-shake");
    })
    .on("mouseleave", function () {
      const icon = $(this).find("i");
      icon.removeClass("fa-shake");
    });

  // Remove pulse animation after first meaningful interaction
  fabMain.one("mouseenter", function () {
    $(this).removeClass("pulse");
  });

  // Track clicks on FAB items
  $(".fab-item").on("click", function (e) {
    let itemType = "other";

    // Determine item type for analytics
    if ($(this).hasClass("realtime-item")) itemType = "realtime";
    else if ($(this).hasClass("microsite-item")) itemType = "microsite";
    else if ($(this).hasClass("whatsapp-item")) itemType = "whatsapp";
    else if ($(this).hasClass("email-item")) itemType = "email";
    else if ($(this).hasClass("login-item")) itemType = "login";
    else if ($(this).hasClass("dashboard-item")) itemType = "dashboard";

    const href = $(this).attr("href");

    // Track analytics
    if (typeof gtag !== "undefined") {
      gtag("event", "fab_item_click", {
        event_category: "navigation",
        event_label: itemType,
        value: href,
      });
    }

    // Show feedback for external links
    if (href && (href.startsWith("http") || href.startsWith("mailto"))) {
      // Close FAB immediately for external links
      closeFAB();

      // Show different loading indicators based on item type
      const originalText = $(this).html();
      let loadingText =
        '<i class="fas fa-spinner fa-spin"></i> <span>Membuka...</span>';

      if (itemType === "whatsapp") {
        loadingText =
          '<i class="fab fa-whatsapp fa-spin"></i> <span>Membuka WhatsApp...</span>';
      } else if (itemType === "email") {
        loadingText =
          '<i class="fas fa-envelope fa-bounce"></i> <span>Membuka Email...</span>';
      } else if (itemType === "microsite") {
        loadingText =
          '<i class="fas fa-globe fa-pulse"></i> <span>Membuka Situs...</span>';
      }

      $(this).html(loadingText);

      setTimeout(() => {
        $(this).html(originalText);
      }, 1000);
    }
  });

  // Auto-pulse periodically to maintain attention
  let pulseInterval = setInterval(function () {
    if (!isOpen && !fabMain.is(":hover") && interactionCount < 5) {
      fabMain.addClass("pulse");
      setTimeout(() => {
        fabMain.removeClass("pulse");
      }, 5000);
    } else if (interactionCount >= 5) {
      // User is familiar with FAB, reduce auto-pulse
      clearInterval(pulseInterval);
    }
  }, 15000);

  // Add notification badge for important updates (can be triggered by server)
  window.addFABNotification = function (count = "") {
    fabMain.find(".fab-badge").remove(); // Remove existing badge
    if (count !== null && count !== "") {
      const badge = $('<span class="fab-badge">' + count + "</span>");
      fabMain.append(badge);
    }
  };

  // Remove notification when FAB is opened
  fabMain.on("click", function () {
    setTimeout(() => {
      $(".fab-badge").fadeOut(300, function () {
        $(this).remove();
      });
    }, 500);
  });

  // Accessibility improvements
  fabMain.attr({
    role: "button",
    "aria-label": "Menu akses cepat",
    "aria-expanded": "false",
    tabindex: "0",
  });

  // Update aria-expanded when state changes
  fabContainer.on("DOMSubtreeModified", function () {
    fabMain.attr("aria-expanded", isOpen ? "true" : "false");
  });

  // Keyboard navigation
  fabMain.on("keydown", function (e) {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      $(this).click();
    }
  });

  // Initialize with pulse for new users
  setTimeout(() => {
    fabMain.addClass("pulse");
  }, 1000);
}

// Defensive cleanup: if body has .modal-open but no modal is visible, remove it
// This prevents the page from being stuck non-scrollable when a modal failed to close.
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
    // silent fail to avoid breaking page scripts
    if (window.console && console.error)
      console.error("modal-open cleanup error", e);
  }
})();

// Chart and modal initialization consolidated
$(document).ready(function () {
  // Chart data (safe guards)
  window.chartData = {
    male: (window.homeStats && window.homeStats.male_count) || 0,
    female: (window.homeStats && window.homeStats.female_count) || 0,
    majors: (window.homeStats && window.homeStats.major_data) || {},
  };

  // Initialize Gender Pie Chart with delay to ensure Chart.js is loaded
  (function initChartWithRetry() {
    if (typeof Chart !== "undefined") {
      try {
        initGenderChart();
      } catch (e) {
        console.error("initGenderChart error:", e);
      }
      return;
    }
    // retry once after short delay
    setTimeout(function () {
      if (typeof Chart !== "undefined") {
        try {
          initGenderChart();
        } catch (e) {
          console.error("initGenderChart error:", e);
        }
      }
    }, 800);
  })();

  // Modal manager to centralize show/hide and attach handlers once
  var ModalManager = (function () {
    var modalEl = null;
    var initialized = false;
    // helpers to temporarily disable the FAB which may overlay the modal
    function disableFab() {
      try {
        var f = document.getElementById("fabContainer");
        if (!f) return;
        // store previous pointerEvents so we can restore
        if (!f.hasAttribute("data-prev-pointer-events"))
          f.setAttribute(
            "data-prev-pointer-events",
            f.style.pointerEvents || ""
          );
        f.style.pointerEvents = "none";
      } catch (e) {}
    }

    function restoreFab() {
      try {
        var f = document.getElementById("fabContainer");
        if (!f) return;
        var prev = f.getAttribute("data-prev-pointer-events");
        if (typeof prev !== "undefined" && prev !== null)
          f.style.pointerEvents = prev;
        else f.style.pointerEvents = "";
        f.removeAttribute("data-prev-pointer-events");
      } catch (e) {}
    }

    function getModal() {
      if (!modalEl) modalEl = document.getElementById("nisnInfoModal");
      return modalEl;
    }

    function addBackdrop() {
      if (!document.querySelectorAll(".modal-backdrop").length) {
        var bd = document.createElement("div");
        bd.className = "modal-backdrop fade show";
        document.body.appendChild(bd);
      }
    }

    function show() {
      try {
        // debug logging removed
      } catch (e) {}
      var m = getModal();
      if (!m) return;
      // prefer Bootstrap API
      try {
        if (window.bootstrap && typeof bootstrap.Modal === "function") {
          var inst = null;
          if (typeof bootstrap.Modal.getInstance === "function") {
            try {
              inst = bootstrap.Modal.getInstance(m);
            } catch (e) {
              inst = null;
            }
          }
          if (!inst) inst = new bootstrap.Modal(m);
          if (inst && typeof inst.show === "function") {
            disableFab();
            return inst.show();
          }
        }
      } catch (e) {
        // fallthrough to jQuery/dom
      }
      // jQuery fallback
      if (typeof $ === "function" && $(m).modal) {
        try {
          // jQuery modal show (no debug log)
          disableFab();
          return $(m).modal("show");
        } catch (e) {}
      }
      // DOM fallback
      m.classList.add("show");
      m.style.display = "block";
      document.body.classList.add("modal-open");
      addBackdrop();
      // ensure FAB cannot intercept pointer events while modal open
      try {
        disableFab();
      } catch (e) {}
    }

    function hide() {
      try {
        // debug logging removed
      } catch (e) {}
      var m = getModal();
      if (!m) return;
      try {
        if (window.bootstrap && typeof bootstrap.Modal === "function") {
          var inst = null;
          if (typeof bootstrap.Modal.getInstance === "function") {
            try {
              inst = bootstrap.Modal.getInstance(m);
            } catch (e) {
              inst = null;
            }
          }
          if (!inst) inst = new bootstrap.Modal(m);
          if (inst && typeof inst.hide === "function") {
            try {
              restoreFab();
            } catch (e) {}
            return inst.hide();
          }
        }
      } catch (e) {}
      if (typeof $ === "function" && $(m).modal) {
        try {
          // jQuery modal hide (no debug log)
          try {
            restoreFab();
          } catch (e) {}
          return $(m).modal("hide");
        } catch (e) {}
      }
      // DOM fallback: hide any visible modal and remove all backdrops
      try {
        document.querySelectorAll(".modal.show").forEach(function (el) {
          el.classList.remove("show");
          el.style.display = "none";
          el.setAttribute("aria-hidden", "true");
        });
        // DOM fallback hide performed (no debug log)
        document.querySelectorAll(".modal-backdrop").forEach(function (b) {
          if (b && b.parentNode) b.parentNode.removeChild(b);
        });
        document.body.classList.remove("modal-open");
        if (document.body.style && document.body.style.overflow === "hidden") {
          document.body.style.overflow = "";
        }
        try {
          m.dispatchEvent(new Event("hideFallback"));
        } catch (e) {}
        // restore FAB pointer behavior after hiding
        try {
          restoreFab();
        } catch (e) {}
        // Extra aggressive cleanup after a short delay in case some
        // other script re-creates backdrops or reinserts inline styles.
        setTimeout(function () {
          try {
            var mm = document.getElementById("nisnInfoModal");
            if (mm) {
              mm.classList.remove("show");
              mm.style.display = "none";
              mm.setAttribute("aria-hidden", "true");
              // remove any inline styles on modal dialog/content that may keep it visible
              var dialogs = mm.querySelectorAll(
                ".modal-dialog, .modal-content"
              );
              dialogs.forEach(function (d) {
                d.style.display = "";
                d.style.visibility = "";
                d.style.opacity = "";
              });
            }
            document.querySelectorAll(".modal-backdrop").forEach(function (b) {
              if (b && b.parentNode) b.parentNode.removeChild(b);
            });
            document.body.classList.remove("modal-open");
            if (
              document.body.style &&
              document.body.style.overflow === "hidden"
            )
              document.body.style.overflow = "";
          } catch (e) {
            console.error &&
              console.error("ModalManager forced cleanup error", e);
          }
        }, 50);
      } catch (e) {}
    }

    function onModalHiddenSave() {
      try {
        var cb = document.getElementById("dontShowNisnInfo");
        if (cb && cb.checked) localStorage.setItem("nisnInfoDontShow", "true");
      } catch (e) {}
    }

    function init() {
      if (initialized) return;
      initialized = true;
      var m = getModal();
      // attach delegated handlers using namespaced events when possible
      if (typeof $ === "function") {
        // clear any existing namespaced handlers once, then attach
        $(document).off(".modalNisn");
        $(document).off(".modalNisnBackdrop");
        $(document).on("click.modalNisn", "#openNisnInfo", function (e) {
          e.preventDefault();
          show();
        });
        $(document).on(
          "click.modalNisn",
          "#closeNisnInfoModal, #nisnInfoModal .btn-close, #nisnInfoModal [data-bs-dismiss='modal']",
          function (e) {
            e && e.preventDefault && e.preventDefault();
            hide();
          }
        );
        $(document).on(
          "click.modalNisnBackdrop",
          ".modal-backdrop",
          function () {
            hide();
          }
        );
        $(document).on("keydown.modalNisn", function (e) {
          if (e.key === "Escape") hide();
        });
        // Bootstrap event hook
        if (m && $(m).on) {
          $(m)
            .off("hidden.bs.modal.modalNisn")
            .on("hidden.bs.modal.modalNisn", onModalHiddenSave);
        }
      } else {
        // Plain DOM handlers (ensure single attachment)
        try {
          document.removeEventListener("click", domClickHandler);
        } catch (e) {}
        document.addEventListener("click", domClickHandler);
        document.removeEventListener("keydown", domKeyHandler);
        document.addEventListener("keydown", domKeyHandler);
        if (m) {
          try {
            m.removeEventListener("hideFallback", onModalHiddenSave);
          } catch (e) {}
          m.addEventListener("hideFallback", onModalHiddenSave);
        }
      }

      function domClickHandler(ev) {
        try {
          var t = ev.target;
          if (!t) return;
          if (t.closest && t.closest("#openNisnInfo")) {
            // DOM open handler
            ev.preventDefault();
            show();
            return;
          }
          if (
            t.closest &&
            (t.closest("#closeNisnInfoModal") ||
              t.closest("#nisnInfoModal .btn-close") ||
              (t.getAttribute && t.getAttribute("data-bs-dismiss") === "modal"))
          ) {
            // DOM close handler
            ev.preventDefault();
            hide();
            return;
          }
          if (t.classList && t.classList.contains("modal-backdrop")) {
            // DOM backdrop handler
            hide();
            return;
          }
        } catch (e) {}
      }

      function domKeyHandler(e) {
        if (e.key === "Escape") hide();
      }
    }

    return {
      init: init,
      show: show,
      hide: hide,
    };
  })();

  // initialize modal manager and auto-show logic
  try {
    // initialize modal manager
    ModalManager.init();
  } catch (e) {}

  // Show NISN info popup only when forced via URL params
  (function autoShow() {
    try {
      var forceShow = false;
      var params = new URLSearchParams(window.location.search || "");
      if (params.get && params.get("showNisn") === "1") forceShow = true;
      if (window.location && window.location.hash === "#show-nisn")
        forceShow = true;
      if (forceShow) {
        ModalManager.show();
      }
    } catch (e) {}
  })();
});

// Function to initialize Gender Pie Chart
function initGenderChart() {
  const canvas = document.getElementById("genderPieChart");
  if (!canvas) {
    return;
  }

  const ctx = canvas.getContext("2d");
  const maleCount = window.chartData.male;
  const femaleCount = window.chartData.female;
  const total = maleCount + femaleCount;

  // chart data prepared

  if (total === 0) {
    // Show "No data" message
    ctx.font = "16px Arial";
    ctx.fillStyle = "#666";
    ctx.textAlign = "center";
    ctx.fillText("Tidak ada data", canvas.width / 2, canvas.height / 2);
    return;
  }

  try {
    // Create the pie chart using Chart.js v2.9.4 syntax
    const chart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Laki-laki", "Perempuan"],
        datasets: [
          {
            data: [maleCount, femaleCount],
            backgroundColor: [
              "#4F46E5", // Blue for male
              "#EC4899", // Pink for female
            ],
            borderColor: ["#4338CA", "#DB2777"],
            borderWidth: 2,
            hoverBorderWidth: 3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
          display: false,
        },
        tooltips: {
          callbacks: {
            label: function (tooltipItem, data) {
              var dataset = data.datasets[tooltipItem.datasetIndex];
              var label = data.labels[tooltipItem.index] || "";
              var value = dataset.data[tooltipItem.index];
              var percentage = ((value / total) * 100).toFixed(1);
              return label + ": " + value + " (" + percentage + "%)";
            },
          },
        },
        animation: {
          animateRotate: true,
          animateScale: true,
          duration: 1000,
        },
        cutoutPercentage: 50,
        elements: {
          arc: {
            borderWidth: 2,
          },
        },
      },
    });

    // chart initialized
  } catch (error) {
    console.error("Error initializing gender chart:", error);
    // Fallback: show text message in canvas
    ctx.font = "14px Arial";
    ctx.fillStyle = "#ff0000";
    ctx.textAlign = "center";
    ctx.fillText("Gagal memuat chart", canvas.width / 2, canvas.height / 2);
  }
}
