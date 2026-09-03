"use strict";

// Tooltip pointer-events:none biar gak block klik
if (!document.getElementById("info-tooltip-style")) {
  var s = document.createElement("style");
  s.id = "info-tooltip-style";
  s.textContent = ".info-action-tooltip{pointer-events:none!important;}";
  document.head.appendChild(s);
}

// DataTable
var tableInfo = $("#table-info").DataTable({
  processing: true,
  serverSide: true,
  bAutoWidth: false,
  bSort: true,
  bStateSave: false,
  bDestroy: true,
  paging: true,
  scrollX: true,
  scrollCollapse: true,
  responsive: false,
  iDisplayLength: 25,
  order: [[1, "asc"]],
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
    url: "./mod/info/datatable.php",
    type: "GET",
  },
  columns: [
    { data: 0, orderable: false, searchable: false },
    { data: 1 },
    { data: 2, orderable: false, searchable: false },
    { data: 3 },
    { data: 4, orderable: false, searchable: false },
    { data: 5, orderable: false, searchable: false },
    { data: 6, orderable: false, searchable: false },
    { data: 7, orderable: false, searchable: false },
  ],
  drawCallback: function () {
    $('[data-toggle="tooltip"]').tooltip("dispose");
    $('[data-toggle="tooltip"]').tooltip({
      container: "body",
      trigger: "hover",
      boundary: "window",
      placement: "top",
      template:
        '<div class="tooltip info-action-tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>',
    });
  },
});

// Reset form
function resetFormInfo() {
  $("#formInfo")[0].reset();
  $("#infoId").val("");
}

// Open modal tambah
$(document).on("click", ".btn-tambah-info", function () {
  resetFormInfo();
  $("#modalInfo .modal-title").text("Tambah Info");
  $("#modalInfo").modal("show");
});

// Open modal edit
$(document).on("click", ".btn-edit-info", function () {
  var id = $(this).data("id");
  var judul = $(this).data("judul");
  var konten = $(this).data("konten");
  var kategori = $(this).data("kategori") || "";
  var aktif = $(this).data("aktif");
  var urutan = $(this).data("urutan");
  var tglMulai = $(this).data("tgl-mulai") || "";
  var tglSelesai = $(this).data("tgl-selesai") || "";

  $("#infoId").val(id);
  $("#infoJudul").val(judul);
  $("#infoKategori").val(kategori);
  $("#infoKonten").val(konten);
  $("#infoAktif").prop("checked", aktif == 1);
  $("#infoUrutan").val(urutan);
  $("#infoTglMulai").val(tglMulai);
  $("#infoTglSelesai").val(tglSelesai);

  $("#modalInfo .modal-title").text("Edit Info");
  $("#modalInfo").modal("show");
});

// Submit form
$(document).on("submit", "#formInfo", function (e) {
  e.preventDefault();
  var btn = $(this).find('button[type="submit"]');
  btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

  $.ajax({
    url: "./mod/info/proses.php?action=simpan",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",
    success: function (res) {
      if (res.success) {
        $("#modalInfo").modal("hide");
        swal({
          title: "Sukses",
          text: res.message,
          icon: "success",
          timer: 1500,
        }).then(function () {
          tableInfo.ajax.reload(null, false);
        });
      } else {
        swal({ title: "Gagal", text: res.message, icon: "error" });
      }
    },
    error: function () {
      swal({ title: "Gagal", text: "Terjadi kesalahan server", icon: "error" });
    },
    complete: function () {
      btn.prop("disabled", false).text("Simpan");
    },
  });
});

// Delete confirmation
$(document).on("click", ".btn-hapus-info", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Info?",
    text: "Data akan dihapus permanen!",
    icon: "warning",
    buttons: {
      cancel: { text: "Batal", visible: true },
      confirm: { text: "Ya, Hapus", className: "btn-danger" },
    },
  }).then(function (val) {
    if (val) {
      $.ajax({
        url: "./mod/info/proses.php?action=hapus",
        type: "POST",
        data: { id: id },
        dataType: "json",
        success: function (res) {
          if (res.success) {
            swal({
              title: "Sukses",
              text: res.message,
              icon: "success",
              timer: 1500,
            }).then(function () {
              tableInfo.ajax.reload(null, false);
            });
          } else {
            swal({ title: "Gagal", text: res.message, icon: "error" });
          }
        },
        error: function () {
          swal({
            title: "Gagal",
            text: "Terjadi kesalahan server",
            icon: "error",
          });
        },
      });
    }
  });
});

// Hapus
