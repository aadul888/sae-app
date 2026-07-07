"use strict";

var tableGuru;

function cleanupGuruModal() {
  var hasVisible = $(".modal.show:visible").length > 0;
  if (!hasVisible) {
    $("body")
      .removeClass("modal-open")
      .css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop").remove();
  }
  if ($(window).width() >= 1200) $(".backdrop.d-xl-none").remove();
}

function hardUnlockGuruIfStuck() {
  if (!$(".modal.show:visible").length && !$(".swal-overlay:visible").length) {
    $("body")
      .removeClass("modal-open")
      .css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop").remove();
  }
}

function openGuruFilterModal() {
  var $modal = $(".modal-filter-guru");
  if (!$modal.length) return;

  cleanupGuruModal();
  $modal.modal({ backdrop: false, keyboard: true, show: true });
  setTimeout(function () {
    $("body")
      .removeClass("modal-open")
      .css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop").remove();
  }, 30);
}

$(document).ready(function () {
  loadGuruTable();

  $(document).off(".guruModalFix");

  // Tombol filter
  $(document).on("click.guruModalFix", ".btn-open-filter-guru", function (e) {
    e.preventDefault();
    openGuruFilterModal();
  });

  // Sinkron nilai filter saat modal terbuka
  $(".modal-filter-guru").on("shown.bs.modal.guruModalFix", function () {
    $("body")
      .removeClass("modal-open")
      .css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop").remove();
  });

  // Terapkan filter
  $(document).on("click.guruModalFix", ".btn-apply-filter-guru", function () {
    $(".modal-filter-guru").modal("hide");
    setTimeout(function () {
      if (tableGuru) tableGuru.ajax.reload();
    }, 220);
  });

  // Reset filter
  $(document).on("click.guruModalFix", ".btn-reset-filter-guru", function () {
    $(".filter-jenis-ptk").val("");
    $(".filter-status-kepegawaian").val("");
    $(".filter-jabatan-ptk").val("");
    $(".modal-filter-guru").modal("hide");
    setTimeout(function () {
      if (tableGuru) tableGuru.ajax.reload();
    }, 220);
  });

  // Cleanup saat modal tertutup
  $(document).on("hidden.bs.modal.guruModalFix", ".modal", function () {
    cleanupGuruModal();
    hardUnlockGuruIfStuck();
  });

  $(document).on(
    "click.guruModalFix",
    '.modal .close, .modal [data-dismiss="modal"]',
    function () {
      $(this).closest(".modal").modal("hide");
    },
  );

  // --- Edit Gelar ---
  $(document).on("click", ".btn-edit-gelar", function () {
    var $btn = $(this);
    $("#edit-gelar-admin-id").val($btn.data("admin-id"));
    $("#edit-gelar-depan").val($btn.data("gelar-depan"));
    $("#edit-gelar-belakang").val($btn.data("gelar-belakang"));
    $(".modal-edit-gelar").modal("show");
  });

  $(document).on("submit", ".form-edit-gelar", function (e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form
      .find(".btn-save-gelar")
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

    $.ajax({
      url: "./mod/guru/proses.php",
      method: "POST",
      data: $form.serialize(),
      dataType: "json",
      success: function (res) {
        try {
          if (res.status === "success") {
            if (typeof Swal !== "undefined") {
              Swal.fire({
                title: "Berhasil",
                text: res.message,
                icon: "success",
                timer: 1200,
                showConfirmButton: false,
              });
            }
            $(".modal-edit-gelar").modal("hide");
            if (tableGuru) tableGuru.ajax.reload(null, false);
          } else {
            if (typeof Swal !== "undefined") {
              Swal.fire({ title: "Gagal", text: res.message, icon: "error" });
            }
          }
        } catch (e) {
          console.error(e);
        }
      },
      error: function () {
        try {
          if (typeof Swal !== "undefined") {
            Swal.fire({
              title: "Error",
              text: "Terjadi kesalahan server.",
              icon: "error",
            });
          }
        } catch (e) {
          console.error(e);
        }
      },
      complete: function () {
        $btn.prop("disabled", false).text("Simpan");
      },
    });
  });
});

function loadGuruTable() {
  tableGuru = $(".datatable-guru").DataTable({
    processing: true,
    serverSide: true,
    bAutoWidth: false,
    bStateSave: true,
    bDestroy: true,
    paging: true,
    iDisplayLength: 25,
    scrollX: true,
    order: [],
    aLengthMenu: [
      [25, 50, 100, -1],
      [25, 50, 100, "All"],
    ],
    language: {
      paginate: {
        previous: "<i class='fas fa-angle-left'>",
        next: "<i class='fas fa-angle-right'>",
      },
    },
    ajax: {
      url: "./mod/guru/datatable.php",
      type: "GET",
      data: function (d) {
        d.jenis_ptk = $(".filter-jenis-ptk").val();
        d.status_kepegawaian = $(".filter-status-kepegawaian").val();
        d.jabatan_ptk = $(".filter-jabatan-ptk").val();
      },
    },
    columnDefs: [{ targets: [0], className: "text-center", orderable: false }],
  });

  $(".datatable-guru").on("xhr.dt", function (e, settings, json) {
    if (!json || !json.stats) return;
    $("#guru-card-total").text(json.stats.total || 0);
    $("#guru-card-jenis").text(json.stats.jenis || 0);
    $("#guru-card-kepegawaian").text(json.stats.kepegawaian || 0);
  });
}

$(document)
  .off("click.guruCopyId")
  .on("click.guruCopyId", ".datatable-guru .copy-id-value", function (e) {
    e.preventDefault();
    var value = String($(this).data("copy") || "").trim();
    if (!value) return;

    var onSuccess = function () {
      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Berhasil",
          text: "ID berhasil dicopy ke clipboard",
          icon: "success",
          timer: 1200,
          showConfirmButton: false,
        });
      }
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard
        .writeText(value)
        .then(onSuccess)
        .catch(function () {
          var $tmp = $('<input type="text" />').val(value).appendTo("body");
          $tmp[0].select();
          document.execCommand("copy");
          $tmp.remove();
          onSuccess();
        });
      return;
    }

    var $tmp = $('<input type="text" />').val(value).appendTo("body");
    $tmp[0].select();
    document.execCommand("copy");
    $tmp.remove();
    onSuccess();
  });
