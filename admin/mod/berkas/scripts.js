"use strict";

function ensureBerkasTooltipStyle() {
  if (document.getElementById("berkas-tooltip-style")) return;
  var style = document.createElement("style");
  style.id = "berkas-tooltip-style";
  style.type = "text/css";
  style.textContent = ".berkas-action-tooltip{pointer-events:none!important;}";
  document.head.appendChild(style);
}

function initBerkasActionTooltips() {
  ensureBerkasTooltipStyle();

  var targets = $(".datatable-berkas .btn-tooltip[data-toggle='tooltip']");
  if (!targets.length) return;

  targets.tooltip("dispose");
  targets.tooltip({
    container: "body",
    trigger: "hover",
    boundary: "window",
    placement: "top",
    template:
      '<div class="tooltip berkas-action-tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>',
  });
}

function ensureBerkasModalLayerStyle() {
  if (document.getElementById("berkas-modal-layer-style")) return;
  var style = document.createElement("style");
  style.id = "berkas-modal-layer-style";
  style.type = "text/css";
  style.textContent =
    "#modalPreviewBerkas,#modalLihatSemuaBerkas{z-index:2060!important;}" +
    ".modal-backdrop.berkas-modal-backdrop{z-index:2050!important;}" +
    ".swal-overlay{z-index:2070!important;}" +
    ".swal-modal{z-index:2080!important;}";
  document.head.appendChild(style);
}

// Restore modal state after swal/viewer closes (prevents modal from becoming disabled)
function restoreBerkasModalState() {
  var $openModal = $("#modalPreviewBerkas.show,#modalLihatSemuaBerkas.show");
  if ($openModal.length > 0) {
    $("body").addClass("modal-open");
    // Ensure backdrop exists
    if (!$(".modal-backdrop").length) {
      $("body").append(
        '<div class="modal-backdrop fade show berkas-modal-backdrop"></div>',
      );
    }
    $openModal.css("overflow-y", "auto");
  }
}

function prepareBerkasModals() {
  ["#modalPreviewBerkas", "#modalLihatSemuaBerkas"].forEach(function (sel) {
    var $modal = $(sel);
    if ($modal.length && !$modal.parent().is("body")) {
      $modal.appendTo("body");
    }
  });
}

function enforceBerkasModalLayer() {
  var $openBerkasModals = $(
    "#modalPreviewBerkas.show,#modalLihatSemuaBerkas.show",
  );
  if ($openBerkasModals.length > 0) {
    $(".modal-backdrop").addClass("berkas-modal-backdrop");
    $("body").addClass("modal-open");
  }
}

function openBerkasModal(selector) {
  prepareBerkasModals();
  var $modal = $(selector);
  if (!$modal.length) return;
  // Disable enforceFocus so swal/viewer overlays are not blocked by Bootstrap modal
  $modal.modal({ backdrop: true, keyboard: true, show: true, focus: false });
  $.fn.modal.Constructor.prototype._enforceFocus = function () {};
  setTimeout(enforceBerkasModalLayer, 0);
}

