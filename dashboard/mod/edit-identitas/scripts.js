"use strict";

// Module initialization flag
window.moduleLoaded = false;

// Loading function for save buttons
function loading() {
  $(".btn-save").prop("disabled", true);
  $(".btn-save").html(
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
  );

  window.setTimeout(function () {
    $(".btn-save").prop("disabled", false);
    $(".btn-save").html('<i class="far fa-save"></i> Simpan Perubahan');
  }, 2000);
}

// Reset the save button inside a specific form (restore enabled state and text)
function resetFormSaveButton(form) {
  try {
    if (!form) {
      // fallback to global
      $(".btn-save").prop("disabled", false);
      $(".btn-save").html('<i class="far fa-save"></i> Simpan Perubahan');
      return;
    }
    var btn = form.querySelector(".btn-save");
    if (btn) {
      btn.disabled = false;
      try {
        btn.innerHTML = '<i class="far fa-save"></i> Simpan Perubahan';
      } catch (e) {}
      $(btn).prop("disabled", false);
    }
  } catch (e) {}
}

// Main module initialization
function initializeModule() {
  try {
    // Initialize DataTable if element exists
    initializeDataTable();

    // Initialize modals
    initializeModals();

    // Initialize forms
    initializeForms();

    // Initialize history actions (edit / delete)
    initializeHistoryActions();

    // Initialize table interactions
    initializeTableInteractions();

    // Fix scrolling issues
    fixScrollingIssues();

    // Apply animations
    initializeAnimations();

    window.moduleLoaded = true;
  } catch (error) {
    // Module initialization failed
  }
}

// Initialize DataTable
function initializeDataTable() {
  if ($(".datatable").length > 0) {
    var table = $(".datatable").DataTable({
      scrollY: false,
      scrollX: false,
      processing: true,
      serverSide: false,
      bAutoWidth: true,
      bSort: false,
      bStateSave: true,
      bDestroy: true,
      paging: true,
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
        url: "./mod/usulan/datatable.php",
        type: "POST",
      },
      columnDefs: [
        {
          targets: [0],
          orderable: false,
        },
      ],
    });
  }
}

// Initialize modals
function initializeModals() {
  // The add-usulan button now opens the unified form modal directly via markup.
}

// Initialize forms
function initializeForms() {
  // Form submission handlers
  initializeIdentitasForm();
  initializeOrangtuaForm();
  initializeWaliForm();
}

