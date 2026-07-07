"use strict";

// Penunjukan Koordinator
$(document).on("click", ".btn-koordinator", function () {
  var id = $(this).attr("data-id");
  var name = $(this).attr("data-name");
  var isKoordinator = $(this).closest('tr').find('span.badge-info').length > 0;
  var setVal = isKoordinator ? 0 : 1;
  var actionText = isKoordinator ? "menghapus status koordinator dari " + name : "menjadikan " + name + " sebagai koordinator kelas";
  var confirmText = isKoordinator ? "Hapus status koordinator?" : "Jadikan Koordinator!";
  swal({
    title: confirmText,
    text: "Anda yakin ingin " + actionText + "?",
    icon: "info",
    buttons: {
      cancel: true,
      confirm: true,
    },
    value: "yes",
  }).then((value) => {
    if (value) {
      loading();
      $.ajax({
        url: "./mod/user/proses.php?action=set_koordinator",
        type: "POST",
        data: { id: id, set: setVal },
        success: function (data) {
          // Autoreload jika akses ditolak
          if (typeof data === 'string' && data.indexOf('Akses ditolak') !== -1) {
            swal({
              title: "Gagal!",
              text: data,
              icon: "error",
              timer: 1800
            });
            setTimeout(function() { window.location.reload(); }, 1800);
            return;
          }
          if (typeof data === 'string' && data.trim() === "success") {
            swal({
              title: "Berhasil!",
              text: isKoordinator ? (name + " bukan koordinator lagi.") : (name + " sekarang menjadi koordinator kelas."),
              icon: "success",
              timer: 2000,
            });
            loadData();
          } else {
            swal({ title: "Gagal!", text: data, icon: "error", timer: 2500 });
          }
        },
        complete: function () {
          $(".loading").hide();
        },
      });
    } else {
      return false;
    }
  });
});

// Upload Foto Massal (ZIP/JPG/PNG)
$(document).on('submit', '.form-import-photo', function(e) {
  e.preventDefault();
  var formData = new FormData(this);
  loading();
  $.ajax({
    url: './mod/user/proses.php?action=upload_photo',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    success: function(data) {
      // Autoreload jika akses ditolak
      if (typeof data === 'string' && data.indexOf('Akses ditolak') !== -1) {
        swal({
          title: 'Gagal!',
          text: data,
          icon: 'error',
          timer: 1800
        });
        setTimeout(function() { window.location.reload(); }, 1800);
        return;
      }
      if (typeof data === 'string' && data.trim().includes('Berhasil')) {
        swal({
          title: 'Berhasil!',
          text: data,
          icon: 'success',
          timer: 2500
        });
        $('.form-import-photo').trigger('reset');
        $('.modal-import-photo').modal('hide');
        loadData();
      } else {
        swal({ title: 'Gagal!', text: data, icon: 'error', timer: 3000 });
      }
    },
    complete: function() {
      $(".loading").hide();
    }
  });
});

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

/** Upload Drag and Drop */
function readURL(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function (e) {
      $(".image-upload-wrap").hide();
      $(".file-upload-image").attr("src", e.target.result);
      $(".file-upload-content").show();
      //$('.image-title').html(input.files[0].name);
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    removeUpload();
  }
}

function removeUpload() {
  $(".file-upload-input").replaceWith($(".file-upload-input").clone());
  $(".file-upload-content").hide();
  $(".image-upload-wrap").show();
  $(".fileInput").val("");
}
$(".image-upload-wrap").bind("dragover", function () {
  $(".image-upload-wrap").addClass("image-dropping");
});
$(".image-upload-wrap").bind("dragleave", function () {
  $(".image-upload-wrap").removeClass("image-dropping");
});

/** Module User/Siswa */
var tableUser;
function cleanupModalArtifacts() {
  var hasVisibleModal = $('.modal.show:visible').length > 0;
  var $ghostModals = $('.modal:visible').not('.show');

  if ($ghostModals.length > 0) {
    $ghostModals
      .removeClass('show')
      .attr('aria-hidden', 'true')
      .removeAttr('aria-modal')
      .css('display', 'none');
  }

  if (!hasVisibleModal) {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  } else {
    var $backdrops = $('.modal-backdrop');
    if ($backdrops.length > 1) {
      $backdrops.not(':last').remove();
    }
    $('.modal-backdrop:not(.show)').remove();
  }

  if ($(window).width() >= 1200) {
    $('.backdrop.d-xl-none').remove();
  }
}

