"use strict";

$(function () {
  var pendingToggle = null;

  function rollbackToggle() {
    if (!pendingToggle) return;
    $(pendingToggle.elem).prop("checked", pendingToggle.originalChecked);
    pendingToggle = null;
  }

  function statusLabel(status) {
    if (status === "LULUS") return "Lulus";
    if (status === "LULUS_BERSYARAT") return "Lulus Bersyarat";
    if (status === "TIDAK_LULUS") return "Tidak Lulus";
    return "Belum Diputuskan";
  }

  if ($.fn.DataTable && $("#table-kelulusan-user").length) {
    $("#table-kelulusan-user").DataTable({
      pageLength: 25,
      scrollX: true,
      scrollCollapse: true,
      order: [[3, "asc"], [2, "asc"]],
      language: {
        search: "Cari:",
        lengthMenu: "_MENU_ data per halaman",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        paginate: {
          previous: "Sebelumnya",
          next: "Berikutnya"
        }
      }
    });
  }

  function openReasonModal(userId, status, catatan) {
    $("#status_user_id").val(userId);
    $("#status_value").val(status);
    $("#status_value_label").text(statusLabel(status));
    $("#status_catatan").val(catatan || "");
    $("#modalStatusKelulusan").modal("show");
  }

  $(document).on("change", ".status-toggle-main", function () {
    var userId = $(this).data("id");
    var checked = $(this).is(":checked");
    var targetStatus = checked ? "LULUS" : "TIDAK_LULUS";
    var originalChecked = !checked;

    $("#status-syarat-" + userId).prop("checked", false);
    pendingToggle = { elem: this, originalChecked: originalChecked };
    openReasonModal(userId, targetStatus, $(this).data("note") || "");
  });

  $(document).on("change", ".status-toggle-syarat", function () {
    var userId = $(this).data("id");
    var checked = $(this).is(":checked");
    var targetStatus = checked ? "LULUS_BERSYARAT" : "TIDAK_LULUS";
    var originalChecked = !checked;

    $("#status-main-" + userId).prop("checked", false);
    pendingToggle = { elem: this, originalChecked: originalChecked };
    openReasonModal(userId, targetStatus, $(this).data("note") || "");
  });

  $("#form-status-kelulusan").on("submit", function (e) {
    e.preventDefault();
    $.ajax({
      url: "./mod/skl-user/proses.php?action=update-status",
      type: "POST",
      data: $(this).serialize(),
      success: function (res) {
        if ($.trim(res) === "success") {
          pendingToggle = null;
          swal({ title: "Berhasil", text: "Keputusan kelulusan disimpan.", icon: "success" }).then(function () {
            location.reload();
          });
        } else {
          swal({ title: "Gagal", text: res, icon: "error" });
        }
      },
      error: function () {
        swal({ title: "Error", text: "Terjadi kesalahan server.", icon: "error" });
      }
    });
  });

  $("#modalStatusKelulusan").on("hidden.bs.modal", function () {
    rollbackToggle();
  });

  $("#btn-lulus-semua").on("click", function () {
    swal({
      title: "Luluskan Semua?",
      text: "Semua murid kelas akhir akan diubah menjadi Lulus.",
      icon: "warning",
      buttons: ["Batal", "Ya, Lanjutkan"],
      dangerMode: true
    }).then(function (ok) {
      if (!ok) return;

      $.ajax({
        url: "./mod/skl-user/proses.php?action=mass-lulus",
        type: "POST",
        success: function (res) {
          var parts = $.trim(res).split("|");
          if (parts[0] === "success") {
            swal({
              title: "Berhasil",
              text: "Total murid yang diperbarui: " + (parts[1] || 0),
              icon: "success"
            }).then(function () {
              location.reload();
            });
          } else {
            swal({ title: "Gagal", text: res, icon: "error" });
          }
        },
        error: function () {
          swal({ title: "Error", text: "Terjadi kesalahan server.", icon: "error" });
        }
      });
    });
  });
});
