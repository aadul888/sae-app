"use strict";
// Module: Lisensi & Pembaruan - client-side logic

function loadTab(id) {
  // Mark active tab
  document.querySelectorAll(".nav-pills .nav-link").forEach(function (el) {
    el.classList.remove("active");
    el.removeAttribute("aria-selected");
  });
  var activeLink = document.querySelector(
    '.nav-pills .nav-link[onclick="loadTab(' + id + ');"]',
  );
  if (activeLink) {
    activeLink.classList.add("active");
    activeLink.setAttribute("aria-selected", "true");
  }

  var formContainer = document.querySelector(".load-form");
  if (!formContainer) return;

  formContainer.innerHTML =
    '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><p class="text-muted mt-3">Memuat...</p></div>';

  setTimeout(function () {
    fetch(
      "./mod/lisensi_pembaruan/form.php?id=" +
        id +
        "&page=" +
        (new URLSearchParams(window.location.search).get("page") || ""),
    )
      .then(function (r) {
        if (!r.ok) throw new Error("HTTP " + r.status);
        return r.text();
      })
      .then(function (html) {
        formContainer.innerHTML = html;
      })
      .catch(function (e) {
        formContainer.innerHTML =
          '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memuat konten: ' +
          e.message +
          "</div>";
      });
  }, 500);
}

// Auto-load first tab on page ready
document.addEventListener("DOMContentLoaded", function () {
  loadTab(1);
  // Auto checkin on page load for telemetry
  doCheckin();
});

function doCheckin() {
  fetch("./mod/lisensi_pembaruan/proses.php?action=checkin")
    .then(function (r) {
      return r.json();
    })
    .then(function (d) {
      // If update available, show badge in sidebar or banner — handled server-side
    })
    .catch(function () {});
}

function checkUpdate(btn) {
  btn.disabled = true;
  btn.innerHTML =
    '<span class="spinner-border spinner-border-sm mr-1"></span> Memeriksa...';
  fetch("./mod/lisensi_pembaruan/proses.php?action=check_update")
    .then(function (r) {
      return r.json();
    })
    .then(function (d) {
      if (d.success && d.update_available) {
        location.reload();
      } else if (d.success) {
        swal({
          title: "Informasi",
          text: "Aplikasi sudah menggunakan versi terbaru.",
          icon: "success",
          timer: 2000,
        });
      } else {
        swal({
          title: "Gagal",
          text: d.message || "Gagal memeriksa pembaruan.",
          icon: "error",
        });
      }
    })
    .catch(function () {
      swal({
        title: "Gagal",
        text: "Gagal terhubung ke server.",
        icon: "error",
      });
    })
    .finally(function () {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-search mr-1"></i> Periksa Pembaruan';
    });
}
