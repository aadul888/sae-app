"use strict";
loadData();
function loadData() {
  var columns = [
    { title: "No", className: "text-center" },
    { title: "NISN", className: "text-center" },
    { title: "Nama", className: "text-center" },
    { title: "Kelas", className: "text-center" },
    { title: "Jenis Izin", className: "text-center" },
    { title: "Tanggal", className: "text-center" },
    { title: "Status", className: "text-center" },
    { title: "Status Wali", className: "text-center" },
    { title: "Konfirmasi", className: "text-center" },
    { title: "Aksi", className: "text-center" },
  ];
  $(".datatable").DataTable({
    scrollY: false,
    scrollX: true,
    processing: true,
    serverSide: true,
    bDestroy: true,
    ajax: {
      url: "./mod/e-izin/datatable.php",
      type: "POST",
    },
    columns: columns,
    language: {
      paginate: {
        previous: "<i class='fas fa-angle-left'></i>",
        next: "<i class='fas fa-angle-right'></i>",
      },
    },
  });
}
$(document).on("click", ".btn-approve", function () {
  var id = $(this).attr("data-id");
  swal({
    title: "Setujui Pengajuan Izin?",
    text: "Izin ini akan disetujui.",
    icon: "info",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Setujui",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
  }).then((willApprove) => {
    if (willApprove) {
      $.ajax({
        url: "./mod/e-izin/proses.php?action=setujui",
        type: "POST",
        data: { id: id },
        success: function (response) {
          swal(
            "Info",
            response,
            response.includes("berhasil") ? "success" : "error"
          );
          loadData();
        },
      });
    }
  });
});
$(document).on("click", ".btn-reject", function () {
  var id = $(this).attr("data-id");
  swal({
    title: "Tolak Pengajuan Izin?",
    text: "Berikan alasan penolakan.",
    content: {
      element: "input",
      attributes: {
        placeholder: "Isi alasan penolakan",
        type: "text",
      },
    },
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Tolak",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
  }).then((alasan) => {
    if (alasan) {
      $.ajax({
        url: "./mod/e-izin/proses.php?action=tolak",
        type: "POST",
        data: { id: id, alasan: alasan },
        success: function (response) {
          swal(
            "Info",
            response,
            response.includes("berhasil") ? "success" : "error"
          );
          loadData();
        },
      });
    }
  });
});
$(document).on("click", ".btn-view-detail", function () {
  var id = $(this).data("id");
  $.ajax({
    type: "POST",
    url: "./mod/e-izin/proses.php?action=detail",
    data: { id: id },
    success: function (response) {
      $("#detail-content").html(response);
      $("#modal-detail").modal("show");
    },
    error: function () {
      alert("Gagal mengambil data detail.");
    },
  });
});
$(document).on("click", ".btn-view-catatan", function () {
  var catatan = $(this).data("catatan");
  swal({
    title: "Catatan Penolakan",
    text: catatan,
    icon: "info",
    button: "Tutup",
  });
});
$(document).on("click", ".btn-edit-catatan", function () {
  var id = $(this).data("id");
  var catatan = $(this).data("catatan");
  $("#edit-id").val(id);
  $("#edit-catatan").val(catatan);
  $("#modal-edit-catatan").modal("show");
});
$("#form-edit-catatan").on("submit", function (e) {
  e.preventDefault();
  var id = $("#edit-id").val();
  var catatan = $("#edit-catatan").val();
  $.ajax({
    url: "./mod/e-izin/proses.php?action=edit_catatan",
    type: "POST",
    data: { id: id, catatan: catatan },
    success: function (response) {
      $("#modal-edit-catatan").modal("hide");
      swal(
        "Info",
        response,
        response.includes("berhasil") ? "success" : "error"
      );
      loadData();
    },
  });
});
$(document).on("click", ".btn-delete", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Pengajuan Izin?",
    text: "Data ini akan dihapus secara permanen.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Hapus",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
    dangerMode: true,
  }).then((willDelete) => {
    if (willDelete) {
      $.ajax({
        url: "./mod/e-izin/proses.php?action=hapus",
        type: "POST",
        data: { id: id },
        success: function (response) {
          swal(
            "Info",
            response,
            response.includes("berhasil") ? "success" : "error"
          );
          loadData();
          try {
            if (
              typeof response === "string" &&
              response.toLowerCase().indexOf("berhasil") !== -1
            ) {
              setTimeout(function () {
                window.location.reload();
              }, 800);
            }
          } catch (e) {}
        },
      });
    }
  });
});
$(document).on("click", "#btn-setujui", function () {
  var id = $(this).data("id");
  swal({
    title: "Setujui Pengajuan Izin?",
    text: "Data ini akan disetujui.",
    icon: "info",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Setujui",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
  }).then((willApprove) => {
    if (willApprove) {
      $.ajax({
        url: "./mod/e-izin/proses.php?action=setujui",
        type: "POST",
        data: { id: id },
        success: function (response) {
          $("#modal-detail").modal("hide");
          swal(
            "Info",
            response,
            response.includes("berhasil") ? "success" : "error"
          );
          loadData();
        },
      });
    }
  });
});
$(document).on("click", "#btn-tolak", function () {
  var id = $(this).data("id");
  var $group = $("#catatanGroup");
  var $alasan = $("#catatan-penolakan");
  if (!$group.is(":visible")) {
    $group.slideDown(150);
    $alasan.focus();
    $(this)
      .removeClass("btn-danger")
      .addClass("btn-warning")
      .html('<i class="fas fa-paper-plane"></i> Kirim Penolakan');
    return;
  }
  var alasanVal = $alasan.val().trim();
  if (alasanVal === "") {
    swal("Oops!", "Catatan penolakan wajib diisi.", "warning");
    return;
  }
  swal({
    title: "Tolak Pengajuan Izin?",
    text: "Data ini akan ditolak dengan catatan.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Tolak",
        value: true,
        visible: true,
        closeModal: true,
      },
    },
  }).then((willReject) => {
    if (willReject) {
      $.ajax({
        url: "./mod/e-izin/proses.php?action=tolak",
        type: "POST",
        data: { id: id, alasan: alasanVal },
        success: function (response) {
          $("#modal-detail").modal("hide");
          swal(
            "Info",
            response,
            response.includes("berhasil") ? "success" : "error"
          );
          loadData();
        },
      });
    }
  });
});
$("#modal-detail").on("hidden.bs.modal", function () {
  var $group = $("#catatanGroup");
  var $alasan = $("#catatan-penolakan");
  var $btnTolak = $("#btn-tolak");
  $group.hide();
  $alasan.val("");
  $btnTolak
    .removeClass("btn-warning")
    .addClass("btn-danger")
    .html('<i class="fas fa-times"></i> Tolak');
});
$(document).on("submit", "#form-izin", function (e) {
  e.preventDefault();
  const formData = $(this).serialize();
  $.ajax({
    url: "./mod/absensi-izin/proses.php?action=ajukan",
    type: "POST",
    data: formData,
    beforeSend: function () {
      swal({
        title: "Mohon Tunggu...",
        text: "Sedang memproses pengajuan izin",
        buttons: false,
        closeOnClickOutside: false,
        closeOnEsc: false,
      });
    },
    success: function (response) {
      swal({
        title: response.includes("berhasil") ? "Berhasil" : "Gagal",
        text: response,
        icon: response.includes("berhasil") ? "success" : "error",
        timer: 2500,
      });
      if (response.includes("berhasil")) {
        $("#form-izin")[0].reset();
        setTimeout(function () {
          $(".datatable").DataTable().ajax.reload(null, false);
        }, 2000);
      }
    },
    error: function () {
      swal.close();
      swal({
        title: "Gagal",
        text: "Terjadi kesalahan saat menghubungi server.",
        icon: "error",
        timer: 2500,
        button: false,
      });
    },
  });
});
$(document).ready(function () {
  const approveForm = document.getElementById("approveForm");
  const btnSetujui = document.getElementById("btnSetujui");
  const btnTolak = document.getElementById("btnTolak");
  const statusIzin = document.getElementById("status_izin");
  const catatanGroup = document.getElementById("catatanGroup");
  const alasanPenolakan = document.getElementById("alasan_penolakan");
  function setButtonLoading(button, isLoading) {
    const icon = button.querySelector("i");
    const text = button.innerHTML;
    if (isLoading) {
      button.disabled = true;
      button.innerHTML =
        '<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...';
    } else {
      button.disabled = false;
      button.innerHTML = text;
    }
  }
  if (btnSetujui) {
    btnSetujui.addEventListener("click", function () {
      swal({
        title: "Setujui Pengajuan Izin?",
        text: "Apakah Anda yakin ingin menyetujui permohonan izin ini?",
        icon: "info",
        buttons: {
          cancel: "Batal",
          confirm: {
            text: "Setujui",
            value: true,
            visible: true,
            closeModal: true,
          },
        },
      }).then((willApprove) => {
        if (willApprove) {
          setButtonLoading(btnSetujui, true);
          statusIzin.value = "Disetujui";
          approveForm.submit();
        }
      });
    });
  }
  if (btnTolak) {
    btnTolak.addEventListener("click", function () {
      if (
        catatanGroup.style.display === "none" ||
        catatanGroup.style.display === ""
      ) {
        catatanGroup.style.display = "block";
        catatanGroup.classList.add("show");
        alasanPenolakan.required = true;
        alasanPenolakan.focus();
        this.innerHTML =
          '<i class="fas fa-paper-plane mr-2"></i>Kirim Penolakan';
        this.classList.remove("btn-danger");
        this.classList.add("btn-warning");
        if (!document.getElementById("btnCancel")) {
          const cancelBtn = document.createElement("button");
          cancelBtn.type = "button";
          cancelBtn.id = "btnCancel";
          cancelBtn.className = "btn btn-light btn-lg mb-2 mb-md-0";
          cancelBtn.innerHTML = '<i class="fas fa-times mr-2"></i>Batal';
          cancelBtn.addEventListener("click", function () {
            catatanGroup.style.display = "none";
            catatanGroup.classList.remove("show");
            alasanPenolakan.value = "";
            alasanPenolakan.required = false;
            alasanPenolakan.classList.remove("is-invalid");
            btnTolak.innerHTML =
              '<i class="fas fa-times mr-2"></i>Tolak & Isi Catatan';
            btnTolak.classList.remove("btn-warning");
            btnTolak.classList.add("btn-danger");
            this.remove();
          });
          btnTolak.parentNode.insertBefore(cancelBtn, btnTolak.nextSibling);
        }
      } else {
        if (alasanPenolakan.value.trim() === "") {
          alasanPenolakan.classList.add("is-invalid");
          alasanPenolakan.placeholder = "Catatan wajib diisi jika menolak";
          alasanPenolakan.focus();
          if (!document.querySelector(".invalid-feedback")) {
            const feedback = document.createElement("div");
            feedback.className = "invalid-feedback";
            feedback.textContent = "Catatan penolakan wajib diisi";
            alasanPenolakan.parentNode.appendChild(feedback);
          }
          return;
        }
        swal({
          title: "Tolak Pengajuan Izin?",
          text: "Apakah Anda yakin ingin menolak permohonan izin ini?",
          icon: "warning",
          buttons: {
            cancel: "Batal",
            confirm: {
              text: "Tolak",
              value: true,
              visible: true,
              closeModal: true,
            },
          },
        }).then((willReject) => {
          if (willReject) {
            alasanPenolakan.classList.remove("is-invalid");
            setButtonLoading(btnTolak, true);
            statusIzin.value = "Ditolak";
            approveForm.submit();
          }
        });
      }
    });
  }
  if (alasanPenolakan) {
    alasanPenolakan.addEventListener("input", function () {
      if (this.value.trim() !== "") {
        this.classList.remove("is-invalid");
        const feedback = document.querySelector(".invalid-feedback");
        if (feedback) feedback.remove();
      }
    });
  }
  if (window.innerWidth <= 768 && catatanGroup) {
    catatanGroup.addEventListener("transitionend", function () {
      if (this.style.display === "block") {
        this.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });
      }
    });
  }
});
$(function () {
  var approveForm = document.getElementById("approveForm");
  var statusInput = document.getElementById("status_izin");
  var konfirmasiInput = document.getElementById("konfirmasi");
  function submitWithKonfirmasi(konf) {
    if (konfirmasiInput) konfirmasiInput.value = konf;
    if (statusInput) statusInput.value = "Disetujui";
    if (approveForm) approveForm.submit();
  }
  function confirmAndSubmit(title, text, konf, confirmLabel) {
    var cfg = {
      title: title,
      text: text,
      icon: "info",
      buttons: {
        cancel: "Batal",
        confirm: confirmLabel || "Ya",
      },
    };
    if (typeof window.swal === "function") {
      window.swal(cfg).then(function (ok) {
        if (!ok) return;
        var approveIdEl = document.getElementById("approve_id");
        if (approveIdEl && approveIdEl.value) {
          var payload = { id: approveIdEl.value, konfirmasi: konf };
          $.post("./proses.php?action=konfirmasi", payload)
            .done(function (resp) {
              try {
                if (typeof resp === "string") resp = JSON.parse(resp);
              } catch (e) {}
              if (resp && resp.success) {
                window
                  .swal(
                    "Sukses",
                    resp.message || "Konfirmasi berhasil.",
                    "success"
                  )
                  .then(function () {
                    window.location.replace("../../e-izin");
                  });
              } else {
                window.swal(
                  "Gagal",
                  resp && resp.message ? resp.message : "Terjadi kesalahan.",
                  "error"
                );
              }
            })
            .fail(function () {
              window.swal(
                "Gagal",
                "Permintaan gagal dikirim ke server.",
                "error"
              );
            });
        } else {
          submitWithKonfirmasi(konf);
        }
      });
      return;
    }
    if (confirm(text)) submitWithKonfirmasi(konf);
  }
  var btnPulang = document.getElementById("btnPulang");
  if (btnPulang) {
    btnPulang.addEventListener("click", function () {
      confirmAndSubmit(
        "Konfirmasi Pulang",
        "Anda akan menandai siswa sebagai pulang.",
        "pulang",
        "Ya, Pulang"
      );
    });
  }
  var btnKeluar = document.getElementById("btnKeluar");
  if (btnKeluar) {
    btnKeluar.addEventListener("click", function () {
      confirmAndSubmit(
        "Konfirmasi Keluar",
        "Anda akan mengizinkan siswa keluar. Setelah itu token kembali akan dibuat untuk proses kembalinya.",
        "keluar",
        "Ya, Keluar"
      );
    });
  }
  var btnKembali = document.getElementById("btnKembali");
  if (btnKembali) {
    btnKembali.addEventListener("click", function () {
      confirmAndSubmit(
        "Konfirmasi Kembali",
        "Anda akan memproses kembali siswa.",
        "kembali",
        "Ya, Kembali"
      );
    });
  }
});

