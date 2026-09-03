"use strict";

$(function () {
  if ($.fn.DataTable && $("#table-skl-history").length) {
    $("#table-skl-history").DataTable({
      pageLength: 25,
      order: [[0, "desc"]],
      scrollX: true,
      scrollCollapse: true,
      responsive: false
    });
  }

  // Filter modal
  $(document).on('click', '.btn-open-filter-history', function () {
    var $modal = $('.modal-filter-history');
    if (!$modal.length) return;
    $modal.modal({ backdrop: false, keyboard: true, show: true });
    setTimeout(function () {
      $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
      $('.modal-backdrop').remove();
    }, 30);
  });
  $(document).on('hidden.bs.modal', '.modal-filter-history', function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  });
});
