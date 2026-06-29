"use strict";

var tableSuratIndex = null;
var tableKategori = null;
var tableJenis = null;

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

function initTableKategori() {
  if ($.fn.DataTable && $("#tableKategori").length) {
    if ($.fn.DataTable.isDataTable("#tableKategori")) {
      $("#tableKategori").DataTable().destroy();
    }
    tableKategori = $("#tableKategori").DataTable({
      paging: true,
      bAutoWidth: false,
      bSort: true,
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
        { orderable: false, targets: [3] },
        { searchable: false, targets: [0, 3] },
      ],
    });
  }
}

function initTableJenis() {
  if ($.fn.DataTable && $("#tableJenis").length) {
    if ($.fn.DataTable.isDataTable("#tableJenis")) {
      $("#tableJenis").DataTable().destroy();
    }
    tableJenis = $("#tableJenis").DataTable({
      paging: true,
      bAutoWidth: false,
      bSort: true,
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
        { orderable: false, targets: [3] },
        { searchable: false, targets: [0, 3] },
      ],
    });
  }
}

/* ====== Reload DataTables after tab visibility ====== */
$(function () {
  initTableSuratIndex();
  initTableKategori();
  initTableJenis();

  // Adjust columns when tab shown (hidden tables need layout recalc)
  $('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
    var target = $(e.target).attr("href");
    if (target === "#tab-kategori" && tableKategori) {
      tableKategori.columns.adjust().draw();
    } else if (target === "#tab-jenis" && tableJenis) {
      tableJenis.columns.adjust().draw();
    } else if (target === "#tab-referensi" && tableSuratIndex) {
      tableSuratIndex.columns.adjust().draw();
    }
  });
});

/* ====== Copy Index ====== */
$(document).on("click", ".btn-copy-index", function () {
  var val = $(this).data("value") || "";
  if (navigator.clipboard && val) navigator.clipboard.writeText(val);
  swal({ title: "Disalin!", text: val, icon: "success", timer: 1200 });
});

/* =================================================================
 * KATEGORI CRUD
 * ================================================================= */

/* ----- TAMBAH KATEGORI (dari tombol + di form indeks) ----- */
$(document).on("click", ".btn-add-kategori", function () {
  $("#f_kategori_id").val(0);
  $("#f_nama_kategori").val("");
  $("#modalKategoriTitle").html(
    '<i class="fas fa-tag mr-2"></i>Tambah Kategori',
  );
  $("#modalKategori").modal("show");
  setTimeout(function () {
    $("#f_nama_kategori").focus();
  }, 300);
});

/* ----- SIMPAN KATEGORI (add / update) ----- */
$(document).on("click", "#btnSimpanKategori", function () {
  var id = parseInt($("#f_kategori_id").val()) || 0;
  var v = $("#f_nama_kategori").val();
  if (v === null || v.trim() === "") {
    swal({
      title: "Peringatan",
      text: "Nama kategori tidak boleh kosong.",
      icon: "warning",
    });
    return;
  }
  v = v.trim();
  var action = id > 0 ? "kategori_update" : "kategori_add";
  $.post(
    "./mod/surat-index/proses.php?action=" + action,
    { id: id, nama: v },
    function (res) {
      try {
        var d = typeof res === "object" ? res : JSON.parse(res);
        if (d.status === "success") {
          swal({
            title: "Berhasil!",
            text: "Kategori tersimpan.",
            icon: "success",
            timer: 1200,
          });
          $("#modalKategori").modal("hide");
          reloadKategoriDropdown();
          location.reload();
        } else {
          swal({
            title: "Gagal!",
            text: d.message || "Gagal menyimpan.",
            icon: "error",
          });
        }
      } catch (e) {
        swal({
          title: "Error!",
          text: "Gagal memproses respons.",
          icon: "error",
        });
      }
    },
  );
});

/* ----- EDIT KATEGORI ----- */
$(document).on("click", ".btn-edit-kategori", function () {
  var id = $(this).data("id");
  var nama = $(this).data("nama");
  $("#f_kategori_id").val(id);
  $("#f_nama_kategori").val(nama);
  $("#modalKategoriTitle").html(
    '<i class="fas fa-edit mr-2"></i>Edit Kategori',
  );
  $("#modalKategori").modal("show");
  setTimeout(function () {
    $("#f_nama_kategori").focus();
  }, 300);
});

/* ----- HAPUS KATEGORI ----- */
$(document).on("click", ".btn-delete-kategori", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Kategori?",
    text: "Data akan dihapus permanen.",
    icon: "warning",
    buttons: ["Batal", "Ya, Hapus!"],
    dangerMode: true,
  }).then(function (confirm) {
    if (!confirm) return;
    $.post(
      "./mod/surat-index/proses.php?action=kategori_delete",
      { id: id },
      function (res) {
        try {
          var d = typeof res === "object" ? res : JSON.parse(res);
          if (d.status === "success") {
            swal({
              title: "Berhasil!",
              text: "Kategori dihapus.",
              icon: "success",
              timer: 1200,
            });
            reloadKategoriDropdown();
            location.reload();
          } else {
            swal({
              title: "Gagal!",
              text: d.message || "Gagal menghapus.",
              icon: "error",
            });
          }
        } catch (e) {
          swal({
            title: "Error!",
            text: "Gagal memproses respons.",
            icon: "error",
          });
        }
      },
    );
  });
});

