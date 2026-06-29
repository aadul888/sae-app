"use strict";

// ====== COPY CLIENT EMAIL ======
function copyClientEmail() {
  var input = document.getElementById("clientEmail");
  if (!input) return;
  input.select();
  input.setSelectionRange(0, 99999);
  try {
    document.execCommand("copy");
    swal({
      title: "Tersalin!",
      text: "Email client berhasil disalin.",
      icon: "success",
      timer: 1200,
    });
  } catch (e) {
    swal({
      title: "Gagal",
      text: "Gagal menyalin. Salin manual: " + input.value,
      icon: "error",
    });
  }
}

$(function () {
  $('[data-toggle="tooltip"]').tooltip();

  // ====== CUSTOM FILE INPUT (Bootstrap 4) ======
  $(document).on("change", ".custom-file-input", function () {
    var fileName = $(this).val().split("\\").pop();
    $(this).siblings(".custom-file-label").addClass("selected").text(fileName);
  });

  // ====== LIVE PREVIEW GELAR ======
  function updateKepsekPreview() {
    var gelarDepan = $("#kepsek_gelar_depan").val().trim();
    var gelarBelakang = $("#kepsek_gelar_belakang").val().trim();
    var nama = $("#kepsek_fullname").val().trim();
    var preview =
      (gelarDepan ? gelarDepan + " " : "") +
      nama +
      (gelarBelakang ? ", " + gelarBelakang : "");
    $("#kepsekPreview .gelar-badge").text(
      preview || "Nama dengan gelar akan tampak di sini",
    );
  }

  $("#kepsek_gelar_depan, #kepsek_gelar_belakang").on(
    "input",
    updateKepsekPreview,
  );
  updateKepsekPreview();

  // ====== LOAD KEPSEK DATA when dropdown changes ======
  window.loadKepsekData = function (adminId) {
    if (!adminId) return;
    $.get(
      "./mod/surat-setting/proses.php?action=load_kepsek&admin_id=" + adminId,
      function (data) {
        try {
          var d = typeof data === "object" ? data : JSON.parse(data);
          if (d.status === "success" && d.data) {
            $("#kepsek_fullname").val(d.data.fullname || "");
            $("#kepsek_nip").val(d.data.gtk_nip || "-");
            $("#kepsek_gelar_depan").val(d.data.gelar_depan || "");
            $("#kepsek_gelar_belakang").val(d.data.gelar_belakang || "");
            updateKepsekPreview();
          }
        } catch (e) {
          // silent
        }
      },
    );
  };

  // ====== SAVE FORM ======
  $(document).on("submit", "#formSuratSetting", function (e) {
    e.preventDefault();
    var btn = $("#btnSave");
    btn
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

    var fd = new FormData(this);
    $.ajax({
      url: "./mod/surat-setting/proses.php?action=save_settings",
      type: "POST",
      data: fd,
      processData: false,
      contentType: false,
      success: function (data) {
        var t = typeof data === "string" ? data.trim() : "";
        if (t === "success") {
          swal({
            title: "Berhasil!",
            text: "Pengaturan surat berhasil disimpan.",
            icon: "success",
            timer: 1500,
          });
          setTimeout(function () {
            location.reload();
          }, 1200);
        } else {
          swal({
            title: "Gagal!",
            text: t || "Terjadi kesalahan saat menyimpan.",
            icon: "error",
          });
        }
      },
      error: function () {
        swal({
          title: "Error!",
          text: "Koneksi gagal. Coba lagi.",
          icon: "error",
        });
      },
      complete: function () {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-2"></i> Simpan Pengaturan');
      },
    });
  });
});
