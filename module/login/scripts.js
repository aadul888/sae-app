"use strict";

// Switch between Login Murid and Login Admin tabs
function switchLoginTab(type) {
  var muridPane = document.getElementById('loginPaneMurid');
  var adminPane = document.getElementById('loginPaneAdmin');
  var tabMurid  = document.getElementById('tabMurid');
  var tabAdmin  = document.getElementById('tabAdmin');
  if (!muridPane || !adminPane) return;
  if (type === 'admin') {
    muridPane.style.display = 'none';
    adminPane.style.display = '';
    tabMurid.classList.remove('active');
    tabAdmin.classList.add('active');
    tabMurid.setAttribute('aria-selected', 'false');
    tabAdmin.setAttribute('aria-selected', 'true');
  } else {
    adminPane.style.display = 'none';
    muridPane.style.display = '';
    tabAdmin.classList.remove('active');
    tabMurid.classList.add('active');
    tabAdmin.setAttribute('aria-selected', 'false');
    tabMurid.setAttribute('aria-selected', 'true');
  }
}

// Password requirements checking (live)
$(document).on("input", "#newPassword", function () {
  var val = $(this).val();
  
  // Check each requirement
  var requirements = [
    { id: "#req-length", test: val.length >= 6 },
    { id: "#req-uppercase", test: /[A-Z]/.test(val) },
    { id: "#req-lowercase", test: /[a-z]/.test(val) },
    { id: "#req-number", test: /[0-9]/.test(val) },
    { id: "#req-special", test: /[^A-Za-z0-9]/.test(val) }
  ];
  
  // Update each requirement indicator
  requirements.forEach(function(req) {
    var element = $(req.id);
    if (req.test) {
      element.addClass('valid');
    } else {
      element.removeClass('valid');
    }
  });

  // Update confirmation password indicator if exists
  var confirmVal = $("#confirmPassword").val();
  var matchIndicator = $("#passwordMatchIndicator");
  var matchElement = $("#password-match");
  
  if (confirmVal.length > 0) {
    matchIndicator.show();
    
    if (val === confirmVal) {
      // Password cocok
      matchElement.addClass('valid');
      matchElement.find('.requirement-text').text('Password sama');
    } else {
      // Password tidak cocok
      matchElement.removeClass('valid');
      matchElement.find('.requirement-text').text('Password tidak sama');
    }
  } else {
    // Sembunyikan indikator jika konfirmasi kosong
    matchIndicator.hide();
  }
  
  // Update legacy progress bar if exists
  if ($("#passwordStrengthBarConfirm").length > 0) {
    var score = requirements.filter(req => req.test).length;
    var percent = (score / 5) * 100;
    var color = score === 5 ? "#28a745" : "#dc3545";
    
    if (val === confirmVal) {
      $("#passwordStrengthBarConfirm").css({
        width: percent + "%",
        backgroundColor: color,
      });
    } else {
      $("#passwordStrengthBarConfirm").css({
        width: "100%",
        backgroundColor: "#dc3545",
      });
    }
  }
});

// Confirmation password checking
$(document).on("input", "#confirmPassword", function () {
  var newVal = $("#newPassword").val();
  var confirmVal = $(this).val();
  var matchIndicator = $("#passwordMatchIndicator");
  var matchElement = $("#password-match");
  
  if (confirmVal.length > 0) {
    matchIndicator.show();
    
    if (confirmVal === newVal && newVal.length > 0) {
      // Password cocok
      matchElement.addClass('valid');
      matchElement.find('.requirement-text').text('Password sama');
    } else {
      // Password tidak cocok
      matchElement.removeClass('valid');
      matchElement.find('.requirement-text').text('Password tidak sama');
    }
  } else {
    // Sembunyikan indikator jika input kosong
    matchIndicator.hide();
  }
  
  // Update legacy progress bar jika ada
  if ($("#passwordStrengthBarConfirm").length > 0) {
    var requirements = [
      newVal.length >= 6,
      /[A-Z]/.test(newVal),
      /[a-z]/.test(newVal),
      /[0-9]/.test(newVal),
      /[^A-Za-z0-9]/.test(newVal)
    ];
    
    var score = requirements.filter(req => req).length;
    var percent = (score / 5) * 100;
    var color = score === 5 ? "#28a745" : "#ffc107";
    
    if (confirmVal === newVal && confirmVal.length > 0) {
      $("#passwordStrengthBarConfirm").css({
        width: percent + "%",
        backgroundColor: color,
      });
    } else if (confirmVal.length > 0) {
      $("#passwordStrengthBarConfirm").css({
        width: "100%",
        backgroundColor: "#dc3545",
      });
    } else {
      $("#passwordStrengthBarConfirm").css({ width: "0%" });
    }
  }
});

// Proses update password via AJAX
$(document).on("submit", "#formUpdatePassword", function (e) {
  e.preventDefault();
  // Client-side password policy validation: min 6, uppercase, lowercase, digit, symbol
  var newPassword = $("#newPassword").val() || "";
  var confirmPassword = $("#confirmPassword").val() || "";
  var policyRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{6,}$/;
  if (!policyRegex.test(newPassword)) {
    swal({
      title: "Gagal",
      text: "Password harus minimal 6 karakter dan mengandung huruf besar, huruf kecil, angka, dan simbol.",
      icon: "error",
    });
    return;
  }
  if (newPassword !== confirmPassword) {
    swal({
      title: "Gagal",
      text: "Konfirmasi password tidak sama.",
      icon: "error",
    });
    return;
  }

  var btn = $(this).find('button[type="submit"]');
  btn.prop("disabled", true).text("Memproses...");
  $.ajax({
    url: "./module/login/proses.php?action=update_password",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        $("#updatePasswordModal").modal("hide");
        swal({
          title: "Berhasil!",
          text: res.msg,
          icon: "success",
          timer: 2500,
        });
        setTimeout(function () {
          location.reload();
        }, 2000);
      } else {
        swal({ title: "Gagal", text: res.msg, icon: "error" });
      }
    },
    error: function () {
      swal({
        title: "Gagal",
        text: "Terjadi kesalahan server.",
        icon: "error",
      });
    },
    complete: function () {
      btn.prop("disabled", false).text("Update Password");
    },
  });
});