// === Lihat Semua Berkas Modal - Per-Document Validation ===
$(document).on("click", ".btn-lihat-semua-berkas", function () {
  var berkasData = $(this).data("berkas");
  var namaSiswa = $(this).data("nama");
  var nisnSiswa = $(this).data("nisn") || "";
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

  var alasanOptions = [
    "Dokumen tidak sesuai",
    "Perbedaan data KK dan Ijazah",
    "Data identitas tidak cocok",
    "Dokumen kurang jelas",
    "Dokumen terpotong",
    "Dokumen tidak lengkap",
    "Format dokumen tidak sesuai",
  ];

  function docStatusBadge(status) {
    if (status === "valid")
      return '<span class="badge badge-success">Valid</span>';
    if (status === "tidak_valid")
      return '<span class="badge badge-danger">Tidak Valid</span>';
    if (status === "")
      return '<span class="badge badge-secondary">Belum</span>';
    return "";
  }

  // Build card for each document
  var allowedKeys = Object.keys(berkasLabels);
  var cardsHtml = '<div class="row">';

  allowedKeys.forEach(function (jenis) {
    var filename = berkasData[jenis];
    if (!filename) return;

    var label = berkasLabels[jenis] || jenis.toUpperCase();
    var ext = filename.split(".").pop().toLowerCase();
    var url = "../content/berkas/" + encodeURIComponent(filename);

    var currentDocValid = berkasData[jenis + "_valid"] || "";
    var currentDocKet = berkasData[jenis + "_keterangan"] || "";

    cardsHtml += '<div class="col-12 col-md-6 mb-3">';
    cardsHtml +=
      '<div class="card doc-validation-card border shadow-sm h-100" data-jenis="' +
      jenis +
      '">';
    cardsHtml +=
      '<div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">';
    cardsHtml += '<h6 class="mb-0 font-weight-bold">' + label + "</h6>";
    cardsHtml +=
      '<div class="doc-status-badge">' +
      docStatusBadge(currentDocValid) +
      "</div>";
    cardsHtml += "</div>";
    cardsHtml += '<div class="card-body">';

    // Preview + Download area
    cardsHtml += '<div class="text-center mb-3 bg-light rounded p-2 p-md-3">';
    if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].indexOf(ext) !== -1) {
      cardsHtml +=
        '<div class="berkas-preview-slot mb-2" data-url="' +
        url +
        '" data-ext="' +
        ext +
        '" data-label="' +
        label +
        '">';
      cardsHtml +=
        '<button type="button" class="btn btn-outline-primary btn-load-berkas-preview">';
      cardsHtml += '<i class="fas fa-eye mr-1"></i>Muat Preview</button></div>';
      // Download button
      cardsHtml +=
        '<a href="' +
        url +
        '" class="btn btn-outline-secondary" target="_blank" download><i class="fas fa-download mr-1"></i>Unduh</a>';
    } else if (ext === "pdf") {
      cardsHtml +=
        '<div class="berkas-preview-slot mb-2" data-url="' +
        url +
        '" data-ext="' +
        ext +
        '" data-label="' +
        label +
        '">';
      cardsHtml +=
        '<button type="button" class="btn btn-outline-primary btn-load-berkas-preview">';
      cardsHtml +=
        '<i class="fas fa-file-pdf mr-1"></i>Muat Preview PDF</button></div>';
      // Download button
      cardsHtml +=
        '<a href="' +
        url +
        '" class="btn btn-outline-secondary" target="_blank" download><i class="fas fa-download mr-1"></i>Unduh</a>';
    } else {
      cardsHtml +=
        '<div class="p-4 border rounded" style="box-shadow:0 4px 8px rgba(0,0,0,0.1);">';
      cardsHtml +=
        '<i class="fas fa-file text-secondary" style="font-size:80px;"></i></div>';
      cardsHtml +=
        '<div class="text-muted mt-1">File tidak dapat dipreview.</div>';
    }
    cardsHtml += "</div>";

    // Per-document validation buttons
    cardsHtml += '<div class="doc-validation-actions text-center">';
    cardsHtml +=
      '<div class="btn-group flex-wrap justify-content-center" role="group" aria-label="Status validasi ' +
      label +
      '">';

    var docOpts = [
      {
        val: "valid",
        label: "Valid",
        btnClass: "btn-outline-success",
        activeClass: "btn-success",
        icon: "fas fa-check-circle",
      },
      {
        val: "tidak_valid",
        label: "Tidak Valid",
        btnClass: "btn-outline-danger",
        activeClass: "btn-danger",
        icon: "fas fa-times-circle",
      },
    ];

    docOpts.forEach(function (opt) {
      var isActive = currentDocValid === opt.val;
      var cls = isActive ? opt.activeClass : opt.btnClass;
      cardsHtml +=
        '<button type="button" class="btn ' +
        cls +
        " doc-validation-btn" +
        (isActive ? " active" : "") +
        '" ';
      cardsHtml += 'data-jenis="' + jenis + '" data-status="' + opt.val + '">';
      cardsHtml += '<i class="' + opt.icon + ' mr-1"></i>' + opt.label;
      cardsHtml += "</button>";
    });

    // Reset button
    cardsHtml +=
      '<button type="button" class="btn btn-outline-secondary doc-validation-btn" ';
    cardsHtml +=
      'data-jenis="' +
      jenis +
      '" data-status="" title="Reset validasi dokumen ini">';
    cardsHtml += '<i class="fas fa-undo mr-1"></i>Reset';
    cardsHtml += "</button>";

    cardsHtml += "</div>"; // btn-group
    cardsHtml += "</div>"; // doc-validation-actions

    // Per-document keterangan (alasan) section
    cardsHtml +=
      '<div class="doc-keterangan-wrapper mt-3 p-3 bg-light rounded" id="doc-ket-' +
      jenis +
      '" style="display:' +
      (currentDocValid === "tidak_valid" ? "block" : "none") +
      ';">';
    cardsHtml +=
      '<label class="mb-2"><strong>Alasan</strong> <span class="text-muted font-weight-normal">(boleh lebih dari 1)</span></label>';
    cardsHtml += '<div class="doc-reason-checkboxes d-flex flex-wrap">';
    alasanOptions.forEach(function (alasan, i) {
      var checked = currentDocKet.indexOf(alasan) !== -1 ? "checked" : "";
      cardsHtml += '<div class="custom-control custom-checkbox mr-3 mb-1">';
      cardsHtml +=
        '<input type="checkbox" class="custom-control-input doc-reason-checkbox" id="doc-reason-' +
        jenis +
        "-" +
        i +
        '" data-jenis="' +
        jenis +
        '" value="' +
        alasan +
        '" ' +
        checked +
        ">";
      cardsHtml +=
        '<label class="custom-control-label" for="doc-reason-' +
        jenis +
        "-" +
        i +
        '"><small>' +
        alasan +
        "</small></label>";
      cardsHtml += "</div>";
    });
    cardsHtml += "</div>"; // reason-checkboxes
    cardsHtml += "</div>"; // doc-keterangan-wrapper

    cardsHtml += "</div>"; // card-body
    cardsHtml += "</div>"; // card
    cardsHtml += "</div>"; // col
  });

  cardsHtml += "</div>"; // row

  if (cardsHtml === '<div class="row"></div>' || !cardsHtml) {
    cardsHtml =
      '<div class="text-center py-5"><i class="fas fa-folder-open text-muted" style="font-size:64px;"></i><p class="mt-3 text-muted">Belum ada berkas yang diupload.</p></div>';
  }

  var totalDocs = $("<div>" + cardsHtml + "</div>").find(
    ".doc-validation-card",
  ).length;

  // Summary bar
  var summaryHtml = "";
  summaryHtml +=
    '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between bg-light rounded p-3 mb-3 border">';
  summaryHtml += '<div class="d-flex align-items-center mb-2 mb-md-0">';
  summaryHtml +=
    '<div><i class="fas fa-file-alt text-primary mr-2" style="font-size:1.25rem;"></i></div>';
  summaryHtml += "<div>";
  summaryHtml +=
    '<h6 class="mb-0 font-weight-bold">Review & Validasi Berkas</h6>';
  summaryHtml +=
    '<small class="text-muted">' +
    (nisnSiswa ? nisnSiswa + " - " : "") +
    namaSiswa +
    " &middot; " +
    totalDocs +
    " dokumen</small>";
  summaryHtml += "</div>";
  summaryHtml += "</div>";
  summaryHtml +=
    '<div class="d-flex align-items-center flex-wrap" style="gap:8px;">';
  summaryHtml +=
    '<div class="d-flex align-items-center"><small class="text-muted mr-2">Status:</small>' +
    '<span id="overall-validation-status">' +
    docStatusBadge(validasi || "") +
    "</span></div>";
  if (!window.is_wali_kelas) {
    summaryHtml +=
      '<button type="button" class="btn btn-primary btn-sm" id="btn-save-all-doc-validation" data-user="' +
      userId +
      '">';
    summaryHtml += '<i class="fas fa-save mr-1"></i> Simpan';
    summaryHtml += "</button>";
  } else {
    summaryHtml += '<span class="badge badge-secondary">Mode Lihat Saja</span>';
  }
  summaryHtml += "</div>";
  summaryHtml += "</div>";

  // Set modal content
  $("#modalLihatSemuaBerkasBody").html(summaryHtml + cardsHtml);
  $("#modalLihatSemuaBerkasBody").append(
    '<div id="modal-validasi-berkas-status" class="mt-2"></div>',
  );

  openBerkasModal("#modalLihatSemuaBerkas");

  // Auto-load first preview
  var $firstLoader = $(
    "#modalLihatSemuaBerkasBody .btn-load-berkas-preview",
  ).first();
  if ($firstLoader.length) {
    $firstLoader.trigger("click");
  }
});