// Initialize Identitas form
function initializeIdentitasForm() {
  var formIdentitas = document.getElementById("formIdentitas");

  if (formIdentitas) {
    // When modal opens, capture current values as originals
    if (typeof $ !== "undefined") {
      $("#modalFormIdentitas").on("shown.bs.modal", function () {
        snapshotForm(formIdentitas);
      });
    } else {
      formIdentitas.addEventListener("shown.bs.modal", function () {
        snapshotForm(formIdentitas);
      });
    }
    formIdentitas.addEventListener("submit", function (e) {
      e.preventDefault();

      // Ensure all required fields are filled (user requested all non-wali fields required)
      const missing = getMissingRequired(formIdentitas);
      if (missing.length) {
        const msg = "Kolom wajib belum diisi: \n" + missing.join("\n");
        if (typeof swal !== "undefined") {
          swal({ title: "", text: msg, icon: "error", timer: 4500 });
        } else {
          showAlert(msg, "danger");
        }
        resetFormSaveButton(formIdentitas);
        return;
      }

      // Compute changed fields for validation, but send full form data so server
      // can compute a complete old->new keterangan (this keeps admin/history
      // consistent with previous behavior).
      const changes = getChangedFormData(formIdentitas);
      if (Object.keys(changes).length === 0) {
        if (typeof swal !== "undefined") {
          swal({
            title: "Peringatan!",
            text: "Harap ubah minimal satu field sebelum disimpan!",
            icon: "warning",
            timer: 2500,
          });
        }
        // Ensure save button is not left in loading state
        resetFormSaveButton(formIdentitas);
        return;
      }
      // DEBUG: show which fields changed; also serialize full form data for sending
      if (typeof console !== "undefined" && Object.keys(changes).length)
        console.debug(
          "Submitting changes (identitas) - changed fields:",
          changes
        );

      // Send full form data (all fields) so server can compute complete keterangan
      // Client-side validation: ensure NIK/No KK and parent NIKs are 16 digits and phones are numeric
      var clientErrors = [];
      var elNik = document.getElementById("nik");
      var elNoKk = document.getElementById("no_kk");
      var elNikAyah = document.getElementById("nik_ayah");
      var elNikIbu = document.getElementById("nik_ibu");
      var elTelp = document.getElementById("telp");
      var elTelpWali = document.getElementById("telp_wali");
      var elEmail = document.getElementById("email");
      if (elNik && elNik.value.trim() && !/^\d{16}$/.test(elNik.value.trim()))
        clientErrors.push("NIK harus terdiri dari 16 digit angka.");
      if (
        elNoKk &&
        elNoKk.value.trim() &&
        !/^\d{16}$/.test(elNoKk.value.trim())
      )
        clientErrors.push("Nomor KK harus terdiri dari 16 digit angka.");
      if (
        elNikAyah &&
        elNikAyah.value.trim() &&
        !/^\d{16}$/.test(elNikAyah.value.trim())
      )
        clientErrors.push("NIK Ayah harus terdiri dari 16 digit angka.");
      if (
        elNikIbu &&
        elNikIbu.value.trim() &&
        !/^\d{16}$/.test(elNikIbu.value.trim())
      )
        clientErrors.push("NIK Ibu harus terdiri dari 16 digit angka.");
      if (elTelp && elTelp.value.trim() && /\D/.test(elTelp.value.trim()))
        clientErrors.push("Nomor telepon hanya boleh berisi angka.");
      if (
        elTelpWali &&
        elTelpWali.value.trim() &&
        /\D/.test(elTelpWali.value.trim())
      )
        clientErrors.push("Nomor telepon wali hanya boleh berisi angka.");
      if (
        elEmail &&
        elEmail.value.trim() &&
        !/^[^@\s]+@smk\.belajar\.id$/i.test(elEmail.value.trim())
      )
        clientErrors.push("Email wajib menggunakan domain @smk.belajar.id");
      if (clientErrors.length) {
        if (typeof swal !== "undefined") {
          swal({
            title: "",
            text: clientErrors.join("\n"),
            icon: "error",
            timer: 4500,
          });
        } else {
          showAlert(clientErrors.join("\n"), "danger");
        }
        resetFormSaveButton(formIdentitas);
        return;
      }

      var serialized = $(formIdentitas).serialize();

      // Submit data
      $.ajax({
        type: "POST",
        url: "./mod/edit-identitas/proses.php?action=usulan_identitas",
        data: serialized,
        beforeSend: function () {
          loading();
        },
        success: function (data, textStatus, jqXHR) {
          var body =
            typeof data === "string" ? data : JSON.stringify(data) || "";
          body = (body || "").trim();
          // Accept several success variants: 'success', '1', or JSON {status:'success'}
          var ok = false;
          try {
            if (typeof data === "object" && data !== null) {
              if (
                data.status &&
                String(data.status).toLowerCase() === "success"
              )
                ok = true;
            }
          } catch (e) {}
          if (!ok) {
            if (body === "success" || body === "1" || /success/i.test(body))
              ok = true;
          }
          if (ok) {
            if (typeof swal !== "undefined") {
              swal({
                title: "Berhasil!",
                text: "Usulan perubahan identitas telah dikirim!",
                icon: "success",
                timer: 2000,
              });
            }

            $("#modalFormIdentitas").modal("hide");
            setTimeout(function () {
              window.location.reload();
            }, 2100);
          } else {
            // If server returned empty body, try to surface raw responseText
            if (!body && jqXHR && jqXHR.responseText)
              body = jqXHR.responseText.trim() || "(empty response)";
            console.error(
              "Save identitas failed:",
              jqXHR.status,
              jqXHR.statusText,
              body
            );
            // Reset button immediately
            resetFormSaveButton(formIdentitas);
            if (typeof swal !== "undefined") {
              var parsed = formatAlertMessage(body || "");
              swal({
                title: "",
                text:
                  parsed.body ||
                  "Terjadi kesalahan saat menyimpan. Cek log server.",
                icon: "error",
                timer: 4500,
              });
            }
          }
        },
        error: function (jqXHR, status, error) {
          console.error(
            "AJAX error (identitas):",
            status,
            error,
            jqXHR.responseText
          );
          resetFormSaveButton(formIdentitas);
          var msg = "Terjadi kesalahan koneksi.";
          if (jqXHR && jqXHR.responseText) msg += " " + jqXHR.responseText;
          if (typeof swal !== "undefined") {
            var parsedErr =
              jqXHR && jqXHR.responseText
                ? formatAlertMessage(jqXHR.responseText).body
                : msg;
            swal({
              title: "",
              text: parsedErr || msg,
              icon: "error",
              timer: 4500,
            });
          }
        },
      });
    });
  }
}

