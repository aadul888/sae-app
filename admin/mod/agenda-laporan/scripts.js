"use strict";

var urlParams = new URLSearchParams(window.location.search);
var tab = urlParams.get('tab') || 'guru';

buildHeader();
loadLaporan();

$(document).on('click', '.btn-open-filter-laporan', function () {
  var $modal = $('.modal-filter-laporan');
  if (!$modal.length) return;
  $modal.modal({ backdrop: false, keyboard: true, show: true });
  setTimeout(function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  }, 30);
});
$(document).on('click', '.btn-apply-filter-laporan', function () {
  $('.modal-filter-laporan').modal('hide');
  loadLaporan();
});
$(document).on('click', '.btn-reset-filter-laporan', function () {
  $('#filter-dari').val('');
  $('#filter-sampai').val('');
  $('#filter-guru, #filter-kelas, #filter-mapel').val('');
  $('.modal-filter-laporan').modal('hide');
  loadLaporan();
});
$(document).on('hidden.bs.modal', '.modal-filter-laporan', function () {
  $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
  $('.modal-backdrop').remove();
});

function buildHeader() {
  var label = tab === 'guru' ? 'Nama Guru' : (tab === 'kelas' ? 'Nama Kelas' : 'Mata Pelajaran');
  var html = '<th class="text-center" width="4">No</th>' +
    '<th class="text-center">' + label + '</th>' +
    '<th class="text-center">Total Jam</th>' +
    '<th class="text-center">Hadir</th>' +
    '<th class="text-center">Tidak Hadir</th>' +
    '<th class="text-center">Tidak Hadir + Tugas</th>' +
    '<th class="text-center">% Kehadiran</th>';
  $('#table-header').html(html);
}

function loadLaporan() {
  var postData = {
    tab: tab,
    dari: $('#filter-dari').val(),
    sampai: $('#filter-sampai').val(),
  };
  if (tab === 'guru') postData.guru_id = $('#filter-guru').val();
  if (tab === 'kelas') postData.kelas_id = $('#filter-kelas').val();
  if (tab === 'mapel') postData.mapel_id = $('#filter-mapel').val();

  $.ajax({
    url: './mod/agenda-laporan/proses.php',
    type: 'POST',
    data: postData,
    dataType: 'json',
    success: function (res) {
      renderSummary(res.summary);
      renderTable(res.data);
    },
    error: function () {
      swal('Error', 'Gagal memuat data laporan', 'error');
    }
  });
}

function renderSummary(s) {
  if (!s) { $('#summary-cards').html(''); return; }
  var total = parseInt(s.total) || 0;
  var hadir = parseInt(s.hadir) || 0;
  var tidak = parseInt(s.tidak_hadir) || 0;
  var tugas = parseInt(s.tidak_hadir_tugas) || 0;
  var pct = total > 0 ? (hadir / total * 100).toFixed(1) : 0;

  var html =
    '<div class="col-12">' +
      '<div class="card user-stats-panel module-stats-shell mb-3">' +
        '<div class="card-body py-2 px-2 px-md-3">' +
          '<div class="user-stats-wrap">' +
            '<div class="user-stats module-stats-grid">' +
              '<div class="module-stat-card user-stat-total">' +
                '<div class="info"><span class="label">Total Jam</span><span class="value">' + total + '</span></div>' +
                '<div class="icon"><i class="fas fa-clock"></i></div>' +
              '</div>' +
              '<div class="module-stat-card user-stat-berkas-valid">' +
                '<div class="info"><span class="label">Hadir</span><span class="value text-success">' + hadir + '</span></div>' +
                '<div class="icon"><i class="fas fa-check-circle"></i></div>' +
              '</div>' +
              '<div class="module-stat-card user-stat-belum-sesuai">' +
                '<div class="info"><span class="label">Tidak Hadir</span><span class="value text-danger">' + tidak + '</span></div>' +
                '<div class="icon"><i class="fas fa-times-circle"></i></div>' +
              '</div>' +
              '<div class="module-stat-card user-stat-identitas">' +
                '<div class="info"><span class="label">% Kehadiran</span><span class="value">' + pct + '%</span></div>' +
                '<div class="icon"><i class="fas fa-percentage"></i></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>' +
    '</div>';

  $('#summary-cards').html(html);
}

function renderTable(data) {
  // Destroy previous DataTable if exists
  if ($.fn.DataTable.isDataTable('.datatable-laporan')) {
    $('.datatable-laporan').DataTable().destroy();
  }

  var tbody = '';
  for (var i = 0; i < data.length; i++) {
    var r = data[i];
    var pctClass = r.persen_hadir >= 80 ? 'text-success' : (r.persen_hadir >= 50 ? 'text-warning' : 'text-danger');
    tbody += '<tr>' +
      '<td class="text-center">' + (i + 1) + '</td>' +
      '<td class="text-center font-weight-bold">' + r.nama + '</td>' +
      '<td class="text-center">' + r.total + '</td>' +
      '<td class="text-center"><span class="badge badge-success">' + r.hadir + '</span></td>' +
      '<td class="text-center"><span class="badge badge-danger">' + r.tidak_hadir + '</span></td>' +
      '<td class="text-center"><span class="badge badge-warning">' + r.tidak_hadir_tugas + '</span></td>' +
      '<td class="text-center ' + pctClass + ' font-weight-bold">' + r.persen_hadir + '%</td>' +
      '</tr>';
  }
  if (data.length === 0) {
    tbody = '<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data untuk filter yang dipilih</td></tr>';
  }

  $('.datatable-laporan tbody').html(tbody);

  if (data.length > 0) {
    $('.datatable-laporan').DataTable({
      paging: true, searching: false, info: true, ordering: true,
      scrollX: true, scrollCollapse: true, responsive: false,
      language: { paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" } },
    });
  }
}