function cleanupStaleUiOverlays() {
  if ($('.swal-overlay').length && $('.swal-modal:visible').length === 0) {
    $('.swal-overlay').remove();
    $('body').removeClass('stop-scrolling');
  }

  $('.mfp-bg, .mfp-wrap').filter(function () {
    return !$(this).is(':visible');
  }).remove();
}

function hardUnlockPageIfStuck() {
  if (
    $('.modal.show:visible').length === 0 &&
    $('.swal-overlay:visible').length === 0 &&
    $('.mfp-wrap:visible, .mfp-bg:visible').length === 0
  ) {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop, .modal-scrollbar-measure').remove();
    if ($(window).width() >= 1200) {
      $('.backdrop.d-xl-none').remove();
    }
    cleanupStaleUiOverlays();
  }
}

function showModalSafely($modal) {
  if (!$modal || !$modal.length) return;

  cleanupStaleUiOverlays();
  cleanupModalArtifacts();

  var isToolbarModal = $modal.is(
    '.modal-filter-kelas, .modal-search, .modal-import, .modal-import-photo, .modal-qrcode'
  );

  if (isToolbarModal) {
    $modal.modal({ backdrop: false, keyboard: true, show: true });
    setTimeout(function () {
      $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
      $('.modal-backdrop').remove();
    }, 30);
    return;
  }

  $modal.modal('show');
}

function forceOpenModal(selector) {
  var $modal = $(selector);
  if ($modal.length === 0) return;

  var $openedModal = $('.modal.show').not($modal);
  if ($openedModal.length > 0) {
    $openedModal.first().one('hidden.bs.modal', function () {
      showModalSafely($modal);
    });
    $openedModal.modal('hide');

    setTimeout(function () {
      if (!$modal.hasClass('show')) {
        showModalSafely($modal);
      }
    }, 360);
    return;
  }

  showModalSafely($modal);
}

function updateKelasLabel(text) {
  var label = text && text.trim() ? text.trim() : "Semua Kelas";
  if ($(".user-kelas-chip").length) {
    $(".user-kelas-chip").text(label);
  }
  if ($(".user-kelas-text").length) {
    $(".user-kelas-text").text(label);
  }
}

function loadData() {
  // Inisialisasi DataTable hanya sekali
  if ($.fn.DataTable.isDataTable('.datatable-user')) {
    $('.datatable-user').DataTable().destroy();
    $('.datatable-user').empty(); // Bersihkan table agar header tetap muncul
    $('.datatable-user').html('<thead class="thead-light"><tr><th class="text-center" style="width:10px;">No</th><th class="text-center" style="width:40px;">Avatar</th><th class="text-center" style="width:40px;">QRCODE</th><th style="width:70px;">NISN</th><th style="min-width:160px;max-width:220px;">Nama</th><th style="width:40px;">Jenis Kelamin</th><th style="width:40px;">Kelas</th><th style="width:40px;">Status</th><th style="width:40px;">Kontak</th><th style="width:40px;">Konfirmasi Data</th><th class="text-center" style="width:110px;min-width:100px;">Aksi</th></tr></thead><tbody></tbody>');
  }
  tableUser = $(".datatable-user").DataTable({
    fnDrawCallback: function () {
      $(".open-popup-link").magnificPopup({
        type: "image",
        removalDelay: 300,
        mainClass: "mfp-fade",
        gallery: { enabled: true },
        zoom: {
          enabled: true,
          duration: 300,
          easing: "ease-in-out",
          opener: function (openerElement) {
            return openerElement.is("img")
              ? openerElement
              : openerElement.find("img");
          },
        },
      });
    },
    processing: true,
    serverSide: false,
    bAutoWidth: false,
    bSort: false,
    bStateSave: false,
    bDestroy: true,
    paging: true,
    scrollX: true,
    scrollCollapse: true,
    responsive: false,
    ssSorting: [],
    iDisplayLength: 25,
    order: [],
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
      url: "./mod/user-tidak-aktif/datatable.php",
      type: "POST",
      data: function (d) {
        d.kelas = $(".filter-kelas").val();
      }
    },
    columnDefs: [
      {
        targets: [0],
        orderable: false,
      },
    ],
  });

  $(window)
    .off("resize.userTableInactive")
    .on("resize.userTableInactive", function () {
      if (tableUser) {
        tableUser.columns.adjust().draw(false);
      }
    });
}

