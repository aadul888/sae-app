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

  // Strip edit param from URL so form doesn't show stale edit view
  var url = new URL(window.location.href);
  url.searchParams.delete("edit");
  history.replaceState(null, "", url.toString());

  var formContainer = document.querySelector(".load-form");
  if (!formContainer) return;

  formContainer.innerHTML =
    '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><p class="text-muted mt-3">Memuat...</p></div>';

  setTimeout(function () {
    var params = new URLSearchParams(window.location.search);
    var editId = params.get("edit") || "";
    fetch(
      "./mod/lisensi/form.php?id=" +
        id +
        "&page=" +
        (params.get("page") || "") +
        (editId ? "&edit=" + editId : ""),
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

// Auto-load tab from URL param or default to 1
document.addEventListener("DOMContentLoaded", function () {
  var params = new URLSearchParams(window.location.search);
  var tabId = parseInt(params.get("tab")) || 1;
  loadTab(tabId);
  doCheckin();
});

function doCheckin() {
  fetch("./mod/lisensi/proses.php?action=checkin")
    .then(function (r) {
      return r.json();
    })
    .then(function (d) {
      // Simpan status update untuk digunakan tab Pembaruan
      if (d.update_available) {
        window.__updateAvailable = true;
        window.__latestVersion = d.latest_version || "";
      }
    })
    .catch(function () {});
}

// checkUpdate & doDeploy dihapus — pembaruan hanya lewat modul Pembaharuan

// ============================================================
//  Auto-load tab from URL param
// ============================================================
document.addEventListener("DOMContentLoaded", function () {
  var params = new URLSearchParams(window.location.search);
  var tabId = parseInt(params.get("tab")) || 1;
  loadTab(tabId);
  doCheckin();
});
