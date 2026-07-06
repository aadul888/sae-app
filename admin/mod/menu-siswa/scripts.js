(function ($) {
  $(function () {
    var table = null;

    function initTable() {
      if (table) {
        table.destroy();
      }
      table = $("#menu-siswa-table").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "./mod/menu-siswa/datatable.php",
          type: "GET",
        },
        ordering: true,
        scrollX: true,
        scrollCollapse: true,
        responsive: false,
        autoWidth: false,
        // Use simple pagination with icons for previous/next
        pagingType: "simple_numbers",
        language: {
          lengthMenu: "Tampilkan _MENU_ data",
          zeroRecords: "Data tidak ditemukan",
          info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
          infoEmpty: "Tidak ada data",
          search: "Cari:",
          paginate: {
            previous: "<i class='fas fa-angle-left'></i>",
            next: "<i class='fas fa-angle-right'></i>",
          },
        },
        // Table columns: No, Menu (label), Status, Action
        columns: [
          { orderable: false }, // No
          null, // Menu/label
          { orderable: false }, // Status (switch)
          { orderable: false }, // Action buttons
        ],
        drawCallback: function (settings) {
          // bind toggles and actions
          bindToggle();
          bindActions();
        },
      });
    }

    function bindToggle() {
      $(".student-menu-toggle")
        .off("change")
        .on("change", function () {
          var id = $(this).data("id");
          var aktif = $(this).is(":checked") ? "Y" : "N";
          $.post(
            "./mod/menu-siswa/proses.php?action=toggle",
            { id: id, aktif: aktif },
            function (resp) {
              if ($.trim(resp) !== "success") {
                alert("Gagal menyimpan status: " + resp);
              }
            }
          ).fail(function () {
            alert("Gagal menghubungi server.");
          });
        });
    }

    function bindActions() {
      $(".btn-update")
        .off("click")
        .on("click", function () {
          var id = $(this).data("id");
          var label = $(this).data("label");
          var slug = $(this).data("slug");
          var position = $(this).data("position");
          $("#menuSiswaModalLabel").text("Edit Menu");
          $("#menuSiswaForm [name=id]").val(id);
          $("#menuSiswaForm [name=label]").val(label);
          $("#menuSiswaForm [name=slug]").val(slug);
          $("#menuSiswaForm [name=position]").val(position);
          $("#menuSiswaModal").modal("show");
        });

      $(".btn-delete")
        .off("click")
        .on("click", function () {
          var label = $(this).closest("tr").find("td:eq(1) strong").text();
          if (!confirm("Hapus menu '" + label + "'? Data yang sudah dihapus tidak dapat dikembalikan.")) return;
          var id = $(this).data("id");
          $.post(
            "./mod/menu-siswa/proses.php?action=delete",
            { id: id },
            function (resp) {
              if ($.trim(resp) === "success") {
                table.ajax.reload(null, false);
                // Show success message
                if (typeof Swal !== 'undefined') {
                  Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Menu berhasil dihapus',
                    timer: 2000,
                    showConfirmButton: false
                  });
                } else {
                  alert("Menu berhasil dihapus");
                }
              } else {
                alert("Gagal menghapus: " + resp);
              }
            }
          ).fail(function () {
            alert("Gagal menghubungi server.");
          });
        });
    }

    // handle add button
    $(document).on("click", ".btn-add", function () {
      $("#menuSiswaModalLabel").text("Tambah Menu");
      $("#menuSiswaForm")[0].reset();
      $("#menuSiswaForm [name=id]").val("");
      // Auto set position to next available position
      $.get("./mod/menu-siswa/datatable.php?get_max_position=1", function(resp) {
        try {
          var data = JSON.parse(resp);
          if (data.max_position) {
            $("#menuSiswaForm [name=position]").val(parseInt(data.max_position) + 10);
          }
        } catch(e) {
          $("#menuSiswaForm [name=position]").val(10);
        }
      }).fail(function() {
        $("#menuSiswaForm [name=position]").val(10);
      });
      $("#menuSiswaModal").modal("show");
    });

    // submit form
    $(document).on("submit", "#menuSiswaForm", function (e) {
      e.preventDefault();
      
      // Validate slug format
      var slug = $("#menuSiswaForm [name=slug]").val().trim();
      var slugPattern = /^[a-z0-9-_]+$/i;
      if (!slugPattern.test(slug)) {
        alert("Slug hanya boleh mengandung huruf, angka, tanda minus (-) dan underscore (_)");
        return;
      }
      
      var data = $(this).serialize();
      var isEdit = $("#menuSiswaForm [name=id]").val() !== "";
      $.post("./mod/menu-siswa/proses.php?action=save", data, function (resp) {
        if ($.trim(resp) === "success") {
          $("#menuSiswaModal").modal("hide");
          if (table) table.ajax.reload(null, false);
          // Show success message
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: isEdit ? 'Menu berhasil diperbarui' : 'Menu berhasil ditambahkan',
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            alert(isEdit ? "Menu berhasil diperbarui" : "Menu berhasil ditambahkan");
          }
        } else {
          alert("Gagal menyimpan: " + resp);
        }
      }).fail(function () {
        alert("Gagal menghubungi server.");
      });
    });

    // sync defaults (accept response 'success' or 'success|N')
    $(document).on("click", ".btn-sync", function () {
      if (
        !confirm(
          "Sinkronisasi default akan menambahkan item default yang belum ada. Lanjutkan?"
        )
      )
        return;
      $.post("./mod/menu-siswa/proses.php?action=sync", {}, function (resp) {
        var r = $.trim(resp || "");
        if (r.indexOf("success") === 0) {
          if (table) table.ajax.reload(null, false);
          var parts = r.split("|");
          if (parts.length > 1) {
            alert("Sinkronisasi selesai. Item baru ditambahkan: " + parts[1]);
          } else {
            // generic success
            console.info("Sinkronisasi berhasil");
          }
        } else {
          alert("Gagal sinkronisasi: " + resp);
        }
      }).fail(function () {
        alert("Gagal menghubungi server.");
      });
    });

    // init
    initTable();
  });
})(jQuery);
