"use strict";

$(document).ready(function () {
  // DataTable untuk inventaris
  if ($.fn.DataTable && $("#tbl-inventaris").length) {
    $("#tbl-inventaris").DataTable({
      responsive: true,
      pageLength: 25,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        emptyTable: "Belum ada data inventaris",
        paginate: {
          previous: "<i class='fas fa-angle-left'></i>",
          next: "<i class='fas fa-angle-right'></i>",
        },
      },
    });
  }

  // DataTable untuk riwayat laporan
  if ($.fn.DataTable && $("#tbl-riwayat").length) {
    $("#tbl-riwayat").DataTable({
      responsive: true,
      pageLength: 10,
      order: [[6, "desc"]],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        emptyTable: "Belum ada laporan",
        paginate: {
          previous: "<i class='fas fa-angle-left'></i>",
          next: "<i class='fas fa-angle-right'></i>",
        },
      },
    });
  }

  // Filter barang berdasarkan kategori
  $("#sel-kategori").on("change", function () {
    var katId = $(this).val();
    var $selBarang = $("#sel-barang");
    $selBarang.find("option").each(function () {
      var opt = $(this);
      if (!opt.val()) return;
      if (!katId || opt.data("kategori") == katId) {
        opt.show();
      } else {
        opt.hide();
      }
    });
    $selBarang.val("");
  });

  // ====== FORM INVENTARIS (AJAX) ======
  $("#form-inventaris").on("submit", function (e) {
    e.preventDefault();
    var form = $(this);
    var btn = form.find("button[type=submit]");
    var formData = new FormData(this);

    btn.prop("disabled", true).html(
      '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'
    );

    $.ajax({
      url: "mod/invetaris-kelas/proses.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (res) {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-1"></i> Simpan Data');
        if (res.status === "success") {
          swal("Berhasil!", res.message, "success").then(function () {
            location.reload();
          });
        } else {
          swal("Gagal!", res.message, "error");
        }
      },
      error: function () {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-1"></i> Simpan Data');
        swal("Error!", "Terjadi kesalahan koneksi.", "error");
      },
    });
  });

  // ====== FORM LAPORAN (AJAX) ======
  $("#form-laporan").on("submit", function (e) {
    e.preventDefault();
    var form = $(this);
    var btn = form.find("button[type=submit]");
    var formData = new FormData(this);

    btn.prop("disabled", true).html(
      '<i class="fas fa-spinner fa-spin"></i> Mengirim...'
    );

    $.ajax({
      url: "mod/invetaris-kelas/proses.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (res) {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-paper-plane mr-1"></i> Kirim Laporan');
        if (res.status === "success") {
          swal("Berhasil!", res.message, "success").then(function () {
            location.reload();
          });
        } else {
          swal("Gagal!", res.message, "error");
        }
      },
      error: function () {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-paper-plane mr-1"></i> Kirim Laporan');
        swal("Error!", "Terjadi kesalahan koneksi.", "error");
      },
    });
  });

  // ====== EDIT INVENTARIS ======
  $(document).on("click", ".btn-edit-inv", function () {
    var el = $(this);
    $("#edit-inv-id").val(el.data("id"));
    $("#edit-jumlah").val(el.data("jumlah"));
    $("#edit-kondisi").val(el.data("kondisi"));
    $("#edit-keterangan").val(el.data("keterangan"));
    $("#modal-edit-inv").modal("show");
  });

  $("#form-edit-inv").on("submit", function (e) {
    e.preventDefault();
    var btn = $(this).find("button[type=submit]");
    btn.prop("disabled", true).html(
      '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'
    );

    $.ajax({
      url: "mod/invetaris-kelas/proses.php",
      type: "POST",
      data: {
        action: "edit_inventaris",
        inv_id: $("#edit-inv-id").val(),
        jumlah: $("#edit-jumlah").val(),
        kondisi: $("#edit-kondisi").val(),
        keterangan: $("#edit-keterangan").val(),
      },
      dataType: "json",
      success: function (res) {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-1"></i> Simpan');
        $("#modal-edit-inv").modal("hide");
        if (res.status === "success") {
          swal("Berhasil!", res.message, "success").then(function () {
            location.reload();
          });
        } else {
          swal("Gagal!", res.message, "error");
        }
      },
      error: function () {
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save mr-1"></i> Simpan');
        swal("Error!", "Terjadi kesalahan koneksi.", "error");
      },
    });
  });

  // ====== HAPUS INVENTARIS ======
  $(document).on("click", ".btn-hapus-inv", function () {
    var id = $(this).data("id");
    swal({
      title: "Hapus Data?",
      text: "Data inventaris ini akan dihapus permanen.",
      icon: "warning",
      buttons: {
        cancel: "Batal",
        confirm: {
          text: "Hapus",
          value: true,
          closeModal: true,
        },
      },
      dangerMode: true,
    }).then(function (willDelete) {
      if (willDelete) {
        $.ajax({
          url: "mod/invetaris-kelas/proses.php",
          type: "POST",
          data: { action: "hapus_inventaris", inv_id: id },
          dataType: "json",
          success: function (res) {
            if (res.status === "success") {
              swal("Berhasil!", res.message, "success").then(function () {
                location.reload();
              });
            } else {
              swal("Gagal!", res.message, "error");
            }
          },
          error: function () {
            swal("Error!", "Terjadi kesalahan koneksi.", "error");
          },
        });
      }
    });
  });
});
