$(document).ready(function () {
  "use strict";

  // Global variables
  var table = null;
  var currentModal = null;
  var viewerInstances = {
    kks: null,
    kip: null,
  };

  // Debug: capture clicks to help diagnose unresponsive buttons
  document.addEventListener(
    "click",
    function (e) {
      try {
        if (e && e.target) {
          console.debug(
            "[usulan-pip] click captured:",
            e.target.tagName,
            e.target.className || "",
            e.target.getAttribute && e.target.getAttribute("data-id"),
          );
        }
      } catch (err) {}
    },
    true,
  );

  // Utility functions
  function loading() {
    $(".btn-save").prop("disabled", true);
    $(".btn-save").html(
      '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...',
    );
    window.setTimeout(function () {
      $(".btn-save").prop("disabled", false);
      $(".btn-save").html('<i class="far fa-save"></i> Simpan');
    }, 2000);
  }

  function stripHtml(html) {
    var doc = new DOMParser().parseFromString(html, "text/html");
    return doc.body.textContent || "";
  }

  function cleanupViewers() {
    if (viewerInstances.kks) {
      try {
        viewerInstances.kks.destroy();
      } catch (e) {}
      viewerInstances.kks = null;
    }
    if (viewerInstances.kip) {
      try {
        viewerInstances.kip.destroy();
      } catch (e) {}
      viewerInstances.kip = null;
    }
  }

  // DataTable initialization
  function initDataTable() {
    if (
      typeof window.jQuery === "undefined" ||
      typeof $.fn.DataTable === "undefined"
    ) {
      setTimeout(initDataTable, 50);
      return;
    }

    table = $(".datatable").DataTable({
      scrollX: false,
      processing: true,
      serverSide: true,
      bAutoWidth: true,
      bSort: false,
      bStateSave: true,
      bDestroy: true,
      paging: true,
      pageLength: 25,
      lengthMenu: [
        [25, 30, 50, -1],
        [25, 30, 50, "All"],
      ],
      searching: true,
      language: {
        paginate: {
          previous: "<i class='fas fa-angle-left'>",
          next: "<i class='fas fa-angle-right'>",
        },
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoFiltered: "",
      },
      ajax: {
        url: "./mod/usulan-pip-ranking/datatable.php",
        type: "POST",
        data: function (d) {
          d.kelas = $(".filter-kelas").val();
          d.status = $(".filter-status").val();
          d.dapodik = $(".filter-dapodik").val();
        },
        dataSrc: function (json) {
          console.debug("[usulan-pip] datatable ajax dataSrc", json);
          return json.data || json.aaData || [];
        },
        error: function (xhr, status, err) {
          console.error("[usulan-pip] datatable ajax error", status, err);
        },
      },
      columnDefs: [{ targets: [0], orderable: false }],
      createdRow: function (row, data) {
        var cleanId = stripHtml(data[0]).trim();
        $(row).addClass("row-" + cleanId);
      },
      initComplete: function (settings, json) {
        console.debug("[usulan-pip] DataTable initComplete", settings, json);
      },
    });
  }

  // Fallback modal styles
  function createFallbackModal() {
    if ($("#fallback-modal-style").length === 0) {
      $("head").append(`
      <style id="fallback-modal-style">
        .fallback-modal-overlay {
          position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
          background: rgba(0,0,0,0.5); z-index: 9999; display: flex; 
          align-items: center; justify-content: center;
        }
        .fallback-modal-content {
          background: white; border-radius: 8px; padding: 20px; 
          max-width: 90%; max-height: 90%; overflow-y: auto;
          box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .fallback-modal-header {
          border-bottom: 1px solid #dee2e6; padding-bottom: 15px; margin-bottom: 15px;
          display: flex; justify-content: space-between; align-items: center;
        }
        .fallback-modal-close {
          background: none; border: none; font-size: 24px; cursor: pointer;
          color: #6c757d; padding: 0; width: 30px; height: 30px;
        }
        .fallback-modal-body { line-height: 1.5; }
        .fallback-modal-footer {
          border-top: 1px solid #dee2e6; padding-top: 15px; margin-top: 15px;
          text-align: right;
        }
        .fallback-btn {
          padding: 8px 16px; margin-left: 8px; border: none; border-radius: 4px;
          cursor: pointer; font-size: 14px;
        }
        .fallback-btn-success { background: #28a745; color: white; }
        .fallback-btn-danger { background: #dc3545; color: white; }
        .fallback-btn-secondary { background: #6c757d; color: white; }
        .fallback-reject-reason { 
          width: 100%; padding: 8px; margin: 10px 0; border: 1px solid #ced4da; 
          border-radius: 4px; resize: vertical; display: none;
        }
      </style>
    `);
    }
  }

  // Modal implementations
  function showBootstrapModal(
    d,
    avatarSrc,
    kkPreview,
    kksPreview,
    kipPreview,
    id,
  ) {
    // Format tanggal jika ada
    var formatDate = function (dateStr) {
      if (!dateStr || dateStr === "-") return "-";
      try {
        var date = new Date(dateStr);
        return date.toLocaleDateString("id-ID");
      } catch (e) {
        return dateStr;
      }
    };

    // Layout modal yang diperbaiki dan dirapihkan
    var modalBodyHtml = `
    <!-- Header Info Card -->
    <div class="card mb-3 border-primary" style="border-width: 2px;">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-md-2 text-center">
            <img src="${avatarSrc}" alt="avatar" class="img-fluid rounded-circle border border-primary mb-2" 
                 style="width:80px;height:80px;object-fit:cover;">
            <h6 class="mb-0 text-primary">${d.user_nama || d.nama_lengkap || "-"}</h6>
            <small class="text-muted">NISN: ${d.user_nisn || d.nisn || "-"}</small>
          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-6 text-center">
                <small class="text-muted d-block">Kelas</small>
                <span class="font-weight-bold">${d.nama_kelas || "-"}</span>
              </div>
              <div class="col-6 text-center">
                <small class="text-muted d-block">Tanggal Pengajuan</small>
                <span class="font-weight-bold">${formatDate(d.tanggal_pengajuan)}</span>
              </div>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <div class="mb-2">
              <small class="text-muted d-block">Status Usulan</small>
              <span class="badge badge-lg ${d.status === "Pending" ? "badge-warning" : d.status === "Disetujui" ? "badge-success" : d.status === "Ditolak" ? "badge-danger" : "badge-secondary"}" 
                    style="font-size:14px;padding:8px 12px;">${d.status || "Pending"}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="row">
      <!-- Data Pribadi -->
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-light py-2">
            <h6 class="mb-0 text-primary"><i class="fas fa-user"></i> Data Pribadi</h6>
          </div>
          <div class="card-body p-3">
            <table class="table table-sm table-borderless mb-3" style="font-size:13px;">
              <tr><td width="40%" class="text-muted">Nama Lengkap</td><td class="font-weight-bold">${d.user_nama || d.nama_lengkap || "-"}</td></tr>
              <tr><td class="text-muted">NISN</td><td class="font-weight-bold">${d.user_nisn || d.nisn || "-"}</td></tr>
              <tr><td class="text-muted">Kelas</td><td class="font-weight-bold">${d.nama_kelas || "-"}</td></tr>
              <tr><td class="text-muted">Tempat Lahir</td><td>${d.tempat_lahir || "-"}</td></tr>
              <tr><td class="text-muted">Tanggal Lahir</td><td>${formatDate(d.tanggal_lahir)}</td></tr>
              <tr><td class="text-muted">Jenis Kelamin</td><td>${d.jenis_kelamin || "-"}</td></tr>
              <tr><td class="text-muted">No. HP</td><td>${d.no_hp || "-"}</td></tr>
            </table>
            
            <div class="border-top pt-3">
              <h6 class="text-secondary mb-2"><i class="fas fa-home"></i> Alamat</h6>
              <p class="mb-0" style="font-size:13px;line-height:1.5;">${d.alamat_lengkap || "-"}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Data Orang Tua -->
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-header bg-light py-2">
            <h6 class="mb-0 text-primary"><i class="fas fa-users"></i> Data Orang Tua</h6>
          </div>
          <div class="card-body p-3">
            <table class="table table-sm table-borderless" style="font-size:13px;">
              <tr><td width="40%" class="text-muted">No. KK</td><td class="font-weight-bold">${d.no_kk || "-"}</td></tr>
              <tr><td class="text-muted">Nama Ayah</td><td class="font-weight-bold">${d.nama_ayah || "-"}</td></tr>
              <tr><td class="text-muted">Pekerjaan Ayah</td><td>${d.pekerjaan_ayah || "-"}</td></tr>
              <tr><td class="text-muted">Penghasilan Ayah</td><td>${d.penghasilan_ayah || "-"}</td></tr>
              <tr><td class="text-muted">Nama Ibu</td><td class="font-weight-bold">${d.nama_ibu || "-"}</td></tr>
              <tr><td class="text-muted">Pekerjaan Ibu</td><td>${d.pekerjaan_ibu || "-"}</td></tr>
              <tr><td class="text-muted">Penghasilan Ibu</td><td>${d.penghasilan_ibu || "-"}</td></tr>
              <tr><td class="text-muted">Nama Wali</td><td class="font-weight-bold">${d.nama_wali || "-"}</td></tr>
              <tr><td class="text-muted">Pekerjaan Wali</td><td>${d.pekerjaan_wali || "-"}</td></tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Usulan PIP -->
    <div class="row mt-3">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-light py-2">
            <h6 class="mb-0 text-primary"><i class="fas fa-file-alt"></i> Data Usulan PIP</h6>
          </div>
          <div class="card-body p-3">
            <div class="row">
              <div class="col-md-6">
                <table class="table table-sm table-borderless" style="font-size:13px;">
                  <tr><td width="40%" class="text-muted">Penerima KPS</td><td class="font-weight-bold">${d.pertanyaan_1 || "-"}</td></tr>
                  <tr><td class="text-muted">Memiliki KIP</td><td class="font-weight-bold">${d.pertanyaan_2 || "-"}</td></tr>
                  <tr><td class="text-muted">No. KKS</td><td><span class="badge badge-info">${d.no_kks || "Belum diisi"}</span></td></tr>
                  <tr><td class="text-muted">No. KIP</td><td><span class="badge badge-info">${d.no_kip || "Belum diisi"}</span></td></tr>
                </table>
              </div>
              <div class="col-md-6">
                <h6 class="text-secondary mb-2">Dokumen Pendukung</h6>
                <div class="mb-2">
                  <small class="text-muted d-block">Berkas KK:</small>
                  ${kkPreview}
                </div>
                <div class="mb-2">
                  <small class="text-muted d-block">Berkas KKS:</small>
                  ${kksPreview}
                </div>
                <div class="mb-2">
                  <small class="text-muted d-block">Berkas KIP:</small>
                  ${kipPreview}
                </div>
              </div>
            </div>
            
            ${
              d.alasan_usulan
                ? `
              <div class="border-top pt-3 mt-3">
                <h6 class="text-secondary mb-2"><i class="fas fa-comment-alt"></i> Alasan Usulan</h6>
                <div class="bg-light p-3 rounded" style="border-left:4px solid #007bff;">
                  <p class="mb-0" style="white-space:pre-line;font-size:13px;line-height:1.6;">${d.alasan_usulan}</p>
                </div>
              </div>
            `
                : ""
            }
            
            ${
              d.alasan_penolakan
                ? `
              <div class="border-top pt-3 mt-3">
                <h6 class="text-danger mb-2"><i class="fas fa-exclamation-triangle"></i> Alasan Penolakan</h6>
                <div class="bg-danger-light p-3 rounded" style="border-left:4px solid #dc3545;background-color:#f8d7da;">
                  <p class="mb-0 text-danger" style="font-size:13px;line-height:1.6;">${d.alasan_penolakan}</p>
                </div>
              </div>
            `
                : ""
            }
            
            ${
              d.keterangan && d.keterangan !== d.alasan_penolakan
                ? `
              <div class="border-top pt-3 mt-3">
                <h6 class="text-secondary mb-2"><i class="fas fa-info-circle"></i> Keterangan</h6>
                <div class="bg-light p-3 rounded">
                  <p class="mb-0" style="font-size:13px;line-height:1.6;">${d.keterangan}</p>
                </div>
              </div>
            `
                : ""
            }
          </div>
        </div>
      </div>
    </div>
  `;
    $("#usulanPipDetailModalBody").html(modalBodyHtml);
    $("#usulanPipDetailModal").addClass("usulan-pip-modal");
    $("#usulanPipDetailModal").modal("show");
  }

  function showFallbackModal(
    d,
    avatarSrc,
    kkPreview,
    kksPreview,
    kipPreview,
    id,
  ) {
    createFallbackModal();

    var modalHtml = `
    <div class="fallback-modal-overlay" id="fallback-modal-${d.usulan_pip_id}">
      <div class="fallback-modal-content" style="width: 800px;">
        <div class="fallback-modal-header">
          <h5>Detail Usulan PIP - ${d.nama_lengkap || "-"}</h5>
          <button class="fallback-modal-close modal-close-btn">&times;</button>
        </div>
        <div class="fallback-modal-body">
          <div style="display: flex; gap: 20px;">
            <div style="flex: 0 0 200px; text-align: center;">
              <img src="${avatarSrc}" alt="avatar" style="max-height:140px; border-radius: 8px; margin-bottom: 15px;">
              <p><strong>NISN:</strong><br>${d.user_nisn || d.nisn || "-"}</p>
              <p><strong>Kelas:</strong><br>${d.nama_kelas || "-"}</p>
            </div>
            <div style="flex: 1;">
              <div style="border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin-bottom: 15px;">
                <h6 style="margin-bottom: 10px; color: #495057;">Informasi Pribadi</h6>
                <table style="width: 100%; line-height: 1.8;">
                  <tr><td style="width: 150px; font-weight: bold;">Nama</td><td>${d.user_nama || d.nama_lengkap || "-"}</td></tr>
                  <tr><td style="font-weight: bold;">Tempat, Tgl Lahir</td><td>${(d.tempat_lahir || "-") + ", " + (d.tanggal_lahir || "-")}</td></tr>
                  <tr><td style="font-weight: bold;">Penghasilan Ayah</td><td>${d.penghasilan_ayah || "-"}</td></tr>
                  <tr><td style="font-weight: bold;">Penghasilan Ibu</td><td>${d.penghasilan_ibu || "-"}</td></tr>
                </table>
              </div>
              <div style="border: 1px solid #dee2e6; border-radius: 4px; padding: 15px;">
                <h6 style="margin-bottom: 10px; color: #495057;">Dokumen & Status</h6>
                <table style="width: 100%; line-height: 1.8;">
                  <tr><td style="width: 150px; font-weight: bold;">Penerima KPS</td><td>${d.pertanyaan_1 || "-"}<br>${kksPreview}</td></tr>
                  <tr><td style="font-weight: bold;">Memiliki KIP</td><td>${d.pertanyaan_2 || "-"}<br>${kipPreview}</td></tr>
                  <tr><td style="font-weight: bold;">Keterangan</td><td>${d.keterangan || "-"}</td></tr>
                  <tr><td style="font-weight: bold;">Alasan Usulan</td><td><span style="white-space:pre-line">${d.alasan_usulan ? d.alasan_usulan : "-"}</span></td></tr>
                  <tr><td style="font-weight: bold;">Tanggal Pengajuan</td><td>${d.tanggal_pengajuan || "-"}</td></tr>
                  <tr><td style="font-weight: bold;">Status</td><td><strong style="color: #007bff;">${d.status || "-"}</strong></td></tr>
                </table>
              </div>
            </div>
          </div>
          <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <div>
              <label style="font-weight: bold; display: block; margin-bottom: 10px;">Tindakan</label>
              <button class="fallback-btn fallback-btn-success btn-approve" data-id="${d.usulan_pip_id}" data-encrypted-id="${id}">✓ Setujui</button>
              <button class="fallback-btn fallback-btn-danger btn-reject" data-id="${d.usulan_pip_id}" data-encrypted-id="${id}">✗ Tolak</button>
            </div>
            <textarea class="fallback-reject-reason reject-reason" rows="3" placeholder="Masukkan alasan penolakan..."></textarea>
            <div style="text-align: right; margin-top: 10px;">
              <button class="fallback-btn fallback-btn-danger btn-submit-reject" data-id="${d.usulan_pip_id}" data-encrypted-id="${id}" style="display: none;">Kirim Penolakan</button>
            </div>
          </div>
        </div>
        <div class="fallback-modal-footer">
          <button class="fallback-btn fallback-btn-secondary modal-close-btn">Tutup</button>
        </div>
      </div>
    </div>
  `;

    $("body").append(modalHtml);
    currentModal = $("#fallback-modal-" + d.usulan_pip_id);
  }

  // Hapus initializeViewers dari event shown.bs.modal
  // Tambahkan handler klik langsung untuk thumbnail gambar KKS/KIP

  // Load custom CSS for diterima if not already loaded
  if (!$('link[href*="usulan-pip-rangking/style.css"]').length) {
    $(
      '<link rel="stylesheet" type="text/css" href="./mod/usulan-pip-ranking/style.css">',
    ).appendTo("head");
  }

  // Modal preview Bootstrap
  if ($("#pipPreviewModal").length === 0) {
    $("body").append(`
      <div class="modal fade" id="pipPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Preview Berkas</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" id="pipPreviewModalBody"></div>
          </div>
        </div>
      </div>
    `);
  }

  // Handler untuk thumbnail gambar KK
  $(document).on("click", ".img-kk-thumb", function (e) {
    e.preventDefault();
    e.stopPropagation();
    var src = $(this).attr("src") || $(this).data("src");
    var ext = src.split(".").pop().toLowerCase();
    var html = "";
    if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(ext)) {
      html = `
        <div style="text-align: center; position: relative;">
          <div style="margin-bottom: 15px;">
            <button type="button" class="btn btn-sm btn-secondary" onclick="rotateImage(-90)">
              <i class="fas fa-undo mr-1"></i> Putar Kiri
            </button>
            <button type="button" class="btn btn-sm btn-secondary ml-2" onclick="rotateImage(90)">
              <i class="fas fa-redo mr-1"></i> Putar Kanan
            </button>
            <button type="button" class="btn btn-sm btn-info ml-2" onclick="resetImage()">
              <i class="fas fa-sync mr-1"></i> Reset
            </button>
          </div>
          <img src="${src}" alt="KK" id="previewImage" style="max-width:95%;max-height:75vh;border-radius:8px;box-shadow:0 2px 8px #0002;transition:transform 0.3s ease;" class="zoomable-image">
        </div>
      `;
    } else if (ext === "pdf") {
      html = `
        <div style="text-align: center; margin-bottom: 15px;">
          <button type="button" class="btn btn-sm btn-secondary" onclick="rotateImage(-90)">
            <i class="fas fa-undo mr-1"></i> Putar Kiri
          </button>
          <button type="button" class="btn btn-sm btn-secondary ml-2" onclick="rotateImage(90)">
            <i class="fas fa-redo mr-1"></i> Putar Kanan
          </button>
          <button type="button" class="btn btn-sm btn-info ml-2" onclick="resetImage()">
            <i class="fas fa-sync mr-1"></i> Reset
          </button>
        </div>
        <div class="pdf-container">
          <iframe src="${src}#toolbar=0&navpanes=0&scrollbar=0&zoom=page-width" width="100%" height="100%" style="border: none; transition: transform 0.3s ease;" id="previewImage"></iframe>
        </div>
      `;
    } else {
      html = `<a href="${src}" target="_blank" class="btn btn-primary">Lihat Berkas</a>`;
    }
    $("#pipPreviewModal .modal-title").html(
      '<i class="fas fa-eye mr-2"></i>Preview Berkas KK',
    );
    $("#pipPreviewModalBody").html(html);
    $("#pipPreviewModal").modal("show");
  });

  // Handler untuk zoom pada gambar di dalam modal preview
  $(document).on("click", ".zoomable-image", function (e) {
    e.stopPropagation();
    $(this).toggleClass("zoomed");
  });

  // Global variables untuk rotasi gambar
  var currentRotation = 0;

  // Fungsi untuk rotasi gambar
  window.rotateImage = function (degrees) {
    currentRotation += degrees;
    var img = document.getElementById("previewImage");
    if (img) {
      img.style.transform = "rotate(" + currentRotation + "deg)";
    }
  };

  // Fungsi untuk reset gambar
  window.resetImage = function () {
    currentRotation = 0;
    var img = document.getElementById("previewImage");
    if (img) {
      img.style.transform = "rotate(0deg)";
      img.classList.remove("zoomed");
    }
  };

  // Handler untuk thumbnail gambar KKS
  $(document).on("click", ".img-kks-thumb", function (e) {
    e.stopPropagation();
    var src = $(this).attr("src") || $(this).data("src");
    var ext = src.split(".").pop().toLowerCase();
    var html = "";
    if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(ext)) {
      html = `
        <div style="text-align: center; position: relative;">
          <div style="margin-bottom: 15px;">
            <button type="button" class="btn btn-sm btn-secondary" onclick="rotateImage(-90)">
              <i class="fas fa-undo mr-1"></i> Putar Kiri
            </button>
            <button type="button" class="btn btn-sm btn-secondary ml-2" onclick="rotateImage(90)">
              <i class="fas fa-redo mr-1"></i> Putar Kanan
            </button>
            <button type="button" class="btn btn-sm btn-info ml-2" onclick="resetImage()">
              <i class="fas fa-sync mr-1"></i> Reset
            </button>
          </div>
          <img src="${src}" alt="KKS" id="previewImage" style="max-width:95%;max-height:75vh;border-radius:8px;box-shadow:0 2px 8px #0002;transition:transform 0.3s ease;" class="zoomable-image">
        </div>
      `;
    } else if (ext === "pdf") {
      html = `
        <div style="text-align: center; margin-bottom: 15px;">
          <button type="button" class="btn btn-sm btn-secondary" onclick="rotateImage(-90)">
            <i class="fas fa-undo mr-1"></i> Putar Kiri
          </button>
          <button type="button" class="btn btn-sm btn-secondary ml-2" onclick="rotateImage(90)">
            <i class="fas fa-redo mr-1"></i> Putar Kanan
          </button>
          <button type="button" class="btn btn-sm btn-info ml-2" onclick="resetImage()">
            <i class="fas fa-sync mr-1"></i> Reset
          </button>
        </div>
        <div class="pdf-container">
          <iframe src="${src}#toolbar=0&navpanes=0&scrollbar=0&zoom=page-width" width="100%" height="100%" style="border: none; transition: transform 0.3s ease;" id="previewImage"></iframe>
        </div>
      `;
    } else {
      html = `<a href="${src}" target="_blank" class="btn btn-primary">Lihat Berkas</a>`;
    }
    $("#pipPreviewModal .modal-title").html(
      '<i class="fas fa-eye mr-2"></i>Preview Berkas KKS',
    );
    $("#pipPreviewModalBody").html(html);
    $("#pipPreviewModal").modal("show");
  });

  // Handler untuk thumbnail gambar KIP
  $(document).on("click", ".img-kip-thumb", function (e) {
    e.stopPropagation();
    var src = $(this).attr("src") || $(this).data("src");
    var ext = src.split(".").pop().toLowerCase();
    var html = "";
    if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(ext)) {
      html = `
        <div style="text-align: center; position: relative;">
          <div style="margin-bottom: 15px;">
            <button type="button" class="btn btn-sm btn-secondary" onclick="rotateImage(-90)">
              <i class="fas fa-undo mr-1"></i> Putar Kiri
            </button>
            <button type="button" class="btn btn-sm btn-secondary ml-2" onclick="rotateImage(90)">
              <i class="fas fa-redo mr-1"></i> Putar Kanan
            </button>
            <button type="button" class="btn btn-sm btn-info ml-2" onclick="resetImage()">
              <i class="fas fa-sync mr-1"></i> Reset
            </button>
          </div>
          <img src="${src}" alt="KIP" id="previewImage" style="max-width:95%;max-height:75vh;border-radius:8px;box-shadow:0 2px 8px #0002;transition:transform 0.3s ease;" class="zoomable-image">
        </div>
      `;
    } else if (ext === "pdf") {
      html = `
        <div style="text-align: center; margin-bottom: 15px;">
          <button type="button" class="btn btn-sm btn-secondary" onclick="rotateImage(-90)">
            <i class="fas fa-undo mr-1"></i> Putar Kiri
          </button>
          <button type="button" class="btn btn-sm btn-secondary ml-2" onclick="rotateImage(90)">
            <i class="fas fa-redo mr-1"></i> Putar Kanan
          </button>
          <button type="button" class="btn btn-sm btn-info ml-2" onclick="resetImage()">
            <i class="fas fa-sync mr-1"></i> Reset
          </button>
        </div>
        <div class="pdf-container">
          <iframe src="${src}#toolbar=0&navpanes=0&scrollbar=0&zoom=page-width" width="100%" height="100%" style="border: none; transition: transform 0.3s ease;" id="previewImage"></iframe>
        </div>
      `;
    } else {
      html = `<a href="${src}" target="_blank" class="btn btn-primary">Lihat Berkas</a>`;
    }
    $("#pipPreviewModal .modal-title").html(
      '<i class="fas fa-eye mr-2"></i>Preview Berkas KIP',
    );
    $("#pipPreviewModalBody").html(html);
    $("#pipPreviewModal").modal("show");
  });

  // Prevent bubbling for thumbnail images so modal works
  $(document).on(
    "click",
    ".img-kk-thumb, .img-kks-thumb, .img-kip-thumb",
    function (e) {
      e.stopPropagation();
    },
  );

  // Toggle keterangan preview in DataTable rows: show full text or collapse
  $(document).on("click", ".keterangan-toggle", function (e) {
    e.preventDefault();
    var $link = $(this);
    var $container = $link.closest(".keterangan-preview");
    if (!$container || !$container.length) return;
    var full = $container.data("full") || $container.attr("data-full") || "";
    if (!$container.hasClass("expanded")) {
      $container.data("short", $container.html());
      var html =
        nl2br(escapeHtml(full)) +
        ' <a href="#" class="keterangan-toggle">tutup</a>';
      $container.addClass("expanded");
      $container.html(html);
    } else {
      var short = $container.data("short") || "";
      $container.removeClass("expanded");
      $container.html(short);
    }
  });

  // small helper functions used above
  function escapeHtml(text) {
    return String(text).replace(/[&"'<>]/g, function (s) {
      return {
        "&": "&amp;",
        '"': "&quot;",
        "'": "&#39;",
        "<": "&lt;",
        ">": "&gt;",
      }[s];
    });
  }
  function nl2br(str) {
    return String(str).replace(/\n/g, "<br>");
  }

  // Delegasi hanya untuk elemen aksi, bukan seluruh dokumen
  $(document).on(
    "click",
    ".btn-view, .table-action-view, .btn-stts, .btn-delete, .btn-approve, .btn-reject, .btn-submit-reject, .btn-submit-approve, .btn-cancel-approve, .btn-cancel-reject, .modal-close-btn, .file-download-link",
    function (e) {
      var $self = $(this);
      // helper to find the nearest action container with id like status-action-<id>
      var findActionContainer = function ($el) {
        var c = $el.closest('[id^="status-action-"]');
        if (c && c.length) return c;
        // sometimes buttons are not inside that container (fallback modal), try parent modal
        var modal = $el.closest('[id^="fallback-modal-"]');
        if (modal && modal.length) return modal;
        return $el.closest(".reject-reason-wrapper").length
          ? $el.closest(".reject-reason-wrapper")
          : $el.closest(".approve-fields-wrapper");
      };

      // View button
      if ($self.hasClass("btn-view") || $self.hasClass("table-action-view")) {
        e.preventDefault();
        var id =
          $self.attr("data-id") || $self.closest("[data-id]").attr("data-id");
        if (id) fetchAndShowModal(id);
        return;
      }

      // (approve handled later by confirmation flow)

      // Cancel approve button
      if ($self.hasClass("btn-cancel-approve")) {
        e.preventDefault();
        var actionContainer = findActionContainer($self);
        if (actionContainer && actionContainer.length) {
          actionContainer.find(".approve-fields-wrapper").hide();
          actionContainer.find(".d-flex").show();
        }
        return;
      }

      // Approve action: confirm then POST status only (no KKS/KIP)
      if ($self.hasClass("btn-approve")) {
        e.preventDefault();
        var id = $self.attr("data-id") || $self.data("id");
        swal({
          title: "Konfirmasi",
          text: "Setujui usulan ini?",
          icon: "warning",
          buttons: {
            cancel: true,
            confirm: { text: "Setujui", closeModal: false },
          },
        }).then(function (confirmed) {
          if (!confirmed) return;
          $.post(
            "./mod/usulan-pip-ranking/proses.php?action=status",
            { id: id, status: "Y" },
            function (data) {
              var resp = data;
              try {
                if (typeof data === "string") resp = JSON.parse(data);
              } catch (e) {
                /* ignore */
              }
              if (resp && resp.success) {
                swal({
                  title: "Berhasil!",
                  text: "Usulan telah disetujui",
                  icon: "success",
                }).then(function () {
                  closeCurrentModal();
                  if (table) table.ajax.reload();
                });
              } else {
                var msg =
                  resp && resp.message ? resp.message : JSON.stringify(resp);
                swal("Error!", "Terjadi kesalahan: " + msg, "error");
              }
            },
          ).fail(function (xhr) {
            swal(
              "Error!",
              "Request gagal: " + (xhr.statusText || xhr.status),
              "error",
            );
          });
        });
        return;
      }

      // Reject button - show reason textarea
      if ($self.hasClass("btn-reject")) {
        e.preventDefault();
        var ac = findActionContainer($self);
        if (ac && ac.length) {
          ac.find(".reject-reason-wrapper").show();
          ac.find(".d-flex").hide();
        }
        return;
      }

      // Cancel reject button
      if ($self.hasClass("btn-cancel-reject")) {
        e.preventDefault();
        var actionContainer = findActionContainer($self);
        if (actionContainer && actionContainer.length) {
          actionContainer.find(".reject-reason-wrapper").hide();
          actionContainer.find(".d-flex").show();
        }
        return;
      }

      // Submit reject button
      if ($self.hasClass("btn-submit-reject")) {
        e.preventDefault();
        var id = $self.attr("data-id");
        var reason = "";
        var actionContainer = findActionContainer($self);
        if (actionContainer && actionContainer.length) {
          reason = actionContainer.find(".reject-reason").val() || "";
        } else {
          // fallback: look for any nearby reject-reason textarea (fallback modal)
          var fallbackModal = $self.closest('[id^="fallback-modal-"]');
          if (fallbackModal && fallbackModal.length) {
            reason = fallbackModal.find(".reject-reason").val() || "";
          } else {
            reason =
              $self
                .closest(".reject-reason-wrapper")
                .find(".reject-reason")
                .val() || "";
          }
        }

        if (!reason.trim()) {
          swal("Peringatan!", "Alasan penolakan harus diisi", "warning");
          return;
        }

        $self
          .prop("disabled", true)
          .html(
            '<span class="spinner-border spinner-border-sm"></span> Memproses...',
          );

        $.post(
          "./mod/usulan-pip-ranking/proses.php?action=status",
          {
            id: id,
            status: "N",
            reason: reason,
          },
          function (data) {
            var resp = data;
            try {
              if (typeof data === "string") resp = JSON.parse(data);
            } catch (e) {
              /* ignore */
            }
            if (resp && resp.success) {
              swal({
                title: "Berhasil!",
                text: "Usulan telah ditolak",
                type: "success",
                confirmButtonText: "OK",
              }).then(function () {
                closeCurrentModal();
                if (table) table.ajax.reload();
              });
            } else {
              var msg =
                resp && resp.message ? resp.message : JSON.stringify(resp);
              swal("Error!", "Terjadi kesalahan: " + msg, "error");
            }
            $self
              .prop("disabled", false)
              .html('<i class="fas fa-paper-plane"></i> Konfirmasi Penolakan');
          },
        );
        return;
      }

      // Status change (legacy - keep for backward compatibility)
      if ($self.hasClass("btn-stts")) {
        var id = $self.attr("data-id");
        var status = $self.attr("data-status");
        $.post(
          "./mod/usulan-pip-ranking/proses.php?action=status",
          { id: id, status: status },
          function (data) {
            if (data == "success") {
              if (status == "-") {
                $(".badge" + id)
                  .attr("class", "badge" + id + " badge badge-info")
                  .html("Diproses");
              } else if (status == "Y") {
                $(".badge" + id)
                  .attr("class", "badge" + id + " badge badge-success")
                  .html("Selesai");
              } else if (status == "N") {
                $(".badge" + id)
                  .attr("class", "badge" + id + " badge badge-danger")
                  .html("Ditolak");
              }
            } else {
              /* debug removed */
            }
          },
        );
        return;
      }

      // Delete handler
      if ($self.hasClass("btn-delete")) {
        var id = $self.attr("data-id");
        swal({
          text: "Anda yakin ingin menghapus data ini?",
          icon: "warning",
          buttons: { cancel: true, confirm: true },
          value: "yes",
        }).then(function (value) {
          if (!value) return false;
          loading();
          $.post(
            "./mod/usulan-pip-ranking/proses.php?action=delete",
            { id: id },
            function (data) {
              var resp = data;
              try {
                if (typeof data === "string") resp = JSON.parse(data);
              } catch (e) {
                /* ignore */
              }
              if (resp && resp.success) {
                swal({
                  title: "Berhasil!",
                  text: "Data berhasil dihapus!",
                  icon: "success",
                  timer: 1500,
                });
                if (table && table.ajax) table.ajax.reload(null, false);
              } else {
                var msg =
                  resp && resp.message ? resp.message : JSON.stringify(resp);
                swal({
                  title: "Gagal!",
                  text: msg,
                  icon: "error",
                  timer: 2500,
                });
              }
            },
          );
        });
        return;
      }

      // Modal close button
      if ($self.hasClass("modal-close-btn")) {
        e.preventDefault();
        closeCurrentModal();
        return;
      }

      // File download links - prevent default navigation
      if ($self.hasClass("file-download-link")) {
        e.stopPropagation();
        // Let the default behavior (target="_blank") handle it
        return;
      }
    },
  );

  // Bootstrap modal cleanup
  $(document).on("hidden.bs.modal", ".modal", function (e) {
    // Reset rotation when modal is closed
    currentRotation = 0;

    if ($(this).attr("id") && $(this).attr("id").includes("usulanModal_")) {
      // Destroy Viewer.js instance saat modal utama ditutup
      if (window._usulanKksViewer) {
        window._usulanKksViewer.destroy();
        window._usulanKksViewer = null;
      }
      if (window._usulanKipViewer) {
        window._usulanKipViewer.destroy();
        window._usulanKipViewer = null;
      }
      cleanupViewers();
      $(this).remove();
      currentModal = null;
    }
  });

  // Prevent viewer events from bubbling to modal (but not affecting viewer functionality)
  $(document).on("hide.viewer viewed view shown.viewer", function (e) {
    e.stopPropagation();
  });

  // Handle viewer close event specifically - don't close main modal
  $(document).on("hidden.viewer", function (e) {
    e.stopPropagation();
    console.debug("[usulan-pip] Viewer closed, keeping main modal open");
  });

  // Fetch and show modal data
  function fetchAndShowModal(id) {
    if (!id) return;
    console.debug("[usulan-pip] fetchAndShowModal id=", id);

    var base_avatar = "../content/avatar/";
    var base_berkas = "../content/berkas/";

    $.ajax({
      url: "./mod/usulan-pip-ranking/proses.php",
      data: { action: "get", id: id },
      method: "GET",
      dataType: "json",
    })
      .done(function (res) {
        console.debug("[usulan-pip] GET response:", res);

        if (!res || !res.success) {
          swal({
            title: "Gagal",
            text: res && res.message ? res.message : "Data tidak ditemukan",
            icon: "error",
          });
          return;
        }

        var d = res.data;
        var avatar = d.user_avatar || d.avatar || "avatar.jpg";
        var avatarSrc =
          avatar === "avatar.jpg" || !avatar
            ? base_avatar + "avatar.jpg"
            : base_avatar + avatar;

        // Preview KK
        var kkPreview = '<span class="text-muted">Tidak ada</span>';
        if (d.berkas_kk) {
          var extKk = d.berkas_kk.split(".").pop().toLowerCase();
          if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(extKk)) {
            kkPreview = `<img src="${base_berkas}${d.berkas_kk}" alt="KK" class="img-kk-thumb" style="max-width:120px;max-height:120px;border-radius:6px;border:1px solid #ccc;cursor:pointer;" />`;
          } else if (extKk === "pdf") {
            kkPreview = `<div class="text-center"><button type="button" class="btn btn-sm btn-danger img-kk-thumb" data-src="${base_berkas}${d.berkas_kk}" style="cursor:pointer;"><i class="fas fa-file-pdf mr-1"></i>PDF KK</button></div>`;
          } else {
            kkPreview = `<a href="${base_berkas}${d.berkas_kk}" class="btn btn-sm btn-info file-download-link" target="_blank">Lihat KK</a>`;
          }
        }

        // Preview KKS
        var kksPreview = '<span class="text-muted">Tidak ada</span>';
        if (d.berkas_kks) {
          var ext = d.berkas_kks.split(".").pop().toLowerCase();
          if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(ext)) {
            kksPreview = `<img src="${base_berkas}${d.berkas_kks}" alt="KKS" class="img-kks-thumb" style="max-width:120px;max-height:120px;border-radius:6px;border:1px solid #ccc;cursor:pointer;" />`;
          } else if (ext === "pdf") {
            kksPreview = `<div class="text-center"><button type="button" class="btn btn-sm btn-danger img-kks-thumb" data-src="${base_berkas}${d.berkas_kks}" style="cursor:pointer;"><i class="fas fa-file-pdf mr-1"></i>PDF KKS</button></div>`;
          } else {
            kksPreview = `<a href="${base_berkas}${d.berkas_kks}" class="btn btn-sm btn-info file-download-link" target="_blank">Lihat KKS</a>`;
          }
        }

        // Preview KIP
        var kipPreview = '<span class="text-muted">Tidak ada</span>';
        if (d.berkas_kip) {
          var ext2 = d.berkas_kip.split(".").pop().toLowerCase();
          if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(ext2)) {
            kipPreview = `<img src="${base_berkas}${d.berkas_kip}" alt="KIP" class="img-kip-thumb" style="max-width:120px;max-height:120px;border-radius:6px;border:1px solid #ccc;cursor:pointer;" />`;
          } else if (ext2 === "pdf") {
            kipPreview = `<div class="text-center"><button type="button" class="btn btn-sm btn-danger img-kip-thumb" data-src="${base_berkas}${d.berkas_kip}" style="cursor:pointer;"><i class="fas fa-file-pdf mr-1"></i>PDF KIP</button></div>`;
          } else {
            kipPreview = `<a href="${base_berkas}${d.berkas_kip}" class="btn btn-sm btn-info file-download-link" target="_blank">Lihat KIP</a>`;
          }
        }

        // Show modal
        if (typeof $.fn.modal !== "undefined") {
          showBootstrapModal(
            d,
            avatarSrc,
            kkPreview,
            kksPreview,
            kipPreview,
            id,
          );
        } else {
          console.warn(
            "[usulan-pip] Bootstrap modal not available, using fallback",
          );
          showFallbackModal(
            d,
            avatarSrc,
            kkPreview,
            kksPreview,
            kipPreview,
            id,
          );
        }
      })
      .fail(function (xhr) {
        console.error("[usulan-pip] GET request failed", xhr.statusText || xhr);
        swal({
          title: "Gagal",
          text: "Tidak dapat mengambil data: " + (xhr.statusText || "error"),
          icon: "error",
        });
      });
  }

  // Close current modal function
  function closeCurrentModal() {
    // Tutup modal Bootstrap jika ada
    if ($("#usulanPipDetailModal").length) {
      $("#usulanPipDetailModal").modal("hide");
    }
    // Tutup modal fallback jika ada
    if (
      typeof currentModal !== "undefined" &&
      currentModal &&
      currentModal.length
    ) {
      currentModal.remove();
      currentModal = null;
    }
  }

  // Manual ranking: Set posisi handler
  $(document).on("click", ".btn-set-pos", function (e) {
    e.preventDefault();
    var id = $(this).data("id");

    // Debug logging
    console.log("Set posisi clicked, ID:", id);

    swal({
      title: "Set Posisi Ranking",
      text: "Masukkan posisi ranking (1 = teratas):",
      content: "input",
      buttons: {
        cancel: {
          text: "Batal",
          visible: true,
          value: false,
        },
        confirm: {
          text: "Set Posisi",
          value: true,
        },
      },
    }).then(function (posisi) {
      if (posisi === null || posisi === false) return; // canceled

      posisi = parseInt(posisi);
      if (isNaN(posisi) || posisi < 1) {
        swal(
          "Error!",
          "Posisi tidak valid. Masukkan angka minimal 1.",
          "error",
        );
        return;
      }

      console.log("Sending set_rank request - ID:", id, "Posisi:", posisi);

      var $btn = $('.btn-set-pos[data-id="' + id + '"]');
      $btn.prop("disabled", true);

      $.ajax({
        url: "./mod/usulan-pip-ranking/proses.php?action=set_rank",
        method: "POST",
        data: { id: id, posisi: posisi },
        dataType: "json",
      })
        .done(function (resp) {
          console.log("Set rank response:", resp);
          if (resp && resp.success) {
            swal(
              "Berhasil!",
              "Posisi ranking berhasil diubah ke #" + posisi,
              "success",
            );
            if (table && typeof table.ajax !== "undefined")
              table.ajax.reload(null, false);
          } else {
            var errorMsg =
              resp && resp.message ? resp.message : "Terjadi kesalahan";
            console.error("Set rank error:", errorMsg);
            swal("Error!", "Gagal menyimpan posisi: " + errorMsg, "error");
          }
        })
        .fail(function (xhr, status, error) {
          console.error("AJAX fail:", xhr.status, status, error);
          console.log("Response text:", xhr.responseText);
          var errorMsg = "Permintaan gagal (" + xhr.status + ")";
          if (xhr.status === 403) {
            errorMsg =
              "Akses ditolak. Hanya wali kelas yang dapat mengatur ranking.";
          } else if (xhr.responseText) {
            try {
              var errorData = JSON.parse(xhr.responseText);
              if (errorData.message) {
                errorMsg = errorData.message;
              }
            } catch (e) {
              errorMsg += " - " + xhr.responseText.substring(0, 100);
            }
          }
          swal("Error!", errorMsg, "error");
        })
        .always(function () {
          $btn.prop("disabled", false);
        });
    });
  });

  // Move up / down handlers
  $(document).on("click", ".btn-move-up, .btn-move-down", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id = $btn.data("id");
    var dir = $btn.hasClass("btn-move-up") ? "up" : "down";
    var dirText = dir === "up" ? "atas" : "bawah";

    // Debug logging
    console.log("Move button clicked - ID:", id, "Direction:", dir);

    $btn.prop("disabled", true);

    $.ajax({
      url: "./mod/usulan-pip-ranking/proses.php?action=move_rank",
      method: "POST",
      data: { id: id, dir: dir },
      dataType: "json",
    })
      .done(function (resp) {
        console.log("Move rank response:", resp);
        if (resp && resp.success) {
          swal(
            "Berhasil!",
            "Posisi ranking berhasil dipindah ke " + dirText,
            "success",
          );
          if (table && typeof table.ajax !== "undefined")
            table.ajax.reload(null, false);
        } else {
          var errorMsg =
            resp && resp.message ? resp.message : "Terjadi kesalahan";
          console.error("Move rank error:", errorMsg);
          swal("Error!", "Gagal mengubah posisi: " + errorMsg, "error");
        }
      })
      .fail(function (xhr, status, error) {
        console.error("AJAX fail:", xhr.status, status, error);
        console.log("Response text:", xhr.responseText);
        var errorMsg = "Permintaan gagal (" + xhr.status + ")";
        if (xhr.status === 403) {
          errorMsg =
            "Akses ditolak. Hanya wali kelas yang dapat mengatur ranking.";
        } else if (xhr.responseText) {
          try {
            var errorData = JSON.parse(xhr.responseText);
            if (errorData.message) {
              errorMsg = errorData.message;
            }
          } catch (e) {
            errorMsg += " - " + xhr.responseText.substring(0, 100);
          }
        }
        swal("Error!", errorMsg, "error");
      })
      .always(function () {
        $btn.prop("disabled", false);
      });
  });

  // Initialize DataTable when document is ready
  if (typeof initDataTable === "function") {
    initDataTable();
  }

  // Restore filter state from localStorage
  var savedKelas =
    localStorage.getItem("usulan_pip_ranking_filter_kelas") || "";
  var savedStatus =
    localStorage.getItem("usulan_pip_ranking_filter_status") || "";
  var savedDapodik =
    localStorage.getItem("usulan_pip_ranking_filter_dapodik") || "";
  if (savedKelas) $(".filter-kelas").val(savedKelas);
  if (savedStatus) $(".filter-status").val(savedStatus);
  if (savedDapodik) $(".filter-dapodik").val(savedDapodik);

  // Event filter kelas
  $(document).on("change", ".filter-kelas", function () {
    localStorage.setItem("usulan_pip_ranking_filter_kelas", $(this).val());
    if (table) {
      table.ajax.reload();
    }
  });

  // Event filter status
  $(document).on("change", ".filter-status", function () {
    localStorage.setItem("usulan_pip_ranking_filter_status", $(this).val());
    if (table) {
      table.ajax.reload();
    }
  });

  // Event filter dapodik
  $(document).on("change", ".filter-dapodik", function () {
    localStorage.setItem("usulan_pip_ranking_filter_dapodik", $(this).val());
    if (table) {
      table.ajax.reload();
    }
  });

  // Event handler untuk checkbox dapodik (sederhana)
  $(document).on("change", ".dapodik-checkbox", function () {
    var $checkbox = $(this);
    var usulanId = $checkbox.data("id");
    var newStatus = $checkbox.is(":checked") ? "Y" : "N";
    var statusText =
      newStatus === "Y" ? "sudah input ke Dapodik" : "belum input ke Dapodik";

    // Disable checkbox sementara
    $checkbox.prop("disabled", true);

    $.ajax({
      url: "./mod/usulan-pip-ranking/proses.php",
      type: "POST",
      data: {
        action: "update_dapodik",
        usulan_pip_id: usulanId,
        dapodik_status: newStatus,
      },
      dataType: "json",
      success: function (response) {
        if (response && response.success) {
          // Success notification
          swal({
            type: "success",
            title: "Berhasil!",
            text: "Status dapodik berhasil diperbarui menjadi " + statusText,
            showConfirmButton: false,
            timer: 2000,
          });

          // Reload table
          if (table && table.ajax) {
            table.ajax.reload(function (json) {
              // Force sync checkboxes setelah reload
              setTimeout(function () {
                $(".dapodik-checkbox").each(function () {
                  var $cb = $(this);
                  var id = $cb.data("id");
                  console.log(
                    "Syncing checkbox for ID:",
                    id,
                    "Current checked:",
                    $cb.is(":checked"),
                  );

                  // Jika ini adalah checkbox yang baru saja diupdate, paksa ke status yang benar
                  if (id == usulanId) {
                    $cb.prop("checked", newStatus === "Y");
                    console.log("Forced checkbox", id, "to", newStatus === "Y");
                  }
                });
              }, 500);
            }, false);
          }
        } else {
          // Revert checkbox & show error
          $checkbox.prop("checked", !$checkbox.is(":checked"));
          swal({
            type: "error",
            title: "Error!",
            text: response.message || "Gagal mengupdate status dapodik",
            confirmButtonText: "OK",
          });
        }
      },
      error: function (xhr) {
        // Revert checkbox & show error
        $checkbox.prop("checked", !$checkbox.is(":checked"));

        var errorMsg = "Terjadi kesalahan saat mengupdate status dapodik";
        if (xhr.status === 403) {
          errorMsg =
            "Akses ditolak. Hanya superadmin dan admin yang dapat mengubah status dapodik.";
        }

        swal({
          type: "error",
          title: "Error!",
          text: errorMsg,
          confirmButtonText: "OK",
        });
      },
      complete: function () {
        // Re-enable checkbox
        $checkbox.prop("disabled", false);
      },
    });
  });
}); // End of jQuery document ready
