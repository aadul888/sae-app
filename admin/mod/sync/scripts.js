// Loader swal sederhana untuk proses AJAX
function loading() {
  swal({
    title: "Mohon tunggu...",
    text: "Sedang memproses data",
    icon: "info",
    buttons: false,
    closeOnClickOutside: false,
    closeOnEsc: false,
  });
}

/** Copy to Clipboard Function */
function copyToClipboard(elementId) {
  const element = document.getElementById(elementId);
  element.select();
  element.setSelectionRange(0, 99999); // For mobile devices

  try {
    document.execCommand("copy");
    swal({
      title: "Berhasil!",
      text: "Data berhasil dicopy ke clipboard",
      icon: "success",
      timer: 1500,
    });
  } catch (err) {
    // Fallback untuk browser modern
    navigator.clipboard.writeText(element.value).then(
      function () {
        swal({
          title: "Berhasil!",
          text: "Data berhasil dicopy ke clipboard",
          icon: "success",
          timer: 1500,
        });
      },
      function () {
        swal({
          title: "Gagal!",
          text: "Gagal copy data",
          icon: "error",
          timer: 2000,
        });
      },
    );
  }
}

// Fungsi loadSetting versi AJAX, sesuai kebutuhan form.php
function loadSetting(settingId) {
  $(".load-form").html(
    '<div class="text-center py-5"><div class="spinner-border text-primary"><span class="sr-only">Loading...</span></div><p class="text-muted mt-3">Memuat pengaturan...</p></div>',
  );

  // Update active tab
  $(".nav-pills .nav-link").removeClass("active");
  $(".nav-pills .nav-link")
    .eq(settingId - 7) // tab koneksi = 7
    .addClass("active");

  // Load form content
  setTimeout(function () {
    $.ajax({
      url: "mod/sync/form.php?id=" + settingId,
      type: "GET",
      success: function (response) {
        $(".load-form").html(response);

        // Setelah form dimuat, tidak ada auto-refresh untuk mencegah override warna tombol
        if (settingId == 8) {
          console.log("Halaman Tarik Data dimuat - tombol siap diklik");
        }
      },
      error: function () {
        $(".load-form").html(
          '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat pengaturan</div>',
        );
      },
    });
  }, 500);
}

/** Fungsi untuk refresh status tombol berdasarkan database */
function refreshButtonStatus() {
  $.ajax({
    url: "mod/sync/proses.php?action=get-status",
    type: "GET",
    success: function (data) {
      let res = {};
      try {
        res = typeof data === "string" ? JSON.parse(data) : data;
      } catch (e) {
        console.error("Error parsing status response:", e);
        return;
      }

      if (res.status === "success" && res.data) {
        // Update status tes koneksi
        if (res.data.connection_status) {
          var $statusBadge = $("#statusKoneksi");
          if (res.data.connection_status === "berhasil") {
            $statusBadge
              .removeClass()
              .addClass("badge badge-success px-3 py-2")
              .html('<i class="fas fa-check-circle mr-1"></i> BERHASIL');
          } else if (res.data.connection_status === "gagal") {
            $statusBadge
              .removeClass()
              .addClass("badge badge-danger px-3 py-2")
              .html('<i class="fas fa-times-circle mr-1"></i> GAGAL');
          } else {
            $statusBadge
              .removeClass()
              .addClass("badge badge-secondary px-3 py-2")
              .html('<i class="fas fa-question-circle mr-1"></i> Belum Diuji');
          }
        }

        // Update status tombol utama tarik data
        if (res.data.sync_status) {
          const $mainButton = $("#btnSyncAllData");
          if ($mainButton.length) {
            const statuses = Object.values(res.data.sync_status || {});
            const isAllSuccess =
              statuses.length > 0 &&
              statuses.every(function (s) {
                return s === "success";
              });
            if (isAllSuccess) {
              $mainButton
                .removeClass("btn-primary btn-warning btn-danger")
                .addClass("btn-success")
                .html(
                  '<i class="fas fa-check-circle mr-1"></i> Tarik Data Dapodik',
                );
            } else {
              $mainButton
                .removeClass("btn-success btn-warning btn-danger")
                .addClass("btn-primary")
                .html(
                  '<i class="fas fa-download mr-1"></i> Tarik Data Dapodik',
                );
            }
          }
        }
      }
    },
    error: function () {
      console.error("Gagal refresh status tombol");
    },
  });
}

