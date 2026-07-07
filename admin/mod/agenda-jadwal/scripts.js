"use strict";

// Detect which sub-tab is active
var urlParams = new URLSearchParams(window.location.search);
var sub = urlParams.get('sub') || 'jadwal';
var jadwalTable, agendaTable;

if (sub === 'jadwal') loadJadwal();
else if (sub === 'agenda') loadAgenda();
else if (sub === 'edit-request') loadEditRequests();

function loadJadwal() {
  jadwalTable = $(".datatable-jadwal").DataTable({
    scrollX: true, processing: true, serverSide: true, bDestroy: true,
    ajax: {
      url: "./mod/agenda-jadwal/datatable.php?type=jadwal",
      type: "POST",
      data: function (d) {
        d.kelas_id = $('#filter-kelas-jadwal').val();
      }
    },
    columns: [
      { title: "No", className: "text-center" },
      { title: "Kelas", className: "text-center" },
      { title: "Hari", className: "text-center" },
      { title: "Jam Ke", className: "text-center" },
      { title: "Mata Pelajaran", className: "text-center" },
      { title: "Guru", className: "text-center" },
    ],
    language: { paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" } },
  });
}

function loadAgenda() {
  agendaTable = $(".datatable-agenda").DataTable({
    scrollX: true, processing: true, serverSide: true, bDestroy: true,
    ajax: {
      url: "./mod/agenda-jadwal/datatable.php?type=agenda",
      type: "POST",
      data: function (d) {
        d.kelas_id = $('#filter-kelas-agenda').val();
        d.tanggal = $('#filter-tanggal').val();
      }
    },
    columns: [
      { title: "No", className: "text-center" },
      { title: "Kelas", className: "text-center" },
      { title: "Tanggal", className: "text-center" },
      { title: "Jam", className: "text-center" },
      { title: "Mapel", className: "text-center" },
      { title: "Guru", className: "text-center" },
      { title: "Kehadiran", className: "text-center" },
      { title: "Materi", className: "text-center" },
      { title: "Foto", className: "text-center" },
    ],
    language: { paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" } },
  });
}

function loadEditRequests() {
  $(".datatable-edit").DataTable({
    scrollX: true, processing: true, serverSide: true, bDestroy: true,
    ajax: { url: "./mod/agenda-jadwal/datatable.php?type=edit-request", type: "POST" },
    columns: [
      { title: "No", className: "text-center" },
      { title: "Kelas", className: "text-center" },
      { title: "Tanggal", className: "text-center" },
      { title: "Koordinator", className: "text-center" },
      { title: "Alasan", className: "text-center" },
      { title: "Status", className: "text-center" },
      { title: "Waktu", className: "text-center" },
      { title: "Aksi", className: "text-center" },
    ],
    language: { paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" } },
  });
}

// Approve agenda edit
$(document).on("click", ".btn-approve-agenda", function () {
  var id = $(this).data("id");
  swal({
    title: "Setujui Permintaan Edit?",
    text: "Koordinator kelas akan dapat mengedit agenda.",
    icon: "info",
    buttons: { cancel: "Batal", confirm: { text: "Ya, Setujui", value: true } },
  }).then(function (ok) {
    if (ok) {
      $.ajax({
        url: "./mod/agenda-jadwal/proses.php?action=approve",
        type: "POST",
        data: { id: id },
        success: function (res) {
          swal("Info", res, res.includes("berhasil") ? "success" : "error");
          loadEditRequests();
        },
      });
    }
  });
});

// Reject agenda edit
$(document).on("click", ".btn-reject-agenda", function () {
  var id = $(this).data("id");
  swal({
    title: "Tolak Permintaan Edit?",
    icon: "warning",
    dangerMode: true,
    buttons: { cancel: "Batal", confirm: { text: "Ya, Tolak", value: true } },
  }).then(function (ok) {
    if (ok) {
      $.ajax({
        url: "./mod/agenda-jadwal/proses.php?action=reject",
        type: "POST",
        data: { id: id },
        success: function (res) {
          swal("Info", res, res.includes("berhasil") ? "success" : "error");
          loadEditRequests();
        },
      });
    }
  });
});

// Filter modal handlers
$(document).on('click', '.btn-open-filter-jadwal', function () {
  var $modal = $('.modal-filter-jadwal');
  if (!$modal.length) return;
  $modal.modal({ backdrop: false, keyboard: true, show: true });
  setTimeout(function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  }, 30);
});
$(document).on('click', '.btn-apply-filter-jadwal', function () {
  $('.modal-filter-jadwal').modal('hide');
  setTimeout(function () {
    if (sub === 'jadwal' && jadwalTable) jadwalTable.ajax.reload();
    else if (sub === 'agenda' && agendaTable) agendaTable.ajax.reload();
  }, 220);
});
$(document).on('click', '.btn-reset-filter-jadwal', function () {
  $('#filter-kelas-jadwal, #filter-kelas-agenda').val('');
  $('#filter-tanggal').val('');
  $('.modal-filter-jadwal').modal('hide');
  setTimeout(function () {
    if (sub === 'jadwal' && jadwalTable) jadwalTable.ajax.reload();
    else if (sub === 'agenda' && agendaTable) agendaTable.ajax.reload();
  }, 220);
});
$(document).on('hidden.bs.modal', '.modal-filter-jadwal', function () {
  $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
  $('.modal-backdrop').remove();
});
