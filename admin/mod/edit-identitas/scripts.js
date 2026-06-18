// ================================
// COPY DATA FUNCTIONALITY
// ================================

// INIT: Bind existing toggle button (server-rendered) - top-level
$(document).ready(function () {
  try {
    var btn = $("#toggle-usulan-btn");
    if (!btn || btn.length === 0) return;

    function setBtnState(closed) {
      btn.removeClass("btn-danger btn-success");
      if (closed) {
        btn.addClass("btn-danger");
        btn.html('<i class="fas fa-lock me-1"></i> Buka Usulan');
        btn.data("closed", 1);
      } else {
        btn.addClass("btn-success");
        btn.html('<i class="fas fa-unlock me-1"></i> Tutup Usulan');
        btn.data("closed", 0);
      }
      btn.prop("disabled", false);
    }

    // Fetch current status and update button
    $.post("./mod/edit-identitas/proses.php?action=get_status", function (res) {
      var d = res;
      try {
        if (typeof res === "string") d = JSON.parse(res);
      } catch (e) {}
      if (d && typeof d.closed !== "undefined") setBtnState(!!d.closed);
    });

    // Click handler
    btn.on("click", function () {
      var closed = !!(btn.data("closed") == 1);
      var newClosed = !closed;
      loadingButton("#toggle-usulan-btn", "Menyimpan...");
      $.ajax({
        url: "./mod/edit-identitas/proses.php?action=toggle_status",
        type: "POST",
        data: { closed: newClosed ? 1 : 0 },
        success: function (resp) {
          var d = resp;
          try {
            if (typeof resp === "string") d = JSON.parse(resp);
          } catch (e) {}
          if (d && typeof d.closed !== "undefined") {
            setBtnState(!!d.closed);
            showToast("Status usulan diperbarui", "success");
          } else {
            showToast("Gagal menyimpan status", "error");
          }
        },
        error: function () {
          showToast("Gagal menyimpan status", "error");
        },
        complete: function () {
          $("#toggle-usulan-btn").prop("disabled", false);
        },
      });
    });
  } catch (e) {
    console.error("Init toggle button error", e);
  }
});

$(document).on("click", ".btn-copy-data", function () {
  const value = $(this).data("value");

  const field = $(this).data("field");
  const button = $(this);

  console.log("Copy button clicked:", { field, value }); // Debug log

  // Modern clipboard API with fallback
  if (navigator.clipboard && window.isSecureContext) {
    // Use modern Clipboard API
    navigator.clipboard
      .writeText(value)
      .then(() => {
        console.log("Clipboard API success"); // Debug log
        copySuccess(button, field);
      })
      .catch((err) => {
        console.error("Clipboard API failed, trying fallback:", err);
        fallbackCopy(value, button, field);
      });
  } else {
    console.log("Using fallback copy method"); // Debug log
    // Fallback for older browsers or non-secure contexts
    fallbackCopy(value, button, field);
  }
});

function copySuccess(button, field) {
  // Show success feedback
  const originalIcon = button.find("i");
  const originalTitle = button.attr("title");

  originalIcon.removeClass("fa-copy").addClass("fa-check");
  button.removeClass("btn-outline-primary").addClass("btn-success");
  button.attr("title", "Data berhasil dicopy!");

  // Reset after 2 seconds
  setTimeout(() => {
    originalIcon.removeClass("fa-check").addClass("fa-copy");
    button.removeClass("btn-success").addClass("btn-outline-primary");
    button.attr("title", originalTitle);
  }, 2000);

  // Show toast notification
  showToast("Data berhasil dicopy: " + field, "success");
}

function fallbackCopy(value, button, field) {
  try {
    // Create temporary textarea to copy text
    const tempTextarea = document.createElement("textarea");
    tempTextarea.value = value;
    tempTextarea.style.position = "fixed";
    tempTextarea.style.left = "-999999px";
    tempTextarea.style.top = "-999999px";
    tempTextarea.style.opacity = "0";
    document.body.appendChild(tempTextarea);

    // Focus and select the text
    tempTextarea.focus();
    tempTextarea.select();
    tempTextarea.setSelectionRange(0, tempTextarea.value.length);

    const successful = document.execCommand("copy");
    console.log("execCommand copy result:", successful); // Debug log

    document.body.removeChild(tempTextarea);

    if (successful) {
      copySuccess(button, field);
    } else {
      console.error("execCommand copy failed");
      showToast("Gagal copy data", "error");
    }
  } catch (err) {
    console.error("Copy failed:", err);
    showToast("Gagal copy data", "error");
  }
}

