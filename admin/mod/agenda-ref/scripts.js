"use strict";

loadData();
function loadData() {
  var columns = [
    { title: "No", className: "text-center" },
    { title: "Kode", className: "text-center" },
    { title: "Nama Mapel", className: "text-center" },
    { title: "Guru Pengampu", className: "text-center" },
    { title: "Status", className: "text-center" },
    { title: "Aksi", className: "text-center" },
  ];

  $(".datatable").DataTable({
    scrollY: false,
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    ajax: { url: "./mod/agenda-ref/datatable.php", type: "POST" },
    columns: columns,
    language: {
      paginate: {
        previous: "<i class='fas fa-angle-left'></i>",
        next: "<i class='fas fa-angle-right'></i>",
      },
    },
  });
}

// Tambah
$(document).on("submit", "#formTambah", function (e) {
  e.preventDefault();
  var form = $(this);
  $.ajax({
    url: "./mod/agenda-ref/proses.php?action=tambah",
    type: "POST",
    data: form.serialize(),
    success: function (res) {
      swal("Info", res, res.includes("berhasil") ? "success" : "error");
      if (res.includes("berhasil")) {
        $("#modalTambah").modal("hide");
        form[0].reset();
        loadData();
      }
    },
  });
});

// Edit - populate modal
$(document).on("click", ".btn-edit", function () {
  var el = $(this);
  $("#edit_mapel_id").val(el.data("id"));
  $("#edit_kode_mapel").val(el.data("kode"));
  $("#edit_nama_mapel").val(el.data("nama"));
  $("#edit_guru_id").val(el.data("guru"));
  $("#edit_aktif").val(el.data("aktif"));
  $("#modalEdit").modal("show");
});

// Edit submit
$(document).on("submit", "#formEdit", function (e) {
  e.preventDefault();
  var form = $(this);
  $.ajax({
    url: "./mod/agenda-ref/proses.php?action=edit",
    type: "POST",
    data: form.serialize(),
    success: function (res) {
      swal("Info", res, res.includes("berhasil") ? "success" : "error");
      if (res.includes("berhasil")) {
        $("#modalEdit").modal("hide");
        loadData();
      }
    },
  });
});

// Hapus
$(document).on("click", ".btn-delete", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Mata Pelajaran?",
    text: "Data yang dihapus tidak dapat dikembalikan!",
    icon: "warning",
    dangerMode: true,
    buttons: { cancel: "Batal", confirm: { text: "Ya, Hapus", value: true } },
  }).then(function (willDelete) {
    if (willDelete) {
      $.ajax({
        url: "./mod/agenda-ref/proses.php?action=hapus",
        type: "POST",
        data: { mapel_id: id },
        success: function (res) {
          swal("Info", res, res.includes("berhasil") ? "success" : "error");
          loadData();
        },
      });
    }
  });
});
