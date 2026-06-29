"use strict";
var skEditingId = 0;
var genFields = []; // array of {name,label,type} dari load_template_tags
var genTemplateId = 0;
var genIndeksId = 0;

$(function () {
  if ($.fn.DataTable && $(".surat-keluar-table").length) {
    $(".surat-keluar-table").DataTable({
      iDisplayLength: 5,
      aLengthMenu: [
        [5, 10, 25, -1],
        [5, 10, 25, "All"],
      ],
      aaSorting: [[0, "desc"]],
      language: {
        search: "Cari:",
        lengthMenu: "_MENU_",
        info: "_START_-_END_ dari _TOTAL_",
        infoEmpty: "0",
        paginate: {
          previous: '<i class="fas fa-angle-left">',
          next: '<i class="fas fa-angle-right">',
        },
      },
      columnDefs: [{ orderable: false, targets: [7] }],
      ajax: {
        url: "./mod/surat-keluar/proses.php?action=datatable",
        dataSrc: function (j) {
          return j.data || [];
        },
      },
    });
  }

  // Init template select → load fields
  initGenerateModule();
});

function resetForm() {
  skEditingId = 0;
  $("#fId").val(0);
  $("#fIndeks").val("");
  $("#fNoSurat").val("");
  $("#fNoSuratDisplay").val("");
  $("#fTglSurat").val(new Date().toISOString().split("T")[0]);
  $("#fPerihal").val("");
  $("#fTujuan").val("");
  $("#fLampiran").val("");
  $("#fLampiranDisplay").val("");
  $("#fIsiSurat").val("");
  $("#fStatus").val("Draf");
  $("#frmTitle").text("Catat Surat Baru");
  $("#fStatusBadge").addClass("d-none");
  $("#btnSimpan").html('<i class="fas fa-save mr-1"></i> Simpan');
  $("#btnBatal").hide();
}

$(document).on("click", ".btn-baru-surat", function () {
  resetForm();
  $("html, body").animate(
    { scrollTop: $(".sk-form-card").offset().top - 90 },
    400,
  );
});

$(document).on("click", "#btnBatal", function () {
  resetForm();
});

$(document).on("click", ".btn-gen-nomor", function () {
  var indeks = $("#fIndeks").find(":selected").data("indeks") || "";
  if (!indeks) {
    swal({ title: "Pilih indeks dulu!", icon: "warning" });
    return;
  }
  $.get(
    "./mod/surat-keluar/proses.php?action=gen_nomor&indeks=" +
      encodeURIComponent(indeks),
    function (d) {
      var no = (d || "").trim();
      if (no) {
        $("#fNoSurat").val(no);
        $("#fNoSuratDisplay").val(no);
      }
    },
  );
});

$(document).on("change", "#fIndeks", function () {
  var o = $(this).find(":selected");
  if (!o.val()) return;
  if (skEditingId > 0) return;
  var indeks = o.data("indeks") || "";
  $.get(
    "./mod/surat-keluar/proses.php?action=gen_nomor&indeks=" +
      encodeURIComponent(indeks),
    function (d) {
      var no = (d || "").trim();
      if (no) {
        $("#fNoSurat").val(no);
        $("#fNoSuratDisplay").val(no);
      }
    },
  );
});