// Initialize Orangtua form
function initializeOrangtuaForm() {
  var formOrangtua = document.getElementById("formOrangtua");

  if (formOrangtua) {
    // When modal opens, capture current values as originals
    if (typeof $ !== "undefined") {
      $("#modalFormOrangtua").on("shown.bs.modal", function () {
        snapshotForm(formOrangtua);
      });
    } else {
      formOrangtua.addEventListener("shown.bs.modal", function () {
        snapshotForm(formOrangtua);
      });
    }
    formOrangtua.addEventListener("submit", function (e) {
      e.preventDefault();

      // Ensure all required fields in Orangtua modal are filled
      const missingO = getMissingRequired(formOrangtua);
      if (missingO.length) {
        const msg = "Kolom wajib belum diisi: \n" + missingO.join("\n");
        if (typeof swal !== "undefined") {
          swal({ title: "", text: msg, icon: "error", timer: 4500 });
        } else {
          showAlert(msg, "danger");
        }
        resetFormSaveButton(formOrangtua);
        return;
      }

      // Compute changed fields for validation, but send full form data
      const changes = getChangedFormData(formOrangtua);
      if (Object.keys(changes).length === 0) {
        if (typeof swal !== "undefined") {
          swal({
            title: "Peringatan!",
            text: "Harap ubah minimal satu field data orang tua sebelum disimpan!",
            icon: "warning",
            timer: 2500,
          });
        }
        // Reset any loading state on the form save button
        resetFormSaveButton(formOrangtua);
        return;
      }
      if (typeof console !== "undefined" && Object.keys(changes).length)
        console.debug(
          "Submitting changes (orangtua) - changed fields:",
          changes
        );

      // Client-side validation for orangtua: NIK Ayah/Ibu must be 16 digits if provided
      var clientErrorsO = [];
      var elNikAyah_o = document.getElementById("nik_ayah");
      var elNikIbu_o = document.getElementById("nik_ibu");
      if (
        elNikAyah_o &&
        elNikAyah_o.value.trim() &&
        !/^\d{16}$/.test(elNikAyah_o.value.trim())
      )
        clientErrorsO.push("NIK Ayah harus terdiri dari 16 digit angka.");
      if (
        elNikIbu_o &&
        elNikIbu_o.value.trim() &&
        !/^\d{16}$/.test(elNikIbu_o.value.trim())
      )
        clientErrorsO.push("NIK Ibu harus terdiri dari 16 digit angka.");
      if (clientErrorsO.length) {
        if (typeof swal !== "undefined") {
          swal({
            title: "",
            text: clientErrorsO.join("\n"),
            icon: "error",
            timer: 4500,
          });
        } else {
          showAlert(clientErrorsO.join("\n"), "danger");
        }
        resetFormSaveButton(formOrangtua);
        return;
      }

      var serializedOrtu = $(formOrangtua).serialize();

      $.ajax({
        type: "POST",
        // send orangtua data to the unified endpoint
        url: "./mod/edit-identitas/proses.php?action=usulan_identitas",
        data: serializedOrtu,
        beforeSend: function () {
          loading();
        },
        success: function (data, textStatus, jqXHR) {
          var body =
            typeof data === "string" ? data : JSON.stringify(data) || "";
          body = (body || "").trim();
          // Accept several success variants: 'success', '1', or JSON {status:'success'}
          var ok = false;
          try {
            if (typeof data === "object" && data !== null) {
              if (
                data.status &&
                String(data.status).toLowerCase() === "success"
              )
                ok = true;
            }
          } catch (e) {}
          if (!ok) {
            if (body === "success" || body === "1" || /success/i.test(body))
              ok = true;
          }
          if (ok) {
            if (typeof swal !== "undefined") {
              swal({
                title: "Berhasil!",
                text: "Usulan perubahan data orang tua telah dikirim!",
                icon: "success",
                timer: 2000,
              });
            }

            $("#modalFormOrangtua").modal("hide");
            setTimeout(function () {
              window.location.reload();
            }, 2100);
          } else {
            if (!body && jqXHR && jqXHR.responseText)
              body = jqXHR.responseText.trim() || "(empty response)";
            console.error(
              "Save orangtua failed:",
              jqXHR.status,
              jqXHR.statusText,
              body
            );
            resetFormSaveButton(formOrangtua);
            if (typeof swal !== "undefined") {
              var parsed = formatAlertMessage(body || "");
              swal({
                title: "",
                text:
                  parsed.body ||
                  "Terjadi kesalahan saat menyimpan. Cek log server.",
                icon: "error",
                timer: 4500,
              });
            }
          }
        },
        error: function (jqXHR, status, error) {
          console.error(
            "AJAX error (orangtua):",
            status,
            error,
            jqXHR.responseText
          );
          resetFormSaveButton(formOrangtua);
          var msg = "Terjadi kesalahan koneksi.";
          if (jqXHR && jqXHR.responseText) msg += " " + jqXHR.responseText;
          if (typeof swal !== "undefined") {
            var parsedErr =
              jqXHR && jqXHR.responseText
                ? formatAlertMessage(jqXHR.responseText).body
                : msg;
            swal({
              title: "",
              text: parsedErr || msg,
              icon: "error",
              timer: 4500,
            });
          }
        },
      });
    });
  }
}

// Initialize Wali form
function initializeWaliForm() {
  var formWali = document.getElementById("formWali");

  if (formWali) {
    // When modal opens, capture current values as originals
    if (typeof $ !== "undefined") {
      $("#modalFormWali").on("shown.bs.modal", function () {
        snapshotForm(formWali);
      });
    } else {
      formWali.addEventListener("shown.bs.modal", function () {
        snapshotForm(formWali);
      });
    }
    formWali.addEventListener("submit", function (e) {
      e.preventDefault();

      // Compute changed fields for validation, but send full form data
      const changes = getChangedFormData(formWali);
      if (Object.keys(changes).length === 0) {
        if (typeof swal !== "undefined") {
          swal({
            title: "Peringatan!",
            text: "Harap ubah minimal satu field data wali sebelum disimpan!",
            icon: "warning",
            timer: 2500,
          });
        }
        // Reset any loading state on the form save button
        resetFormSaveButton(formWali);
        return;
      }
      if (typeof console !== "undefined" && Object.keys(changes).length)
        console.debug("Submitting changes (wali) - changed fields:", changes);

      // Client-side validation for wali: telp_wali must contain only digits if provided
      var clientErrorsW = [];
      var elTelpW = document.getElementById("telp_wali");
      if (elTelpW && elTelpW.value.trim() && /\D/.test(elTelpW.value.trim()))
        clientErrorsW.push("Nomor telepon wali hanya boleh berisi angka.");
      if (clientErrorsW.length) {
        if (typeof swal !== "undefined") {
          swal({
            title: "",
            text: clientErrorsW.join("\n"),
            icon: "error",
            timer: 4500,
          });
        } else {
          showAlert(clientErrorsW.join("\n"), "danger");
        }
        resetFormSaveButton(formWali);
        return;
      }

      var serializedWali = $(formWali).serialize();

      $.ajax({
        type: "POST",
        // send wali data to the unified endpoint
        url: "./mod/edit-identitas/proses.php?action=usulan_identitas",
        data: serializedWali,
        beforeSend: function () {
          loading();
        },
        success: function (data, textStatus, jqXHR) {
          var body =
            typeof data === "string" ? data : JSON.stringify(data) || "";
          body = (body || "").trim();
          // Accept several success variants: 'success', '1', or JSON {status:'success'}
          var ok = false;
          try {
            if (typeof data === "object" && data !== null) {
              if (
                data.status &&
                String(data.status).toLowerCase() === "success"
              )
                ok = true;
            }
          } catch (e) {}
          if (!ok) {
            if (body === "success" || body === "1" || /success/i.test(body))
              ok = true;
          }
          if (ok) {
            if (typeof swal !== "undefined") {
              swal({
                title: "Berhasil!",
                text: "Usulan perubahan data wali telah dikirim!",
                icon: "success",
                timer: 2000,
              });
            }

            $("#modalFormWali").modal("hide");
            setTimeout(function () {
              window.location.reload();
            }, 2100);
          } else {
            if (!body && jqXHR && jqXHR.responseText)
              body = jqXHR.responseText.trim() || "(empty response)";
            console.error(
              "Save wali failed:",
              jqXHR.status,
              jqXHR.statusText,
              body
            );
            resetFormSaveButton(formWali);
            if (typeof swal !== "undefined") {
              var parsed = formatAlertMessage(body || "");
              swal({
                title: "",
                text:
                  parsed.body ||
                  "Terjadi kesalahan saat menyimpan. Cek log server.",
                icon: "error",
                timer: 4500,
              });
            }
          }
        },
        error: function (jqXHR, status, error) {
          console.error(
            "AJAX error (wali):",
            status,
            error,
            jqXHR.responseText
          );
          resetFormSaveButton(formWali);
          var msg = "Terjadi kesalahan koneksi.";
          if (jqXHR && jqXHR.responseText) msg += " " + jqXHR.responseText;
          if (typeof swal !== "undefined") {
            var parsedErr =
              jqXHR && jqXHR.responseText
                ? formatAlertMessage(jqXHR.responseText).body
                : msg;
            swal({
              title: "",
              text: parsedErr || msg,
              icon: "error",
              timer: 4500,
            });
          }
        },
      });
    });
  }
}

