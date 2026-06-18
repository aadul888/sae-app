<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    include('../../../library/function.php');

    $modul_id = 14;
    include __DIR__ . '/../check_role.php';
    if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
        echo json_encode(array(
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        ));
        exit;
    }

    // Kolom tabel lokasi
    $aColumns = ['lokasi_id', 'nama_lokasi', 'keterangan', 'latitude', 'longitude', 'radius', 'status'];
    $sIndexColumn = "lokasi_id";
    $sTable = "lokasi";

    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;
    $gaSql['link'] = new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    if ($gaSql['link']->connect_error) {
        die("Connection failed: " . $gaSql['link']->connect_error);
    }

    $gaSql['link']->set_charset("utf8mb4");

    // Pagination
    $sLimit = "";
    if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
        $sLimit = "LIMIT " . intval($_GET['iDisplayStart']) . ", " . intval($_GET['iDisplayLength']);
    }

    // Ordering
    $sOrder = "ORDER BY lokasi_id DESC";
    if (isset($_GET['iSortCol_0'])) {
        $sOrder = "ORDER BY ";
        for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
            if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . " " . ($_GET['sSortDir_' . $i] === 'asc' ? 'ASC' : 'DESC') . ", ";
            }
        }
        $sOrder = rtrim($sOrder, ", ");
        if ($sOrder == "ORDER BY") {
            $sOrder = "ORDER BY lokasi_id DESC";
        }
    }

    // Searching
    $sWhere = "";
    if (!empty($_GET['sSearch'])) {
        $sWhere = "WHERE ";
        foreach ($aColumns as $col) {
            $sWhere .= "$col LIKE '%" . $gaSql['link']->real_escape_string($_GET['sSearch']) . "%' OR ";
        }
        $sWhere = rtrim($sWhere, " OR ");
    }

    // Main query
    $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . implode(", ", $aColumns) . " FROM $sTable $sWhere $sOrder $sLimit";
    $rResult = $gaSql['link']->query($sQuery);

    // Filtered count
    $sQuery = "SELECT FOUND_ROWS()";
    $rResultFilterTotal = $gaSql['link']->query($sQuery);
    $iFilteredTotal = $rResultFilterTotal->fetch_array()[0];

    // Total count
    $sQuery = "SELECT COUNT($sIndexColumn) FROM $sTable";
    $rResultTotal = $gaSql['link']->query($sQuery);
    $iTotal = $rResultTotal->fetch_array()[0];

    // Output for DataTables
    $output = [
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => []
    ];

    // Nomor urut
    $no = $_GET['iDisplayStart'] + 1;
    while ($row = $rResult->fetch_assoc()) {
        $outputRow = [];

        $outputRow[] = '<div class="text-center">' . $no++ . '</div>';
        $outputRow[] = '<div class="text-center">' . htmlspecialchars($row['nama_lokasi']) . '</div>';
        $outputRow[] = '<div class="text-center">' . $row['latitude'] . ', ' . $row['longitude'] . '</div>';
        $outputRow[] = '<div class="text-center">' . $row['radius'] . ' m</div>';
        $outputRow[] = '<div class="text-center"><span class="badge badge-' . ($row['status'] == 'aktif' ? 'success' : 'secondary') . '">' . $row['status'] . '</span></div>';

        $outputRow[] = '
        <div class="text-center">
            <a href="javascript:void(0)" class="table-action table-action-edit btn-edit btn-tooltip" 
                title="Edit Lokasi"
                data-id="' . $row['lokasi_id'] . '"
                data-nama="' . htmlspecialchars($row['nama_lokasi']) . '"
                data-ket="' . htmlspecialchars($row['keterangan'] ?? '') . '"
                data-lat="' . $row['latitude'] . '"
                data-lng="' . $row['longitude'] . '"
                data-radius="' . $row['radius'] . '"
                data-status="' . $row['status'] . '">
                <i class="fas fa-edit"></i>
            </a>
            <a href="javascript:void(0)" class="table-action table-action-delete btn-delete btn-tooltip" 
                title="Hapus Lokasi"
                data-id="' . $row['lokasi_id'] . '"
                data-name="' . htmlspecialchars($row['nama_lokasi']) . '">
                <i class="fas fa-trash"></i>
            </a>
        </div>';

        $output['aaData'][] = $outputRow;
    }

    echo json_encode($output);
}
