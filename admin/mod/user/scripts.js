"use strict";

// Penunjukan Koordinator
$(document).on("click", ".btn-koordinator", function () {
  var id = $(this).attr("data-id");
  var name = $(this).attr("data-name");
  var isKoordinator = $(this).closest("tr").find("span.badge-info").length > 0;
  var setVal = isKoordinator ? 0 : 1;
  var actionText = isKoordinator
    ? "menghapus status koordinator dari " + name
    : "menjadikan " + name + " sebagai koordinator kelas";
  var confirmText = isKoordinator
    ? "Hapus status koordinator?"
    : "Jadikan Koordinator!";
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
          if (
            typeof data === "string" &&
            data.indexOf("Akses ditolak") !== -1
          ) {
            swal({ title: "Gagal!", text: data, icon: "error", timer: 1800 });
            setTimeout(function () {
              window.location.reload();
            }, 1800);
            return;
          }
          if (typeof data === "string" && data.trim() === "success") {
            swal({
              title: "Berhasil!",
              text: isKoordinator
                ? name + " bukan koordinator lagi."
                : name + " sekarang menjadi koordinator kelas.",
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
$(document).on("submit", ".form-import-photo", function (e) {
  e.preventDefault();
  var formData = new FormData(this);
  loading();
  $.ajax({
    url: "./mod/user/proses.php?action=upload_photo",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
      // Autoreload jika akses ditolak
      if (typeof data === "string" && data.indexOf("Akses ditolak") !== -1) {
        swal({
          title: "Gagal!",
          text: data,
          icon: "error",
          timer: 1800,
        });
        setTimeout(function () {
          window.location.reload();
        }, 1800);
        return;
      }
      if (typeof data === "string" && data.trim().includes("Berhasil")) {
        swal({
          title: "Berhasil!",
          text: data,
          icon: "success",
          timer: 2500,
        });
        $(".form-import-photo").trigger("reset");
        $(".modal-import-photo").modal("hide");
        loadData();
      } else {
        swal({ title: "Gagal!", text: data, icon: "error", timer: 3000 });
      }
    },
    complete: function () {
      $(".loading").hide();
    },
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

/** Module User/Siswa */
var tableUser;

function cleanupModalArtifacts() {
  var hasVisibleModal = $('.modal.show:visible').length > 0;
  var $ghostModals = $('.modal:visible').not('.show');

  // Force-hide ghost modals left by interrupted transitions.
  if ($ghostModals.length > 0) {
    $ghostModals
      .removeClass('show')
      .attr('aria-hidden', 'true')
      .removeAttr('aria-modal')
      .css('display', 'none');
  }

  if (!hasVisibleModal) {
    $('body')
      .removeClass('modal-open')
      .css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  } else {
    var $backdrops = $('.modal-backdrop');
    if ($backdrops.length > 1) {
      $backdrops.not(':last').remove();
    }
    $('.modal-backdrop:not(.show)').remove();
  }

  // Desktop safety: mobile sidenav backdrop must not block desktop clicks.
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
    $('body')
      .removeClass('modal-open')
      .css({ 'padding-right': '', overflow: '' });
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

    // Fallback if hidden event does not fire.
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
  if ($.fn.DataTable.isDataTable(".datatable-user")) {
    $(".datatable-user").DataTable().destroy();
    $(".datatable-user").empty(); // Bersihkan table agar header tetap muncul
    $(".datatable-user").html(
      '<thead class="thead-light"><tr><th class="text-center" style="width:10px;">No</th><th class="text-center" style="width:40px;">Avatar</th><th class="text-center" style="width:40px;">QRCODE</th><th style="width:70px;">NISN</th><th style="min-width:160px;max-width:220px;">Nama</th><th style="width:40px;">Jenis Kelamin</th><th style="width:40px;">Kelas</th><th style="width:40px;">Status</th><th style="width:40px;">Kontak</th><th style="width:40px;">Konfirmasi Data</th><th class="text-center" style="width:110px;min-width:100px;">Aksi</th></tr></thead><tbody></tbody>'
    );
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
    serverSide: true,
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
      url: "./mod/user/datatable.php",
      type: "GET",
      data: function (d) {
        d.kelas = $(".filter-kelas").val();
      },
      dataSrc: function (json) {
        try {
          if (json && json.statusStat) {
            var s = json.statusStat;
            $("#user-stat-total .value").text(s.total || 0);
            $("#user-stat-identitas .value").text(s.identitas_sesuai || 0);
            $("#user-stat-belum-sesuai .value").text(
              s.identitas_belum_sesuai || 0
            );
            $("#user-stat-belum .value").text(s.belum_konfirmasi || 0);
            $("#user-stat-berkas-valid .value").text(s.berkas_valid || 0);
            $("#user-stat-berkas-belum .value").text(s.berkas_belum || 0);
          }
        } catch (e) {
          console.warn("Failed to set user stats", e);
        }
        // DataTables expects an array of rows in aaData or data
        if (json && json.aaData) return json.aaData;
        if (json && json.data) return json.data;
        return [];
      },
    },
    columnDefs: [
      {
        targets: [0],
        orderable: false,
      },
    ],
  });

  $(window)
    .off("resize.userTable")
    .on("resize.userTable", function () {
      if (tableUser) {
        tableUser.columns.adjust().draw(false);
      }
    });
}

// Jalankan saat halaman siap
$(document).ready(function () {
  $('body').addClass('page-user-module');
  loadData();

  // Prevent duplicated handlers if this script is evaluated more than once.
  $(document).off('.userModalFix');
  $(window).off('.userModalFix');

  // Route toolbar modal buttons through a single modal-open path.
  $(document).on('click.userModalFix', '.btn-open-filter-kelas', function (e) {
    e.preventDefault();
    forceOpenModal('.modal-filter-kelas');
    return false;
  });

  $(document).on('click.userModalFix', '.btn-search-data', function (e) {
    e.preventDefault();
    forceOpenModal('.modal-search');
    return false;
  });

  $(document).on('click.userModalFix', '.btn-import', function (e) {
    e.preventDefault();
    $('.form-import').trigger('reset');
    forceOpenModal('.modal-import');
    return false;
  });

  $(document).on('click.userModalFix', '.btn-import-photo', function (e) {
    e.preventDefault();
    $('.form-import-photo').trigger('reset');
    forceOpenModal('.modal-import-photo');
    return false;
  });

  // Print and export open the same modal export selector.
  $(document).on('click.userModalFix', '.btn-print, .btn-qrcode, .btn-export-open', function (e) {
    e.preventDefault();
    forceOpenModal('.modal-qrcode');
    return false;
  });

  // Single source of truth: cleanup when modal fully hidden.
  $(document).on('hidden.bs.modal.userModalFix', '.modal', function () {
    cleanupStaleUiOverlays();
    cleanupModalArtifacts();
    hardUnlockPageIfStuck();
  });

  // Toolbar modals are intentionally non-blocking; keep body unlocked while open.
  $(document).on(
    'shown.bs.modal.userModalFix',
    '.modal-filter-kelas, .modal-search, .modal-import, .modal-import-photo, .modal-qrcode',
    function () {
      $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
      $('.modal-backdrop').remove();
    }
  );

  // Extra guard for dismiss buttons and close icon.
  $(document).on('click.userModalFix', '.modal .close, .modal [data-dismiss="modal"]', function () {
    var $modal = $(this).closest('.modal');
    if ($modal.length) {
      $modal.modal('hide');
    }
  });

  // Browser restore/back-forward cache can leave stale overlay locks.
  $(window).on('pageshow.userModalFix focus.userModalFix', function () {
    setTimeout(function () {
      cleanupStaleUiOverlays();
      cleanupModalArtifacts();
      hardUnlockPageIfStuck();
    }, 80);
  });

  $(document).on('visibilitychange.userModalFix', function () {
    if (!document.hidden) {
      setTimeout(function () {
        cleanupStaleUiOverlays();
        cleanupModalArtifacts();
        hardUnlockPageIfStuck();
      }, 80);
    }
  });

  // Manual escape hatch for stuck state.
  $(document).on('keyup.userModalFix', function (e) {
    if (e.key === 'Escape') {
      $('.modal').modal('hide');
      cleanupModalArtifacts();
      hardUnlockPageIfStuck();
    }
  });

  // Modal filter kelas: sinkronkan nilai awal
  $('.modal-filter-kelas').on('shown.bs.modal.userModalFix', function () {
    var currentVal = $('.filter-kelas').val() || '';
    $(this).find('.modal-filter-kelas-select').val(currentVal);
  });

  // Terapkan filter kelas dari modal
  $(document).on('click.userModalFix', '.btn-apply-filter-kelas', function () {
    var $modal = $(this).closest('.modal-filter-kelas');
    var $select = $modal.find('.modal-filter-kelas-select');
    var selectedVal = $select.val() || '';
    var selectedText = $select.find('option:selected').text() || 'Semua Kelas';

    $('.filter-kelas').val(selectedVal);
    updateKelasLabel(selectedText);
    $modal.modal('hide');

    setTimeout(function () {
      loadData();
    }, 220);
  });

  // Reset filter kelas
  $(document).on('click.userModalFix', '.btn-reset-filter-kelas', function () {
    var $modal = $(this).closest('.modal-filter-kelas');
    $modal.find('.modal-filter-kelas-select').val('');
    $('.filter-kelas').val('');
    updateKelasLabel('Semua Kelas');
    $modal.modal('hide');

    setTimeout(function () {
      loadData();
    }, 220);
  });

  // Inisialisasi teks filter saat pertama kali render
  updateKelasLabel('Semua Kelas');

});

// Clipboard copy handler for profile view (buttons with class .btn-copy)
$(document).on("click", ".btn-copy", function (e) {
  e.preventDefault();
  var $btn = $(this);
  var $p = $btn.closest("p");
  // support combined address copy (data-combine="address")
  var combine = $btn.attr("data-combine") || $btn.data("combine") || "";
  var text = "";
  if (combine === "address") {
    // find the nearest card container and collect address parts
    var $card = $btn.closest(".card");
    var alamat = ($card.find(".copy-alamat").first().text() || "").trim();
    var rt = ($card.find(".copy-rt").first().text() || "").trim();
    var rw = ($card.find(".copy-rw").first().text() || "").trim();
    var desa = ($card.find(".copy-desa").first().text() || "").trim();
    var kec = ($card.find(".copy-kecamatan").first().text() || "").trim();
    var kode = ($card.find(".copy-kodepos").first().text() || "").trim();

    if (alamat) text += alamat;
    if (rt || rw) {
      var rtrw = "RT/RW " + (rt || "-") + "/" + (rw || "-");
      text += (text ? ", " : "") + rtrw;
    }
    if (desa) text += (text ? ", " : "") + desa;
    if (kec) text += (text ? ", " : "") + kec;
    if (kode) text += (text ? " " : "") + kode;
  } else {
    var $span = $p.find(".copy-value").first();
    text = $span && $span.text() ? $span.text().trim() : "";
  }
  if (!text) return;

  function showSuccess() {
    var orig = $btn.html();
    $btn.html('<i class="fa fa-check"></i>');
    setTimeout(function () {
      $btn.html(orig);
    }, 1200);
  }

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard
      .writeText(text)
      .then(function () {
        showSuccess();
      })
      .catch(function () {
        // fallback to execCommand
        var $ta = $("<textarea>").val(text).appendTo("body").select();
        try {
          document.execCommand("copy");
          showSuccess();
        } catch (e) {
          // ignore
        }
        $ta.remove();
      });
  } else {
    var $ta = $("<textarea>").val(text).appendTo("body").select();
    try {
      document.execCommand("copy");
      showSuccess();
    } catch (e) {
      // ignore
    }
    $ta.remove();
  }
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
      if (typeof data === "string" && data.indexOf("Akses ditolak") !== -1) {
        swal({
          title: "Gagal!",
          text: data,
          icon: "error",
          timer: 1800,
        });
        setTimeout(function () {
          window.location.reload();
        }, 1800);
        return;
      }
      if (typeof data === "string" && data.trim() === "success") {
        swal({
          title: "Berhasil!",
          text: "Data berhasil disimpan.!",
          icon: "success",
          timer: 2500,
        }).then(function () {
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
      if (typeof data === "string" && data.indexOf("Akses ditolak") !== -1) {
        swal({
          title: "Gagal!",
          text: data,
          icon: "error",
          timer: 1800,
        });
        setTimeout(function () {
          window.location.reload();
        }, 1800);
        return;
      }
      if (typeof data === "string" && data.trim() === "success") {
        swal({
          title: "Berhasil!",
          text: "Data berhasil disimpan.!",
          icon: "success",
          timer: 2500,
        }).then(function () {
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
  var isAktif = active === "Y";
  var dataactive = isAktif ? "N" : "Y";
  var confirmText = isAktif ? "Nonaktifkan siswa ini?" : "Aktifkan siswa ini?";
  var confirmDesc = isAktif
    ? "Siswa akan dinonaktifkan dan tidak dapat login."
    : "Siswa akan diaktifkan dan dapat login.";
  swal({
    title: confirmText,
    text: confirmDesc,
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: isAktif ? "Nonaktifkan" : "Aktifkan",
        value: true,
        closeModal: false,
      },
    },
    dangerMode: true,
  }).then(function (willChange) {
    if (willChange) {
      var dataString = "id=" + id + "&active=" + dataactive;
      $.ajax({
        type: "POST",
        url: "./mod/user/proses.php?action=active",
        data: dataString,
        success: function (data) {
          // Autoreload jika akses ditolak
          if (
            typeof data === "string" &&
            data.indexOf("Akses ditolak") !== -1
          ) {
            swal({
              title: "Gagal!",
              text: data,
              icon: "error",
              timer: 1800,
            });
            setTimeout(function () {
              window.location.reload();
            }, 1800);
            return;
          }
          if (data === "success") {
            swal({
              title: "Berhasil!",
              text: isAktif
                ? "Siswa berhasil dinonaktifkan."
                : "Siswa berhasil diaktifkan.",
              icon: "success",
              timer: 1500,
            });
            loadData();
          } else {
            swal({ title: "Gagal!", text: data, icon: "error", timer: 2000 });
          }
        },
      });
    } else {
      // Kembalikan toggle ke posisi semula jika batal
      setTimeout(function () {
        loadData();
      }, 300);
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
      if (typeof data === "string" && data.indexOf("Akses ditolak") !== -1) {
        swal({
          title: "Gagal!",
          text: data,
          icon: "error",
          timer: 1800,
        });
        setTimeout(function () {
          window.location.reload();
        }, 1800);
        return;
      }
      if (typeof data === "string" && data.includes("success")) {
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
          if (
            typeof data === "string" &&
            data.indexOf("Akses ditolak") !== -1
          ) {
            swal({
              title: "Gagal!",
              text: data,
              icon: "error",
              timer: 1800,
            });
            setTimeout(function () {
              window.location.reload();
            }, 1800);
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

$(document).on("click", ".btn-export", function () {
  var kelas = $(".kelas").val();
  //var type = $("input:radio[name=type]:checked").val()
  var url = "./mod/user/print.php?kelas=" + kelas + "";
  window.open(url, "_blank");
});

// Reset Password Simple (Tanpa WhatsApp)
$(document).on("click", ".btn-reset-password-simple", function () {
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
        url: "./mod/user/proses.php?action=reset_password_simple",
        type: "POST",
        data: { id: id },
        success: function (data) {
          if (typeof data === "string" && data.trim() === "success") {
            swal({
              title: "Berhasil!",
              text: "Password " + name + " berhasil direset ke NISN!",
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
        closeModal: false,
      },
    },
    dangerMode: true,
  }).then((willDelete) => {
    if (willDelete) {
      loading();
      $.ajax({
        url: "./mod/user/proses.php?action=delete_all",
        type: "POST",
        success: function (data) {
          if (typeof data === "string" && data.trim() === "success") {
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
          swal({
            title: "Gagal!",
            text: "Terjadi kesalahan koneksi.",
            icon: "error",
            timer: 3000,
          });
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

// Handler untuk tombol sinkronisasi data dari Dapodik
$(document).on("click", ".btn-sync-integration", function () {
  swal({
    title: "Sinkronisasi Data Dapodik",
    text: "Anda yakin ingin menjalankan sinkronisasi data user dari Dapodik?\n\nProses ini akan:\n1. Menambahkan siswa baru dari data sync Dapodik\n2. Memperbarui data siswa yang sudah ada\n3. Menonaktifkan siswa yang tidak ada di data terbaru",
    icon: "info",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Ya, Jalankan Sinkronisasi",
        value: true,
        closeModal: false,
      },
    },
    dangerMode: false,
  }).then(function (willSync) {
    if (willSync) {
      // Tampilkan loading
      swal({
        title: "Sedang Memproses...",
        text: "Menjalankan sinkronisasi data dari Dapodik. Mohon tunggu...",
        icon: "info",
        buttons: false,
        closeOnClickOutside: false,
        closeOnEsc: false,
      });

      // Panggil endpoint integrasi
      $.ajax({
        url: "../api/integrate_user_sync.php",
        type: "POST",
        dataType: "json",
        timeout: 60000, // 60 detik timeout
        success: function (response) {
          if (response.success) {
            const stats = response.statistics || {};
            let message = `Sinkronisasi berhasil!\n\n`;
            message += `📊 STATISTIK:\n`;
            message += `• Total data sync: ${stats.total_sync_data || 0}\n`;
            message += `• User baru ditambahkan: ${
              stats.new_users_added || 0
            }\n`;
            message += `• User diperbarui: ${
              stats.existing_users_updated || 0
            }\n`;
            message += `• User dinonaktifkan: ${
              stats.users_deactivated || 0
            }\n`;

            if (stats.errors > 0) {
              message += `• Error: ${stats.errors}\n`;
            }

            message += `\n⏱️ Waktu eksekusi: ${
              response.execution_time || "N/A"
            }`;

            swal({
              title: "Sinkronisasi Berhasil!",
              text: message,
              icon: "success",
              button: "OK",
            }).then(function () {
              // Refresh tabel user
              if (typeof loadData === "function") {
                loadData();
              } else {
                location.reload();
              }
            });
          } else {
            swal({
              title: "Sinkronisasi Gagal",
              text:
                response.message ||
                "Terjadi kesalahan saat menjalankan sinkronisasi",
              icon: "error",
              button: "OK",
            });
          }
        },
        error: function (xhr, status, error) {
          let errorMessage =
            "Terjadi kesalahan saat menjalankan sinkronisasi:\n\n";

          if (status === "timeout") {
            errorMessage += "Timeout - Proses memakan waktu terlalu lama";
          } else if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage += xhr.responseJSON.message;
          } else {
            errorMessage += `${status}: ${error}`;
          }

          swal({
            title: "Error Sinkronisasi",
            text: errorMessage,
            icon: "error",
            button: "OK",
          });
        },
      });
    }
  });
});

// Upload Foto Berdasarkan NISN
$(document).on("submit", ".form-upload-foto-nisn", function (e) {
  e.preventDefault();
  var formData = new FormData(this);
  loading();
  $.ajax({
    url: "./mod/user/proses.php?action=upload_foto_nisn",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
      if (typeof data === "string" && data.trim() === "success") {
        swal({
          title: "Berhasil!",
          text: "Foto berhasil diupload!",
          icon: "success",
          timer: 2000,
        });
        $(".form-upload-foto-nisn").trigger("reset");
        loadData();
      } else {
        swal({ title: "Gagal!", text: data, icon: "error", timer: 2500 });
      }
    },
    complete: function () {
      $(".loading").hide();
    },
  });
});
/* ------- Reset Konfirmasi Data ------- */
$(document).on("click", ".btn-reset-konfirmasi", function () {
  var id = $(this).attr("data-id");
  var name = $(this).attr("data-name");
  swal({
    title: "Reset Konfirmasi Data!",
    text:
      "Anda yakin ingin mereset status konfirmasi data " +
      name +
      "?\nStatus akan menjadi 'Belum Konfirmasi'.",
    icon: "warning",
    buttons: {
      cancel: true,
      confirm: true,
    },
    value: "yes",
  }).then(function (value) {
    if (value) {
      loading();
      $.ajax({
        url: "./mod/user/proses.php?action=reset_konfirmasi",
        type: "POST",
        data: { id: id },
        success: function (data) {
          if (typeof data === "string" && data.trim() === "success") {
            swal({
              title: "Berhasil!",
              text: "Konfirmasi data berhasil direset.",
              icon: "success",
              timer: 2000,
            });
            loadData();
          } else {
            swal({ title: "Gagal!", text: data, icon: "error", timer: 2500 });
          }
        },
        error: function () {
          swal({
            title: "Gagal!",
            text: "Terjadi kesalahan koneksi.",
            icon: "error",
            timer: 2500,
          });
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
// Modal Search handling (moved from inline in user.php)
jQuery(function ($) {
  var $modal = $(".modal-search");
  // ketika pilihan tipe pencarian di-klik, update hidden input dan load pilihan
  function loadSearchOptions(type) {
    var $select = $modal.find(".search-select");
    $select.prop("disabled", true);
    $select.html("<option>Memuat daftar...</option>");
    $.post(
      "./mod/user/proses.php?action=search_options",
      { search_by: type },
      function (data) {
        $select.html(data);
        $select.prop("disabled", false);
      }
    ).fail(function () {
      $select.html('<option value="">Gagal memuat daftar</option>');
      $select.prop("disabled", false);
    });
  }

  // When category select changes, update hidden input and load values
  $modal.on("change", ".search-type-select", function () {
    var val = $(this).val();
    $modal.find(".search-by").val(val);
    loadSearchOptions(val);
  });

  // load default options when modal shown
  $modal.on("shown.bs.modal", function () {
    var defaultType = $modal.find(".search-type-select").val() || "nik_no_kk";
    $modal.find(".search-by").val(defaultType);
    loadSearchOptions(defaultType);
  });

  // submit form pencarian (AJAX)
  $modal.on("submit", ".form-search", function (e) {
    e.preventDefault();
    // read values from the modal container (search inputs are in a different form)
    var q = $modal.find(".search-select").val();
    var by = $modal.find(".search-by").val();
    if (!q || q === "") {
      // No selection made — clear previous results and do nothing
      $modal.find(".search-results").html("");
      return;
    }
    $modal
      .find(".search-results")
      .html('<div class="text-center py-2">Mencari...</div>');
    // request ke backend: mod/user/proses.php?action=search
    $.post(
      "./mod/user/proses.php?action=search",
      { query: q, search_by: by },
      function (data) {
        $modal.find(".search-results").html(data);
      }
    ).fail(function () {
      $modal
        .find(".search-results")
        .html('<div class="alert alert-danger">Gagal mencari data.</div>');
    });
  });
});

/**
 * Helper untuk membuka modal preview kartu pelajar
 *
 * FITUR:
 * - Tampil dalam modal Bootstrap 4 (tidak membuka tab baru)
 * - Preview kartu depan dan belakang
 * - Download terpisah untuk depan/belakang
 * - Loading state dan error handling
 * - Auto cleanup modal setelah ditutup
 */
function openKartuModal(user_id, nisn = null) {
  // debug logs removed

  // Buat URL dengan parameter modal
  let url = `./mod/user/download_kartu.php?modal=1&user_id=${encodeURIComponent(
    user_id
  )}`;
  if (nisn) {
    url += `&nisn=${encodeURIComponent(nisn)}`;
  }

  // Tambahkan parameter debug jika ada hash #debug di URL
  if (window.location.hash === "#debug") {
    url += "&debug=1";
  }

  // debug logs removed

  // Cek jika modal sudah ada, hapus dulu
  let existingModal = document.getElementById("kartuPreviewModal");
  if (existingModal) {
    $(existingModal).off("hidden.bs.modal");
    existingModal.remove();
    cleanupModalArtifacts();
  }

  // Buat modal element untuk Bootstrap 4
  const modalHtml = `
    <div class="modal fade" id="kartuPreviewModal" tabindex="-1" role="dialog" aria-labelledby="kartuPreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="kartuPreviewModalLabel">
              <i class="fas fa-id-card mr-2"></i>Preview Kartu Pelajar
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body text-center">
            <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;

  // debug logs removed
  // Tambahkan modal ke body
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  // Get modal element
  const modalElement = document.getElementById("kartuPreviewModal");
  modalElement.setAttribute("data-user-id", String(user_id || ""));
  modalElement.setAttribute("data-nisn", String(nisn || ""));
  // debug logs removed

  // Load konten via AJAX
  // debug logs removed
  $.get(url)
    .done(function (html) {
      // debug logs removed

      // Replace modal content
      const modalContent = modalElement.querySelector(".modal-content");
      modalContent.innerHTML = `
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-id-card mr-2"></i>Preview Kartu Pelajar
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        ${html}
      `;

      // debug logs removed

      // Show modal menggunakan Bootstrap 4
      $(modalElement).modal("show");

      // Add event handlers for download buttons after modal content loaded
      setupModalDownloadHandlers(modalElement);
      // debug logs removed
    })
    .fail(function (xhr, status, error) {
      // debug logs removed

      const modalBody = modalElement.querySelector(".modal-body");
      modalBody.innerHTML = `
        <div class="text-center text-danger">
          <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
          <h5>Gagal Memuat Preview</h5>
          <p>Terjadi kesalahan saat memuat preview kartu pelajar.</p>
          <p class="small text-muted">Error: ${error} (Status: ${xhr.status})</p>
          <p class="small text-muted">URL: ${url}</p>
        </div>
      `;
      $(modalElement).modal("show");
    });

  // Clean up modal when hidden
  $(modalElement).on("hidden.bs.modal", function () {
    // debug logs removed
    modalElement.remove();
    cleanupModalArtifacts();
  });

  return modalElement;
}

// Setup download handlers for modal buttons
function setupModalDownloadHandlers(modalElement) {
  // debug logs removed

  const btnDepan = modalElement.querySelector("#btn-download-depan-modal");
  const btnBelakang = modalElement.querySelector(
    "#btn-download-belakang-modal"
  );
  const kartuDepan = modalElement.querySelector("#kartu-depan-modal");
  const kartuBelakang = modalElement.querySelector("#kartu-belakang-modal");
  const userId = (modalElement.getAttribute("data-user-id") || "").trim();

  // debug logs removed

  if (btnDepan && userId !== "") {
    btnDepan.addEventListener("click", function () {
      const originalHtml = btnDepan.innerHTML;
      btnDepan.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyiapkan...';
      btnDepan.disabled = true;

      const url =
        "./mod/user/download_kartu.php?user_id=" +
        encodeURIComponent(userId) +
        "&side=depan&t=" +
        Date.now();
      const link = document.createElement("a");
      link.href = url;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      setTimeout(function () {
        btnDepan.innerHTML = originalHtml;
        btnDepan.disabled = false;
      }, 600);
    });
  }

  if (btnBelakang && userId !== "") {
    btnBelakang.addEventListener("click", function () {
      const originalHtml = btnBelakang.innerHTML;
      btnBelakang.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyiapkan...';
      btnBelakang.disabled = true;

      const url =
        "./mod/user/download_kartu.php?user_id=" +
        encodeURIComponent(userId) +
        "&side=belakang&t=" +
        Date.now();
      const link = document.createElement("a");
      link.href = url;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      setTimeout(function () {
        btnBelakang.innerHTML = originalHtml;
        btnBelakang.disabled = false;
      }, 600);
    });
  }
}

// Reset Password User dengan WhatsApp
$(document).on("click", ".btn-reset-password-wa", function () {
  var id = $(this).attr("data-id");
  var name = $(this).attr("data-name");
  swal({
    title: "Reset Password + WhatsApp?",
    text: "Password " + name + " akan direset ke NISN dan informasi akan dikirim via WhatsApp (jika nomor terverifikasi).",
    icon: "warning",
    buttons: {
      cancel: {
        text: "Batal",
        value: null,
        visible: true,
        className: "",
        closeModal: true,
      },
      confirm: {
        text: "Reset + WhatsApp",
        value: true,
        visible: true,
        className: "btn-danger",
        closeModal: true
      }
    },
    dangerMode: true,
  }).then((willReset) => {
    if (willReset) {
      loading();
      $.ajax({
        url: "./mod/user/proses.php?action=reset_password",
        type: "POST",
        data: { id: id },
        success: function (data) {
          // Autoreload jika akses ditolak
          if (
            typeof data === "string" &&
            data.indexOf("Akses ditolak") !== -1
          ) {
            swal({ title: "Gagal!", text: data, icon: "error", timer: 1800 });
            setTimeout(function () {
              window.location.reload();
            }, 1800);
            return;
          }
          
          if (typeof data === "string" && data.trim() === "success_with_wa") {
            swal({
              title: "Berhasil!",
              text: "Password " + name + " berhasil direset dan informasi telah dikirim via WhatsApp.",
              icon: "success",
              timer: 3000,
            });
            loadData();
          } else if (typeof data === "string" && data.trim() === "success_no_wa") {
            swal({
              title: "Berhasil!",
              text: "Password " + name + " berhasil direset. WhatsApp tidak terkirim (nomor tidak ada/tidak terverifikasi).",
              icon: "warning",
              timer: 3000,
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
