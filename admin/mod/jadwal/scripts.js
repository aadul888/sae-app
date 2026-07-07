"use strict";

var tableJadwal;

// Fungsi untuk memulai loading
function loading() {
  $(".btn-save").prop("disabled", true);
  $(".btn-save").html(
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
  );
  window.setTimeout(function () {
    $(".btn-save").prop("disabled", false);
    $(".btn-save").html('<i class="far fa-save"></i> Simpan');
  }, 2000);
}

function loadData() {
  if ($.fn.DataTable.isDataTable(".datatable")) {
    tableJadwal = $(".datatable").DataTable();
    tableJadwal.ajax.reload(null, false);
    return;
  }

  tableJadwal = $(".datatable").DataTable({
    scrollY: false,
    scrollX: false,
    processing: true,
    serverSide: false,
    bAutoWidth: true,
    bSort: false,
    bStateSave: true,
    bDestroy: true,
    paging: true,
    ssSorting: [[0, "desc"]],
    iDisplayLength: 25,
    aLengthMenu: [
      [25, 30, 50, -1],
      [25, 30, 50, "All"],
    ],
    language: {
      paginate: {
        previous: "<i class='fas fa-angle-left'>",
        next: "<i class='fas fa-angle-right'>",
      },
    },
    ajax: {
      url: "./mod/jadwal/datatable.php",
      type: "POST",
    },
    columnDefs: [
      {
        targets: [0],
        orderable: false,
      },
      {
        targets: [4],
        orderable: false,
      },
    ],
  });
}

// Fungsi untuk mengubah status jadwal (Aktif/Nonaktif)
$(document).on("click", ".btn-status", function () {
  var id = $(this).data("id");
  var status = $(this).data("status");

  $.ajax({
    type: "POST",
    url: "./mod/jadwal/proses.php?action=update_status",
    data: { id: id, status: status },
    cache: false,
    beforeSend: function () {
      loading();
    },
    success: function (response) {
      var result = typeof response === "string" ? JSON.parse(response) : response;
      if (result.status === "success") {
        swal({
          title: "Berhasil!",
          text: result.message,
          icon: "success",
          timer: 2500,
        });
        loadData(); // Reload tabel setelah update
      } else {
        swal({
          title: "Oops!",
          text: result.message,
          icon: "error",
          timer: 2500,
        });
      }
    },
  });
});

$(document).ready(function () {
  $(".timepicker").timepicker({
    showInputs: false,
    showMeridian: false,
    use24hours: true,
    format: "HH:mm",
  });

  loadData();

  $(document).on("click", ".btn-update", function () {
    var id = $(this).data("id");
    var waktuMulai = $(this).data("waktu-mulai");
    var waktuSelesai = $(this).data("waktu-selesai");

    $("#editModal #edit-id").val(id);
    $("#editModal #edit-waktu-mulai").val(waktuMulai);
    $("#editModal #edit-waktu-selesai").val(waktuSelesai);

    $("#editModal").modal("show");
  });

  $(".btn-save").off("click").on("click", function () {
    $(".btn-save").prop("disabled", true);

    var data = $(".form-edit").serialize();
    $.ajax({
      type: "POST",
      url: "./mod/jadwal/proses.php?action=update",
      data: data,
      cache: false,
      beforeSend: function () {
        loading();
      },
      success: function (response) {
        var result = typeof response === "string" ? JSON.parse(response) : response;
        if (result.status === "success") {
          swal({
            title: "Berhasil!",
            text: result.message,
            icon: "success",
            timer: 2500,
          });
          $(".form-edit").trigger("reset");
          $("#editModal").modal("hide");
          loadData();
        } else {
          swal({
            title: "Oops!",
            text: result.message,
            icon: "error",
            timer: 2500,
          });
        }
      },
      complete: function () {
        $(".btn-save").prop("disabled", false);
      },
    });
    return false;
  });
});
