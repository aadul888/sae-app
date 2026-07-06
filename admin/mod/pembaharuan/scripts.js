'use strict';
// Module: Pembaharuan - client-side logic for listing and CRUD
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


// timepicker not used in pembaharuan module




/** Module Posisi */
$(document).ready(function() {
    loadData();
});
function loadData(){
    //datatables
    $('.datatable').DataTable({
        "scrollX": true,
        "scrollCollapse": true,
        "responsive": false,
        "autoWidth": false,
        "processing": true, 
        "serverSide": false, 
        "bSort": false,
        "bStateSave": true,
        "bDestroy" : true,
        "paging": true,
        "aaSorting" : [[0, 'desc']],
        "iDisplayLength": 25,
        "aLengthMenu": [
            [25, 30, 50, -1],
            [25, 30, 50, "All"]
        ],
        language: {
          lengthMenu: "Tampilkan _MENU_ data",
          zeroRecords: "Data tidak ditemukan",
          info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
          infoEmpty: "Tidak ada data",
          search: "Cari:",
          paginate: {
            previous: "<i class='fas fa-angle-left'>",
            next: "<i class='fas fa-angle-right'>"
          }
        },
        "ajax": {
            "url": "./mod/pembaharuan/datatable.php",
            "type": "GET"
        },
        "columnDefs": [{ 
            "targets": [ 0 ], 
            "orderable": false, 
        },],
    });
}


/* -------- MODAL ADD Pembaharuan */
$(document).on('click', '.btn-add', function(){
    $('.modal-add').modal('show');
    $('.modal-title-name').html('Tambah Pembaharuan baru');
    $(".form-add").trigger("reset");
});
    // hint for pembaharuan field
    $('.pembaharuan').attr('placeholder', 'Satu poin per baris, misal:\nPerbaikan UI login\nTambah fitur export PDF');


    /** Tambah / Update Pembaharuan  */
    $(".form-add").validate({
        rules: {
            version: { required: true },
            release_date: { required: true }
        },
        messages: {
            version: { required: "Silahkan masukkan versi" },
            release_date: { required: "Silahkan masukkan tanggal rilis" }
        },
        submitHandler: submitForm_Add
    });

    function submitForm_Add() {
        var data = $(".form-add").serialize();
        $.ajax({
            type: 'POST',
            url: './mod/pembaharuan/proses.php?action=add',
            data: data,
            cache: false,
            beforeSend: function() { loading(); },
            success: function (data) {
                if ($.trim(data) == 'success') {
                    swal({title: 'Berhasil!', text: 'Data berhasil disimpan.!', icon: 'success', timer: 2000,});
                    $(".form-add").trigger("reset");
                    $('.modal-add').modal('hide');
                    loadData();
                } else {
                    swal({title: 'Oops!', text: data, icon: 'error', timer: 2500,});
                }
            }
        });
        return false;
    }

    /**  Update pembaharuan */
    $(document).on('click', '.btn-update', function(){
        var id = $(this).data("id");
        var version = $(this).data("version");
        var release = $(this).data("release");
        var mandatory = $(this).data("mandatory");
    var link = $(this).data("link");
    // prefer explicit data attributes set by server: data-pembaharuan and data-perbaikan
    var pembaharuan = $(this).data("pembaharuan") || $(this).data("desc") || '';
    var perbaikan = $(this).data("perbaikan") || '';
        $('.id').val(id);
        $('.version').val(version);
    $('.release_date').val(release);
        $('.mandatory').val(mandatory);
        $('.download_link').val(link);
        $('.pembaharuan').val(pembaharuan);
        $('.perbaikan').val(perbaikan);
        $('.modal-title-name').html('Edit ' + version);
        $('.modal-add').modal('show');
    });


/** Hapus data pembaharuan */
    $(document).on('click', '.btn-delete', function(){ 
    var id = $(this).attr("data-id");
    var name = $(this).attr("data-name");
    swal({
        text: "Anda yakin ingin menghapus kelas " + name + ".?",
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
                url:'./mod/pembaharuan/proses.php?action=delete',
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
        } else {  
            return false;
        }  
    });
});

// No mass-update features required for pembaharuan module.