// Proses lupa password via AJAX
$(document).on("submit", "#formLupaPassword", function (e) {
  e.preventDefault();
  
  var nomor_hp = $("#nomorHP").val().trim();
  if (!nomor_hp) {
    swal({
      title: "Gagal",
      text: "Nomor HP tidak boleh kosong.",
      icon: "error",
    });
    return;
  }
  
  // Validasi format nomor HP Indonesia
  var hp_pattern = /^(08|62)[0-9]{8,12}$/;
  var cleaned_hp = nomor_hp.replace(/[^0-9]/g, '');
  if (!hp_pattern.test(cleaned_hp)) {
    swal({
      title: "Gagal",
      text: "Format nomor HP tidak valid. Gunakan format: 08xxxxxxxxx",
      icon: "error",
    });
    return;
  }

  var btn = $(this).find('button[type="submit"]');
  var originalText = btn.html();
  btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mengirim...');
  
  $.ajax({
    url: "./module/login/proses.php?action=lupa_password",
    type: "POST",
    data: $(this).serialize(),
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        $("#lupaPasswordModal").modal("hide");
        $("#formLupaPassword")[0].reset();
        swal({
          title: "Berhasil!",
          text: res.msg,
          icon: "success",
          timer: 5000,
        });
      } else if (res.status === "warning") {
        $("#lupaPasswordModal").modal("hide");
        $("#formLupaPassword")[0].reset();
        swal({
          title: "Peringatan!",
          text: res.msg,
          icon: "warning",
          timer: 5000,
        });
      } else {
        swal({ title: "Gagal", text: res.msg, icon: "error" });
      }
    },
    error: function () {
      swal({
        title: "Gagal",
        text: "Terjadi kesalahan server. Silakan coba lagi.",
        icon: "error",
      });
    },
    complete: function () {
      btn.prop("disabled", false).html(originalText);
    },
  });
});
function loading() {
  $(".btn-save").prop("disabled", true);
  // add spinner to button
  $(".btn-save").html(
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
  );
  window.setTimeout(function () {
    $(".btn-save").prop("disabled", false);
    $(".btn-save").html('<i class="fas fa-search"></i> Cari Data Saya');
  }, 2000);
}

$(".password").keypress(function (e) {
  if (e.which === 32) return false;
});

function loading_login() {
  $(".btn-loading").prop("disabled", true);
  // add spinner to button
  $(".btn-loading").html(
    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
  );
  window.setTimeout(function () {
    $(".btn-loading").prop("disabled", false);
    $(".btn-loading").html("Login");
  }, 3000);
}

$(document.body).on("click", ".toggle-password", function () {
  $(this).toggleClass("fa-eye-slash");
  var input = $($(this).attr("toggle"));
  if (input.attr("type") == "password") {
    input.attr("type", "text");
  } else {
    input.attr("type", "password");
  }
});

$(".form-login").submit(function (e) {
  loading();
  e.preventDefault();
  $.ajax({
    url: "./module/login/proses.php?action=login",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    async: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      if (typeof data === 'string' && data.indexOf('sso:') === 0) {
        // redirect for SSO
        window.location = data.substr(4);
        return;
      }
      if (data == "success") {
        $(".form-login").trigger("reset");
        swal({
          title: "Berhasil!",
          text: "Berhasil login\nSelamat datang!",
          icon: "success",
          timer: 2500,
        });
        $(".password").val("");
        setTimeout(function () {
          window.location.href = "./dashboard/home";
        }, 2500);
      } else if (data == "success_default_password") {
        // Langsung tampilkan modal update password tanpa swal error
        showUpdatePasswordModal();
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2500 });
      }
    },
    complete: function () {
      $(".loading").hide();
    },
  });
});

document.addEventListener("DOMContentLoaded", function () {
  var urlParams = new URLSearchParams(window.location.search);
  var nisn = urlParams.get("nisn");
  if (nisn) {
    var input = document.querySelector('input[name="username"]');
    if (input) {
      input.value = nisn;
    }
  }
});

// Fungsi untuk menampilkan modal update password
function showUpdatePasswordModal() {
  var modal = new bootstrap.Modal(
    document.getElementById("updatePasswordModal")
  );
  modal.show();
}

// Event handler untuk tombol lupa password
$(document).on("click", "#lupaPasswordBtn", function(e) {
  e.preventDefault();
  var modal = new bootstrap.Modal(
    document.getElementById("lupaPasswordModal")
  );
  modal.show();
});

// Tambahkan elemen bar progress di bawah input konfirmasi password jika belum ada
$(document).ready(function () {
  if (
    $("#confirmPassword").length &&
    $("#passwordStrengthBarConfirm").length === 0
  ) {
    $("#confirmPassword").after(
      '<div class="progress mt-2" style="height:6px;"><div id="passwordStrengthBarConfirm" class="progress-bar" role="progressbar" style="width:0%"></div></div>'
    );
  }
});
