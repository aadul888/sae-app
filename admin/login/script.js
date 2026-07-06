/* On click password reset button */
$(".passwordResetBtn").click(function () {
  $(".forgot-show").show();
  $(".login-show").hide();
});

/* On click Loginbutton */
$(".loginBtn").click(function () {
  $(".forgot-show").hide();
  $(".login-show").show();
});

/* ----------- LOGIN ------------*/
$(".login").submit(function (e) {
  e.preventDefault();
  if ($(".username").val() == "" && $(".password").val() == "") {
    swal({
      title: "Oops!",
      text: "Harap bidang inputan tidak boleh ada yang kosong.!",
      icon: "error",
      timer: 2500,
    });
    return false;
    //loading();
  } else {
    //loading();
    $.ajax({
      url: "./proses.php?action=login",
      type: "POST",
      data: new FormData(this),
      processData: false,
      contentType: false,
      cache: false,
      async: false,
      beforeSend: function () {
        // loading();
      },
      success: function (data) {
        if (typeof data === "string" && data.indexOf("redirect:registrasi") === 0) {
          swal({
            title: "Sinkronisasi Wajib",
            text: "Tarik data gagal atau belum lengkap. Anda akan diarahkan ke halaman registrasi.",
            icon: "warning",
            timer: 1800,
          });
          setTimeout(function () {
            var pathname = window.location.pathname || "/";
            var appRoot = pathname.replace(/\/admin\/login(?:\/.*)?$/i, "/");
            if (!/\/$/.test(appRoot)) {
              appRoot += "/";
            }
            window.location.href = appRoot + "registrasi/";
          }, 1600);
          return;
        }

        if (data == "success") {
          swal({
            title: "Berhasil!",
            text: "Selamat datang.!",
            icon: "success",
            timer: 1500,
          });
          setTimeout(function () {
            window.location.href = "../";
          }, 2000);
        } else {
          swal({ title: "Oops!", text: data, icon: "error", timer: 2000 });
        }
      },
      complete: function () {},
    });
  }
});

/* ----------- Forgot ------------*/
$(".forgot").submit(function (e) {
  e.preventDefault();
  $.ajax({
    url: "./proses.php?action=forgot",
    type: "POST",
    data: new FormData(this),
    processData: false,
    contentType: false,
    cache: false,
    async: false,

    success: function (data) {
      if (data == "success") {
        swal({
          title: "Berhasil!",
          text: "Selamat Password Anda berhasil kami Resset Ulang.!",
          icon: "success",
          timer: 3500,
        });
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 3500 });
      }
    },
    complete: function () {},
  });
});

/* Toggle password visibility (eye icon) */
$(document).on("click", ".toggle-password", function (e) {
  e.preventDefault();
  var $btn = $(this);
  var $icon = $btn.find("i");
  var $input = $btn.closest(".input-group").find("input").first();
  if ($input.attr("type") === "password") {
    $input.attr("type", "text");
    $icon.removeClass("fa-eye").addClass("fa-eye-slash");
  } else {
    $input.attr("type", "password");
    $icon.removeClass("fa-eye-slash").addClass("fa-eye");
  }
});
