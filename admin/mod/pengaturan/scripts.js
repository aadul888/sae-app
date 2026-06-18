/** Test Koneksi langsung dari tabel (tanpa isi form) */
$(document).on("click", ".btn-test-dapodik", function () {
  var id = $(this).data("id");
  var $row = $(this).closest("tr");
  var $statusCell = $row.find("td").eq(5); // kolom status
  swal({
    title: "Test Koneksi",
    text: "Menguji koneksi ke server Dapodik...",
    icon: "info",
    buttons: false,
    closeOnClickOutside: false,
    closeOnEsc: false,
  });
  $.ajax({
    url: "mod/pengaturan/proses.php?action=dapodik-test-connection",
    type: "POST",
    data: { id: id },
    dataType: "json",
    success: function (res) {
      if (res.status === "berhasil") {
        $statusCell.html('<span class="badge badge-success">Berhasil</span>');
        swal({
          title: "Berhasil",
          text: res.message,
          icon: "success",
          timer: 3000,
        });
      } else {
        $statusCell.html('<span class="badge badge-danger">Gagal</span>');
        swal({
          title: "Gagal",
          text: res.message,
          icon: "error",
          timer: 4000,
        });
      }
    },
    error: function (xhr) {
      $statusCell.html('<span class="badge badge-danger">Gagal</span>');
      swal({
        title: "Error!",
        text: xhr.statusText,
        icon: "error",
        timer: 2500,
      });
    },
  });
});
/** Toggle Password Visibility */
function togglePassword(button) {
  const input = button.parentElement.parentElement.querySelector("input");
  const icon = button.querySelector("i");

  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

/** Test Email Function */
function testEmail() {
  const emailData = {
    username: $('input[name="gmail_username"]').val(),
    password: $('input[name="gmail_password"]').val(),
    host: $('input[name="gmail_host"]').val(),
    port: $('input[name="gmail_port"]').val(),
  };

  if (
    !emailData.username ||
    !emailData.password ||
    !emailData.host ||
    !emailData.port
  ) {
    swal("Error!", "Lengkapi semua field email terlebih dahulu", "error");
    return;
  }

  swal("Test Email", "Mengirim email test...", "info");

  // Simulate email test (replace with actual AJAX call)
  setTimeout(function () {
    swal("Berhasil!", "Email test berhasil dikirim", "success");
  }, 2000);
}

/** Backup Files Function */
function backupFiles() {
  const selectedFolders = $(".backup-asset-folder:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  if (!selectedFolders.length) {
    swal("Validasi", "Pilih minimal satu folder aset untuk di-backup.", "warning");
    return;
  }

  const mode = $('input[name="backup_mode"]:checked').val() || "per-folder";

  swal({
    title: "Mulai Backup Aset",
    text:
      "Sistem akan scan aset dan membuat file ZIP " +
      (mode === "single" ? "gabungan." : "per folder.") +
      " Proses akan otomatis mengunduh saat selesai.",
    icon: "info",
    buttons: ["Batal", "Mulai"],
    dangerMode: false,
  }).then((willBackup) => {
    if (!willBackup) {
      return;
    }

    runAssetBackupFlow(selectedFolders, mode);
  });
}

function formatBytes(bytes) {
  const value = Number(bytes) || 0;
  if (value <= 0) return "0 B";
  const units = ["B", "KB", "MB", "GB", "TB"];
  const idx = Math.min(units.length - 1, Math.floor(Math.log(value) / Math.log(1024)));
  const num = value / Math.pow(1024, idx);
  return (idx === 0 ? Math.round(num) : num.toFixed(2)) + " " + units[idx];
}

function triggerDownload(url) {
  const link = document.createElement("a");
  link.href = url;
  link.target = "_blank";
  link.rel = "noopener";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function runAssetBackupFlow(selectedFolders, mode) {
  swal({
    title: "Menyiapkan Backup",
    text: "Memindai folder aset terpilih...",
    icon: "info",
    buttons: false,
    closeOnClickOutside: false,
    closeOnEsc: false,
  });

  $.ajax({
    url: "mod/pengaturan/proses.php?action=backup-assets-scan",
    type: "POST",
    dataType: "json",
    data: { folders: selectedFolders },
    success: function (scanRes) {
      if (!scanRes || scanRes.status !== "success") {
        swal("Gagal", (scanRes && scanRes.message) || "Gagal memindai aset backup.", "error");
        return;
      }

      const scanData = scanRes.data || {};
      const byFolder = scanData.by_folder || {};
      const totalFiles = Number(scanData.total_files) || 0;
      const totalSize = Number(scanData.total_size) || 0;

      if (totalFiles === 0) {
        swal("Info", "Tidak ada file di folder aset terpilih.", "info");
        return;
      }

      const folderQueue = scanData.folders || selectedFolders;

      if (mode === "single") {
        swal({
          title: "Membuat ZIP Gabungan",
          text:
            "Memproses " +
            totalFiles +
            " file (" +
            formatBytes(totalSize) +
            ") menjadi satu file ZIP...",
          icon: "info",
          buttons: false,
          closeOnClickOutside: false,
          closeOnEsc: false,
        });

        $.ajax({
          url: "mod/pengaturan/proses.php?action=backup-assets-create",
          type: "POST",
          dataType: "json",
          data: {
            mode: "single",
            folders: folderQueue,
          },
          success: function (createRes) {
            if (!createRes || createRes.status !== "success") {
              swal("Gagal", (createRes && createRes.message) || "Gagal membuat ZIP backup.", "error");
              return;
            }

            const data = createRes.data || {};
            if (data.download_url) {
              triggerDownload(data.download_url);
            }

            swal("Berhasil", "ZIP backup selesai dibuat dan unduhan dimulai.", "success");
          },
          error: function (xhr) {
            swal("Error", xhr.statusText || "Gagal membuat ZIP backup.", "error");
          },
        });
        return;
      }

      let done = 0;
      const total = folderQueue.length;

      const processNext = function () {
        if (done >= total) {
          swal("Berhasil", "Semua ZIP aset berhasil dibuat dan unduhan dimulai.", "success");
          return;
        }

        const folder = folderQueue[done];
        const stat = byFolder[folder] || {};
        swal({
          title: "Membuat ZIP Aset",
          text:
            "Folder " +
            (stat.label || folder) +
            " (" +
            (Number(stat.file_count) || 0) +
            " file, " +
            formatBytes(Number(stat.total_size) || 0) +
            ")\nProgress: " +
            (done + 1) +
            "/" +
            total,
          icon: "info",
          buttons: false,
          closeOnClickOutside: false,
          closeOnEsc: false,
        });

        $.ajax({
          url: "mod/pengaturan/proses.php?action=backup-assets-create",
          type: "POST",
          dataType: "json",
          data: {
            mode: "per-folder",
            folder: folder,
            folders: folderQueue,
          },
          success: function (createRes) {
            if (!createRes || createRes.status !== "success") {
              swal("Gagal", (createRes && createRes.message) || "Gagal membuat ZIP aset.", "error");
              return;
            }

            const data = createRes.data || {};
            if (data.download_url) {
              triggerDownload(data.download_url);
            }

            done += 1;
            processNext();
          },
          error: function (xhr) {
            swal("Error", xhr.statusText || "Gagal membuat ZIP aset.", "error");
          },
        });
      };

      processNext();
    },
    error: function (xhr) {
      swal("Error", xhr.statusText || "Gagal memindai aset backup.", "error");
    },
  });
}

function updateBackupAssetSummary() {
  const total = $(".backup-asset-folder").length;
  const selected = $(".backup-asset-folder:checked").length;
  const mode = $('input[name="backup_mode"]:checked').val() || "per-folder";
  const modeText = mode === "single" ? "1 ZIP" : "ZIP per folder";
  const $summary = $("#backupAssetSummary");
  if ($summary.length) {
    $summary.text("Aset terpilih: " + selected + "/" + total + " folder | Mode: " + modeText);
  }
}

$(document).on("change", ".backup-asset-folder, input[name='backup_mode']", function () {
  updateBackupAssetSummary();
});

/** Custom File Input */
$(document).on("change", ".custom-file-input", function () {
  const fileName = $(this).val().split("\\").pop();
  $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});

/** Edit Modul Handler */
$(document).on("click", ".btn-edit-modul", function () {
  const id = $(this).data("id");
  const nama = $(this).data("nama");

  $("#modul_id").val(id);
  $("#modul_nama").val(nama);

  // Scroll to form
  $("html, body").animate(
    {
      scrollTop: $(".form-modul").offset().top - 100,
    },
    500
  );

  // Highlight form
  $(".form-modul").addClass("highlight-form");
  setTimeout(function () {
    $(".form-modul").removeClass("highlight-form");
  }, 2000);
});

/** Delete Modul Handler */
$(document).on("click", ".btn-delete-modul", function () {
  var id = $(this).data("id");
  if (!id) return;
  swal({
    title: "Hapus Modul?",
    text: "Modul akan dihapus dan tidak dapat dikembalikan.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Hapus",
        value: true,
        visible: true,
        className: "",
        closeModal: true,
      },
    },
    dangerMode: true,
  }).then((willDelete) => {
    if (willDelete) {
      $.ajax({
        url: "mod/pengaturan/proses.php?action=modul-delete",
        type: "POST",
        data: { id: id },
        success: function (data) {
          var trimmed = (data || "").toString().trim();
          if (trimmed === "success") {
            swal({ title: "Berhasil", text: "Modul berhasil dihapus.", icon: "success", timer: 1500 }).then(function () {
              // reload module list (page 1)
              loadSetting(2, 1); // tab 2 is Modul
            });
          } else {
            swal({ title: "Gagal", text: trimmed || "Gagal menghapus modul.", icon: "error", timer: 2500 });
          }
        },
        error: function (xhr) {
          swal({ title: "Error", text: xhr.statusText || "Terjadi kesalahan.", icon: "error", timer: 2500 });
        },
      });
    }
  });
});