// Fix scrolling issues
function fixScrollingIssues() {
  // Ensure body can scroll
  document.body.style.overflow = "auto";
  document.body.style.overflowX = "hidden";

  // Add proper padding for footer navigation
  document.body.style.paddingBottom =
    window.innerWidth <= 768 ? "120px" : "100px";
}

// Initialize animations
function initializeAnimations() {
  // Add smooth transitions to cards
  var cards = document.querySelectorAll(".card");
  cards.forEach(function (card, index) {
    card.style.opacity = "0";
    card.style.transform = "translateY(20px)";

    setTimeout(function () {
      card.style.transition = "opacity 0.6s ease, transform 0.6s ease";
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    }, index * 100);
  });
}

// Form validation helper
function validateForm(form) {
  const requiredFields = form.querySelectorAll("[required]");
  let isValid = true;

  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      field.classList.add("is-invalid");
      isValid = false;
    } else {
      field.classList.remove("is-invalid");
    }
  });

  return isValid;
}

// Return array of human-friendly labels for required fields that are empty
function getMissingRequired(form) {
  const missing = [];
  const requiredFields = form.querySelectorAll("[required]");
  requiredFields.forEach((field) => {
    if (!field.value || !String(field.value).trim()) {
      // Try to find a label that targets this field
      let labelText = null;
      if (field.id) {
        const lab =
          form.querySelector('label[for="' + field.id + '"]') ||
          document.querySelector('label[for="' + field.id + '"]');
        if (lab) labelText = lab.textContent.trim();
      }
      if (!labelText) labelText = field.name || field.id || "(field)";
      // clean label text (remove trailing asterisks if present)
      labelText = labelText.replace(/\*|\(required\)/gi, "").trim();
      missing.push(labelText);
    }
  });
  return missing;
}

// Update progress status
function updateProgressStatus() {
  const statusBadge = document.querySelector(".badge");
  const progressBar = document.querySelector(".progress-bar");
  const progressText = document.querySelector("small");

  if (statusBadge) {
    statusBadge.className = "badge bg-success fs-6 px-3 py-2";
    statusBadge.innerHTML =
      '<i class="fas fa-paper-plane me-1"></i>Berhasil Dikirim';
  }

  if (progressBar) {
    progressBar.style.width = "25%";
    progressBar.className =
      "progress-bar progress-bar-striped progress-bar-animated bg-success";
  }

  if (progressText) {
    progressText.textContent = "25% Complete";
  }

  // Update progress steps
  const firstStep = document.querySelector(".col-3:first-child div");
  if (firstStep) {
    firstStep.className = "text-success";
  }
}

// Add new history row
function addNewHistoryRow(formType, formData) {
  const tableBody = document.querySelector("tbody");
  if (!tableBody) return;

  // Remove empty state if exists
  const emptyRow = tableBody.querySelector('td[colspan="4"]');
  if (emptyRow) {
    emptyRow.closest("tr").remove();
  }

  // Create new row
  const newRow = document.createElement("tr");
  newRow.style.opacity = "0";
  newRow.style.transform = "translateY(20px)";

  const jenisDataMap = {
    default:
      '<span class="badge bg-primary text-white badge-jenis">Perubahan Data</span>',
  };

  const currentDate = new Date();
  const dateStr = currentDate.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
  const timeStr = currentDate.toLocaleTimeString("id-ID", {
    hour: "2-digit",
    minute: "2-digit",
  });

  newRow.innerHTML = `
    <td class="text-center">1</td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-warning btn-edit-usulan" data-id="" data-keterangan="" title="Edit usulan"><i class="fas fa-edit"></i></button>
      <button type="button" class="btn btn-sm btn-danger btn-delete-usulan ms-1" data-id="" title="Hapus usulan"><i class="fas fa-trash"></i></button>
    </td>
    <td>${jenisDataMap["default"]}</td>
    <td>
      <small class="text-muted">
        ${dateStr}<br>
        ${timeStr}
      </small>
    </td>
  `;

  // Insert at the beginning
  tableBody.insertBefore(newRow, tableBody.firstChild);

  // Animate the new row
  setTimeout(() => {
    newRow.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    newRow.style.opacity = "1";
    newRow.style.transform = "translateY(0)";
  }, 100);

  // Update row numbers
  updateRowNumbers();
}