// Jalankan saat halaman siap
$(document).ready(function () {
  $('body').addClass('page-user-module');
  loadData();

  $(document).off('.userModalFixInactive');
  $(window).off('.userModalFixInactive');

  $(document).on('click.userModalFixInactive', '.btn-open-filter-kelas', function (e) {
    e.preventDefault();
    forceOpenModal('.modal-filter-kelas');
    return false;
  });

  $(document).on('click.userModalFixInactive', '.btn-import', function (e) {
    e.preventDefault();
    $('.form-import').trigger('reset');
    forceOpenModal('.modal-import');
    return false;
  });

  $(document).on('click.userModalFixInactive', '.btn-import-photo', function (e) {
    e.preventDefault();
    $('.form-import-photo').trigger('reset');
    forceOpenModal('.modal-import-photo');
    return false;
  });

  $(document).on('click.userModalFixInactive', '.btn-print, .btn-qrcode, .btn-export-open', function (e) {
    e.preventDefault();
    forceOpenModal('.modal-qrcode');
    return false;
  });

  $(document).on('hidden.bs.modal.userModalFixInactive', '.modal', function () {
    cleanupStaleUiOverlays();
    cleanupModalArtifacts();
    hardUnlockPageIfStuck();
  });

  $(document).on(
    'shown.bs.modal.userModalFixInactive',
    '.modal-filter-kelas, .modal-search, .modal-import, .modal-import-photo, .modal-qrcode',
    function () {
      $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
      $('.modal-backdrop').remove();
    }
  );

  $(document).on('click.userModalFixInactive', '.modal .close, .modal [data-dismiss="modal"]', function () {
    var $modal = $(this).closest('.modal');
    if ($modal.length) {
      $modal.modal('hide');
    }
  });

  $(window).on('pageshow.userModalFixInactive focus.userModalFixInactive', function () {
    setTimeout(function () {
      cleanupStaleUiOverlays();
      cleanupModalArtifacts();
      hardUnlockPageIfStuck();
    }, 80);
  });

  $(document).on('visibilitychange.userModalFixInactive', function () {
    if (!document.hidden) {
      setTimeout(function () {
        cleanupStaleUiOverlays();
        cleanupModalArtifacts();
        hardUnlockPageIfStuck();
      }, 80);
    }
  });

  $(document).on('keyup.userModalFixInactive', function (e) {
    if (e.key === 'Escape') {
      $('.modal').modal('hide');
      cleanupModalArtifacts();
      hardUnlockPageIfStuck();
    }
  });

  $('.modal-filter-kelas').on('shown.bs.modal.userModalFixInactive', function () {
    var currentVal = $(".filter-kelas").val() || "";
    $(this).find(".modal-filter-kelas-select").val(currentVal);
  });

  $(document).on('click.userModalFixInactive', '.btn-apply-filter-kelas', function () {
    var $modal = $(this).closest(".modal-filter-kelas");
    var $select = $modal.find(".modal-filter-kelas-select");
    var selectedVal = $select.val() || "";
    var selectedText = $select.find("option:selected").text() || "Semua Kelas";

    $(".filter-kelas").val(selectedVal);
    updateKelasLabel(selectedText);

    $modal.modal('hide');

    setTimeout(function () {
      loadData();
    }, 220);
  });

  $(document).on('click.userModalFixInactive', '.btn-reset-filter-kelas', function () {
    var $modal = $(this).closest(".modal-filter-kelas");
    $modal.find(".modal-filter-kelas-select").val("");
    $(".filter-kelas").val("");
    updateKelasLabel("Semua Kelas");

    $modal.modal('hide');

    setTimeout(function () {
      loadData();
    }, 220);
  });

  updateKelasLabel("Semua Kelas");
});

$("body").on("click", ".datepicker", function () {
  $(this).datepicker({
    format: "dd-mm-yyyy",
    autoclose: true,
  });
  $(this).datepicker("show");
});

/** Tambah User/Pegawai */
$(".password").keypress(function (e) {
  if (e.which === 32) return false;
});

$(".toggle-password").click(function () {
  $(this).toggleClass("fa-eye fa-eye-slash");
  var input = $($(this).attr("toggle"));
  if (input.attr("type") == "password") {
    input.attr("type", "text");
  } else {
    input.attr("type", "password");
  }
});