$(document).on("submit", "#formSurat", function (e) {
  e.preventDefault();
  if (!SK_CAN_EDIT) {
    swal({
      title: "Akses ditolak",
      text: "Anda tidak punya hak modifikasi.",
      icon: "error",
    });
    return;
  }
  $("#fLampiran").val($("#fLampiranDisplay").val());
  var isEdit = skEditingId > 0;
  var fd = new FormData(this);
  fd.set("action", isEdit ? "update_surat" : "buat");
  if (isEdit) fd.set("id", skEditingId);
  $.ajax({
    url:
      "./mod/surat-keluar/proses.php?action=" +
      (isEdit ? "update_surat" : "buat"),
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (data) {
      try {
        var r = typeof data === "object" ? data : JSON.parse(data);
        if (r.status === "success") {
          swal({
            title: "Berhasil!",
            text: isEdit ? "Surat diperbarui." : "Surat tersimpan.",
            icon: "success",
            timer: 1500,
          });
          setTimeout(
            function () {
              location.reload();
            },
            isEdit ? 1500 : 2000,
          );
        } else {
          swal({ title: "Gagal!", text: r.message || data, icon: "error" });
        }
      } catch (e) {
        swal({ title: "Error!", text: data, icon: "error" });
      }
    },
    error: function (xhr) {
      var m = "Koneksi gagal.";
      if (xhr && xhr.responseText)
        m = xhr.responseText
          .replace(/<[^>]*>/g, " ")
          .trim()
          .substring(0, 200);
      swal({ title: "Error!", text: m, icon: "error" });
    },
  });
});

$(document).on("click", ".btn-edit-surat", function () {
  var id = $(this).data("id");
  if (!id) return;
  $.ajax({
    url: "./mod/surat-keluar/proses.php?action=load_surat&id=" + id,
    type: "GET",
    dataType: "json",
    success: function (r) {
      if (r.status !== "success") {
        swal({ title: "Error", text: r.message, icon: "error" });
        return;
      }
      var d = r.data;
      skEditingId = d.id;
      $("#fId").val(d.id);
      $("#fIndeks").val(d.indeks_id);
      $("#fNoSurat").val(d.no_surat);
      $("#fNoSuratDisplay").val(d.no_surat);
      $("#fTglSurat").val(d.tgl_surat ? d.tgl_surat.substring(0, 10) : "");
      $("#fPerihal").val(d.perihal);
      $("#fTujuan").val(d.tujuan);
      $("#fLampiran").val(d.lampiran || "");
      $("#fLampiranDisplay").val(d.lampiran || "");
      $("#fIsiSurat").val(d.isi_surat || "");
      $("#fStatus").val(d.status);
      $("#frmTitle").text("Edit Surat — " + d.no_surat);
      var badgeCls =
        d.status === "Terkirim"
          ? "badge-success"
          : d.status === "Draf"
            ? "badge-warning"
            : "badge-secondary";
      $("#fStatusBadge")
        .removeClass("d-none badge-success badge-warning badge-secondary")
        .addClass(badgeCls)
        .text(d.status);
      $("#btnSimpan").html('<i class="fas fa-save mr-1"></i> Perbarui');
      $("#btnBatal").show();
      $("html, body").animate(
        { scrollTop: $(".sk-form-card").offset().top - 90 },
        400,
      );
    },
    error: function () {
      swal({ title: "Error", text: "Gagal memuat surat.", icon: "error" });
    },
  });
});

$(document).on("click", ".btn-delete-keluar", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus?",
    text: "Surat akan dihapus permanen.",
    icon: "warning",
    buttons: ["Batal", "Ya"],
    dangerMode: true,
  }).then(function (ok) {
    if (!ok) return;
    $.post(
      "./mod/surat-keluar/proses.php?action=delete",
      { id: id },
      function (data) {
        if (data.trim() === "success") {
          swal({
            title: "Berhasil!",
            text: "Surat dihapus.",
            icon: "success",
            timer: 1500,
          });
          setTimeout(function () {
            location.reload();
          }, 1200);
        } else {
          swal({ title: "Gagal!", text: data, icon: "error" });
        }
      },
    );
  });
});

$(document).on("click", ".btn-kirim-surat", function () {
  var id = $(this).data("id");
  swal({
    title: "Tandai Terkirim?",
    text: "Status surat akan diubah menjadi Terkirim.",
    icon: "info",
    buttons: ["Batal", "Ya"],
  }).then(function (ok) {
    if (!ok) return;
    $.post(
      "./mod/surat-keluar/proses.php?action=kirim",
      { id: id },
      function (data) {
        if (data.trim() === "success") {
          swal({
            title: "Berhasil!",
            text: "Status diperbarui.",
            icon: "success",
            timer: 1500,
          });
          setTimeout(function () {
            location.reload();
          }, 1200);
        } else {
          swal({ title: "Gagal!", text: data, icon: "error" });
        }
      },
    );
  });
});

