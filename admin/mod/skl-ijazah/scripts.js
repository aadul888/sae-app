"use strict";

$(function () {

  // === Select2 untuk dropdown murid ===
  if ($.fn.select2 && $("#select-murid-ijazah").length) {
    $("#select-murid-ijazah").select2({
      placeholder: "-- Cari NISN atau Nama Murid --",
      allowClear: true,
      width: "100%"
    });
  }

  // === Drag & Drop handler ===
  function initDropzone(dropzoneId, inputId, multiple) {
    var $zone = $("#" + dropzoneId);
    var $input = $("#" + inputId);
    if (!$zone.length || !$input.length) return;

    function setFiles(files) {
      if (!files || !files.length) return;
      var dt = new DataTransfer();
      for (var i = 0; i < files.length; i++) dt.items.add(files[i]);
      $input[0].files = dt.files;
      $zone.addClass("has-file").removeClass("dragover");
      if (files.length === 1) {
        $zone.find(".skl-dropzone-filename").text(files[0].name);
      } else {
        $zone.find(".skl-dropzone-filename").text(files.length + " file dipilih");
      }
    }

    $zone.on("click", function () { $input.trigger("click"); });
    $input.on("change", function () {
      if (this.files && this.files.length) setFiles(this.files);
    });
    $zone.on("dragover dragenter", function (e) {
      e.preventDefault(); e.stopPropagation();
      $zone.addClass("dragover");
    });
    $zone.on("dragleave dragend", function (e) {
      e.preventDefault(); e.stopPropagation();
      $zone.removeClass("dragover");
    });
    $zone.on("drop", function (e) {
      e.preventDefault(); e.stopPropagation();
      var files = e.originalEvent.dataTransfer.files;
      if (files && files.length) setFiles(files);
    });
  }

  initDropzone("dropzone-single-ijazah", "ijazah_file_input");
  initDropzone("dropzone-bulk-ijazah", "zip_ijazah_input");

  // === DataTable + filter kelas & status ijazah ===
  var ijazahTable = null;
  if ($.fn.DataTable && $("#table-ijazah-monitor").length) {
    ijazahTable = $("#table-ijazah-monitor").DataTable({ pageLength: 25, scrollX: true, scrollCollapse: true, responsive: false });

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
      if (settings.nTable !== document.getElementById("table-ijazah-monitor")) return true;
      var filterKelas = $("#filter-kelas-ijazah").val();
      if (filterKelas && data[3] !== filterKelas) return false;
      var row = settings.aoData[dataIndex].nTr;
      var filterStatus = $("#filter-status-ijazah").val();
      if (filterStatus) {
        if (!row || $(row).data("ijazah-status") !== filterStatus) return false;
      }
      var filterKonfirmasi = $("#filter-konfirmasi-ijazah").val();
      if (filterKonfirmasi) {
        if (!row || $(row).data("konfirmasi-status") !== filterKonfirmasi) return false;
      }
      return true;
    });

    $("#filter-kelas-ijazah, #filter-status-ijazah, #filter-konfirmasi-ijazah").on("change", function () {
      ijazahTable.draw();
    });
  }

  // Filter modal handlers
  $(document).on('click', '.btn-open-filter-ijazah', function () {
    var $modal = $('.modal-filter-ijazah');
    if (!$modal.length) return;
    $modal.modal({ backdrop: false, keyboard: true, show: true });
    setTimeout(function () {
      $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
      $('.modal-backdrop').remove();
    }, 30);
  });
  $(document).on('click', '.btn-apply-filter-ijazah', function () {
    $('.modal-filter-ijazah').modal('hide');
    if (ijazahTable) ijazahTable.draw();
  });
  $(document).on('click', '.btn-reset-filter-ijazah', function () {
    $('#filter-kelas-ijazah').val('');
    $('#filter-status-ijazah').val('');
    $('#filter-konfirmasi-ijazah').val('');
    $('.modal-filter-ijazah').modal('hide');
    if (ijazahTable) ijazahTable.draw();
  });
  $(document).on('hidden.bs.modal', '.modal-filter-ijazah', function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  });

  // === Upload per murid ===
  $("#form-upload-single-ijazah").on("submit", function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
      url: IJAZAH_BASE + "proses.php?action=upload",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        if (res.success) {
          swal({ title: "Berhasil", text: res.message, icon: "success" }).then(function () {
            location.reload();
          });
        } else {
          swal({ title: "Gagal", text: res.message, icon: "error" });
        }
      },
      error: function () {
        swal({ title: "Error", text: "Terjadi kesalahan server.", icon: "error" });
      }
    });
  });

  // === Upload batch (ZIP) ===
  $("#form-upload-batch-ijazah").on("submit", function (e) {
    e.preventDefault();
    if (!$("#zip_ijazah_input")[0].files.length) {
      swal({ title: "Perhatian", text: "Pilih file ZIP terlebih dahulu.", icon: "warning" });
      return;
    }

    var formData = new FormData(this);
    var xhr = new XMLHttpRequest();

    // Show progress modal
    $("#modalUploadProgress").modal("show");
    $("#upload-percent").text("0%");
    $("#upload-progress-bar").css("width", "0%").attr("aria-valuenow", 0).text("0%");
    $("#upload-status").text("Memulai upload...");

    // Progress handler
    xhr.upload.addEventListener("progress", function (e) {
      if (e.lengthComputable) {
        var percent = Math.round((e.loaded / e.total) * 100);
        $("#upload-percent").text(percent + "%");
        $("#upload-progress-bar").css("width", percent + "%").attr("aria-valuenow", percent).text(percent + "%");
        $("#upload-status").text("Mengupload: " + (e.loaded / 1024 / 1024).toFixed(2) + " MB / " + (e.total / 1024 / 1024).toFixed(2) + " MB");
      }
    });

    // Load handler
    xhr.addEventListener("load", function () {
      if (xhr.status === 200) {
        try {
          var res = JSON.parse(xhr.responseText);
          $("#modalUploadProgress").modal("hide");
          if (res.success) {
            swal({
              title: "Upload Selesai",
              text: "Berhasil: " + res.sukses + " file, Gagal/Dilewati: " + res.gagal + " file.",
              icon: res.gagal > 0 ? "warning" : "success"
            }).then(function () {
              location.reload();
            });
          } else {
            swal({ title: "Gagal", text: res.message, icon: "error" });
          }
        } catch (err) {
          $("#modalUploadProgress").modal("hide");
          swal({ title: "Error", text: "Response tidak valid dari server.", icon: "error" });
        }
      } else {
        $("#modalUploadProgress").modal("hide");
        swal({ title: "Error", text: "HTTP error: " + xhr.status, icon: "error" });
      }
    });

    // Error handler
    xhr.addEventListener("error", function () {
      $("#modalUploadProgress").modal("hide");
      swal({ title: "Error", text: "Terjadi kesalahan saat upload.", icon: "error" });
    });

    // Abort handler
    xhr.addEventListener("abort", function () {
      $("#modalUploadProgress").modal("hide");
      swal({ title: "Dibatalkan", text: "Upload dibatalkan.", icon: "info" });
    });

    xhr.open("POST", IJAZAH_BASE + "proses.php?action=upload-batch", true);
    if (window.CSRF_TOKEN) {
      xhr.setRequestHeader("Csrf-Token", window.CSRF_TOKEN);
    }
    xhr.send(formData);
  });

  // === Preview Ijazah PDF ===
  $(document).on("click", ".btn-preview-ijazah", function () {
    var uid  = $(this).data("uid");
    var nama = $(this).data("nama");
    $("#preview-ijazah-nama").text(nama);
    $("#modalPreviewIjazahBody").html(
      '<iframe src="' + IJAZAH_BASE + 'proses.php?action=preview&uid=' + encodeURIComponent(uid) + '" style="width:100%;height:80vh;border:none;" frameborder="0"></iframe>'
    );
    $("#modalPreviewIjazah").modal("show");
  });

  $("#modalPreviewIjazah").on("hidden.bs.modal", function () {
    $("#modalPreviewIjazahBody").html("");
  });

  // === Hapus ijazah ===
  $(document).on("click", ".btn-hapus-ijazah", function () {
    var uid = $(this).data("id");
    var nama = $(this).data("nama");
    swal({
      title: "Hapus Ijazah?",
      text: "Ijazah milik " + nama + " akan dihapus permanen.",
      icon: "warning",
      buttons: ["Batal", "Ya, Hapus"],
      dangerMode: true
    }).then(function (ok) {
      if (!ok) return;
      $.ajax({
        url: IJAZAH_BASE + "proses.php?action=hapus",
        type: "POST",
        data: { user_id: uid },
        success: function (res) {
          if (res.success) {
            swal({ title: "Berhasil", text: res.message, icon: "success" }).then(function () { location.reload(); });
          } else {
            swal({ title: "Gagal", text: res.message, icon: "error" });
          }
        },
        error: function () {
          swal({ title: "Error", text: "Terjadi kesalahan server.", icon: "error" });
        }
      });
    });
  });

  // === Lihat catatan kesalahan ===
  $(document).on("click", ".btn-lihat-catatan", function () {
    var nama = $(this).data("nama");
    var catatan = $(this).data("catatan");
    $("#catatan-nama").text(nama);
    $("#catatan-isi").text(catatan);
    $("#modalCatatanKesalahan").modal("show");
  });

});
