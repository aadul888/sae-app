'use strict';

$(function () {
  if ($.fn.DataTable && $('.surat-keluar-table').length) {
    $('.surat-keluar-table').DataTable({
      iDisplayLength: 25,
      aaSorting: [[0, 'desc']],
      responsive: true,
      language: { paginate: { previous: '<', next: '>' } }
    });
  }
});

/* ====== Pilih indeks → auto nomor, isi detail ====== */
$(document).on('change', '#slcIndeks', function () {
  var opt = $(this).find(':selected');
  if (!opt.val()) { $('#detailSuratWrapper').slideUp(); return; }

  var indeks = opt.data('indeks');
  var perihal = opt.data('perihal');
  var kategori = opt.data('kategori');
  var templateFile = opt.data('template') || '';

  $('#fKategori').val(kategori);
  $('#fPerihal').val(perihal);
  $('#fIsiSurat').val('');
  $('#templatePreviewCard').hide();

  // Generate nomor surat via AJAX
  $.get('./mod/surat-keluar/proses.php?action=gen_nomor&indeks=' + indeks + '&kategori=' + encodeURIComponent(kategori), function (data) {
    $('#fNoSurat').val(data.trim());
  });

  // Jika ada template, load isinya
  if (templateFile) {
    $('#templateFileName').text('(' + templateFile + ')');
    $.get('./mod/surat-keluar/proses.php?action=load_template&file=' + encodeURIComponent(templateFile), function (data) {
      try { var d = JSON.parse(data); if (d.content) $('#fIsiSurat').val(d.content); } catch(e) {}
      if (data.trim()) $('#fIsiSurat').val(data.trim());
    });
    $('#templatePreviewCard').show();
  }

  $('#detailSuratWrapper').slideDown();
});

/* ====== SIMPAN SURAT ====== */
$(document).on('submit', '#formBuatSurat', function (e) {
  e.preventDefault();
  var btn = $('#btnSimpanSurat');
  btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

  var fd = new FormData(this);
  $.ajax({
    url: './mod/surat-keluar/proses.php?action=buat',
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    success: function (data) {
      try {
        var res = typeof data === 'object' ? data : JSON.parse(data);
        if (res.status === 'success') {
          swal({ title: 'Berhasil!', text: 'Surat tersimpan.', icon: 'success', timer: 1500 });
          if (res.download_url) {
            window.open(res.download_url, '_blank');
          }
          setTimeout(function () { location.reload(); }, 1200);
        } else {
          swal({ title: 'Gagal!', text: res.message || data, icon: 'error' });
        }
      } catch (e) {
        swal({ title: 'Error!', text: data, icon: 'error' });
      }
    },
    error: function () { swal({ title: 'Error!', text: 'Koneksi gagal.', icon: 'error' }); },
    complete: function () { btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan & Download'); }
  });
});

/* ====== HAPUS SURAT ====== */
$(document).on('click', '.btn-delete-keluar', function () {
  var id = $(this).data('id');
  swal({
    title: 'Hapus Surat?',
    text: 'Surat akan dihapus permanen.',
    icon: 'warning',
    buttons: ['Batal', 'Ya, Hapus!'],
    dangerMode: true
  }).then(function (ok) {
    if (!ok) return;
    $.post('./mod/surat-keluar/proses.php?action=delete', { id: id }, function (data) {
      if (data.trim() === 'success') {
        swal({ title: 'Berhasil!', text: 'Surat dihapus.', icon: 'success', timer: 1500 });
        setTimeout(function () { location.reload(); }, 1200);
      } else {
        swal({ title: 'Gagal!', text: data, icon: 'error' });
      }
    });
  });
});

/* ====== CETAK ====== */
$(document).on('click', '.btn-cetak-surat', function () {
  var id = $(this).data('id');
  window.open('./mod/surat-keluar/proses.php?action=cetak&id=' + id, '_blank');
});

/* ====== EXPORT ====== */
$(document).on('click', '.btn-export-surat-keluar', function () {
  window.location.href = './mod/surat-keluar/proses.php?action=export_excel';
});
