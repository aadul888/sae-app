"use strict";

(function () {
  const ACTIONS = [
    { action: "getSekolah", label: "Sekolah", timeout: 60000 },
    { action: "getGtk", label: "GTK", timeout: 30000 },
    { action: "getRombonganBelajar", label: "Rombongan Belajar", timeout: 30000 },
    { action: "getPesertaDidik", label: "Peserta Didik", timeout: 180000 },
    { action: "getPengguna", label: "Pengguna", timeout: 30000 }
  ];
  let syncInProgress = false;
  let pollTimer = null;
  let completedActionCount = 0;
  let progressDisplayPercent = 0;
  let progressTargetPercent = 0;
  let progressAnimator = null;

  function getPayload() {
    return {
      base_url: ($("#registrasi-base-url").val() || "").trim(),
      npsn: ($("#registrasi-npsn").val() || "").trim(),
      token: ($("#registrasi-token").val() || "").trim()
    };
  }

  function parseResponse(data) {
    try {
      return typeof data === "string" ? JSON.parse(data) : data;
    } catch (error) {
      return { status: "error", message: "Response server tidak valid" };
    }
  }

  function escapeHtml(value) {
    return $("<div>").text(value || "").html();
  }

  function appRootPath() {
    const hiddenRoot = ($("#registrasi-app-root").val() || "").trim();
    if (hiddenRoot) {
      return hiddenRoot.replace(/\/+$/, "") + "/";
    }

    const pathParts = (window.location.pathname || "/").split("/").filter(Boolean);
    if (!pathParts.length) {
      return "/";
    }
    return "/" + pathParts[0] + "/";
  }

  function appUrl(segment) {
    const root = appRootPath();
    const clean = String(segment || "").replace(/^\/+/, "");
    return root + clean;
  }

  function compactText(value, maxLen) {
    const text = String(value || "").replace(/\s+/g, " ").trim();
    if (!text) {
      return "";
    }
    const limit = Math.max(24, Number(maxLen) || 120);
    return text.length > limit ? text.substring(0, limit - 1) + "..." : text;
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
      failed: read("Failed")
    };
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

  function clampPercent(value) {
    return Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
  }

  function formatDataProgress(processed, total) {
    const safeProcessed = Math.max(0, Number(processed) || 0);
    const safeTotal = Math.max(0, Number(total) || 0);
    if (safeTotal <= 0) {
      return "Memproses data: " + safeProcessed;
    }
    const percent = clampPercent((safeProcessed / safeTotal) * 100);
    return "Memproses data: " + safeProcessed + "/" + safeTotal + " (" + percent + "%)";
  }

  function applyProgressPercent(percent) {
    const $progress = $("#registrasiFloatingProgress");
    if (!$progress.length) {
      return;
    }

    const safePercent = clampPercent(percent);
    $progress[0].style.setProperty("--registrasi-progress", safePercent + "%");
    $("#registrasiProgressPercent").text(safePercent + "%");
  }

  function startProgressAnimator() {
    if (progressAnimator) {
      return;
    }

    progressAnimator = window.setInterval(function () {
      if (progressDisplayPercent === progressTargetPercent) {
        window.clearInterval(progressAnimator);
        progressAnimator = null;
        return;
      }

      progressDisplayPercent += progressTargetPercent > progressDisplayPercent ? 1 : -1;
      applyProgressPercent(progressDisplayPercent);
    }, 16);
  }

  function setProgressTarget(percent) {
    progressTargetPercent = clampPercent(percent);
    startProgressAnimator();
  }

  function computeLivePercent(current) {
    const steps = Math.max(1, ACTIONS.length);
    const completed = Math.max(0, Math.min(steps, completedActionCount));

    let withinStep = 0;
    if (current && current.status === "running") {
      if (current.total > 0) {
        withinStep = Math.max(0, Math.min(1, (Number(current.processed) || 0) / Number(current.total)));
      } else {
        withinStep = 0.08;
      }
    } else if (current && current.status === "success") {
      withinStep = 1;
    }

    return clampPercent(((completed + withinStep) / steps) * 100);
  }

  function buildRowMeta(item) {
    const parts = [];
    if (item.scope === "row") {
      parts.push(formatDataProgress(item.processed, item.total));
      if (item.updated || item.inserted || item.failed || item.skipped) {
        const stats = [
          "Updated: " + (item.updated || 0),
          "Inserted: " + (item.inserted || 0),
          "Skipped: " + (item.skipped || 0),
          "Failed: " + (item.failed || 0)
        ];
        parts.push(stats.join(", "));
      }
    } else {
      const counters = parseCounters(item.message || "");
      const hasCounters = [counters.processed, counters.updated, counters.inserted, counters.failed, counters.skipped]
        .some(function (val) { return val !== null; });

      if (hasCounters) {
        const processed = counters.processed !== null ? counters.processed : 0;
        const updated = counters.updated !== null ? counters.updated : 0;
        const inserted = counters.inserted !== null ? counters.inserted : 0;
        const skipped = counters.skipped !== null ? counters.skipped : 0;
        const failed = counters.failed !== null ? counters.failed : 0;
        parts.push("Data diproses: " + processed + " | Updated: " + updated + " | Inserted: " + inserted + " | Skipped: " + skipped + " | Failed: " + failed);
      } else if (item.message) {
        parts.push(compactText(sanitizeMessage(item.message), 84));
      }
    }

    return parts.join(" • ");
  }

  function buildStatusIcon(item) {
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

  function setFloatingProgress(percent, label, meta) {
    const $progress = $("#registrasiFloatingProgress");
    if (!$progress.length) {
      return;
    }

    const safePercent = clampPercent(percent);
    $progress.prop("hidden", false).addClass("is-visible");
    setProgressTarget(safePercent);
    $("#registrasiProgressLabel").text(label || "Memproses...");
    $("#registrasiProgressMeta").text(meta || "");
  }

  function setStepProgressBar(percent, text) {
    const safePercent = Math.max(0, Math.min(100, Math.round(percent || 0)));
    const $wrap = $("#registrasiStepBarWrap");
    const $bar = $("#registrasiStepBar");
    const $txt = $("#registrasiStepBarText");
    if (!$wrap.length) { return; }
    $wrap.show();
    $bar.css("width", safePercent + "%").attr("aria-valuenow", safePercent).text(safePercent + "%");
    $txt.text(text || "");
  }

  function hideStepProgressBar() {
    $("#registrasiStepBarWrap").hide();
    $("#registrasiStepBar").css("width", "0%").attr("aria-valuenow", 0).text("0%");
    $("#registrasiStepBarText").text("");
  }

  function renderProgressStream(items) {
    const $stream = $("#registrasiProgressStream");
    if (!$stream.length) {
      return;
    }

    // Only show failed items — success is conveyed by ring/progress bar, not stream.
    const failedItems = (items || []).filter(function (item) {
      return item.status === "failed" || Number(item.failed) > 0;
    });

    if (!failedItems.length) {
      $stream.html('<div class="sae-floating-progress-stream-empty">Apabila ada Error akan ditampilkan disini.</div>');
      return;
    }

    const visibleItems = failedItems.slice(0, 5);
    const html = visibleItems.map(function (item) {
      const endpoint = item.endpoint_label || item.endpoint || "Proses";
      const detail = buildRowMeta(item);

      return '<div class="sae-floating-progress-item is-failed">' +
        '<div class="sae-floating-progress-item-head">' +
          buildStatusIcon(item) +
          '<strong>' + escapeHtml(endpoint) + '</strong>' +
        '</div>' +
        '<span>' + escapeHtml(item.total > 0 ? formatDataProgress(Number(item.processed) || 0, Number(item.total)) : "Gagal") + '</span>' +
        (detail ? '<em>' + escapeHtml(detail) + '</em>' : '') +
      '</div>';
    }).join('');

    $stream.html(html);
  }

  function buildRecentItemsFromStatus(snapshot) {
    const runtime = snapshot && snapshot.current_progress ? snapshot.current_progress : null;
    if (runtime && Array.isArray(runtime.items) && runtime.items.length) {
      return runtime.items;
    }

    const items = [];
    const logs = snapshot && snapshot.recent_logs ? snapshot.recent_logs : [];
    logs.forEach(function (entry) {
      items.push({
        endpoint: entry.endpoint,
        endpoint_label: ACTIONS.find(function (item) { return item.action === entry.endpoint; })?.label || entry.endpoint,
        status: entry.status,
        total_records: entry.total_records,
        message: entry.message
      });
    });

    return items;
  }

  function fetchProgressSnapshot() {
    return $.ajax({
      url: appUrl("admin/mod/sync/proses.php?action=get-status&_=" + Date.now()),
      type: "GET",
      cache: false,
      timeout: 10000
    }).then(function (data) {
      const parsed = parseResponse(data);
      return parsed.data || {};
    }).catch(function () {
      return {};
    });
  }

  function startProgressPolling() {
    if (pollTimer) {
      window.clearInterval(pollTimer);
    }

    const pump = function () {
      fetchProgressSnapshot().then(function (snapshot) {
        const items = buildRecentItemsFromStatus(snapshot);
        const current = snapshot && snapshot.current_progress ? snapshot.current_progress.current : null;
        if (current && syncInProgress) {
          const livePercent = computeLivePercent(current);
          const rowSuffix = (current.scope === "row" && current.row_label)
            ? (" — " + compactText(current.row_label, 30))
            : "";
          const currentLabel = (current.endpoint_label || current.endpoint || "Proses") + rowSuffix;
          const total = Number(current.total) || 0;
          const processed = Number(current.processed) || 0;
          const currentMeta = total > 0
            ? formatDataProgress(processed, total)
            : (sanitizeMessage(current.message || "") || "Proses sinkronisasi sedang berjalan.");
          setFloatingProgress(livePercent, currentLabel, currentMeta);
          // Update progress bar per step (0–100% dalam satu action)
          if (total > 0) {
            const stepPercent = Math.round((processed / total) * 100);
            setStepProgressBar(stepPercent, formatDataProgress(processed, total));
          }
        }
        renderProgressStream(items);
      });
    };

    pump();
    pollTimer = window.setInterval(pump, 350);
  }

  function stopProgressPolling() {
    if (pollTimer) {
      window.clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  $(document).on("click", "#btnResetSyncTables", function () {
    const $btn = $(this);
    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Menghapus...');
    $.ajax({
      url: appUrl("admin/mod/sync/proses.php?action=clear-sync-tables"),
      type: "POST",
      timeout: 15000,
      success: function (data) {
        const result = parseResponse(data);
        if (result.status === "success") {
          $btn.html('<i class="fas fa-check"></i> Berhasil, memuat ulang...');
          window.setTimeout(function () {
            window.location.reload();
          }, 1200);
        } else {
          const message = result.message || "Gagal menghapus data sinkron.";
          setFloatingProgress(0, "Gagal menghapus data", compactText(message, 120));
          $btn.prop("disabled", false).html('<i class="fas fa-trash-alt"></i> Hapus Data &amp; Ulangi');
        }
      },
      error: function (xhr, status) {
        let message = "Gagal menghapus data sinkron.";
        if (status === "timeout") {
          message = "Request timeout saat menghapus data sinkron.";
        } else if (xhr && xhr.responseText) {
          const parsed = parseResponse(xhr.responseText);
          message = parsed.message || compactText(xhr.responseText, 120);
        }
        setFloatingProgress(0, "Gagal menghapus data", compactText(message, 120));
        $btn.prop("disabled", false).html('<i class="fas fa-trash-alt"></i> Hapus Data &amp; Ulangi');
      }
    });
  });

  window.addEventListener("beforeunload", function (event) {
    if (!syncInProgress) {
      return;
    }

    event.preventDefault();
    event.returnValue = "Proses tarik data masih berjalan. Jangan tutup atau refresh halaman ini.";
    return event.returnValue;
  });

  function hideFloatingProgress() {
    const $progress = $("#registrasiFloatingProgress");
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

  function runAction(config) {
    return new Promise(function (resolve) {
      $.ajax({
        url: appUrl("admin/mod/sync/proses.php?action=" + config.action),
        type: "POST",
        timeout: config.timeout,
        success: function (data) {
          const result = parseResponse(data);
          resolve({
            ok: result.status === "success",
            label: config.label,
            message: result.message || (result.status === "success" ? "Proses selesai" : "Proses gagal")
          });
        },
        error: function (xhr, status) {
          let message = "Terjadi kesalahan jaringan";
          if (status === "timeout") {
            message = "Request timeout. Server membutuhkan waktu terlalu lama.";
          } else if (xhr && xhr.responseText) {
            try {
              const parsed = JSON.parse(xhr.responseText);
              message = parsed.message || message;
            } catch (error) {
              message = xhr.responseText.substring(0, 180);
            }
          }

          resolve({
            ok: false,
            label: config.label,
            message: message
          });
        }
      });
    });
  }

  $(document).on("submit", ".registrasi-sync-form", async function (event) {
    event.preventDefault();

    const payload = getPayload();
    if (!payload.base_url || !payload.npsn || !payload.token) {
      setFloatingProgress(0, "Lengkapi form terlebih dahulu", "Alamat Dapodik, NPSN, dan token wajib diisi.");
      renderProgressStream([]);
      window.setTimeout(hideFloatingProgress, 2200);
      return false;
    }

    const $button = $("#btnRegistrasiSync");
    const originalText = $button.html();
    syncInProgress = true;
    completedActionCount = 0;
    progressDisplayPercent = 0;
    progressTargetPercent = 0;
    applyProgressPercent(0);
    $button.prop("disabled", true).text("Memproses tarik data...");
    setFloatingProgress(0, "Menyimpan konfigurasi...", "Menyiapkan koneksi awal ke Dapodik.");
    renderProgressStream([]);
    startProgressPolling();

    $.ajax({
      url: appUrl("admin/mod/sync/proses.php?action=save-dapodik-config"),
      type: "POST",
      data: payload,
      timeout: 15000,
      success: async function (data) {
        const saved = parseResponse(data);
        if (saved.status !== "success") {
          syncInProgress = false;
          stopProgressPolling();
          hideFloatingProgress();
          setFloatingProgress(0, "Konfigurasi gagal disimpan", saved.message || "Konfigurasi Dapodik gagal disimpan.");
          renderProgressStream([]);
          window.setTimeout(hideFloatingProgress, 3200);
          $button.prop("disabled", false).html(originalText);
          return;
        }

        const results = [];
        for (let index = 0; index < ACTIONS.length; index += 1) {
          const config = ACTIONS[index];
          const basePercent = Math.round((completedActionCount / ACTIONS.length) * 100);
          setFloatingProgress(basePercent, "Memproses " + config.label + "...", "Menghubungi server Dapodik, tunggu sebentar...");
          setStepProgressBar(0, "Memproses " + config.label + ": menunggu data dari server...");

          const result = await runAction(config);
          results.push(result);
          if (result.ok) {
            completedActionCount += 1;
          }

          const donePercent = Math.round((completedActionCount / ACTIONS.length) * 100);
          const doneCounters = parseCounters(result.message || "");
          const doneMeta = result.ok
            ? (doneCounters.processed !== null ? "Selesai: " + doneCounters.processed + " data diproses" + (doneCounters.failed ? ", " + doneCounters.failed + " gagal" : "") : "Langkah " + (index + 1) + " dari " + ACTIONS.length + " selesai.")
            : compactText(sanitizeMessage(result.message || "Proses gagal."), 100);
          setFloatingProgress(donePercent, result.ok ? (config.label + " ✔") : (config.label + " ✘"), doneMeta);
          if (result.ok) {
            setStepProgressBar(100, doneMeta);
          } else {
            hideStepProgressBar();
          }
          const snapshot = await fetchProgressSnapshot();
          renderProgressStream(buildRecentItemsFromStatus(snapshot));

          if (!result.ok) {
            break;
          }
        }

        const failed = results.filter(function (item) {
          return !item.ok;
        });

        $button.prop("disabled", false).html(originalText);
        syncInProgress = false;
        stopProgressPolling();

        if (failed.length === 0) {
          hideStepProgressBar();
          setFloatingProgress(100, "Tarik data selesai", "Semua data awal berhasil ditarik. Mengalihkan ke halaman utama...");
          setTimeout(function () {
            hideFloatingProgress();
            window.location.href = appUrl("home/");
          }, 1800);
        } else {
          const failedLabels = failed.map(function (item) { return item.label; }).join(", ");
          setFloatingProgress(Math.max(20, Math.round((results.length / ACTIONS.length) * 100)), "Penarikan data dihentikan", "Ada kegagalan pada: " + failedLabels + ". Proses dihentikan. Perbaiki lalu ulangi lagi dari awal.");
          fetchProgressSnapshot().then(function (snapshot) {
            renderProgressStream(buildRecentItemsFromStatus(snapshot));
          });
          $("#registrasiResetSection").show();
        }
      },
      error: function (xhr, status) {
        let message = "Terjadi kesalahan saat menyimpan konfigurasi Dapodik.";
        if (status === "timeout") {
          message = "Penyimpanan konfigurasi timeout. Silakan coba lagi.";
        } else if (xhr && xhr.responseText) {
          try {
            const parsed = JSON.parse(xhr.responseText);
            message = parsed.message || message;
          } catch (error) {
            message = xhr.responseText.substring(0, 180);
          }
        }

        syncInProgress = false;
        stopProgressPolling();
        setFloatingProgress(0, "Terjadi kesalahan", message);
        fetchProgressSnapshot().then(function (snapshot) {
          renderProgressStream(buildRecentItemsFromStatus(snapshot));
        });
        $button.prop("disabled", false).html(originalText);
      }
    });
  });
})();