// Auto-load form koneksi (id=7) saat halaman dibuka
document.addEventListener("DOMContentLoaded", function () {
  loadSetting(7);
});

function getDapodikConfigPayload() {
  return {
    base_url: ($("#dapodik-base-url").val() || "").trim(),
    token: ($("#dapodik-token").val() || "").trim(),
    npsn: ($("#dapodik-npsn").val() || "").trim(),
  };
}

function setDapodikStatusBadge(status) {
  var $badge = $("#statusAPI");
  if (!$badge.length) return;

  if (status === "berhasil") {
    $badge
      .removeClass()
      .addClass("badge badge-success px-3 py-2")
      .html('<i class="fas fa-check-circle mr-1"></i>BERHASIL');
  } else if (status === "gagal") {
    $badge
      .removeClass()
      .addClass("badge badge-danger px-3 py-2")
      .html('<i class="fas fa-times-circle mr-1"></i>GAGAL');
  } else {
    $badge
      .removeClass()
      .addClass("badge badge-secondary px-3 py-2")
      .html('<i class="fas fa-question-circle mr-1"></i>BELUM DIUJI');
  }
}

$(document).on("click", "#btn-test-api", function () {
  const payload = getDapodikConfigPayload();

  if (!payload.base_url || !payload.token || !payload.npsn) {
    swal({
      title: "Validasi",
      text: "URL Dapodik, token, dan NPSN wajib diisi.",
      icon: "warning",
    });
    return;
  }

  const $button = $(this);
  const originalText = $button.html();
  $button
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin mr-1"></i>Testing...');

  $.ajax({
    url: "mod/sync/proses.php?action=test-dapodik-connection",
    type: "POST",
    data: payload,
    timeout: 60000,
    success: function (resp) {
      let res = {};
      try {
        res = typeof resp === "string" ? JSON.parse(resp) : resp;
      } catch (e) {
        res = { status: "error", message: "Invalid server response" };
      }

      if (res.status === "success") {
        setDapodikStatusBadge("berhasil");
        swal({
          title: "Koneksi Berhasil",
          text: res.message || "Koneksi ke Dapodik berhasil.",
          icon: "success",
        });
      } else {
        setDapodikStatusBadge("gagal");
        swal({
          title: "Koneksi Gagal",
          text: res.message || "Gagal menghubungi Dapodik.",
          icon: "error",
        });
      }
    },
    error: function (xhr) {
      setDapodikStatusBadge("gagal");
      swal({
        title: "Error",
        text:
          "Gagal menguji koneksi Dapodik: " +
          (xhr.statusText || "Network error"),
        icon: "error",
      });
    },
    complete: function () {
      $button.prop("disabled", false).html(originalText);
    },
  });
});

$(document).on("click", "#btn-generate-key", function () {
  const payload = getDapodikConfigPayload();

  if (!payload.base_url || !payload.token || !payload.npsn) {
    swal({
      title: "Validasi",
      text: "URL Dapodik, token, dan NPSN wajib diisi.",
      icon: "warning",
    });
    return;
  }

  const $button = $(this);
  const originalText = $button.html();
  $button
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

  $.ajax({
    url: "mod/sync/proses.php?action=save-dapodik-config",
    method: "POST",
    data: payload,
    timeout: 10000,
    success: function (resp) {
      let res = {};
      try {
        res = typeof resp === "string" ? JSON.parse(resp) : resp;
      } catch (e) {
        res = { status: "error", message: "Invalid server response" };
      }

      if (res.status === "success") {
        setDapodikStatusBadge("belum");
        swal({
          title: "Berhasil",
          text: res.message || "Konfigurasi Dapodik tersimpan.",
          icon: "success",
        });
      } else {
        swal({
          title: "Gagal",
          text: res.message || "Terjadi kesalahan saat menyimpan konfigurasi.",
          icon: "error",
        });
      }
    },
    error: function (xhr) {
      swal({
        title: "Error",
        text:
          "Gagal menyimpan konfigurasi: " + (xhr.statusText || "Network error"),
        icon: "error",
      });
    },
    complete: function () {
      $button.prop("disabled", false).html(originalText);
    },
  });
});

