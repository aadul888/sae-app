"use strict";

$(document).on("change", ".jurusan-logo", function () {
  var preview = $("#logo-preview");
  preview.html("");
  if (this.files && this.files[0]) {
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.html(
        '<img src="' +
          e.target.result +
          '" alt="Preview Logo" style="max-width:100px;max-height:100px;border:1px solid #ddd;padding:2px;border-radius:6px;">',
      );
    };
    reader.readAsDataURL(this.files[0]);
  }
});

function setSaveButtonLoading(isLoading) {
  var btn = $(".btn-save");
  if (isLoading) {
    btn.prop("disabled", true);
    btn.html(
      '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Menyimpan...',
    );
  } else {
    btn.prop("disabled", false);
    btn.html('<i class="far fa-save mr-1"></i> Simpan Perubahan');
  }
}

$(document).ready(function () {
  loadData();
});

function loadData() {
  $(".datatable").DataTable({
    scrollY: false,
    scrollX: false,
    processing: true,
    serverSide: false,
    bAutoWidth: true,
    bSort: true,
    bStateSave: true,
    bDestroy: true,
    paging: true,
    aaSorting: [
      [2, "asc"],
      [4, "asc"],
    ],
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
      url: "./mod/jurusan/datatable.php",
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

$(document).on("click", ".btn-update", function () {
  var id = $(this).attr("data-id");
  var kode = $(this).attr("data-kode");
  var name = $(this).attr("data-name");

  $(".id").val(id);
  $(".jurusan-kode").val(kode);
  $(".jurusan-nama").val(name);
  $(".modal-title-name").text(name);
  $("#logo-preview").html("");
  $(".jurusan-logo").val("");
  setSaveButtonLoading(false);
  $(".modal-add").appendTo("body").modal("show");
});

$(".form-add").validate({
  rules: {
    kode_jurusan: { required: true },
  },
  messages: {
    kode_jurusan: { required: "Kode jurusan wajib diisi" },
  },
  submitHandler: submitForm_Logo,
});

function submitForm_Logo() {
  var form = $(".form-add")[0];
  var formData = new FormData(form);
  if (!formData.has("csrf_token") && window.CSRF_TOKEN) {
    formData.append("csrf_token", window.CSRF_TOKEN);
  }

  $.ajax({
    type: "POST",
    url: "./mod/jurusan/proses.php?action=update_meta",
    data: formData,
    cache: false,
    contentType: false,
    processData: false,
    beforeSend: function () {
      setSaveButtonLoading(true);
    },
    success: function (data) {
      if (data === "success") {
        swal({
          title: "Berhasil!",
          text: "Data jurusan berhasil diperbarui.",
          icon: "success",
          timer: 1500,
          buttons: false,
        }).then(function () {
          location.reload();
        });
        setTimeout(function () {
          location.reload();
        }, 1500);
      } else {
        swal({
          title: "Oops!",
          text: data,
          icon: "error",
          timer: 2500,
        });
      }
    },
    error: function (xhr, status, err) {
      var msg = "Terjadi kesalahan pada server.";
      if (xhr && xhr.responseText) {
        msg = xhr.responseText.replace(/(<([^>]+)>)/gi, "").substring(0, 250);
      }
      swal({
        title: "Error!",
        text: msg,
        icon: "error",
      });
    },
    complete: function () {
      setSaveButtonLoading(false);
    },
  });

  return false;
}