/** Restore Form Handler */
$(document).on("submit", "#restoreForm", function (e) {
  e.preventDefault();

  const fileInput = $("#backupFile")[0];
  if (!fileInput.files.length) {
    swal("Error!", "Pilih file backup terlebih dahulu", "error");
    return;
  }

  swal({
    title: "Restore Database?",
    text: "Operasi ini akan menimpa data yang ada. Pastikan Anda sudah melakukan backup!",
    icon: "warning",
    buttons: ["Batal", "Ya, Restore"],
    dangerMode: true,
  }).then((willRestore) => {
    if (willRestore) {
      const formData = new FormData(this);

      swal("Processing...", "Melakukan restore database...", "info");

      // Simulate restore process
      setTimeout(function () {
        swal("Berhasil!", "Database berhasil di-restore", "success");
      }, 3000);

      // In real implementation:
      /*
      $.ajax({
        url: 'mod/pengaturan/proses.php?action=restore-database',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          if (response.status === 'success') {
            swal("Berhasil!", response.message, "success");
          } else {
            swal("Error!", response.message, "error");
          }
        }
      });
      */
    }
  });
});

/** Load Setting Function Enhancement (supports optional page param) */
function loadSetting(settingId, page) {
  $(".load-form").html(
    '<div class="text-center py-5"><div class="spinner-border text-primary"><span class="sr-only">Loading...</span></div><p class="text-muted mt-3">Memuat pengaturan...</p></div>'
  );

  // Update active tab - find by onclick attribute to avoid index mismatch
  $(".nav-pills .nav-link").removeClass("active");
  $(".nav-pills .nav-link").filter(function() {
    return $(this).attr('onclick') === 'loadSetting(' + settingId + ');';
  }).addClass("active");

  // Build URL with optional page
  var url = "mod/pengaturan/form.php?id=" + settingId;
  if (typeof page !== 'undefined' && page !== null) {
    url += '&page=' + parseInt(page);
  }

  // Load form content
  setTimeout(function () {
    $.ajax({
      url: url,
      type: "GET",
      success: function (response) {
        $(".load-form").html(response);

        // Initialize any plugins needed
        if (settingId === 1) {
          // Initialize file upload previews for logo forms
          initializeFileUploads();
        } else if (settingId === 4) {
          updateBackupAssetSummary();
        }
      },
      error: function () {
        $(".load-form").html(
          '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat pengaturan</div>'
        );
      },
    });
  }, 500);
}
 
