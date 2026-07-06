"use strict";

$(document).ready(function () {
  // Cek ketersediaan SweetAlert
  if (typeof swal === "undefined") {
    console.error(
      "SweetAlert library is not loaded. Please check footer inclusion."
    );
    return;
  }

  // Tangkap form pengajuan izin (cari action yang mengandung add_izin)
  var $formIzin = $("form[action*='add_izin']");
  if (!$formIzin.length) return;

  $formIzin.on("submit", function (e) {
    e.preventDefault();
    var form = this;

    // Ambil nilai untuk ringkasan konfirmasi dan validasi sederhana
    var jenis = $(form).find('[name="jenis_izin"]').val().trim();
    var mulai = $(form).find('[name="tanggal_mulai"]').val().trim();

    if (!jenis || !mulai) {
      swal({
        title: "Data tidak lengkap",
        text: "Jenis izin dan tanggal mulai wajib diisi.",
        type: "warning",
        confirmButtonText: "OK",
      });
      return;
    }

    var selesaiVal =
      $(form).find('[name="tanggal_selesai"]').val().trim() || mulai;
    var keterangan = $(form).find('[name="keterangan"]').val().trim();

    // Konfirmasi sebelum submit
    swal({
      title: "Ajukan Izin?",
      text:
        "Jenis: " + jenis + "\nMulai: " + mulai + "\nSelesai: " + selesaiVal,
      type: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Ajukan",
      cancelButtonText: "Batal",
    }).then(function (confirmed) {
      if (!confirmed) return;

      // Tampilkan loading
      swal({
        title: "Mengirim...",
        text: "Mohon tunggu, permohonan izin sedang dikirim.",
        type: "info",
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
      });

      // Kirim via AJAX, gunakan serialized data
      $.ajax({
        url: $(form).attr("action") || "mod/izin/proses.php?action=add_izin",
        type: "POST",
        data: $formIzin.serialize(),
        dataType: "json",
        timeout: 20000,
        success: function (response) {
          swal.close();

          if (response.success) {
            swal({
              title: "Berhasil!",
              text: response.message,
              type: "success",
              confirmButtonText: "OK",
            }).then(function () {
              window.location.href = "?mod=izin";
            });
          } else {
            swal({
              title: "Perhatian",
              text: response.message,
              type: "warning",
              confirmButtonText: "OK",
            });
          }
        },
        error: function (xhr, status, err) {
          swal.close();

          var errorMessage = "Terjadi kesalahan saat mengirim permohonan.";

          // Coba parse JSON response
          try {
            var jsonResp = JSON.parse(xhr.responseText);
            if (jsonResp.message) {
              errorMessage = jsonResp.message;
            }
          } catch (e) {
            // Bukan JSON, gunakan text biasa
            if (status === "timeout") {
              errorMessage = "Request timeout. Silakan coba lagi.";
            } else if (xhr.responseText) {
              errorMessage = xhr.responseText.trim() || errorMessage;
            } else {
              errorMessage += " (" + err + ")";
            }
          }

          swal({
            title: "Error",
            text: errorMessage,
            type: "error",
            confirmButtonText: "OK",
          });
        },
      });
    });
  });
});