// Toast notification function
function showToast(message, type = "info") {
  // Remove existing toast if any
  const existingToast = document.querySelector(".copy-toast");
  if (existingToast) {
    existingToast.remove();
  }

  const toast = document.createElement("div");
  toast.className = `copy-toast toast-${type}`;
  toast.innerHTML = `
    <div class="toast-content">
      <i class="fas fa-${
        type === "success" ? "check" : "exclamation-triangle"
      }"></i>
      <span>${message}</span>
    </div>
  `;

  document.body.appendChild(toast);

  // Show toast
  setTimeout(() => toast.classList.add("show"), 100);

  // Hide toast after 3 seconds
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// ================================
// MODERN ZOOM FUNCTIONALITY
// ================================

let currentZoomLevel = 1;
let isZooming = false;
let isDragging = false;
let startX,
  startY,
  translateX = 0,
  translateY = 0;

function openZoomModal(imageSrc) {
  const modal = document.getElementById("zoom-modal");
  const img = document.getElementById("zoom-image");

  if (modal && img) {
    img.src = imageSrc;
    modal.classList.add("active");
    currentZoomLevel = 1;
    translateX = 0;
    translateY = 0;
    resetZoom();

    // Prevent body scroll when modal is open
    document.body.style.overflow = "hidden";

    // Add drag functionality
    setupImageDrag(img);
  }
}

function closeZoomModal() {
  const modal = document.getElementById("zoom-modal");
  if (modal) {
    modal.classList.remove("active");
    currentZoomLevel = 1;
    translateX = 0;
    translateY = 0;

    // Restore body scroll
    document.body.style.overflow = "";
  }
}

function zoomIn() {
  if (currentZoomLevel < 3) {
    currentZoomLevel += 0.25;
    applyZoom();
  }
}

function zoomOut() {
  if (currentZoomLevel > 0.5) {
    currentZoomLevel -= 0.25;
    applyZoom();
  }
}

function resetZoom() {
  currentZoomLevel = 1;
  translateX = 0;
  translateY = 0;
  applyZoom();
}

function applyZoom() {
  const img = document.getElementById("zoom-image");
  if (img) {
    img.style.transform = `scale(${currentZoomLevel}) translate(${translateX}px, ${translateY}px)`;
    img.style.transition = isZooming
      ? "transform 0.1s ease"
      : "transform 0.3s ease";
  }
}

function setupImageDrag(img) {
  img.addEventListener("mousedown", startDrag);
  img.addEventListener("touchstart", startDrag);
  document.addEventListener("mousemove", drag);
  document.addEventListener("touchmove", drag);
  document.addEventListener("mouseup", stopDrag);
  document.addEventListener("touchend", stopDrag);

  // Mouse wheel zoom
  img.addEventListener("wheel", function (e) {
    e.preventDefault();
    isZooming = true;

    if (e.deltaY < 0) {
      zoomIn();
    } else {
      zoomOut();
    }

    setTimeout(() => {
      isZooming = false;
    }, 100);
  });
}

function startDrag(e) {
  if (currentZoomLevel <= 1) return;

  isDragging = true;
  const clientX = e.type === "mousedown" ? e.clientX : e.touches[0].clientX;
  const clientY = e.type === "mousedown" ? e.clientY : e.touches[0].clientY;

  startX = clientX - translateX;
  startY = clientY - translateY;

  e.preventDefault();
}

function drag(e) {
  if (!isDragging || currentZoomLevel <= 1) return;

  const clientX = e.type === "mousemove" ? e.clientX : e.touches[0].clientX;
  const clientY = e.type === "mousemove" ? e.clientY : e.touches[0].clientY;

  translateX = clientX - startX;
  translateY = clientY - startY;

  applyZoom();
  e.preventDefault();
}

function stopDrag() {
  isDragging = false;
}

// Close modal when clicking outside the image
$(document).on("click", "#zoom-modal", function (e) {
  if (e.target === this) {
    closeZoomModal();
  }
});

// Keyboard controls for zoom modal
$(document).on("keydown", function (e) {
  const modal = document.getElementById("zoom-modal");
  if (modal && modal.classList.contains("active")) {
    switch (e.key) {
      case "Escape":
        closeZoomModal();
        break;
      case "+":
      case "=":
        zoomIn();
        break;
      case "-":
        zoomOut();
        break;
      case "0":
        resetZoom();
        break;
    }
  }
});

// ================================
// REJECTION FORM FUNCTIONS
// ================================

function showRejectForm() {
  document.getElementById("reject-form").style.display = "block";
}

function hideRejectForm() {
  document.getElementById("reject-form").style.display = "none";
  document.getElementById("catatan-penolakan").value = "";
}

// ================================
// DETAIL MODAL ENHANCEMENT
// ================================

$(document).on("shown.bs.modal", "#modal-detail", function () {
  // Add any initialization code here if needed
  console.log("Detail modal shown");

  // Test clipboard availability
  console.log(
    "Clipboard API available:",
    !!(navigator.clipboard && window.isSecureContext)
  );
  console.log("execCommand available:", !!document.execCommand);
});

$(document).on("hidden.bs.modal", "#modal-detail", function () {
  // Clean up when modal is closed
  hideRejectForm();
});
("use strict");

// ================================
// OLD PREVIEW MODAL (DEPRECATED)
// ================================

// Legacy preview berkas modal - now using zoom modal instead
$(document).on("click", ".btn-lihat-berkas", function () {
  var filename = $(this).data("filename");
  if (!filename) return;

  // Use path relative to admin folder for browser access
  var url = "../../content/berkas/" + encodeURIComponent(filename);
  console.log("Opening zoom modal with URL:", url); // Debug log
  openZoomModal(url);
});

// Load DataTable
loadData();
function loadData() {
  var table;
  $(document).ready(function () {
    table = $(".datatable").DataTable({
      scrollY: false,
      scrollX: true,
      processing: true,
      serverSide: true,
      bAutoWidth: true,
      bSort: false,
      bStateSave: true,
      bDestroy: true,
      paging: true,
      ssSorting: [[0, "desc"]],
      iDisplayLength: 25,
      aLengthMenu: [
        [25, 50, 100, -1],
        [25, 50, 100, "All"],
      ],
      language: {
        paginate: {
          previous: "<i class='fas fa-angle-left'></i>",
          next: "<i class='fas fa-angle-right'></i>",
        },
      },
      ajax: {
        url: "./mod/edit-identitas/datatable.php",
        type: "POST",
        dataSrc: function (json) {
          // Update statistik status
          if (json && json.statusStat) {
            $("#stat-total").text(json.statusStat.total || 0);
            $("#stat-disetujui").text(json.statusStat.disetujui || 0);
            $("#stat-ditolak").text(json.statusStat.ditolak || 0);
            $("#stat-berhasil").text(json.statusStat.berhasil || 0);
            $("#stat-proses").text(json.statusStat.proses || 0);
          } else {
            $(
              "#stat-total,#stat-disetujui,#stat-ditolak,#stat-berhasil,#stat-proses"
            ).text(0);
          }
          return json.aaData;
        },
      },
      columnDefs: [
        {
          targets: [0],
          orderable: false,
        },
      ],
      drawCallback: function (settings) {
        // Update jumlah data di card (total filtered)
        var api = this.api();
        var total = api.page.info().recordsDisplay;
        // $("#jumlah-data-datatables").text(total); // Sudah digantikan dengan statistik
      },
    });
  });
}

// Loading Button (optional kalau mau pakai)
function loadingButton(selector, text) {
  $(selector).prop("disabled", true);
  $(selector).html(
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
      text
  );
}

$(document).on("click", ".btn-view-detail", function () {
  var id = $(this).data("id");

  // Show loading state
  $("#detail-content").html(`
    <div class="loading-container">
      <div class="loading-spinner"></div>
      <p>Memuat data...</p>
    </div>
  `);

  $("#modal-detail").modal("show");

  $.ajax({
    type: "POST",
    url: "./mod/edit-identitas/proses.php?action=detail",
    data: { id: id },
    success: function (response) {
      try {
        // Log a short preview to help debugging
        if (typeof console !== "undefined")
          console.debug(
            "Detail response (len):",
            (response || "").toString().length
          );
        if (typeof console !== "undefined")
          console.debug(
            "Detail response preview:",
            (response || "").toString().substring(0, 200)
          );
      } catch (e) {}

      // If server returned JSON (error object), surface message
      var maybe = null;
      try {
        if (typeof response === "string") maybe = JSON.parse(response);
        else if (typeof response === "object") maybe = response;
      } catch (e) {
        maybe = null;
      }
      if (
        maybe &&
        maybe.status &&
        maybe.status.toLowerCase &&
        maybe.status.toLowerCase() === "error"
      ) {
        var msg = maybe.message || "Gagal mengambil data detail.";
        $("#detail-content").html(
          '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>' +
            msg +
            "</div>"
        );
        return;
      }

      // Otherwise, inject HTML
      $("#detail-content").html(response);
    },
    error: function () {
      $("#detail-content").html(`
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          Gagal mengambil data detail. Silakan coba lagi.
        </div>
      `);
    },
  });
});

$(document).ready(function () {
  // Toggle catatan panjang jika tombol "Lihat" diklik
  $(document).on("click", ".btn-show-full", function () {
    var $parent = $(this).closest("small");
    $parent.find(".catatan-short").hide();
    $parent.find(".catatan-full").removeClass("d-none").show();
    $(this).remove(); // hapus tombol "Lihat"
  });

  // Jika menggunakan tooltip
  $('[data-toggle="tooltip"]').tooltip();
});

function reloadAfterAction() {
  // Reload DataTable jika ada, jika tidak reload halaman
  let reloaded = false;
  try {
    if ($(".datatable").length > 0 && $(".datatable").DataTable) {
      $(".datatable").DataTable().ajax.reload(null, false);
      reloaded = true;
    }
  } catch (e) {}
  if (!reloaded) {
    location.reload();
  }
}

$(document).on("click", ".btn-delete", function () {
  var id = $(this).data("id");
  swal({
    title: "Hapus Pengajuan?",
    text: "Data ini akan dihapus secara permanen.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Hapus",
        value: true,
        visible: true,
        className: "",
        closeModal: true,
      },
    },
    dangerMode: true,
  }).then((willDelete) => {
    if (willDelete) {
      $.ajax({
        url: "./mod/edit-identitas/proses.php?action=hapus",
        type: "POST",
        data: { id: id },
        success: function (response) {
          if (response.includes("berhasil")) {
            swal({
              title: "Berhasil!",
              text: response,
              icon: "success",
              timer: 1500,
            }).then(reloadAfterAction);
          } else {
            swal({
              title: "Gagal!",
              text: response,
              icon: "error",
              timer: 2000,
            });
          }
        },
        error: function () {
          swal({
            title: "Gagal!",
            text: "Terjadi kesalahan sistem.",
            icon: "error",
            timer: 2000,
          });
        },
      });
    }
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
    url: "./mod/edit-identitas/proses.php?action=edit_catatan",
    type: "POST",
    data: { id: id, catatan: catatan },
    success: function (response) {
      $("#modal-edit-catatan").modal("hide");
      swal({
        title: "Info",
        text: response,
        icon: response.includes("berhasil") ? "success" : "error",
        timer: 1500,
      }).then(reloadAfterAction);
    },
    error: function () {
      swal({
        title: "Gagal",
        text: "Terjadi kesalahan sistem.",
        icon: "error",
        timer: 2500,
      });
    },
  });
});

