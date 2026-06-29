"use strict";

var tableSuratIndex = null;

function initTableSuratIndex() {
  if ($.fn.DataTable && $("#tableSuratIndex").length) {
    if ($.fn.DataTable.isDataTable("#tableSuratIndex")) {
      $("#tableSuratIndex").DataTable().destroy();
    }
    tableSuratIndex = $("#tableSuratIndex").DataTable({
      processing: false,
      serverSide: false,
      bAutoWidth: false,
      bSort: true,
      bStateSave: false,
      bDestroy: true,
      paging: true,
      scrollX: true,
      scrollCollapse: true,
      responsive: false,
      iDisplayLength: 25,
      order: [[0, "asc"]],
      aLengthMenu: [
        [25, 30, 50, -1],
        [25, 30, 50, "All"],
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        infoFiltered: "(difilter dari _MAX_ total)",
        paginate: {
          previous: '<i class="fas fa-angle-left">',
          next: '<i class="fas fa-angle-right">',
        },
      },
      columnDefs: [
        { orderable: false, targets: [7] },
        { searchable: false, targets: [0, 5, 6, 7] },
      ],
    });
  }
}

$(function () {
  initTableSuratIndex();
  $('[data-toggle="tooltip"]').tooltip();
});

/* ====== Copy Index ====== */
$(document).on("click", ".btn-copy-index", function () {
  var val = $(this).data("value") || "";
  if (navigator.clipboard && val) navigator.clipboard.writeText(val);
  swal({ title: "Disalin!", text: val, icon: "success", timer: 1200 });
});

/* ====== TAMBAH KATEGORI BARU ====== */
$(document).on("click", ".btn-add-kategori", function () {
  var v = prompt("Masukkan nama kategori baru:");
  if (v === null || v.trim() === "") return;
  v = v.trim();
  $("<option>").val(v).text(v).appendTo("#f_kategori");
  $("#f_kategori").val(v);
  swal({
    title: "Berhasil!",
    text: 'Kategori "' + v + '" ditambahkan.',
    type: "success",
    timer: 1200,
  });
});

/* ====== TAMBAH JENIS SURAT BARU ====== */
$(document).on("click", ".btn-add-jenis", function () {
  var v = prompt("Masukkan jenis surat baru:");
  if (v === null || v.trim() === "") return;
  v = v.trim();
  $("<option>").val(v).text(v).appendTo("#f_jenis");
  $("#f_jenis").val(v);
  swal({
    title: "Berhasil!",
    text: 'Jenis "' + v + '" ditambahkan.',
    type: "success",
    timer: 1200,
  });
});

/* ====== FORM TAMBAH / EDIT INDEX ====== */
$(document).on("submit", "#formIndex", function (e) {
  e.preventDefault();
  var btn = $(this).find("button[type=submit]");
  btn
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');
  var fd = new FormData(this);
  $.ajax({
    url:
      "./mod/surat-index/proses.php?action=" +
      (parseInt($("#f_id").val()) > 0 ? "update" : "add"),
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (data) {
      var t = typeof data === "string" ? data.trim() : "";
      if (t === "success") {
        swal({
          title: "Berhasil!",
          text: "Data indeks tersimpan.",
          icon: "success",
          timer: 1500,
        });
        $("#modalIndex").modal("hide");
        setTimeout(function () {
          location.reload();
        }, 1200);
      } else {
        swal({ title: "Gagal!", text: t || "Gagal menyimpan.", icon: "error" });
      }
    },
    error: function () {
      swal({ title: "Error!", text: "Koneksi gagal.", icon: "error" });
    },
    complete: function () {
      btn
        .prop("disabled", false)
        .html('<i class="fas fa-save mr-1"></i>Simpan');
    },
  });
});