$(document).on("click", ".btn-export-surat-keluar", function () {
  location.href = "./mod/surat-keluar/proses.php?action=export_excel";
});

// ===================================================================
// Generate Module — Template-based PDF generation & Spreadsheet
// ===================================================================

function initGenerateModule() {
  var $sel = $("#genTemplateSelect");
  if (!$sel.length) return;

  // Load fields when template selected
  $sel.on("change", function () {
    var tid = parseInt($(this).val()) || 0;
    if (!tid) {
      resetGenerateForm();
      return;
    }
    genTemplateId = tid;
    genIndeksId = parseInt($(this).find(":selected").data("indeks-id")) || 0;

    // Auto-generate nomor surat
    var indeks = $(this).find(":selected").data("indeks") || "";
    if (indeks) {
      $.get(
        "./mod/surat-keluar/proses.php?action=gen_nomor&indeks=" +
          encodeURIComponent(indeks),
        function (d) {
          var no = (d || "").trim();
          if (no) $("#genNoSurat").val(no);
        },
      );
    }

    // Load template fields via AJAX
    loadTemplateFields(tid);
  });

  // Manual generate nomor
  $("#btnGenNomor").on("click", function () {
    var indeks = $sel.find(":selected").data("indeks") || "";
    if (!indeks) {
      swal({ title: "Pilih template dulu!", icon: "warning" });
      return;
    }
    $.get(
      "./mod/surat-keluar/proses.php?action=gen_nomor&indeks=" +
        encodeURIComponent(indeks),
      function (d) {
        var no = (d || "").trim();
        if (no) $("#genNoSurat").val(no);
      },
    );
  });

  // Generate PDF
  $("#btnGenerate").on("click", function () {
    generatePdf();
  });

  // Simpan & Kirim ke Spreadsheet
  $("#btnSaveToSpreadsheet").on("click", function () {
    saveToSpreadsheet();
  });

  // Reset
  $("#btnGenReset").on("click", function () {
    resetGenerateForm();
    $sel.val("").trigger("change");
  });
}

function loadTemplateFields(templateId) {
  $("#genDynamicFields").html(
    '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin mb-2" style="font-size:32px;"></i><p class="mb-0">Memuat form...</p></div>',
  );
  $("#genActions").hide();

  $.ajax({
    url:
      "./mod/surat-keluar/proses.php?action=load_template_tags&template_id=" +
      templateId,
    type: "GET",
    dataType: "json",
    success: function (r) {
      if (r.status !== "success") {
        $("#genDynamicFields").html(
          '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle mr-1"></i>' +
            (r.message || "Gagal memuat form.") +
            "</div>",
        );
        return;
      }
      genFields = r.fields || [];
      buildDynamicForm(genFields);
      $("#genActions").show();
    },
    error: function () {
      $("#genDynamicFields").html(
        '<div class="alert alert-danger mb-0"><i class="fas fa-times mr-1"></i>Gagal terhubung ke server.</div>',
      );
    },
  });
}

function buildDynamicForm(fields) {
  var html = '<div class="row-dynamic">';
  $.each(fields, function (i, f) {
    var required = "required";
    var inputEl = "";
    if (f.type === "textarea") {
      inputEl =
        '<textarea name="' +
        f.name +
        '" id="gf_' +
        f.name +
        '" class="form-control" rows="3" ' +
        required +
        "></textarea>";
    } else if (f.type === "date") {
      inputEl =
        '<input type="date" name="' +
        f.name +
        '" id="gf_' +
        f.name +
        '" class="form-control" value="' +
        new Date().toISOString().split("T")[0] +
        '" ' +
        required +
        ">";
    } else {
      inputEl =
        '<input type="text" name="' +
        f.name +
        '" id="gf_' +
        f.name +
        '" class="form-control" placeholder="' +
        f.label +
        '" ' +
        required +
        ">";
    }
    html += '<div class="form-group">';
    html += '<label for="gf_' + f.name + '">' + f.label + "</label>";
    html += inputEl;
    html += "</div>";
  });
  html += "</div>";
  $("#genDynamicFields").html(html);
}