// Tombol SETUJUI di modal
$(document).on("click", "#btn-setujui", function () {
  var id = $(this).data("id");

  swal({
    title: "Setujui Pengajuan?",
    text: "Data perubahan ini akan disetujui.",
    icon: "info",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Setujui",
        value: true,
        visible: true,
        className: "",
        closeModal: true,
      },
    },
    dangerMode: false,
  }).then((willApprove) => {
    if (willApprove) {
      $.ajax({
        url: "./mod/edit-identitas/proses.php?action=setujui",
        type: "POST",
        data: { id: id },
        success: function (response) {
          $("#modal-detail").modal("hide");
          swal({
            title: "Info",
            text: response,
            icon: response.includes("berhasil") ? "success" : "error",
            timer: 1500,
          }).then(reloadAfterAction);
        },
      });
    }
  });
});

// Tombol TOLAK di modal
$(document).on("click", "#btn-tolak", function () {
  var id = $(this).data("id");
  var alasan = $("#catatan-penolakan").val().trim();

  if (alasan === "") {
    swal("Oops!", "Catatan penolakan wajib diisi.", "warning");
    return;
  }

  swal({
    title: "Tolak Pengajuan?",
    text: "Data ini akan ditolak dengan catatan.",
    icon: "warning",
    buttons: {
      cancel: "Batal",
      confirm: {
        text: "Tolak",
        value: true,
        visible: true,
        className: "",
        closeModal: true,
      },
    },
    dangerMode: true,
  }).then((willReject) => {
    if (willReject) {
      $.ajax({
        url: "./mod/edit-identitas/proses.php?action=tolak",
        type: "POST",
        data: { id: id, alasan: alasan },
        success: function (response) {
          $("#modal-detail").modal("hide");
          swal({
            title: "Info",
            text: response,
            icon: response.includes("berhasil") ? "success" : "error",
            timer: 1500,
          }).then(reloadAfterAction);
        },
      });
    }
  });
});

