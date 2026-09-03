'use strict';

$(function () {
  if ($.fn.DataTable && $('.surat-dashboard-table').length) {
    $('.surat-dashboard-table').DataTable({
      iDisplayLength: 10,
      aaSorting: [[3, 'desc']],
      responsive: true,
      language: {
        paginate: { previous: '<', next: '>' }
      }
    });
  }
});

$(document).on('submit', '.form-surat-dummy', function (e) {
  e.preventDefault();
  swal({
    title: 'Info',
    text: 'Form masih menggunakan data dummy. Integrasi database akan dibuat pada tahap berikutnya.',
    icon: 'info'
  });
});

$(document).on('click', '.btn-detail-surat', function () {
  swal({
    title: 'Detail Surat',
    text: 'Detail surat dummy akan ditampilkan lengkap setelah tabel database tersedia.',
    icon: 'info'
  });
});