// Helpers to snapshot form original values and collect changed fields
function snapshotForm(form) {
  if (!form) return {};
  const map = {};
  const elements = form.querySelectorAll(
    "input[name],select[name],textarea[name]"
  );
  elements.forEach((el) => {
    if (!el.name) return;
    if (el.type === "checkbox" || el.type === "radio") {
      if (el.checked) map[el.name] = el.value;
      else if (!(el.name in map)) map[el.name] = "";
    } else {
      map[el.name] = el.value !== undefined ? el.value : "";
    }
  });
  try {
    form._originalValues = map;
  } catch (e) {}
  return map;
}

function getChangedFormData(form) {
  const out = {};
  if (!form) return out;
  const originals = form._originalValues || snapshotForm(form);
  const elements = form.querySelectorAll(
    "input[name],select[name],textarea[name]"
  );
  elements.forEach((el) => {
    if (!el.name) return;
    let val = "";
    if (el.type === "checkbox") {
      val = el.checked ? el.value : "";
    } else if (el.type === "radio") {
      if (!el.checked) return; // only submit checked radio
      val = el.value;
    } else {
      val = el.value !== undefined ? el.value : "";
    }
    const orig = originals.hasOwnProperty(el.name) ? originals[el.name] : "";
    if (String((val || "").trim()) !== String((orig || "").trim())) {
      out[el.name] = val;
    }
  });
  return out;
}

// Update row numbers
function updateRowNumbers() {
  const rows = document.querySelectorAll("tbody tr");
  rows.forEach((row, index) => {
    const numberCell = row.querySelector("td:first-child");
    if (numberCell && !row.querySelector("td[colspan]")) {
      numberCell.textContent = index + 1;
    }
  });
}

// Table interactions
function initializeTableInteractions() {
  const tableRows = document.querySelectorAll(".table tbody tr");

  tableRows.forEach((row) => {
    row.addEventListener("mouseenter", function () {
      if (!this.querySelector("td[colspan]")) {
        this.style.transform = "scale(1.002)";
        this.style.boxShadow = "0 4px 15px rgba(0, 123, 255, 0.1)";
      }
    });

    row.addEventListener("mouseleave", function () {
      this.style.transform = "scale(1)";
      this.style.boxShadow = "none";
    });
  });
}

