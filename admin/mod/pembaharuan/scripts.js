"use strict";
// Module: Pembaruan - check update & deploy

var deployPollTimer = null;

function getCsrfToken() {
  return window.CSRF_TOKEN || "";
}

function getCheckUrl() {
  return (
    "./mod/pembaharuan/proses.php?action=check_remote_commits&csrf=" +
    encodeURIComponent(getCsrfToken()) +
    "&_=" +
    Date.now()
  );
}

function getDeployUrl() {
  return (
    "./mod/pembaharuan/proses.php?action=deploy_start&csrf=" +
    encodeURIComponent(getCsrfToken())
  );
}

function getDeployStatusUrl() {
  return (
    "./mod/pembaharuan/proses.php?action=status&csrf=" +
    encodeURIComponent(getCsrfToken())
  );
}

function checkUpdateNow() {
  var btn = document.getElementById("btn-check-update");
  var statusArea = document.getElementById("update-status-area");
  var deployBtn = document.getElementById("btn-deploy");
  btn.disabled = true;
  btn.innerHTML =
    '<span class="spinner-border spinner-border-sm mr-1"></span> Memeriksa...';
  statusArea.innerHTML =
    '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';

  fetch(getCheckUrl())
    .then(function (r) {
      return r.json();
    })
    .then(function (d) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-search mr-1"></i> Cek Pembaruan';
      if (d.update_available) {
        statusArea.innerHTML =
          '<div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> <strong>Pembaruan tersedia!</strong> ' +
          "Lokal: <code>" +
          (d.local_version || "-") +
          "</code> &rarr; Remote: <code>" +
          (d.remote_version || "-") +
          "</code>. " +
          'Klik "Proses Update" untuk memperbarui.</div>';
        deployBtn.classList.remove("d-none");
      } else {
        var msg = d.message || "Aplikasi sudah versi terbaru.";
        statusArea.innerHTML =
          '<div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i> ' +
          msg +
          " (<code>" +
          (d.local_version || "-") +
          "</code>)." +
          "</div>";
        // Sembunyikan tombol update jika sudah terbaru
        deployBtn.classList.add("d-none");
      }
    })
    .catch(function (e) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-search mr-1"></i> Cek Pembaruan';
      statusArea.innerHTML =
        '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memeriksa: ' +
        (e.message || "koneksi error") +
        "</div>";
    });
}

function appendDeployLog(text) {
  var logPre = document.getElementById("deploy-log");
  logPre.textContent += text + "\n";
  logPre.scrollTop = logPre.scrollHeight;
}

function setDeployButtonIdle() {
  var btn = document.getElementById("btn-deploy");
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-download mr-1"></i> Proses Update';
}

function stopDeployPolling() {
  if (deployPollTimer) {
    window.clearInterval(deployPollTimer);
    deployPollTimer = null;
  }
}

function renderDeployState(job) {
  var logPre = document.getElementById("deploy-log");
  var lines = job && Array.isArray(job.log) ? job.log : [];
  logPre.textContent = lines.join("\n");
  logPre.scrollTop = logPre.scrollHeight;
}

function pollDeployStatus() {
  fetch(getDeployStatusUrl() + "&_=" + Date.now())
    .then(function (r) {
      return r.json();
    })
    .then(function (d) {
      var job = d && d.job ? d.job : null;
      if (!job) {
        throw new Error("Status update tidak tersedia.");
      }

      renderDeployState(job);

      if (job.status === "success") {
        stopDeployPolling();
        setDeployButtonIdle();
        appendDeployLog("\n✓ Update berhasil.");
        if (typeof swal === "function") {
          swal({
            title: "Pembaruan Berhasil",
            text: job.message || "Sistem berhasil diperbarui.",
            icon: "success",
          }).then(function () {
            window.location.reload();
          });
        }
        return;
      }

      if (job.status === "failed") {
        stopDeployPolling();
        setDeployButtonIdle();
        appendDeployLog("\n✗ Update gagal.");
        if (typeof swal === "function") {
          swal({
            title: "Pembaruan Gagal",
            text: job.message || "Terjadi kegagalan saat update sistem.",
            icon: "error",
          });
        }
      }
    })
    .catch(function (e) {
      stopDeployPolling();
      setDeployButtonIdle();
      appendDeployLog("Error status: " + (e.message || "koneksi error"));
    });
}

function doDeployNow() {
  var btn = document.getElementById("btn-deploy");
  var logArea = document.getElementById("deploy-log-area");
  var logPre = document.getElementById("deploy-log");
  btn.disabled = true;
  btn.innerHTML =
    '<span class="spinner-border spinner-border-sm mr-1"></span> Mengupdate...';
  logArea.style.display = "block";
  logPre.textContent = "";
  appendDeployLog("Memulai proses update...");

  fetch(getDeployUrl() + "&_=" + Date.now())
    .then(function (r) {
      return r.json();
    })
    .then(function (d) {
      if (!d.success) {
        throw new Error(d.message || "Gagal memulai proses update.");
      }

      appendDeployLog(d.message || "Proses update berjalan di background.");
      renderDeployState(d.job || {});
      stopDeployPolling();
      pollDeployStatus();
      deployPollTimer = window.setInterval(pollDeployStatus, 1500);
    })
    .catch(function (e) {
      setDeployButtonIdle();
      appendDeployLog("Error: " + (e.message || "koneksi error"));
    });
}

function copyDeployLog() {
  var text = document.getElementById("deploy-log").textContent;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text);
    return;
  }
  var ta = document.createElement("textarea");
  ta.value = text;
  document.body.appendChild(ta);
  ta.select();
  document.execCommand("copy");
  document.body.removeChild(ta);
}