/** Add */
$(".form-add").submit(function (e) {
  loading();
  e.preventDefault();
  $.ajax({
    url: "./mod/user/proses.php?action=add",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    async: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      // Autoreload jika akses ditolak
      if (typeof data === 'string' && data.indexOf('Akses ditolak') !== -1) {
        swal({
          title: "Gagal!",
          text: data,
          icon: "error",
          timer: 1800
        });
        setTimeout(function() { window.location.reload(); }, 1800);
        return;
      }
      if (typeof data === 'string' && data.trim() === "success") {
        swal({
          title: "Berhasil!",
          text: "Data berhasil disimpan.!",
          icon: "success",
          timer: 2500,
        }).then(function() {
          window.location.href = "./user";
        });
        $(".form-add").trigger("reset");
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2500 });
      }
    },
    complete: function () {
      $(".loading").hide();
    },
  });
});

/* ------ Update User/Siswa ------- */
$(".form-update").submit(function (e) {
  loading();
  e.preventDefault();
  $.ajax({
    url: "./mod/user/proses.php?action=update",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    async: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      // Autoreload jika akses ditolak
      if (typeof data === 'string' && data.indexOf('Akses ditolak') !== -1) {
        swal({
          title: "Gagal!",
          text: data,
          icon: "error",
          timer: 1800
        });
        setTimeout(function() { window.location.reload(); }, 1800);
        return;
      }
      if (typeof data === 'string' && data.trim() === "success") {
        swal({
          title: "Berhasil!",
          text: "Data berhasil disimpan.!",
          icon: "success",
          timer: 2500,
        }).then(function() {
          window.location.href = "./user";
        });
        $(".form-update").trigger("reset");
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2500 });
      }
    },
    complete: function () {
      $(".loading").hide();
    },
  });
});

