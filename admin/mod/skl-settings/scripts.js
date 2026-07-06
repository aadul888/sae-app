"use strict";

$(function () {
  $("#form-kelulusan-toggle").on("submit", function (e) {
    e.preventDefault();
    var data = $(this).serializeArray();
    var checkboxes = ["is_open", "show_skl_to_user", "allow_download_skl"];
    checkboxes.forEach(function (name) {
      if (!$(this).find("[name='" + name + "']").is(":checked")) {
        data.push({ name: name, value: "N" });
      }
    }.bind(this));

    $.ajax({
      url: "./mod/skl-settings/proses.php?action=update-setting",
      type: "POST",
      data: data,
      success: function (res) {
        if ($.trim(res) === "success") {
          swal({ title: "Berhasil", text: "Pengaturan kelulusan disimpan.", icon: "success" });
        } else {
          swal({ title: "Gagal", text: res, icon: "error" });
        }
      },
      error: function () {
        swal({ title: "Error", text: "Terjadi kesalahan server.", icon: "error" });
      }
    });
  });

  $("#form-skl-settings").on("submit", function (e) {
    e.preventDefault();
    $.ajax({
      url: "./mod/skl-settings/proses.php?action=save",
      type: "POST",
      data: $(this).serialize(),
      success: function (res) {
        if ($.trim(res) === "success") {
          swal({ title: "Berhasil", text: "Pengumuman kelulusan disimpan.", icon: "success" });
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
