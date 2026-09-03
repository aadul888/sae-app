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


$('.timepicker').timepicker({
    showInputs: false,
    showMeridian: false,
    use24hours: true,
    format :'HH:mm'
});

$("body").on("click", ".datepicker", function(){
    $(this).datepicker({
      format: 'dd-mm-yyyy',
      autoclose:true
    });
    $(this).datepicker("show");
});



/** Dropdown - Tidak diperlukan lagi karena sudah menggunakan select kelas langsung */


$(document).on('click', '.btn-filter', function(){
    loadData();
});

$(document).ready(function () {
    if ($('.load-data').length) {
        $('.load-data').html('<div class="alert alert-info text-center"><i class="fas fa-info-circle"></i> Silahkan pilih filter lalu klik tombol Filter.</div>');
    }
});

function loadData() {
    var kelas = $('.kelas').val();
    var bulan = $('.bulan').val();
    var tahun = $('.tahun').val();

    if(kelas == '' || bulan == '' || tahun == ''){
        swal({title: 'Oops!', text: 'Silahkan pilih filter datanya', icon: 'error', timer: 1500,});
    }else{
        $.ajax({
            type: 'POST',
            url  : './mod/laporan-absensi-kelas/proses.php?action=filtering',
            data: {kelas:kelas,bulan:bulan,tahun:tahun},
            cache: false,
            success: function(data){
                $(".load-data").html(data);
            }
        });
    }  
};


/** Pagination */

$(document).on('click', '.btn-pagination', function(){ 
    var kelas = $('.kelas').val();
    var bulan = $('.bulan').val();
    var tahun = $('.tahun').val();

    var month_d = new Array();
    month_d[0] = "Januari";
    month_d[1] = "Februari";
    month_d[2] = "Maret";
    month_d[3] = "April";
    month_d[4] = "Mei";
    month_d[5] = "Juni";
    month_d[6] = "Juli";
    month_d[7] = "Agustus";
    month_d[8] = "September";
    month_d[9] = "Oktober";
    month_d[10] = "November";
    month_d[11] = "Desember";
    var halaman = $(this).attr("data-id");
    var d     = new Date(bulan);
    var n     = month_d[d.getMonth()];
    $('.result-month').html(n);
    $.ajax({
        url: './mod/laporan-absensi-kelas/proses.php?action=filtering&halaman='+halaman+'',
        method:"POST",
        data: {kelas:kelas,bulan:bulan,tahun:tahun},
        dataType:"text",
        cache: false,
        success: function (data) {
            $('.load-data').html(data);
        },
    });
});


/** Print */
$(document).on('click', '.btn-print', function(){
    var tipe        = $(this).attr("data-tipe");
    var kelas   = $('.kelas').val();
    var bulan   = $('.bulan').val();
    var tahun   = $('.tahun').val();
    var url     = "./mod/laporan-absensi-kelas/print.php?action=print&kelas="+kelas+"&bulan="+bulan+"&tahun="+tahun+"&tipe="+tipe+""; 
    window.open(url, '_blank');
});


