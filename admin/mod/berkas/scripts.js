"use strict";
// === Preview Berkas Modal dengan Zoom (gambar) ===
$(document).on("click", ".btn-lihat-berkas", function () {
  var filename = $(this).data("filename");
  if (!filename) return;
  var ext = filename.split(".").pop().toLowerCase();
  var url = "../content/berkas/" + encodeURIComponent(filename);
  var html = "";
  if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].indexOf(ext) !== -1) {
    html =
      '<img id="img-preview-berkas" class="preview-zoomable" src="' +
      url +
      '" alt="Preview" style="max-width:100%;max-height:70vh;cursor:zoom-in;">';
  } else if (ext === "pdf") {
    html =
      '<iframe src="' +
      url +
      '" style="width:100%;height:70vh;" frameborder="0"></iframe>';
  } else {
    // Download link removed per request — show placeholder text instead
    html = '<div class="text-muted">File cannot be previewed.</div>';
  }
  $("#modalPreviewBerkasBody").html(html);
  $("#modalPreviewBerkas").modal("show");
});

// Event handler untuk zoom gambar di modal preview - menggunakan event delegation
$(document).on("click", ".preview-zoomable", function () {
  var imgElement = this;

  // Hancurkan viewer yang mungkin sudah ada untuk elemen ini
  if (imgElement._viewer) {
    imgElement._viewer.destroy();
    imgElement._viewer = null;
  }

  // Buat viewer baru setiap kali diklik
  if (window.Viewer) {
    imgElement._viewer = new Viewer(imgElement, {
      inline: false,
      navbar: false,
      toolbar: {
        zoomIn: 4,
        zoomOut: 4,
        oneToOne: 4,
        reset: 4,
        prev: 0,
        play: 0,
        next: 0,
        rotateLeft: 4,
        rotateRight: 4,
        flipHorizontal: 4,
        flipVertical: 4,
      },
      title: false,
      movable: true,
      zoomable: true,
      rotatable: true,
      scalable: true,
      transition: true,
      fullscreen: true,
      keyboard: true,
      // Event handler ketika viewer ditutup
      hidden: function () {
        // Destroy viewer setelah ditutup untuk mencegah konflik
        setTimeout(function () {
          if (imgElement._viewer) {
            imgElement._viewer.destroy();
            imgElement._viewer = null;
          }
        }, 100);
      },
    });

    // Langsung tampilkan viewer
    imgElement._viewer.show();
  }
});

// Event handler untuk membersihkan viewer ketika modal preview ditutup
$(document).on("hidden.bs.modal", "#modalPreviewBerkas", function () {
  // Bersihkan viewer yang mungkin masih ada
  $(this)
    .find(".preview-zoomable")
    .each(function () {
      if (this._viewer) {
        try {
          this._viewer.destroy();
          this._viewer = null;
        } catch (e) {
          console.log("Error destroying preview viewer:", e);
        }
      }
    });
});

