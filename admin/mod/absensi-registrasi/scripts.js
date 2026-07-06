"use strict";
var tableRegistrasi;

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
    tableRegistrasi = $(".datatable").DataTable();
    tableRegistrasi.ajax.reload(null, false);
    return;
  }

  tableRegistrasi = $(".datatable").DataTable({
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
      url: "./mod/absensi-registrasi/datatable.php",
      type: "POST",
    },
    columnDefs: [
      {
        targets: [0],
        orderable: false,
      },
    ],
  });
}

$(document).ready(function () {
  var $modalAdd = $(".modal-add");
  if ($modalAdd.length) {
    $modalAdd.appendTo("body");
  }
  loadData();
});

$(document).on("click", ".btn-add", function () {
  $(".modal-add").modal("show");
  $(".modal-title").html("Tambah Registrasi RFID");
  $(".form-add").trigger("reset");
  $(".id").val("");
});

$(".form-add").validate({
  rules: {
    field: {
      required: true,
    },
  },
  messages: {
    field: {
      required: "Silahkan masukkan data sesuai inputan",
    },
  },
  submitHandler: submitForm_Add,
});

function submitForm_Add() {
  var data = $(".form-add").serialize();
  $.ajax({
    type: "POST",
    url: "./mod/absensi-registrasi/proses.php?action=add",
    data: data,
    cache: false,
    async: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      if (data == "success") {
        swal({
          title: "Berhasil!",
          text: "Data berhasil disimpan.!",
          icon: "success",
          timer: 2500,
        });
        $(".form-add").trigger("reset");
        $(".modal-add").modal("hide");
        loadData();
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2500 });
        loadData();
      }
    },
  });
  return false;
}

$(document).on("click", ".btn-update", function () {
  var nisn = $(this).attr("data-nisn");
  var nama = $(this).attr("data-nama");
  var rfid = $(this).attr("data-rfid");
  $(".id").val(""); // jika tidak pakai id, kosongkan saja
  $(".siswa-nisn-input").val(nisn + " - " + nama); // tampilkan di input autocomplete
  $(".siswa-nisn-hidden").val(nisn); // set hidden input
  $(".rfid").val(rfid); // isi input RFID
  $(".modal-title-name").html(rfid);
  $(".modal-add").modal("show");
  $(".modal-title").html("Update Registrasi RFID");
});

$(document).on("click", ".btn-delete", function () {
  var nisn = $(this).attr("data-nisn");
  var nama = $(this).attr("data-nama");
  var rfid = $(this).attr("data-rfid") || "";
  // Jika data-rfid tidak ada, ambil dari kolom tabel (fallback)
  if (!rfid) {
    var $row = $(this).closest("tr");
    rfid = $row.find("td:eq(4)").text().trim();
  }
  swal({
    text: "Anda yakin ingin menghapus RFID '" + nama + "'?",
    icon: "warning",
    buttons: {
      cancel: true,
      confirm: true,
    },
    value: "yes",
  }).then((value) => {
    if (value) {
      loading();
      $.ajax({
        url: "./mod/absensi-registrasi/proses.php?action=delete",
        type: "POST",
        data: { nisn: nisn },
        success: function (data) {
          if (data == "success") {
            swal({
              title: "Berhasil!",
              text: "Data berhasil dihapus.!",
              icon: "success",
              timer: 2500,
            });
            loadData();
          } else {
            swal({ title: "Gagal!", text: data, icon: "error", timer: 2500 });
          }
        },
      });
    } else {
      return false;
    }
  });
});

// --- Autocomplete Siswa ---
$(document).ready(function () {
  // Inject siswaList dari PHP
  if (typeof window.siswaList === "undefined") {
    window.siswaList = [];
  }
  var input = $(".siswa-nisn-input");
  var resultsDiv = $(".autocomplete-results");
  var hiddenInput = $(".siswa-nisn-hidden");

  input.on("input", function () {
    var val = $(this).val().toLowerCase();
    resultsDiv.html("");
    if (val.length < 2) return;
    var matches = window.siswaList.filter(function (s) {
      return (
        s.nisn.toLowerCase().includes(val) ||
        s.nama_lengkap.toLowerCase().includes(val)
      );
    });
    if (matches.length === 0) return;
    var list = $("<ul>")
      .css({
        listStyle: "none",
        padding: 0,
        margin: 0,
        background: "#fff",
        border: "1px solid #ccc",
        position: "absolute",
        width: "100%",
      })
      .appendTo(resultsDiv);
    matches.forEach(function (s) {
      var item = $("<li>")
        .text(s.nisn + " - " + s.nama_lengkap)
        .css({ padding: "6px 12px", cursor: "pointer" })
        .on("mousedown", function () {
          input.val(s.nisn + " - " + s.nama_lengkap);
          hiddenInput.val(s.nisn);
          resultsDiv.html("");
        });
      list.append(item);
    });
  });

  input.on("change", function () {
    if (!$(this).val().includes(" - ")) hiddenInput.val("");
  });

  $(".form-add").on("submit", function (e) {
    var nisnVal = hiddenInput.val();
    if (!nisnVal) {
      e.preventDefault();
      input.focus();
      alert("Silakan pilih siswa dari daftar autocomplete.");
    } else {
      input.val(nisnVal);
    }
  });
});