// ================================
// ZOOM MODAL FUNCTIONALITY
// ================================

let zoomLevel = 1;
let zoomPanX = 0;
let zoomPanY = 0;
let rotationAngle = 0;
let isZoomDragging = false;
let zoomStartX = 0;
let zoomStartY = 0;

function openZoomModal(imageUrl) {
  const zoomModal = `
    <div class="zoom-modal" id="zoomModal">
      <div class="zoom-modal-content">
        <img id="zoomImage" src="${imageUrl}" alt="Zoomed Image">
        <button class="zoom-modal-close" onclick="closeZoomModal()">
          <i class="fas fa-times"></i>
        </button>
        <div class="zoom-modal-controls">
          <button class="zoom-control-btn" onclick="zoomIn()">
            <i class="fas fa-plus"></i>
          </button>
          <button class="zoom-control-btn" onclick="zoomOut()">
            <i class="fas fa-minus"></i>
          </button>
          <button class="zoom-control-btn" onclick="rotateImage()">
            <i class="fas fa-redo"></i>
          </button>
          <button class="zoom-control-btn" onclick="resetZoom()">
            <i class="fas fa-expand"></i>
          </button>
        </div>
      </div>
    </div>
  `;

  // Remove existing modal if any
  const existingModal = document.getElementById("zoomModal");
  if (existingModal) {
    existingModal.remove();
  }

  // Add to body
  document.body.insertAdjacentHTML("beforeend", zoomModal);

  // Show modal
  const modal = document.getElementById("zoomModal");
  const image = document.getElementById("zoomImage");

  setTimeout(() => {
    modal.classList.add("active");
  }, 10);

  // Reset zoom state
  zoomLevel = 1;
  zoomPanX = 0;
  zoomPanY = 0;
  rotationAngle = 0;
  updateImageTransform();

  // Add event listeners
  setupZoomEvents(image);
}