// === Lihat Semua Berkas Modal ===
$(document).on("click", ".btn-lihat-semua-berkas", function () {
  var berkasData = $(this).data("berkas");
  var namaSiswa = $(this).data("nama");
  var userId = $(this).data("user");
  var validasi = $(this).data("validasi");
  if (!berkasData) return;
  var berkasLabels = {
    kk: "Kartu Keluarga",
    ijazah: "Ijazah",
    akte: "Akte Lahir",
    kip: "KIP",
    kks: "KKS/PKH/BPNT",
    kis: "KIS",
  };
  var html = "";
  var imageIds = [];

  // Only render known berkas keys; skip any metadata like 'keterangan' or 'validasi_by'
  var allowedKeys = Object.keys(berkasLabels);
  Object.keys(berkasData).forEach(function (jenis) {
    if (allowedKeys.indexOf(jenis) === -1) return; // skip unknown fields
    var filename = berkasData[jenis];
    var label = berkasLabels[jenis] || jenis.toUpperCase();
    var ext = filename.split(".").pop().toLowerCase();
    var url = "../content/berkas/" + encodeURIComponent(filename);
    html += '<div class="mb-4 border-bottom pb-3">';
    html += '<h5 class="text-center text-primary mb-3">' + label + "</h5>";
    html += '<div class="text-center">';
    if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].indexOf(ext) !== -1) {
      var imageId = "berkas-img-" + jenis + "-" + Date.now();
      imageIds.push(imageId);
      html +=
        '<img id="' +
        imageId +
        '" src="' +
        url +
        '" alt="' +
        label +
        '" class="img-fluid border berkas-zoomable" style="max-width:100%;max-height:500px;box-shadow:0 4px 8px rgba(0,0,0,0.1);cursor:zoom-in;" title="Klik untuk zoom">';
    } else if (ext === "pdf") {
      html +=
        '<iframe src="' +
        url +
        '" style="width:100%;height:500px;border:1px solid #ddd;box-shadow:0 4px 8px rgba(0,0,0,0.1);" frameborder="0"></iframe>';
    } else {
      html +=
        '<div class="p-4 border" style="box-shadow:0 4px 8px rgba(0,0,0,0.1);">';
      html +=
        '<i class="fas fa-file text-secondary" style="font-size:80px;"></i>';
      html += "</div>";
      html += '<div class="text-muted">File cannot be previewed.</div>';
    }
    html += "</div>";
    html += "</div>";
  });

  // Setup modal header sederhana
  $("#modalLihatSemuaBerkasLabel").text(
    "Review & Validasi Berkas - " + namaSiswa
  );

  // Buat panel validasi modern sticky di atas
  var validasi_opsi = {
    "": {
      text: "Belum Divalidasi",
      class: "secondary",
      icon: "fas fa-clock",
      color: "#6c757d",
    },
    valid: {
      text: "Valid",
      class: "success",
      icon: "fas fa-check-circle",
      color: "#28a745",
    },
    tidak_valid: {
      text: "Tidak Valid",
      class: "danger",
      icon: "fas fa-times-circle",
      color: "#dc3545",
    },
    revisi: {
      text: "Perlu Revisi",
      class: "warning",
      icon: "fas fa-exclamation-triangle",
      color: "#ffc107",
    },
  };

  var validasiPanel = "";
  if (window.is_wali_kelas) {
    // Read-only panel for wali kelas: show current status and any note, no action buttons
    var st = validasi_opsi[validasi] || {
      text: "Belum Divalidasi",
      class: "secondary",
      color: "#6c757d",
    };
    validasiPanel +=
      '<div class="validation-panel bg-white border rounded shadow-sm mb-4" style="position:sticky;top:0;z-index:100;margin:-1rem -1rem 1rem -1rem;padding:1rem;">';
    validasiPanel += '<div class="row align-items-center">';
    validasiPanel += '<div class="col-12">';
    validasiPanel +=
      '<h6 class="mb-2 text-dark"><i class="fas fa-clipboard-check text-primary mr-2"></i>Status Validasi Berkas</h6>';
    validasiPanel +=
      '<div><span class="badge badge-' +
      st.class +
      '">' +
      st.text +
      "</span></div>";
    var existingNote =
      berkasData && berkasData["keterangan"] ? berkasData["keterangan"] : "";
    if (existingNote) {
      // escape text content
      var esc = $("<div>").text(existingNote).html();
      validasiPanel +=
        '<div class="mt-3"><label class="mb-1"><strong>Catatan</strong></label><div class="p-2 bg-light border rounded small" style="white-space:pre-wrap;">' +
        esc +
        "</div></div>";
    }
    validasiPanel += "</div></div></div>";
  } else {
    // Full interactive panel for admins (original behavior)
    validasiPanel =
      '<div class="validation-panel bg-white border rounded shadow-sm mb-4" style="position:sticky;top:0;z-index:100;margin:-1rem -1rem 1rem -1rem;padding:1rem;">';
    validasiPanel += '<div class="row align-items-center">';
    validasiPanel += '<div class="col-md-4">';
    validasiPanel +=
      '<h6 class="mb-2 text-dark"><i class="fas fa-clipboard-check text-primary mr-2"></i>Status Validasi Berkas</h6>';
    validasiPanel +=
      '<small class="text-muted">Pilih status setelah mereview semua berkas</small>';
    validasiPanel += "</div>";

    validasiPanel += '<div class="col-md-8">';
    validasiPanel +=
      '<div class="validation-buttons d-flex flex-wrap gap-2" style="gap:0.5rem;">';

    Object.keys(validasi_opsi).forEach(function (val, index) {
      var opsi = validasi_opsi[val];
      var isActive = val === validasi;
      var activeClass = isActive ? "active shadow-sm" : "";
      var borderColor = isActive ? opsi.color : "#dee2e6";
      var bgColor = isActive ? opsi.color : "#ffffff";
      var textColor = isActive ? "#ffffff" : "#495057";

      validasiPanel +=
        '<button type="button" class="validation-btn ' + activeClass + '" ';
      validasiPanel += 'data-status="' + val + '" data-user="' + userId + '" ';
      validasiPanel += 'style="';
      validasiPanel += "border: 2px solid " + borderColor + ";";
      validasiPanel += "background-color: " + bgColor + ";";
      validasiPanel += "color: " + textColor + ";";
      validasiPanel += "padding: 0.75rem 1rem;";
      validasiPanel += "border-radius: 0.5rem;";
      validasiPanel += "font-weight: 500;";
      validasiPanel += "transition: all 0.3s ease;";
      validasiPanel += "cursor: pointer;";
      validasiPanel += "min-width: 140px;";
      validasiPanel += "margin-right: 0.5rem;";
      validasiPanel += "margin-bottom: 0.5rem;";
      validasiPanel += '">';
      validasiPanel += '<i class="' + opsi.icon + ' mr-2"></i>' + opsi.text;
      validasiPanel += "</button>";
    });

    validasiPanel += "</div>";
    var showNote = validasi === "tidak_valid" || validasi === "revisi";
    validasiPanel +=
      '<div class="mt-3" id="validation-keterangan-wrapper" ' +
      (showNote ? "" : 'style="display:none;"') +
      ">";
    validasiPanel +=
      '<label for="validation-keterangan" class="mb-1"><strong>Catatan (Keterangan)</strong> <small class="text-muted">(wajib untuk Penolakan/Revisi)</small></label>';
    validasiPanel +=
      '<textarea id="validation-keterangan" class="form-control" rows="3" placeholder="Tulis alasan penolakan atau instruksi revisi di sini..."></textarea>';
    validasiPanel += "</div>";
    validasiPanel +=
      '<div id="modal-validasi-berkas-status" class="mt-2"></div>';
    validasiPanel += "</div>";
    validasiPanel += "</div>";
    validasiPanel += "</div>";
  }

  // Gabungkan panel validasi dengan konten berkas
  html = validasiPanel + html;
  $("#modalLihatSemuaBerkasBody").html(html);
  // Prefill keterangan if present in payload
  try {
    var existingNote =
      berkasData && berkasData["keterangan"] ? berkasData["keterangan"] : "";
    $("#validation-keterangan").val(existingNote);
  } catch (e) {}
  $("#modalLihatSemuaBerkas").modal("show");
});

