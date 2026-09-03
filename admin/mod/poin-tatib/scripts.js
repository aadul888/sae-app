"use strict";
$(document).ready(function () {
  var prosesUrl = "./mod/poin-tatib/proses.php";
  var dtUrl     = "./mod/poin-tatib/datatable.php";

  // ========== DATATABLE ==========
  var table = $("#tbl-tatib").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: dtUrl,
      type: "POST",
      data: function (d) {
        d.pasal_id = $("#filter-pasal").val();
        d.kategori = $("#filter-kategori").val();
        d.aktif    = $("#filter-aktif").val();
      }
    },
    columns: [
      { className: "text-center" },
      null,
      null,
      null,
      { className: "text-center" },
      { className: "text-center" },
      { className: "text-center" },
      { className: "text-center", orderable: false }
    ],
    order: [[2, "asc"]],
    language: {
      processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>',
      lengthMenu: "Tampilkan _MENU_ data",
      zeroRecords: "Data tidak ditemukan",
      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
      infoEmpty: "Tidak ada data",
      infoFiltered: "(disaring dari _MAX_ total)",
      search: "Cari:",
      paginate: { first: "Â«", last: "Â»", next: "â€º", previous: "â€¹" }
    },
    scrollX: true,
    scrollCollapse: true,
    responsive: false,
    autoWidth: false
  });

  // ========== FILTER ==========
  $(".btn-apply-filter-tatib").on("click", function () {
    table.ajax.reload();
    $("#modalFilterTatib").modal("hide");
  });
  $(".btn-reset-filter-tatib").on("click", function () {
    $("#filter-pasal, #filter-kategori, #filter-aktif").val("");
    table.ajax.reload();
    $("#modalFilterTatib").modal("hide");
  });

  // ========== HELPERS ==========
  function resetModal(title, activeTab) {
    $("#form-pasal")[0].reset();
    $("#pasal-id").val(0);
    $("#btn-hapus-pasal-modal").hide();
    $("#form-ayat")[0].reset();
    $("#ayat-id").val(0);
    $("#ayat-poin").val(10);
    $("#modal-tatib-title").html(title);
    if (activeTab === "ayat") {
      $("#tab-ayat-link").tab("show");
    } else {
      $("#tab-pasal-link").tab("show");
    }
    $("#modal-tatib").modal("show");
  }

  // ========== TAMBAH ==========
  $("#btn-tambah-tatib").on("click", function () {
    resetModal('<i class="fas fa-plus-circle mr-2"></i>Tambah Data', "pasal");
  });

  // ========== EDIT PASAL ==========
  $(document).on("click", ".btn-edit-pasal", function () {
    resetModal('<i class="fas fa-book mr-2"></i>Edit Pasal', "pasal");
    var id = $(this).data("id");
    $("#pasal-id").val(id);
    $("#pasal-kode").val($(this).data("kode"));
    $("#pasal-nama").val($(this).data("nama"));
    $("#pasal-deskripsi").val($(this).data("deskripsi"));
    $("#pasal-urutan").val($(this).data("urutan"));
    $("#pasal-aktif").val($(this).data("aktif"));
    $("#btn-hapus-pasal-modal").show().data("pasal-id", id);
  });

  // ========== EDIT AYAT ==========
  $(document).on("click", ".btn-edit-ayat", function () {
    resetModal('<i class="fas fa-list mr-2"></i>Edit Ayat', "ayat");
    $("#ayat-id").val($(this).data("id"));
    $("#ayat-pasal-id").val($(this).data("pasal"));
    $("#ayat-kode").val($(this).data("kode"));
    $("#ayat-jenis").val($(this).data("jenis"));
    $("#ayat-deskripsi").val($(this).data("deskripsi"));
    $("#ayat-kategori").val($(this).data("kategori"));
    $("#ayat-poin").val($(this).data("poin"));
    $("#ayat-urutan").val($(this).data("urutan"));
    $("#ayat-aktif").val($(this).data("aktif"));
  });

  // ========== SUBMIT PASAL ==========
  $("#form-pasal").on("submit", function (e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
    $.ajax({
      url: prosesUrl,
      type: "POST",
      data: $(this).serialize() + "&action=simpan_pasal",
      dataType: "json",
      success: function (res) {
        if (res.status === "success") {
          $("#modal-tatib").modal("hide");
          swal({ title: "Berhasil!", text: res.message, icon: "success", timer: 1500 }).then(function () { table.ajax.reload(); });
        } else {
          swal("Gagal", res.message, "error");
          btn.prop("disabled", false).html('<i class="fas fa-save mr-1"></i>Simpan Pasal');
        }
      },
      error: function () {
        swal("Error", "Terjadi kesalahan koneksi", "error");
        btn.prop("disabled", false).html('<i class="fas fa-save mr-1"></i>Simpan Pasal');
      }
    });
  });

  // ========== SUBMIT AYAT ==========
  $("#form-ayat").on("submit", function (e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
    $.ajax({
      url: prosesUrl,
      type: "POST",
      data: $(this).serialize() + "&action=simpan_ayat",
      dataType: "json",
      success: function (res) {
        if (res.status === "success") {
          $("#modal-tatib").modal("hide");
          swal({ title: "Berhasil!", text: res.message, icon: "success", timer: 1500 }).then(function () { table.ajax.reload(); });
        } else {
          swal("Gagal", res.message, "error");
          btn.prop("disabled", false).html('<i class="fas fa-save mr-1"></i>Simpan Ayat');
        }
      },
      error: function () {
        swal("Error", "Terjadi kesalahan koneksi", "error");
        btn.prop("disabled", false).html('<i class="fas fa-save mr-1"></i>Simpan Ayat');
      }
    });
  });

  // ========== HAPUS PASAL (from modal) ==========
  $("#btn-hapus-pasal-modal").on("click", function () {
    var id = $(this).data("pasal-id");
    $("#modal-tatib").modal("hide");
    swal({
      title: "Hapus Pasal?",
      text: "Semua ayat di bawah pasal ini juga akan terhapus!",
      icon: "warning",
      buttons: { cancel: "Batal", confirm: { text: "Ya, Hapus!", className: "btn-danger" } },
      dangerMode: true
    }).then(function (ok) {
      if (ok) {
        $.post(prosesUrl, { action: "hapus_pasal", pasal_id: id }, function (res) {
          if (res.status === "success") {
            swal({ title: "Terhapus!", text: res.message, icon: "success", timer: 1500 }).then(function () { table.ajax.reload(); });
          } else { swal("Gagal", res.message, "error"); }
        }, "json");
      }
    });
  });

  // ========== HAPUS AYAT ==========
  $(document).on("click", ".btn-hapus-ayat", function () {
    var id = $(this).data("id");
    swal({
      title: "Hapus Ayat?",
      text: "Data ayat pelanggaran ini akan dihapus permanen",
      icon: "warning",
      buttons: { cancel: "Batal", confirm: { text: "Ya, Hapus!", className: "btn-danger" } },
      dangerMode: true
    }).then(function (ok) {
      if (ok) {
        $.post(prosesUrl, { action: "hapus_ayat", ayat_id: id }, function (res) {
          if (res.status === "success") {
            swal({ title: "Terhapus!", text: res.message, icon: "success", timer: 1500 }).then(function () { table.ajax.reload(); });
          } else { swal("Gagal", res.message, "error"); }
        }, "json");
      }
    });
  });

});
