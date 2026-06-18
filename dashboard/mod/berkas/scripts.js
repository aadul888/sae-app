"use strict";

$(document).ready(function () {
  // Check if SweetAlert is available
  if (typeof swal === "undefined") {
    console.error(
      "SweetAlert library is not loaded. Please check footer inclusion."
    );
    return;
  }

  // File preview handler
  $(".berkas-file-input").on("change", function () {
    const input = this;
    const fileType = input.name;
    const previewDiv = $("#preview-" + fileType);

    // Clear previous preview
    previewDiv.empty();

    if (input.files && input.files[0]) {
      const file = input.files[0];
      const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB

      // Validate file size (10MB)
      if (file.size > 10 * 1024 * 1024) {
        swal({
          title: "File Terlalu Besar",
          text: `File ${fileType.toUpperCase()} melebihi 10MB. Ukuran: ${fileSize}MB`,
          type: "error",
          confirmButtonText: "OK",
        });
        input.value = "";
        return;
      }

      // Validate file type
      const allowedTypes = [
        "image/jpeg",
        "image/jpg",
        "image/png",
        "application/pdf",
      ];
      if (!allowedTypes.includes(file.type)) {
        swal({
          title: "Tipe File Tidak Didukung",
          text: "Hanya file JPG, PNG, atau PDF yang diizinkan.",
          type: "error",
          confirmButtonText: "OK",
        });
        input.value = "";
        return;
      }

      // Show file preview
      const reader = new FileReader();
      reader.onload = function (e) {
        let previewContent = "";

        if (file.type.startsWith("image/")) {
          previewContent = `
            <div class="berkas-preview-item">
              <img src="${e.target.result}" alt="Preview ${fileType}" class="berkas-preview-img">
              <div class="berkas-preview-info">
                <small><strong>${file.name}</strong></small><br>
                <small>Ukuran: ${fileSize} MB</small>
              </div>
            </div>
          `;
        } else if (file.type === "application/pdf") {
          previewContent = `
            <div class="berkas-preview-item">
              <div class="berkas-preview-pdf">
                <i class="fas fa-file-pdf fa-3x text-danger"></i>
              </div>
              <div class="berkas-preview-info">
                <small><strong>${file.name}</strong></small><br>
                <small>Ukuran: ${fileSize} MB</small>
              </div>
            </div>
          `;
        }

        previewDiv.html(previewContent);
      };
      reader.readAsDataURL(file);
    }
  });

  // Clear preview when form is reset
  $("form").on("reset", function () {
    $(".berkas-preview").empty();
  });

  // Thumbnail click -> open preview modal (image or pdf)
  $(document).on("click", ".berkas-thumb-link", function (e) {
    e.preventDefault();
    const url = $(this).data("file-url");
    const fileExt = url.split(".").pop().toLowerCase();
    const modal = $("#modalPreviewBerkas");
    const container = $("#previewContainer");
    container.empty();

    if (["jpg", "jpeg", "png", "gif"].includes(fileExt)) {
      const img = $("<img>").attr("src", url).attr("alt", "Preview image");
      container.append(img);

      // simple zoom: click to toggle scale
      let zoomed = false;
      img.on("click", function () {
        if (!zoomed) {
          $(this).css("transform", "scale(2)");
          $(this).css("cursor", "zoom-out");
          zoomed = true;
        } else {
          $(this).css("transform", "scale(1)");
          $(this).css("cursor", "zoom-in");
          zoomed = false;
        }
      });

      // mouse wheel zoom
      img.on("wheel", function (ev) {
        ev.preventDefault();
        const delta = ev.originalEvent.deltaY;
        const current = $(this).css("transform");
        // keep toggle behavior simple: toggle on wheel
        if (delta < 0) $(this).css("transform", "scale(2)");
        else $(this).css("transform", "scale(1)");
      });
    } else if (fileExt === "pdf") {
      const iframe = $("<iframe>").addClass("pdf-iframe").attr("src", url);
      container.append(iframe);
    } else {
      // fallback to download/open in new tab
      window.open(url, "_blank");
      return;
    }

    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
      var modalInstance = new bootstrap.Modal(modal[0]);
      modalInstance.show();
    } else {
      modal.modal("show");
    }
  });

  // Upload single file (per-field)
  $(document).on("click", ".berkas-upload-one", function (e) {
    e.preventDefault();
    const field = $(this).data("field");
    const input = $("#" + field)[0];

    if (!input || !input.files || input.files.length === 0) {
      swal({
        title: "Tidak Ada File",
        text: "Pilih file pada input terlebih dahulu untuk " + field,
        type: "warning",
      });
      return;
    }

    const file = input.files[0];
    const formData = new FormData();
    formData.append(field, file);

    swal({
      title: "Mengupload...",
      text: "Mohon tunggu, sedang memproses upload.",
      type: "info",
      showConfirmButton: false,
      allowOutsideClick: false,
    });

    $.ajax({
      url: "mod/berkas/proses.php?action=add",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        const clean = response.trim();
        if (clean === "success") {
          swal({
            title: "Upload Berhasil",
            text: "File " + field + " berhasil diupload.",
            type: "success",
          }).then(() => location.reload());
        } else {
          swal({
            title: "Upload Gagal",
            text: clean || "Terjadi kesalahan",
            type: "error",
          });
        }
      },
      error: function (xhr, status) {
        swal({
          title: "Error",
          text: xhr.responseText || status,
          type: "error",
        });
      },
    });
  });

  // Delete single file (per-field)
  $(document).on("click", ".berkas-delete-one", function (e) {
    e.preventDefault();
    const field = $(this).data("field");

    swal({
      title: "Hapus berkas " + field + "?",
      text: "File akan dihapus permanen.",
      type: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (confirm) {
      if (!confirm) return;
      swal({
        title: "Menghapus...",
        text: "Mohon tunggu",
        type: "info",
        showConfirmButton: false,
      });
      $.ajax({
        url: "mod/berkas/proses.php?action=delete_one",
        type: "POST",
        data: { field: field },
        success: function (response) {
          const clean = response.trim();
          if (clean === "success") {
            swal({
              title: "Dihapus",
              text: "File " + field + " berhasil dihapus.",
              type: "success",
            }).then(() => location.reload());
          } else {
            swal({
              title: "Gagal",
              text: clean || "Terjadi kesalahan",
              type: "error",
            });
          }
        },
        error: function (xhr, status) {
          swal({
            title: "Error",
            text: xhr.responseText || status,
            type: "error",
          });
        },
      });
    });
  });
});
