"use strict";

var tablePembelajaran;

function cleanupPembelajaranModal() {
  var hasVisible = $('.modal.show:visible').length > 0;
  if (!hasVisible) {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  }
}

function openPembelajaranFilterModal() {
  var $modal = $('.modal-filter-pembelajaran');
  if (!$modal.length) return;
  cleanupPembelajaranModal();
  $modal.modal({ backdrop: false, keyboard: true, show: true });
  setTimeout(function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  }, 30);
}

$(document).ready(function () {
  tablePembelajaran = $(".datatable-pembelajaran").DataTable({
    processing: true,
    serverSide: true,
    bAutoWidth: false,
    bStateSave: true,
    bDestroy: true,
    paging: true,
    iDisplayLength: 25,
    scrollX: true,
    order: [],
    aLengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
    language: {
      paginate: {
        previous: "<i class='fas fa-angle-left'>",
        next: "<i class='fas fa-angle-right'>"
      }
    },
    ajax: {
      url: "./mod/pembelajaran/datatable.php",
      type: "GET",
      data: function (d) {
        d.rombel = $(".filter-rombel").val();
        d.guru = $(".filter-guru").val();
        d.status_kurikulum = $(".filter-status-kurikulum").val();
      }
    },
    columnDefs: [
      { targets: [0, 5], className: "text-center" }
    ]
  });

  $('.datatable-pembelajaran').on('xhr.dt', function (e, settings, json) {
    if (!json || !json.stats) return;
    $('#pemb-card-jam').text(json.stats.total_jam || 0);
    $('#pemb-card-mapel').text(json.stats.total_mapel || 0);
    $('#pemb-card-rombel').text(json.stats.total_rombel || 0);
    $('#pemb-card-guru').text(json.stats.total_guru || 0);
  });

  $(document).on('click', '.btn-open-filter-pembelajaran', function (e) {
    e.preventDefault();
    openPembelajaranFilterModal();
  });

  $('.modal-filter-pembelajaran').on('shown.bs.modal', function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  });

  $(document).on('click', '.btn-apply-filter-pembelajaran', function () {
    $('.modal-filter-pembelajaran').modal('hide');
    setTimeout(function () { if (tablePembelajaran) tablePembelajaran.ajax.reload(); }, 220);
  });

  $(document).on('click', '.btn-reset-filter-pembelajaran', function () {
    $('.filter-rombel').val('');
    $('.filter-guru').val('');
    $('.filter-status-kurikulum').val('');
    $('.modal-filter-pembelajaran').modal('hide');
    setTimeout(function () { if (tablePembelajaran) tablePembelajaran.ajax.reload(); }, 220);
  });

  $(document).on('hidden.bs.modal', '.modal', function () {
    cleanupPembelajaranModal();
  });
});
