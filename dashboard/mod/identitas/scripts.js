"use strict";

// Identitas Page Scripts
document.addEventListener("DOMContentLoaded", function () {
  // Initialize page animations
  initializeIdentitasAnimations();

  // Initialize form handling
  initializeConfirmationForm();

  // Initialize table interactions
  initializeTableInteractions();
});

// Page animations
function initializeIdentitasAnimations() {
  // Animate cards on load
  const cards = document.querySelectorAll(".card");
  cards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(20px)";

    setTimeout(() => {
      card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    }, index * 100);
  });

  // Animate header sections
  const headerSections = document.querySelectorAll(
    '[class*="identitas-header-section"]'
  );
  headerSections.forEach((section, index) => {
    section.style.opacity = "0";
    section.style.transform = "translateX(-20px)";

    setTimeout(() => {
      section.style.transition = "opacity 0.6s ease, transform 0.6s ease";
      section.style.opacity = "1";
      section.style.transform = "translateX(0)";
    }, 200 + index * 150);
  });
}

// Confirmation form handling
function initializeConfirmationForm() {
  const form = document.getElementById("formKonfirmasiIdentitas");
  const modal = document.getElementById("modalKonfirmasi");
  const errorDiv = document.getElementById("konfirmasiError");

  // Handle cancel buttons using event delegation for better compatibility
  if (modal) {
    // Simplified event delegation for close buttons
    modal.addEventListener("click", function (e) {
      const target = e.target;

      // Check if clicked element is a cancel/close button
      if (
        target.hasAttribute("data-bs-dismiss") ||
        target.classList.contains("btn-close") ||
        (target.type === "button" && target.textContent.trim() === "Batal")
      ) {
        e.preventDefault();
        e.stopPropagation();

        // Reset form quickly
        if (form) {
          form.reset();
        }

        // Hide error message
        if (errorDiv) {
          errorDiv.style.display = "none";
        }

        // Force close modal immediately
        modal.style.display = "none";
        modal.classList.remove("show");
        document.body.classList.remove("modal-open");
        document.body.style.paddingRight = "";
        document.body.style.overflow = "";

        // Remove backdrop
        const backdrop = document.querySelector(".modal-backdrop");
        if (backdrop) {
          backdrop.remove();
        }
      }
    });
  }

  // Handle modal events
  if (modal) {
    // Handle modal close events
    modal.addEventListener("hidden.bs.modal", function () {
      // Reset form when modal is closed
      if (form) {
        form.reset();
      }
      if (errorDiv) {
        errorDiv.style.display = "none";
      }
    });

    // Handle modal backdrop click
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        // Reset form when clicking outside
        if (form) {
          form.reset();
        }
        if (errorDiv) {
          errorDiv.style.display = "none";
        }
      }
    });

    // Handle escape key
    modal.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        if (form) {
          form.reset();
        }
        if (errorDiv) {
          errorDiv.style.display = "none";
        }
      }
    });
  }

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const konfirmasiSelect = form.querySelector('select[name="konfirmasi"]');

      if (!konfirmasiSelect.value) {
        if (errorDiv) {
          errorDiv.style.display = "block";
        }
        return;
      }

      if (errorDiv) {
        errorDiv.style.display = "none";
      }

      // Show loading state
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

      // Submit form using fetch API (faster and more reliable)
      const formData = new FormData(form);

      // Use fetch with timeout for faster response
      const timeoutPromise = new Promise((_, reject) =>
        setTimeout(() => reject(new Error("Request timeout")), 10000)
      );

      const fetchPromise = fetch("./mod/identitas/proses.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      Promise.race([fetchPromise, timeoutPromise])
        .then((response) => {
          if (!response.ok) {
            throw new Error("Network response was not ok");
          }
          return response.text();
        })
        .then((data) => {
          // Reset button first
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;

          if (data.trim() === "success") {
            // Update button state
            const konfirmasiValue = konfirmasiSelect.value;
            const btnKonfirmasi = document.getElementById("btnKonfirmasiData");

            if (btnKonfirmasi) {
              btnKonfirmasi.disabled = true;
              btnKonfirmasi.className = `btn w-100 ${
                konfirmasiValue === "Sesuai" ? "btn-success" : "btn-danger"
              }`;
              btnKonfirmasi.textContent = konfirmasiValue;
              btnKonfirmasi.removeAttribute("data-bs-toggle");
              btnKonfirmasi.removeAttribute("data-bs-target");
            }

            // Close modal immediately
            if (modal) {
              modal.style.display = "none";
              modal.classList.remove("show");
              document.body.classList.remove("modal-open");

              // Remove backdrop
              const backdrop = document.querySelector(".modal-backdrop");
              if (backdrop) {
                backdrop.remove();
              }
            }

            // Show success message with SweetAlert or fallback
            if (typeof swal !== "undefined") {
              swal({
                title: "Berhasil!",
                text: "Konfirmasi data berhasil disimpan!",
                icon: "success",
                timer: 2000,
                showConfirmButton: false,
              });
            } else if (typeof Swal !== "undefined") {
              Swal.fire({
                title: "Berhasil!",
                text: "Konfirmasi data berhasil disimpan!",
                icon: "success",
                timer: 2000,
                showConfirmButton: false,
              });
            } else {
              showSuccessMessage("Konfirmasi data berhasil disimpan!");
            }

            // Reload page after short delay
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          } else {
            // Show error message with SweetAlert or fallback
            if (typeof swal !== "undefined") {
              swal({
                title: "Gagal!",
                text: data,
                icon: "error",
                timer: 3000,
                showConfirmButton: true,
              });
            } else if (typeof Swal !== "undefined") {
              Swal.fire({
                title: "Gagal!",
                text: data,
                icon: "error",
                timer: 3000,
                showConfirmButton: true,
              });
            } else {
              alert("Error: " + data);
            }
          }
        })
        .catch((error) => {
          // Reset button
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;

          let errorMessage = "";
          if (error.message === "Request timeout") {
            errorMessage = "Request timeout. Silakan coba lagi.";
          } else {
            errorMessage = "Terjadi kesalahan: " + error.message;
          }

          // Show error with SweetAlert or fallback
          if (typeof swal !== "undefined") {
            swal({
              title: "Error!",
              text: errorMessage,
              icon: "error",
              timer: 3000,
              showConfirmButton: true,
            });
          } else if (typeof Swal !== "undefined") {
            Swal.fire({
              title: "Error!",
              text: errorMessage,
              icon: "error",
              timer: 3000,
              showConfirmButton: true,
            });
          } else {
            alert(errorMessage);
          }
        });
    });
  }

  // Reset error when selection changes
  const konfirmasiSelect = form?.querySelector('select[name="konfirmasi"]');
  if (konfirmasiSelect && errorDiv) {
    konfirmasiSelect.addEventListener("change", function () {
      if (this.value) {
        errorDiv.style.display = "none";
      }
    });
  }
}