const SYNC_ACTIONS = [
  { action: "getSekolah", label: "Sekolah" },
  { action: "getGtk", label: "GTK" },
  { action: "getRombonganBelajar", label: "Rombongan Belajar" },
  { action: "getPesertaDidik", label: "Peserta Didik" },
  { action: "getPengguna", label: "Pengguna" },
];

let syncPullInProgress = false;
let syncPollTimer = null;
let syncCompletedActionCount = 0;
let syncProgressDisplayPercent = 0;
let syncProgressTargetPercent = 0;
let syncProgressAnimator = null;
let syncStepDisplayPercent = 0;
let syncStepTargetPercent = 0;
let syncStepAnimator = null;
let syncLiveTick = 0;

function parseSyncResponse(data) {
  try {
    return typeof data === "string" ? JSON.parse(data) : data;
  } catch (e) {
    return { status: "error", message: "Invalid response from server" };
  }
}

function escapeHtml(value) {
  return $("<div>")
    .text(value || "")
    .html();
}

function sanitizeMessage(message) {
  const text = String(message || "");
  if (!text) {
    return "";
  }

  const lines = text.split(/\r?\n/);
  const cleanLines = [];
  for (let i = 0; i < lines.length; i += 1) {
    const line = lines[i].trim();
    if (!line) {
      continue;
    }
    if (/^details\s*:/i.test(line) || /^debugoutput\s*:/i.test(line)) {
      continue;
    }
    cleanLines.push(line);
  }

  return cleanLines.join(" ");
}

function compactText(value, maxLen) {
  const text = String(value || "")
    .replace(/\s+/g, " ")
    .trim();
  if (!text) {
    return "";
  }

  const limit = Math.max(24, Number(maxLen) || 120);
  return text.length > limit ? text.substring(0, limit - 1) + "..." : text;
}

function clampPercent(value) {
  return Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
}

function parseCounters(message) {
  const text = String(message || "");
  const read = function (label) {
    const match = text.match(new RegExp(label + "\\s*:\\s*(\\d+)", "i"));
    return match ? parseInt(match[1], 10) : null;
  };

  return {
    processed: read("Processed"),
    updated: read("Updated"),
    inserted: read("Inserted"),
    skipped: read("Skipped"),
    failed: read("Failed"),
  };
}

function buildSyncErrorMessage(xhr, status) {
  let errorMessage = "Terjadi kesalahan jaringan";
  if (status === "timeout") {
    errorMessage =
      "Request timeout - Server membutuhkan waktu terlalu lama. Silakan coba lagi.";
  } else if (xhr && xhr.responseText) {
    try {
      const parsed = JSON.parse(xhr.responseText);
      errorMessage = parsed.message || errorMessage;
    } catch (e) {
      errorMessage = xhr.responseText.substring(0, 180);
    }
  }

  return errorMessage + (xhr && xhr.status ? " (HTTP " + xhr.status + ")" : "");
}

function formatDataProgress(processed, total) {
  const safeProcessed = Math.max(0, Number(processed) || 0);
  const safeTotal = Math.max(0, Number(total) || 0);
  if (safeTotal <= 0) {
    return "Memproses data: " + safeProcessed;
  }

  const percent = clampPercent((safeProcessed / safeTotal) * 100);
  return (
    "Memproses data: " + safeProcessed + "/" + safeTotal + " (" + percent + "%)"
  );
}

