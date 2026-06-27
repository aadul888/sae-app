'use strict';

/* ====== SURAT INDEX / REFERENSI SURAT ====== */

$(function () {
  // Table tanpa DataTables — gunakan CSS table-striped murni untuk hindari konflik
  // Fungsi DataTable hanya di-enable jika diperlukan nanti
});

/* ====== Copy Index ====== */
$(document).on('click', '.btn-copy-index', function () {
  var val = $(this).data('value') || '';
  if (navigator.clipboard && val) navigator.clipboard.writeText(val);
  swal({ title: 'Disalin!', text: val, icon: 'success', timer: 1200 });
});

/* ====== FORM TAMBAH / EDIT INDEX ====== */
$(document).on('submit', '#formIndex', function (e) {
  e.preventDefault();
  var form = this;
  var fd = new FormData(form);
  $.ajax({
    url: './mod/surat-index/proses.php?action=' + (fd.get('id') > 0 ? 'update' : 'add'),
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    success: function (data) {
      if (data.trim() === 'success') {
        swal({ title: 'Berhasil!', text: 'Data indeks tersimpan.', icon: 'success', timer: 1500 });
        $('#modalIndex').modal('hide');
        setTimeout(function () { location.reload(); }, 1200);
      } else {
        swal({ title: 'Gagal!', text: data, icon: 'error' });
      }
    },
    error: function () { swal({ title: 'Error!', text: 'Koneksi gagal.', icon: 'error' }); }
  });
});

/* ====== EDIT INDEX (modal populate) ====== */
$(document).on('click', '.btn-edit-index', function () {
  var id = $(this).data('id');
  $.get('./mod/surat-index/proses.php?action=get&id=' + id, function (data) {
    try {
      var d = typeof data === 'object' ? data : JSON.parse(data);
      if (d.status === 'success' && d.data) {
        $('#modalIndexTitle').html('<i class="fas fa-edit mr-2"></i>Edit Indeks');
        $('#f_id').val(d.data.id);
        $('#f_indeks').val(d.data.indeks).prop('readonly', true);
        $('#f_perihal').val(d.data.perihal);
        $('#f_kategori').val(d.data.kategori);
        $('#f_jenis').val(d.data.jenis_surat);
        $('#modalIndex').modal('show');
      } else {
        swal({ title: 'Error!', text: d.message || 'Data tidak ditemukan.', icon: 'error' });
      }
    } catch (e) {
      swal({ title: 'Error!', text: 'Gagal parse data.', icon: 'error' });
    }
  });
});

// Reset modal tambah
$('#modalIndex').on('hidden.bs.modal', function () {
  if ($('#f_id').val() == 0) return;
  $('#f_id').val(0);
  $('#f_indeks').prop('readonly', false).val('');
  $('#f_perihal').val('');
  $('#modalIndexTitle').html('<i class="fas fa-plus mr-2"></i>Tambah Indeks');
});

/* ====== DELETE INDEX ====== */
$(document).on('click', '.btn-delete-index', function () {
  var id = $(this).data('id');
  var btn = $(this);
  swal({
    title: 'Hapus Indeks?',
    text: 'Data akan dihapus permanen.',
    icon: 'warning',
    buttons: ['Batal', 'Ya, Hapus!'],
    dangerMode: true
  }).then(function (confirm) {
    if (!confirm) return;
    $.post('./mod/surat-index/proses.php?action=delete', { id: id }, function (data) {
      if (data.trim() === 'success') {
        swal({ title: 'Berhasil!', text: 'Indeks dihapus.', icon: 'success', timer: 1500 });
        setTimeout(function () { location.reload(); }, 1200);
      } else {
        swal({ title: 'Gagal!', text: data, icon: 'error' });
      }
    });
  });
});

/* ====== IMPORT EXCEL ====== */
$(document).on("submit", "#formImportExcel, .form-import-excel", function (e) {
  e.preventDefault();
  $.ajax({
    url: "./mod/surat-index/proses.php?action=import_excel",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    async: false,
    success: function (data) {
      if (typeof data === "string" && data.indexOf("Akses ditolak") !== -1) {
        swal({ title: "Gagal!", text: data, icon: "error", timer: 1800 });
        setTimeout(function () { window.location.reload(); }, 1800);
        return;
      }
      if (data === "success" || (typeof data === "string" && data.trim() === "success")) {
        swal({ title: "Berhasil!", text: "Data indeks berhasil diimport!", icon: "success", timer: 2500 });
        $(".form-import").trigger("reset");
        $(".modal-import").modal("hide");
        setTimeout(function () { location.reload(); }, 1500);
      } else {
        var msg = typeof data === "string" ? data : "Import gagal.";
        swal({ title: "Gagal!", text: msg, icon: "error", timer: 3000 });
      }
    },
    complete: function () {},
  });
});

/* ====== UPLOAD TEMPLATE WORD ====== */
$(document).on('submit', '#formUploadTemplate', function (e) {
  e.preventDefault();
  var fd = new FormData(this);
  $.ajax({
    url: './mod/surat-index/proses.php?action=upload_template',
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    success: function (data) {
      try {
        var res = typeof data === 'object' ? data : JSON.parse(data);
        if (res.status === 'success') {
          swal({ title: 'Berhasil!', text: res.message || 'Template diupload.', icon: 'success', timer: 2000 });
          setTimeout(function () { location.reload(); }, 1500);
        } else {
          swal({ title: 'Gagal!', text: res.message || data, icon: 'error' });
        }
      } catch (e) {
        swal({ title: 'Error!', text: data, icon: 'error' });
      }
    },
    error: function () { swal({ title: 'Error!', text: 'Koneksi gagal.', icon: 'error' }); }
  });
});

/* ====== EXPORT EXCEL ====== */
$(document).on('click', '.btn-export-index', function () {
  window.location.href = './mod/surat-index/proses.php?action=export_excel';
});

/* ====== DOWNLOAD TEMPLATE EXCEL ====== */
$(document).on('click', '#downloadTemplateExcel', function (e) {
  e.preventDefault();
  window.location.href = './mod/surat-index/proses.php?action=download_template_excel';
});
