"use strict";

$(function () {

  // === Select2 untuk dropdown murid ===
  if ($.fn.select2 && $("#select-murid-skl").length) {
    $("#select-murid-skl").select2({
      placeholder: "-- Cari NISN atau Nama Murid --",
      allowClear: true,
      width: "100%"
    });
  }

  // === Drag & Drop handler ===
  function initDropzone(dropzoneId, inputId) {
    var $zone = $("#" + dropzoneId);
    var $input = $("#" + inputId);
    if (!$zone.length || !$input.length) return;

    function setFile(file) {
      var dt = new DataTransfer();
      dt.items.add(file);
      $input[0].files = dt.files;
      $zone.addClass("has-file").removeClass("dragover");
      $zone.find(".skl-dropzone-filename").text(file.name);
    }

    $zone.on("click", function () { $input.trigger("click"); });
    $input.on("change", function () {
      if (this.files && this.files[0]) setFile(this.files[0]);
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
      var file = e.originalEvent.dataTransfer.files[0];
      if (file) setFile(file);
    });
  }

  initDropzone("dropzone-single", "skl_file_input");
  initDropzone("dropzone-bulk", "zip_file_input");

  // === DataTable + filter kelas & status SKL ===
  var sklTable = null;
  if ($.fn.DataTable && $("#table-skl-monitor").length) {
    sklTable = $("#table-skl-monitor").DataTable({ pageLength: 25, scrollX: true, scrollCollapse: true, responsive: false });

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
      if (settings.nTable !== document.getElementById("table-skl-monitor")) return true;
      var filterKelas = $("#filter-kelas-skl").val();
      if (filterKelas && data[3] !== filterKelas) return false;
      var filterSkl = $("#filter-status-skl").val();
      if (filterSkl) {
        var row = settings.aoData[dataIndex].nTr;
        if (!row || $(row).data("skl-status") !== filterSkl) return false;
      }
      return true;
    });

    $("#filter-kelas-skl, #filter-status-skl").on("change", function () {
      sklTable.draw();
    });
  }

  // Filter modal handlers
  $(document).on('click', '.btn-open-filter-skl-monitor', function () {
    var $modal = $('.modal-filter-skl-monitor');
    if (!$modal.length) return;
    $modal.modal({ backdrop: false, keyboard: true, show: true });
    setTimeout(function () {
      $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
      $('.modal-backdrop').remove();
    }, 30);
  });
  $(document).on('click', '.btn-apply-filter-skl-monitor', function () {
    $('.modal-filter-skl-monitor').modal('hide');
    if (sklTable) sklTable.draw();
  });
  $(document).on('click', '.btn-reset-filter-skl-monitor', function () {
    $('#filter-kelas-skl').val('');
    $('#filter-status-skl').val('');
    $('.modal-filter-skl-monitor').modal('hide');
    if (sklTable) sklTable.draw();
  });
  $(document).on('hidden.bs.modal', '.modal-filter-skl-monitor', function () {
    $('body').removeClass('modal-open').css({ 'padding-right': '', overflow: '' });
    $('.modal-backdrop').remove();
  });

  // === Upload SKL per murid ===
  $("#form-import-single").on("submit", function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
      url: "./mod/skl-import/proses.php?action=single",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        if ($.trim(res) === "success") {
          swal({ title: "Berhasil", text: "SKL berhasil diupload.", icon: "success" }).then(function () {
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

  // === Import masal via ZIP ===
  $("#form-import-bulk").on("submit", function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
      url: "./mod/skl-import/proses.php?action=bulk",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        var parts = $.trim(res).split("|");
        if (parts[0] === "success") {
          swal({
            title: "Import Selesai",
            text: "Berhasil: " + (parts[1] || 0) + " data, Gagal: " + (parts[2] || 0) + " data, Dilewati: " + (parts[3] || 0) + " file non-PDF.",
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

  // === Preview SKL PDF ===
  $(document).on("click", ".btn-lihat-skl", function () {
    var filename = $(this).data("filename");
    if (!filename) return;
    var url = "../content/skl/" + encodeURIComponent(filename);
    $("#modalPreviewSKLLabel").text("Preview SKL � " + filename);
    $("#modalPreviewSKLBody").html(
      '<iframe src="' + url + '" style="width:100%;height:80vh;border:none;" frameborder="0"></iframe>'
    );
    $("#modalPreviewSKL").modal("show");
  });

  $("#modalPreviewSKL").on("hidden.bs.modal", function () {
    $("#modalPreviewSKLBody").html("");
  });

});