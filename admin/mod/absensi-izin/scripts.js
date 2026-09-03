"use strict";

var tableIzin;

function cleanupIzinModal() {
  var hasVisible = $(".modal.show:visible").length > 0;
  if (!hasVisible) {
    $("body").removeClass("modal-open").css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop").remove();
  }
  if ($(window).width() >= 1200) {
    $(".backdrop.d-xl-none").remove();
  }
}

function hardUnlockIzinIfStuck() {
  if (
    !$(".modal.show:visible").length &&
    !$(".swal-overlay:visible").length &&
    !$(".mfp-wrap:visible, .mfp-bg:visible").length
  ) {
    $("body").removeClass("modal-open").css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop, .modal-scrollbar-measure").remove();
    if ($(window).width() >= 1200) {
      $(".backdrop.d-xl-none").remove();
    }
  }
}

function openIzinFilterModal() {
  var $modal = $(".modal-filter-izin");
  if (!$modal.length) return;

  cleanupIzinModal();
  $modal.modal({ backdrop: false, keyboard: true, show: true });
  setTimeout(function () {
    $("body").removeClass("modal-open").css({ "padding-right": "", overflow: "" });
    $(".modal-backdrop").remove();
  }, 30);
}

function updateIzinStats(stats) {
  stats = stats || {};
  $("#izin-card-total").text(stats.total || 0);
  $("#izin-card-menunggu").text(stats.menunggu || 0);
  $("#izin-card-setuju").text(stats.disetujui || 0);
  $("#izin-card-tolak").text(stats.ditolak || 0);
}

function notifyResult(response) {
  var text = response || "Terjadi kesalahan";
  var isSuccess = String(text).toLowerCase().indexOf("berhasil") !== -1;
  swal("Info", text, isSuccess ? "success" : "error");
}

function reloadIzinTable() {
  if (tableIzin) {
    tableIzin.ajax.reload(null, false);
  }
}

function loadData() {
  tableIzin = $(".datatable-izin").DataTable({
    processing: true,
    serverSide: true,
    bAutoWidth: false,
    bStateSave: true,
    bDestroy: true,
    paging: true,
    iDisplayLength: 25,
    scrollX: true,
    order: [],
    aLengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
    language: {
      paginate: {
        previous: "<i class='fas fa-angle-left'></i>",
        next: "<i class='fas fa-angle-right'></i>",
      },
    },
    ajax: {
      url: "./mod/absensi-izin/datatable.php",
      type: "POST",
      data: function (d) {
        d.filter_status = $(".filter-status-izin").val();
      },
    },
    columnDefs: [{ targets: [0, 7], className: "text-center", orderable: false }],
  });

  $(".datatable-izin").on("xhr.dt", function (e, settings, json) {
    if (!json || !json.statusStat) return;
    updateIzinStats(json.statusStat);
  });
}

$(document).ready(function () {
  loadData();
  $(document).off(".izinModalFix");

  $(document).on("click.izinModalFix", ".btn-open-filter-izin", function (e) {
    e.preventDefault();
    openIzinFilterModal();
  });

  $(document).on("click.izinModalFix", ".btn-reload-izin", function () {
    reloadIzinTable();
  });

  $(document).on("click.izinModalFix", ".btn-apply-filter-izin", function () {
    $(".modal-filter-izin").modal("hide");
    setTimeout(function () {
      reloadIzinTable();
    }, 220);
  });

  $(document).on("click.izinModalFix", ".btn-reset-filter-izin", function () {
    $(".filter-status-izin").val("");
    $(".modal-filter-izin").modal("hide");
    setTimeout(function () {
      reloadIzinTable();
    }, 220);
  });

  $(document).on("hidden.bs.modal.izinModalFix", ".modal", function () {
    cleanupIzinModal();
    hardUnlockIzinIfStuck();
  });

  $(document).on("click.izinModalFix", ".modal .close, .modal [data-dismiss='modal']", function () {
    $(this).closest(".modal").modal("hide");
  });
});

// Tombol SETUJUI
$(document).on("click", ".btn-approve", function () {
  var id = $(this).attr("data-id");
  swal({
    title: "Setujui Pengajuan Izin?",
    text: "Izin ini akan disetujui.",
    icon: "info",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Setujui",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
  }).then((willApprove) => {
    if (willApprove) {
      $.ajax({
        url: "./mod/absensi-izin/proses.php?action=setujui",
        type: "POST",
        data: { id: id },
        success: function (response) {
          notifyResult(response);
          reloadIzinTable();
        },
      });
    }
  });
});

// Tombol TOLAK
$(document).on("click", ".btn-reject", function () {
  var id = $(this).attr("data-id");
  swal({
    title: "Tolak Pengajuan Izin?",
    text: "Berikan alasan penolakan.",
    content: {
      element: "input",
      attributes: {
        placeholder: "Isi alasan penolakan",
        type: "text",
      },
    },
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Tolak",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
  }).then((alasan) => {
    if (alasan) {
      $.ajax({
        url: "./mod/absensi-izin/proses.php?action=tolak",
        type: "POST",
        data: { id: id, alasan: alasan },
        success: function (response) {
          notifyResult(response);
          reloadIzinTable();
        },
      });
    }
  });
});

