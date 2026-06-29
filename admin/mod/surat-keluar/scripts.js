"use strict";
var skEditingId = 0;

$(function () {
  if ($.fn.DataTable && $(".surat-keluar-table").length) {
    $(".surat-keluar-table").DataTable({
      iDisplayLength: 5,
      aLengthMenu: [[5, 10, 25, -1], [5, 10, 25, "All"]],
      aaSorting: [[0, "desc"]],
      language: {
        search: "Cari:",
        lengthMenu: "_MENU_",
        info: "_START_-_END_ dari _TOTAL_",
        infoEmpty: "0",
        paginate: { previous: "<i class=\"fas fa-angle-left\">", next: "<i class=\"fas fa-angle-right\">" },
      },
      columnDefs: [{ orderable: false, targets: [7] }],
      ajax: { url: "./mod/surat-keluar/proses.php?action=datatable", dataSrc: function (j) { return j.data || []; } },
    });
  }
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
  $("#btnSimpan").html("<i class=\"fas fa-save mr-1\"></i> Simpan");
  $("#btnBatal").hide();
}

$(document).on("click", ".btn-baru-surat", function () {
  resetForm();
  $("html, body").animate({ scrollTop: $(".sk-form-card").offset().top - 90 }, 400);
});

$(document).on("click", "#btnBatal", function () { resetForm(); });

$(document).on("click", ".btn-gen-nomor", function () {
  var indeks = $("#fIndeks").find(":selected").data("indeks") || "";
  if (!indeks) { swal({ title: "Pilih indeks dulu!", icon: "warning" }); return; }
  $.get("./mod/surat-keluar/proses.php?action=gen_nomor&indeks=" + encodeURIComponent(indeks), function (d) {
    var no = (d || "").trim();
    if (no) { $("#fNoSurat").val(no); $("#fNoSuratDisplay").val(no); }
  });
});

$(document).on("change", "#fIndeks", function () {
  var o = $(this).find(":selected");
  if (!o.val()) return;
  if (skEditingId > 0) return;
  var indeks = o.data("indeks") || "";
  $.get("./mod/surat-keluar/proses.php?action=gen_nomor&indeks=" + encodeURIComponent(indeks), function (d) {
    var no = (d || "").trim();
    if (no) { $("#fNoSurat").val(no); $("#fNoSuratDisplay").val(no); }
  });
});

$(document).on("submit", "#formSurat", function (e) {
  e.preventDefault();
  if (!SK_CAN_EDIT) {
    swal({ title: "Akses ditolak", text: "Anda tidak punya hak modifikasi.", icon: "error" });
    return;
  }
  $("#fLampiran").val($("#fLampiranDisplay").val());
  var isEdit = skEditingId > 0;
  var fd = new FormData(this);
  fd.set("action", isEdit ? "update_surat" : "buat");
  if (isEdit) fd.set("id", skEditingId);
  $.ajax({
    url: "./mod/surat-keluar/proses.php?action=" + (isEdit ? "update_surat" : "buat"),
    type: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (data) {
      try {
        var r = typeof data === "object" ? data : JSON.parse(data);
        if (r.status === "success") {
          swal({ title: "Berhasil!", text: isEdit ? "Surat diperbarui." : "Surat tersimpan.", icon: "success", timer: 1500 });
          setTimeout(function () { location.reload(); }, isEdit ? 1500 : 2000);
        } else {
          swal({ title: "Gagal!", text: r.message || data, icon: "error" });
        }
      } catch (e) {
        swal({ title: "Error!", text: data, icon: "error" });
      }
    },
    error: function (xhr) {
      var m = "Koneksi gagal.";
      if (xhr && xhr.responseText) m = xhr.responseText.replace(/<[^>]*>/g, " ").trim().substring(0, 200);
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
      if (r.status !== "success") { swal({ title: "Error", text: r.message, icon: "error" }); return; }
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
      var badgeCls = d.status === "Terkirim" ? "badge-success" : d.status === "Draf" ? "badge-warning" : "badge-secondary";
      $("#fStatusBadge").removeClass("d-none badge-success badge-warning badge-secondary").addClass(badgeCls).text(d.status);
      $("#btnSimpan").html("<i class=\"fas fa-save mr-1\"></i> Perbarui");
      $("#btnBatal").show();
      $("html, body").animate({ scrollTop: $(".sk-form-card").offset().top - 90 }, 400);
    },
    error: function () { swal({ title: "Error", text: "Gagal memuat surat.", icon: "error" }); },
  });
});

$(document).on("click", ".btn-delete-keluar", function () {
  var id = $(this).data("id");
  swal({ title: "Hapus?", text: "Surat akan dihapus permanen.", icon: "warning", buttons: ["Batal", "Ya"], dangerMode: true }).then(function (ok) {
    if (!ok) return;
    $.post("./mod/surat-keluar/proses.php?action=delete", { id: id }, function (data) {
      if (data.trim() === "success") {
        swal({ title: "Berhasil!", text: "Surat dihapus.", icon: "success", timer: 1500 });
        setTimeout(function () { location.reload(); }, 1200);
      } else { swal({ title: "Gagal!", text: data, icon: "error" }); }
    });
  });
});

$(document).on("click", ".btn-kirim-surat", function () {
  var id = $(this).data("id");
  swal({ title: "Tandai Terkirim?", text: "Status surat akan diubah menjadi Terkirim.", icon: "info", buttons: ["Batal", "Ya"] }).then(function (ok) {
    if (!ok) return;
    $.post("./mod/surat-keluar/proses.php?action=kirim", { id: id }, function (data) {
      if (data.trim() === "success") {
        swal({ title: "Berhasil!", text: "Status diperbarui.", icon: "success", timer: 1500 });
        setTimeout(function () { location.reload(); }, 1200);
      } else { swal({ title: "Gagal!", text: data, icon: "error" }); }
    });
  });
});

$(document).on("click", ".btn-export-surat-keluar", function () {
  location.href = "./mod/surat-keluar/proses.php?action=export_excel";
});
