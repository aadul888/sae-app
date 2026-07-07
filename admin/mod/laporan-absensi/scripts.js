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

/** Module  */
function loadData() {
  var tanggal = $(".tanggal").val();
  $.ajax({
    type: "POST",
    url: "./mod/laporan-absensi/proses.php?action=filtering",
    data: { tanggal: tanggal },
    cache: false,
    success: function (data) {
      $(".load-data").html(data);
    },
  });
}

$(document).on("click", ".btn-pagination", function () {
  var tanggal = $(".tanggal").val();
  var halaman = $(this).attr("data-id");

  $.ajax({
    url:
      "./mod/laporan-absensi/proses.php?action=filtering&halaman=" +
      halaman +
      "",
    method: "POST",
    data: { tanggal: tanggal },
    dataType: "text",
    cache: false,
    success: function (data) {
      $(".load-data").html(data);
    },
  });
});

/** Dropdown */
$(".tanggal").change(function () {
  loadData();
});

/** Print */
$(document).on("click", ".btn-print", function () {
  var tipe = $(this).attr("data-tipe");
  var tanggal = $(".tanggal").val();
  var url = "./mod/laporan-absensi/print.php?action=print&tanggal=" + tanggal + "&tipe=" + tipe;
  window.open(url, "_blank");
});

// Submit form edit
$(document).on("click", ".btn-edit", function () {
  var id = $(this).data("id");
  $.ajax({
    url: "./mod/laporan-absensi/proses.php?action=edit-form",
    method: "POST",
    data: { id: id },
    success: function (response) {
      $("#modalKoreksiAbsen .modal-body").html(response);
      $("#modalKoreksiAbsen").modal("show");
    }
  });
});

// Submit form edit/koreksi absensi
$(document).on("submit", "#form-edit-data", function (e) {
  e.preventDefault();
  // Ambil data sesuai field tabel absensi
  var formData = {
    id: $(this).find('[name="id"]').val(),
    tanggal: $(this).find('[name="tanggal"]').val(),
    jam_masuk: $(this).find('[name="jam_masuk"]').val(),
    status_masuk: $(this).find('[name="status_masuk"]').val(),
    jam_pulang: $(this).find('[name="jam_pulang"]').val(),
    status_pulang: $(this).find('[name="status_pulang"]').val(),
    kehadiran: $(this).find('[name="kehadiran"]').val()
  };
  var $btn = $(".btn-save");
  $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
  $.ajax({
    url: "./mod/laporan-absensi/proses.php?action=update",
    method: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
  // debug log removed
      $btn.prop("disabled", false).html('<i class="far fa-save"></i> Simpan');
      if (response.status === "success") {
        $("#modalKoreksiAbsen").modal("hide");
        loadData();
        swal('Berhasil', response.message || 'Absensi berhasil diperbarui.', 'success');
      } else {
        swal('Gagal', response.message || 'Gagal mengupdate absensi.', 'error');
      }
    },
    error: function () {
      $btn.prop("disabled", false).html('<i class="far fa-save"></i> Simpan');
      swal('Gagal', 'Gagal mengupdate absensi.', 'error');
    }
  });
});

// Hapus data absensi
$(document).on("click", ".btn-delete", function () {
  var id = $(this).attr("data-id");
  swal({
    title: 'Hapus Data?',
    text: 'Data absensi akan dihapus secara permanen!',
    icon: 'warning',
    buttons: ['Batal', 'Ya, hapus!'],
    dangerMode: true
  }).then(function(willDelete) {
    if (willDelete) {
      $.ajax({
        url: "./mod/laporan-absensi/proses.php?action=delete",
        type: "POST",
        data: { id: id },
        dataType: "json",
        success: function (data) {
          if (data.status === "success") {
            loadData();
            swal('Berhasil', 'Data absensi berhasil dihapus.', 'success');
          } else {
            swal('Gagal', data.message || 'Gagal menghapus data absensi.', 'error');
          }
        },
        error: function () {
          swal('Gagal', 'Gagal menghapus data absensi.', 'error');
        }
      });
    }
  });
});

$(document).ready(function () {
  loadData();
});

// Approve manual attendance
$(document).on("click", ".btn-approve-manual", function () {
  var id = $(this).data("id");
  var btn = $(this);
  swal({
    title: 'Setujui Absensi Manual?',
    text: 'Absensi manual ini akan disetujui.',
    icon: 'info',
    buttons: ['Batal', 'Ya, Setujui'],
  }).then(function(ok) {
    if (ok) {
      btn.prop("disabled", true);
      $.ajax({
        url: "./mod/laporan-absensi/proses.php?action=approve_manual",
        type: "POST",
        data: { id: id },
        dataType: "json",
        success: function (res) {
          if (res.status === "success") {
            loadData();
            swal('Berhasil', res.message, 'success');
          } else {
            swal('Gagal', res.message, 'error');
          }
        },
        complete: function() { btn.prop("disabled", false); }
      });
    }
  });
});

// Reject manual attendance
$(document).on("click", ".btn-reject-manual", function () {
  var id = $(this).data("id");
  var btn = $(this);
  swal({
    title: 'Tolak Absensi Manual?',
    text: 'Absensi manual ini akan ditolak dan dihapus.',
    icon: 'warning',
    buttons: ['Batal', 'Ya, Tolak'],
    dangerMode: true
  }).then(function(ok) {
    if (ok) {
      btn.prop("disabled", true);
      $.ajax({
        url: "./mod/laporan-absensi/proses.php?action=reject_manual",
        type: "POST",
        data: { id: id },
        dataType: "json",
        success: function (res) {
          if (res.status === "success") {
            loadData();
            swal('Berhasil', res.message, 'success');
          } else {
            swal('Gagal', res.message, 'error');
          }
        },
        complete: function() { btn.prop("disabled", false); }
      });
    }
  });
});