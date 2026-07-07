"use strict";

(function () {
  function hasSwal() {
    return (
      typeof window.Swal === "function" ||
      (window.Swal && typeof window.Swal.fire === "function") ||
      typeof window.swal === "function"
    );
  }

  document.addEventListener("click", function (ev) {
    var btn = ev.target.closest && ev.target.closest('[data-dismiss="modal"]');
    if (!btn) return;
    var modal = btn.closest && btn.closest(".modal");
    if (!modal) return;
    ev.preventDefault();
    try {
      if (window.jQuery && window.jQuery(modal).modal) {
        window.jQuery(modal).modal("hide");
        return;
      }
    } catch (e) {}
    try {
      modal.classList.remove("show");
      modal.style.display = "none";
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
      var back = document.querySelector(".modal-backdrop");
      if (back && back.parentNode) back.parentNode.removeChild(back);
    } catch (e) {}
  });

  function showAlert(icon, title, text) {
    if (typeof window.swal === "function") {
      try {
        window.swal({ title: title || "", text: text || "", icon: icon || "" });
        return Promise.resolve();
      } catch (e) {
        console.warn(
          "swal() invocation failed, falling back to Swal.fire if available",
          e
        );
      }
    }

    if (window.Swal && typeof window.Swal.fire === "function") {
      return window.Swal.fire({
        icon: icon,
        title: title || "",
        text: text || "",
      });
    }

    console.warn(
      "No swal/Swal available, falling back to alert()",
      title,
      text
    );
    alert((title ? title + "\n" : "") + (text || ""));
    return Promise.resolve();
  }

  function formatErrors(errors) {
    if (Array.isArray(errors)) return errors.join("\n");
    return errors || "";
  }

  function findIzinForm() {
    var forms = document.getElementsByTagName("form");
    for (var i = 0; i < forms.length; i++) {
      var f = forms[i];
      if (
        f.getAttribute("action") &&
        f.getAttribute("action").indexOf("proses.php") !== -1
      )
        return f;
    }
    return (
      document.querySelector(".izin-page-container form") ||
      document.querySelector("form")
    );
  }

  // Client-side validation
  function validateFormData(data) {
    var errors = [];
    var jenis = (data.get("jenis_izin") || "").trim();
    var tanggal = (data.get("tanggal") || "").trim();
    var keterangan = (data.get("keterangan") || "").trim();

    if (!jenis) errors.push("Jenis izin wajib diisi.");
    if (jenis && jenis.length > 100)
      errors.push("Jenis izin maksimal 100 karakter.");

    if (!tanggal) {
      errors.push("Tanggal e-izin wajib diisi.");
    } else {
      var m = tanggal.match(/^\d{4}-\d{2}-\d{2}$/);
      if (!m) {
        errors.push("Format tanggal tidak valid (gunakan YYYY-MM-DD).");
      } else {
        var d1 = new Date(tanggal + "T00:00:00");
        var now = new Date();
        now.setHours(0, 0, 0, 0);
        if (d1 < now)
          errors.push("Tanggal e-izin tidak boleh tanggal yang sudah lewat.");
      }
    }
    if (keterangan.length > 500)
      errors.push("Keterangan maksimal 500 karakter.");
    return errors;
  }

  function postForm(url, formData) {
    return fetch(url, {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
    }).then(function (res) {
      return res.text().then(function (txt) {
        try {
          return JSON.parse(txt);
        } catch (e) {
          return txt;
        }
      });
    });
  }

  // --- Main: form submit handling ---
  function initForm() {
    var form = findIzinForm();
    if (!form) return;

    var submitBtn =
      form.querySelector(
        'button[type="submit"], button[type="button"].btn-primary'
      ) || null;

    form.addEventListener("submit", function (ev) {
      if (submitBtn && submitBtn.disabled) {
        return;
      }
      ev.preventDefault();

      var fd = new FormData(form);
      var errs = validateFormData(fd);
      if (errs.length) {
        showAlert("error", "Validasi Gagal", formatErrors(errs));
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
      }

      var action = form.getAttribute("action") || window.location.href;

      postForm(action, fd)
        .then(function (resp) {
          if (typeof resp === "object") {
            if (resp.success) {
              showAlert(
                "success",
                "Berhasil",
                resp.message || "Permohonan izin berhasil diajukan."
              ).then(function () {
                setTimeout(function () {
                  window.location.href =
                    window.location.pathname +
                    window.location.search.replace(/(\?|&)mod=[^&]*/, "") +
                    "?mod=e-izin";
                }, 2500);
              });
            } else {
              showAlert(
                "error",
                "Gagal",
                resp.message || "Terjadi kesalahan saat mengajukan izin."
              );
            }
          } else {
            var txt = String(resp || "").trim();
            if (txt) {
              showAlert("info", "Respons Server", txt);
            } else {
              showAlert("error", "Gagal", "Respons tidak dapat diproses.");
            }
          }
        })
        .catch(function (err) {
          showAlert("error", "Network Error", err.message || String(err));
        })
        .finally(function () {
          if (submitBtn) {
            submitBtn.disabled = false;
          }
        });
    });
  }

  // --- QR Modal and approve link handling ---
  function initQrModal() {
    function closeQrModal() {
      try {
        if (qrAutoCloseTimer) {
          clearTimeout(qrAutoCloseTimer);
          qrAutoCloseTimer = null;
        }
      } catch (e) {}
      try {
        clearQrCountdown();
      } catch (e) {}
      try {
        stopPolling && stopPolling();
      } catch (e) {}
      try {
        if (window.jQuery && window.jQuery("#modal-qrcode").modal) {
          try {
            window.jQuery("#modal-qrcode").modal("hide");
          } catch (e) {}
        }
      } catch (e) {}
      try {
        var m = document.getElementById("modal-qrcode");
        if (m) {
          m.style.display = "none";
          m.classList.remove("show");
          m.setAttribute("aria-hidden", "true");
        }
      } catch (e) {}
      try {
        document.querySelectorAll(".modal-backdrop").forEach(function (b) {
          b.parentNode && b.parentNode.removeChild(b);
        });
      } catch (e) {}
      try {
        document.body.classList.remove("modal-open");
      } catch (e) {}
    }
    // Polling helpers (detect DB status changes)
    var pollTimers = {};
    var qrAutoCloseTimer = null;
    var qrCountdownInterval = null;
    var pollState = {}; // stores per-id backoff/delay and paused state
    var lastStatus = {};
    var POLL_BASE = 1400;
    var POLL_MAX = 10000;
    var POLL_ERROR_BACKOFF = 2;

    // helper: start/clear countdown display in modal
    function startQrCountdown(seconds) {
      try {
        clearQrCountdown();
      } catch (e) {}
      var secs = parseInt(seconds, 10) || 10;
      try {
        var body = document.querySelector("#modal-qrcode .modal-body");
        if (body) {
          var existing = document.getElementById("qr-countdown");
          if (!existing) {
            existing = document.createElement("div");
            existing.id = "qr-countdown";
            existing.style.marginTop = "10px";
            existing.style.fontSize = "0.95rem";
            existing.style.color = "#6c757d";
            existing.setAttribute("aria-live", "polite");
            body.appendChild(existing);
          }
          existing.textContent = "Tutup otomatis dalam " + secs + " detik";
          qrCountdownInterval = setInterval(function () {
            secs -= 1;
            if (secs <= 0) {
              try {
                existing.textContent = "Menutup...";
              } catch (e) {}
              clearQrCountdown();
              return;
            }
            try {
              existing.textContent = "Tutup otomatis dalam " + secs + " detik";
            } catch (e) {}
          }, 1000);
        }
      } catch (e) {}
    }

    function clearQrCountdown() {
      try {
        if (qrCountdownInterval) {
          clearInterval(qrCountdownInterval);
          qrCountdownInterval = null;
        }
      } catch (e) {}
      try {
        var el = document.getElementById("qr-countdown");
        if (el && el.parentNode) el.parentNode.removeChild(el);
      } catch (e) {}
    }

    // Pause/resume polling when page is hidden to reduce server load
    document.addEventListener("visibilitychange", function () {
      try {
        if (document.hidden) {
          Object.keys(pollState).forEach(function (k) {
            pollState[k].paused = true;
          });
        } else {
          Object.keys(pollState).forEach(function (k) {
            if (pollState[k].paused) {
              pollState[k].paused = false;
              try {
                // immediately trigger a poll when tab becomes visible
                if (typeof pollState[k].runner === "function")
                  pollState[k].runner();
              } catch (e) {}
            }
          });
        }
      } catch (e) {}
    });

    function startPolling(id, role) {
      try {
        stopPolling(id);
      } catch (e) {}
      if (!id) return;
      lastStatus[id] = lastStatus[id] || {
        admin: null,
        wali: null,
        version: null,
        firstRun: true,
      };
      pollState[id] = pollState[id] || {
        delay: POLL_BASE,
        paused: false,
        runner: null,
      };

      // recursive polling loop (long-poll style but short delays) — more robust than setInterval
      (function doPoll() {
        // expose runner so visibility handler can invoke it
        try {
          pollState[id].runner = doPoll;
        } catch (e) {}

        // If page hidden, mark as paused and bail — visibility handler will resume
        if (document.hidden) {
          try {
            pollState[id].paused = true;
          } catch (e) {}
          return;
        }

        var fetchOpts = {
          credentials: "same-origin",
          headers: { "X-Requested-With": "XMLHttpRequest" },
        };
        fetch(
          "mod/e-izin/e-izin.php?ajax_check_status=1&id=" +
            encodeURIComponent(id),
          fetchOpts
        )
          .then(function (r) {
            try {
              var ct =
                r.headers && r.headers.get
                  ? r.headers.get("content-type") || ""
                  : "";
              if (
                r.redirect ||
                (ct &&
                  ct.indexOf("application/json") === -1 &&
                  /^(text|application)\//.test(ct))
              ) {
                return r
                  .text()
                  .then(function (t) {
                    var body = String(t || "").trim();
                    if (
                      body.indexOf("<!DOCTYPE") === 0 ||
                      /<html[\s>]/i.test(body)
                    ) {
                      try {
                        stopPolling(id);
                      } catch (e) {}
                      try {
                        showAlert(
                          "error",
                          "Sesi Berakhir",
                          "Sesi Anda mungkin telah berakhir. Silakan login ulang."
                        );
                      } catch (e) {}
                      return { ok: false, session_missing: true };
                    }
                    return { ok: false };
                  })
                  .catch(function () {
                    return { ok: false };
                  });
              }
            } catch (e) {}
            if (!r.ok) {
              return r
                .text()
                .then(function () {
                  return { ok: false };
                })
                .catch(function () {
                  return { ok: false };
                });
            }
            return r.json().catch(function () {
              return { ok: false };
            });
          })
          .then(function (json) {
            // reset backoff on successful JSON response
            try {
              pollState[id].delay = POLL_BASE;
            } catch (e) {}

            if (json && json.session === false) {
              try {
                stopPolling(id);
              } catch (e) {}
              try {
                if (!window._eizin_session_alert_shown) {
                  window._eizin_session_alert_shown = true;
                  showAlert(
                    "error",
                    "Sesi Berakhir",
                    "Sesi Anda mungkin telah berakhir. Silakan login ulang."
                  );
                }
              } catch (e) {}
              return;
            }

            if (!json || !json.ok) {
              // schedule next poll with current delay
              try {
                clearTimeout(pollTimers[id]);
                pollTimers[id] = setTimeout(doPoll, pollState[id].delay);
              } catch (e) {}
              return;
            }

            var admin = (json.status_izin || "").toLowerCase();
            var wali = (json.status_izin_wali || "").toLowerCase();
            var version = json.version || null;

            if (lastStatus[id].firstRun) {
              lastStatus[id].firstRun = false;
              lastStatus[id].admin = admin;
              lastStatus[id].wali = wali;
              lastStatus[id].version = version;
              if (admin === "disetujui" && wali === "disetujui") {
                try {
                  stopPolling(id);
                } catch (e) {}
                var _msg =
                  "Izin Anda telah disetujui oleh Petugas & Wali Kelas.";
                try {
                  if (window.Swal && typeof window.Swal.fire === "function") {
                    window.Swal.fire({
                      icon: "success",
                      title: "Disetujui",
                      text: _msg,
                      toast: true,
                      position: "top-end",
                      showConfirmButton: false,
                      timer: 1800,
                      timerProgressBar: true,
                    });
                  } else if (typeof window.swal === "function") {
                    window.swal({
                      title: "Disetujui",
                      text: _msg,
                      icon: "success",
                      timer: 1800,
                    });
                  } else {
                    showAlert("success", "Disetujui", _msg);
                  }
                } catch (e) {
                  try {
                    showAlert("success", "Disetujui", _msg);
                  } catch (e) {}
                }
                setTimeout(function () {
                  try {
                    location.reload();
                  } catch (e) {
                    window.location.reload && window.location.reload();
                  }
                }, 1900);
                return;
              }
              if (admin === "disetujui" || wali === "disetujui") {
                try {
                  stopPolling(id);
                } catch (e) {}
                var _msg2 =
                  "Izin telah menerima persetujuan. Memuat ulang halaman.";
                try {
                  if (window.Swal && typeof window.Swal.fire === "function") {
                    window.Swal.fire({
                      icon: "info",
                      title: "Status",
                      text: _msg2,
                      toast: true,
                      position: "top-end",
                      showConfirmButton: false,
                      timer: 1800,
                      timerProgressBar: true,
                    });
                  } else if (typeof window.swal === "function") {
                    window.swal({
                      title: "Status",
                      text: _msg2,
                      icon: "info",
                      timer: 1800,
                    });
                  } else {
                    showAlert("info", "Status", _msg2);
                  }
                } catch (e) {
                  try {
                    showAlert("info", "Status", _msg2);
                  } catch (e) {}
                }
                setTimeout(function () {
                  try {
                    location.reload();
                  } catch (e) {
                    window.location.reload && window.location.reload();
                  }
                }, 1900);
                return;
              }
            } else {
              if (
                version &&
                lastStatus[id].version &&
                version !== lastStatus[id].version
              ) {
                lastStatus[id].version = version;
                lastStatus[id].admin = admin;
                lastStatus[id].wali = wali;
                notifyAndReload(
                  id,
                  "Status",
                  admin === "disetujui" && wali === "disetujui"
                    ? "disetujui"
                    : admin === "disetujui"
                    ? "disetujui"
                    : wali
                );
                return;
              }
              if (lastStatus[id].admin !== admin) {
                lastStatus[id].admin = admin;
                notifyAndReload(id, "Petugas", admin);
                return;
              }
              if (lastStatus[id].wali !== wali) {
                lastStatus[id].wali = wali;
                notifyAndReload(id, "Wali Kelas", wali);
                return;
              }
            }

            // no change -> schedule next poll
            try {
              clearTimeout(pollTimers[id]);
              pollTimers[id] = setTimeout(doPoll, pollState[id].delay);
            } catch (e) {}
          })
          .catch(function (err) {
            // on network/error increase delay (exponential backoff)
            try {
              pollState[id].delay = Math.min(
                POLL_MAX,
                Math.max(
                  POLL_BASE,
                  (pollState[id].delay || POLL_BASE) * POLL_ERROR_BACKOFF
                )
              );
              clearTimeout(pollTimers[id]);
              pollTimers[id] = setTimeout(doPoll, pollState[id].delay);
            } catch (e) {}
          });
      })();
      // Helper: check server-side konfirmasi state for a given izin id, retry a few times
      function checkServerThenReload(watchId, maxAttempts, interval) {
        maxAttempts = parseInt(maxAttempts, 10) || 5;
        interval = parseInt(interval, 10) || 700;
        if (!watchId) {
          setTimeout(function () {
            try {
              location.reload();
            } catch (e) {
              window.location.reload && window.location.reload();
            }
          }, 300);
          return;
        }
        var attempts = 0;
        (function tryOnce() {
          attempts++;
          fetch(
            "mod/e-izin/e-izin.php?ajax_check_status=1&id=" +
              encodeURIComponent(watchId),
            {
              credentials: "same-origin",
              headers: { "X-Requested-With": "XMLHttpRequest" },
            }
          )
            .then(function (r) {
              return r.json().catch(function () {
                return null;
              });
            })
            .then(function (json) {
              try {
                var state =
                  json && json.state ? String(json.state).toLowerCase() : null;
                if (state === "returned") {
                  try {
                    location.reload();
                  } catch (e) {
                    window.location.reload && window.location.reload();
                  }
                  return;
                }
                // Backward-compat: fall back to checking konfirmasi if state not provided
                var konf =
                  json && json.konfirmasi
                    ? String(json.konfirmasi).toLowerCase()
                    : "";
                if (konf === "keluar" || konf === "pulang") {
                  try {
                    location.reload();
                  } catch (e) {
                    window.location.reload && window.location.reload();
                  }
                  return;
                }
              } catch (e) {}
              if (attempts < maxAttempts) {
                setTimeout(tryOnce, interval);
              } else {
                try {
                  location.reload();
                } catch (e) {
                  window.location.reload && window.location.reload();
                }
              }
            })
            .catch(function () {
              if (attempts < maxAttempts) setTimeout(tryOnce, interval);
              else
                try {
                  location.reload();
                } catch (e) {
                  window.location.reload && window.location.reload();
                }
            });
        })();
      }
    }
    function stopPolling(id) {
      try {
      } catch (e) {}
      if (id) {
        if (pollTimers[id]) {
          clearTimeout(pollTimers[id]);
          delete pollTimers[id];
        }
        return;
      }
      Object.keys(pollTimers).forEach(function (k) {
        clearTimeout(pollTimers[k]);
        delete pollTimers[k];
      });
    }
    try {
      // expose start/stop helpers so page-level scripts can invoke polling
      if (typeof window !== "undefined") {
        window.eizin_startPolling = startPolling;
        window.eizin_stopPolling = stopPolling;
      }
    } catch (e) {}
    function notifyAndReload(id, actor, status) {
      try {
        stopPolling(id);
      } catch (e) {}
      var title = "Status berubah";
      var text = actor + " mengubah status menjadi: " + status;
      var icon = /disetujui|approved/i.test(status)
        ? "success"
        : /ditolak|rejected/i.test(status)
        ? "error"
        : "info";
      try {
        if (window.Swal && typeof window.Swal.fire === "function") {
          window.Swal.fire({
            icon: icon,
            title: title,
            text: text,
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true,
          });
          setTimeout(function () {
            try {
              location.reload();
            } catch (e) {
              window.location.reload && window.location.reload();
            }
          }, 1900);
          return;
        }
      } catch (e) {}
      if (typeof window.swal === "function") {
        try {
          window.swal({ title: title, text: text, icon: icon, timer: 1800 });
        } catch (e) {
          showAlert(icon, title, text);
        }
        setTimeout(function () {
          try {
            location.reload();
          } catch (e) {
            window.location.reload && window.location.reload();
          }
        }, 1900);
        return;
      }
      // Fallback: show blocking alert then reload after short delay
      showAlert(icon, title, text);
      setTimeout(function () {
        try {
          location.reload();
        } catch (e) {
          window.location.reload && window.location.reload();
        }
      }, 1900);
    }
    document.addEventListener("click", function (ev) {
      var btn = ev.target.closest && ev.target.closest(".btn-show-qrcode");
      if (!btn) return;
      try {
      } catch (e) {}
      ev.preventDefault();

      var id = btn.getAttribute("data-id") || btn.dataset.id;
      var role = btn.getAttribute("data-role") || btn.dataset.role || "";
      try {
        var dataAdmin =
          btn.getAttribute("data-admin") || btn.dataset.admin || "";
        var dataTugas =
          btn.getAttribute("data-tugas_tambahan") ||
          btn.dataset.tugas_tambahan ||
          btn.getAttribute("data-tugas") ||
          btn.dataset.tugas ||
          "";
        if (
          !role &&
          dataAdmin === "2" &&
          dataTugas &&
          String(dataTugas).indexOf("7") !== -1
        ) {
          role = "security";
        }
        if (
          role === "admin" &&
          dataAdmin === "2" &&
          dataTugas &&
          String(dataTugas).indexOf("7") !== -1
        ) {
          role = "security";
        }
      } catch (e) {}
      if (!id)
        return showAlert(
          "error",
          "ID Tidak Valid",
          "ID permission tidak diberikan."
        );

      var img = document.getElementById("img-qrcode");
      var hiddenInput = document.getElementById("qr-approve-link");
      var note = document.getElementById("qr-note");
      var waliHelper = document.getElementById("qr-wali-helper");
      if (img) {
        var qSrc = "mod/e-izin/qrcode.php?id=" + encodeURIComponent(id);
        if (role) qSrc += "&role=" + encodeURIComponent(role);
        qSrc += "&_=" + Date.now();
        img.src = qSrc;
        img.alt = "QR Code Izin #" + id + (role ? " (" + role + ")" : "");
        // Do not start polling from the QR modal open handler.
        // Polling is controlled globally (server-inserted watch id) or by other flows.
      }
      if (hiddenInput) {
        hiddenInput.value = "";
        try {
          hiddenInput.dataset.izinId = id;
          if (role) hiddenInput.dataset.role = role;
        } catch (e) {}
      }
      try {
        window._lastQrRole = role || null;
      } catch (e) {}
      if (note)
        note.textContent =
          role === "wali"
            ? "Memuat link terenkripsi untuk wali kelas..."
            : role === "admin"
            ? "Memuat link terenkripsi untuk petugas/admin..."
            : "Memuat link terenkripsi...";
      if (waliHelper) waliHelper.style.display = "none";

      var btnCopyLocal = document.getElementById("btn-copy-approve");
      if (btnCopyLocal) {
        if (!btnCopyLocal._origHtml)
          btnCopyLocal._origHtml = btnCopyLocal.innerHTML;
        if (role === "admin") {
          try {
            btnCopyLocal.style.display = "none";
            btnCopyLocal.disabled = true;
          } catch (e) {}
        } else {
          btnCopyLocal.style.display = "inline-block";
          btnCopyLocal.disabled = true;
          btnCopyLocal.setAttribute("data-loading", "1");
          btnCopyLocal.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Memuat...';
        }
      }

      var encUrl = "mod/e-izin/encode_link.php?id=" + encodeURIComponent(id);
      if (role) encUrl += "&role=" + encodeURIComponent(role);
      fetch(encUrl, {
        credentials: "same-origin",
      })
        .then(function (res) {
          return res.json().catch(function () {
            return { success: false, message: "Invalid JSON response" };
          });
        })
        .then(function (json) {
          var btnCopyNow = document.getElementById("btn-copy-approve");
          var link = null;
          try {
            link =
              json && (json.link || json.url) ? json.link || json.url : null;
          } catch (e) {
            link = null;
          }
          if (json && json.success && link) {
            if (hiddenInput) {
              hiddenInput.value = link;
              try {
                hiddenInput.dataset.url = link;
              } catch (e) {}
            }
            var visible = document.getElementById("qr-link-visible");
            if (visible) visible.style.display = "none";
            if (note)
              note.textContent =
                role === "wali"
                  ? "Tunjukkan QR ini ke wali kelas untuk proses approve atau salin link."
                  : "Tunjukkan QR ini ke petugas/admin untuk proses approve.";
            if (btnCopyNow) {
              if (role === "wali") {
                btnCopyNow.disabled = false;
                btnCopyNow.removeAttribute("data-loading");
                try {
                  btnCopyNow.style.display = "inline-block";
                  btnCopyNow.innerHTML =
                    btnCopyNow._origHtml ||
                    '<i class="fas fa-copy"></i> Salin Link Approve';
                } catch (e) {}
                try {
                  if (waliHelper) waliHelper.style.display = "block";
                } catch (e) {}
              } else {
                try {
                  btnCopyNow.disabled = true;
                  btnCopyNow.style.display = "none";
                } catch (e) {}
                try {
                  if (waliHelper) waliHelper.style.display = "none";
                } catch (e) {}
              }
            }
          } else {
            if (note) note.textContent = "Gagal memuat link approve.";
            console.warn("encode_link failed", json);
            if (btnCopyNow) {
              btnCopyNow.disabled = true;
              btnCopyNow.removeAttribute("data-loading");
              try {
                btnCopyNow.innerHTML =
                  btnCopyNow._origHtml ||
                  '<i class="fas fa-copy"></i> Salin Link Approve';
              } catch (e) {}
            }
            var visibleFail = document.getElementById("qr-link-visible");
            if (visibleFail) visibleFail.style.display = "none";
            if (json && json.message)
              showAlert("error", "Gagal memuat link", json.message);
          }
        })
        .catch(function (err) {
          if (note) note.textContent = "Gagal memuat link approve.";
          console.error(err);
          var btnCopyNow = document.getElementById("btn-copy-approve");
          if (btnCopyNow) {
            btnCopyNow.disabled = true;
            btnCopyNow.removeAttribute("data-loading");
          }
          showAlert(
            "error",
            "Gagal memuat link",
            err && err.message ? err.message : String(err)
          );
        });

      try {
        var existingBackdrops = document.querySelectorAll(".modal-backdrop");
        existingBackdrops.forEach(function (b) {
          try {
            b.parentNode && b.parentNode.removeChild(b);
          } catch (e) {}
        });

        if (window.jQuery && window.jQuery("#modal-qrcode").modal) {
          try {
            window
              .jQuery("#modal-qrcode")
              .modal({ backdrop: false, keyboard: true, show: true });

            try {
              // start visual countdown in modal
              startQrCountdown(10);
            } catch (e) {}

            // schedule auto-close and reload after 10 seconds
            try {
              if (qrAutoCloseTimer) {
                clearTimeout(qrAutoCloseTimer);
                qrAutoCloseTimer = null;
              }
              qrAutoCloseTimer = setTimeout(function () {
                try {
                  stopPolling && stopPolling();
                } catch (e) {}
                try {
                  if (window.jQuery && window.jQuery("#modal-qrcode").modal) {
                    try {
                      window.jQuery("#modal-qrcode").modal("hide");
                    } catch (e) {}
                  } else {
                    var mm = document.getElementById("modal-qrcode");
                    if (mm) {
                      mm.style.display = "none";
                      mm.classList.remove("show");
                      mm.setAttribute("aria-hidden", "true");
                    }
                  }
                } catch (e) {}
                setTimeout(function () {
                  try {
                    // Attempt to confirm server-side konfirmasi before forcing reload
                    checkServerThenReload(id, 6, 800);
                  } catch (e) {
                    try {
                      location.reload();
                    } catch (err) {
                      window.location.reload && window.location.reload();
                    }
                  }
                }, 300);
              }, 10000);
            } catch (e) {}

            setTimeout(function () {
              try {
                var mchk = document.getElementById("modal-qrcode");
                var visible = false;
                if (mchk) {
                  var rects = mchk.getClientRects && mchk.getClientRects();
                  if (rects && rects.length) visible = true;
                  var cs = window.getComputedStyle(mchk);
                  if (cs && cs.display === "none") visible = false;
                }
                if (!visible) {
                  console.warn(
                    "jQuery modal did not display the dialog; invoking manualShowModal fallback"
                  );
                  manualShowModal();
                }
              } catch (e) {
                console.warn("visibility check failed", e);
              }
            }, 120);
            setTimeout(function () {
              try {
                document
                  .querySelectorAll(".modal-backdrop")
                  .forEach(function (b) {
                    b.parentNode && b.parentNode.removeChild(b);
                  });
                var m = document.getElementById("modal-qrcode");
                if (m) {
                  m.style.zIndex = 20000;
                  var dlg = m.querySelector(".modal-dialog");
                  if (dlg) dlg.style.zIndex = 20001;
                }
                document.body.classList.remove("modal-open");
              } catch (e) {
                console.warn("cleanup modal backdrops failed", e);
              }
            }, 50);
            window.jQuery("#modal-qrcode").on("hidden.bs.modal", function () {
              try {
                document
                  .querySelectorAll(".modal-backdrop")
                  .forEach(function (b) {
                    b.parentNode && b.parentNode.removeChild(b);
                  });
                document.body.classList.remove("modal-open");
                try {
                  stopPolling && stopPolling();
                } catch (e) {}
                try {
                  if (qrAutoCloseTimer) {
                    clearTimeout(qrAutoCloseTimer);
                    qrAutoCloseTimer = null;
                  }
                } catch (e) {}
                try {
                  clearQrCountdown();
                } catch (e) {}
              } catch (e) {}
            });
          } catch (errModal) {
            console.warn(
              "jQuery modal() threw, falling back to manual show",
              errModal
            );
            manualShowModal();
          }
        } else {
          manualShowModal();
        }

        function manualShowModal() {
          var modal = document.getElementById("modal-qrcode");
          if (!modal) return;
          try {
            if (modal.parentNode !== document.body)
              document.body.appendChild(modal);
            modal.style.display = "block";
            modal.classList.add("show");
            modal.setAttribute("aria-hidden", "false");
            modal.style.zIndex = 20000;
            var dlg = modal.querySelector(".modal-dialog");
            if (dlg) dlg.style.zIndex = 20001;
            document.querySelectorAll(".modal-backdrop").forEach(function (b) {
              b.parentNode && b.parentNode.removeChild(b);
            });
            // start visual countdown and schedule auto-close for manual modal
            try {
              startQrCountdown(10);
            } catch (e) {}
            try {
              if (qrAutoCloseTimer) {
                clearTimeout(qrAutoCloseTimer);
                qrAutoCloseTimer = null;
              }
              qrAutoCloseTimer = setTimeout(function () {
                try {
                  stopPolling && stopPolling();
                } catch (e) {}
                try {
                  modal.style.display = "none";
                  modal.classList.remove("show");
                  modal.setAttribute("aria-hidden", "true");
                } catch (e) {}
                try {
                  document
                    .querySelectorAll(".modal-backdrop")
                    .forEach(function (b) {
                      b.parentNode && b.parentNode.removeChild(b);
                    });
                } catch (e) {}
                setTimeout(function () {
                  try {
                    checkServerThenReload(id, 6, 800);
                  } catch (e) {
                    try {
                      location.reload();
                    } catch (err) {
                      window.location.reload && window.location.reload();
                    }
                  }
                }, 300);
              }, 10000);
            } catch (e) {}
            modal
              .querySelectorAll('[data-dismiss="modal"]')
              .forEach(function (btn) {
                btn.addEventListener("click", hide);
              });
            function escHandler(e) {
              if (e.key === "Escape" || e.keyCode === 27) hide();
            }
            document.addEventListener("keydown", escHandler);
            function hide() {
              try {
                modal.style.display = "none";
                modal.classList.remove("show");
                modal.setAttribute("aria-hidden", "true");
              } catch (e) {}
              try {
                document
                  .querySelectorAll(".modal-backdrop")
                  .forEach(function (b) {
                    b.parentNode && b.parentNode.removeChild(b);
                  });
              } catch (e) {}
              try {
                // clear auto-close timer and countdown when hidden manually
                if (qrAutoCloseTimer) {
                  clearTimeout(qrAutoCloseTimer);
                  qrAutoCloseTimer = null;
                }
              } catch (e) {}
              try {
                clearQrCountdown();
              } catch (e) {}
              try {
                document.body.classList.remove("modal-open");
              } catch (e) {}
              try {
                document.removeEventListener("keydown", escHandler);
              } catch (e) {}
            }
          } catch (e) {
            console.warn("manualShowModal failed", e);
          }
        }
      } catch (e) {
        console.warn("Could not open modal via JS", e);
      }
    });

    document.addEventListener("click", function (ev) {
      var trg = ev.target.closest && ev.target.closest("#btn-copy-approve");
      if (!trg) return;
      ev.preventDefault();

      var hiddenInput = document.getElementById("qr-approve-link");
      var val = hiddenInput ? hiddenInput.value : "";

      if (val) {
        doCopyFlow(val);
        return;
      }

      var fetchId =
        hiddenInput && hiddenInput.dataset && hiddenInput.dataset.izinId;
      if (!fetchId) {
        return showAlert(
          "error",
          "Tidak ada link",
          "Link approve tidak tersedia. Buka QR modal lalu coba lagi."
        );
      }

      showAlert("info", "Memuat Link", "Mencoba memuat link persetujuan...");
      var encFetchUrl =
        "mod/e-izin/encode_link.php?id=" + encodeURIComponent(fetchId);
      try {
        stopPolling && stopPolling();
      } catch (e) {}
      try {
        var dsRole =
          hiddenInput && hiddenInput.dataset && hiddenInput.dataset.role
            ? hiddenInput.dataset.role
            : null;
        if (!dsRole && typeof window._lastQrRole !== "undefined")
          dsRole = window._lastQrRole;
        if (dsRole) encFetchUrl += "&role=" + encodeURIComponent(dsRole);
      } catch (e) {}

      var _encFetchPromise = fetch(encFetchUrl, {
        credentials: "same-origin",
      });

      _encFetchPromise
        .then(function (r) {
          return r.json().catch(function () {
            return { success: false, message: "Invalid JSON" };
          });
        })
        .then(function (j) {
          var link = j && (j.link || j.url) ? j.link || j.url : null;
          if (j && j.success && link) {
            if (hiddenInput) hiddenInput.value = link;
            try {
              hiddenInput.dataset.url = link;
            } catch (e) {}
            try {
              trg.disabled = false;
              if (trg._origHtml) trg.innerHTML = trg._origHtml;
            } catch (e) {}
            promptCopyWithDialog(link);
          } else {
            showAlert(
              "error",
              "Gagal",
              "Tidak dapat memuat link approve: " +
                (j && j.message ? j.message : "Unknown")
            );
          }
        })
        .catch(function (err) {
          showAlert(
            "error",
            "Gagal",
            "Kesalahan jaringan: " +
              (err && err.message ? err.message : String(err))
          );
        });

      function doCopyFlow(value) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard
            .writeText(value)
            .then(function () {
              var p = showAlert(
                "success",
                "Tersalin",
                "Link approve berhasil disalin silahkan berikan ke Wali Kelas."
              );
              if (p && typeof p.then === "function") {
                p.then(function () {
                  try {
                    closeQrModal();
                  } catch (e) {}
                  setTimeout(function () {
                    window.location.reload();
                  }, 2500);
                });
              } else {
                try {
                  closeQrModal();
                } catch (e) {}
                setTimeout(function () {
                  window.location.reload();
                }, 2500);
              }
            })
            .catch(function () {
              fallbackCopy(value);
            });
        } else {
          fallbackCopy(value);
        }
      }

      function promptCopyWithDialog(url) {
        if (window.Swal && typeof window.Swal.fire === "function") {
          window.Swal.fire({
            icon: "info",
            title: "Link siap disalin",
            text: "Klik Tombol 'Salin Sekarang' untuk menyalin link approve ke clipboard.",
            showCancelButton: true,
            confirmButtonText: "Salin Sekarang",
            cancelButtonText: "Batal",
          }).then(function (res) {
            if (res && res.isConfirmed) {
              doCopyFlow(url);
            }
          });
          return;
        }

        if (typeof window.swal === "function") {
          try {
            var cfg = {
              title: "Link siap disalin",
              text: "Klik Salin Sekarang untuk menyalin link approve ke clipboard.",
              icon: "info",
              buttons: { cancel: "Batal", confirm: "Salin Sekarang" },
            };
            var p = window.swal(cfg);
            if (p && typeof p.then === "function") {
              p.then(function (ok) {
                if (ok) doCopyFlow(url);
              });
              return;
            }
            window.swal(cfg, function (ok) {
              if (ok) doCopyFlow(url);
            });
            return;
          } catch (e) {}
        }

        if (confirm("Salin link approve ke clipboard sekarang?")) {
          doCopyFlow(url);
        }
      }
    });

    function fallbackCopy(text) {
      var ta = document.createElement("textarea");
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try {
        var ok = document.execCommand("copy");
        var p = showAlert(
          ok ? "success" : "error",
          ok ? "Tersalin" : "Gagal",
          ok
            ? "Link approve berhasil disalin."
            : "Browser tidak mendukung penyalinan otomatis."
        );
        try {
          document.body.removeChild(ta);
        } catch (e) {}
        if (ok) {
          if (p && typeof p.then === "function") {
            p.then(function () {
              try {
                closeQrModal();
              } catch (e) {}
              setTimeout(function () {
                window.location.reload();
              }, 2500);
            });
          } else {
            try {
              closeQrModal();
            } catch (e) {}
            setTimeout(function () {
              window.location.reload();
            }, 2500);
          }
        }
      } catch (e) {
        try {
          document.body.removeChild(ta);
        } catch (e) {}
        showAlert("error", "Gagal", "Tidak dapat menyalin ke clipboard.");
      }
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      initForm();
      initQrModal();
      initKartuModal();
    });
  } else {
    initForm();
    initQrModal();
    initKartuModal();
  }

  // If server rendered a watch id, start background polling to autoreload on status change
  try {
    if (
      window &&
      window._eizin_watch_id &&
      typeof window.eizin_startPolling === "function"
    ) {
      try {
        window.eizin_startPolling(
          window._eizin_watch_id,
          window._eizin_watch_role || ""
        );
      } catch (e) {
        try {
          window.eizin_startPolling(window._eizin_watch_id);
        } catch (e) {}
      }
    }
  } catch (e) {}

  function initKartuModal() {
    var kartuAutoCloseTimer = null;
    var kartuCountdownInterval = null;

    // Local helper for kartu modal to poll server state before reloading
    function checkServerThenReloadKartu(watchId, maxAttempts, interval) {
      maxAttempts = parseInt(maxAttempts, 10) || 5;
      interval = parseInt(interval, 10) || 700;
      if (!watchId) {
        setTimeout(function () {
          try {
            location.reload();
          } catch (e) {
            window.location.reload && window.location.reload();
          }
        }, 300);
        return;
      }
      var attempts = 0;
      (function tryOnce() {
        attempts++;
        fetch(
          "mod/e-izin/e-izin.php?ajax_check_status=1&id=" +
            encodeURIComponent(watchId),
          {
            credentials: "same-origin",
            headers: { "X-Requested-With": "XMLHttpRequest" },
          }
        )
          .then(function (r) {
            return r.json().catch(function () {
              return null;
            });
          })
          .then(function (json) {
            try {
              var state =
                json && json.state ? String(json.state).toLowerCase() : null;
              if (state === "returned") {
                try {
                  location.reload();
                } catch (e) {
                  window.location.reload && window.location.reload();
                }
                return;
              }
              var konf =
                json && json.konfirmasi
                  ? String(json.konfirmasi).toLowerCase()
                  : "";
              if (konf === "keluar" || konf === "pulang") {
                try {
                  location.reload();
                } catch (e) {
                  window.location.reload && window.location.reload();
                }
                return;
              }
            } catch (e) {}
            if (attempts < maxAttempts) {
              setTimeout(tryOnce, interval);
            } else {
              try {
                location.reload();
              } catch (e) {
                window.location.reload && window.location.reload();
              }
            }
          })
          .catch(function () {
            if (attempts < maxAttempts) setTimeout(tryOnce, interval);
            else
              try {
                location.reload();
              } catch (e) {
                window.location.reload && window.location.reload();
              }
          });
      })();
    }

    function startKartuCountdown(seconds) {
      try {
        try {
          if (kartuCountdownInterval) {
            clearInterval(kartuCountdownInterval);
            kartuCountdownInterval = null;
          }
        } catch (e) {}
        var secs = parseInt(seconds, 10) || 10;
        var body = document.querySelector("#modal-kartu-izin .modal-body");
        if (body) {
          var existing = document.getElementById("kartu-countdown");
          if (!existing) {
            existing = document.createElement("div");
            existing.id = "kartu-countdown";
            existing.style.marginTop = "10px";
            existing.style.fontSize = "0.95rem";
            existing.style.color = "#6c757d";
            existing.setAttribute("aria-live", "polite");
            body.appendChild(existing);
          }
          existing.textContent = "Tutup otomatis dalam " + secs + " detik";
          kartuCountdownInterval = setInterval(function () {
            secs -= 1;
            if (secs <= 0) {
              try {
                existing.textContent = "Menutup...";
              } catch (e) {}
              try {
                clearKartuCountdown();
              } catch (e) {}
              return;
            }
            try {
              existing.textContent = "Tutup otomatis dalam " + secs + " detik";
            } catch (e) {}
          }, 1000);
        }
      } catch (e) {}
    }

    function clearKartuCountdown() {
      try {
        if (kartuCountdownInterval) {
          clearInterval(kartuCountdownInterval);
          kartuCountdownInterval = null;
        }
      } catch (e) {}
      try {
        var el = document.getElementById("kartu-countdown");
        if (el && el.parentNode) el.parentNode.removeChild(el);
      } catch (e) {}
    }

    document.addEventListener("click", function (ev) {
      var btn = ev.target.closest && ev.target.closest(".btn-show-kartu");
      if (!btn) return;
      ev.preventDefault();

      var id = btn.getAttribute("data-id");
      var jenis = btn.getAttribute("data-jenis") || "";
      var tanggal = btn.getAttribute("data-tanggal") || "";
      var keterangan = btn.getAttribute("data-keterangan") || "";
      var nama = btn.getAttribute("data-nama") || "";
      var kelas = btn.getAttribute("data-kelas") || "";
      var avatar = btn.getAttribute("data-avatar") || "";
      var approveUrl = btn.getAttribute("data-approve-url") || "";

      try {
        document.getElementById("kartu-nama").textContent = nama;
      } catch (e) {}
      try {
        document.getElementById("kartu-kelas").textContent = kelas;
      } catch (e) {}
      try {
        document.getElementById("kartu-jenis").textContent = jenis;
      } catch (e) {}
      try {
        document.getElementById("kartu-tanggal").textContent = tanggal;
      } catch (e) {}
      try {
        document.getElementById("kartu-keterangan").textContent = keterangan;
      } catch (e) {}
      try {
        document.getElementById("kartu-avatar").src = avatar;
      } catch (e) {}
      try {
        document.getElementById("kartu-diajukan").textContent =
          new Date().toLocaleString();
      } catch (e) {}

      try {
        var q = document.getElementById("kartu-qrcode");
        if (q) {
          var base =
            "mod/e-izin/qrcode.php?id=" +
            encodeURIComponent(id) +
            "&role=security";
          q.src = base + "&_=" + Date.now();
        }
      } catch (e) {}

      try {
        var printLink = document.getElementById("kartu-print");
        if (printLink)
          printLink.href =
            "mod/e-izin/print_kartu.php?id=" + encodeURIComponent(id);
      } catch (e) {}

      try {
        var approveAnchor = document.getElementById("kartu-approve");
        if (approveAnchor) {
          if (approveUrl) {
            approveAnchor.href = approveUrl;
            approveAnchor.style.display = "inline-block";
          } else {
            approveAnchor.href = "#";
            approveAnchor.style.display = "none";
          }
        }
      } catch (e) {}

      try {
        if (window.jQuery && window.jQuery("#modal-kartu-izin").modal) {
          window
            .jQuery("#modal-kartu-izin")
            .modal({ backdrop: false, keyboard: true, show: true });
          try {
            startKartuCountdown(10);
          } catch (e) {}
          try {
            if (kartuAutoCloseTimer) {
              clearTimeout(kartuAutoCloseTimer);
              kartuAutoCloseTimer = null;
            }
            kartuAutoCloseTimer = setTimeout(function () {
              try {
                if (window.jQuery && window.jQuery("#modal-kartu-izin").modal) {
                  window.jQuery("#modal-kartu-izin").modal("hide");
                } else {
                  var mm = document.getElementById("modal-kartu-izin");
                  if (mm) {
                    mm.style.display = "none";
                    mm.classList.remove("show");
                    mm.setAttribute("aria-hidden", "true");
                  }
                }
              } catch (e) {}
              try {
                clearKartuCountdown();
              } catch (e) {}
              setTimeout(function () {
                try {
                  checkServerThenReloadKartu(id, 6, 800);
                } catch (e) {
                  try {
                    location.reload();
                  } catch (err) {
                    window.location.reload && window.location.reload();
                  }
                }
              }, 300);
            }, 10000);
          } catch (e) {}
        } else {
          var m = document.getElementById("modal-kartu-izin");
          if (m) {
            if (m.parentNode !== document.body) document.body.appendChild(m);
            m.style.display = "block";
            m.classList.add("show");
            m.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open");
            try {
              startKartuCountdown(10);
            } catch (e) {}
            try {
              if (kartuAutoCloseTimer) {
                clearTimeout(kartuAutoCloseTimer);
                kartuAutoCloseTimer = null;
              }
              kartuAutoCloseTimer = setTimeout(function () {
                try {
                  m.style.display = "none";
                  m.classList.remove("show");
                  m.setAttribute("aria-hidden", "true");
                } catch (e) {}
                try {
                  document
                    .querySelectorAll(".modal-backdrop")
                    .forEach(function (b) {
                      b.parentNode && b.parentNode.removeChild(b);
                    });
                } catch (e) {}
                try {
                  clearKartuCountdown();
                } catch (e) {}
                setTimeout(function () {
                  try {
                    checkServerThenReloadKartu(id, 6, 800);
                  } catch (e) {
                    try {
                      location.reload();
                    } catch (err) {
                      window.location.reload && window.location.reload();
                    }
                  }
                }, 300);
              }, 10000);
            } catch (e) {}
          }
        }
      } catch (e) {
        console.warn("Could not show kartu modal", e);
      }

      try {
        if (
          window.jQuery &&
          typeof window.jQuery === "function" &&
          window.jQuery("#modal-kartu-izin").on
        ) {
          window.jQuery("#modal-kartu-izin").on("hidden.bs.modal", function () {
            try {
              if (kartuAutoCloseTimer) {
                clearTimeout(kartuAutoCloseTimer);
                kartuAutoCloseTimer = null;
              }
            } catch (e) {}
            try {
              clearKartuCountdown();
            } catch (e) {}
          });
        }
      } catch (e) {}

      try {
        var modalEl = document.getElementById("modal-kartu-izin");
        if (modalEl) {
          document.querySelectorAll(".modal-backdrop").forEach(function (b) {
            try {
              b.parentNode && b.parentNode.removeChild(b);
            } catch (e) {}
          });
          modalEl
            .querySelectorAll('[data-dismiss="modal"]')
            .forEach(function (btn) {
              try {
                btn.addEventListener("click", function (ev) {
                  ev.preventDefault();
                  try {
                    if (window.jQuery && window.jQuery(modalEl).modal) {
                      window.jQuery(modalEl).modal("hide");
                      return;
                    }
                  } catch (e) {}
                  try {
                    modalEl.style.display = "none";
                    modalEl.classList.remove("show");
                    modalEl.setAttribute("aria-hidden", "true");
                  } catch (e) {}
                  try {
                    document.body.classList.remove("modal-open");
                  } catch (e) {}
                  try {
                    if (kartuAutoCloseTimer) {
                      clearTimeout(kartuAutoCloseTimer);
                      kartuAutoCloseTimer = null;
                    }
                  } catch (e) {}
                  try {
                    clearKartuCountdown();
                  } catch (e) {}
                });
              } catch (e) {}
            });

          (function () {
            function escHandler(e) {
              if (e.key === "Escape" || e.keyCode === 27) {
                try {
                  if (window.jQuery && window.jQuery(modalEl).modal) {
                    window.jQuery(modalEl).modal("hide");
                    return;
                  }
                } catch (e) {}
                try {
                  modalEl.style.display = "none";
                  modalEl.classList.remove("show");
                  modalEl.setAttribute("aria-hidden", "true");
                } catch (e) {}
                try {
                  document.body.classList.remove("modal-open");
                } catch (e) {}
                try {
                  document.removeEventListener("keydown", escHandler);
                } catch (e) {}
              }
            }
            setTimeout(function () {
              document.addEventListener("keydown", escHandler);
            }, 10);
          })();
        }
      } catch (e) {}
    });
  }
})();
