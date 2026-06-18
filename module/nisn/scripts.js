"use strict";
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

$(".form-nisn").submit(function (e) {
  loading();
  e.preventDefault();
  $.ajax({
    url: "./module/home/proses.php?action=cari",
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
      var results = data.split("/");
      var success = results[0];
      var nisn = results[1];
      if (success == "success") {
        $(".form-nisn").trigger("reset");
        window.setTimeout((window.location.href = "./nisn/" + nisn + ""), 2500);
      } else {
        swal({ title: "Oops!", text: data, icon: "error", timer: 2500 });
      }
    },
    complete: function () {
      $(".loading").hide();
    },
  });
});