function getFieldValues() {
  var vals = {};
  $.each(genFields, function (i, f) {
    vals[f.name] = $("#gf_" + f.name).val() || "";
  });
  return vals;
}

function generatePdf() {
  var templateId = genTemplateId;
  if (!templateId) {
    swal({ title: "Pilih template dulu!", icon: "warning" });
    return;
  }

  var noSurat = $("#genNoSurat").val().trim();
  if (!noSurat) {
    swal({
      title: "Nomor surat belum digenerate!",
      text: "Klik tombol generate nomor atau pilih template.",
      icon: "warning",
    });
    return;
  }

  var fieldVals = getFieldValues();

  // Validasi tidak boleh kosong
  for (var key in fieldVals) {
    if (!fieldVals[key]) {
      swal({
        title: "Lengkapi data!",
        text: "Field " + key + " masih kosong.",
        icon: "warning",
      });
      return;
    }
  }

  swal({
    title: "Memproses...",
    text: "Mengunduh template dari Google Docs dan generate PDF.",
    icon: "info",
    buttons: false,
    closeOnClickOutside: false,
  });

  // Simpan dulu surat baru (status Draf) kalau belum ada
  var suratId = parseInt($("#genSuratId").val()) || 0;

  var savePromise;
  if (suratId <= 0) {
    // Buat surat baru dulu via form submit manual
    savePromise = createDraftSurat(noSurat, fieldVals);
  } else {
    savePromise = $.Deferred().resolve(suratId).promise();
  }

  $.when(savePromise).then(function (newSuratId) {
    if (!newSuratId) {
      swal.close();
      swal({
        title: "Gagal!",
        text: "Tidak dapat menyimpan draft surat.",
        icon: "error",
      });
      return;
    }
    $("#genSuratId").val(newSuratId);

    // Generate PDF — tambahkan timeout 120 detik
    $.ajax({
      url: "./mod/surat-keluar/proses.php?action=generate_pdf",
      type: "POST",
      timeout: 120000, // 2 menit
      data: {
        template_id: templateId,
        surat_id: newSuratId,
        no_surat: noSurat,
        field_values: JSON.stringify(fieldVals),
      },
      dataType: "json",
      success: function (r) {
        swal.close();
        if (r.status === "success") {
          // Tampilkan PDF
          showPdfPreview(r.pdf_url);
          $("#genPdfPath").val(r.pdf_path || "");
          swal({
            title: "PDF Berhasil!",
            text: "Surat berhasil digenerate.",
            icon: "success",
            timer: 1500,
          });
        } else {
          swal({
            title: "Gagal!",
            text: r.message || "Gagal generate PDF.",
            icon: "error",
          });
        }
      },
      error: function (jqXHR, textStatus) {
        swal.close();
        var msg = "Koneksi gagal.";
        if (textStatus === "timeout") {
          msg = "Waktu habis! Server terlalu lama merespons. Coba lagi nanti.";
        } else if (textStatus === "error") {
          msg = "Terjadi kesalahan komunikasi dengan server.";
        }
        swal({ title: "Error!", text: msg, icon: "error" });
      },
    });
  });
}

