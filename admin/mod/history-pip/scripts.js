'use strict';

// History PIP: DataTable with filter
$(function(){
  var table = null;

  function initDataTable(){
    if (typeof $.fn.DataTable === 'undefined') return setTimeout(initDataTable, 50);
    if ($.fn.DataTable.isDataTable('.datatable')) $('.datatable').DataTable().destroy();
    table = $('.datatable').DataTable({
      processing: true,
      serverSide: true,
      bSort: false,
      paging: true,
      scrollX: true,
      scrollCollapse: true,
      responsive: false,
      iDisplayLength: 25,
      aLengthMenu: [[25,50,-1],[25,50,'All']],
      language: {
        paginate: { previous: "<i class='fas fa-angle-left'>", next: "<i class='fas fa-angle-right'>" }
      },
      ajax: {
        url: './mod/history-pip/datatable.php',
        type: 'POST',
        data: function(d){
          d.status = $('#filter-status-history').val();
          d.kelas  = $('#filter-kelas-history').val();
        },
        dataSrc: function(json){ return json.data || []; }
      },
      columns: [
        { data: 0, width: '40px' },
        { data: 1, width: '60px', orderable: false },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
        { data: 7 },
        { data: 8, orderable: false }
      ]
    });
  }

  function reloadTable(){ if (table && table.ajax) table.ajax.reload(null, false); }

  // Filter apply
  $(document).on('click', '.btn-apply-filter-history', function(){
    $('#modalFilterHistoryPip').modal('hide');
    reloadTable();
  });

  // Filter reset
  $(document).on('click', '.btn-reset-filter-history', function(){
    $('#filter-status-history').val('');
    $('#filter-kelas-history').val('');
    reloadTable();
  });

  initDataTable();
});