// Initialize history action handlers (edit / delete)
function initializeHistoryActions() {
  // Delegate using jQuery if available for dynamic rows
  if (typeof $ !== "undefined") {
    $(document).on("click", ".btn-delete-usulan", function (e) {
      e.preventDefault();
      const id = $(this).data("id") || "";
      const row = $(this).closest("tr");

      const doRemoveRow = function () {
        row.remove();
        updateRowNumbers();
        showSuccessMessage("Usulan dihapus");
      };

      if (!id) {
        // No server id (client-only row) — just remove
        doRemoveRow();
        return;
      }

      // Confirm
      if (typeof swal !== "undefined") {
        swal({
          title: "Konfirmasi",
          text: "Yakin ingin menghapus usulan ini?",
          icon: "warning",
          buttons: true,
          dangerMode: true,
        }).then((willDelete) => {
          if (willDelete) {
            $.post("./mod/edit-identitas/proses.php?action=hapus_usulan", {
              id: id,
            })
              .done(function (data) {
                var resp = data;
                try {
                  if (typeof data === "string") resp = JSON.parse(data);
                } catch (e) {}
                var ok = false;
                if (typeof resp === "object" && resp !== null) {
                  if (
                    resp.status &&
                    String(resp.status).toLowerCase() === "success"
                  )
                    ok = true;
                  if (!ok && resp.message && /success/i.test(resp.message))
                    ok = true;
                } else if (typeof resp === "string") {
                  if (resp.trim() === "success" || resp.trim() === "1")
                    ok = true;
                }
                if (ok) {
                  doRemoveRow();
                  // Give user a moment to see the success, then reload to refresh state
                  setTimeout(function () {
                    window.location.reload();
                  }, 800);
                } else {
                  showAlert(
                    (resp && resp.message) || resp || "Gagal menghapus usulan",
                    "danger"
                  );
                }
              })
              .fail(function () {
                showAlert("Terjadi kesalahan koneksi saat menghapus", "danger");
              });
          }
        });
      } else {
        if (confirm("Yakin ingin menghapus usulan ini?")) {
          $.post("./mod/edit-identitas/proses.php?action=hapus_usulan", {
            id: id,
          })
            .done(function (data) {
              var resp = data;
              try {
                if (typeof data === "string") resp = JSON.parse(data);
              } catch (e) {}
              var ok = false;
              if (typeof resp === "object" && resp !== null) {
                if (
                  resp.status &&
                  String(resp.status).toLowerCase() === "success"
                )
                  ok = true;
                if (!ok && resp.message && /success/i.test(resp.message))
                  ok = true;
              } else if (typeof resp === "string") {
                if (resp.trim() === "success" || resp.trim() === "1") ok = true;
              }
              if (ok) {
                doRemoveRow();
                // Refresh after short delay so user sees change
                setTimeout(function () {
                  window.location.reload();
                }, 800);
              } else {
                showAlert(
                  (resp && resp.message) || resp || "Gagal menghapus usulan",
                  "danger"
                );
              }
            })
            .fail(function () {
              showAlert("Terjadi kesalahan koneksi saat menghapus", "danger");
            });
        }
      }
    });

    $(document).on("click", ".btn-edit-usulan", function (e) {
      e.preventDefault();
      const k = $(this).attr("data-keterangan") || "";
      const editId = $(this).data("id") || "";
      // set the hidden perubahan id so server treats this as an update
      try {
        if (typeof editId !== "undefined" && editId) {
          $("#perubahan_id").val(editId);
        }
      } catch (err) {}
      if (!k) {
        showAlert("Data usulan tidak tersedia untuk diedit", "warning");
        return;
      }
      let obj = {};
      try {
        obj = JSON.parse(k);
      } catch (err) {
        showAlert("Gagal membaca data usulan", "danger");
        return;
      }

      // Snapshot current form values before populating (so we know originals)
      let formIdentitasEl = null;
      try {
        formIdentitasEl = document.getElementById("formIdentitas");
        if (formIdentitasEl) snapshotForm(formIdentitasEl);
      } catch (err) {}

      // Populate the Identitas form fields with the submitted changes (scoped to the form)
      const fieldsToPopulate = [
        // Personal
        "nama_lengkap",
        "no_kk",
        "nik",
        "jenis_kelamin",
        "tempat_lahir",
        "tanggal_lahir",
        "agama",

        // Data Keluarga
        "status_keluarga",
        "anak_ke",

        // Alamat
        "alamat",
        "rt",
        "rw",
        "desa",
        "kecamatan",
        "kodepos",

        // Kontak & Data Sekolah
        "telp",
        "email",
        "sekolah_asal",
        "diterima_dikelas",
        "diterima_tanggal",

        // Orang Tua
        "nik_ayah",
        "nama_ayah",
        "pekerjaan_ayah",
        "nik_ibu",
        "nama_ibu",
        "pekerjaan_ibu",

        // Wali
        "nama_wali",
        "telp_wali",
        "alamat_wali",
        "pekerjaan_wali",
      ];
      fieldsToPopulate.forEach(function (f) {
        if (!obj.hasOwnProperty(f)) return;
        let val = obj[f];
        // If stored as {old:..., new:...}, prefer the new value
        if (val && typeof val === "object" && val.hasOwnProperty("new")) {
          val = val["new"];
        }
        // Try to find the element inside the form first (avoid duplicate IDs on page)
        let el = null;
        try {
          if (formIdentitasEl)
            el = formIdentitasEl.querySelector('[name="' + f + '"]');
        } catch (e) {
          el = null;
        }
        // Fallback to global id if not found
        if (!el) el = document.getElementById(f);
        if (!el) return;
        try {
          if (
            el.tagName === "SELECT" ||
            el.tagName === "INPUT" ||
            el.tagName === "TEXTAREA"
          ) {
            el.value = val;
            // trigger change event for any listeners
            try {
              el.dispatchEvent(new Event("change"));
            } catch (e) {}
          }
        } catch (e) {}
      });

      // Show modal for editing
      if (typeof $("#modalFormIdentitas").modal === "function") {
        $("#modalFormIdentitas").modal("show");
      }
    });
    // View approved usulan
    $(document).on("click", ".btn-view-usulan", function (e) {
      e.preventDefault();
      const k = $(this).attr("data-keterangan") || "";
      if (!k) {
        showAlert("Data usulan tidak tersedia", "warning");
        return;
      }
      let obj = {};
      try {
        obj = JSON.parse(k);
      } catch (err) {
        showAlert("Gagal membaca data usulan", "danger");
        return;
      }

      // Build a simple human-friendly rendering: ringkasan + field -> old / new
      const labelMap = {
        nama_lengkap: "Nama Lengkap",
        no_kk: "Nomor KK",
        nik: "NIK",
        jenis_kelamin: "Jenis Kelamin",
        tempat_lahir: "Tempat Lahir",
        tanggal_lahir: "Tanggal Lahir",
        agama: "Agama",
        nik_ayah: "NIK Ayah",
        nama_ayah: "Nama Ayah",
        nik_ibu: "NIK Ibu",
        nama_ibu: "Nama Ibu",
        nama_wali: "Nama Wali",
        telp_wali: "Telp Wali",
      };

      const labels = [];
      const rows = [];
      for (let kf in obj) {
        if (!Object.prototype.hasOwnProperty.call(obj, kf)) continue;
        if (kf.indexOf("_") === 0) continue;
        let val = obj[kf];
        let oldv = "";
        let newv = "";
        if (val && typeof val === "object") {
          oldv = val.old || "";
          newv = val.new || "";
        } else {
          newv = val || "";
        }
        if (oldv || newv) {
          rows.push({ label: labelMap[kf] || kf, oldv: oldv, newv: newv });
          labels.push(labelMap[kf] || kf);
        }
      }

      // Render ringkasan
      const ringkasan = labels.slice(0, 6).join(", ");
      $("#viewUsulanRingkasan").html(
        ringkasan ? "<strong>" + ringkasan + "</strong>" : "Detail perubahan"
      );

      // Render details table-like with visual diff highlighting
      // Old values: muted; New values (when changed): highlighted + bold
      let html =
        '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Field</th><th>Data Lama</th><th>Data Baru</th></tr></thead><tbody>';
      rows.forEach(function (r) {
        const oldvSafe = $("<div>")
          .text(r.oldv || "")
          .html();
        const newvSafe = $("<div>")
          .text(r.newv || "")
          .html();
        // Consider values different if trimmed string differs
        const changed =
          String((r.oldv || "").trim()) !== String((r.newv || "").trim());
        const oldClass = changed ? "text-muted" : "";
        const newClass = changed ? "text-success fw-bold" : "";

        html +=
          "<tr>" +
          "<td>" +
          $("<div>").text(r.label).html() +
          "</td>" +
          '<td class="' +
          oldClass +
          '">' +
          oldvSafe +
          "</td>" +
          '<td class="' +
          newClass +
          '">' +
          newvSafe +
          "</td>" +
          "</tr>";
      });
      html += "</tbody></table></div>";
      $("#viewUsulanDetails").html(html);

      if (typeof $("#modalViewUsulan").modal === "function") {
        $("#modalViewUsulan").modal("show");
      }
    });
    // Clear perubahan_id when modal is hidden (so new submissions are not mistaken for edits)
    $(document).on("hidden.bs.modal", "#modalFormIdentitas", function () {
      try {
        $("#perubahan_id").val("");
        // reset any save button state
        resetFormSaveButton(document.getElementById("formIdentitas"));
      } catch (e) {}
    });
  }
}

