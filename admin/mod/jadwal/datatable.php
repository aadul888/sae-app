<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once('../../../library/function.php');

    $modul_id = 12;
    include __DIR__ . '/../check_role.php';
    if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
        echo json_encode(array(
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        ));
        exit;
    }

    // Definisikan kolom-kolom untuk tabel jadwal
    $aColumns = [
        'id',
        'hari',
        'waktu_mulai',
        'waktu_selesai',
        'status'
    ];
    $sIndexColumn = "id";
    $sTable = "jadwal"; // Tabel yang digunakan

    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] = new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    // Batasi jumlah data yang ditampilkan
    $sLimit = "";
    if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
        $sLimit = "LIMIT " . mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayStart']) . ", " .
            mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayLength']);
    }

    // Pengurutan berdasarkan kolom
    $sOrder = "ORDER BY id DESC";
    if (isset($_GET['iSortCol_0'])) {
        $sOrder = "ORDER BY ";
        for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
            if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . " " .
                    mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_' . $i]) . ", ";
            }
        }
        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY") {
            $sOrder = "ORDER BY id DESC";
        }
    }

    // Pencarian data berdasarkan kolom
    $sWhere = "";
    if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
        $sWhere = "WHERE (";
        for ($i = 0; $i < count($aColumns); $i++) {
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $_GET['sSearch']) . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    // Query untuk mengambil data dari tabel jadwal
    $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) . "
               FROM $sTable
               $sWhere
               $sOrder
               $sLimit";

    $rResult = mysqli_query($gaSql['link'], $sQuery);

    // Query untuk mendapatkan jumlah hasil yang difilter
    $sQuery = "SELECT FOUND_ROWS()";
    $rResultFilterTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];

    // Query untuk mendapatkan jumlah total baris di tabel jadwal
    $sQuery = "SELECT COUNT(" . $sIndexColumn . ") FROM jadwal";
    $rResultTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultTotal = mysqli_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];

    // Siapkan output untuk DataTables
    $output = array(
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );

    $no = 0;
    while ($aRow = mysqli_fetch_array($rResult)) {
        $no++;
        extract($aRow);
        $row = array();

        // Format waktu
    $waktuMulaiFormatted = date('H.i', strtotime($waktu_mulai));
    $waktuSelesaiFormatted = date('H.i', strtotime($waktu_selesai));

        // Isi row dengan data untuk setiap kolom
        $row[] = '<div class="text-center">' . $no . '</div>';
        $row[] = '<div class="text-center">' . $hari . '</div>';
        $row[] = '<div class="text-center">' . $waktuMulaiFormatted . '</div>';
        $row[] = '<div class="text-center">' . $waktuSelesaiFormatted . '</div>';

        // Tombol Aksi (Hanya Edit)
        $row[] = '<div class="text-center">
            <a href="javascript:void(0)" class="table-action table-action-primary btn-update btn-tooltip" data-toggle="tooltip" data-placement="right" title="Edit" 
                data-id="' . $id . '" data-hari="' . $hari . '" data-waktu-mulai="' . $waktuMulaiFormatted . '" data-waktu-selesai="' . $waktuSelesaiFormatted . '">
                <i class="fas fa-edit"></i>
            </a>
        </div>';


        // Tombol status custom-toggle
        if ($status == 'Y') {
            $statusBtn = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-status status' . $id . '" data-id="' . $id . '" data-status="N" checked>
                <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
            </label>';
        } else {
            $statusBtn = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-status status' . $id . '" data-id="' . $id . '" data-status="Y">
                <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
            </label>';
        }
        $row[] = '<div class="text-center">' . $statusBtn . '</div>';


        // Tambahkan row ke output
        $output['aaData'][] = $row;
    }

    // Outputkan data dalam format JSON
    echo json_encode($output);
}