/* =================================================================
 * JENIS SURAT CRUD
 * ================================================================= */

/* ----- TAMBAH JENIS (dari tombol + di form indeks) ----- */
$(document).on("click", ".btn-add-jenis", function () {
  $("#f_jenis_id").val(0);
  $("#f_nama_jenis").val("");
  $("#modalJenisTitle").html(
    '<i class="fas fa-envelope mr-2"></i>Tambah Jenis Surat',
  );
  $("#modalJenis").modal("show");
  setTimeout(function () {
    $("#f_nama_jenis").focus();
  }, 300);
});

/* ----- SIMPAN JENIS ----- */
$(document).on("click", "#btnSimpanJenis", function () {
  var id = parseInt($("#f_jenis_id").val()) || 0;
  var v = $("#f_nama_jenis").val();
  if (v === null || v.trim() === "") {
    swal({
      title: "Peringatan",
      text: "Nama jenis surat tidak boleh kosong.",
      icon: "warning",
    });
    return;
  }
  v = v.trim();
  var action = id > 0 ? "jenis_update" : "jenis_add";
  $.post(
    "./mod/surat-index/proses.php?action=" + action,
    { id: id, nama: v },
    function (res) {
      try {
        var d = typeof res === "object" ? res : JSON.parse(res);
        if (d.status === "success") {
          swal({
            title: "Berhasil!",
            text: "Jenis surat tersimpan.",
            icon: "success",
            timer: 1200,
          });
          $("#modalJenis").modal("hide");
          reloadJenisDropdown();
          location.reload();
        } else {
          swal({
            title: "Gagal!",
            text: d.message || "Gagal menyimpan.",
            icon: "error",
          });
        }
      } catch (e) {
        swal({
          title: "Error!",
          text: "Gagal memproses respons.",
          icon: "error",
        });
      }
    },
  );
});

/* ----- EDIT JENIS ----- */
$(document).on("click", ".btn-edit-jenis", function () {
  var id = $(this).data("id");
  var nama = $(this).data("nama");
  $("#f_jenis_id").val(id);
  $("#f_nama_jenis").val(nama);
  $("#modalJenisTitle").html(
    '<i class="fas fa-edit mr-2"></i>Edit Jenis Surat',
  );
  $("#modalJenis").modal("show");
  setTimeout(function () {
    $("#f_nama_jenis").focus();
  }, 300);
});

/* ----- HAPUS JENIS ----- */
$(document).on("click", ".btn-delete-jenis", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Jenis Surat?",
    text: "Data akan dihapus permanen.",
    icon: "warning",
    buttons: ["Batal", "Ya, Hapus!"],
    dangerMode: true,
  }).then(function (confirm) {
    if (!confirm) return;
    $.post(
      "./mod/surat-index/proses.php?action=jenis_delete",
      { id: id },
      function (res) {
        try {
          var d = typeof res === "object" ? res : JSON.parse(res);
          if (d.status === "success") {
            swal({
              title: "Berhasil!",
              text: "Jenis surat dihapus.",
              icon: "success",
              timer: 1200,
            });
            reloadJenisDropdown();
            location.reload();
          } else {
            swal({
              title: "Gagal!",
              text: d.message || "Gagal menghapus.",
              icon: "error",
            });
          }
        } catch (e) {
          swal({
            title: "Error!",
            text: "Gagal memproses respons.",
            icon: "error",
          });
        }
      },
    );
  });
});

/* =================================================================
 * HELPER: Reload dropdown kategori & jenis
 * ================================================================= */
function reloadKategoriDropdown() {
  $.get("./mod/surat-index/proses.php?action=kategori_list", function (res) {
    try {
      var d = typeof res === "object" ? res : JSON.parse(res);
      if (d.status === "success" && d.data) {
        var sel = $("#f_kategori");
        var current = sel.val();
        sel.find("option").not(":first").remove();
        $.each(d.data, function (i, item) {
          sel.append(
            $("<option>").val(item.nama_kategori).text(item.nama_kategori),
          );
        });
        sel.val(current);
      }
    } catch (e) {}
  });
}

function reloadJenisDropdown() {
  $.get("./mod/surat-index/proses.php?action=jenis_list", function (res) {
    try {
      var d = typeof res === "object" ? res : JSON.parse(res);
      if (d.status === "success" && d.data) {
        var sel = $("#f_jenis");
        var current = sel.val();
        sel.find("option").not(":first").remove();
        $.each(d.data, function (i, item) {
          sel.append($("<option>").val(item.nama_jenis).text(item.nama_jenis));
        });
        sel.val(current);
      }
    } catch (e) {}
  });
}

/* =================================================================
 * INDEX CRUD (existing — kept unchanged)
 * ================================================================= */

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
        $("#f_docid").val(d.data.format_template || "");
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