// --- opener window helper: open approve pages with window.open and handle close messages ---
(function () {
  if (typeof window === "undefined") return;
  window.eizin_windows = window.eizin_windows || {};

  window.openApproveWindow = function (url) {
    try {
      var match = String(url).match(/[?&](?:id|t)=([^&]+)/i);
      var id = match ? match[1] : Date.now() + Math.floor(Math.random() * 1000);
      var w = window.open(url, "_blank");
      if (w) {
        window.eizin_windows[id] = w;
      }
      return w;
    } catch (e) {
      return null;
    }
  };

  window.addEventListener(
    "message",
    function (ev) {
      try {
        var d = ev.data || {};
        if (d && d.type === "eizin-close") {
          var id = d.id;
          if (id && window.eizin_windows && window.eizin_windows[id]) {
            try {
              window.eizin_windows[id].close();
            } catch (e) {}
            try {
              delete window.eizin_windows[id];
            } catch (e) {}
            return;
          }
          // fallback: close all known child windows opened by this page
          if (window.eizin_windows) {
            for (var k in window.eizin_windows) {
              try {
                window.eizin_windows[k].close();
              } catch (e) {}
              try {
                delete window.eizin_windows[k];
              } catch (e) {}
            }
          }
        }
      } catch (e) {}
    },
    false
  );

  // Intercept clicks on approve links inside this module so they open via window.open and are trackable
  document.addEventListener(
    "click",
    function (ev) {
      try {
        var t = ev.target;
        while (t && t.tagName !== "A") t = t.parentNode;
        if (!t || t.tagName !== "A") return;
        var href = t.getAttribute("href") || "";
        if (
          href.indexOf("/admin/mod/e-izin/approve") !== -1 &&
          (!t.target || t.target === "_self")
        ) {
          ev.preventDefault();
          try {
            window.openApproveWindow(href);
          } catch (e) {}
        }
      } catch (e) {}
    },
    false
  );
})();