// Handler validasi berkas dengan UI modern
$(document).on("click", ".validation-btn", function () {
  var button = $(this);
  var user_id = button.data("user");
  var status = button.data("status");

  console.log("Validation clicked:", { user_id: user_id, status: status });

  if (!user_id) {
    console.error("Missing user_id");
    $("#modal-validasi-berkas-status").html(
      '<div class="alert alert-danger py-2 mb-0">' +
        '<i class="fas fa-exclamation-circle mr-2"></i>' +
        "Error: User ID tidak ditemukan. Silakan tutup modal dan coba lagi." +
        "</div>"
    );
    return;
  }

  if (status === undefined || status === null) {
    console.error("Missing status");
    $("#modal-validasi-berkas-status").html(
      '<div class="alert alert-danger py-2 mb-0">' +
        '<i class="fas fa-exclamation-circle mr-2"></i>' +
        "Error: Status tidak valid." +
        "</div>"
    );
    return;
  }

  // Toggle visibility of keterangan field depending on selected status
  if (status === "tidak_valid" || status === "revisi") {
    $("#validation-keterangan-wrapper").show();
  } else {
    $("#validation-keterangan-wrapper").hide();
  }

  // For rejection/revisi require keterangan
  if (
    (status === "tidak_valid" || status === "revisi") &&
    ($("#validation-keterangan").val() || "").trim() === ""
  ) {
    // show error if keterangan is required but not provided
    $("#modal-validasi-berkas-status").html(
      '<div class="alert alert-danger py-2 mb-0"><i class="fas fa-exclamation-circle mr-2"></i> Keterangan diperlukan untuk status Penolakan atau Revisi. Silakan isi catatan terlebih dahulu.</div>'
    );
    return;
  }

  // Definisi warna untuk setiap status
  var validasi_opsi = {
    "": { color: "#6c757d", text: "Belum Divalidasi" },
    valid: { color: "#28a745", text: "Valid" },
    tidak_valid: { color: "#dc3545", text: "Tidak Valid" },
    revisi: { color: "#ffc107", text: "Perlu Revisi" },
  };

  // Disable semua tombol dan set loading state
  $(".validation-btn").prop("disabled", true).css("opacity", "0.6");
  $("#modal-validasi-berkas-status").html(
    '<div class="alert alert-info py-2"><i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan status validasi...</div>'
  );

  $.ajax({
    url: "./mod/berkas/proses.php?action=validasi_berkas",
    type: "POST",
    data: {
      user_id: user_id,
      status: status,
      keterangan: $("#validation-keterangan").val(),
    },
    dataType: "text",
    beforeSend: function () {
      console.log("Sending AJAX request:", {
        url: "./mod/berkas/proses.php?action=validasi_berkas",
        data: {
          user_id: user_id,
          status: status,
          keterangan: $("#validation-keterangan").val(),
        },
      });
    },
    success: function (response) {
      console.log("Validasi response:", response);
      var res = response.trim();
      if (res === "success") {
        // Reset semua tombol ke state inactive
        $(".validation-btn").each(function () {
          $(this).removeClass("active shadow-sm").css({
            "background-color": "#ffffff",
            color: "#495057",
            "border-color": "#dee2e6",
          });
        });

        // Set tombol yang dipilih ke state active
        var selectedOpsi = validasi_opsi[status];
        button.addClass("active shadow-sm").css({
          "background-color": selectedOpsi.color,
          color: "#ffffff",
          "border-color": selectedOpsi.color,
        });

        // Tampilkan pesan sukses dengan styling menarik
        $("#modal-validasi-berkas-status").html(
          '<div class="alert alert-success py-2 mb-0">' +
            '<i class="fas fa-check-circle mr-2"></i>' +
            "Status berkas berhasil diperbarui menjadi: <strong>" +
            selectedOpsi.text +
            "</strong>" +
            "</div>"
        );

        // Auto reload datatable dan tutup modal setelah 2 detik
        setTimeout(function () {
          reloadAfterAction();
          $("#modalLihatSemuaBerkas").modal("hide");
        }, 2000);
      } else {
        $("#modal-validasi-berkas-status").html(
          '<div class="alert alert-danger py-2 mb-0">' +
            '<i class="fas fa-exclamation-circle mr-2"></i>' +
            "Gagal menyimpan: " +
            res +
            "</div>"
        );
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", status, error);
      $("#modal-validasi-berkas-status").html(
        '<div class="alert alert-danger py-2 mb-0">' +
          '<i class="fas fa-exclamation-triangle mr-2"></i>' +
          "Terjadi kesalahan server. Silakan coba lagi." +
          "</div>"
      );
    },
    complete: function () {
      // Enable kembali semua tombol
      $(".validation-btn").prop("disabled", false).css("opacity", "1");

      // Clear status message setelah 5 detik jika gagal
      setTimeout(function () {
        if (!$("#modal-validasi-berkas-status").text().includes("berhasil")) {
          $("#modal-validasi-berkas-status").empty();
        }
      }, 5000);
    },
  });
});

// Hover effects untuk tombol validasi
$(document)
  .on("mouseenter", ".validation-btn:not(.active)", function () {
    var status = $(this).data("status");
    var validasi_opsi = {
      "": { color: "#6c757d" },
      valid: { color: "#28a745" },
      tidak_valid: { color: "#dc3545" },
      revisi: { color: "#ffc107" },
    };

    $(this).css({
      "background-color": validasi_opsi[status].color + "20",
      "border-color": validasi_opsi[status].color,
      transform: "translateY(-1px)",
    });
  })
  .on("mouseleave", ".validation-btn:not(.active)", function () {
    $(this).css({
      "background-color": "#ffffff",
      "border-color": "#dee2e6",
      transform: "translateY(0)",
    });
  });

// Event handler untuk zoom gambar berkas - menggunakan event delegation
$(document).on("click", ".berkas-zoomable", function () {
  var imgElement = this;

  // Hancurkan viewer yang mungkin sudah ada untuk elemen ini
  if (imgElement._viewer) {
    imgElement._viewer.destroy();
    imgElement._viewer = null;
  }

  // Buat viewer baru setiap kali diklik
  if (window.Viewer) {
    imgElement._viewer = new Viewer(imgElement, {
      inline: false,
      navbar: false,
      toolbar: {
        zoomIn: 4,
        zoomOut: 4,
        oneToOne: 4,
        reset: 4,
        prev: 0,
        play: 0,
        next: 0,
        rotateLeft: 4,
        rotateRight: 4,
        flipHorizontal: 4,
        flipVertical: 4,
      },
      title: false,
      movable: true,
      zoomable: true,
      rotatable: true,
      scalable: true,
      transition: true,
      fullscreen: true,
      keyboard: true,
      // Event handler ketika viewer ditutup
      hidden: function () {
        // Destroy viewer setelah ditutup untuk mencegah konflik
        setTimeout(function () {
          if (imgElement._viewer) {
            imgElement._viewer.destroy();
            imgElement._viewer = null;
          }
        }, 100);
      },
    });

    // Langsung tampilkan viewer
    imgElement._viewer.show();
  }
});

// Event handler untuk membersihkan semua viewer ketika modal ditutup
$(document).on("hidden.bs.modal", "#modalLihatSemuaBerkas", function () {
  // Bersihkan semua viewer yang mungkin masih ada
  $(this)
    .find(".berkas-zoomable")
    .each(function () {
      if (this._viewer) {
        try {
          this._viewer.destroy();
          this._viewer = null;
        } catch (e) {
          console.log("Error destroying viewer:", e);
        }
      }
    });
});

// Fungsi untuk preview berkas individual dari modal semua berkas
window.previewSingleBerkas = function (filename) {
  var ext = filename.split(".").pop().toLowerCase();
  var url = "../content/berkas/" + encodeURIComponent(filename);
  var html = "";

  if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].indexOf(ext) !== -1) {
    html =
      '<img id="img-preview-berkas" class="preview-zoomable" src="' +
      url +
      '" alt="Preview" style="max-width:100%;max-height:70vh;cursor:zoom-in;">';
  } else if (ext === "pdf") {
    html =
      '<iframe src="' +
      url +
      '" style="width:100%;height:70vh;" frameborder="0"></iframe>';
  } else {
    // Download link removed per request — show placeholder text instead
    html = '<div class="text-muted">File cannot be previewed.</div>';
  }

  $("#modalPreviewBerkasBody").html(html);
  $("#modalPreviewBerkas").modal("show");
};

