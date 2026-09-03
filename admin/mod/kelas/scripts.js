'use strict';

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
        aaSorting: [[1, 'asc'], [2, 'asc']],
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
            url: './mod/kelas/datatable.php',
            type: 'POST',
            dataType: 'json',
            dataSrc: function(json) {
                if (!json) return [];
                if (Array.isArray(json.data)) return json.data;
                if (Array.isArray(json.aaData)) return json.aaData;
                if (Array.isArray(json)) return json;
                return [];
            },
            error: function(xhr, status, error) {
                console.error('DataTables AJAX error:', status, error);
                try {
                    console.log('Response text:', xhr.responseText);
                } catch (e) {}
            }
        },
        columnDefs: [{
            targets: [0],
            orderable: false
        }]
    });
}