// Utility functions
function showAlert(message, type = "info") {
  try {
    // Normalize message signature to avoid duplicate alerts
    const normalized =
      typeof message === "string" ? message.trim() : JSON.stringify(message);
    const existing = document.querySelectorAll(".edit-identitas-alert");
    for (let el of existing) {
      if (el.dataset && el.dataset.hash === normalized) {
        // refresh timeout and return
        clearTimeout(el._removeTimer);
        el._removeTimer = setTimeout(() => {
          try {
            if (el.parentNode) el.parentNode.removeChild(el);
          } catch (e) {}
        }, 5000);
        return;
      }
    }

    const { body } = formatAlertMessage(message, type);

    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show edit-identitas-alert`;
    alertDiv.setAttribute("role", "alert");
    alertDiv.style.cssText = "z-index:11000; min-width:320px; max-width:420px;";
    alertDiv.dataset.hash = normalized;

    // Prefer to place the alert inside an open modal if present so it's visually attached
    const modalOpen = document.querySelector(".modal.show");
    const container = modalOpen
      ? modalOpen.querySelector(".modal-content") || modalOpen
      : document.body;

    alertDiv.innerHTML = `
      <div class="d-flex">
        <div class="me-2 align-self-start"><i class="fas fa-${getAlertIcon(
          type
        )}"></i></div>
        <div style="flex:1;">
          <div>${body}</div>
        </div>
        <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;

    if (container === document.body) {
      alertDiv.style.position = "fixed";
      alertDiv.style.top = "20px";
      alertDiv.style.right = "20px";
    } else {
      alertDiv.style.position = "relative";
      alertDiv.style.marginBottom = "12px";
    }

    container.appendChild(alertDiv);

    // Auto remove after 5 seconds
    alertDiv._removeTimer = setTimeout(() => {
      try {
        if (alertDiv.parentNode) alertDiv.parentNode.removeChild(alertDiv);
      } catch (e) {}
    }, 5000);
  } catch (e) {
    // fallback to native alert if something unexpected happens
    try {
      window.alert(
        typeof message === "string" ? message : JSON.stringify(message)
      );
    } catch (ie) {}
  }
}

function showSuccessMessage(message) {
  showAlert(message, "success");
}

// Helper to normalize various message shapes into a title/body pair
function formatAlertMessage(message, type) {
  // Always return only the user-facing message text (no title).
  let body = "";

  if (typeof message === "string") {
    // try to parse JSON-like responses and pick `message` property if present
    try {
      const parsed = JSON.parse(message);
      if (parsed && typeof parsed === "object") {
        body = parsed.message || parsed.msg || JSON.stringify(parsed);
      } else {
        body = message;
      }
    } catch (e) {
      body = message;
    }
  } else if (typeof message === "object" && message !== null) {
    body = message.message || message.msg || JSON.stringify(message);
  } else {
    body = String(message);
  }

  // Strip tags to avoid accidental HTML injection
  body = String(body).replace(/<[^>]*>?/gm, "");
  return { title: "", body: body };
}

function getAlertIcon(type) {
  const icons = {
    success: "check-circle",
    warning: "exclamation-triangle",
    danger: "times-circle",
    info: "info-circle",
  };
  return icons[type] || "info-circle";
}

// Legacy loadData function for compatibility
function loadData() {
  initializeModule();
}

// Auto-initialize when DOM is ready
if (typeof $ !== "undefined") {
  $(document).ready(function () {
    // Immediate check if module is already loaded
    if (!window.moduleLoaded) {
      setTimeout(function () {
        initializeModule();
      }, 100);
    }
  });
} else {
  // Fallback if jQuery is not available
  document.addEventListener("DOMContentLoaded", function () {
    if (!window.moduleLoaded) {
      setTimeout(function () {
        initializeModule();
      }, 100);
    }
  });
}

// Window resize handler
$(window).on("resize", function () {
  document.body.style.paddingBottom =
    window.innerWidth <= 768 ? "120px" : "100px";
});

// Export functions for global access
window.loading = loading;
window.loadData = loadData;
window.initializeModule = initializeModule;
window.showSuccessMessage = showSuccessMessage;
window.showAlert = showAlert;

/**
 * Modal Form Identitas Peserta Didik - JavaScript
 * Handles form validation, user interactions, and data processing
 */

/**
 * Modal Form Identitas Peserta Didik - JavaScript
 * Handles form validation, user interactions, and data processing
 */