// Preview handler for berkas images - loads image/PDF into preview slot
$(document).on("click", ".btn-load-berkas-preview", function () {
  var $button = $(this);
  var $slot = $button.closest(".berkas-preview-slot");
  if (!$slot.length || $slot.data("loaded")) return;

  var url = $slot.data("url");
  var ext = ($slot.data("ext") || "").toLowerCase();
  var label = $slot.data("label") || "Preview";

  if (!url) return;

  if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].indexOf(ext) !== -1) {
    var imgId =
      "berkas-img-" + Date.now() + "-" + Math.floor(Math.random() * 1000);
    $slot.html(
      '<img id="' +
        imgId +
        '" src="' +
        url +
        '" alt="' +
        label +
        '" class="img-fluid border berkas-zoomable" loading="lazy" style="max-width:100%;max-height:500px;box-shadow:0 4px 8px rgba(0,0,0,0.1);cursor:zoom-in;" title="Klik untuk zoom">',
    );
  } else if (ext === "pdf") {
    $slot.html(
      '<iframe src="' +
        url +
        '" loading="lazy" style="width:100%;height:500px;border:1px solid #ddd;box-shadow:0 4px 8px rgba(0,0,0,0.1);" frameborder="0"></iframe>',
    );
  }

  $slot.data("loaded", true);
});

