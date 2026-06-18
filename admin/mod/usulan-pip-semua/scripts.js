'use strict';

// Global variables
var table = null;
var currentModal = null;
var viewerInstances = {
  kks: null,
  kip: null
};

// Debug: capture clicks to help diagnose unresponsive buttons
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
    serverSide: true, // ubah dari false ke true
    bAutoWidth: true,
    bSort: false,
    bStateSave: true,
    bDestroy: true,
    paging: true,
    iDisplayLength: 25,
    aLengthMenu: [[25, 30, 50, -1], [25, 30, 50, 'All']],
    language: {
      paginate: { previous: "<i class='fas fa-angle-left'>", next: "<i class='fas fa-angle-right'>" }
    },
    ajax: {
      url: './mod/usulan-pip-semua/datatable.php',
      type: 'POST',
      data: function (d) {
        d.kelas = $('.filter-kelas').val();
        d.status = $('.filter-status').val();
      },
      dataSrc: function (json) { 
        console.debug('[usulan-pip] datatable ajax dataSrc', json); 
        
        // Update statistik jika tersedia dalam response
        if (json && json.stats) {
          var s = json.stats;
          $('#pip-stat-total .value').text(s.total || 0);
          $('#pip-stat-pending .value').text(s.pending || 0);
          $('#pip-stat-disetujui .value').text(s.disetujui || 0);
          $('#pip-stat-ditolak .value').text(s.ditolak || 0);
        }
        
        return json.data || json.aaData || [];
      },
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
              <tr><td class="text-muted">No. HP</td><td>${d.telp || '-'}</td></tr>
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
              <tr><td class="text-muted">Pekerjaan Ayah</td><td>${d.pekerjaan_ayah || '-'}</td></tr>
              <tr><td class="text-muted">Penghasilan Ayah</td><td>${d.penghasilan_ayah || 'Tidak Tetap'}</td></tr>
              <tr><td class="text-muted">Nama Ibu</td><td class="font-weight-bold">${d.nama_ibu || '-'}</td></tr>
              <tr><td class="text-muted">Pekerjaan Ibu</td><td>${d.pekerjaan_ibu || '-'}</td></tr>
              <tr><td class="text-muted">Penghasilan Ibu</td><td>${d.penghasilan_ibu || 'Tidak Tetap'}</td></tr>
              <tr><td class="text-muted">Nama Wali</td><td class="font-weight-bold">${d.nama_wali || '-'}</td></tr>
              <tr><td class="text-muted">Pekerjaan Wali</td><td>${d.pekerjaan_wali || '-'}</td></tr>
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
    
    <!-- Action Panel -->
    <div class="card mt-3 border-warning">
      <div class="card-header bg-warning text-white py-2">
        <h6 class="mb-0"><i class="fas fa-tools"></i> Tindakan</h6>
      </div>
      <div class="card-body p-3">
        <div id="status-action-${d.usulan_pip_id}">
          <!-- Action Buttons -->
            <div class="d-flex justify-content-center align-items-center mb-3" style="gap:15px;">
            <!-- data-id uses numeric DB id for selectors/POST; keep encrypted id in data-encrypted-id if needed -->
            <button class="btn btn-success btn-md btn-approve px-4" data-id="${d.usulan_pip_id}" data-encrypted-id="${id}">
              <i class="fas fa-check-circle"></i> Setujui Usulan
            </button>
            <button class="btn btn-danger btn-md btn-reject px-4" data-id="${d.usulan_pip_id}" data-encrypted-id="${id}">
              <i class="fas fa-times-circle"></i> Tolak Usulan
            </button>
          </div>
          
          <!-- Approve action will be confirmed directly, KKS/KIP not required -->
          
          <!-- Rejection Reason -->
          <div class="reject-reason-wrapper" style="display:none;">
            <div class="form-group">
              <label class="font-weight-bold text-danger" style="font-size:14px;">
                <i class="fas fa-exclamation-triangle"></i> Alasan Penolakan
              </label>
              <textarea class="form-control reject-reason" rows="3" style="font-size:13px;" 
                        placeholder="Jelaskan alasan penolakan usulan ini..."></textarea>
            </div>
            <div class="text-center">
              <button class="btn btn-danger btn-md btn-submit-reject" data-id="${d.usulan_pip_id}" data-encrypted-id="${id}">
                <i class="fas fa-paper-plane"></i> Konfirmasi Penolakan
              </button>
              <button class="btn btn-secondary btn-md btn-cancel-reject ml-2">
                <i class="fas fa-arrow-left"></i> Batal
              </button>
            </div>
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

// Tambahkan initializeViewers dari event shown.bs.modal
// Tambahkan handler klik langsung untuk thumbnail gambar KKS/KIP
$(document).ready(function() {
  // Initialize DataTable
  if (typeof initDataTable === 'function') {
    initDataTable();
  }

  // Handler untuk tombol Tambah Usulan
  $(document).on('click', '#btnTambahUsulan', function(e) {
    e.preventDefault();
    // Reset modal ke step 1
    resetTambahUsulanModal();
    $('#tambahUsulanModal').modal('show');
  });

  // Handler untuk input NISN (Enter key)
  $(document).on('keypress', '#inputNISN', function(e) {
    // Hanya izinkan angka (0-9) dan tombol Enter
    var charCode = e.which || e.keyCode;
    if (charCode === 13) { // Enter key
      $('#btnCariSiswa').click();
      return;
    }
    if (charCode < 48 || charCode > 57) { // Bukan angka 0-9
      e.preventDefault();
      return false;
    }
  });
  
  // Handler untuk paste dan input event di NISN
  $(document).on('input paste', '#inputNISN', function(e) {
    var value = $(this).val();
    // Hapus semua karakter non-digit
    value = value.replace(/\D/g, '');
    // Batasi maksimal 10 digit
    if (value.length > 10) {
      value = value.substring(0, 10);
    }
    $(this).val(value);
    
    // Update visual feedback
    var $input = $(this);
    var $feedback = $('#nisn-feedback');
    
    if (value.length === 0) {
      $input.removeClass('is-valid is-invalid');
      $feedback.hide();
    } else if (value.length === 10) {
      $input.removeClass('is-invalid').addClass('is-valid');
      $feedback.removeClass('invalid-feedback').addClass('valid-feedback').text('Format NISN benar').show();
    } else {
      $input.removeClass('is-valid').addClass('is-invalid');
      $feedback.removeClass('valid-feedback').addClass('invalid-feedback').text(`NISN harus 10 digit (${value.length}/10)`).show();
    }
  });

  // Handler untuk tombol Cari Siswa
  $(document).on('click', '#btnCariSiswa', function(e) {
    e.preventDefault();
    var nisn = $('#inputNISN').val().trim();
    
    if (nisn === '') {
      swal({
        icon: 'warning',
        title: 'NISN Diperlukan',
        text: 'Silahkan masukkan NISN siswa',
        button: 'OK'
      });
      $('#inputNISN').focus();
      return;
    }
    
    // Validasi NISN harus 10 digit angka
    if (!/^\d{10}$/.test(nisn)) {
      swal({
        icon: 'warning',
        title: 'Format NISN Salah',
        text: 'NISN harus berupa 10 digit angka',
        button: 'OK'
      });
      $('#inputNISN').focus().select();
      return;
    }
    
    // Show loading
    showLoadingStep();
    
    // AJAX request untuk mencari data siswa
    $.ajax({
      url: './mod/usulan-pip-semua/proses.php?action=search_siswa',
      type: 'POST',
      data: { nisn: nisn },
      dataType: 'json',
      success: function(response) {
        hideLoadingStep();
        if (response.success) {
          showKonfirmasiStep(response.data);
        } else {
          swal({
            icon: 'error',
            title: 'Data Tidak Ditemukan',
            text: response.message,
            button: 'OK'
          });
          // Tetap di step input NISN, fokus ke input
          setTimeout(() => {
            $('#inputNISN').focus().select();
          }, 500);
        }
      },
      error: function(xhr, status, error) {
        hideLoadingStep();
        swal({
          icon: 'error',
          title: 'Kesalahan Sistem',
          text: 'Terjadi kesalahan saat mencari data siswa',
          button: 'OK'
        });
        // Tetap di step input NISN, fokus ke input
        setTimeout(() => {
          $('#inputNISN').focus().select();
        }, 500);
        console.error('AJAX Error:', error);
      }
    });
  });

  // Handler untuk tombol Kembali dari step 2 ke step 1
  $(document).on('click', '#btnKembaliNISN', function(e) {
    e.preventDefault();
    showInputNisnStep();
  });

  // Handler untuk tombol Buat Usulan
  $(document).on('click', '#btnBuatUsulan', function(e) {
    e.preventDefault();
    var nisn = $('#inputNISN').val().trim();
    var alasanUsulan = $('#alasanUsulan').val() || 'Usulan dari Wali Kelas';
    
    // Konfirmasi dengan SweetAlert
    swal({
      title: 'Konfirmasi Usulan',
      text: 'Usulan PIP akan langsung disetujui. Apakah Anda yakin ingin melanjutkan?',
      icon: 'warning',
      buttons: {
        cancel: {
          text: 'Batal',
          value: false,
          visible: true,
          className: 'btn btn-secondary'
        },
        confirm: {
          text: 'Ya, Setujui Usulan',
          value: true,
          visible: true,
          className: 'btn btn-success'
        }
      }
    }).then((result) => {
      if (!result) {
        return;
      }
      
      // Show loading
      showLoadingStep();
    
      // AJAX request untuk membuat usulan
      $.ajax({
        url: './mod/usulan-pip-semua/proses.php?action=create',
        type: 'POST',
        data: { 
          nisn: nisn,
          alasan_usulan: alasanUsulan
        },
        dataType: 'json',
        success: function(response) {
          hideLoadingStep();
          if (response.success) {
            swal({
              icon: 'success',
              title: 'Berhasil!',
              text: 'Usulan PIP berhasil dibuat dan langsung disetujui!',
              button: 'OK'
            }).then(() => {
              $('#tambahUsulanModal').modal('hide');
              // Reload DataTable
              if (table && typeof table.ajax.reload === 'function') {
                table.ajax.reload(null, false);
              } else {
                location.reload();
              }
            });
          } else {
            swal({
              icon: 'error',
              title: 'Gagal Membuat Usulan',
              text: response.message,
              button: 'OK'
            });
          }
        },
        error: function(xhr, status, error) {
          hideLoadingStep();
          swal({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Terjadi kesalahan saat membuat usulan',
            button: 'OK'
          });
          console.error('AJAX Error:', error);
        }
      });
    });
  });

  // Functions untuk mengelola step modal
  function resetTambahUsulanModal() {
    $('#inputNISN').val('').removeClass('is-valid is-invalid');
    $('#nisn-feedback').hide();
    $('#alasanUsulan').val('Usulan dari Wali Kelas');
    $('#dataSiswaContainer').html('');
    showInputNisnStep();
  }

  function showInputNisnStep() {
    $('#step-input-nisn').show();
    $('#step-konfirmasi-siswa').hide();
    $('#loading-indicator').hide();
    $('#inputNISN').focus();
  }

  function showKonfirmasiStep(data) {
    $('#step-input-nisn').hide();
    $('#step-konfirmasi-siswa').show();
    $('#loading-indicator').hide();
    
    // Format data siswa
    var avatarSrc = (data.avatar && data.avatar !== 'avatar.jpg') ? 
                    '../content/avatar/' + data.avatar : 
                    '../content/avatar/avatar.jpg';
    
    // Validasi requirements
    var foto_valid = data.avatar && data.avatar !== 'avatar.jpg' && data.avatar !== '' && data.avatar !== null;
    var identitas_valid = data.status && data.status.toLowerCase() === 'aktif' && 
                          data.konfirmasi && data.konfirmasi.toLowerCase() === 'sesuai';
    var berkas_valid = data.validasi_berkas && data.validasi_berkas.toLowerCase() === 'valid';
    
    var allValid = foto_valid && identitas_valid && berkas_valid;
    
    var validationCardsHtml = `
      <div class="verification-cards mb-4">
        <!-- Foto Profil Card -->
        <div class="verification-card ${foto_valid ? 'valid' : 'invalid'}">
          <div class="card-icon">
            ${foto_valid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>'}
          </div>
          <div class="card-content">
            <h6 class="card-title">Foto Profil</h6>
            <p class="card-description">${foto_valid ? 'Foto profil sudah tersedia dan valid' : 'Foto profil belum diunggah atau masih default'}</p>
          </div>
          <div class="card-status">
            <span class="status-badge ${foto_valid ? 'valid' : 'invalid'}">
              ${foto_valid ? '<i class="fas fa-check"></i> VALID' : '<i class="fas fa-times"></i> BELUM VALID'}
            </span>
          </div>
        </div>

        <!-- Status Identitas Card -->
        <div class="verification-card ${identitas_valid ? 'valid' : 'invalid'}">
          <div class="card-icon">
            ${identitas_valid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>'}
          </div>
          <div class="card-content">
            <h6 class="card-title">Status Identitas</h6>
            <p class="card-description">
              Status: <strong>${(data.status || 'Tidak Diketahui').charAt(0).toUpperCase() + (data.status || 'Tidak Diketahui').slice(1)}</strong><br>
              Konfirmasi: <strong>${(data.konfirmasi || 'Belum').charAt(0).toUpperCase() + (data.konfirmasi || 'Belum').slice(1)}</strong>
            </p>
          </div>
          <div class="card-status">
            <span class="status-badge ${identitas_valid ? 'valid' : 'invalid'}">
              ${identitas_valid ? '<i class="fas fa-check"></i> VALID' : '<i class="fas fa-times"></i> BELUM VALID'}
            </span>
          </div>
        </div>

        <!-- Validasi Berkas Card -->
        <div class="verification-card ${berkas_valid ? 'valid' : 'invalid'}">
          <div class="card-icon">
            ${berkas_valid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>'}
          </div>
          <div class="card-content">
            <h6 class="card-title">Validasi Berkas</h6>
            <p class="card-description">${berkas_valid ? 'Berkas sudah divalidasi dan lengkap' : 'Berkas belum lengkap atau belum divalidasi'}</p>
          </div>
          <div class="card-status">
            <span class="status-badge ${berkas_valid ? 'valid' : 'invalid'}">
              ${berkas_valid ? '<i class="fas fa-check"></i> VALID' : '<i class="fas fa-times"></i> BELUM VALID'}
            </span>
          </div>
        </div>
      </div>
      
      ${allValid ? `
      <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <strong>Semua Persyaratan Terpenuhi!</strong> Siswa memenuhi semua persyaratan untuk usulan PIP.
      </div>
      ` : `
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Catatan:</strong> Beberapa persyaratan belum terpenuhi. Usulan dapat dibuat namun mungkin memerlukan tindak lanjut untuk melengkapi data siswa.
      </div>
      `}
    `;
    
    var dataSiswaHtml = `
      ${validationCardsHtml}
      
      <div class="card border-primary">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-3 text-center">
              <img src="${avatarSrc}" alt="avatar" class="img-fluid rounded-circle border border-primary" 
                   style="width:80px;height:80px;object-fit:cover;">
              <h5 class="mt-2 mb-1 text-primary">${data.nama_lengkap || '-'}</h5>
              <small class="text-muted">NISN: ${data.nisn || '-'}</small>
            </div>
            <div class="col-md-9">
              <div class="row">
                <div class="col-md-6">
                  <table class="table table-sm table-borderless">
                    <tr><td width="40%" class="text-muted">Nama Lengkap</td><td class="font-weight-bold">${data.nama_lengkap || '-'}</td></tr>
                    <tr><td class="text-muted">NISN</td><td class="font-weight-bold">${data.nisn || '-'}</td></tr>
                    <tr><td class="text-muted">Kelas</td><td class="font-weight-bold">${data.nama_kelas || '-'}</td></tr>
                    <tr><td class="text-muted">Jenis Kelamin</td><td>${data.jenis_kelamin || '-'}</td></tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-sm table-borderless">
                    <tr><td width="40%" class="text-muted">Tempat Lahir</td><td>${data.tempat_lahir || '-'}</td></tr>
                    <tr><td class="text-muted">Tanggal Lahir</td><td>${data.tanggal_lahir || '-'}</td></tr>
                    <tr><td class="text-muted">No. HP</td><td>${data.telp || '-'}</td></tr>
                    <tr><td class="text-muted">Email</td><td>${data.email || '-'}</td></tr>
                  </table>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-6">
                  <h6 class="text-secondary mb-2">Data Orang Tua</h6>
                  <table class="table table-sm table-borderless">
                    <tr><td width="40%" class="text-muted">Nama Ayah</td><td class="font-weight-bold">${data.nama_ayah || '-'}</td></tr>
                    <tr><td class="text-muted">Pekerjaan Ayah</td><td>${data.pekerjaan_ayah || '-'}</td></tr>
                    <tr><td class="text-muted">Penghasilan Ayah</td><td>${data.penghasilan_ayah || 'Tidak Tetap'}</td></tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <h6 class="text-secondary mb-2">&nbsp;</h6>
                  <table class="table table-sm table-borderless">
                    <tr><td width="40%" class="text-muted">Nama Ibu</td><td class="font-weight-bold">${data.nama_ibu || '-'}</td></tr>
                    <tr><td class="text-muted">Pekerjaan Ibu</td><td>${data.pekerjaan_ibu || '-'}</td></tr>
                    <tr><td class="text-muted">Penghasilan Ibu</td><td>${data.penghasilan_ibu || 'Tidak Tetap'}</td></tr>
                  </table>
                </div>
              </div>
              ${(data.nama_wali && data.nama_wali !== '') ? `
              <div class="row">
                <div class="col-md-12">
                  <h6 class="text-secondary mb-2">Data Wali</h6>
                  <table class="table table-sm table-borderless">
                    <tr><td width="20%" class="text-muted">Nama Wali</td><td class="font-weight-bold">${data.nama_wali}</td></tr>
                    <tr><td class="text-muted">Pekerjaan Wali</td><td>${data.pekerjaan_wali || '-'}</td></tr>
                  </table>
                </div>
              </div>
              ` : ''}
              </div>
              ${data.alamat_lengkap ? `
              <div class="border-top pt-2 mt-2">
                <small class="text-muted">Alamat:</small>
                <p class="mb-0 font-weight-bold">${data.alamat_lengkap}</p>
              </div>
              ` : ''}
            </div>
          </div>
        </div>
      </div>
    `;
    
    $('#dataSiswaContainer').html(dataSiswaHtml);
    $('#alasanUsulan').focus();
  }

  function showLoadingStep() {
    $('#step-input-nisn').hide();
    $('#step-konfirmasi-siswa').hide();
    $('#loading-indicator').show();
  }

  function hideLoadingStep() {
    $('#loading-indicator').hide();
  }
  // Load custom CSS if not already loaded
  if (!$('link[href*="usulan-pip-semua/style.css"]').length) {
    $('<link rel="stylesheet" type="text/css" href="./mod/usulan-pip-semua/style.css">').appendTo('head');
  }

  initDataTable();

  // Restore filter state from localStorage
  var savedKelas = localStorage.getItem('usulan_pip_filter_kelas') || '';
  var savedStatus = localStorage.getItem('usulan_pip_filter_status') || '';
  if (savedKelas) $('.filter-kelas').val(savedKelas);
  if (savedStatus) $('.filter-status').val(savedStatus);

  // Event filter kelas
  $(document).on('change', '.filter-kelas', function() {
    localStorage.setItem('usulan_pip_filter_kelas', $(this).val());
    if (table) {
      table.ajax.reload();
    }
  });
  
  // Event filter status  
  $(document).on('change', '.filter-status', function() {
    localStorage.setItem('usulan_pip_filter_status', $(this).val());
    if (table) {
      table.ajax.reload();
    }
  });


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

    // Approve action: confirm then POST status only (no KKS/KIP)
    if ($self.hasClass('btn-approve')) {
      e.preventDefault();
      var id = $self.attr('data-id') || $self.data('id');

      // Get the full usulan data first
      $.get('./mod/usulan-pip-semua/proses.php', { action: 'get', id: id })
        .done(function(getResp){
          var resp = getResp;
          try { if (typeof getResp === 'string') resp = JSON.parse(getResp); } catch(e) { resp = { success: false }; }
          if (!resp || !resp.success) {
            swal('Error', 'Gagal memuat data untuk konfirmasi', 'error');
            return;
          }
          var d = resp.data || {};

          // Then fetch kriteria list
          $.get('./mod/kriteria-pip/proses.php', { action: 'list_kriteria' })
            .done(function(kresp){
              var kre = kresp;
              try { if (typeof kresp === 'string') kre = JSON.parse(kresp); } catch(e) { kre = { success: false }; }
              var kriteriaList = [];
              if (kre && kre.success && Array.isArray(kre.data)) {
                kriteriaList = kre.data.map(function(r){ return r.nama_kriteria; });
              } else {
                kriteriaList = ['Memiliki KIP','Memiliki KPS/KKS/PKH','Penghasilan Orang Tua','Tempat Tinggal'];
              }

              var alasan = (d.alasan_usulan || '').toString().trim().toLowerCase();
              var preCheckAll = (alasan === 'sesuai');

              // Build modal using template literal for readability
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
                html += '<div class="form-check">';
                html += '<input class="form-check-input approve-kriteria-checkbox" type="checkbox" value="' + escapeHtml(kriteriaList[i]) + '" id="' + modalId + '_' + key + '" ' + checked + '>';
                html += '<label class="form-check-label" for="' + modalId + '_' + key + '">' + escapeHtml(kriteriaList[i]) + '</label>';
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
                $.post('./mod/usulan-pip-semua/proses.php?action=status', postData)
                  .done(function (postResp) {
                    var presp = postResp;
                    try { if (typeof postResp === 'string') presp = JSON.parse(postResp); } catch (e) {}
                    if (presp && presp.success) {
                      $m.modal('hide');
                      $m.remove();
                      swal({ title: 'Berhasil!', text: 'Usulan telah disetujui', icon: 'success' }).then(function () {
                        closeCurrentModal();
                        if (table) table.ajax.reload();
                        try { window.location.reload(); } catch (e) { /* ignore */ }
                      });
                      // Fallback: force reload after short delay in case swal.then isn't available in this swal version
                      setTimeout(function () { try { window.location.reload(); } catch (e) { /* ignore */ } }, 2000);
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
      
      $.post('./mod/usulan-pip-semua/proses.php?action=status', { 
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
      var id = $self.attr('data-id');
      var status = $self.attr('data-status');
      $.post('./mod/usulan-pip-semua/proses.php?action=status', { id: id, status: status }, function (data) {
        if (data == 'success') {
          if (status == '-') { $('.badge' + id).attr('class', 'badge' + id + ' badge badge-info').html('Diproses'); }
          else if (status == 'Y') { $('.badge' + id).attr('class', 'badge' + id + ' badge badge-success').html('Selesai'); }
          else if (status == 'N') { $('.badge' + id).attr('class', 'badge' + id + ' badge badge-danger').html('Ditolak'); }
  } else { /* debug removed */ }
      });
      return;
    }

    // Delete handler
    if ($self.hasClass('btn-delete')) {
      var id = $self.attr('data-id');
      swal({ 
        title: 'Konfirmasi Penghapusan',
        text: 'Anda yakin ingin menghapus data usulan PIP ini?\n\nData dan berkas terkait akan dihapus permanen dari database dan tidak dapat dikembalikan.', 
        icon: 'warning', 
        buttons: {
          cancel: {
            text: 'Batal',
            visible: true,
            className: 'btn btn-secondary'
          },
          confirm: {
            text: 'Ya, Hapus Data',
            className: 'btn btn-danger'
          }
        },
        dangerMode: true
      })
        .then(function (willDelete) {
          if (!willDelete) return false;
          loading();
          $.post('./mod/usulan-pip-semua/proses.php?action=delete', { id: id }, function (data) {
            var resp = data;
            try { if (typeof data === 'string') resp = JSON.parse(data); } catch (e) { /* ignore */ }
            if (resp && resp.success) {
              var message = resp.message || 'Data berhasil dihapus dari database!';
              swal({ 
                title: 'Berhasil!', 
                text: message, 
                icon: 'success', 
                timer: 3000,
                buttons: {
                  confirm: {
                    text: 'OK',
                    className: 'btn btn-success'
                  }
                }
              });
              if (table && table.ajax) table.ajax.reload(null, false);
              // Reload page to update statistics
              setTimeout(function() {
                try { window.location.reload(); } catch (e) { /* ignore */ }
              }, 1500);
            } else {
              var msg = (resp && resp.message) ? resp.message : 'Terjadi kesalahan saat menghapus data';
              swal({ 
                title: 'Gagal!', 
                text: msg, 
                icon: 'error', 
                timer: 4000,
                buttons: {
                  confirm: {
                    text: 'OK',
                    className: 'btn btn-primary'
                  }
                }
              });
            }
          }).fail(function(xhr, status, error) {
            swal({ 
              title: 'Error!', 
              text: 'Koneksi gagal: ' + error, 
              icon: 'error',
              buttons: {
                confirm: {
                  text: 'OK',
                  className: 'btn btn-primary'
                }
              }
            });
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
    url: './mod/usulan-pip-semua/proses.php',
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