class IdentitasFormHandler {
  constructor() {
    this.form = document.getElementById("formIdentitas");
    this.modal = document.getElementById("modalFormIdentitas");
    this.submitBtn = null;
    this.originalBtnText = "";
    this.init();
  }

  /**
   * Initialize the form handler
   */
  init() {
    if (!this.form || !this.modal) {
      console.error("Form or modal element not found");
      return;
    }
    this.submitBtn = this.form.querySelector(".btn-save");
    this.originalBtnText = this.submitBtn ? this.submitBtn.innerHTML : "";
    this.bindEvents();
    this.setupValidationRules();
  }

  /**
   * Bind all event listeners
   */
  bindEvents() {
    // Form submission
    this.form.addEventListener("submit", (e) => this.handleSubmit(e));

    // Real-time validation
    this.form.addEventListener("input", (e) => this.handleInput(e));
    this.form.addEventListener("change", (e) => this.handleChange(e));

    // Modal events
    this.modal.addEventListener("shown.bs.modal", () => this.onModalShown());
    this.modal.addEventListener("hidden.bs.modal", () => this.onModalHidden());

    // Special input formatting
    this.setupInputFormatting();
  }

  /**
   * Setup input formatting for specific fields
   */
  setupInputFormatting() {
    const nikInput = document.getElementById("nik");
    const telInput = document.getElementById("telp");
    const kodePosInput = document.getElementById("kodepos");
    const rtInput = document.getElementById("rt");
    const rwInput = document.getElementById("rw");
    const anakKeInput = document.getElementById("anak_ke");

    // NIK: Only numbers, max 16 digits
    if (nikInput) {
      nikInput.addEventListener("input", (e) => {
        e.target.value = e.target.value.replace(/\D/g, "").substring(0, 16);
        this.validateNIK(e.target);
      });
    }

    // Phone: Only numbers, format Indonesian phone
    if (telInput) {
      telInput.addEventListener("input", (e) => {
        let value = e.target.value.replace(/\D/g, "");
        // Auto-format Indonesian phone number
        if (value.startsWith("8") && value.length > 1) {
          value = "0" + value;
        }
        e.target.value = value.substring(0, 15);
        this.validatePhone(e.target);
      });
    }

    // Postal code: Only numbers, max 5 digits
    if (kodePosInput) {
      kodePosInput.addEventListener("input", (e) => {
        e.target.value = e.target.value.replace(/\D/g, "").substring(0, 5);
      });
    }

    // RT/RW: Only numbers, max 3 digits
    [rtInput, rwInput].forEach((input) => {
      if (input) {
        input.addEventListener("input", (e) => {
          e.target.value = e.target.value.replace(/\D/g, "").substring(0, 3);
        });
      }
    });

    // Anak ke: Only numbers, reasonable range
    if (anakKeInput) {
      anakKeInput.addEventListener("input", (e) => {
        let value = parseInt(e.target.value) || 0;
        if (value > 20) value = 20;
        if (value < 0) value = 0;
        e.target.value = value || "";
      });
    }
  }

  /**
   * Setup custom validation rules
   */
  setupValidationRules() {
    const emailInput = document.getElementById("email");
    const nikInput = document.getElementById("nik");
    const telInput = document.getElementById("telp");

    // Email validation
    if (emailInput) {
      emailInput.addEventListener("blur", (e) => this.validateEmail(e.target));
    }

    // NIK validation
    if (nikInput) {
      nikInput.addEventListener("blur", (e) => this.validateNIK(e.target));
    }

    // Phone validation
    if (telInput) {
      telInput.addEventListener("blur", (e) => this.validatePhone(e.target));
    }
  }

  /**
   * Handle form input events
   */
  handleInput(e) {
    const input = e.target;
    // Remove invalid class if input becomes valid
    if (input.checkValidity()) {
      input.classList.remove("is-invalid");
      input.classList.add("is-valid");
    }
  }

  /**
   * Handle form change events
   */
  handleChange(e) {
    this.handleInput(e);
  }

  /**
   * Validate NIK format and length
   */
  validateNIK(input) {
    const value = input.value.trim();
    const isValid = /^\d{16}$/.test(value);
    if (value && !isValid) {
      input.classList.add("is-invalid");
      input.classList.remove("is-valid");
    } else if (value && isValid) {
      input.classList.remove("is-invalid");
      input.classList.add("is-valid");
    }
    return isValid || !value;
  }

  /**
   * Validate Nomor KK format and length
   */
  validateNoKK(input) {
    const value = input.value.trim();
    const isValid = /^\d{16}$/.test(value);
    if (value && !isValid) {
      input.classList.add("is-invalid");
      input.classList.remove("is-valid");
    } else if (value && isValid) {
      input.classList.remove("is-invalid");
      input.classList.add("is-valid");
    }
    return isValid || !value;
  }

  /**
   * Validate email format
   */
  validateEmail(input) {
    const value = input.value.trim();
    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    if (value && !isValid) {
      input.classList.add("is-invalid");
      input.classList.remove("is-valid");
    } else if (value && isValid) {
      input.classList.remove("is-invalid");
      input.classList.add("is-valid");
    }
    return isValid || !value;
  }

  /**
   * Validate phone number format
   */
  validatePhone(input) {
    const value = input.value.trim();
    const isValid = /^0[8-9]\d{8,12}$/.test(value);
    if (value && !isValid) {
      input.classList.add("is-invalid");
      input.classList.remove("is-valid");
    } else if (value && isValid) {
      input.classList.remove("is-invalid");
      input.classList.add("is-valid");
    }
    return isValid || !value;
  }
}
