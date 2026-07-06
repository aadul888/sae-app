"use strict";

$(document).ready(function () {
  // DataTables
  if ($.fn.DataTable) {
    var dtOpts = {
      pageLength: 25,
      scrollX: true,
      scrollCollapse: true,
      responsive: false,
      autoWidth: false,
      language: {
        lengthMenu: "Tampilkan _MENU_ data",
        zeroRecords: "Data tidak ditemukan",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        search: "Cari:",
        paginate: { previous: "<i class='fas fa-angle-left'></i>", next: "<i class='fas fa-angle-right'></i>" },
      },
    };
    $("#tbl-kategori").DataTable($.extend({}, dtOpts, { language: $.extend({}, dtOpts.language, { emptyTable: "Belum ada data kategori" }) }));
    $("#tbl-barang").DataTable($.extend({}, dtOpts, { language: $.extend({}, dtOpts.language, { emptyTable: "Belum ada data barang" }) }));
  }

  // Header tambah buttons — switch to correct tab then open modal
  $("#btn-add-kategori-hdr").on("click", function () {
    $("#tab-kategori").tab("show");
    $("#btn-add-kategori").trigger("click-open");
    $("#kat-id").val("");
    $("#kat-action").val("tambah_kategori");
    $("#kat-nama").val("");
    $("#kat-keterangan").val("");
    $("#modal-kategori-title").html('<i class="fas fa-folder mr-2"></i>Tambah Kategori');
    $("#modal-kategori").modal("show");
  });
  $("#btn-add-barang-hdr").on("click", function () {
    $("#tab-barang").tab("show");
    $("#brg-id").val("");
    $("#brg-action").val("tambah_barang");
    $("#brg-kategori").val("");
    $("#brg-kode").val("");
    $("#brg-nama").val("");
    $("#brg-satuan").val("Unit");
    $("#brg-keterangan").val("");
    $("#modal-barang-title").html('<i class="fas fa-boxes mr-2"></i>Tambah Barang');
    $("#modal-barang").modal("show");
  });

  // ====== KATEGORI ======
  $(document).on("click", ".btn-edit-kategori", function () {
    var el = $(this);
    $("#kat-id").val(el.data("id"));
    $("#kat-action").val("edit_kategori");
    $("#kat-nama").val(el.data("nama"));
    $("#kat-keterangan").val(el.data("keterangan"));
    $("#modal-kategori-title").html(
      '<i class="fas fa-edit mr-2"></i>Edit Kategori'
    );
    $("#modal-kategori").modal("show");
  });

  $("#form-kategori").on("submit", function (e) {
    e.preventDefault();
    var btn = $(this).find("button[type=submit]");
    btn.prop("disabled", true).html(
      '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'
    );

    $.ajax({
      url: "./mod/inv-master/proses.php",
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function (res) {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-1"></i> Simpan');
        $("#modal-kategori").modal("hide");
        swal(
          res.status === "success" ? "Berhasil!" : "Gagal!",
          res.message,
          res.status === "success" ? "success" : "error"
        ).then(function () {
          if (res.status === "success") location.reload();
        });
      },
      error: function () {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-1"></i> Simpan');
        swal("Error!", "Terjadi kesalahan koneksi.", "error");
      },
    });
  });

  $(document).on("click", ".btn-hapus-kategori", function () {
    var id = $(this).data("id");
    swal({
      title: "Hapus Kategori?",
      text: "Kategori yang memiliki barang tidak bisa dihapus.",
      icon: "warning",
      buttons: { cancel: "Batal", confirm: { text: "Hapus", closeModal: true } },
      dangerMode: true,
    }).then(function (ok) {
      if (ok) {
        $.ajax({
          url: "./mod/inv-master/proses.php",
          type: "POST",
          data: { action: "hapus_kategori", kategori_id: id },
          dataType: "json",
          success: function (res) {
            swal(
              res.status === "success" ? "Berhasil!" : "Gagal!",
              res.message,
              res.status === "success" ? "success" : "error"
            ).then(function () {
              if (res.status === "success") location.reload();
            });
          },
        });
      }
    });
  });

  // ====== BARANG ======
  $(document).on("click", ".btn-edit-barang", function () {
    var el = $(this);
    $("#brg-id").val(el.data("id"));
    $("#brg-action").val("edit_barang");
    $("#brg-kategori").val(el.data("kategori"));
    $("#brg-kode").val(el.data("kode"));
    $("#brg-nama").val(el.data("nama"));
    $("#brg-satuan").val(el.data("satuan"));
    $("#brg-keterangan").val(el.data("keterangan"));
    $("#modal-barang-title").html(
      '<i class="fas fa-edit mr-2"></i>Edit Barang'
    );
    $("#modal-barang").modal("show");
  });

  $("#form-barang").on("submit", function (e) {
    e.preventDefault();
    var btn = $(this).find("button[type=submit]");
    btn.prop("disabled", true).html(
      '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'
    );

    $.ajax({
      url: "./mod/inv-master/proses.php",
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function (res) {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-1"></i> Simpan');
        $("#modal-barang").modal("hide");
        swal(
          res.status === "success" ? "Berhasil!" : "Gagal!",
          res.message,
          res.status === "success" ? "success" : "error"
        ).then(function () {
          if (res.status === "success") location.reload();
        });
      },
      error: function () {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-1"></i> Simpan');
        swal("Error!", "Terjadi kesalahan koneksi.", "error");
      },
    });
  });

  $(document).on("click", ".btn-hapus-barang", function () {
    var id = $(this).data("id");
    swal({
      title: "Hapus Barang?",
      text: "Barang yang sudah digunakan di inventaris kelas tidak bisa dihapus.",
      icon: "warning",
      buttons: { cancel: "Batal", confirm: { text: "Hapus", closeModal: true } },
      dangerMode: true,
    }).then(function (ok) {
      if (ok) {
        $.ajax({
          url: "./mod/inv-master/proses.php",
          type: "POST",
          data: { action: "hapus_barang", barang_id: id },
          dataType: "json",
          success: function (res) {
            swal(
              res.status === "success" ? "Berhasil!" : "Gagal!",
              res.message,
              res.status === "success" ? "success" : "error"
            ).then(function () {
              if (res.status === "success") location.reload();
            });
          },
        });
      }
    });
  });
});