// === Untuk ADMIN: Validasi Manual Berkas ===

function reloadAfterAction() {
  // Reload only the DataTable rows (no full page reload)
  try {
    if ($.fn.DataTable.isDataTable(".datatable-berkas")) {
      var sel = $(".filter-kelas");
      var prevKelas = sel.length ? sel.val() : null;
      // persist the selected kelas so we can restore even if options are rebuilt
      if (prevKelas !== null && sel.length) {
        try {
          localStorage.setItem("berkas_filter_kelas", prevKelas);
        } catch (e) {}
      }
      var tbl = $(".datatable-berkas").DataTable();
      console.log(
        "reloadAfterAction: reloading .datatable-berkas with filter",
        prevKelas
      );
      tbl.ajax.reload(function () {
        // restore filter value (if DOM was changed by other code)
        try {
          var stored = null;
          try {
            stored = localStorage.getItem("berkas_filter_kelas");
          } catch (e) {
            stored = prevKelas;
          }
          if (stored === null) stored = prevKelas;
          if (stored !== null && sel.length) {
            // if option doesn't exist (options were rebuilt), re-add it so we can select it
            if (sel.find('option[value="' + stored + '"]').length === 0) {
              // Use stored value as text if label not available
              sel.append($("<option>", { value: stored, text: stored }));
            }
            sel.val(stored);
            // trigger change so any dependent handlers react
            sel.trigger("change");
          }
        } catch (e) {
          console.error("reloadAfterAction restore error", e);
        }
      }, false);
    }
  } catch (e) {
    console.error("reloadAfterAction error", e);
  }
}

