"use strict";

var tableGuruNonaktif;

function cleanupGuruNonaktifModal() {
  var hasVisible = $('.modal.show:visible').length > 0;
  if (!hasVisible) {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  }
  if ($(window).width() >= 1200) $('.backdrop.d-xl-none').remove();
}

function hardUnlockGuruNonaktifIfStuck() {
  if (!$('.modal.show:visible').length && !$('.swal-overlay:visible').length) {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  }
}

function openGuruNonaktifFilterModal() {
  var $modal = $('.modal-filter-guru-nonaktif');
  if (!$modal.length) return;

  cleanupGuruNonaktifModal();
  $modal.modal({ backdrop: false, keyboard: true, show: true });
  setTimeout(function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  }, 30);
}

$(document).ready(function () {
  loadGuruNonaktifTable();

  $(document).off('.guruNonaktifModalFix');

  $(document).on('click.guruNonaktifModalFix', '.btn-open-filter-guru-nonaktif', function (e) {
    e.preventDefault();
    openGuruNonaktifFilterModal();
  });

  $('.modal-filter-guru-nonaktif').on('shown.bs.modal.guruNonaktifModalFix', function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  });

  $(document).on('click.guruNonaktifModalFix', '.btn-apply-filter-guru-nonaktif', function () {
    $('.modal-filter-guru-nonaktif').modal('hide');
    setTimeout(function () { if (tableGuruNonaktif) tableGuruNonaktif.ajax.reload(); }, 220);
  });

  $(document).on('click.guruNonaktifModalFix', '.btn-reset-filter-guru-nonaktif', function () {
    $('.filter-jenis-ptk').val('');
    $('.filter-status-kepegawaian').val('');
    $('.filter-jabatan-ptk').val('');
    $('.modal-filter-guru-nonaktif').modal('hide');
    setTimeout(function () { if (tableGuruNonaktif) tableGuruNonaktif.ajax.reload(); }, 220);
  });

  $(document).on('hidden.bs.modal.guruNonaktifModalFix', '.modal', function () {
    cleanupGuruNonaktifModal();
    hardUnlockGuruNonaktifIfStuck();
  });

  $(document).on('click.guruNonaktifModalFix', '.modal .close, .modal [data-dismiss="modal"]', function () {
    $(this).closest('.modal').modal('hide');
  });
});

function loadGuruNonaktifTable() {
  tableGuruNonaktif = $(".datatable-guru-nonaktif").DataTable({
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
      url: "./mod/guru-tidak-aktif/datatable.php",
      type: "GET",
      data: function (d) {
        d.jenis_ptk = $(".filter-jenis-ptk").val();
        d.status_kepegawaian = $(".filter-status-kepegawaian").val();
        d.jabatan_ptk = $(".filter-jabatan-ptk").val();
      }
    },
    columnDefs: [
      { targets: [0], className: "text-center", orderable: false }
    ]
  });
}

$(document).off('click.guruNonaktifCopyId').on('click.guruNonaktifCopyId', '.datatable-guru-nonaktif .copy-id-value', function (e) {
  e.preventDefault();
  var value = String($(this).data('copy') || '').trim();
  if (!value) return;

  var onSuccess = function () {
    if (typeof swal === 'function') {
      swal({
        title: 'Berhasil',
        text: 'ID berhasil dicopy ke clipboard',
        icon: 'success',
        timer: 1200,
        buttons: false
      });
    }
  };

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(value).then(onSuccess).catch(function () {
      var $tmp = $('<input type="text" />').val(value).appendTo('body');
      $tmp[0].select();
      document.execCommand('copy');
      $tmp.remove();
      onSuccess();
    });
    return;
  }

  var $tmp = $('<input type="text" />').val(value).appendTo('body');
  $tmp[0].select();
  document.execCommand('copy');
  $tmp.remove();
  onSuccess();
});