function createDraftSurat(noSurat, fieldVals) {
  var defer = $.Deferred();

  var indeksId = genIndeksId;
  var perihal =
    $("#fPerihal").val().trim() ||
    "Surat " + $("#genTemplateSelect").find(":selected").text();

  $.ajax({
    url: "./mod/surat-keluar/proses.php?action=buat",
    type: "POST",
    data: {
      indeks_id: indeksId,
      no_surat: noSurat,
      perihal: perihal,
      tujuan: "",
      tgl_surat:
        $("#fTglSurat").val() || new Date().toISOString().split("T")[0],
      lampiran: "",
      isi_surat: "",
    },
    dataType: "json",
    success: function (r) {
      if (r.status === "success" && r.id) {
        defer.resolve(r.id);
      } else {
        defer.resolve(0);
      }
    },
    error: function () {
      defer.resolve(0);
    },
  });

  return defer.promise();
}

function showPdfPreview(url) {
  var $iframe = $("#pdfPreview");
  var $placeholder = $(".sk-preview-placeholder");

  $placeholder.addClass("d-none");
  $iframe.removeClass("d-none").attr("src", url);
  $("#btnDownloadPdf").attr("href", url).removeClass("d-none");
  $("#btnPrintPdf").removeClass("d-none");
}

function resetGenerateForm() {
  genTemplateId = 0;
  genIndeksId = 0;
  genFields = [];
  $("#genNoSurat").val("");
  $("#genDynamicFields").html(
    '<div class="text-center text-muted py-5"><i class="fas fa-arrow-left mb-2" style="font-size:32px;"></i><p class="mb-0">Pilih template terlebih dahulu untuk menampilkan form.</p></div>',
  );
  $("#genActions").hide();
  $("#pdfPreview").addClass("d-none").attr("src", "about:blank");
  $(".sk-preview-placeholder").removeClass("d-none");
  $("#btnDownloadPdf").addClass("d-none").attr("href", "#");
  $("#btnPrintPdf").addClass("d-none");
  $("#genSuratId").val(0);
  $("#genPdfPath").val("");
  $("#genStatusBadge").addClass("d-none");
}

function saveToSpreadsheet() {
  var suratId = parseInt($("#genSuratId").val()) || 0;
  if (!suratId) {
    swal({
      title: "Generate dulu!",
      text: "Generate PDF terlebih dahulu sebelum menyimpan ke spreadsheet.",
      icon: "warning",
    });
    return;
  }

  var fieldVals = getFieldValues();

  swal({
    title: "Menyimpan...",
    text: "Mengirim data ke Google Spreadsheet.",
    icon: "info",
    buttons: false,
    closeOnClickOutside: false,
  });

  $.ajax({
    url: "./mod/surat-keluar/proses.php?action=save_to_spreadsheet",
    type: "POST",
    data: {
      surat_id: suratId,
      field_values: JSON.stringify(fieldVals),
    },
    dataType: "json",
    success: function (r) {
      swal.close();
      if (r.status === "success") {
        swal({
          title: "Berhasil!",
          text: r.message || "Data berhasil disimpan ke spreadsheet.",
          icon: "success",
          buttons: {
            open: { text: "Buka Spreadsheet", value: "open" },
            ok: { text: "OK", value: "ok" },
          },
        }).then(function (val) {
          if (val === "open" && r.spreadsheet_url) {
            window.open(r.spreadsheet_url, "_blank");
          }
          if (r.spreadsheet_range) {
            $("#genStatusBadge")
              .removeClass("d-none")
              .text("Spreadsheet: " + r.spreadsheet_range);
          }
          setTimeout(function () {
            location.reload();
          }, 2000);
        });
      } else {
        swal({
          title: "Gagal!",
          text: r.message || "Gagal menyimpan.",
          icon: "error",
        });
      }
    },
    error: function () {
      swal.close();
      swal({ title: "Error!", text: "Koneksi gagal.", icon: "error" });
    },
  });
}

// Print PDF
$(document).on("click", "#btnPrintPdf", function () {
  var pdfUrl = $("#pdfPreview").attr("src");
  if (pdfUrl && pdfUrl !== "about:blank") {
    window.open(pdfUrl, "_blank");
  }
});