// Initial load: show settings page 1
loadSetting(1, 1);

/** Initialize File Upload Previews */
function initializeFileUploads() {
  $(".upload").on("change", function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      const imgElement = $(this).closest(".card").find("img");

      reader.onload = function (e) {
        imgElement.attr("src", e.target.result);
      };

      reader.readAsDataURL(file);
    }
  });
}

/** Enhanced Form Submission */
$(document).on("submit", ".form-modul", function (e) {
  e.preventDefault();
  var form = $(this)[0];
  var formData = new FormData(form);
  $.ajax({
    url: "mod/pengaturan/proses.php?action=modul-save",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      if (data == "success") {
        swal({
          title: "Berhasil!",
          text: "Data modul berhasil disimpan!",
          icon: "success",
          timer: 1500,
        });
        loadSetting(2);
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2000 });
      }
    },
  });
});

// Isi form edit modul
$(document).on("click", ".btn-edit-modul", function () {
  var id = $(this).data("id");
  var nama = $(this).data("nama");
  $("#modul_id").val(id);
  $("#modul_nama").val(nama);
});

/** Setting Logo Pertama */
$(document).on("change", ".logo", function (e) {
  var file_data = $(".logo").prop("files")[0];
  var image_name = file_data.name;
  var image_extension = image_name.split(".").pop().toLowerCase();

  if (jQuery.inArray(image_extension, ["gif", "jpg", "jpeg", "png"]) == -1) {
    swal({
      title: "Oops!",
      text: "File yang di unggah tidak sesuai dengan format, File harus jpg, jpeg, gif, png.!",
      icon: "error",
      timer: 2000,
    });
  }

  var form_data = new FormData();
  form_data.append("file", file_data);
  $.ajax({
    url: "mod/pengaturan/proses.php?action=logo",
    method: "POST",
    data: form_data,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      if (data == "success") {
        swal({
          title: "Behasil!",
          text: "Berhasil menyimpan logo website.!",
          icon: "success",
          timer: 1500,
        });
        loadSetting(1);
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2000 });
      }
    },
  });
});

