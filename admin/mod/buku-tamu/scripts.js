"use strict";

var dtTamu, dtSurvey;
var PERM = window.BUKU_TAMU_PERM || { edit: false, del: false };

function loadTamu() {
  dtTamu = $("#tableTamu").DataTable({
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    order: [],
    ajax: {
      url: "./mod/buku-tamu/datatable.php",
      type: "POST",
      data: function (d) {
        d.mode = "tamu";
        d.dari = $("#f_dari").val();
        d.sampai = $("#f_sampai").val();
        d.status = $("#f_status").val();
      },
    },
    columns: [
      { title: "No", className: "text-center", orderable: false },
      { title: "Foto", className: "text-center", orderable: false },
      { title: "Nama / Instansi" },
      { title: "Keperluan" },
      { title: "Tanggal", className: "text-center" },
      { title: "Masuk", className: "text-center" },
      { title: "Keluar", className: "text-center" },
      { title: "Status", className: "text-center" },
      { title: "Aksi", className: "text-center", orderable: false },
    ],
    language: {
      paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" },
    },
  });
}

function loadSurvey() {
  dtSurvey = $("#tableSurvey").DataTable({
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    order: [],
    ajax: {
      url: "./mod/buku-tamu/datatable.php",
      type: "POST",
      data: function (d) { d.mode = "survey"; },
    },
    columns: [
      { title: "No", className: "text-center", orderable: false },
      { title: "Tamu" },
      { title: "Rating", className: "text-center", orderable: false },
      { title: "Pelayanan", className: "text-center" },
      { title: "Kecepatan", className: "text-center" },
      { title: "Kenyamanan", className: "text-center" },
      { title: "Komentar", orderable: false },
      { title: "Waktu", className: "text-center" },
    ],
    language: {
      paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" },
    },
  });
}

$(document).ready(function () {
  loadTamu();
  $('a[href="#tab-survey"]').on("shown.bs.tab", function () {
    if (!dtSurvey) { loadSurvey(); } else { dtSurvey.columns.adjust(); }
  });
});

// Filter
$(document).on("submit", "#filterData", function (e) {
  e.preventDefault();
  if (dtTamu) dtTamu.ajax.reload();
});
$(document).on("click", "#resetFilter", function () {
  $("#f_dari, #f_sampai, #f_status").val("");
  if (dtTamu) dtTamu.ajax.reload();
});

// Detail
$(document).on("click", ".btn-detail", function () {
  var id = $(this).data("id");
  $("#detailBody").html('<div class="text-center text-muted py-4">Memuat...</div>');
  $("#modalDetail").modal("show");
  $.getJSON("./mod/buku-tamu/proses.php?action=detail&id=" + id, function (res) {
    if (res.status !== "success") { $("#detailBody").html('<div class="text-danger">' + (res.message || "Gagal memuat") + "</div>"); return; }
    var d = res.data, s = res.survey;
    var foto = d.foto
      ? '<img src="../content/tamu/' + encodeURIComponent(d.foto) + '" class="img-fluid rounded shadow-sm mb-2" style="max-height:220px" onerror="this.style.display=\'none\'">'
      : '';
    var html = '<div class="row"><div class="col-md-4 text-center">' + foto +
      '<div class="badge badge-' + (d.status === "Aktif" ? "warning" : d.status === "Selesai" ? "success" : "danger") + '">' + d.status + "</div></div>" +
      '<div class="col-md-8"><table class="table table-sm">' +
      tr("Guest ID", d.guest_id) + tr("Nama", d.nama) + tr("Instansi", d.instansi) +
      tr("Telepon", d.telepon || "-") + tr("Keperluan", d.keperluan) + tr("Keterangan", d.keterangan || "-") +
      tr("Tanggal", d.tanggal_kunjungan) + tr("Masuk", d.waktu_masuk) + tr("Keluar", d.waktu_keluar || "-") +
      "</table></div></div>";
    if (s) {
      html += '<hr><h6><i class="fas fa-star text-warning mr-1"></i>Survey Kepuasan</h6><table class="table table-sm">' +
        tr("Rating", s.rating + " / 5") + tr("Pelayanan", s.pelayanan) + tr("Kecepatan", s.kecepatan) +
        tr("Kenyamanan", s.kenyamanan) + tr("Komentar", s.komentar || "-") + "</table>";
    }
    $("#detailBody").html(html);
  });
});
function tr(k, v) {
  return "<tr><th style='width:35%'>" + k + "</th><td>" + $("<div>").text(v == null ? "" : v).html() + "</td></tr>";
}

// Edit
$(document).on("click", ".btn-edit-tamu", function () {
  var el = $(this);
  $("#t_id").val(el.data("id"));
  $("#t_nama").val(el.data("nama"));
  $("#t_instansi").val(el.data("instansi"));
  $("#t_telepon").val(el.data("telepon"));
  $("#t_keperluan").val(el.data("keperluan"));
  $("#t_keterangan").val(el.data("keterangan"));
  $("#t_status").val(el.data("status"));
  $("#modalEditTamu").modal("show");
});
$(document).on("submit", "#formEditTamu", function (e) {
  e.preventDefault();
  $.ajax({
    url: "./mod/buku-tamu/proses.php?action=edit",
    type: "POST",
    data: $(this).serialize(),
    success: function (res) {
      swal("Info", res, res.includes("berhasil") ? "success" : "error");
      if (res.includes("berhasil")) { $("#modalEditTamu").modal("hide"); dtTamu.ajax.reload(null, false); }
    },
  });
});

// Checkout
$(document).on("click", ".btn-checkout", function () {
  var id = $(this).data("id");
  swal({
    title: "Check-out tamu ini?",
    text: "Waktu keluar akan dicatat sekarang.",
    icon: "info",
    buttons: { cancel: "Batal", confirm: { text: "Ya, Check-out", value: true } },
  }).then(function (ok) {
    if (!ok) return;
    $.post("./mod/buku-tamu/proses.php?action=checkout", { id: id }, function (res) {
      swal("Info", res.message, res.status === "success" ? "success" : "error");
      if (res.status === "success") dtTamu.ajax.reload(null, false);
    }, "json");
  });
});

// Delete
$(document).on("click", ".btn-delete-tamu", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus data tamu?",
    text: "Data, log, dan survey terkait akan dihapus permanen!",
    icon: "warning",
    dangerMode: true,
    buttons: { cancel: "Batal", confirm: { text: "Ya, Hapus", value: true } },
  }).then(function (ok) {
    if (!ok) return;
    $.post("./mod/buku-tamu/proses.php?action=hapus", { id: id }, function (res) {
      swal("Info", res.message, res.status === "success" ? "success" : "error");
      if (res.status === "success") dtTamu.ajax.reload(null, false);
    }, "json");
  });
});