$(document).on("change", ".select-validasi-berkas", function () {
  const select = $(this);
  const user_id = select.data("user");
  const status = select.val();
  select.prop("disabled", true);
  // If status requires note, prompt admin for keterangan
  var needsNote = status === "tidak_valid" || status === "revisi";
  var keterangan = "";
  if (needsNote) {
    keterangan = prompt(
      'Masukkan keterangan / alasan untuk status "' + status + '" (wajib):',
      select.data("keterangan") || ""
    );
    if (keterangan === null) {
      // user cancelled, revert select
      select.prop("disabled", false);
      // revert to previous value if available
      if (select.data("current") !== undefined)
        select.val(select.data("current"));
      return;
    }
    keterangan = keterangan.trim();
    if (!keterangan) {
      alert(
        "Keterangan diperlukan untuk penolakan atau revisi. Silakan coba lagi."
      );
      select.prop("disabled", false);
      if (select.data("current") !== undefined)
        select.val(select.data("current"));
      return;
    }
  }

  $.ajax({
    url: "./mod/berkas/proses.php?action=validasi_berkas",
    type: "POST",
    data: { user_id: user_id, status: status, keterangan: keterangan },
    success: function (response) {
      const res = response.trim();
      if (res === "success") {
        swal({
          title: "Berhasil!",
          text: "Status validasi berkas diperbarui.",
          icon: "success",
          timer: 1200,
        });
        // Immediately reload only the DataTable to reflect changes
        reloadAfterAction();
      } else {
        swal({
          title: "Gagal!",
          text: res,
          icon: "error",
          timer: 2000,
        });
      }
    },
    error: function () {
      swal({
        title: "Error!",
        text: "Terjadi kesalahan server.",
        icon: "error",
        timer: 2000,
      });
    },
    complete: function () {
      select.prop("disabled", false);
    },
  });
});

