"use strict";

var dtInstansi, dtTujuan;

function loadInstansi() {
  dtInstansi = $("#tableInstansi").DataTable({
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    ajax: {
      url: "./mod/tamu-referensi/datatable.php",
      type: "POST",
      data: function (d) { d.jenis = "instansi"; },
    },
    columns: [
      { title: "No", className: "text-center", orderable: false },
      { title: "Nama Instansi" },
      { title: "Jenis", className: "text-center" },
      { title: "Telepon", className: "text-center" },
      { title: "Alamat" },
      { title: "Status", className: "text-center" },
      { title: "Aksi", className: "text-center", orderable: false },
    ],
    language: {
      paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" },
    },
  });
}

function loadTujuan() {
  dtTujuan = $("#tableTujuan").DataTable({
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    ajax: {
      url: "./mod/tamu-referensi/datatable.php",
      type: "POST",
      data: function (d) { d.jenis = "tujuan"; },
    },
    columns: [
      { title: "No", className: "text-center", orderable: false },
      { title: "Nama Tujuan" },
      { title: "Keterangan" },
      { title: "Status", className: "text-center" },
      { title: "Aksi", className: "text-center", orderable: false },
    ],
    language: {
      paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" },
    },
  });
}

$(document).ready(function () {
  loadInstansi();
  // Tujuan tab initialised on first show so DataTable sizes columns correctly
  $('a[href="#tab-tujuan"]').on("shown.bs.tab", function () {
    if (!dtTujuan) { loadTujuan(); } else { dtTujuan.columns.adjust(); }
  });
});

function postRef(url, data, reloadFn) {
  $.ajax({
    url: url,
    type: "POST",
    data: data,
    success: function (res) {
      swal("Info", res, res.includes("berhasil") ? "success" : "error");
      if (res.includes("berhasil")) {
        $(".modal").modal("hide");
        if (reloadFn) reloadFn();
      }
    },
  });
}

/* ---------------- INSTANSI ---------------- */
$(document).on("submit", "#formTambahInstansi", function (e) {
  e.preventDefault();
  var form = $(this);
  postRef("./mod/tamu-referensi/proses.php?action=tambah_instansi", form.serialize(), function () {
    form[0].reset();
    dtInstansi.ajax.reload(null, false);
  });
});

$(document).on("click", ".btn-edit-instansi", function () {
  var el = $(this);
  $("#ei_id").val(el.data("id"));
  $("#ei_nama").val(el.data("nama"));
  $("#ei_jenis").val(el.data("jenis"));
  $("#ei_telepon").val(el.data("telepon"));
  $("#ei_email").val(el.data("email"));
  $("#ei_alamat").val(el.data("alamat"));
  $("#ei_active").val(el.data("active"));
  $("#modalEditInstansi").modal("show");
});

$(document).on("submit", "#formEditInstansi", function (e) {
  e.preventDefault();
  postRef("./mod/tamu-referensi/proses.php?action=edit_instansi", $(this).serialize(), function () {
    dtInstansi.ajax.reload(null, false);
  });
});

$(document).on("click", ".btn-delete-instansi", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Instansi?",
    text: "Data yang dihapus tidak dapat dikembalikan!",
    icon: "warning",
    dangerMode: true,
    buttons: { cancel: "Batal", confirm: { text: "Ya, Hapus", value: true } },
  }).then(function (ok) {
    if (ok) {
      postRef("./mod/tamu-referensi/proses.php?action=hapus_instansi", { id: id }, function () {
        dtInstansi.ajax.reload(null, false);
      });
    }
  });
});

/* ---------------- TUJUAN ---------------- */
$(document).on("submit", "#formTambahTujuan", function (e) {
  e.preventDefault();
  var form = $(this);
  postRef("./mod/tamu-referensi/proses.php?action=tambah_tujuan", form.serialize(), function () {
    form[0].reset();
    if (dtTujuan) dtTujuan.ajax.reload(null, false);
  });
});

$(document).on("click", ".btn-edit-tujuan", function () {
  var el = $(this);
  $("#et_id").val(el.data("id"));
  $("#et_nama").val(el.data("nama"));
  $("#et_keterangan").val(el.data("keterangan"));
  $("#et_active").val(el.data("active"));
  $("#modalEditTujuan").modal("show");
});

$(document).on("submit", "#formEditTujuan", function (e) {
  e.preventDefault();
  postRef("./mod/tamu-referensi/proses.php?action=edit_tujuan", $(this).serialize(), function () {
    if (dtTujuan) dtTujuan.ajax.reload(null, false);
  });
});

$(document).on("click", ".btn-delete-tujuan", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Tujuan?",
    text: "Data yang dihapus tidak dapat dikembalikan!",
    icon: "warning",
    dangerMode: true,
    buttons: { cancel: "Batal", confirm: { text: "Ya, Hapus", value: true } },
  }).then(function (ok) {
    if (ok) {
      postRef("./mod/tamu-referensi/proses.php?action=hapus_tujuan", { id: id }, function () {
        if (dtTujuan) dtTujuan.ajax.reload(null, false);
      });
    }
  });
});