/* ====== EDIT INDEX (modal populate) ====== */
$(document).on("click", ".btn-edit-index", function () {
  var id = $(this).data("id");
  $.get("./mod/surat-index/proses.php?action=get&id=" + id, function (data) {
    try {
      var d = typeof data === "object" ? data : JSON.parse(data);
      if (d.status === "success" && d.data) {
        $("#modalIndexTitle").html(
          '<i class="fas fa-edit mr-2"></i>Edit Indeks',
        );
        $("#f_id").val(d.data.id);
        $("#f_indeks").val(d.data.indeks).prop("readonly", true);
        $("#f_perihal").val(d.data.perihal);
        $("#f_kategori").val(d.data.kategori);
        if ($('#f_jenis option[value="' + d.data.jenis_surat + '"]').length) {
          $("#f_jenis").val(d.data.jenis_surat);
        } else {
          $("#f_jenis")
            .append(
              $("<option>").val(d.data.jenis_surat).text(d.data.jenis_surat),
            )
            .val(d.data.jenis_surat);
        }
        $("#modalIndex").modal("show");
      } else {
        swal({
          title: "Error!",
          text: d.message || "Data tidak ditemukan.",
          icon: "error",
        });
      }
    } catch (e) {
      swal({ title: "Error!", text: "Gagal parse data.", icon: "error" });
    }
  });
});

// Reset modal tambah
$("#modalIndex").on("hidden.bs.modal", function () {
  if ($("#f_id").val() == 0) return;
  $("#f_id").val(0);
  $("#f_indeks").prop("readonly", false).val("");
  $("#f_perihal").val("");
  $("#modalIndexTitle").html('<i class="fas fa-plus mr-2"></i>Tambah Indeks');
});

/* ====== DELETE INDEX ====== */
$(document).on("click", ".btn-delete-index", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Indeks?",
    text: "Data akan dihapus permanen.",
    icon: "warning",
    buttons: ["Batal", "Ya, Hapus!"],
    dangerMode: true,
  }).then(function (confirm) {
    if (!confirm) return;
    $.post(
      "./mod/surat-index/proses.php?action=delete",
      { id: id },
      function (data) {
        if (data.trim() === "success") {
          swal({
            title: "Berhasil!",
            text: "Indeks dihapus.",
            icon: "success",
            timer: 1500,
          });
          setTimeout(function () {
            location.reload();
          }, 1200);
        } else {
          swal({ title: "Gagal!", text: data, icon: "error" });
        }
      },
    );
  });
});

/* ====== IMPORT EXCEL ====== */
$(document).on("submit", "#formImportExcel, .form-import", function (e) {
  e.preventDefault();
  var btn = $(this).find("button[type=submit]");
  btn
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin mr-1"></i>Import...');
  $.ajax({
    url: "./mod/surat-index/proses.php?action=import_excel",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    success: function (res) {
      var ok = false,
        msg = "Import gagal.";
      if (typeof res === "object" && res !== null) {
        ok = !!res.ok;
        msg = res.msg || msg;
      } else if (typeof res === "string") {
        var t = res.trim();
        try {
          var j = JSON.parse(t);
          ok = !!j.ok;
          msg = j.msg || msg;
        } catch (e) {
          ok = t.indexOf("success") !== -1;
          msg = t;
        }
      }
      if (ok) {
        swal({ title: "Berhasil!", text: msg, icon: "success", timer: 2000 });
        $("#modalImportExcel").modal("hide");
        setTimeout(function () {
          location.reload();
        }, 1500);
      } else {
        swal({ title: "Gagal!", text: msg, icon: "error" });
      }
    },
    error: function (xhr) {
      var msg = "Koneksi gagal.";
      if (xhr && xhr.responseText)
        msg = xhr.responseText
          .replace(/<[^>]*>/g, " ")
          .trim()
          .substring(0, 200);
      swal({ title: "Error!", text: msg, icon: "error" });
    },
    complete: function () {
      btn
        .prop("disabled", false)
        .html('<i class="fas fa-upload mr-1"></i>Import');
    },
  });
});

/* ====== EXPORT EXCEL ====== */
$(document).on("click", ".btn-export-index", function () {
  window.location.href = "./mod/surat-index/proses.php?action=export_excel";
});

/* ====== DOWNLOAD TEMPLATE EXCEL ====== */
$(document).on("click", "#downloadTemplateExcel", function (e) {
  e.preventDefault();
  window.location.href =
    "./mod/surat-index/proses.php?action=download_template_excel";
});