function applySyncProgressPercent(percent) {
  const $progress = $("#syncFloatingProgress");
  if (!$progress.length) {
    return;
  }

  const safePercent = clampPercent(percent);
  $progress[0].style.setProperty("--registrasi-progress", safePercent + "%");
  $("#syncProgressPercent").text(safePercent + "%");
}

function startSyncProgressAnimator() {
  if (syncProgressAnimator) {
    return;
  }

  syncProgressAnimator = window.setInterval(function () {
    if (syncProgressDisplayPercent === syncProgressTargetPercent) {
      window.clearInterval(syncProgressAnimator);
      syncProgressAnimator = null;
      return;
    }

    syncProgressDisplayPercent +=
      syncProgressTargetPercent > syncProgressDisplayPercent ? 1 : -1;
    applySyncProgressPercent(syncProgressDisplayPercent);
  }, 16);
}

function setSyncProgressTarget(percent) {
  syncProgressTargetPercent = clampPercent(percent);
  startSyncProgressAnimator();
}

function setSyncFloatingProgress(percent, label, meta) {
  const $progress = $("#syncFloatingProgress");
  if (!$progress.length) {
    return;
  }

  $progress.prop("hidden", false).addClass("is-visible");
  setSyncProgressTarget(percent);
  $("#syncProgressLabel").text(label || "Memproses...");
  $("#syncProgressMeta").text(meta || "");
}

function hideSyncFloatingProgress() {
  const $progress = $("#syncFloatingProgress");
  if (!$progress.length) {
    return;
  }

  $progress.removeClass("is-visible");
  window.setTimeout(function () {
    if (!$progress.hasClass("is-visible")) {
      $progress.prop("hidden", true);
    }
  }, 220);
}

function setSyncStepProgressBar(percent, text) {
  const $wrap = $("#syncStepBarWrap");
  if (!$wrap.length) {
    return;
  }

  $wrap.show();
  const safePercent = clampPercent(percent);
  syncStepTargetPercent = safePercent;
  startSyncStepAnimator();
  $("#syncStepBarText").text("");
}

function applySyncStepBarPercent(percent) {
  const safePercent = clampPercent(percent);
  $("#syncStepBar")
    .css("width", safePercent + "%")
    .attr("aria-valuenow", safePercent)
    .text(safePercent + "%");
}

function startSyncStepAnimator() {
  if (syncStepAnimator) {
    return;
  }

  syncStepAnimator = window.setInterval(function () {
    if (syncStepDisplayPercent === syncStepTargetPercent) {
      window.clearInterval(syncStepAnimator);
      syncStepAnimator = null;
      return;
    }

    syncStepDisplayPercent +=
      syncStepTargetPercent > syncStepDisplayPercent ? 1 : -1;
    applySyncStepBarPercent(syncStepDisplayPercent);
  }, 16);
}

function hideSyncStepProgressBar() {
  $("#syncStepBarWrap").hide();
  syncStepDisplayPercent = 0;
  syncStepTargetPercent = 0;
  if (syncStepAnimator) {
    window.clearInterval(syncStepAnimator);
    syncStepAnimator = null;
  }
  applySyncStepBarPercent(0);
  $("#syncStepBarText").text("");
}

function buildSyncStatusIcon(item) {
  if (item.status === "running") {
    return '<i class="fas fa-spinner fa-spin sae-progress-icon"></i>';
  }
  if (item.status === "success") {
    return '<i class="fas fa-check-circle sae-progress-icon"></i>';
  }
  if (item.status === "failed") {
    return '<i class="fas fa-times-circle sae-progress-icon"></i>';
  }
  return '<i class="fas fa-circle sae-progress-icon"></i>';
}