function closeZoomModal() {
  const modal = document.getElementById("zoomModal");
  if (modal) {
    modal.classList.remove("active");
    setTimeout(() => {
      modal.remove();
    }, 300);
  }
}

function zoomIn() {
  zoomLevel = Math.min(zoomLevel * 1.2, 5);
  updateImageTransform();
}

function zoomOut() {
  zoomLevel = Math.max(zoomLevel / 1.2, 0.5);
  updateImageTransform();
}

function resetZoom() {
  zoomLevel = 1;
  zoomPanX = 0;
  zoomPanY = 0;
  rotationAngle = 0;
  updateImageTransform();
}

function rotateImage() {
  rotationAngle = (rotationAngle + 90) % 360;
  updateImageTransform();
}

function updateImageTransform() {
  const image = document.getElementById("zoomImage");
  if (image) {
    image.style.transform = `rotate(${rotationAngle}deg) scale(${zoomLevel}) translate(${zoomPanX}px, ${zoomPanY}px)`;
  }
}

function setupZoomEvents(image) {
  // Mouse wheel zoom
  image.addEventListener("wheel", (e) => {
    e.preventDefault();
    if (e.deltaY < 0) {
      zoomIn();
    } else {
      zoomOut();
    }
  });

  // Drag to pan
  image.addEventListener("mousedown", (e) => {
    if (zoomLevel > 1) {
      isZoomDragging = true;
      zoomStartX = e.clientX - zoomPanX;
      zoomStartY = e.clientY - zoomPanY;
      image.style.cursor = "grabbing";
    }
  });

  document.addEventListener("mousemove", (e) => {
    if (isZoomDragging && zoomLevel > 1) {
      zoomPanX = e.clientX - zoomStartX;
      zoomPanY = e.clientY - zoomStartY;
      updateImageTransform();
    }
  });

  document.addEventListener("mouseup", () => {
    if (isZoomDragging) {
      isZoomDragging = false;
      image.style.cursor = "grab";
    }
  });

  // Close modal on background click
  const modal = document.getElementById("zoomModal");
  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      closeZoomModal();
    }
  });

  // Keyboard controls
  document.addEventListener("keydown", (e) => {
    if (modal.classList.contains("active")) {
      switch (e.key) {
        case "Escape":
          closeZoomModal();
          break;
        case "+":
        case "=":
          e.preventDefault();
          zoomIn();
          break;
        case "-":
          e.preventDefault();
          zoomOut();
          break;
        case "0":
          e.preventDefault();
          resetZoom();
          break;
        case "r":
        case "R":
          e.preventDefault();
          rotateImage();
          break;
      }
    }
  });
}
