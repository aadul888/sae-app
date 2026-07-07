"use strict";

var tableLibur;

// Fungsi untuk memulai loading
function loading() {
  $(".btn-save").prop("disabled", true);
  $(".btn-save").html(
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
  );
  window.setTimeout(function () {
    $(".btn-save").prop("disabled", false);
    $(".btn-save").html('<i class="far fa-save"></i> Simpan');
  }, 2000);
}

function loadData() {
  if ($.fn.DataTable.isDataTable(".datatable")) {
    tableLibur = $(".datatable").DataTable();
    tableLibur.ajax.reload(null, false);
    return;
  }

  tableLibur = $(".datatable").DataTable({
    scrollY: false,
    scrollX: false,
    processing: true,
    serverSide: false,
    bAutoWidth: true,
    bSort: false,
    bStateSave: true,
    bDestroy: true,
    paging: true,
    ssSorting: [[0, "desc"]],
    iDisplayLength: 25,
    aLengthMenu: [
      [25, 30, 50, -1],
      [25, 30, 50, "All"],
    ],
    language: {
      paginate: {
        previous: "<i class='fas fa-angle-left'>",
        next: "<i class='fas fa-angle-right'>",
      },
    },
    ajax: {
      url: "./mod/libur/datatable.php",
      type: "POST",
    },
    columnDefs: [
      {
        targets: [0],
        orderable: false,
      },
      {
        targets: [4],
        orderable: false,
      },
    ],
  });
}

$(document).ready(function () {
  loadData();
});

/* -------- Modal Tambah Hari Libur -------- */
$(document).on("click", ".btn-add", function () {
  $(".modal-title").html("Tambah Hari Libur");
  $(".form-add").trigger("reset");
  $(".modal-add").modal("show");
});

// Validasi form tambah
$(".form-add").validate({
  rules: {
    tanggal_mulai: { required: true },
    tanggal_selesai: { required: true },
    keterangan: { required: true },
  },
  messages: {
    tanggal_mulai: { required: "Tanggal mulai harus diisi" },
    tanggal_selesai: { required: "Tanggal selesai harus diisi" },
    keterangan: { required: "Keterangan harus diisi" },
  },
  submitHandler: submitForm_Add,
});

// Fungsi untuk tambah hari libur
function submitForm_Add() {
  $(".btn-save").prop("disabled", true);

  var data = $(".form-add").serialize();
  $.ajax({
    type: "POST",
    url: "./mod/libur/proses.php?action=add",
    data: data,
    cache: false,
    beforeSend: function () {
      loading();
    },
    success: function (response) {
      var result = typeof response === "string" ? JSON.parse(response) : response;
      if (result.status === "success") {
        swal({
          title: "Berhasil!",
          text: result.message,
          icon: "success",
          timer: 2500,
        });
        $(".form-add").trigger("reset");
        $(".modal-add").modal("hide");
        loadData();
      } else {
        swal({
          title: "Oops!",
          text: result.message,
          icon: "error",
          timer: 2500,
        });
      }
    },
    complete: function () {
      $(".btn-save").prop("disabled", false);
    },
  });
  return false;
}

/* -------- Modal Edit Hari Libur -------- */
$(document).on("click", ".btn-update", function () {
  var id = $(this).data("id");
  var tanggal_mulai = $(this).data("tanggal_mulai");
  var tanggal_selesai = $(this).data("tanggal_selesai");
  var keterangan = $(this).data("keterangan");

  $(".id").val(id);
  $(".edit-tanggal_mulai").val(tanggal_mulai);
  $(".edit-tanggal_selesai").val(tanggal_selesai);
  $(".edit-keterangan").val(keterangan);

  $(".modal-title").html("Edit Hari Libur");
  $(".modal-edit").modal("show");
});

// Validasi form edit
$(".form-edit").validate({
  rules: {
    tanggal_mulai: { required: true },
    tanggal_selesai: { required: true },
    keterangan: { required: true },
  },
  messages: {
    tanggal_mulai: { required: "Tanggal mulai harus diisi" },
    tanggal_selesai: { required: "Tanggal selesai harus diisi" },
    keterangan: { required: "Keterangan harus diisi" },
  },
  submitHandler: submitForm_Edit,
});

// Fungsi untuk mengirim data update ke server
function submitForm_Edit() {
  $(".btn-save-edit").prop("disabled", true);

  var data = $(".form-edit").serialize();
  $.ajax({
    type: "POST",
    url: "./mod/libur/proses.php?action=edit",
    data: data,
    cache: false,
    beforeSend: function () {
      $(".btn-save-edit").html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
      );
    },
    success: function (response) {
      var result = typeof response === "string" ? JSON.parse(response) : response;
      if (result.status === "success") {
        swal({
          title: "Berhasil!",
          text: result.message,
          icon: "success",
          timer: 2500,
        });
        $(".form-edit").trigger("reset");
        $(".modal-edit").modal("hide");
        loadData();
      } else {
        swal({
          title: "Oops!",
          text: result.message,
          icon: "error",
          timer: 2500,
        });
      }
    },
    complete: function () {
      $(".btn-save-edit").prop("disabled", false);
      $(".btn-save-edit").html('<i class="far fa-save"></i> Simpan');
    },
  });
  return false;
}

/* -------- Hapus Hari Libur -------- */
$(document).on("click", ".btn-delete", function () {
  var id = $(this).data("id");

  swal({
    title: "Yakin ingin menghapus?",
    text: "Data akan dihapus secara permanen!",
    icon: "warning",
    buttons: true,
    dangerMode: true,
  }).then((willDelete) => {
    if (willDelete) {
      $.ajax({
        type: "POST",
        url: "./mod/libur/proses.php?action=delete",
        data: { id: id },
        cache: false,
        success: function (response) {
          var result = typeof response === "string" ? JSON.parse(response) : response;
          if (result.status === "success") {
            swal({
              title: "Berhasil!",
              text: result.message,
              icon: "success",
              timer: 2500,
            });
            loadData();
          } else {
            swal({
              title: "Oops!",
              text: result.message,
              icon: "error",
              timer: 2500,
            });
          }
        },
      });
    }
  });
});