function buildSyncRowMeta(item) {
  if (item.scope === "row") {
    if (item.row_label) {
      return "Sedang menarik: " + compactText(item.row_label, 48);
    }
    return "Sedang menarik data...";
  }

  const counters = parseCounters(item.message || "");
  const hasCounters = [
    counters.processed,
    counters.updated,
    counters.inserted,
    counters.skipped,
    counters.failed,
  ].some(function (value) {
    return value !== null;
  });

  if (hasCounters) {
    return (
      "Processed: " +
      (counters.processed !== null ? counters.processed : 0) +
      " | Updated: " +
      (counters.updated !== null ? counters.updated : 0) +
      " | Inserted: " +
      (counters.inserted !== null ? counters.inserted : 0) +
      " | Skipped: " +
      (counters.skipped !== null ? counters.skipped : 0) +
      " | Failed: " +
      (counters.failed !== null ? counters.failed : 0)
    );
  }

  return compactText(sanitizeMessage(item.message || ""), 96);
}

function buildPendingSyncItems() {
  const nextAction = SYNC_ACTIONS[syncCompletedActionCount] || SYNC_ACTIONS[0];
  return [
    {
      endpoint: nextAction.action,
      endpoint_label: nextAction.label,
      status: "running",
      scope: "table",
      processed: 0,
      total: 0,
      message: "Menyiapkan proses penarikan data...",
    },
  ];
}

function renderSyncProgressStream(items, current) {
  const $stream = $("#syncProgressStream");
  if (!$stream.length) {
    return;
  }

  const history = Array.isArray(items) ? items : [];
  let activeItem = null;

  if (current && current.status === "running") {
    activeItem = Object.assign({}, current, { is_active: true });
  }

  if (!activeItem) {
    const runningFromHistory = history.find(function (item) {
      return item && item.status === "running";
    });
    if (runningFromHistory) {
      activeItem = Object.assign({}, runningFromHistory, { is_active: true });
    }
  }

  if (!activeItem && syncPullInProgress) {
    const nextAction =
      SYNC_ACTIONS[syncCompletedActionCount] ||
      SYNC_ACTIONS[SYNC_ACTIONS.length - 1];
    if (nextAction) {
      activeItem = {
        endpoint: nextAction.action,
        endpoint_label: nextAction.label,
        status: "running",
        scope: "table",
        processed: 0,
        total: 0,
        message: "Menunggu respons server...",
        is_active: true,
      };
    }
  }

  if (!activeItem) {
    const failedItem = history.find(function (item) {
      return item && item.status === "failed";
    });
    if (failedItem) {
      activeItem = Object.assign({}, failedItem, { is_active: true });
    }
  }

  if (!activeItem) {
    $stream.html(
      '<div class="sae-floating-progress-stream-empty">Menunggu aktivitas penarikan data dari server.</div>',
    );
    return;
  }

  const html = [activeItem]
    .map(function (item) {
      const endpoint = item.endpoint_label || item.endpoint || "Proses";
      const rowSuffix =
        item.scope === "row" && item.row_label
          ? " - " + compactText(item.row_label, 28)
          : "";
      const status = item.status || "waiting";
      const statusClass =
        status === "running"
          ? "is-running"
          : status === "success"
            ? "is-success"
            : status === "failed"
              ? "is-failed"
              : "";
      const statusText =
        status === "running"
          ? "Sedang menarik data..."
          : status === "success"
            ? "Selesai"
            : status === "failed"
              ? "Gagal"
              : "Menunggu";
      const detail = buildSyncRowMeta(item);

      return (
        '<div class="sae-floating-progress-item ' +
        statusClass +
        (item.is_active ? " is-active" : "") +
        '">' +
        '<div class="sae-floating-progress-item-head">' +
        buildSyncStatusIcon(item) +
        "<strong>" +
        escapeHtml(endpoint + rowSuffix) +
        "</strong>" +
        "</div>" +
        (detail ? "<em>" + escapeHtml(detail) + "</em>" : "") +
        "</div>"
      );
    })
    .join("");

  $stream.html(html);
}

function computeLiveSyncPercent(current) {
  const steps = Math.max(1, SYNC_ACTIONS.length);
  const completed = Math.max(0, Math.min(steps, syncCompletedActionCount));

  let withinStep = 0;
  if (current && current.status === "running") {
    if (Number(current.total) > 0) {
      withinStep = Math.max(
        0,
        Math.min(1, (Number(current.processed) || 0) / Number(current.total)),
      );
    } else {
      withinStep = 0.08;
    }
  } else if (current && current.status === "success") {
    withinStep = 1;
  }

  return clampPercent(((completed + withinStep) / steps) * 100);
}