/* === Untuk ADMIN: Lihat Data Berkas Siswa === */

/* Load DataTable */
loadDataBerkas();
function loadDataBerkas() {
  // Destroy jika sudah ada
  if ($.fn.DataTable.isDataTable(".datatable-berkas")) {
    $(".datatable-berkas").DataTable().destroy();
  }
  // Ambil user_id dari URL jika ada
  function getUrlParameter(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
    var results = regex.exec(window.location.search);
    return results === null
      ? null
      : decodeURIComponent(results[1].replace(/\+/g, " "));
  }
  var user_id = getUrlParameter("user_id");
  var dt = $(".datatable-berkas").DataTable({
    scrollX: true,
    processing: true,
    serverSide: true,
    ajax: {
      url:
        "./mod/berkas/datatable.php" +
        (user_id ? "?user_id=" + encodeURIComponent(user_id) : ""),
      type: "POST",
      data: function (d) {
        if (!user_id) {
          d.kelas = $(".filter-kelas").val();
        }
      },
      dataSrc: function (json) {
        // update statistik kartu jika tersedia
        if (json && json.statusStat) {
          try {
            $("#berkas-stat-total").text(json.statusStat.total || 0);
            $("#berkas-stat-valid").text(json.statusStat.valid || 0);
            $("#berkas-stat-tidak").text(json.statusStat.tidak_valid || 0);
            $("#berkas-stat-revisi").text(json.statusStat.revisi || 0);
            $("#berkas-stat-belum").text(json.statusStat.belum || 0);
          } catch (e) {
            console.error("Update stats failed", e);
          }
        }
        // Expose flag to client so we can tailor UI for wali kelas (view-only)
        try {
          window.is_wali_kelas = !!(json && json.isWaliKelas);
        } catch (e) {
          window.is_wali_kelas = false;
        }
        return json.data || json.aaData || [];
      },
    },
    pageLength: 25,
    lengthMenu: [
      [25, 50, 100, -1],
      [25, 50, 100, "All"],
    ],
    language: {
      paginate: {
        previous: "<i class='fas fa-angle-left'></i>",
        next: "<i class='fas fa-angle-right'></i>",
      },
      search: "",
      searchPlaceholder: "Cari nama, NISN, kelas, atau dokumen...",
    },
    columnDefs: [
      {
        targets: "_all",
        className: "text-center align-middle",
      },
    ],
    initComplete: function () {
      // Jika user_id ada di URL, sembunyikan filter kelas dan search
      if (user_id) {
        $(".filter-kelas").closest("div").hide();
        // Ambil NISN dari backend, lalu set ke kolom search DataTable
        $.ajax({
          url: "./mod/berkas/get_nisn.php",
          type: "POST",
          data: { user_id: user_id },
          dataType: "json",
          success: function (res) {
            if (res && res.nisn) {
              // Set kolom search dan trigger pencarian
              var searchBox = $(
                ".datatable-berkas_filter input[type='search']"
              );
              searchBox.val(res.nisn);
              searchBox.trigger("keyup");
            }
          },
        });
      }
    },
  });

  // Customisasi form search agar lebih rapi dan konsisten Bootstrap
  var table = $(".datatable-berkas").DataTable();
  var searchBox = $(".datatable-berkas_filter input[type='search']");
  searchBox
    .addClass("form-control form-control-sm d-inline-block")
    .attr("placeholder", "Cari nama, NISN, kelas, atau dokumen...")
    .css({ width: "260px", "max-width": "100%", "margin-left": "8px" });
  $(".datatable-berkas_filter label")
    .addClass("mb-2 fw-bold")
    .contents()
    .filter(function () {
      return this.nodeType === 3;
    })
    .remove();
  $('[data-toggle="tooltip"]').tooltip();
}

