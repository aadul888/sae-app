"use strict";

var tableSuratTemplate = null;

function initTableSuratTemplate() {
  if ($.fn.DataTable && $("#tableSuratTemplate").length) {
    if ($.fn.DataTable.isDataTable("#tableSuratTemplate")) {
      $("#tableSuratTemplate").DataTable().destroy();
    }
    tableSuratTemplate = $("#tableSuratTemplate").DataTable({
      processing: false,
      serverSide: false,
      bAutoWidth: false,
      bSort: true,
      bStateSave: false,
      bDestroy: true,
      paging: true,
      scrollX: true,
      scrollCollapse: true,
      responsive: false,
      iDisplayLength: 25,
      order: [[0, "asc"]],
      aLengthMenu: [
        [25, 30, 50, -1],
        [25, 30, 50, "All"],
      ],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        infoFiltered: "(difilter dari _MAX_ total)",
        paginate: {
          previous: '<i class="fas fa-angle-left">',
          next: '<i class="fas fa-angle-right">',
        },
      },
      columnDefs: [
        { orderable: false, targets: [7] },
        { searchable: false, targets: [0, 7] },
      ],
    });
  }
}

$(function () {
  initTableSuratTemplate();

  // Select2 untuk dropdown indeks (bisa search/ketik)
  $("#f_indeks_id").select2({
    dropdownParent: $("#modalTemplate"),
    placeholder: "-- Pilih Indeks --",
    allowClear: false,
    width: "100%",
    templateResult: function (data) {
      if (!data.id) return data.text;
      // Teks dibungkus span agar CSS word-wrap bisa bekerja
      var text = $("<span>")
        .text(data.text || "")
        .html();
      return $(
        '<span style="white-space:normal;word-wrap:break-word;display:block;line-height:1.3">' +
          text +
          "</span>",
      );
    },
  });
});

/* ====== AUTO-FILL JENIS SURAT when indeks berubah ====== */
$(document).on("change", "#f_indeks_id", function () {
  var selected = $(this).find("option:selected");
  var jenis = selected.data("jenis") || "";
  $("#f_jenis_surat").val(jenis);
});

/* ====== HELP GOOGLE DOC ID ====== */
$(document).on("click", ".btn-help-docid", function () {
  swal({
    title: "Cara Mendapatkan Google Doc ID",
    text: "Buka Google Docs \u2192 lihat URL:\nhttps://docs.google.com/document/d/1ABCxyz123/edit\n\nCopy bagian 1ABCxyz123 (string panjang antara /d/ dan /edit).",
    icon: "info",
  });
});

/* ====== PREVIEW GOOGLE DOCS (Embedded Viewer) ====== */
$(document).on("click", ".btn-preview-template", function () {
  var docId = $(this).data("docid");
  var indeks = $(this).data("indeks");
  if (!docId) {
    swal({ title: "Error", text: "Doc ID tidak ditemukan.", icon: "error" });
    return;
  }
  $("#viewDocLabel").text(indeks);
  $("#viewDocIframe").attr(
    "src",
    "https://docs.google.com/document/d/" +
      encodeURIComponent(docId) +
      "/preview",
  );
  $("#modalViewDoc").modal("show");
});

$("#modalViewDoc").on("hidden.bs.modal", function () {
  $("#viewDocIframe").attr("src", "about:blank");
});

/* ====== SCAN {{tags}} FROM GOOGLE DOCS ====== */
$(document).on("click", ".btn-scan-tags", function () {
  var id = $(this).data("id");
  var btn = $(this);
  btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

  $.ajax({
    url: "./mod/surat-template/proses.php?action=scan_tags",
    type: "POST",
    data: { template_id: id },
    dataType: "json",
    success: function (r) {
      if (r.status === "success") {
        var html =
          '<div class="alert alert-success">Ditemukan <strong>' +
          r.count +
          "</strong> tag.</div>";
        html +=
          '<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Nama Tag</th><th>Label</th><th>Tipe</th></tr></thead><tbody>';
        $.each(r.fields, function (i, f) {
          html +=
            "<tr><td>" +
            (i + 1) +
            "</td><td><code>{{" +
            f.name +
            "}}</code></td><td>" +
            f.label +
            "</td><td>" +
            f.type +
            "</td></tr>";
        });
        html += "</tbody></table>";
        html +=
          '<hr><div class="alert alert-info mb-0"><i class="fas fa-info-circle mr-1"></i>Tag otomatis disimpan ke kolom <strong>Variabel Tag</strong>.</div>';
        $("#scanResultLabel").text(r.count + " tag ditemukan");
        $("#scanResultContent").html(html);
        $("#modalScanResult").modal("show");
      } else {
        swal({
          title: "Gagal!",
          text: r.message || "Gagal scan.",
          icon: "error",
        });
      }
    },
    error: function (xhr) {
      var msg = "Koneksi gagal.";
      if (xhr && xhr.responseText) msg = xhr.responseText.substring(0, 200);
      swal({ title: "Error!", text: msg, icon: "error" });
    },
    complete: function () {
      btn.prop("disabled", false).html('<i class="fas fa-tags"></i>');
    },
  });
});

