<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

    // Columns for kriteria_pip table
    $aColumns = [
        "kp.id AS id",
        "kp.nama_kriteria AS nama_kriteria",
        "kp.deskripsi AS deskripsi",
        "kp.poin AS poin"
    ];
    $sIndexColumn = "kp.id";
    $sTable = "kriteria_pip kp";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    // Accept either GET or POST (DataTables may use POST depending on configuration)
    $req = &$_REQUEST;
    if (isset($req['iDisplayStart']) && isset($req['iDisplayLength']) && $req['iDisplayLength'] != '-1') {
        $sLimit = "LIMIT " . mysqli_real_escape_string($gaSql['link'], $req['iDisplayStart']) . ", " .
            mysqli_real_escape_string($gaSql['link'], $req['iDisplayLength']);
    }

    // Simple ordering: default by id asc. DataTables client may not send useful column indices.
    $sOrder = "ORDER BY {$sIndexColumn} ASC";

    $sWhere = "";
    // Global search across the defined columns
    if (isset($req['sSearch']) && $req['sSearch'] != "") {
        $sWhere = "WHERE (";
        $search = mysqli_real_escape_string($gaSql['link'], $req['sSearch']);
        foreach ($aColumns as $col) {
            $sWhere .= $col . " LIKE '%" . $search . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    for ($i = 0; $i < count($aColumns); $i++) {
        if (isset($req['bSearchable_' . $i]) && $req['bSearchable_' . $i] == "true" && !empty($req['sSearch_' . $i])) {
            if ($sWhere == "") {
                $sWhere = "WHERE ";
            } else {
                $sWhere .= " AND ";
            }
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $req['sSearch_' . $i]) . "%' ";
        }
    }

    $sQuery = " SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) . " FROM $sTable $sWhere $sOrder $sLimit ";
    $rResult = mysqli_query($gaSql['link'], $sQuery);

    // prepare default output structure (DataTables-friendly)
    $output = ['data' => []];

    if ($rResult === false) {
        // return a minimal JSON response so DataTables doesn't break
        $err = mysqli_error($gaSql['link']);
        $output['error'] = 'DB error: ' . $err;
        echo json_encode($output);
        exit;
    }

    $sQuery = "SELECT FOUND_ROWS()";
    $rResultFilterTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
    // total records in base table
    $totalQ = mysqli_query($gaSql['link'], "SELECT COUNT(*) AS cnt FROM kriteria_pip");
    $totalRow = mysqli_fetch_assoc($totalQ);
    $iTotal = intval($totalRow['cnt'] ?? 0);
    $iFiltered = intval($aResultFilterTotal[0] ?? $iTotal);

    $no = 0;
    while ($aRow = mysqli_fetch_assoc($rResult)) {
        $no++;
        $row = array();

        // No
        $row[] = '<div class="text-center">' . $no . '</div>';

        // Nama Kriteria
        $row[] = htmlspecialchars($aRow['nama_kriteria'] ?? '');

        // Deskripsi (short preview)
        $desc = trim($aRow['deskripsi'] ?? '');
        $shortDesc = (mb_strlen($desc) > 120) ? htmlspecialchars(mb_substr($desc, 0, 120)) . '...' : htmlspecialchars($desc);
        $row[] = nl2br($shortDesc);

        // Poin
        $row[] = htmlspecialchars($aRow['poin'] ?? '');

        // Actions: edit + delete (use encrypted id for frontend)
        $enc = function_exists('convert') ? convert('encrypt', $aRow['id']) : $aRow['id'];
    $editBtn = '<a href="javascript:void(0)" class="table-action table-action-edit btn-tooltip btn-edit-kriteria mr-2" title="Edit" data-id="' . $enc . '"><i class="fas fa-edit"></i></a>';
    $delBtn = '<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete-kriteria" title="Hapus" data-id="' . $enc . '"><i class="fas fa-trash"></i></a>';
        $row[] = '<div class="text-left">' . $editBtn . $delBtn . '</div>';

        $output['data'][] = $row;
    }
    // include DataTables meta keys
    $final = [
        'data' => $output['data'],
        'recordsTotal' => $iTotal,
        'recordsFiltered' => $iFiltered,
        'draw' => isset($req['draw']) ? intval($req['draw']) : 0
    ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($final);
}
