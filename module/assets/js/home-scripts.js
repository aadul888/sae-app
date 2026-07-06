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
    console.warn("FAB elements not found");
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
      if (typeof gtag !== 'undefined') {
        gtag('event', 'fab_opened', {
          'event_category': 'engagement',
          'event_label': 'floating_action_button'
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
    if (isOpen && !fabContainer.is(e.target) && fabContainer.has(e.target).length === 0) {
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
  $(".fab-item.realtime-item").on("mouseenter", function () {
    const icon = $(this).find("i");
    icon.addClass("fa-spin");
    
    // Add temporary glow effect
    $(this).css("box-shadow", "0 10px 30px rgba(102, 126, 234, 0.6)");
  }).on("mouseleave", function () {
    const icon = $(this).find("i");
    icon.removeClass("fa-spin");
    
    // Remove glow effect
    $(this).css("box-shadow", "");
  });

  // Add special effects for WhatsApp item
  $(".fab-item.whatsapp-item").on("mouseenter", function () {
    const icon = $(this).find("i");
    icon.addClass("fa-bounce");
  }).on("mouseleave", function () {
    const icon = $(this).find("i");
    icon.removeClass("fa-bounce");
  });

  // Add special effects for microsite item
  $(".fab-item.microsite-item").on("mouseenter", function () {
    const icon = $(this).find("i");
    icon.addClass("fa-pulse");
  }).on("mouseleave", function () {
    const icon = $(this).find("i");
    icon.removeClass("fa-pulse");
  });

  // Add special effects for email item
  $(".fab-item.email-item").on("mouseenter", function () {
    const icon = $(this).find("i");
    icon.addClass("fa-shake");
  }).on("mouseleave", function () {
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
    if (typeof gtag !== 'undefined') {
      gtag('event', 'fab_item_click', {
        'event_category': 'navigation',
        'event_label': itemType,
        'value': href
      });
    }
    
    // Show feedback for external links
    if (href && (href.startsWith("http") || href.startsWith("mailto"))) {
      // Close FAB immediately for external links
      closeFAB();
      
      // Show different loading indicators based on item type
      const originalText = $(this).html();
      let loadingText = '<i class="fas fa-spinner fa-spin"></i> <span>Membuka...</span>';
      
      if (itemType === "whatsapp") {
        loadingText = '<i class="fab fa-whatsapp fa-spin"></i> <span>Membuka WhatsApp...</span>';
      } else if (itemType === "email") {
        loadingText = '<i class="fas fa-envelope fa-bounce"></i> <span>Membuka Email...</span>';
      } else if (itemType === "microsite") {
        loadingText = '<i class="fas fa-globe fa-pulse"></i> <span>Membuka Situs...</span>';
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
  window.addFABNotification = function(count = '') {
    fabMain.find(".fab-badge").remove(); // Remove existing badge
    if (count !== null && count !== '') {
      const badge = $('<span class="fab-badge">' + count + '</span>');
      fabMain.append(badge);
    }
  };

  // Remove notification when FAB is opened
  fabMain.on("click", function() {
    setTimeout(() => {
      $(".fab-badge").fadeOut(300, function() {
        $(this).remove();
      });
    }, 500);
  });

  // Accessibility improvements
  fabMain.attr({
    'role': 'button',
    'aria-label': 'Menu akses cepat',
    'aria-expanded': 'false',
    'tabindex': '0'
  });

  // Update aria-expanded when state changes
  fabContainer.on("DOMSubtreeModified", function() {
    fabMain.attr("aria-expanded", isOpen ? "true" : "false");
  });

  // Keyboard navigation
  fabMain.on("keydown", function(e) {
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
