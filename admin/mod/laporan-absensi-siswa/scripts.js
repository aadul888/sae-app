"use strict";
function loading() {
  $(".btn-save").prop("disabled", true);
  // add spinner to button
  $(".btn-save").html(
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
  );
  window.setTimeout(function () {
    $(".btn-save").prop("disabled", false);
    $(".btn-save").html('<i class="far fa-save"></i> Simpan');
  }, 2000);
}

$(".timepicker").timepicker({
  showInputs: false,
  showMeridian: false,
  use24hours: true,
  format: "HH:mm",
});

$("body").on("click", ".datepicker", function () {
  $(this).datepicker({
    format: "dd-mm-yyyy",
    autoclose: true,
  });
  $(this).datepicker("show");
});

/** Dropdown */
$(".kelas").change(function () {
  var kelas = $(this).val();
  $.ajax({
    type: "POST",
    url: "./mod/laporan-absensi-siswa/proses.php?action=dropdown",
    data: { kelas: kelas },
    cache: false,
    success: function (data) {
      $(".siswa").html(data);
    },
  });
});

$(document).ready(function () {
  if ($(".load-data").length) {
    $(".load-data").html('<div class="alert alert-info text-center"><i class="fas fa-info-circle"></i> Silahkan pilih siswa, bulan, dan tahun lalu klik Filter.</div>');
  }
});

$(document).on("click", ".btn-filter", function () {
  var siswa = $(".siswa").val();
  var bulan = $(".bulan").val();
  var tahun = $(".tahun").val();

  if (siswa == "" || bulan == "" || tahun == "") {
    swal({
      title: "Oops!",
      text: "Silahkan pilih filter datanya",
      icon: "error",
      timer: 1500,
    });
  } else {
    $.ajax({
      type: "POST",
      url: "./mod/laporan-absensi-siswa/proses.php?action=filtering",
      data: { siswa: siswa, bulan: bulan, tahun: tahun },
      cache: false,
      success: function (data) {
        $(".load-data").html(data);
      },
    });
  }
});

/** Print */
$(document).on("click", ".btn-print", function () {
  var tipe = $(this).attr("data-tipe");
  var siswa = $(".siswa").val();
  var bulan = $(".bulan").val();
  var tahun = $(".tahun").val();
  if (siswa == "" || bulan == "" || tahun == "") {
    swal({
      title: "Oops!",
      text: "Silahkan pilih filter datanya sebelum mencetak",
      icon: "error",
      timer: 1500,
    });
    return;
  }
  var url =
    "./mod/laporan-absensi-siswa/print.php?action=print&siswa=" +
    siswa +
    "&bulan=" +
    bulan +
    "&tahun=" +
    tahun +
    "&tipe=" +
    tipe +
    "";
  window.open(url, "_blank");
});
