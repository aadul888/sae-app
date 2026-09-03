"use strict";

var invKelasTable;

function loadData(kelas) {
  var postData = {};
  if (kelas) postData.kelas = kelas;

  if (invKelasTable) {
    invKelasTable.destroy();
  }

  invKelasTable = $(".datatable").DataTable({
    scrollY: false,
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    ajax: {
      url: "./mod/inv-kelas/datatable.php",
      type: "POST",
      data: function (d) {
        if (kelas) d.kelas = kelas;
      },
    },
    columns: [
      { title: "No", className: "text-center" },
      { title: "Kelas", className: "text-center" },
      { title: "Barang" },
      { title: "Jumlah", className: "text-center" },
      { title: "Kondisi", className: "text-center" },
      { title: "Keterangan" },
      { title: "Di-input Oleh", className: "text-center" },
      { title: "Tanggal", className: "text-center" },
      { title: "Aksi", className: "text-center" },
    ],
    language: {
      search: "Cari:",
      emptyTable: "Belum ada data inventaris",
      paginate: {
        previous: "<i class='fas fa-angle-left'></i>",
        next: "<i class='fas fa-angle-right'></i>",
      },
    },
  });
}

// Initial load
loadData("");

// Filter modal handlers
$(".btn-apply-filter-inv-kelas").on("click", function () {
  loadData($("#filter-kelas").val());
  $("#modalFilterInvKelas").modal("hide");
});
$(".btn-reset-filter-inv-kelas").on("click", function () {
  $("#filter-kelas").val("");
  loadData("");
  $("#modalFilterInvKelas").modal("hide");
});

// Detail
$(document).on("click", ".btn-detail-inv", function () {
  var id = $(this).data("id");
  $.ajax({
    type: "POST",
    url: "./mod/inv-kelas/proses.php?action=detail",
    data: { id: id },
    success: function (response) {
      $("#detail-inv-content").html(response);
      $("#modal-detail-inv").modal("show");
    },
    error: function () {
      swal("Error!", "Gagal mengambil data detail.", "error");
    },
  });
});

// Hapus
$(document).on("click", ".btn-hapus-inv", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Data Inventaris?",
    text: "Data ini akan dihapus permanen.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: { text: "Hapus", closeModal: true },
    },
    dangerMode: true,
  }).then(function (ok) {
    if (ok) {
      $.ajax({
        url: "./mod/inv-kelas/proses.php?action=hapus",
        type: "POST",
        data: { id: id },
        success: function (response) {
          swal(
            "Info",
            response,
            response.indexOf("berhasil") !== -1 ? "success" : "error"
          ).then(function () {
            if (response.indexOf("berhasil") !== -1) location.reload();
          });
        },
      });
    }
  });
});