// Per-document validation button click
$(document).on("click", ".doc-validation-btn", function () {
  var btn = $(this);
  var jenis = btn.data("jenis");
  var status = btn.data("status");
  var card = btn.closest(".doc-validation-card");

  // Reset all buttons in this card to outline style
  card
    .find(".doc-validation-btn")
    .removeClass(
      "active btn-success btn-danger btn-outline-success btn-outline-danger",
    )
    .addClass("btn-outline-secondary");

  if (status === "valid") {
    btn
      .removeClass("btn-outline-secondary btn-outline-success")
      .addClass("active btn-success");
  } else if (status === "tidak_valid") {
    btn
      .removeClass("btn-outline-secondary btn-outline-danger")
      .addClass("active btn-danger");
  }

  // Toggle keterangan wrapper for this card (only for tidak_valid)
  var ketWrapper = card.find(".doc-keterangan-wrapper");
  if (status === "tidak_valid") {
    ketWrapper.show();
  } else {
    ketWrapper.hide();
  }

  // Update individual badge in card header
  var badgeHtml = "";
  if (status === "valid")
    badgeHtml = '<span class="badge badge-success">Valid</span>';
  else if (status === "tidak_valid")
    badgeHtml = '<span class="badge badge-danger">Tidak Valid</span>';
  else badgeHtml = '<span class="badge badge-secondary">Belum</span>';
  card.find(".doc-status-badge").html(badgeHtml);

  // Recompute overall status
  updateOverallValidationStatus();
});

// Helper to collect per-document data
function collectAllDocValidations() {
  var docs = [];
  $(".doc-validation-card").each(function () {
    var jenis = $(this).data("jenis");
    var activeBtn = $(this).find(".doc-validation-btn.active").first();
    var status = activeBtn.length ? activeBtn.data("status") : "";
    var reasons = [];
    $(this)
      .find(".doc-reason-checkbox:checked")
      .each(function () {
        reasons.push($(this).val());
      });
    docs.push({
      jenis: jenis,
      status: status,
      keterangan: reasons.join("; "),
    });
  });
  return docs;
}

function updateOverallValidationStatus() {
  var docs = collectAllDocValidations();
  var statuses = docs.map(function (d) {
    return d.status;
  });

  var overall = "";
  if (statuses.indexOf("tidak_valid") !== -1) {
    overall = "tidak_valid";
  } else if (
    statuses.length > 0 &&
    statuses.indexOf("") === -1 &&
    statuses.indexOf("tidak_valid") === -1
  ) {
    overall = "valid";
  }

  var badgeHtml = "";
  if (overall === "valid")
    badgeHtml = '<span class="badge badge-success">Valid</span>';
  else if (overall === "tidak_valid")
    badgeHtml = '<span class="badge badge-danger">Tidak Valid</span>';
  else
    badgeHtml = '<span class="badge badge-secondary">Belum Divalidasi</span>';

  $("#overall-validation-status").html(badgeHtml);
}