/** Setting Logo Kedua  */
$(document).on("change", ".logo2", function (e) {
  var file_data = $(".logo2").prop("files")[0];
  var image_name = file_data.name;
  var image_extension = image_name.split(".").pop().toLowerCase();

  if (jQuery.inArray(image_extension, ["gif", "jpg", "jpeg", "png"]) == -1) {
    swal({
      title: "Oops!",
      text: "File yang di unggah tidak sesuai dengan format, File harus jpg, jpeg, gif, png.!",
      icon: "error",
      timer: 2000,
    });
    return;
  }

  var form_data = new FormData();
  form_data.append("file2", file_data);
  $.ajax({
    url: "mod/pengaturan/proses.php?action=logo2",
    method: "POST",
    data: form_data,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      if (data == "success") {
        swal({
          title: "Behasil!",
          text: "Berhasil menyimpan logo website 2.!",
          icon: "success",
          timer: 1500,
        });
        loadSetting(1);
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2000 });
      }
    },
  });
});
"use strict";
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

/** Setting Favicon */
$(document).on("change", ".favicon", function () {
  var file_data = $(".favicon").prop("files")[0];
  var image_name = file_data.name;
  var image_extension = image_name.split(".").pop().toLowerCase();

  if (jQuery.inArray(image_extension, ["gif", "jpg", "jpeg", "png"]) == -1) {
    swal({
      title: "Oops!",
      text: "File yang di unggah tidak sesuai dengan format, File harus jpg, jpeg, gif, png.!",
      icon: "error",
      timer: 2000,
    });
  }
  var form_data = new FormData();
  form_data.append("file", file_data);
  $.ajax({
    url: "mod/pengaturan/proses.php?action=favicon",
    method: "POST",
    data: form_data,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend: function () {},
    success: function (data) {
      if (data == "success") {
        swal({
          title: "Behasil!",
          text: "Berhasil menyimpan favicon.!",
          icon: "success",
          timer: 1500,
        });
        loadSetting(1);
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2000 });
      }
    },
  });
});

  /** Setting kop sekolah (upload kop sekolah) */
  // Match the file input class used in the form: `class="upload kop d-none"`
  $(document).on("change", ".kop", function () {
    var file = this.files[0];
    if (!file) return;

    var name = file.name;
    var ext = name.split(".").pop().toLowerCase();
    var allowed = ["gif", "jpg", "jpeg", "png"];

    if ($.inArray(ext, allowed) === -1) {
      swal({
        title: "Oops!",
        text: "Format tidak didukung. Gunakan jpg, jpeg, png, gif, atau pdf.",
        icon: "error",
        timer: 2000,
      });
      $(this).val("");
      return;
    }

    var maxSize = 2 * 1024 * 1024; // 2MB
    if (file.size > maxSize) {
      swal({
        title: "Oops!",
        text: "File terlalu besar. Maksimum 2MB.",
        icon: "error",
        timer: 2000,
      });
      $(this).val("");
      return;
    }

    var form_data = new FormData();
    form_data.append("file_kop", file);

    $.ajax({
      url: "mod/pengaturan/proses.php?action=kop-sekolah",
      method: "POST",
      data: form_data,
      contentType: false,
      processData: false,
      cache: false,
      beforeSend: function () {
        loading();
        swal({ title: "Mengunggah...", text: "Mengunggah kop sekolah...", icon: "info", buttons: false });
      },
      success: function (data) {
        var trimmed = (data || "").toString().trim();
        if (trimmed === "success") {
          swal({
            title: "Berhasil!",
            text: "Kop sekolah berhasil diunggah.",
            icon: "success",
            timer: 1500,
          });

          // Update preview if there's an <img class="preview-kop"> in the form
          if (ext !== "pdf") {
            var reader = new FileReader();
            reader.onload = function (e) {
              $(".preview-kop").attr("src", e.target.result);
            };
            reader.readAsDataURL(file);
          }

          // reload current settings (adjust id if kop is under different tab)
          loadSetting(1);
        } else {
              swal({ title: "Oops!", text: trimmed || "Terjadi kesalahan saat upload.", icon: "error", timer: 3500 });
        }
      },
      error: function (xhr) {
            var errorMsg = "Terjadi kesalahan koneksi ke server.";
            if (xhr && xhr.responseText) {
              errorMsg = xhr.responseText.trim();
            }
            swal({
              title: "Error!",
              text: errorMsg,
              icon: "error",
              timer: 3500,
            });
      },
    });
  });

