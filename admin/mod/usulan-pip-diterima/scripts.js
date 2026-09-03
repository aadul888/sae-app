'use strict';

// Global variables
var table = null;
var currentModal = null;
var viewerInstances = {
  kks: null,
  kip: null
};

// (debug lines removed)
document.addEventListener('click', function (e) {
  try {
    if (e && e.target) {
      console.debug('[usulan-pip] click captured:', e.target.tagName, e.target.className || '', e.target.getAttribute && e.target.getAttribute('data-id'));
    }
  } catch (err) {}
}, true);

// Utility functions
function loading() {
  $('.btn-save').prop('disabled', true);
  $('.btn-save').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
  window.setTimeout(function () {
    $('.btn-save').prop('disabled', false);
    $('.btn-save').html('<i class="far fa-save"></i> Simpan');
  }, 2000);
}

function stripHtml(html) {
  var doc = new DOMParser().parseFromString(html, 'text/html');
  return doc.body.textContent || '';
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
  if (typeof window.jQuery === 'undefined' || typeof $.fn.DataTable === 'undefined') {
    setTimeout(initDataTable, 50);
    return;
  }

  table = $('.datatable').DataTable({
    scrollX: false,
    processing: true,
    serverSide: true,
    bAutoWidth: true,
    bSort: false,
    bStateSave: true,
    bDestroy: true,
    paging: true,
    pageLength: 25,
    lengthMenu: [[25, 30, 50, -1], [25, 30, 50, 'All']],
    searching: true,
    language: {
      paginate: { previous: "<i class='fas fa-angle-left'>", next: "<i class='fas fa-angle-right'>" },
      search: "Cari:",
      lengthMenu: "Tampilkan _MENU_ data",
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      infoFiltered: "(difilter dari _MAX_ total data)"
    },
    ajax: {
      url: './mod/usulan-pip-diterima/datatable.php',
      type: 'POST',
      dataSrc: function (json) { console.debug('[usulan-pip] datatable ajax dataSrc', json); return json.data || json.aaData || []; },
      error: function (xhr, status, err) { console.error('[usulan-pip] datatable ajax error', status, err); }
    },
    columnDefs: [{ targets: [0], orderable: false }],
    createdRow: function (row, data) { var cleanId = stripHtml(data[0]).trim(); $(row).addClass('row-' + cleanId); },
    initComplete: function (settings, json) { console.debug('[usulan-pip] DataTable initComplete', settings, json); }
  });
}

