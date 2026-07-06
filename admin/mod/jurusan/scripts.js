'use strict';

$(document).on('change', '.jurusan-logo', function() {
    var preview = $('#logo-preview');
    preview.html('');
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.html('<img src="' + e.target.result + '" alt="Preview Logo" style="max-width:100px;max-height:100px;border:1px solid #ddd;padding:2px;border-radius:6px;">');
        };
        reader.readAsDataURL(this.files[0]);
    }
});

function loading() {
    $('.btn-save').prop('disabled', true);
    $('.btn-save').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
    window.setTimeout(function() {
        $('.btn-save').prop('disabled', false);
        $('.btn-save').html('<i class="far fa-save"></i> Simpan Perubahan');
    }, 2000);
}

$(document).ready(function() {
    loadData();
});

function loadData() {
    $('.datatable').DataTable({
        scrollY: false,
        scrollX: false,
        processing: true,
        serverSide: false,
        bAutoWidth: true,
        bSort: true,
        bStateSave: true,
        bDestroy: true,
        paging: true,
        aaSorting: [[2, 'asc'], [4, 'asc']],
        iDisplayLength: 25,
        aLengthMenu: [
            [25, 30, 50, -1],
            [25, 30, 50, 'All']
        ],
        language: {
            paginate: {
                previous: "<i class='fas fa-angle-left'>",
                next: "<i class='fas fa-angle-right'>"
            }
        },
        ajax: {
            url: './mod/jurusan/datatable.php',
            type: 'POST'
        },
        columnDefs: [{
            targets: [0],
            orderable: false
        }]
    });
}

$(document).on('click', '.btn-update', function() {
    var id = $(this).attr('data-id');
    var kode = $(this).attr('data-kode');
    var name = $(this).attr('data-name');

    $('.id').val(id);
    $('.jurusan-kode').val(kode);
    $('.jurusan-nama').val(name);
    $('.modal-title-name').html(name);
    $('#logo-preview').html('');
    $('.jurusan-logo').val('');
    $('.modal-add').modal('show');
});

$('.form-add').validate({
    rules: {
        kode_jurusan: { required: true }
    },
    messages: {
        kode_jurusan: { required: 'Kode jurusan wajib diisi' }
    },
    submitHandler: submitForm_Logo
});

function submitForm_Logo() {
    var form = $('.form-add')[0];
    var formData = new FormData(form);

    $.ajax({
        type: 'POST',
        url: './mod/jurusan/proses.php?action=update_meta',
        data: formData,
        cache: false,
        async: false,
        contentType: false,
        processData: false,
        beforeSend: function() {
            loading();
        },
        success: function(data) {
            if (data === 'success') {
                swal({
                    title: 'Berhasil!',
                    text: 'Data jurusan berhasil diperbarui.',
                    icon: 'success',
                    timer: 2500
                });
                $('.form-add').trigger('reset');
                $('#logo-preview').html('');
                $('.modal-add').modal('hide');
                loadData();
            } else {
                swal({
                    title: 'Oops!',
                    text: data,
                    icon: 'error',
                    timer: 2500
                });
            }
        }
    });

    return false;
}