/* ====== RELOAD PAGE when Scan Result modal ditutup ====== */
// Tutup / dismiss modal scan → reload agar data variabel_tag terbaru tampil
$("#modalScanResult").on("hidden.bs.modal", function () {
  location.reload();
});

/* ====== FORM TAMBAH / EDIT TEMPLATE ====== */
$(document).on("submit", "#formTemplate", function (e) {
  e.preventDefault();
  var btn = $(this).find("button[type=submit]");
  btn
    .prop("disabled", true)
    .html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');
  var fd = new FormData(this);
  $.ajax({
    url:
      "./mod/surat-template/proses.php?action=" +
      (parseInt($("#f_id").val()) > 0 ? "update" : "add"),
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (data) {
      var t = typeof data === "string" ? data.trim() : "";
      if (t === "success") {
        swal({
          title: "Berhasil!",
          text: "Template tersimpan.",
          icon: "success",
          timer: 1500,
        });
        $("#modalTemplate").modal("hide");
        setTimeout(function () {
          location.reload();
        }, 1200);
      } else {
        swal({ title: "Gagal!", text: t || "Gagal menyimpan.", icon: "error" });
      }
    },
    error: function () {
      swal({ title: "Error!", text: "Koneksi gagal.", icon: "error" });
    },
    complete: function () {
      btn
        .prop("disabled", false)
        .html('<i class="fas fa-save mr-1"></i>Simpan');
    },
  });
});

/* ====== EDIT TEMPLATE (modal populate) ====== */
$(document).on("click", ".btn-edit-template", function () {
  var id = $(this).data("id");
  $.get("./mod/surat-template/proses.php?action=get&id=" + id, function (data) {
    try {
      var d = typeof data === "object" ? data : JSON.parse(data);
      if (d.status === "success" && d.data) {
        $("#modalTemplateTitle").html(
          '<i class="fas fa-edit mr-2"></i>Edit Template',
        );
        $("#f_id").val(d.data.id);
        $("#f_indeks_id").val(d.data.indeks_id);
        // Trigger change to update jenis_surat
        $("#f_indeks_id").trigger("change");
        // Override with actual stored jenis_surat if different
        if (d.data.jenis_surat) $("#f_jenis_surat").val(d.data.jenis_surat);
        $("#f_nama_pembuat").val(d.data.nama_pembuat);
        $("#f_link_dokumen").val(d.data.link_dokumen);
        $("#f_deskripsi").val(d.data.deskripsi);
        $("#modalTemplate").modal("show");
      } else {
        swal({
          title: "Error!",
          text: d.message || "Data tidak ditemukan.",
          icon: "error",
        });
      }
    } catch (e) {
      swal({ title: "Error!", text: "Gagal parse data.", icon: "error" });
    }
  });
});

// Reset modal tambah
$("#modalTemplate").on("hidden.bs.modal", function () {
  if ($("#f_id").val() == 0) return;
  $("#f_id").val(0);
  $("#f_indeks_id").val("");
  $("#f_jenis_surat").val("");
  $("#f_nama_pembuat").val("");
  $("#f_link_dokumen").val("");
  $("#f_deskripsi").val("");
  $("#modalTemplateTitle").html(
    '<i class="fas fa-plus mr-2"></i>Tambah Template',
  );
});

/* ====== DELETE TEMPLATE ====== */
$(document).on("click", ".btn-delete-template", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Template?",
    text: "Data akan dihapus permanen.",
    icon: "warning",
    buttons: ["Batal", "Ya, Hapus!"],
    dangerMode: true,
  }).then(function (confirm) {
    if (!confirm) return;
    $.post(
      "./mod/surat-template/proses.php?action=delete",
      { id: id },
      function (data) {
        if (data.trim() === "success") {
          swal({
            title: "Berhasil!",
            text: "Template dihapus.",
            icon: "success",
            timer: 1500,
          });
          setTimeout(function () {
            location.reload();
          }, 1200);
        } else {
          swal({
            title: "Gagal!",
            text: data.trim() || "Gagal menghapus.",
            icon: "error",
          });
        }
      },
    );
  });
});