// Table interactions
function initializeTableInteractions() {
  const tableRows = document.querySelectorAll(".identitas-table tbody tr");

  tableRows.forEach((row) => {
    row.addEventListener("mouseenter", function () {
      this.style.transform = "scale(1.002)";
      this.style.boxShadow = "0 4px 15px rgba(0, 123, 255, 0.1)";
    });

    row.addEventListener("mouseleave", function () {
      this.style.transform = "scale(1)";
      this.style.boxShadow = "none";
    });
  });
}

// Utility functions
function showSuccessMessage(message) {
  // Create success alert
  const alertDiv = document.createElement("div");
  alertDiv.className =
    "alert alert-success alert-dismissible fade show position-fixed";
  alertDiv.style.cssText =
    "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
  alertDiv.innerHTML = `
    <i class="fas fa-check-circle me-2"></i>${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;

  document.body.appendChild(alertDiv);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.remove();
    }
  }, 5000);
}

// Export functions for global access
window.showSuccessMessage = showSuccessMessage;

// Initialize error div display on page load
document.addEventListener("DOMContentLoaded", function () {
  const errorDiv = document.getElementById("konfirmasiError");
  if (errorDiv) {
    errorDiv.style.display = "none";
  }

  // Initialize confirmation button functionality
  const btnKonfirmasi = document.getElementById("btnKonfirmasiData");
  if (btnKonfirmasi) {
    // Update button appearance based on current status
    const konfirmasiStatus = btnKonfirmasi.getAttribute("data-konfirmasi");

    if (btnKonfirmasi.disabled) {
      // Button is already confirmed, just update the appearance
      if (konfirmasiStatus === "Sesuai") {
        btnKonfirmasi.className = "btn btn-success w-100";
        btnKonfirmasi.textContent = "Sesuai";
      } else if (konfirmasiStatus === "Belum Sesuai") {
        btnKonfirmasi.className = "btn btn-danger w-100";
        btnKonfirmasi.textContent = "Belum Sesuai";
      }
    } else {
      // Button is active, add click event listener
      btnKonfirmasi.addEventListener("click", function () {
        const modal = document.getElementById("modalKonfirmasi");
        if (modal) {
          // Use Bootstrap modal
          if (typeof bootstrap !== "undefined") {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
          } else if (typeof $ !== "undefined") {
            $(modal).modal("show");
          }
        }
      });
    }
  }

  // Global handler for all cancel/close buttons in any modal
  document.addEventListener("click", function (e) {
    const target = e.target;

    // Check if clicked element is inside a modal and is a cancel/close button
    if (
      target.closest(".modal") &&
      (target.hasAttribute("data-bs-dismiss") ||
        target.classList.contains("btn-close") ||
        (target.classList.contains("btn-secondary") &&
          target.textContent.trim() === "Batal"))
    ) {
      const modal = target.closest(".modal");
      const form = modal?.querySelector("form");
      const errorDiv = modal?.querySelector("#konfirmasiError");

      // Reset form
      if (form) {
        form.reset();
      }

      // Hide error message
      if (errorDiv) {
        errorDiv.style.display = "none";
      }
    }
  });
});
