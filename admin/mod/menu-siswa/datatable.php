<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

    // Handle special request for max position
    if (isset($_GET['get_max_position'])) {
        $result = $connection->query("SELECT MAX(position) as max_position FROM student_menu");
        $row = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['max_position' => intval($row['max_position'])]);
        exit;
    }

    $aColumns = array('id', 'label', 'slug', 'position', 'aktif');
    $sIndexColumn = 'id';
    $sTable = 'student_menu';

    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;
    $gaSql['link'] = new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    // Paging
    $sLimit = '';
    if (isset($_REQUEST['start']) && isset($_REQUEST['length']) && $_REQUEST['length'] != '-1') {
        $sLimit = 'LIMIT ' . intval($_REQUEST['start']) . ', ' . intval($_REQUEST['length']);
    } elseif (isset($_REQUEST['iDisplayStart']) && $_REQUEST['iDisplayLength'] != '-1') {
        $sLimit = 'LIMIT ' . intval($_REQUEST['iDisplayStart']) . ', ' . intval($_REQUEST['iDisplayLength']);
    }

    // Ordering
    $sOrder = 'ORDER BY position ASC, id ASC';
    if (isset($_REQUEST['order']) || isset($_REQUEST['iSortCol_0'])) {
        $orderClauses = array();
        $sortingCols = isset($_REQUEST['order']) ? count($_REQUEST['order']) : (isset($_REQUEST['iSortingCols']) ? intval($_REQUEST['iSortingCols']) : 0);
        for ($i = 0; $i < $sortingCols; $i++) {
            if (isset($_REQUEST['order'])) {
                $colIndex = intval($_REQUEST['order'][$i]['column']);
                $dir = strtoupper($_REQUEST['order'][$i]['dir']) === 'DESC' ? 'DESC' : 'ASC';
                $bSortable = isset($_REQUEST['columns'][$colIndex]['orderable']) ? $_REQUEST['columns'][$colIndex]['orderable'] : 'true';
            } else {
                $colIndex = intval($_REQUEST['iSortCol_' . $i]);
                $dir = isset($_REQUEST['sSortDir_' . $i]) ? (strtoupper($_REQUEST['sSortDir_' . $i]) === 'DESC' ? 'DESC' : 'ASC') : 'ASC';
                $bSortable = isset($_REQUEST['bSortable_' . $colIndex]) ? $_REQUEST['bSortable_' . $colIndex] : 'true';
            }
            // Only allow sorting on defined columns (label is at index 1 for our table)
            if ($bSortable == 'true' && isset($aColumns[$colIndex])) {
                $colName = $aColumns[$colIndex];
                // prevent ordering by non-data columns (No / Status)
                if (in_array($colName, array('label', 'slug', 'position', 'aktif'))) {
                    $orderClauses[] = $colName . ' ' . $dir;
                }
            }
        }
        if (count($orderClauses) > 0) {
            $sOrder = 'ORDER BY ' . implode(', ', $orderClauses);
        }
    }

    // Filtering (global)
    $sWhere = '';
    $search = '';
    if (isset($_REQUEST['search']['value'])) {
        $search = $_REQUEST['search']['value'];
    } elseif (isset($_REQUEST['sSearch'])) {
        $search = $_REQUEST['sSearch'];
    }
    if ($search != '') {
        $sWhere = "WHERE (";
        $sWhere .= "label LIKE '%" . mysqli_real_escape_string($gaSql['link'], $search) . "%' OR slug LIKE '%" . mysqli_real_escape_string($gaSql['link'], $search) . "%')";
    }

    // Individual column filtering (optional)
    for ($i = 0; $i < count($aColumns); $i++) {
        if (isset($_REQUEST['columns'][$i]['search']['value']) && $_REQUEST['columns'][$i]['search']['value'] != '') {
            $colSearch = mysqli_real_escape_string($gaSql['link'], $_REQUEST['columns'][$i]['search']['value']);
            if ($sWhere == '') $sWhere = 'WHERE ';
            else $sWhere .= ' AND ';
            $sWhere .= $aColumns[$i] . " LIKE '%" . $colSearch . "%' ";
        }
    }

    // Main query
    $sQuery = "SELECT SQL_CALC_FOUND_ROWS id, label, slug, position, aktif FROM " . $sTable . " " . $sWhere . " " . $sOrder . " " . $sLimit;
    $rResult = mysqli_query($gaSql['link'], $sQuery);

    // Data set length after filtering
    $rResultFilterTotal = mysqli_query($gaSql['link'], "SELECT FOUND_ROWS()");
    $aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];

    // Total data set length
    $rResultTotal = mysqli_query($gaSql['link'], "SELECT COUNT(" . $sIndexColumn . ") FROM " . $sTable);
    $aResultTotal = mysqli_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];

    $output = array(
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
        'iTotalRecords' => $iTotal,
        'iTotalDisplayRecords' => $iFilteredTotal,
        'aaData' => array(),
        'recordsTotal' => $iTotal,
        'recordsFiltered' => $iFilteredTotal,
        'data' => array()
    );

    $start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : (isset($_REQUEST['iDisplayStart']) ? intval($_REQUEST['iDisplayStart']) : 0);
    $no = $start;
    while ($aRow = mysqli_fetch_assoc($rResult)) {
        $no++;
        $row = array();
        $row[] = '<div class="text-center">' . $no . '</div>';
        $row[] = '<div><strong>' . htmlspecialchars($aRow['label']) . '</strong><br><small class="text-muted">Slug: ' . htmlspecialchars($aRow['slug']) . ' | Posisi: ' . intval($aRow['position']) . '</small></div>';
        $checked = ($aRow['aktif'] === 'Y') ? 'checked' : '';
        $row[] = '<div class="text-center"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input student-menu-toggle" id="student_menu_' . intval($aRow['id']) . '" data-id="' . intval($aRow['id']) . '" ' . $checked . '><label class="custom-control-label" for="student_menu_' . intval($aRow['id']) . '"></label></div></div>';
        
        // Action buttons
        $actionButtons = '<div class="text-center">';
        $actionButtons .= '<a href="javascript:void(0)" class="table-action table-action-warning btn-update btn-tooltip" data-id="' . intval($aRow['id']) . '" data-label="' . htmlspecialchars($aRow['label']) . '" data-slug="' . htmlspecialchars($aRow['slug']) . '" data-position="' . intval($aRow['position']) . '" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></a>';
        $actionButtons .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-delete btn-tooltip" data-id="' . intval($aRow['id']) . '" data-toggle="tooltip" title="Hapus"><i class="fas fa-trash"></i></a>';
        $actionButtons .= '</div>';
        $row[] = $actionButtons;

        $output['aaData'][] = $row;
        $output['data'][] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($output);
}