// Tombol Lihat Detail
$(document).on("click", ".btn-view-detail", function () {
  var id = $(this).data("id");
  $.ajax({
    type: "POST",
    url: "./mod/absensi-izin/proses.php?action=detail",
    data: { id: id },
    success: function (response) {
      $("#detail-content").html(response);
      $("#modal-detail").modal("show");
    },
    error: function () {
      alert("Gagal mengambil data detail.");
    },
  });
});

// Tombol Lihat Catatan
$(document).on("click", ".btn-view-catatan", function () {
  var catatan = $(this).data("catatan");
  swal({
    title: "Catatan Penolakan",
    text: catatan,
    icon: "info",
    button: "Tutup",
  });
});

// Tombol Edit Catatan
$(document).on("click", ".btn-edit-catatan", function () {
  var id = $(this).data("id");
  var catatan = $(this).data("catatan");
  $("#edit-id").val(id);
  $("#edit-catatan").val(catatan);
  $("#modal-edit-catatan").modal("show");
});

// Simpan Edit Catatan
$("#form-edit-catatan").on("submit", function (e) {
  e.preventDefault();
  var id = $("#edit-id").val();
  var catatan = $("#edit-catatan").val();

  $.ajax({
    url: "./mod/absensi-izin/proses.php?action=edit_catatan",
    type: "POST",
    data: { id: id, catatan: catatan },
    success: function (response) {
      $("#modal-edit-catatan").modal("hide");
      notifyResult(response);
      reloadIzinTable();
    },
  });
});

// Tombol HAPUS
$(document).on("click", ".btn-delete", function () {
  var id = $(this).data("id");

  swal({
    title: "Hapus Pengajuan Izin?",
    text: "Data ini akan dihapus secara permanen.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Hapus",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
    dangerMode: true,
  }).then((willDelete) => {
    if (willDelete) {
      $.ajax({
        url: "./mod/absensi-izin/proses.php?action=hapus",
        type: "POST",
        data: { id: id },
        success: function (response) {
          notifyResult(response);
          reloadIzinTable();
        },
      });
    }
  });
});

// Modal SETUJUI
$(document).on("click", "#btn-setujui", function () {
  var id = $(this).data("id");

  swal({
    title: "Setujui Pengajuan Izin?",
    text: "Data ini akan disetujui.",
    icon: "info",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Setujui",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
  }).then((willApprove) => {
    if (willApprove) {
      $.ajax({
        url: "./mod/absensi-izin/proses.php?action=setujui",
        type: "POST",
        data: { id: id },
        success: function (response) {
          $("#modal-detail").modal("hide");
          notifyResult(response);
          reloadIzinTable();
        },
      });
    }
  });
});

// Modal TOLAK
$(document).on("click", "#btn-tolak", function () {
  var id = $(this).data("id");
  var alasan = $("#catatan-penolakan").val().trim();

  if (alasan === "") {
    swal("Oops!", "Catatan penolakan wajib diisi.", "warning");
    return;
  }

  swal({
    title: "Tolak Pengajuan Izin?",
    text: "Data ini akan ditolak dengan catatan.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Tolak",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
  }).then((willReject) => {
    if (willReject) {
      $.ajax({
        url: "./mod/absensi-izin/proses.php?action=tolak",
        type: "POST",
        data: { id: id, alasan: alasan },
        success: function (response) {
          $("#modal-detail").modal("hide");
          notifyResult(response);
          reloadIzinTable();
        },
      });
    }
  });
});

// Submit Form Izin (Siswa)
$(document).on("submit", "#form-izin", function (e) {
  e.preventDefault();
  const formData = $(this).serialize();

  $.ajax({
    url: "./mod/absensi-izin/proses.php?action=ajukan",
    type: "POST",
    data: formData,
    beforeSend: function () {
      swal({
        title: "Mohon Tunggu...",
        text: "Sedang memproses pengajuan izin",
        buttons: false,
        closeOnClickOutside: false,
        closeOnEsc: false,
      });
    },
    success: function (response) {
      swal({
        title: response.includes("berhasil") ? "Berhasil" : "Gagal",
        text: response,
        icon: response.includes("berhasil") ? "success" : "error",
        timer: 2500,
      });

      if (response.includes("berhasil")) {
        $("#form-izin")[0].reset();

        // ✅ Auto reload table setelah 2 detik
        setTimeout(function () {
          reloadIzinTable();
        }, 2000);
      }
    },
    error: function () {
      swal.close();
      swal({
        title: "Gagal",
        text: "Terjadi kesalahan saat menghubungi server.",
        icon: "error",
        timer: 2500,
        button: false,
      });
    },
  });
});
