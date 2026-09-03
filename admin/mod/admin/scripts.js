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


/** Module Admin */
loadData();
function loadData(){
    var table;
    $(document).ready(function() {
        //datatables
    table = $('.datatable-user').DataTable({
      "fnDrawCallback": function () {
        $('.open-popup-link').magnificPopup({
        type: 'image',
        removalDelay: 300,
        mainClass: 'mfp-fade',
          gallery: {
            enabled: true
          },
          zoom: {
            enabled: true,
            duration: 300,
            easing: 'ease-in-out',
            opener: function (openerElement) {
              return openerElement.is('img') ? openerElement : openerElement.find('img');
            }
          }
        });
      },
      "processing": true,
      "serverSide": true,
      "bAutoWidth": true,
      "bSort": true,
      "bStateSave": true,
      "bDestroy" : true,
      "paging": true,
      "iDisplayLength": 25,
      "order": [[2, 'asc']],
      "aLengthMenu": [
        [25, 30, 50, -1],
        [25, 30, 50, "All"]
      ],
      language: {
        paginate: {
        previous: "<i class='fas fa-angle-left'>",
        next: "<i class='fas fa-angle-right'>"
        }
      },
      "ajax": {
        "url": "./mod/admin/datatable.php",
        "type": "POST",
        "data": function(d) {
          d.filter_level = $('#filter-level').val();
          d.filter_tugas = $('#filter-tugas').val();
        }
      },
      "columnDefs": [
        { "targets": [0, 1, 7, 8], "orderable": false },
        { "targets": [2, 3, 4, 5, 6], "orderable": true }
      ],
      "scrollX": true,
      "scrollCollapse": true,
      "responsive": false,
      "autoWidth": false,
    });

    // Filter modal handlers
    $(document).on('click', '.btn-apply-filter-admin', function() {
      table.ajax.reload();
      $('#modalFilterAdmin').modal('hide');
    });
    $(document).on('click', '.btn-reset-filter-admin', function() {
      $('#filter-level').val('');
      $('#filter-tugas').val('');
      table.ajax.reload();
      $('#modalFilterAdmin').modal('hide');
    });

    });
}

    $("body").on("click", ".datepicker", function(){
        $(this).datepicker({
          format: 'dd-mm-yyyy',
          autoclose:true
        });
        $(this).datepicker("show");
    });


    /** Tambah User/Pegawai */
    $('.password').keypress(function( e ) {
        if(e.which === 32) 
        return false;
    });


    $(".toggle-password").click(function() {
        $(this).toggleClass("fa-eye fa-eye-slash");
        var input = $($(this).attr("toggle"));
        if (input.attr("type") == "password") {
          input.attr("type", "text");
        } else {
          input.attr("type", "password");
        }
      });

      
    /** Check Email */
    /*$(".email").keyup(function(){
        var email = $(this).val().trim();
        if(email != ''){
           $.ajax({
              url: './mod/admin/proses.php?action=check-email',
              type: 'post',
              data: {email:email},
              success: function(response){
                  $('.email-response').html(response);
               }
           });
        }else{
           $(".email-response").html("");
        }
  
      });

    /** Add */

    $(".form-add").validate({
        // Specify validation rules
        rules: {
            field: {
                required: true
            },
            email: {
                required: true,
                email: true
            },

            telp: {
                required: true,
                number: true
            },
            password: {
                required: true,
                minlength: 6,
                maxlength: 15
            },
            alamat: {
                required: true,
                minlength: 10,
                maxlength: 150
            }
        },

        // Specify validation error messages
        messages: {
            field: {
                required: "Silahkan masukkan data sesuai inputan",
            },
          password: {
            required: "Please provide a password",
            minlength: "Password anda paling sedikit berisi 6 karakter"
          },
          email: {
            required: "Silahkan masukkan alamat email anda",
            email: "Email seharusnya dalam format: smakpalapik@gmail.com"
          },
        },
        // in the "action" attribute of the form when valid
        submitHandler: submitForm_Add
      });

    /* handle form submit */
    function submitForm_Add() { 
        // Use FormData to handle array inputs properly
        var form = $(".form-add")[0];
        var formData = new FormData(form);
        
        $.ajax({    
            type : 'POST',
            url  : './mod/admin/proses.php?action=add',
            data : formData,
            processData: false,
            contentType: false,
            cache: false,
            async: false,
            beforeSend: function() { 
                loading();
            },
            success: function (data) {
                if (data == 'success') {
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500,});
                    $(".form-add").trigger("reset");
                    // Clear tugas tambahan tags akan dihandle otomatis oleh form reset handler
                    window.setTimeout(function() {
                        window.location.href = "./admin";
                    }, 2500);
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
        return false; 
    }


    /* ------ Update Admin ------- */
  $(".form-update").validate({
    rules: {
      fullname: { required: true },
      username: { required: true },
      phone: { required: true, number: true },
      email: { required: true, email: true },
      level: { required: true },
    },
    messages: {
      fullname: { required: "Nama Lengkap tidak boleh kosong" },
      username: { required: "Username tidak boleh kosong" },
      phone: { required: "No. Telp tidak boleh kosong", number: "Hanya angka" },
      email: { required: "Silahkan masukkan alamat email anda", email: "Format email salah" },
      level: { required: "Level utama wajib dipilih" },
    },
    submitHandler: submitForm_Update
  });

  function submitForm_Update() {
    // Ambil data form manual agar tugas_tambahan[] multiselect ikut terkirim
    var form = $(".form-update")[0];
    var formData = new FormData(form);
    $.ajax({
      type: 'POST',
      url: './mod/admin/proses.php?action=update',
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      async: false,
      beforeSend: function () {
        loading();
      },
      success: function (data) {
        if (data == 'success') {
          swal({ title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2500 });
          $(".form-add").trigger("reset");
          setTimeout(function () { history.back(); }, 3000);
        } else {
          swal({ title: 'Oops!', text: data, icon: 'error', timer: 2500 });
        }
      }
    });
    return false;
  }

    /** ------- Forgot ---------- */
    $(document).on('click', '.btn-forgot', function(){ 
        var id = $(this).attr("data-id");
        var name = $(this).attr("data-name");
          swal({
            title: "Resset Password!",
            text: "Anda yakin ingin meresset password "+name+".?\r\nPassword baru: 123456",
            icon: "info",
              buttons: {
                cancel: true,
                confirm: true,
              },
            value: "yes",
          })

          .then((value) => {
            if(value) {
                loading();
                $.ajax({  
                     url:'./mod/admin/proses.php?action=forgot',
                     type:'POST',    
                     data:{id:id},  
                    success:function(data){ 
                        if (data == 'success') {
                            swal({title: 'Berhasil!', text: 'Password berhasil direset.!', icon: 'success', timer: 2500,});
                            loadData();
                        } else {
                            swal({title: 'Gagal!', text: data, icon: 'error', timer:2500,});
                            
                        }
                     }  
                });  
           } else{  
            return false;
        }  
    });
}); 



    /* ------------- Set Active User --------------*/
    $(document).on('click', '.btn-active', function(){
        var id = $(this).attr("data-id");
        var active = $(".active"+id).attr("data-active");
        if(active == "Y"){
            var dataactive = "N";
        }else{
            var dataactive = "Y";
        }
         var dataString = 'id='+ id + '&active='+ dataactive;
        $.ajax({
            type: "POST",
            url: "./mod/admin/proses.php?action=active",
            data: dataString,
            success: function (data) {
                if(active == "Y"){
                    $(".active"+id).attr("data-active","N");
                }else{
                    $(".active"+id).attr("data-active","Y");
                }
    
              if (data == 'success') {
                    // debug removed: Successfully set active
                }else{
                   // debug removed: data
                }
            }
        });
      });


/** Hapus data Admin */
$(document).on('click', '.btn-delete', function(){ 
    var id = $(this).attr("data-id");
    var name = $(this).attr("data-name");
      swal({
        text: "Anda yakin ingin menghapus user "+name+".?",
        icon: "warning",
          buttons: {
            cancel: true,
            confirm: true,
          },
        value: "yes",
      })

      .then((value) => {
        if(value) {
            loading();
            $.ajax({  
                 url:'./mod/admin/proses.php?action=delete',
                 type:'POST',    
                 data:{id:id},  
                success:function(data){ 
                    if (data == 'success') {
                        swal({title: 'Berhasil!', text: 'Data berhasil dihapus.!', icon: 'success', timer: 2500,});
                        loadData();
                    } else {
                        swal({title: 'Gagal!', text: data, icon: 'error', timer:2500,});
                        
                    }
                 }  
            });  
          } else{  
            return false;
          }  
    });  
}); 


// Proses Import Admin: Tampilkan modal dan reset form
$(document).on("click", ".btn-import-admin", function () {
  $("#importAdminModal").modal("show");
  $("#importAdminModal form").trigger("reset");
});

// Handler submit form import admin (lebih spesifik dan pasti bekerja)
$(document).on("submit", "#form-import-admin", function (e) {
  e.preventDefault();
  loading();
  var form = this;
  $.ajax({
    url: "./mod/admin/proses.php?action=import",
    type: "POST",
    data: new FormData(form),
    processData: false,
    contentType: false,
    cache: false,
    async: false,
    beforeSend: function () {
      loading();
    },
    success: function (data) {
      if (typeof data === 'string' && data.toLowerCase().includes("import berhasil")) {
        swal({
          title: "Berhasil!",
          text: data,
          icon: "success",
          timer: 2500,
        });
        $("#importAdminModal form").trigger("reset");
        $("#importAdminModal").modal("hide");
        loadData();
      } else {
        let msg = (typeof data === 'string' && data.trim().length > 0) ? data : 'Gagal tanpa pesan detail. Cek console/network.';
        swal({ title: "Gagal!", text: msg, icon: "error", timer: 10000 });
        if (typeof data === 'string') {
          console.error('Import Admin Error:', data);
        } else {
          console.error('Import Admin Error: Response tidak terdefinisi atau kosong', data);
        }
      }
    },
    error: function(xhr, status, error) {
      swal({ title: "Gagal!", text: 'AJAX Error: ' + error, icon: "error", timer: 6000 });
      console.error('AJAX Error:', error, xhr.responseText);
    },
    complete: function () {},
  });
});

// ==================== TUGAS TAMBAHAN HANDLER ====================
$(document).ready(function() {
  
  // Update dropdown state and placeholder text
  function updateDropdownState() {
    const dropdown = $("#add-tugas-select");
    dropdown.show().prop('disabled', false).css({
      'display': 'inline-block !important',
      'visibility': 'visible !important',
      'opacity': '1 !important'
    });
  }
  
  // Build jurusan select HTML
  function buildJurusanSelect(levelId) {
    if (typeof JURUSAN_DATA === 'undefined' || !JURUSAN_DATA.length) return '';
    var html = '<select class="jurusan-select" data-for-level="' + levelId + '" ' +
               'style="font-size: 11px; border: 1px solid #5e72e4; background: #fff; padding: 2px 6px; ' +
               'border-radius: 4px; margin-left: 4px; color: #333; max-width: 120px;">';
    html += '<option value="">Jurusan?</option>';
    for (var i = 0; i < JURUSAN_DATA.length; i++) {
      var j = JURUSAN_DATA[i];
      html += '<option value="' + j.jurusan_id + '">' + j.kode_jurusan + ' - ' + j.nama_jurusan + '</option>';
    }
    html += '</select>';
    return html;
  }
  
  // Create and add a new tugas tag
  function addTugasTag(levelId, levelName, needJurusan) {
    if (needJurusan == '1' || needJurusan === true) {
      // Show jurusan selector inline before creating tag
      var jurusanHtml = buildJurusanSelect(levelId);
      var pendingTag = `
        <span class="badge badge-warning tugas-tag-pending d-inline-flex align-items-center" 
              data-level-id="${levelId}" data-level-name="${levelName}"
              style="font-size: 12px; padding: 6px 10px; border-radius: 15px; gap: 5px; line-height: 1;">
          <span>${levelName.toUpperCase()}</span>
          ${jurusanHtml}
          <i class="fas fa-times cancel-pending" 
             style="cursor: pointer; font-size: 10px; opacity: 0.8;" 
             title="Batal"></i>
        </span>`;
      $("#add-tugas-select").before(pendingTag);
    } else {
      createFinalTag(levelId, levelName, '', '');
    }
  }
  
  // Create the final confirmed tag
  function createFinalTag(levelId, levelName, jurusanId, jurusanKode) {
    var displayName = levelName.toUpperCase();
    var hiddenValue = levelId;
    if (jurusanId && jurusanId !== '') {
      displayName += ' - ' + jurusanKode;
      hiddenValue = levelId + ':' + jurusanId;
    }
    const newTag = `
      <span class="badge badge-info tugas-tag d-inline-flex align-items-center" 
            data-level-id="${levelId}" data-jurusan-id="${jurusanId}"
            style="font-size: 12px; padding: 6px 10px; border-radius: 15px; gap: 5px; line-height: 1;">
        <span>${displayName}</span>
        <i class="fas fa-times remove-tag" 
           style="cursor: pointer; font-size: 10px; opacity: 0.8;" 
           title="Hapus tugas tambahan"></i>
        <input type="hidden" name="tugas_tambahan[]" value="${hiddenValue}">
      </span>`;
    
    $("#add-tugas-select").before(newTag);
  }
  
  // Handle jurusan selection from pending tag
  $(document).on("change", ".jurusan-select", function() {
    var $this = $(this);
    var jurusanId = $this.val();
    if (!jurusanId) return;
    
    var jurusanKode = $this.find("option:selected").text().trim();
    var $pending = $this.closest(".tugas-tag-pending");
    var levelId = $pending.data("level-id");
    var levelName = $pending.data("level-name");
    
    $pending.remove();
    createFinalTag(levelId, levelName, jurusanId, jurusanKode);
    updateDropdownState();
  });
  
  // Cancel pending jurusan selection
  $(document).on("click", ".cancel-pending", function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $pending = $(this).closest(".tugas-tag-pending");
    var levelId = $pending.data("level-id");
    
    // Show option back in dropdown
    $("#add-tugas-select").find('option[value="' + levelId + '"]').show();
    $pending.remove();
    updateDropdownState();
  });
  
  // Handle dropdown selection
  $(document).on("change", "#add-tugas-select", function() {
    const $this = $(this);
    const selectedValue = $this.val();
    const selectedOption = $this.find("option:selected");
    const selectedText = selectedOption.data("name") || selectedOption.text().trim();
    const needJurusan = selectedOption.data("need-jurusan");
    
    if (selectedValue && selectedText && selectedValue !== "") {
      addTugasTag(selectedValue, selectedText, needJurusan);
      selectedOption.hide();
      $this.val("");
      updateDropdownState();
    }
  });
  
  // Handle tag removal
  $(document).on("click", ".remove-tag", function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const tag = $(this).closest(".tugas-tag");
    const levelId = tag.data("level-id");
    
    // Show option back in dropdown
    const dropdown = $("#add-tugas-select");
    const hiddenOption = dropdown.find(`option[value="${levelId}"]`);
    
    if (hiddenOption.length > 0) {
      hiddenOption.show();
    }
    
    tag.remove();
    updateDropdownState();
  });
  
  // Handle level utama change - reset tugas tambahan options
  $(document).on("change", "select[name='level']", function() {
    const selectedLevelId = $(this).val();
    const tugasDropdown = $("#add-tugas-select");
    
    // Clear existing tags and reset all options
    $(".selected-tags .tugas-tag").remove();
    tugasDropdown.find("option:not(:first)").show();
    tugasDropdown.val("");
    
    // Hide selected level utama from tugas tambahan options
    if (selectedLevelId && selectedLevelId !== "" && selectedLevelId !== "0") {
      tugasDropdown.find(`option[value="${selectedLevelId}"]`).hide();
    }
    
    updateDropdownState();
  });
  
  // Clear tugas tambahan when form is reset
  $(document).on("reset", ".form-add, .form-update", function() {
    setTimeout(function() {
      $(".selected-tags .tugas-tag").remove();
      const dropdown = $("#add-tugas-select");
      dropdown.find("option:not(:first)").show();
      dropdown.val("");
      updateDropdownState();
    }, 50);
  });
  
  // Tag hover effects
  $(document).on("mouseenter", ".tugas-tag", function() {
    $(this).css("opacity", "0.8");
  });
  
  $(document).on("mouseleave", ".tugas-tag", function() {
    $(this).css("opacity", "1");
  });
  
  // Initialize dropdown on page load
  function initializeTugasDropdown() {
    const dropdown = $("#add-tugas-select");
    if (dropdown.length > 0) {
      dropdown.find("option:not(:first)").show();
      
      // Hide current level utama option
      const currentLevel = $("select[name='level']").val();
      if (currentLevel && currentLevel !== "" && currentLevel !== "0") {
        dropdown.find(`option[value="${currentLevel}"]`).hide();
      }
      
      updateDropdownState();
    }
  }
  
  // Initialize when ready
  initializeTugasDropdown();
  
  // Ensure initialization after DOM is fully loaded
  setTimeout(initializeTugasDropdown, 100);
  
});