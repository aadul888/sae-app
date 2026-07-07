"use strict";

var pinjamTable;

function loadPinjamData(statusFilter) {
  if (pinjamTable) {
    pinjamTable.destroy();
  }

  pinjamTable = $(".datatable").DataTable({
    scrollY: false,
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    ajax: {
      url: "./mod/inv-pinjam/datatable.php",
      type: "POST",
      data: function (d) {
        if (statusFilter) d.status_filter = statusFilter;
      },
    },
    columns: [
      { title: "No", className: "text-center" },
      { title: "Peminjam" },
      { title: "Kelas", className: "text-center" },
      { title: "Barang" },
      { title: "Jml", className: "text-center" },
      { title: "Tgl Pinjam", className: "text-center" },
      { title: "Tgl Kembali", className: "text-center" },
      { title: "Status", className: "text-center" },
      { title: "Aksi", className: "text-center" },
    ],
    language: {
      search: "Cari:",
      emptyTable: "Belum ada data peminjaman",
      paginate: {
        previous: "<i class='fas fa-angle-left'></i>",
        next: "<i class='fas fa-angle-right'></i>",
      },
    },
  });
}

loadPinjamData("");

// Filter modal handlers
$(".btn-apply-filter-pinjam").on("click", function () {
  loadPinjamData($("#filter-status-pinjam").val());
  $("#modalFilterInvPinjam").modal("hide");
});
$(".btn-reset-filter-pinjam").on("click", function () {
  $("#filter-status-pinjam").val("");
  loadPinjamData("");
  $("#modalFilterInvPinjam").modal("hide");
});

// Search siswa with debounce
var searchTimeout;
$(document).on("input", "#pinjam-user", function () {
  // handled by select2 below
});

// Init modal tambah
$(document).on("click", "#btn-tambah-pinjam", function () {
  $("#form-pinjam")[0].reset();
  $("#pinjam-id").val("0");
  $("#pinjam-tgl-pinjam").val(new Date().toISOString().split("T")[0]);
  $("#modal-pinjam-title").html(
    '<i class="fas fa-plus mr-2"></i>Tambah Peminjaman'
  );

  // Init select2 for user search if not already done
  if (!$("#pinjam-user").data("select2")) {
    $("#pinjam-user").select2({
      dropdownParent: $("#modal-pinjam"),
      ajax: {
        url: "./mod/inv-pinjam/proses.php?action=cari_siswa",
        type: "POST",
        dataType: "json",
        delay: 400,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          return data;
        },
      },
      minimumInputLength: 2,
      placeholder: "Ketik nama atau NISN...",
      allowClear: true,
    });

    // Auto-fill kelas when siswa selected
    $("#pinjam-user").on("select2:select", function (e) {
      var data = e.params.data;
      if (data.kelas) {
        $("#pinjam-kelas").val(data.kelas);
      }
    });
  }

  $("#modal-pinjam").modal("show");
});

// Submit form tambah
$("#form-pinjam").on("submit", function (e) {
  e.preventDefault();
  var btn = $(this).find("button[type=submit]");
  btn.prop("disabled", true).html(
    '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'
  );

  $.ajax({
    url: "./mod/inv-pinjam/proses.php?action=tambah",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",
    success: function (res) {
      btn
        .prop("disabled", false)
        .html('<i class="fas fa-save mr-1"></i> Simpan');
      $("#modal-pinjam").modal("hide");
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

// Detail
$(document).on("click", ".btn-detail-pinjam", function () {
  var id = $(this).data("id");
  $.ajax({
    type: "POST",
    url: "./mod/inv-pinjam/proses.php?action=detail",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        var d = res.data;
        var statusBadge = "warning";
        if (d.status === "Dikembalikan") statusBadge = "success";
        else if (d.status === "Terlambat") statusBadge = "danger";
        else if (d.status === "Hilang") statusBadge = "dark";

        var html =
          '<div class="card border-0"><div class="card-body">' +
          '<div class="row mb-2"><div class="col-4 text-muted">Peminjam</div><div class="col-8"><strong>' +
          (d.nama_lengkap || "-") +
          "</strong> <small>(" +
          (d.nisn || "") +
          ")</small></div></div>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Kelas</div><div class="col-8">' +
          (d.nama_kelas || "-") +
          "</div></div>" +
          "<hr>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Barang</div><div class="col-8">' +
          (d.nama_barang || "-") +
          " <small class='text-muted'>(" +
          (d.kode_barang || "") +
          ")</small></div></div>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Jumlah</div><div class="col-8">' +
          d.jumlah_pinjam +
          "</div></div>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Tgl Pinjam</div><div class="col-8">' +
          d.tanggal_pinjam +
          "</div></div>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Rencana Kembali</div><div class="col-8">' +
          (d.tanggal_kembali || "-") +
          "</div></div>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Kembali Aktual</div><div class="col-8">' +
          (d.tanggal_dikembalikan || "-") +
          "</div></div>" +
          "<hr>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Status</div><div class="col-8"><span class="badge badge-' +
          statusBadge +
          '">' +
          d.status +
          "</span></div></div>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Keterangan</div><div class="col-8">' +
          (d.keterangan || "-") +
          "</div></div>" +
          '<div class="row mb-2"><div class="col-4 text-muted">Dicatat Oleh</div><div class="col-8">' +
          (d.admin_nama || "-") +
          "</div></div>" +
          "</div></div>";

        swal({
          title: "Detail Peminjaman",
          content: (function () {
            var div = document.createElement("div");
            div.innerHTML = html;
            return div;
          })(),
        });
      } else {
        swal("Error!", res.message, "error");
      }
    },
  });
});

// Kembalikan
$(document).on("click", ".btn-kembalikan", function () {
  var el = $(this);
  $("#kembali-id").val(el.data("id"));
  $("#kembali-keterangan").val(el.data("keterangan"));
  $("#kembali-tgl").val(new Date().toISOString().split("T")[0]);
  $("#kembali-status").val("Dikembalikan");
  $("#modal-kembalikan").modal("show");
});

$("#form-kembalikan").on("submit", function (e) {
  e.preventDefault();
  var btn = $(this).find("button[type=submit]");
  btn.prop("disabled", true).html(
    '<i class="fas fa-spinner fa-spin"></i> Memproses...'
  );

  $.ajax({
    url: "./mod/inv-pinjam/proses.php?action=kembalikan",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",
    success: function (res) {
      btn
        .prop("disabled", false)
        .html('<i class="fas fa-check mr-1"></i> Proses');
      $("#modal-kembalikan").modal("hide");
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
        .html('<i class="fas fa-check mr-1"></i> Proses');
      swal("Error!", "Terjadi kesalahan koneksi.", "error");
    },
  });
});

// Hapus
$(document).on("click", ".btn-hapus-pinjam", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Data?",
    text: "Data peminjaman ini akan dihapus permanen.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: { text: "Hapus", closeModal: true },
    },
    dangerMode: true,
  }).then(function (ok) {
    if (ok) {
      $.ajax({
        url: "./mod/inv-pinjam/proses.php?action=hapus",
        type: "POST",
        data: { id: id },
        dataType: "json",
        success: function (res) {
          swal(
            res.status === "success" ? "Berhasil!" : "Gagal!",
            res.message,
            res.status === "success" ? "success" : "error"
          ).then(function () {
            if (res.status === "success") location.reload();
          });
        },
      });
    }
  });
});