// Jalankan saat halaman siap
$(document).ready(function () {
  // Restore previously selected kelas filter (if any) so loadDataBerkas uses it
  try {
    var storedFilter = null;
    try {
      storedFilter = localStorage.getItem("berkas_filter_kelas");
    } catch (e) {
      storedFilter = null;
    }
    var sel = $(".filter-kelas");
    if (storedFilter && sel.length) {
      if (sel.find('option[value="' + storedFilter + '"]').length === 0) {
        sel.append($("<option>", { value: storedFilter, text: storedFilter }));
      }
      sel.val(storedFilter);
    }
  } catch (e) {
    console.error("restore filter error", e);
  }

  loadDataBerkas();
  // Event filter kelas
  $(".filter-kelas").on("change", function () {
    // persist selection so other flows can restore it after table reloads
    try {
      localStorage.setItem("berkas_filter_kelas", $(this).val());
    } catch (e) {}
    loadDataBerkas();
  });
});

// === Untuk ADMIN: Hapus Berkas ===
$(document).on("click", ".btn-hapus-berkas", function () {
  const button = $(this);
  const user_id = button.data("id");
  const nama = button.data("name") || "";
  swal({
    title: "Hapus Semua Berkas?",
    text: `Semua berkas milik ${nama} akan dihapus permanen. Lanjutkan?`,
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Hapus",
        value: true,
        visible: true,
        className: "",
        closeModal: false,
      },
    },
    dangerMode: true,
  }).then(function (willDelete) {
    if (willDelete) {
      $.ajax({
        url: "./mod/berkas/proses.php?action=delete_berkas",
        type: "POST",
        data: { user_id: user_id },
        success: function (response) {
          const res = response.trim();
          if (res === "success") {
            swal({
              title: "Berhasil!",
              text: "Semua berkas berhasil dihapus.",
              icon: "success",
              timer: 1200,
            });
            // Immediately reload only the DataTable to reflect changes
            reloadAfterAction();
          } else {
            swal({
              title: "Gagal!",
              text: res,
              icon: "error",
              timer: 2000,
            });
          }
        },
        error: function () {
          swal({
            title: "Error!",
            text: "Terjadi kesalahan server.",
            icon: "error",
            timer: 2000,
          });
        },
      });
    }
  });
});