// Fallback modal styles
function createFallbackModal() {
  if ($('#fallback-modal-style').length === 0) {
    $('head').append(`
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
function showBootstrapModal(d, avatarSrc, kksPreview, kipPreview, id) {
  // Format tanggal jika ada
  var formatDate = function(dateStr) {
    if (!dateStr || dateStr === '-') return '-';
    try {
      var date = new Date(dateStr);
      return date.toLocaleDateString('id-ID');
    } catch(e) {
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
            <h6 class="mb-0 text-primary">${d.user_nama || d.nama_lengkap || '-'}</h6>
            <small class="text-muted">NISN: ${d.user_nisn || d.nisn || '-'}</small>
          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-6 text-center">
                <small class="text-muted d-block">Kelas</small>
                <span class="font-weight-bold">${d.nama_kelas || '-'}</span>
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
              <span class="badge badge-lg ${d.status === 'Pending' ? 'badge-warning' : d.status === 'Disetujui' ? 'badge-success' : d.status === 'Ditolak' ? 'badge-danger' : 'badge-secondary'}" 
                    style="font-size:14px;padding:8px 12px;">${d.status || 'Pending'}</span>
            </div>
            <div class="mt-2">
              <small class="text-muted d-block">Poin</small>
              <span class="font-weight-bold" style="font-size:16px;color:#28a745;">${typeof d.poin !== 'undefined' ? d.poin : 0}</span>
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
              <tr><td width="40%" class="text-muted">Nama Lengkap</td><td class="font-weight-bold">${d.user_nama || d.nama_lengkap || '-'}</td></tr>
              <tr><td class="text-muted">NISN</td><td class="font-weight-bold">${d.user_nisn || d.nisn || '-'}</td></tr>
              <tr><td class="text-muted">Kelas</td><td class="font-weight-bold">${d.nama_kelas || '-'}</td></tr>
              <tr><td class="text-muted">Tempat Lahir</td><td>${d.tempat_lahir || '-'}</td></tr>
              <tr><td class="text-muted">Tanggal Lahir</td><td>${formatDate(d.tanggal_lahir)}</td></tr>
              <tr><td class="text-muted">Jenis Kelamin</td><td>${d.jenis_kelamin || '-'}</td></tr>
              <tr><td class="text-muted">No. HP</td><td>${d.no_hp || '-'}</td></tr>
            </table>
            
            <div class="border-top pt-3">
              <h6 class="text-secondary mb-2"><i class="fas fa-home"></i> Alamat</h6>
              <p class="mb-0" style="font-size:13px;line-height:1.5;">${d.alamat_lengkap || '-'}</p>
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
              <tr><td width="40%" class="text-muted">Nama Ayah</td><td class="font-weight-bold">${d.nama_ayah || '-'}</td></tr>
              <tr><td class="text-muted">Penghasilan Ayah</td><td>${d.penghasilan_ayah || '-'}</td></tr>
              <tr><td class="text-muted">Nama Ibu</td><td class="font-weight-bold">${d.nama_ibu || '-'}</td></tr>
              <tr><td class="text-muted">Penghasilan Ibu</td><td>${d.penghasilan_ibu || '-'}</td></tr>
              <tr><td class="text-muted">Nama Wali</td><td class="font-weight-bold">${d.nama_wali || '-'}</td></tr>
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
                  <tr><td width="40%" class="text-muted">Penerima KPS</td><td class="font-weight-bold">${d.pertanyaan_1 || '-'}</td></tr>
                  <tr><td class="text-muted">Memiliki KIP</td><td class="font-weight-bold">${d.pertanyaan_2 || '-'}</td></tr>
                  <tr><td class="text-muted">No. KKS</td><td><span class="badge badge-info">${d.no_kks || 'Belum diisi'}</span></td></tr>
                  <tr><td class="text-muted">No. KIP</td><td><span class="badge badge-info">${d.no_kip || 'Belum diisi'}</span></td></tr>
                </table>
              </div>
              <div class="col-md-6">
                <h6 class="text-secondary mb-2">Dokumen Pendukung</h6>
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
            
            ${d.alasan_usulan ? `
              <div class="border-top pt-3 mt-3">
                <h6 class="text-secondary mb-2"><i class="fas fa-comment-alt"></i> Alasan Usulan</h6>
                <div class="bg-light p-3 rounded" style="border-left:4px solid #007bff;">
                  <p class="mb-0" style="white-space:pre-line;font-size:13px;line-height:1.6;">${d.alasan_usulan}</p>
                </div>
              </div>
            ` : ''}
            
            ${d.alasan_penolakan ? `
              <div class="border-top pt-3 mt-3">
                <h6 class="text-danger mb-2"><i class="fas fa-exclamation-triangle"></i> Alasan Penolakan</h6>
                <div class="bg-danger-light p-3 rounded" style="border-left:4px solid #dc3545;background-color:#f8d7da;">
                  <p class="mb-0 text-danger" style="font-size:13px;line-height:1.6;">${d.alasan_penolakan}</p>
                </div>
              </div>
            ` : ''}
            
            ${d.keterangan && d.keterangan !== d.alasan_penolakan ? `
              <div class="border-top pt-3 mt-3">
                <h6 class="text-secondary mb-2"><i class="fas fa-info-circle"></i> Keterangan</h6>
                <div class="bg-light p-3 rounded">
                  <p class="mb-0" style="font-size:13px;line-height:1.6;">${d.keterangan}</p>
                </div>
              </div>
            ` : ''}
          </div>
        </div>
      </div>
    </div>
  `;
  $('#usulanPipDetailModalBody').html(modalBodyHtml);
  $('#usulanPipDetailModal').addClass('usulan-pip-modal');
  $('#usulanPipDetailModal').modal('show');
}

function showFallbackModal(d, avatarSrc, kksPreview, kipPreview, id) {
  createFallbackModal();
  
  var modalHtml = `
    <div class="fallback-modal-overlay" id="fallback-modal-${d.usulan_pip_id}">
      <div class="fallback-modal-content" style="width: 800px;">
        <div class="fallback-modal-header">
          <h5>Detail Usulan PIP - ${d.nama_lengkap || '-'}</h5>
          <button class="fallback-modal-close modal-close-btn">&times;</button>
        </div>
        <div class="fallback-modal-body">
          <div style="display: flex; gap: 20px;">
            <div style="flex: 0 0 200px; text-align: center;">
              <img src="${avatarSrc}" alt="avatar" style="max-height:140px; border-radius: 8px; margin-bottom: 15px;">
              <p><strong>NISN:</strong><br>${d.user_nisn || d.nisn || '-'}</p>
              <p><strong>Kelas:</strong><br>${d.nama_kelas || '-'}</p>
            </div>
            <div style="flex: 1;">
              <div style="border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin-bottom: 15px;">
                <h6 style="margin-bottom: 10px; color: #495057;">Informasi Pribadi</h6>
                <table style="width: 100%; line-height: 1.8;">
                  <tr><td style="width: 150px; font-weight: bold;">Nama</td><td>${d.user_nama || d.nama_lengkap || '-'}</td></tr>
                  <tr><td style="font-weight: bold;">Tempat, Tgl Lahir</td><td>${(d.tempat_lahir||'-') + ', ' + (d.tanggal_lahir||'-')}</td></tr>
                  <tr><td style="font-weight: bold;">Penghasilan Ayah</td><td>${d.penghasilan_ayah || '-'}</td></tr>
                  <tr><td style="font-weight: bold;">Penghasilan Ibu</td><td>${d.penghasilan_ibu || '-'}</td></tr>
                </table>
              </div>
              <div style="border: 1px solid #dee2e6; border-radius: 4px; padding: 15px;">
                <h6 style="margin-bottom: 10px; color: #495057;">Dokumen & Status</h6>
                <table style="width: 100%; line-height: 1.8;">
                  <tr><td style="width: 150px; font-weight: bold;">Penerima KPS</td><td>${d.pertanyaan_1 || '-'}<br>${kksPreview}</td></tr>
                  <tr><td style="font-weight: bold;">Memiliki KIP</td><td>${d.pertanyaan_2 || '-'}<br>${kipPreview}</td></tr>
                  <tr><td style="font-weight: bold;">Keterangan</td><td>${d.keterangan || '-'}</td></tr>
                  <tr><td style="font-weight: bold;">Alasan Usulan</td><td><span style="white-space:pre-line">${d.alasan_usulan ? d.alasan_usulan : '-'}</span></td></tr>
                  <tr><td style="font-weight: bold;">Tanggal Pengajuan</td><td>${d.tanggal_pengajuan || '-'}</td></tr>
                  <tr><td style="font-weight: bold;">Status</td><td><strong style="color: #007bff;">${d.status || '-'}</strong></td></tr>
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

  $('body').append(modalHtml);
  currentModal = $('#fallback-modal-' + d.usulan_pip_id);
}

// Hapus initializeViewers dari event shown.bs.modal
// Tambahkan handler klik langsung untuk thumbnail gambar KKS/KIP
$(document).ready(function() {
  // Load custom CSS for diterima if not already loaded
  if (!$('link[href*="usulan-pip-diterima/style.css"]').length) {
    $('<link rel="stylesheet" type="text/css" href="./mod/usulan-pip-diterima/style.css">').appendTo('head');
  }

  initDataTable();


  // Modal preview Bootstrap
  if ($('#pipPreviewModal').length === 0) {
    $('body').append(`
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

  // Handler untuk thumbnail gambar KKS
  $(document).on('click', '.img-kks-thumb', function(e) {
    e.stopPropagation();
    var src = $(this).attr('src');
    var ext = src.split('.').pop().toLowerCase();
    var html = '';
    if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
      html = `<img src="${src}" alt="KKS" style="max-width:100%;max-height:80vh;border-radius:8px;box-shadow:0 2px 8px #0002;">`;
    } else if (ext === "pdf") {
      html = `<embed src="${src}" type="application/pdf" width="100%" height="600px" style="border-radius:8px;">`;
    } else {
      html = `<a href="${src}" target="_blank">Lihat Berkas</a>`;
    }
    $('#pipPreviewModalBody').html(html);
    $('#pipPreviewModal').modal('show');
  });

  // Handler untuk thumbnail gambar KIP
  $(document).on('click', '.img-kip-thumb', function(e) {
    e.stopPropagation();
    var src = $(this).attr('src');
    var ext = src.split('.').pop().toLowerCase();
    var html = '';
    if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
      html = `<img src="${src}" alt="KIP" style="max-width:100%;max-height:80vh;border-radius:8px;box-shadow:0 2px 8px #0002;">`;
    } else if (ext === "pdf") {
      html = `<embed src="${src}" type="application/pdf" width="100%" height="600px" style="border-radius:8px;">`;
    } else {
      html = `<a href="${src}" target="_blank">Lihat Berkas</a>`;
    }
    $('#pipPreviewModalBody').html(html);
    $('#pipPreviewModal').modal('show');
  });

  // Prevent bubbling for thumbnail images so modal works
  $(document).on('click', '.img-kks-thumb, .img-kip-thumb', function(e) {
    e.stopPropagation();
  });

  // Toggle keterangan preview in DataTable rows: show full text or collapse
  $(document).on('click', '.keterangan-toggle', function (e) {
    e.preventDefault();
    var $link = $(this);
    var $container = $link.closest('.keterangan-preview');
    if (!$container || !$container.length) return;
    var full = $container.data('full') || $container.attr('data-full') || '';
    if (!$container.hasClass('expanded')) {
      $container.data('short', $container.html());
      var html = nl2br(escapeHtml(full)) + ' <a href="#" class="keterangan-toggle">tutup</a>';
      $container.addClass('expanded');
      $container.html(html);
    } else {
      var short = $container.data('short') || '';
      $container.removeClass('expanded');
      $container.html(short);
    }
  });

  // small helper functions used above
  function escapeHtml(text) {
    return String(text).replace(/[&"'<>]/g, function (s) {
      return ({'&':'&amp;','"':'&quot;',"'":'&#39;','<':'&lt;','>':'&gt;'})[s];
    });
  }
  function nl2br(str) {
    return String(str).replace(/\n/g, '<br>');
  }

  // Delegasi hanya untuk elemen aksi, bukan seluruh dokumen
  $(document).on('click', '.btn-view, .table-action-view, .btn-stts, .btn-delete, .btn-approve, .btn-reject, .btn-submit-reject, .btn-submit-approve, .btn-cancel-approve, .btn-cancel-reject, .modal-close-btn, .file-download-link', function(e) {
    var $self = $(this);
    // helper to find the nearest action container with id like status-action-<id>
    var findActionContainer = function($el) {
      var c = $el.closest('[id^="status-action-"]');
      if (c && c.length) return c;
      // sometimes buttons are not inside that container (fallback modal), try parent modal
      var modal = $el.closest('[id^="fallback-modal-"]');
      if (modal && modal.length) return modal;
      return $el.closest('.reject-reason-wrapper').length ? $el.closest('.reject-reason-wrapper') : $el.closest('.approve-fields-wrapper');
    };

    // View button
    if ($self.hasClass('btn-view') || $self.hasClass('table-action-view')) {
      e.preventDefault();
      var id = $self.attr('data-id') || $self.closest('[data-id]').attr('data-id');
      if (id) fetchAndShowModal(id);
      return;
    }

    // (approve handled later by confirmation flow)

    // Cancel approve button
    if ($self.hasClass('btn-cancel-approve')) {
      e.preventDefault();
      var actionContainer = findActionContainer($self);
      if (actionContainer && actionContainer.length) {
        actionContainer.find('.approve-fields-wrapper').hide();
        actionContainer.find('.d-flex').show();
      }
      return;
    }

    // Approve action: show criteria modal then POST id,status,kriteria,alasan_usulan
    if ($self.hasClass('btn-approve')) {
      e.preventDefault();
      var id = $self.attr('data-id') || $self.data('id');

      // Load full usulan data first (to prefill alasan if present)
      $.get('./mod/usulan-pip-diterima/proses.php', { action: 'get', id: id })
        .done(function(getResp){
          var resp = getResp;
          try { if (typeof getResp === 'string') resp = JSON.parse(getResp); } catch(e) { resp = { success: false }; }
          if (!resp || !resp.success) {
            swal('Error', 'Gagal memuat data untuk konfirmasi', 'error');
            return;
          }
          var d = resp.data || {};

          // Fetch kriteria list
          $.get('./mod/kriteria-pip/proses.php', { action: 'list_kriteria' })
            .done(function(kresp){
              var kre = kresp;
              try { if (typeof kresp === 'string') kre = JSON.parse(kresp); } catch(e) { kre = { success: false }; }
              var kriteriaList = [];
              if (kre && kre.success && Array.isArray(kre.data)) {
                // Prefer sending IDs to backend when available
                kriteriaList = kre.data.map(function(r){ return { id: r.id, name: r.nama_kriteria }; });
              } else {
                kriteriaList = [{ id: 0, name: 'Memiliki KIP' }, { id: 0, name: 'Memiliki KPS/KKS/PKH' }, { id: 0, name: 'Penghasilan Orang Tua' }, { id: 0, name: 'Tempat Tinggal' }];
              }

              var alasan = (d.alasan_usulan || '').toString().trim().toLowerCase();
              var preCheckAll = (alasan === 'sesuai');

              var modalId = 'approveKriteriaModal_' + id;
              var html = '';
              html += '<div class="modal fade" id="' + modalId + '" tabindex="-1" role="dialog" aria-hidden="true">';
              html += '<div class="modal-dialog modal-lg"><div class="modal-content">';
              html += '<div class="modal-header"><h5 class="modal-title">Pilih Kriteria untuk Persetujuan</h5>';
              html += '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
              html += '<div class="modal-body">';
              html += '<p>Centang kriteria yang relevan untuk usulan ini.</p>';
              html += '<div class="form-group mb-3">';
              html += '<label class="d-block">Alasan penilaian</label>';
              html += '<div class="form-check form-check-inline">';
              html += '<input class="form-check-input approve-alasan-radio" type="radio" name="approve_alasan" id="' + modalId + '_alasan_sesuai" value="sesuai" ' + (alasan === 'sesuai' ? 'checked' : '') + '> ';
              html += '<label class="form-check-label" for="' + modalId + '_alasan_sesuai">Sesuai</label>';
              html += '</div>';
              html += '<div class="form-check form-check-inline">';
              html += '<input class="form-check-input approve-alasan-radio" type="radio" name="approve_alasan" id="' + modalId + '_alasan_tidak" value="tidak sesuai" ' + (alasan === 'tidak sesuai' ? 'checked' : '') + '> ';
              html += '<label class="form-check-label" for="' + modalId + '_alasan_tidak">Tidak Sesuai</label>';
              html += '</div>';
              html += '</div>';
              html += '<div class="form-group">';

              for (var i = 0; i < kriteriaList.length; i++) {
                var key = 'k_' + i;
                var checked = preCheckAll ? 'checked' : '';
                var val = kriteriaList[i].id && kriteriaList[i].id !== 0 ? kriteriaList[i].id : kriteriaList[i].name;
                html += '<div class="form-check">';
                html += '<input class="form-check-input approve-kriteria-checkbox" type="checkbox" value="' + escapeHtml(val) + '" id="' + modalId + '_' + key + '" ' + checked + '>';
                html += '<label class="form-check-label" for="' + modalId + '_' + key + '">' + escapeHtml(kriteriaList[i].name) + '</label>';
                html += '</div>';
              }

              html += '</div></div>';
              html += '<div class="modal-footer">';
              html += '<button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>';
              html += '<button type="button" class="btn btn-primary btn-confirm-approve" data-id="' + id + '">Setujui</button>';
              html += '</div></div></div></div>';

              $('body').append(html);
              var $m = $('#' + modalId);
              $m.modal({ backdrop: 'static', keyboard: false });

              $m.on('click', '.btn-confirm-approve', function (ev) {
                ev.preventDefault();
                var sel = [];
                $m.find('.approve-kriteria-checkbox:checked').each(function () { sel.push($(this).val()); });
                var postData = { id: id, status: 'Y', kriteria: JSON.stringify(sel) };
                var selectedAlasan = $m.find('input[name="approve_alasan"]:checked').val() || '';
                postData.alasan_usulan = selectedAlasan;
                var $btn = $(this).prop('disabled', true).text('Processing...');
                $.post('./mod/usulan-pip-diterima/proses.php?action=status', postData)
                  .done(function (postResp) {
                    var presp = postResp;
                    try { if (typeof postResp === 'string') presp = JSON.parse(postResp); } catch (e) {}
                    if (presp && presp.success) {
                      $m.modal('hide');
                      $m.remove();
                      swal({ title: 'Berhasil!', text: 'Usulan telah disetujui', icon: 'success' }).then(function () {
                        closeCurrentModal();
                        if (table) table.ajax.reload();
                      });
                    } else {
                      var msg = (presp && presp.message) ? presp.message : JSON.stringify(presp);
                      swal('Error!', 'Terjadi kesalahan: ' + msg, 'error');
                      $btn.prop('disabled', false).text('Setujui');
                    }
                  })
                  .fail(function (xhr) { swal('Error!', 'Request gagal: ' + (xhr.statusText || xhr.status), 'error'); $btn.prop('disabled', false).text('Setujui'); });
              });

              $m.on('hidden.bs.modal', function () { $m.remove(); });
            })
            .fail(function () { swal('Error', 'Gagal memuat daftar kriteria', 'error'); });
        })
        .fail(function () { swal('Error', 'Gagal memuat data usulan', 'error'); });

      return;
    }

    // Reject button - show reason textarea
    if ($self.hasClass('btn-reject')) {
      e.preventDefault();
      var ac = findActionContainer($self);
      if (ac && ac.length) {
        ac.find('.reject-reason-wrapper').show();
        ac.find('.d-flex').hide();
      }
      return;
    }

    // Cancel reject button
    if ($self.hasClass('btn-cancel-reject')) {
      e.preventDefault();
      var actionContainer = findActionContainer($self);
      if (actionContainer && actionContainer.length) {
        actionContainer.find('.reject-reason-wrapper').hide();
        actionContainer.find('.d-flex').show();
      }
      return;
    }

    // Submit reject button
    if ($self.hasClass('btn-submit-reject')) {
      e.preventDefault();
      var id = $self.attr('data-id');
      var reason = '';
      var actionContainer = findActionContainer($self);
      if (actionContainer && actionContainer.length) {
        reason = actionContainer.find('.reject-reason').val() || '';
      } else {
        // fallback: look for any nearby reject-reason textarea (fallback modal)
        var fallbackModal = $self.closest('[id^="fallback-modal-"]');
        if (fallbackModal && fallbackModal.length) {
          reason = fallbackModal.find('.reject-reason').val() || '';
        } else {
          reason = $self.closest('.reject-reason-wrapper').find('.reject-reason').val() || '';
        }
      }
      
      if (!reason.trim()) {
        swal("Peringatan!", "Alasan penolakan harus diisi", "warning");
        return;
      }
      
      $self.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
      
      $.post('./mod/usulan-pip-diterima/proses.php?action=status', { 
        id: id, 
        status: 'N',
        reason: reason
      }, function (data) {
        var resp = data;
        try { if (typeof data === 'string') resp = JSON.parse(data); } catch (e) { /* ignore */ }
        if (resp && resp.success) {
          swal({
            title: "Berhasil!",
            text: "Usulan telah ditolak",
            type: "success",
            confirmButtonText: "OK"
          }).then(function() {
            closeCurrentModal();
            if (table) table.ajax.reload();
          });
        } else {
          var msg = (resp && resp.message) ? resp.message : JSON.stringify(resp);
          swal("Error!", "Terjadi kesalahan: " + msg, "error");
        }
        $self.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Konfirmasi Penolakan');
      });
      return;
    }

    // Status change (legacy - keep for backward compatibility)
    if ($self.hasClass('btn-stts')) {
      e.preventDefault();
      var id = $self.attr('data-id') || $self.data('id');
      var status = $self.attr('data-status');

      // Send status change request and expect JSON response
      $.post('./mod/usulan-pip-diterima/proses.php?action=status', { id: id, status: status }, function (data) {
        var resp = data;
        try { if (typeof data === 'string') resp = JSON.parse(data); } catch (err) { /* ignore */ }
        if (resp && resp.success) {
          // Inform the user and refresh the table/modal
          var msg = 'Status berhasil diperbarui';
          if (status === '-') msg = 'Status dikembalikan ke Pending';
          else if (status === 'Y') msg = 'Status diubah menjadi Disetujui';
          else if (status === 'N') msg = 'Status diubah menjadi Ditolak';
          swal({ title: 'Berhasil', text: msg, icon: 'success' }).then(function () {
            closeCurrentModal();
            if (table && table.ajax) table.ajax.reload(null, false);
          });
        } else {
          var errMsg = (resp && resp.message) ? resp.message : JSON.stringify(resp);
          swal('Gagal', 'Terjadi kesalahan: ' + errMsg, 'error');
        }
      }).fail(function (xhr) {
        swal('Gagal', 'Request gagal: ' + (xhr.statusText || xhr.status), 'error');
      });

      return;
    }

    // Delete handler
    if ($self.hasClass('btn-delete')) {
      var id = $self.attr('data-id');
      swal({ text: 'Anda yakin ingin menghapus data ini?', icon: 'warning', buttons: { cancel: true, confirm: true }, value: 'yes' })
        .then(function (value) {
          if (!value) return false;
          loading();
          $.post('./mod/usulan-pip-diterima/proses.php?action=delete', { id: id }, function (data) {
            var resp = data;
            try { if (typeof data === 'string') resp = JSON.parse(data); } catch (e) { /* ignore */ }
            if (resp && resp.success) {
              swal({ title: 'Berhasil!', text: 'Data berhasil dihapus!', icon: 'success', timer: 1500 });
              if (table && table.ajax) table.ajax.reload(null, false);
            } else {
              var msg = (resp && resp.message) ? resp.message : JSON.stringify(resp);
              swal({ title: 'Gagal!', text: msg, icon: 'error', timer: 2500 });
            }
          });
        });
      return;
    }

    // Modal close button
    if ($self.hasClass('modal-close-btn')) {
      e.preventDefault();
      closeCurrentModal();
      return;
    }

    // File download links - prevent default navigation
    if ($self.hasClass('file-download-link')) {
      e.stopPropagation();
      // Let the default behavior (target="_blank") handle it
      return;
    }
  });

  // Bootstrap modal cleanup
  $(document).on('hidden.bs.modal', '.modal', function(e) {
    if ($(this).attr('id') && $(this).attr('id').includes('usulanModal_')) {
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
  $(document).on('hide.viewer viewed view shown.viewer', function(e) {
    e.stopPropagation();
  });
  
  // Handle viewer close event specifically - don't close main modal
  $(document).on('hidden.viewer', function(e) {
    e.stopPropagation();
    console.debug('[usulan-pip] Viewer closed, keeping main modal open');
  });
});

// Fetch and show modal data
function fetchAndShowModal(id) {
  if (!id) return;
  console.debug('[usulan-pip] fetchAndShowModal id=', id);
  
  var base_avatar = '../content/avatar/';
  var base_berkas = '../content/berkas/';
  
  $.ajax({
  url: './mod/usulan-pip-diterima/proses.php',
    data: { action: 'get', id: id },
    method: 'GET',
    dataType: 'json'
  }).done(function (res) {
    console.debug('[usulan-pip] GET response:', res);
    
    if (!res || !res.success) {
      swal({ title: 'Gagal', text: (res && res.message) ? res.message : 'Data tidak ditemukan', icon: 'error' });
      return;
    }

    var d = res.data;
    var avatar = d.user_avatar || d.avatar || 'avatar.jpg';
    var avatarSrc = (avatar === 'avatar.jpg' || !avatar) ? base_avatar + 'avatar.jpg' : (base_avatar + avatar);
    
    // Preview KKS
    var kksPreview = '<span class="text-muted">Tidak ada</span>';
    if (d.kks_file) {
      var ext = d.kks_file.split('.').pop().toLowerCase();
      if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext)) {
        kksPreview = `<img src="${base_berkas}${d.kks_file}" alt="KKS" class="img-kks-thumb" style="max-width:120px;max-height:120px;border-radius:6px;border:1px solid #ccc;cursor:pointer;" />`;
      } else if (ext === "pdf") {
        kksPreview = `<embed src="${base_berkas}${d.kks_file}" type="application/pdf" width="120" height="120" style="border-radius:6px;border:1px solid #ccc;" />`;
      } else {
        kksPreview = `<a href="${base_berkas}${d.kks_file}" class="btn btn-sm btn-info file-download-link" target="_blank">Lihat KKS</a>`;
      }
    }
    
    // Preview KIP
    var kipPreview = '<span class="text-muted">Tidak ada</span>';
    if (d.kip_file) {
      var ext2 = d.kip_file.split('.').pop().toLowerCase();
      if (["jpg","jpeg","png","gif","bmp","webp"].includes(ext2)) {
        kipPreview = `<img src="${base_berkas}${d.kip_file}" alt="KIP" class="img-kip-thumb" style="max-width:120px;max-height:120px;border-radius:6px;border:1px solid #ccc;cursor:pointer;" />`;
      } else if (ext2 === "pdf") {
        kipPreview = `<embed src="${base_berkas}${d.kip_file}" type="application/pdf" width="120" height="120" style="border-radius:6px;border:1px solid #ccc;" />`;
      } else {
        kipPreview = `<a href="${base_berkas}${d.kip_file}" class="btn btn-sm btn-info file-download-link" target="_blank">Lihat KIP</a>`;
      }
    }
    
    // Show modal
    if (typeof $.fn.modal !== 'undefined') {
      showBootstrapModal(d, avatarSrc, kksPreview, kipPreview, id);
    } else {
      console.warn('[usulan-pip] Bootstrap modal not available, using fallback');
      showFallbackModal(d, avatarSrc, kksPreview, kipPreview, id);
    }
  }).fail(function (xhr) {
    console.error('[usulan-pip] GET request failed', xhr.statusText || xhr);
    swal({ title: 'Gagal', text: 'Tidak dapat mengambil data: ' + (xhr.statusText || 'error'), icon: 'error' });
  });
}

// Close current modal function
function closeCurrentModal() {
  // Tutup modal Bootstrap jika ada
  if ($('#usulanPipDetailModal').length) {
    $('#usulanPipDetailModal').modal('hide');
  }
  // Tutup modal fallback jika ada
  if (typeof currentModal !== 'undefined' && currentModal && currentModal.length) {
    currentModal.remove();
    currentModal = null;
  }
}