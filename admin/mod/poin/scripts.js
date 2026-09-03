"use strict";
$(document).ready(function () {
  var prosesUrl = "./mod/poin/proses.php";
  var dtUrl = "./mod/poin/datatable.php";

  // DataTable
  var table = $("#tbl-pelanggaran").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: dtUrl,
      type: "POST",
      data: function (d) {
        d.kelas_id = $("#filter-kelas").val();
        d.status = $("#filter-status").val();
        d.dari = $("#filter-dari").val();
        d.sampai = $("#filter-sampai").val();
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
    order: [[5, "desc"]],
    language: {
      processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>',
      lengthMenu: "Tampilkan _MENU_ data",
      zeroRecords: "Data tidak ditemukan",
      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
      infoEmpty: "Tidak ada data",
      infoFiltered: "(disaring dari _MAX_ total)",
      search: "Cari:",
      paginate: { first: "«", last: "»", next: "›", previous: "‹" }
    },
    scrollX: true,
    scrollCollapse: true,
    responsive: false,
    autoWidth: false
  });

  // Filters
  $("#filter-kelas, #filter-status, #filter-dari, #filter-sampai").on("change", function () {
    table.ajax.reload();
  });

  // Set default date
  var today = new Date().toISOString().split("T")[0];
  $("#pel-tanggal").val(today);

  // === Tambah Pelanggaran ===
  $("#btn-tambah-pelanggaran").on("click", function () {
    $("#modal-pelanggaran-title").html('<i class="fas fa-exclamation-triangle mr-2"></i>Catat Pelanggaran');
    $("#form-pelanggaran")[0].reset();
    $("#pelanggaran-id").val(0);
    $("#pel-tanggal").val(today);
    $("#pel-user-id").val(0);
    $("#pel-murid-selected, #pel-ortu-info").addClass("d-none");
    $("#pel-murid-results").hide().empty();
    $("#pel-cari-murid").val("");
    $("#pel-repeat-warning").addClass("d-none");
    $("#modal-pelanggaran").modal("show");
  });

  // === Search Murid (min 3 chars) ===
  var searchTimer = null;
  $("#pel-cari-murid").on("input", function () {
    var q = $.trim($(this).val());
    clearTimeout(searchTimer);
    if (q.length < 3) { $("#pel-murid-results").hide().empty(); return; }
    searchTimer = setTimeout(function () {
      $.post(prosesUrl, { action: "cari_murid", keyword: q }, function (res) {
        var container = $("#pel-murid-results").empty();
        if (res.status === "success" && res.data.length > 0) {
          res.data.forEach(function (m) {
            container.append(
              '<a href="#" class="list-group-item list-group-item-action py-2 murid-option" data-id="' + m.user_id + '">' +
              '<strong>' + m.nama_lengkap + '</strong> <small class="text-muted">(' + m.nisn + ')</small>' +
              '<br><small class="text-muted">' + (m.nama_kelas || '-') + '</small>' +
              '</a>'
            );
          });
          container.show();
        } else {
          container.append('<div class="list-group-item text-muted py-2">Tidak ditemukan</div>').show();
        }
      }, "json");
    }, 300);
  });

  // Select murid from search results
  $(document).on("click", ".murid-option", function (e) {
    e.preventDefault();
    var user_id = $(this).data("id");
    $("#pel-user-id").val(user_id);
    $("#pel-murid-results").hide().empty();
    $("#pel-cari-murid").val("");

    // Load murid info + ortu
    $.post(prosesUrl, { action: "get_murid_info", user_id: user_id }, function (res) {
      if (res.status === "success") {
        var d = res.data;
        var poinCls = parseInt(d.total_poin) >= 100 ? "text-danger" : (parseInt(d.total_poin) >= 70 ? "text-warning" : "");
        $("#pel-murid-nama").html(
          '<strong>' + d.nama_lengkap + '</strong> <small class="text-muted">(' + d.nisn + ') - ' + (d.nama_kelas || '-') + '</small>' +
          '<br><small>Total Poin: <strong class="' + poinCls + '">' + d.total_poin + '</strong> | Kasus: ' + d.jumlah_kasus + '</small>'
        );
        if (parseInt(d.total_poin) >= 100) {
          $("#pel-murid-selected").removeClass("d-none alert-light").addClass("alert-danger");
        } else {
          $("#pel-murid-selected").removeClass("d-none alert-danger").addClass("alert-light");
        }

        // Ortu info
        var ortuHtml = '';
        if (d.nama_ayah) ortuHtml += '<tr><td width="100"><i class="fas fa-male text-primary mr-1"></i>Ayah</td><td>' + d.nama_ayah + '</td></tr>';
        if (d.nama_ibu) ortuHtml += '<tr><td><i class="fas fa-female text-danger mr-1"></i>Ibu</td><td>' + d.nama_ibu + '</td></tr>';
        if (d.nama_wali) ortuHtml += '<tr><td><i class="fas fa-user-shield text-info mr-1"></i>Wali</td><td>' + d.nama_wali + '</td></tr>';
        if (d.telp_ortu) ortuHtml += '<tr><td><i class="fas fa-phone text-success mr-1"></i>Telepon</td><td><a href="tel:' + d.telp_ortu + '">' + d.telp_ortu + '</a></td></tr>';
        if (ortuHtml) {
          $("#pel-ortu-table").html(ortuHtml);
          $("#pel-ortu-info").removeClass("d-none");
        } else {
          $("#pel-ortu-info").addClass("d-none");
        }
      }
    }, "json");
    checkRepeat();
  });

  // Clear murid selection
  $(document).on("click", ".btn-clear-murid", function () {
    $("#pel-user-id").val(0);
    $("#pel-murid-selected, #pel-ortu-info").addClass("d-none");
    $("#pel-cari-murid").val("");
    $("#pel-repeat-warning").addClass("d-none");
  });

  // Close search results when clicking outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest("#pel-cari-murid, #pel-murid-results").length) {
      $("#pel-murid-results").hide();
    }
  });

  // Ayat change -> set poin + check repeat
  $("#pel-ayat").on("change", function () {
    var opt = $(this).find(":selected");
    var poin = opt.data("poin") || 0;
    $("#pel-poin").val(poin);
    checkRepeat();
  });

  function checkRepeat() {
    var user_id = $("#pel-user-id").val();
    var ayat_id = $("#pel-ayat").val();
    if (!user_id || user_id == '0' || !ayat_id) { $("#pel-repeat-warning").addClass("d-none"); return; }
    $.post(prosesUrl, { action: "check_repeat", user_id: user_id, ayat_id: ayat_id }, function (res) {
      if (res.count > 0) {
        $("#pel-repeat-warning").removeClass("d-none").find("span").text(
          "Murid ini sudah melakukan pelanggaran yang sama sebanyak " + res.count + " kali! (Pengulangan)"
        );
      } else {
        $("#pel-repeat-warning").addClass("d-none");
      }
    }, "json");
  }

  // Submit
  $("#form-pelanggaran").on("submit", function (e) {
    e.preventDefault();
    // Validate murid selected
    if (!$("#pel-user-id").val() || $("#pel-user-id").val() == '0') {
      swal("Perhatian", "Pilih murid terlebih dahulu", "warning"); return;
    }
    // Validate file sizes
    var fotoInput = $("#pel-bukti-foto")[0];
    var videoInput = $("#pel-bukti-video")[0];
    if (fotoInput.files.length > 0 && fotoInput.files[0].size > 2 * 1024 * 1024) {
      swal("Perhatian", "Ukuran foto maksimal 2MB", "warning"); return;
    }
    if (videoInput.files.length > 0 && videoInput.files[0].size > 5 * 1024 * 1024) {
      swal("Perhatian", "Ukuran video maksimal 5MB", "warning"); return;
    }
    var btn = $(this).find('button[type="submit"]');
    btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
    var formData = new FormData(this);
    formData.append("action", "simpan_pelanggaran");
    $.ajax({
      url: prosesUrl,
      type: "POST",
      data: formData,
      dataType: "json",
      processData: false,
      contentType: false,
      success: function (res) {
        if (res.status === "success") {
          var icon = (res.total_poin && res.total_poin >= 100) ? "warning" : "success";
          swal({ title: "Berhasil!", text: res.message, icon: icon }).then(function () {
            $("#modal-pelanggaran").modal("hide");
            table.ajax.reload();
            location.reload();
          });
        } else {
          swal("Gagal", res.message, "error");
          btn.prop("disabled", false).html('<i class="fas fa-save mr-1"></i>Simpan');
        }
      },
      error: function () {
        swal("Error", "Terjadi kesalahan koneksi", "error");
        btn.prop("disabled", false).html('<i class="fas fa-save mr-1"></i>Simpan');
      }
    });
  });

  // Detail
  $(document).on("click", ".btn-detail-pel", function () {
    var id = $(this).data("id");
    $("#detail-content").html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    $("#modal-detail").modal("show");
    $.post(prosesUrl, { action: "detail_pelanggaran", pelanggaran_id: id }, function (res) {
      if (res.status === "success") {
        var d = res.data;
        var kat_cls = "success"; if (d.kategori === "Sedang") kat_cls = "warning"; else if (d.kategori === "Berat") kat_cls = "danger"; else if (d.kategori === "Sangat Berat") kat_cls = "dark";
        var status_cls = "success"; if (d.status === "Disanggah") status_cls = "info"; else if (d.status === "Dikurangi") status_cls = "warning"; else if (d.status === "Dihapus") status_cls = "secondary";

        var html = '<div class="row">';
        html += '<div class="col-md-6"><h5 class="text-primary"><i class="fas fa-user mr-1"></i>Data Murid</h5><table class="table table-sm table-borderless">';
        html += '<tr><td width="120">Nama</td><td><strong>' + d.nama_lengkap + '</strong></td></tr>';
        html += '<tr><td>NISN</td><td>' + d.nisn + '</td></tr>';
        html += '<tr><td>Kelas</td><td>' + (d.nama_kelas||'-') + '</td></tr>';
        html += '<tr><td>Orang Tua</td><td>' + (d.nama_ayah||'-') + ' / ' + (d.nama_ibu||'-') + '</td></tr>';
        html += '<tr><td>Telp Ortu</td><td>' + (d.telp_ortu||'-') + '</td></tr>';
        html += '<tr><td>Total Poin</td><td><span class="badge badge-' + (d.total_poin_siswa >= 100 ? 'danger' : (d.total_poin_siswa >= 70 ? 'warning' : 'primary')) + '" style="font-size:16px">' + d.total_poin_siswa + '</span></td></tr>';
        html += '</table></div>';

        html += '<div class="col-md-6"><h5 class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Detail Pelanggaran</h5><table class="table table-sm table-borderless">';
        html += '<tr><td width="120">Pasal</td><td>' + d.kode_pasal + ' - ' + d.nama_pasal + '</td></tr>';
        html += '<tr><td>Ayat</td><td>' + d.kode_ayat + '</td></tr>';
        html += '<tr><td>Pelanggaran</td><td><strong>' + d.jenis_pelanggaran + '</strong></td></tr>';
        html += '<tr><td>Kategori</td><td><span class="badge badge-' + kat_cls + '">' + d.kategori + '</span></td></tr>';
        html += '<tr><td>Poin</td><td><span class="badge badge-danger" style="font-size:16px">' + d.poin_diberikan + '</span></td></tr>';
        html += '<tr><td>Tanggal</td><td>' + d.tanggal_kejadian + '</td></tr>';
        html += '<tr><td>Status</td><td><span class="badge badge-' + status_cls + '">' + d.status + '</span></td></tr>';
        html += '<tr><td>Semester</td><td>' + (d.nama_semester||'-') + '</td></tr>';
        if (d.is_pengulangan === 'Y') html += '<tr><td>Pengulangan</td><td><span class="badge badge-warning">Ke-' + d.jumlah_pengulangan + '</span></td></tr>';
        if (d.keterangan) html += '<tr><td>Keterangan</td><td>' + d.keterangan + '</td></tr>';
        html += '<tr><td>Dicatat oleh</td><td>' + (d.nama_admin||'-') + '</td></tr>';
        html += '</table></div></div>';

        // Bukti foto/video
        if (d.bukti_foto || d.bukti_video) {
          html += '<hr><h5><i class="fas fa-camera mr-1"></i>Bukti</h5><div class="row">';
          if (d.bukti_foto) html += '<div class="col-md-6 mb-2"><img src="../content/pelanggaran/foto/' + d.bukti_foto + '" class="img-fluid rounded shadow-sm" style="max-height:300px;cursor:pointer;" onclick="window.open(this.src)"></div>';
          if (d.bukti_video) html += '<div class="col-md-6 mb-2"><video controls class="w-100 rounded shadow-sm" style="max-height:300px;"><source src="../content/pelanggaran/video/' + d.bukti_video + '"></video></div>';
          html += '</div>';
        }

        if (d.sanggah_list && d.sanggah_list.length > 0) {
          html += '<hr><h5><i class="fas fa-hand-paper mr-1"></i>Riwayat Sanggahan</h5>';
          html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Poin Dikurangi</th></tr></thead><tbody>';
          d.sanggah_list.forEach(function(s) {
            html += '<tr><td>' + s.tanggal_pengajuan + '</td><td>' + s.jenis_sanggah + '</td><td>' + (s.alasan||'-') + '</td><td>' + s.status + '</td><td>' + s.poin_dikurangi + '</td></tr>';
          });
          html += '</tbody></table></div>';
        }

        $("#detail-content").html(html);
      } else {
        $("#detail-content").html('<p class="text-danger text-center">' + res.message + '</p>');
      }
    }, "json");
  });

  // Hapus
  $(document).on("click", ".btn-hapus-pel", function () {
    var id = $(this).data("id");
    swal({
      title: "Hapus Pelanggaran?",
      text: "Data akan diubah statusnya menjadi 'Dihapus'",
      icon: "warning",
      buttons: { cancel: "Batal", confirm: { text: "Ya, Hapus!", className: "btn-danger" } },
      dangerMode: true
    }).then(function (ok) {
      if (ok) {
        $.post(prosesUrl, { action: "hapus_pelanggaran", pelanggaran_id: id }, function (res) {
          if (res.status === "success") {
            swal({ title: "Berhasil!", text: res.message, icon: "success", timer: 1500 }).then(function () { table.ajax.reload(); });
          } else { swal("Gagal", res.message, "error"); }
        }, "json");
      }
    });
  });
});