// Save all per-document validations at once
$(document).on("click", "#btn-save-all-doc-validation", function () {
  var btn = $(this);
  var user_id = btn.data("user");
  var docs = collectAllDocValidations();

  var docsToSave = docs.filter(function (d) {
    return d.status !== "";
  });

  if (docsToSave.length === 0) {
    $("#modal-validasi-berkas-status").html(
      '<div class="alert alert-warning py-2 mb-0"><i class="fas fa-exclamation-circle mr-2"></i>Belum ada perubahan status validasi. Silakan pilih status untuk setiap dokumen.</div>',
    );
    return;
  }

  // Validate: if any doc has tidak_valid without reason, warn
  var missingReason = false;
  docsToSave.forEach(function (d) {
    if (d.status === "tidak_valid" && d.keterangan.trim() === "") {
      missingReason = true;
    }
  });

  function showFinalSaveConfirm() {
    swal({
      title: "Simpan Semua Validasi?",
      text:
        "Status validasi untuk " +
        docsToSave.length +
        " dokumen akan disimpan.",
      icon: "warning",
      buttons: {
        cancel: "Batal",
        confirm: {
          text: "Simpan",
          value: true,
          visible: true,
          closeModal: true,
        },
      },
      dangerMode: false,
    }).then(function (confirmed) {
      restoreBerkasModalState();
      if (!confirmed) return;

      btn
        .prop("disabled", true)
        .html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

      $.ajax({
        url: "./mod/berkas/proses.php?action=validasi_dokumen",
        type: "POST",
        data: {
          user_id: user_id,
          dokumen: docsToSave,
        },
        dataType: "text",
        success: function (response) {
          var res = response.trim();
          if (res === "success") {
            $("#modal-validasi-berkas-status").html(
              '<div class="alert alert-success py-2 mb-0"><i class="fas fa-check-circle mr-2"></i>Validasi per dokumen berhasil disimpan.</div>',
            );
            setTimeout(function () {
              reloadAfterAction();
              $("#modalLihatSemuaBerkas").modal("hide");
            }, 1500);
          } else {
            $("#modal-validasi-berkas-status").html(
              '<div class="alert alert-danger py-2 mb-0"><i class="fas fa-exclamation-circle mr-2"></i>Gagal: ' +
                res +
                "</div>",
            );
          }
        },
        error: function () {
          $("#modal-validasi-berkas-status").html(
            '<div class="alert alert-danger py-2 mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Terjadi kesalahan server.</div>',
          );
        },
        complete: function () {
          btn
            .prop("disabled", false)
            .html('<i class="fas fa-save mr-1"></i> Simpan');
        },
      });
    });
  }

  if (missingReason) {
    swal({
      title: "Perhatian",
      text: "Ada dokumen dengan status Tidak Valid tanpa alasan. Lanjutkan tetap menyimpan?",
      icon: "warning",
      buttons: {
        cancel: "Batal",
        confirm: {
          text: "Lanjutkan",
          value: true,
          visible: true,
          closeModal: true,
        },
      },
      dangerMode: true,
    }).then(function (willContinue) {
      restoreBerkasModalState();
      if (!willContinue) return;
      showFinalSaveConfirm();
    });
    return;
  }

  showFinalSaveConfirm();
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
          restoreBerkasModalState();
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

  if (
    !$("#modalPreviewBerkas").hasClass("show") &&
    !$("#modalLihatSemuaBerkas").hasClass("show")
  ) {
    $("body")
      .removeClass("modal-open")
      .css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop").remove();
  }
});

// === Untuk ADMIN: Reload DataTable ===

function reloadAfterAction() {
  // Reload only the DataTable rows (no full page reload)
  try {
    if ($.fn.DataTable.isDataTable(".datatable-berkas")) {
      var tbl = $(".datatable-berkas").DataTable();
      tbl.ajax.reload(null, false);
    }
  } catch (e) {
    console.error("reloadAfterAction error", e);
  }
}