function fetchSyncProgressSnapshot() {
  return $.ajax({
    url: "mod/sync/proses.php?action=get-status&_=" + Date.now(),
    type: "GET",
    cache: false,
    timeout: 10000,
  })
    .then(function (data) {
      const parsed = parseSyncResponse(data);
      return parsed.data || {};
    })
    .catch(function () {
      return {};
    });
}

function startSyncProgressPolling() {
  if (syncPollTimer) {
    window.clearInterval(syncPollTimer);
  }

  const pump = function () {
    fetchSyncProgressSnapshot().then(function (snapshot) {
      const runtime =
        snapshot && snapshot.current_progress
          ? snapshot.current_progress
          : null;
      const items =
        runtime && Array.isArray(runtime.items) ? runtime.items : [];
      const current = runtime && runtime.current ? runtime.current : null;

      if (current && syncPullInProgress) {
        const livePercent = computeLiveSyncPercent(current);
        const rowSuffix =
          current.scope === "row" && current.row_label
            ? " - " + compactText(current.row_label, 32)
            : "";
        const currentLabel =
          (current.endpoint_label || current.endpoint || "Proses") + rowSuffix;
        const total = Number(current.total) || 0;
        const processed = Number(current.processed) || 0;
        const meta =
          total > 0
            ? formatDataProgress(processed, total)
            : sanitizeMessage(current.message || "") ||
              "Proses sinkronisasi sedang berjalan.";

        setSyncFloatingProgress(livePercent, currentLabel, meta);
        if (total > 0) {
          syncLiveTick = 0;
          setSyncStepProgressBar(Math.round((processed / total) * 100));
        } else {
          syncLiveTick = (syncLiveTick + 1) % 40;
          const breathing =
            8 +
            Math.round((Math.sin((syncLiveTick / 40) * Math.PI * 2) + 1) * 8);
          setSyncStepProgressBar(breathing);
        }
      }

      renderSyncProgressStream(items, current);
    });
  };

  pump();
  syncPollTimer = window.setInterval(pump, 350);
}

function stopSyncProgressPolling() {
  if (syncPollTimer) {
    window.clearInterval(syncPollTimer);
    syncPollTimer = null;
  }
}

function runSyncAction(config) {
  return new Promise(function (resolve) {
    $.ajax({
      url: "mod/sync/proses.php?action=" + config.action,
      type: "POST",
      success: function (data) {
        const result = parseSyncResponse(data);
        const ok = result.status === "success" || result.status === "info";
        resolve({
          ok: ok,
          label: config.label,
          action: config.action,
          message: result.message || (ok ? "Proses selesai" : "Proses gagal"),
        });
      },
      error: function (xhr, status) {
        resolve({
          ok: false,
          label: config.label,
          action: config.action,
          message: buildSyncErrorMessage(xhr, status),
        });
      },
    });
  });
}

