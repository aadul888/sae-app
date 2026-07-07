"use strict";

var reportTable;

function loadReportData(kelas, statusFilter) {
  if (reportTable) {
    reportTable.destroy();
  }

  reportTable = $(".datatable").DataTable({
    scrollY: false,
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    ajax: {
      url: "./mod/inv-report/datatable.php",
      type: "POST",
      data: function (d) {
        if (kelas) d.kelas = kelas;
        if (statusFilter) d.status_filter = statusFilter;
      },
    },
    columns: [
      { title: "No", className: "text-center" },
      { title: "Kelas", className: "text-center" },
      { title: "Jenis", className: "text-center" },
      { title: "Deskripsi" },
      { title: "Prioritas", className: "text-center" },
      { title: "Status", className: "text-center" },
      { title: "Pelapor", className: "text-center" },
      { title: "Tanggal", className: "text-center" },
      { title: "Aksi", className: "text-center" },
    ],
    language: {
      search: "Cari:",
      emptyTable: "Belum ada laporan",
      paginate: {
        previous: "<i class='fas fa-angle-left'></i>",
        next: "<i class='fas fa-angle-right'></i>",
      },
    },
  });
}

// Initial load
loadReportData("", "");

// Filter modal handlers
$(".btn-apply-filter-report").on("click", function () {
  loadReportData($("#filter-kelas").val(), $("#filter-status").val());
  $("#modalFilterInvReport").modal("hide");
});
$(".btn-reset-filter-report").on("click", function () {
  $("#filter-kelas, #filter-status").val("");
  loadReportData("", "");
  $("#modalFilterInvReport").modal("hide");
});

// Detail
$(document).on("click", ".btn-detail-laporan", function () {
  var id = $(this).data("id");
  $.ajax({
    type: "POST",
    url: "./mod/inv-report/proses.php?action=detail",
    data: { id: id },
    success: function (response) {
      $("#detail-laporan-content").html(response);
      $("#modal-detail-laporan").modal("show");
    },
    error: function () {
      swal("Error!", "Gagal mengambil data detail.", "error");
    },
  });
});

// Proses
$(document).on("click", ".btn-proses-laporan", function () {
  var el = $(this);
  $("#proses-id").val(el.data("id"));
  $("#proses-status").val(el.data("status"));
  $("#proses-catatan").val(el.data("catatan"));
  $("#modal-proses").modal("show");
});

$("#form-proses").on("submit", function (e) {
  e.preventDefault();
  var btn = $(this).find("button[type=submit]");
  btn.prop("disabled", true).html(
    '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'
  );

  $.ajax({
    url: "./mod/inv-report/proses.php?action=proses",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",
    success: function (res) {
      btn
        .prop("disabled", false)
        .html('<i class="fas fa-save mr-1"></i> Simpan');
      $("#modal-proses").modal("hide");
      swal(
        res.status === "success" ? "Berhasil!" : "Gagal!",
        res.message,
        res.status === "success" ? "success" : "error"
      ).then(function () {
        if (res.status === "success") location.reload();
      });
    },
    error: function () {
      btn
        .prop("disabled", false)
        .html('<i class="fas fa-save mr-1"></i> Simpan');
      swal("Error!", "Terjadi kesalahan koneksi.", "error");
    },
  });
});

// Hapus
$(document).on("click", ".btn-hapus-laporan", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Laporan?",
    text: "Laporan ini akan dihapus permanen.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: { text: "Hapus", closeModal: true },
    },
    dangerMode: true,
  }).then(function (ok) {
    if (ok) {
      $.ajax({
        url: "./mod/inv-report/proses.php?action=hapus",
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