/* === Untuk ADMIN: Lihat Data Berkas Siswa === */

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
          d.status = $(".filter-status").val();
        }
      },
      dataSrc: function (json) {
        // update statistik kartu jika tersedia
        if (json && json.statusStat) {
          try {
            $("#berkas-stat-total").text(json.statusStat.total || 0);
            $("#berkas-stat-valid").text(json.statusStat.valid || 0);
            $("#berkas-stat-tidak").text(json.statusStat.tidak_valid || 0);
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
        $(".btn-open-filter-kelas").hide();
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
                ".datatable-berkas_filter input[type='search']",
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

  // Keep tooltip title visible but never block action button clicks.
  initBerkasActionTooltips();
  $(".datatable-berkas")
    .off("draw.dt.berkasTooltip")
    .on("draw.dt.berkasTooltip", function () {
      initBerkasActionTooltips();
    });
}

// Jalankan saat halaman siap
$(document).ready(function () {
  $("body").addClass("page-user-module");
  ensureBerkasModalLayerStyle();
  prepareBerkasModals();

  $(document)
    .off("shown.bs.modal.berkasLayer")
    .on(
      "shown.bs.modal.berkasLayer",
      "#modalPreviewBerkas,#modalLihatSemuaBerkas",
      function () {
        enforceBerkasModalLayer();
      },
    );

  // Restore previously selected kelas filter (if any) so loadDataBerkas uses it
  try {
    var storedFilter = null;
    try {
      storedFilter = localStorage.getItem("berkas_filter_kelas");
    } catch (e) {
      storedFilter = null;
    }
    if (storedFilter) {
      $(".filter-kelas").val(storedFilter);
    }
  } catch (e) {
    console.error("restore filter error", e);
  }
  // Restore status filter
  try {
    var storedStatus = localStorage.getItem("berkas_filter_status");
    if (storedStatus) {
      $(".filter-status").val(storedStatus);
    }
  } catch (e) {}

  // Initial label
  updateBerkasFilterLabel();

  loadDataBerkas();

  // === Filter Modal Handlers (like user module) ===

  // Open filter modal
  $(document).on("click", ".btn-open-filter-kelas", function (e) {
    e.preventDefault();
    // Sync current values to modal
    var currentKelas = $(".filter-kelas").val() || "";
    var currentStatus = $(".filter-status").val() || "";
    $(".modal-filter-kelas-select").val(currentKelas);
    $(".modal-status-select").val(currentStatus);
    $(".modal-filter-kelas").modal({
      backdrop: false,
      keyboard: true,
      show: true,
    });
    setTimeout(function () {
      $("body")
        .removeClass("modal-open")
        .css({ "padding-right": "", overflow: "" });
      $(".modal-backdrop").remove();
    }, 30);
    return false;
  });

  // Terapkan filter
  $(document).on("click", ".btn-apply-filter-kelas", function () {
    var $modal = $(".modal-filter-kelas");
    var selectedKelasVal =
      $modal.find(".modal-filter-kelas-select").val() || "";
    var selectedStatusVal = $modal.find(".modal-status-select").val() || "";

    $(".filter-kelas").val(selectedKelasVal);
    $(".filter-status").val(selectedStatusVal);

    // Persist
    try {
      localStorage.setItem("berkas_filter_kelas", selectedKelasVal);
    } catch (e) {}
    try {
      localStorage.setItem("berkas_filter_status", selectedStatusVal);
    } catch (e) {}

    updateBerkasFilterLabel();
    $modal.modal("hide");

    setTimeout(function () {
      loadDataBerkas();
    }, 220);
  });

  // Reset filter
  $(document).on("click", ".btn-reset-filter-kelas", function () {
    var $modal = $(".modal-filter-kelas");
    $modal.find(".modal-filter-kelas-select").val("");
    $modal.find(".modal-status-select").val("");
    $(".filter-kelas").val("");
    $(".filter-status").val("");

    try {
      localStorage.setItem("berkas_filter_kelas", "");
    } catch (e) {}
    try {
      localStorage.setItem("berkas_filter_status", "");
    } catch (e) {}

    updateBerkasFilterLabel();
    $modal.modal("hide");

    setTimeout(function () {
      loadDataBerkas();
    }, 220);
  });

  // Non-blocking modal: keep body unlocked while open
  $(document).on("shown.bs.modal", ".modal-filter-kelas", function () {
    $("body")
      .removeClass("modal-open")
      .css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop").remove();
  });

  // Cleanup on hide
  $(document).on("hidden.bs.modal", ".modal-filter-kelas", function () {
    if ($(".modal.show").length === 0) {
      $("body")
        .removeClass("modal-open")
        .css({ "padding-right": "", overflow: "" });
      $(".modal-backdrop").remove();
    }
  });
});

function updateBerkasFilterLabel() {
  // Icon-only filter button — no label needed. Updates button title for accessibility.
  var kelasVal = $(".filter-kelas").val() || "";
  var statusVal = $(".filter-status").val() || "";
  var parts = ["Filter Kelas & Status"];
  if (kelasVal) {
    var $opt = $(".modal-filter-kelas-select option[value='" + kelasVal + "']");
    parts.push("Kelas: " + ($opt.length ? $opt.text() : kelasVal));
  }
  if (statusVal) {
    var statusLabel = "";
    switch (statusVal) {
      case "valid":
        statusLabel = "Valid";
        break;
      case "tidak_valid":
        statusLabel = "Tidak Valid";
        break;
      case "belum":
        statusLabel = "Belum Divalidasi";
        break;
    }
    if (statusLabel) parts.push("Status: " + statusLabel);
  }
  $(".btn-open-filter-kelas").attr("title", parts.join(" | "));
}

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
