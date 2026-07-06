'use strict';
 'use strict';

// Clean single script for Kriteria PIP: DataTable + CRUD via modal
$(function(){
  var table = null;

  function initDataTable(){
    if (typeof $.fn.DataTable === 'undefined') return setTimeout(initDataTable, 50);
    table = $('.datatable').DataTable({
      processing: true,
      serverSide: true,
      scrollX: true,
      scrollCollapse: true,
      responsive: false,
      ajax: { url: './mod/kriteria-pip/datatable.php', type: 'POST', dataSrc: function(json){ return json.data || []; } },
      columns: [ { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4, orderable: false } ],
      pageLength: 25,
      lengthMenu: [[25,50,-1],[25,50,'All']]
    });
  }

  function reloadTable(){ if (table && table.ajax) table.ajax.reload(null, false); }

  function resetForm(){
    $('#kriteriaForm')[0].reset();
    $('#kriteria_id').val('');
    $('#nama_kriteria_select').val('');
    $('#nama_kriteria_custom').hide().val('');
    $('#nama_kriteria_hidden').val('');
  }

  // Add
  $(document).on('click', '.btn-add', function(e){ e.preventDefault(); resetForm(); $('#kriteriaModal').modal('show'); });

  // Edit
  $(document).on('click', '.btn-edit-kriteria', function(e){
    e.preventDefault();
    var enc = $(this).data('id');
    if(!enc) return alert('ID tidak tersedia');
    $.get('./mod/kriteria-pip/proses.php?action=get_kriteria', { id: enc }, function(res){
      if(res && res.success){
        var d = res.data;
        $('#kriteria_id').val(enc);
        // try to match existing name to an option
        var matched = false;
        $('#nama_kriteria_select option').each(function(){
          if($.trim($(this).text()) === $.trim(d.nama_kriteria)){
            $('#nama_kriteria_select').val($(this).val());
            matched = true;
            return false;
          }
        });
        if(!matched){
          $('#nama_kriteria_select').val('__lainnya__');
          $('#nama_kriteria_custom').show().val(d.nama_kriteria);
          $('#nama_kriteria_hidden').val(d.nama_kriteria);
        } else {
          $('#nama_kriteria_custom').hide().val('');
          $('#nama_kriteria_hidden').val(d.nama_kriteria);
        }
        $('#deskripsi').val(d.deskripsi);
        $('#poin').val(d.poin);
        $('#kriteriaModal').modal('show');
      } else alert('Data tidak ditemukan');
    }, 'json').fail(function(){ alert('Gagal memuat data'); });
  });

  // Show/hide custom input when select changes
  $(document).on('change', '#nama_kriteria_select', function(){
    if($(this).val() === '__lainnya__'){
      $('#nama_kriteria_custom').show().focus();
      $('#nama_kriteria_hidden').val($('#nama_kriteria_custom').val());
    } else {
      $('#nama_kriteria_custom').hide().val('');
      var text = $(this).val() === '' ? '' : $('#nama_kriteria_select option:selected').text();
      $('#nama_kriteria_hidden').val(text);
    }
  });

  // Save
  $(document).on('click', '.btn-save-kriteria', function(e){
    e.preventDefault();
    var selected = $('#nama_kriteria_select').val();
    var namaVal = '';
    if(selected === '__lainnya__'){
      namaVal = $('#nama_kriteria_custom').val().trim();
    } else {
      if(selected === ''){
        namaVal = '';
      } else {
        namaVal = $('#nama_kriteria_select option:selected').text();
      }
    }
    $('#nama_kriteria_hidden').val(namaVal);
    var data = $('#kriteriaForm').serialize();
    $.post('./mod/kriteria-pip/proses.php?action=save_kriteria', data, function(res){ if(res && res.success){ $('#kriteriaModal').modal('hide'); reloadTable(); } else { alert('Gagal menyimpan: ' + (res.message || 'error')); } }, 'json').fail(function(){ alert('Request gagal'); });
  });

  // Delete
  $(document).on('click', '.btn-delete-kriteria', function(e){
    e.preventDefault();
    if(!confirm('Hapus data ini?')) return;
    var enc = $(this).data('id');
    $.post('./mod/kriteria-pip/proses.php?action=delete_kriteria', { id: enc }, function(res){ if(res && res.success) reloadTable(); else alert('Gagal menghapus: ' + (res.message || 'error')); }, 'json').fail(function(){ alert('Request gagal'); });
  });

  initDataTable();
});