$(document).on("click", "#btnSyncAllData", async function () {
  if (syncPullInProgress) {
    return;
  }

  const $button = $(this);
  const originalHtml = $button.html();

  syncPullInProgress = true;
  syncCompletedActionCount = 0;
  syncProgressDisplayPercent = 0;
  syncProgressTargetPercent = 0;
  syncStepDisplayPercent = 0;
  syncStepTargetPercent = 0;
  applySyncProgressPercent(0);
  applySyncStepBarPercent(0);

  $button
    .prop("disabled", true)
    .removeClass("btn-primary btn-success btn-danger")
    .addClass("btn-warning")
    .html('<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...');
  setSyncFloatingProgress(
    0,
    "Menyiapkan penarikan data...",
    "Sinkronisasi semua endpoint Dapodik sedang dimulai.",
  );
  renderSyncProgressStream(buildPendingSyncItems(), null);
  startSyncProgressPolling();

  const results = [];
  for (let index = 0; index < SYNC_ACTIONS.length; index += 1) {
    const actionConfig = SYNC_ACTIONS[index];
    const basePercent = Math.round(
      (syncCompletedActionCount / SYNC_ACTIONS.length) * 100,
    );
    setSyncFloatingProgress(
      basePercent,
      "Memproses " + actionConfig.label + "...",
      "Menghubungi server Dapodik, mohon tunggu...",
    );
    setSyncStepProgressBar(0);

    const result = await runSyncAction(actionConfig);
    results.push(result);
    if (result.ok) {
      syncCompletedActionCount += 1;
    }

    const donePercent = Math.round(
      (syncCompletedActionCount / SYNC_ACTIONS.length) * 100,
    );
    const counters = parseCounters(result.message || "");
    const doneMeta = result.ok
      ? counters.processed !== null
        ? "Selesai: " +
          counters.processed +
          " data diproses" +
          (counters.failed ? ", " + counters.failed + " gagal" : "")
        : "Langkah " +
          (index + 1) +
          " dari " +
          SYNC_ACTIONS.length +
          " selesai."
      : compactText(sanitizeMessage(result.message || "Proses gagal."), 100);
    setSyncFloatingProgress(
      donePercent,
      result.ok
        ? actionConfig.label + " selesai"
        : actionConfig.label + " gagal",
      doneMeta,
    );

    if (result.ok) {
      setSyncStepProgressBar(100, doneMeta);
    } else {
      hideSyncStepProgressBar();
      break;
    }
  }

  const failed = results.filter(function (item) {
    return !item.ok;
  });

  syncPullInProgress = false;
  stopSyncProgressPolling();
  $button.prop("disabled", false).removeClass("btn-warning");

  if (failed.length === 0) {
    hideSyncStepProgressBar();
    $button
      .addClass("btn-success")
      .html('<i class="fas fa-check-circle mr-1"></i> Tarik Data Dapodik');
    setSyncFloatingProgress(
      100,
      "Tarik data selesai",
      "Semua data terbaru dari Dapodik berhasil ditarik.",
    );

    swal({
      title: "Semua Data Berhasil Ditarik",
      text: "Sinkronisasi data terbaru Dapodik selesai untuk semua endpoint.",
      icon: "success",
      timer: 2500,
    });

    window.setTimeout(function () {
      hideSyncFloatingProgress();
      loadSetting(8);
    }, 1200);
  } else {
    const failedLabels = failed
      .map(function (item) {
        return item.label;
      })
      .join(", ");
    $button
      .addClass("btn-danger")
      .html(
        '<i class="fas fa-exclamation-circle mr-1"></i> Tarik Data Dapodik',
      );
    setSyncFloatingProgress(
      Math.max(20, Math.round((results.length / SYNC_ACTIONS.length) * 100)),
      "Penarikan data dihentikan",
      "Ada kegagalan pada: " +
        failedLabels +
        ". Silakan periksa log lalu coba lagi.",
    );
    fetchSyncProgressSnapshot().then(function (snapshot) {
      const runtime =
        snapshot && snapshot.current_progress
          ? snapshot.current_progress
          : null;
      renderSyncProgressStream(
        runtime && Array.isArray(runtime.items) ? runtime.items : [],
        runtime && runtime.current ? runtime.current : null,
      );
    });

    swal({
      title: "Tarik Data Gagal",
      text: failed[0].message || "Terjadi kesalahan saat menarik data Dapodik.",
      icon: "error",
    });
  }

  if (!failed.length) {
    $button.html('<i class="fas fa-download mr-1"></i> Tarik Data Dapodik');
  }
});

window.addEventListener("beforeunload", function (event) {
  if (!syncPullInProgress) {
    return;
  }

  event.preventDefault();
  event.returnValue =
    "Proses tarik data masih berjalan. Jangan tutup atau refresh halaman ini.";
  return event.returnValue;
});

/** Kirim Data SAE -> PKL */
function getPklConfigPayload() {
  return {
    pkl_base_url: ($("#pkl-base-url").val() || "").trim(),
    api_token: ($("#pkl-api-token").val() || "").trim(),
  };
}