/** Setting Web */
$(".load-form").on("submit", ".form-setting", function (e) {
  e.preventDefault();
  $.ajax({
    url: "mod/pengaturan/proses.php?action=setting-web",
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
      if (data == "success") {
        swal({
          title: "Berhasil!",
          text: "Pengaturan Web berhasil disimpan.!",
          icon: "success",
          timer: 2500,
        });
        loadSetting(1);
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2500 });
      }
    },
  });
});

/** Setting Absensi */
$(".load-form").on("submit", ".form-setting-absensi", function (e) {
  e.preventDefault();
  $.ajax({
    url: "mod/pengaturan/proses.php?action=setting-absensi",
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
      if (data == "success") {
        swal({
          title: "Berhasil!",
          text: "Pengaturan Absen berhasil disimpan.!",
          icon: "success",
          timer: 2500,
        });
        loadSetting(2);
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2500 });
      }
    },
  });
});

/** Setting Server */
$(".load-form").on("submit", ".form-setting-server", function (e) {
  e.preventDefault();
  $.ajax({
    url: "mod/pengaturan/proses.php?action=setting-server",
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
      if (data == "success") {
        swal({
          title: "Berhasil!",
          text: "Pengaturan Server berhasil disimpan.!",
          icon: "success",
          timer: 2500,
        });
        loadSetting(3);
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2500 });
      }
    },
  });
});

/* ------------- Set Active Lokasi --------------*/
$(document).on("click", ".btn-active", function () {
  var id = $(this).attr("data-id");
  var active = $(".active" + id).attr("data-active");
  if (active == "Y") {
    var dataactive = "N";
  } else {
    var dataactive = "Y";
  }
  var dataString = "id=" + id + "&active=" + dataactive;
  $.ajax({
    type: "POST",
    url: "./mod/libur-kantor/proses.php?action=active",
    data: dataString,
    success: function (data) {
      if (active == "Y") {
        $(".active" + id).attr("data-active", "N");
      } else {
        $(".active" + id).attr("data-active", "Y");
      }

      if (data == "success") {
        // debug removed: Successfully set active
      } else {
        // debug removed: data
      }
    },
  });
});