/** ------- Forgot ---------- */
$(document).on("click", ".btn-forgot", function () {
  var id = $(this).attr("data-id");
  var name = $(this).attr("data-name");
  swal({
    title: "Resset Password!",
    text:
      "Anda yakin ingin meresset password " +
      name +
      ".?\r\nPassword baru: 123456",
    icon: "info",
    buttons: {
      cancel: true,
      confirm: true,
    },
    value: "yes",
  }).then((value) => {
    if (value) {
      loading();
      $.ajax({
        url: "./mod/user/proses.php?action=forgot",
        type: "POST",
        data: { id: id },
        success: function (data) {
          if (data == "success") {
            swal({
              title: "Berhasil!",
              text: "Password berhasil diresset.!",
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

/* ------------- Set Active User --------------*/
$(document).on("click", ".btn-active", function () {
  var id = $(this).attr("data-id");
  var active = $(".active" + id).attr("data-active");
  var isAktif = (active === "Y");
  var dataactive = isAktif ? "N" : "Y";
  var confirmText = isAktif ? "Nonaktifkan siswa ini?" : "Aktifkan siswa ini?";
  var confirmDesc = isAktif ? "Siswa akan dinonaktifkan dan tidak dapat login." : "Siswa akan diaktifkan dan dapat login.";
  swal({
    title: confirmText,
    text: confirmDesc,
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: isAktif ? "Nonaktifkan" : "Aktifkan",
        value: true,
        closeModal: false
      }
    },
    dangerMode: true
  }).then(function(willChange) {
    if (willChange) {
      var dataString = "id=" + id + "&active=" + dataactive;
      $.ajax({
        type: "POST",
        url: "./mod/user-tidak-aktif/proses.php?action=active",
        data: dataString,
        success: function (data) {
          // Autoreload jika akses ditolak
          if (typeof data === 'string' && data.indexOf('Akses ditolak') !== -1) {
            swal({
              title: "Gagal!",
              text: data,
              icon: "error",
              timer: 1800
            });
            setTimeout(function() { window.location.reload(); }, 1800);
            return;
          }
          if (data === "success") {
            swal({
              title: "Berhasil!",
              text: isAktif ? "Siswa berhasil dinonaktifkan." : "Siswa berhasil diaktifkan.",
              icon: "success",
              timer: 1500
            });
            loadData();
          } else {
            swal({ title: "Gagal!", text: data, icon: "error", timer: 2000 });
          }
        }
      });
    } else {
      // Kembalikan toggle ke posisi semula jika batal
      setTimeout(function() { loadData(); }, 300);
    }
  });
});

/** Import User/Pegawai */
$(".form-import").submit(function (e) {
  e.preventDefault();
  loading();
  $.ajax({
    url: "./mod/user/proses.php?action=import",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    async: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      // Autoreload jika akses ditolak
      if (typeof data === 'string' && data.indexOf('Akses ditolak') !== -1) {
        swal({
          title: "Gagal!",
          text: data,
          icon: "error",
          timer: 1800
        });
        setTimeout(function() { window.location.reload(); }, 1800);
        return;
      }
      if (typeof data === 'string' && data.includes("success")) {
        swal({
          title: "Berhasil!",
          text: "Data berhasil diimport!",
          icon: "success",
          timer: 2500,
        });
        $(".form-import").trigger("reset");
        $(".modal-import").modal("hide");
        loadData();
      } else {
        swal({ title: "Gagal!", text: data, icon: "error", timer: 3000 });
      }
    },
    complete: function () {},
  });
});

/** Hapus data User/pegawai */
$(document).on("click", ".btn-delete", function () {
  var id = $(this).attr("data-id");
  var name = $(this).attr("data-name");
  swal({
    text: "Anda yakin ingin menghapus user " + name + ".?",
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
        url: "./mod/user/proses.php?action=delete",
        type: "POST",
        data: { id: id },
        success: function (data) {
          // Autoreload jika akses ditolak
          if (typeof data === 'string' && data.indexOf('Akses ditolak') !== -1) {
            swal({
              title: "Gagal!",
              text: data,
              icon: "error",
              timer: 1800
            });
            setTimeout(function() { window.location.reload(); }, 1800);
            return;
          }
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

$(document).on("click", ".btn-qrcode", function () {
  $(".modal-qrcode").modal("show");
});

$(document).on("click", ".btn-export", function () {
  var kelas = $(".kelas").val();
  //var type = $("input:radio[name=type]:checked").val()
  var url = "./mod/user/print.php?kelas=" + kelas + "";
  window.open(url, "_blank");
});

// Reset Password User ke NISN
$(document).on("click", ".btn-reset-password", function () {
  var id = $(this).attr("data-id");
  var name = $(this).attr("data-name");
  swal({
    title: "Reset Password!",
    text:
      "Anda yakin ingin mereset password " +
      name +
      "?\nPassword akan direset ke NISN user ini.",
    icon: "info",
    buttons: {
      cancel: true,
      confirm: true,
    },
    value: "yes",
  }).then((value) => {
    if (value) {
      loading();
      $.ajax({
        url: "./mod/user/proses.php?action=reset_password",
        type: "POST",
        data: { id: id },
        success: function (data) {
          if (typeof data === 'string' && data.trim() === "success") {
            swal({
              title: "Berhasil!",
              text: "Password berhasil direset ke NISN!",
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


// Hapus Semua Siswa (Mass Delete)
$(document).on("click", ".btn-delete-all", function () {
  swal({
    title: "Hapus Semua Siswa!",
    text: "Anda yakin ingin menghapus SEMUA data siswa beserta seluruh aset terkait (avatar, qrcode, berkas, dll)?\nTindakan ini tidak dapat dibatalkan!",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Ya, Hapus Semua!",
        value: true,
        closeModal: false
      }
    },
    dangerMode: true,
  }).then((willDelete) => {
    if (willDelete) {
      loading();
      $.ajax({
        url: "./mod/user/proses.php?action=delete_all",
        type: "POST",
        success: function (data) {
          if (typeof data === 'string' && data.trim() === "success") {
            swal({
              title: "Berhasil!",
              text: "Seluruh data siswa dan aset terkait berhasil dihapus!",
              icon: "success",
              timer: 3000,
            });
            loadData();
          } else {
            swal({ title: "Gagal!", text: data, icon: "error", timer: 3000 });
          }
        },
        error: function () {
          swal({ title: "Gagal!", text: "Terjadi kesalahan koneksi.", icon: "error", timer: 3000 });
        },
        complete: function () {
          $(".loading").hide();
        },
      });
    } else {
      return false;
    }
  });
});

// Upload Foto Berdasarkan NISN
$(document).on('submit', '.form-upload-foto-nisn', function(e) {
  e.preventDefault();
  var formData = new FormData(this);
  loading();
  $.ajax({
    url: './mod/user/proses.php?action=upload_foto_nisn',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    success: function(data) {
      if (typeof data === 'string' && data.trim() === 'success') {
        swal({
          title: 'Berhasil!',
          text: 'Foto berhasil diupload!',
          icon: 'success',
          timer: 2000
        });
        $('.form-upload-foto-nisn').trigger('reset');
        loadData();
      } else {
        swal({ title: 'Gagal!', text: data, icon: 'error', timer: 2500 });
      }
    },
    complete: function() {
      $(".loading").hide();
    }
  });
});