$(document).on("click", "#btn-save-pkl-config", function () {
  const payload = getPklConfigPayload();
  if (!payload.pkl_base_url || !payload.api_token) {
    swal({
      title: "Validasi",
      text: "URL API PKL dan Token API wajib diisi.",
      icon: "warning",
    });
    return;
  }

  const $btn = $(this);
  const oldHtml = $btn.html();
  $btn
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

  $.ajax({
    url: "mod/sync/proses.php?action=save-pkl-config",
    type: "POST",
    data: payload,
    success: function (resp) {
      let res = {};
      try {
        res = typeof resp === "string" ? JSON.parse(resp) : resp;
      } catch (e) {
        res = { status: "error", message: "Invalid server response" };
      }
      swal({
        title: res.status === "success" ? "Berhasil" : "Gagal",
        text: res.message || "",
        icon: res.status === "success" ? "success" : "error",
      });
    },
    error: function () {
      swal({
        title: "Error",
        text: "Gagal menyimpan konfigurasi PKL.",
        icon: "error",
      });
    },
    complete: function () {
      $btn.prop("disabled", false).html(oldHtml);
    },
  });
});

$(document).on("click", "#btn-test-pkl-config", function () {
  const payload = getPklConfigPayload();
  if (!payload.pkl_base_url || !payload.api_token) {
    swal({
      title: "Validasi",
      text: "URL API PKL dan Token API wajib diisi.",
      icon: "warning",
    });
    return;
  }

  const $btn = $(this);
  const oldHtml = $btn.html();
  $btn
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin mr-1"></i>Testing...');

  $.ajax({
    url: "mod/sync/proses.php?action=test-pkl-config",
    type: "POST",
    data: payload,
    timeout: 30000,
    success: function (resp) {
      let res = {};
      try {
        res = typeof resp === "string" ? JSON.parse(resp) : resp;
      } catch (e) {
        res = { status: "error", message: "Invalid server response" };
      }
      swal({
        title: res.status === "success" ? "Koneksi Berhasil" : "Koneksi Gagal",
        text: res.message || "",
        icon: res.status === "success" ? "success" : "error",
      });
    },
    error: function () {
      swal({
        title: "Error",
        text: "Gagal menghubungi endpoint PKL.",
        icon: "error",
      });
    },
    complete: function () {
      $btn.prop("disabled", false).html(oldHtml);
    },
  });
});

function sendDataToPkl(actionName, buttonSelector, successTitle) {
  const $btn = $(buttonSelector);
  const oldHtml = $btn.html();
  $btn
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin mr-1"></i>Proses...');

  $.ajax({
    url: "mod/sync/proses.php?action=" + actionName,
    type: "POST",
    timeout: 180000,
    success: function (resp) {
      let res = {};
      try {
        res = typeof resp === "string" ? JSON.parse(resp) : resp;
      } catch (e) {
        res = { status: "error", message: "Invalid server response" };
      }
      swal({
        title: res.status === "success" ? successTitle : "Kirim Gagal",
        text: res.message || "",
        icon: res.status === "success" ? "success" : "error",
      });
    },
    error: function (xhr, status) {
      let msg = "Terjadi kesalahan jaringan.";
      if (status === "timeout") {
        msg = "Request timeout saat mengirim data ke PKL.";
      }
      swal({ title: "Error", text: msg, icon: "error" });
    },
    complete: function () {
      $btn.prop("disabled", false).html(oldHtml);
    },
  });
}

$(document).on("click", "#btn-send-admin-pkl", function () {
  sendDataToPkl(
    "send-pkl-admin",
    "#btn-send-admin-pkl",
    "Kirim Admin Berhasil",
  );
});

$(document).on("click", "#btn-send-user12-pkl", function () {
  sendDataToPkl(
    "send-pkl-user12",
    "#btn-send-user12-pkl",
    "Kirim User Tingkat 12 Berhasil",
  );
});