/** Setting WhatsApp Gateway */
$(".load-form").on("submit", ".form-whatsapp-gateway", function (e) {
  e.preventDefault();
  $.ajax({
    url: "mod/pengaturan/proses.php?action=whatsapp-gateway",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      if (data == "success") {
        swal("Berhasil!", "Konfigurasi WhatsApp Gateway berhasil disimpan", "success");
        loadSetting(7); // reload form
      } else {
        swal("Gagal!", data, "error");
      }
    },
    error: function (xhr) {
      swal("Error!", "Terjadi kesalahan sistem", "error");
    },
  });
});

/** Setting Maintenance */
$(".load-form").on("submit", ".form-maintenance", function (e) {
  e.preventDefault();
  $.ajax({
    url: "mod/pengaturan/proses.php?action=maintenance-setting",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      var trimmed = (data || "").toString().trim();
      if (trimmed === "success") {
        swal("Berhasil!", "Pengaturan maintenance berhasil disimpan", "success");
        loadSetting(8);
      } else {
        swal("Gagal!", trimmed || "Terjadi kesalahan saat menyimpan pengaturan maintenance", "error");
      }
    },
    error: function (xhr) {
      swal("Error!", xhr.statusText || "Terjadi kesalahan sistem", "error");
    },
  });
});

/** Test WhatsApp Function */
function testWhatsApp() {
  swal({
    title: "Test WhatsApp Gateway",
    content: {
      element: "div",
      attributes: {
        innerHTML: `
          <div class="form-group mb-3">
            <label class="font-weight-bold">Nomor Telepon:</label>
            <input type="tel" id="test-phone" class="form-control" placeholder="08123456789" required>
            <small class="text-muted">Masukkan nomor dengan format: 08123456789</small>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold">Pesan:</label>
            <textarea id="test-message" class="form-control" rows="3" placeholder="Test pesan dari sistem">Test pesan dari sistem WhatsApp Gateway</textarea>
          </div>
        `
      }
    },
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Kirim Test",
        value: true,
        visible: true,
        className: "btn btn-success",
        closeModal: false
      }
    },
    dangerMode: false,
  }).then((willTest) => {
    if (willTest) {
      const phone = document.getElementById('test-phone').value;
      const message = document.getElementById('test-message').value;
      
      if (!phone) {
        swal("Error!", "Nomor telepon harus diisi", "error");
        return false;
      }
      
      // Show loading
      swal({
        title: "Mengirim Test...",
        text: "Mohon tunggu, sedang mengirim pesan test ke WhatsApp",
        icon: "info",
        buttons: false,
        closeOnClickOutside: false,
        closeOnEsc: false
      });
      
      // Send test request
      $.ajax({
        url: "mod/pengaturan/test-whatsapp.php",
        method: "POST",
        data: {
          phone: phone,
          message: message
        },
        dataType: "json",
        success: function(response) {
          if (response.status === 'success') {
            swal({
              title: "Berhasil!",
              text: response.message,
              icon: "success",
              content: {
                element: "div",
                attributes: {
                  innerHTML: `
                    <div class="alert alert-success mt-3">
                      <strong>Detail:</strong><br>
                      <small>
                        Mode: ${response.details.mode}<br>
                        Nomor: ${response.details.phone}<br>
                        Waktu: ${response.details.timestamp}
                      </small>
                    </div>
                  `
                }
              }
            });
          } else if (response.status === 'warning') {
            swal({
              title: "Mode Simulasi",
              text: response.message,
              icon: "warning",
              content: {
                element: "div",
                attributes: {
                  innerHTML: `
                    <div class="alert alert-warning mt-3">
                      <strong>Info:</strong> Sistem berjalan dalam mode simulasi<br>
                      <small>Aktifkan WhatsApp Gateway untuk pengiriman sesungguhnya</small>
                    </div>
                  `
                }
              }
            });
          } else {
            swal({
              title: "Gagal!",
              text: response.message,
              icon: "error",
              content: {
                element: "div",
                attributes: {
                  innerHTML: `
                    <div class="alert alert-danger mt-3">
                      <strong>Detail Error:</strong><br>
                      <small>${response.details ? JSON.stringify(response.details) : 'Tidak ada detail error'}</small>
                    </div>
                  `
                }
              }
            });
          }
        },
        error: function(xhr, status, error) {
          swal("Error!", "Terjadi kesalahan: " + error, "error");
        }
      });
    }
  });
}
