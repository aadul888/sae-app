"use strict";

var dtPkl;
var PERM = window.PKL_PERM || { send: false };

function loadPkl() {
  dtPkl = $("#tablePkl").DataTable({
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    order: [],
    ajax: {
      url: "./mod/tarik-peserta-pkl/datatable.php",
      type: "POST",
      data: function (d) {
        d.f_kelas = $("#f_kelas").val();
        d.f_jurusan = $("#f_jurusan").val();
        d.f_kirim = $("#f_kirim").val();
      },
    },
    columns: [
      { title: "", className: "text-center", orderable: false },
      { title: "No", className: "text-center", orderable: false },
      { title: "NISN" },
      { title: "Nama" },
      { title: "Kelas", className: "text-center" },
      { title: "Jurusan" },
      { title: "Status Kirim", className: "text-center" },
    ],
    language: {
      paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" },
    },
    drawCallback: function () {
      $("#checkAll").prop("checked", false);
      updateCount();
    },
  });
}

function updateCount() {
  $("#selCount").text($(".rowCheck:checked").length);
}

$(document).ready(function () {
  loadPkl();
});

$(document).on("submit", "#filterPkl", function (e) {
  e.preventDefault();
  if (dtPkl) dtPkl.ajax.reload();
});

$(document).on("change", "#checkAll", function () {
  $(".rowCheck").prop("checked", $(this).is(":checked"));
  updateCount();
});
$(document).on("change", ".rowCheck", updateCount);

// Save config
$(document).on("submit", "#formKonfigPkl", function (e) {
  e.preventDefault();
  if (!PERM.send) return;
  $.post("./mod/tarik-peserta-pkl/proses.php?action=save_config", $(this).serialize(), function (res) {
    swal("Info", res.message, res.status === "success" ? "success" : "error");
    if (res.status === "success") { $("#modalKonfigPkl").modal("hide"); }
  }, "json");
});

// Send selected
$(document).on("click", "#btnKirim", function () {
  var nisn = $(".rowCheck:checked").map(function () { return this.value; }).get();
  if (nisn.length === 0) { swal("Info", "Pilih minimal satu siswa terlebih dahulu.", "warning"); return; }
  swal({
    title: "Kirim " + nisn.length + " peserta ke e-PKL?",
    text: "Data siswa terpilih akan dikirim ke aplikasi e-PKL.",
    icon: "info",
    buttons: { cancel: "Batal", confirm: { text: "Ya, Kirim", value: true } },
  }).then(function (ok) {
    if (!ok) return;
    var $btn = $("#btnKirim").prop("disabled", true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Mengirim...');
    $.ajax({
      url: "./mod/tarik-peserta-pkl/proses.php?action=send",
      type: "POST",
      data: { nisn: nisn },
      dataType: "json",
      success: function (res) {
        swal("Info", res.message, res.status === "success" ? "success" : "error");
        if (dtPkl) dtPkl.ajax.reload(null, false);
      },
      error: function () { swal("Error", "Terjadi kesalahan saat mengirim.", "error"); },
      complete: function () { $btn.prop("disabled", false).html('<i class="fas fa-paper-plane mr-1"></i>Kirim Terpilih (<span id="selCount">0</span>)'); updateCount(); },
    });
  });
});
