"use strict";

var tableLokasi;
window.map = window.map || null;
window.marker = window.marker || null;

// Fungsi loading
function loading() {
  $(".btn-save").prop("disabled", true);
  $(".btn-save").html(
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
  );
  setTimeout(() => {
    $(".btn-save").prop("disabled", false);
    $(".btn-save").html('<i class="far fa-save"></i> Simpan');
  }, 2000);
}

function loadData() {
  if ($.fn.DataTable.isDataTable(".datatable")) {
    tableLokasi = $(".datatable").DataTable();
    tableLokasi.ajax.reload(null, false);
    return;
  }

  tableLokasi = $(".datatable").DataTable({
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
      url: "./mod/absensi-lokasi/datatable.php",
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

$(document).ready(function () {
  var $modalAdd = $(".modal-add");
  if ($modalAdd.length) {
    $modalAdd.appendTo("body");
  }
  loadData();
});

// Modal Tambah Lokasi
$(document).on("click", ".btn-add", function () {
  $(".modal-title").html("Tambah Lokasi");
  $(".form-add").trigger("reset");
  $(".modal-add").modal("show");
  $(".id").val("");
});

// Validasi dan Submit Form
$(".form-add").validate({
  rules: {
    nama_lokasi: { required: true },
    latitude: { required: true, number: true },
    longitude: { required: true, number: true },
    radius: { required: true, number: true },
  },
  messages: {
    nama_lokasi: { required: "Nama lokasi wajib diisi" },
    latitude: { required: "Latitude wajib diisi", number: "Harus angka" },
    longitude: { required: "Longitude wajib diisi", number: "Harus angka" },
    radius: { required: "Radius wajib diisi", number: "Harus angka" },
  },
  submitHandler: submitForm_Add,
});

function submitForm_Add() {
  let data = $(".form-add").serialize();
  $.ajax({
    type: "POST",
    url: "./mod/absensi-lokasi/proses.php?action=simpan",
    data: data,
    cache: false,
    beforeSend: loading,
    success: function (response) {
      response = typeof response === "string" ? JSON.parse(response) : response;
      if (response.success) {
        swal({
          title: "Berhasil!",
          text: "Data lokasi disimpan.",
          icon: "success",
          timer: 2000,
        });
        $(".form-add").trigger("reset");
        $(".modal-add").modal("hide");
        loadData();
      } else {
        swal({
          title: "Gagal!",
          text: response.error || "Terjadi kesalahan!",
          icon: "error",
          timer: 2500,
        });
      }
    },
  });
  return false;
}

// Modal Edit Lokasi
$(document).on("click", ".btn-edit", function () {
  $(".modal-title").html("Edit Lokasi");
  $(".form-add").trigger("reset");
  $(".modal-add").modal("show");

  $(".id").val($(this).data("id"));
  $("[name=nama_lokasi]").val($(this).data("nama"));
  $("[name=keterangan]").val($(this).data("ket"));
  $("[name=latitude]").val($(this).data("lat"));
  $("[name=longitude]").val($(this).data("lng"));
  $("[name=radius]").val($(this).data("radius"));
  $("[name=status]").val($(this).data("status"));

  setTimeout(() => {
    initializeMap();
  }, 400);
});

// Hapus Lokasi
$(document).on("click", ".btn-delete", function () {
  var id = $(this).data("id");
  var name = $(this).data("name");

  swal({
    title: "Yakin ingin menghapus lokasi?",
    text: name,
    icon: "warning",
    buttons: true,
    dangerMode: true,
  }).then((willDelete) => {
    if (willDelete) {
      $.ajax({
        type: "POST",
        url: "./mod/absensi-lokasi/proses.php?action=delete",
        data: { lokasi_id: id },
        cache: false,
        success: function (response) {
          response = typeof response === "string" ? JSON.parse(response) : response;
          if (response.success) {
            swal({
              title: "Berhasil!",
              text: "Lokasi berhasil dihapus.",
              icon: "success",
              timer: 2000,
            });
            loadData();
          } else {
            swal({
              title: "Gagal!",
              text: response.error,
              icon: "error",
              timer: 2500,
            });
          }
        },
      });
    }
  });
});

// Tampilkan peta saat Tambah Lokasi
$(document).on("click", ".btn-add", function () {
  setTimeout(() => {
    initializeMap();
    const lat = parseFloat($('[name="latitude"]').val()) || -6.9;
    const lng = parseFloat($('[name="longitude"]').val()) || 107.3;
    const latlng = L.latLng(lat, lng);
    window.map.setView(latlng, 13);
    if (window.marker) {
      window.marker.setLatLng(latlng);
    } else {
      window.marker = L.marker(latlng).addTo(window.map);
    }
  }, 500);
});

// Tampilkan peta saat Edit Lokasi
function initializeMap() {
  if (!window.map) {
    window.map = L.map("map").setView([-6.9, 107.3], 13); // default view
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "&copy; OpenStreetMap",
    }).addTo(window.map);

    window.map.on("click", function (e) {
      const lat = e.latlng.lat.toFixed(8);
      const lng = e.latlng.lng.toFixed(8);
      $('[name="latitude"]').val(lat);
      $('[name="longitude"]').val(lng);

      if (window.marker) {
        window.marker.setLatLng(e.latlng);
      } else {
        window.marker = L.marker(e.latlng).addTo(window.map);
      }

      window.map.setView(e.latlng, 16);
    });
  }

  // Pastikan peta merender ulang dan pindah ke posisi input koordinat
  setTimeout(() => {
    window.map.invalidateSize();

    const lat = parseFloat($('[name="latitude"]').val());
    const lng = parseFloat($('[name="longitude"]').val());

    if (!isNaN(lat) && !isNaN(lng)) {
      const latlng = L.latLng(lat, lng);
      window.map.setView(latlng, 16);
      if (window.marker) {
        window.marker.setLatLng(latlng);
      } else {
        window.marker = L.marker(latlng).addTo(window.map);
      }
    }
  }, 300);
}

// Auto tampilkan marker saat koordinat diisi manual
$('[name="latitude"], [name="longitude"]').on("input", function () {
  const lat = parseFloat($('[name="latitude"]').val());
  const lng = parseFloat($('[name="longitude"]').val());

  if (!isNaN(lat) && !isNaN(lng)) {
    const latlng = L.latLng(lat, lng);
    if (!window.map) initializeMap();
    window.map.setView(latlng, 16);

    if (window.marker) {
      window.marker.setLatLng(latlng);
    } else {
      window.marker = L.marker(latlng).addTo(window.map);
    }
  }
});
