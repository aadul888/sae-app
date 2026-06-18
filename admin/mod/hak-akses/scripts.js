'use strict';
function loading(){
    $('.btn-save').prop("disabled", true);
      // add spinner to button
      $('.btn-save').html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...'
      );
     window.setTimeout(function () {
      $('.btn-save').prop("disabled", false);
      $('.btn-save').html('<i class="far fa-save"></i> Simpan'
      );
    }, 2000);
}

function applyLevelUiState(){
  var $meta = $('.load-data .hak-meta');
  var isOperator = $meta.attr('data-operator') === '1';
  var levelName = $meta.attr('data-level-name') || $('.title-header').text();

  if (isOperator) {
    $('.btn-add').prop('disabled', true).attr('title', 'Operator Sekolah adalah superadmin dan otomatis memiliki semua modul');
    $('.module-hint').text('Operator Sekolah adalah superadmin, penambahan modul manual tidak diperlukan.');
  } else {
    $('.btn-add').prop('disabled', false).attr('title', 'Tambah');
    $('.module-hint').text('Daftar modul untuk ' + levelName + ' hanya menampilkan modul yang sesuai aturan level.');
  }
}

  function loadModuleOptions(levelId){
    var $select = $('.form-add select[name="modul_id"]');
    if (!$select.length) {
      return;
    }

    $select.html('<option value="">Memuat modul...</option>');
    $.get('./mod/hak-akses/proses.php?action=module_options&id=' + levelId, function (html) {
      $select.html(html);
    }).fail(function () {
      $select.html('<option value="">Gagal memuat modul</option>');
    });
  }

function loadData(id){
  $(".load-data").html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Loading data...</p></div>');
  $(".load-data").load("mod/hak-akses/proses.php?action=data&id="+id+"", function () {
    applyLevelUiState();
  });
  $(".level").val(id);
    loadModuleOptions(id);
}

var $firstTab = $('.btn-tab').first();
if ($firstTab.length) {
  $firstTab.addClass('active');
  $('.title-header').html($firstTab.attr('data-name'));
  loadData($firstTab.attr('data-id'));
}


$(document).on('click', '.btn-tab', function(){
  $('.btn-tab').removeClass('active');
  $(this).addClass('active');
  var name = $(this).attr("data-name");
  var id = $(this).attr('data-id');
  $('.title-header').html(name);
  loadData(id);
});



/** Tambah data */
$(document).on('click', '.btn-add', function(){
  if ($(this).prop('disabled')) {
    return;
  }
  $('.modal-add').modal('show');
  $('.modal-title').html('Tambah hak akses');
  loadModuleOptions($('.level').val());
});


/** Add Hak akses */
$('.form-add').submit(function (e) {
  var id = $('.level').val();
  e.preventDefault();
  loading();
      $.ajax({
          url:"./mod/hak-akses/proses.php?action=add",
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
              if (data == 'success') {
                  swal({title: 'Berhasil!', text: 'Hak akses berhasil disimpan.!', icon: 'success', timer: 1500,});
                $('.form-add')[0].reset();
                  loadData(id);
              } else {
                  swal({title: 'Oops!', text: data, icon: 'error', timer: 1500,});
              }

          },
          complete: function () {
              
          },
      });
}); 


/* ------------- Set Active Lihat--------------*/
$(document).on('click', '.lihat', function(){
var id = $(this).attr("data-id");
var active = $(this).attr("data-active");

 var dataString = 'id='+ id + '&active='+ active;
$.ajax({
    type: "POST",
    url: "./mod/hak-akses/proses.php?action=lihat",
    data: dataString,
  success: function (data) {
    if (data == 'success') {
      // success - no debug log in production
    } else {
       // no-op on error here (handled elsewhere)
    }
    }
});
});


/* ------------- Set Active modifikasi --------------*/
$(document).on('click', '.modifikasi', function(){
var id = $(this).attr("data-id");
var active = $(this).attr("data-active");

 var dataString = 'id='+ id + '&active='+ active;
$.ajax({
    type: "POST",
    url: "./mod/hak-akses/proses.php?action=modifikasi",
    data: dataString,
  success: function (data) {
    if (data == 'success') {
      // success - nothing to log
    } else {
       // no-op on error here (handled elsewhere)
    }
    }
});
});

/* ------------- Set Active Hapus --------------*/
$(document).on('click', '.hapus', function(){
var id = $(this).attr("data-id");
var active = $(this).attr("data-active");

 var dataString = 'id='+ id + '&active='+ active;
$.ajax({
    type: "POST",
    url: "./mod/hak-akses/proses.php?action=hapus",
    data: dataString,
  success: function (data) {
    if (data == 'success') {
      // success - nothing to log in production
    } else {
       // no-op on error here (handled elsewhere)
    }
    }
});
});

/* ------------- Delete Role Entry --------------*/
$(document).on('click', '.btn-delete-role', function(){
  var id = $(this).attr("data-id");
  var levelId = $('.level').val();
  swal({
    title: 'Hapus Role?',
    text: 'Role ini akan dihapus permanen.',
    icon: 'warning',
    buttons: ['Batal', 'Ya, Hapus!'],
    dangerMode: true,
  }).then(function(willDelete) {
    if (willDelete) {
      $.ajax({
        type: "POST",
        url: "./mod/hak-akses/proses.php?action=delete_role",
        data: { id: id },
        success: function (data) {
          if (data == 'success') {
            swal({ title: 'Berhasil!', text: 'Role berhasil dihapus.', icon: 'success', timer: 1500 });
            loadData(levelId);
          } else {
            swal({ title: 'Gagal!', text: 'Gagal menghapus role.', icon: 'error', timer: 1500 });
          }
        }
      });
    }
  });
});
    
    