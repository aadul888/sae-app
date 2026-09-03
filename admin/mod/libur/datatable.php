<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once('../../../library/function.php');

    $modul_id = 13;
    include __DIR__ . '/../check_role.php';
    if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
        echo json_encode(array(
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        ));
        exit;
    }

    // Definisikan kolom-kolom untuk tabel hari_libur
    $aColumns = ['id', 'tanggal_mulai', 'tanggal_selesai', 'keterangan'];
    $sIndexColumn = "id";
    $sTable = "hari_libur";

    // Koneksi database
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;
    $gaSql['link'] = new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    // Cek koneksi
    if ($gaSql['link']->connect_error) {
        die("Connection failed: " . $gaSql['link']->connect_error);
    }

    // Batasi jumlah data yang ditampilkan (pagination)
    $sLimit = "";
    if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
        $sLimit = "LIMIT " . intval($_GET['iDisplayStart']) . ", " . intval($_GET['iDisplayLength']);
    }

    // Pengurutan data berdasarkan tanggal_mulai
    $sOrder = "ORDER BY tanggal_mulai DESC";  // Ganti dengan 'tanggal_mulai' sebagai kolom pengurutan
    if (isset($_GET['iSortCol_0'])) {
        $sOrder = "ORDER BY ";
        for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
            if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . " " . ($_GET['sSortDir_' . $i] === 'asc' ? 'ASC' : 'DESC') . ", ";
            }
        }
        $sOrder = rtrim($sOrder, ", ");
        if ($sOrder == "ORDER BY") {
            $sOrder = "ORDER BY tanggal_mulai DESC";  // Ganti dengan 'tanggal_mulai' jika tidak ada pengurutan spesifik
        }
    }

    // Pencarian data
    $sWhere = "";
    if (!empty($_GET['sSearch'])) {
        $sWhere = "WHERE ";
        foreach ($aColumns as $col) {
            $sWhere .= "$col LIKE '%" . $gaSql['link']->real_escape_string($_GET['sSearch']) . "%' OR ";
        }
        $sWhere = rtrim($sWhere, " OR ");
    }

    // Query utama
    $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . implode(", ", $aColumns) . " FROM $sTable $sWhere $sOrder $sLimit";
    $rResult = $gaSql['link']->query($sQuery);

    // Total data setelah filter
    $sQuery = "SELECT FOUND_ROWS()";
    $rResultFilterTotal = $gaSql['link']->query($sQuery);
    $iFilteredTotal = $rResultFilterTotal->fetch_array()[0];

    // Total data di tabel
    $sQuery = "SELECT COUNT($sIndexColumn) FROM $sTable";
    $rResultTotal = $gaSql['link']->query($sQuery);
    $iTotal = $rResultTotal->fetch_array()[0];

    // Siapkan output JSON untuk DataTables
    $output = [
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => []
    ];

    // Nomor urut data
    $no = $_GET['iDisplayStart'] + 1;
    while ($aRow = $rResult->fetch_assoc()) {
        $row = [];

        // Format tanggal mulai dan selesai
        $tanggalMulaiFormatted = date('d-m-Y', strtotime($aRow['tanggal_mulai']));
        $tanggalSelesaiFormatted = date('d-m-Y', strtotime($aRow['tanggal_selesai']));

        // Menampilkan data dalam format tabel
        $row[] = '<div class="text-center">' . $no++ . '</div>';
        $row[] = '<div class="text-center">' . $tanggalMulaiFormatted . '</div>';
        $row[] = '<div class="text-center">' . $tanggalSelesaiFormatted . '</div>';
        $row[] = '<div class="text-center">' . htmlspecialchars($aRow['keterangan']) . '</div>';

        // Tombol Aksi (Edit & Hapus)
        // Tombol Aksi (Edit & Hapus)
        $row[] = '<div class="text-center">
                        <a href="javascript:void(0)" class="table-action table-action-primary btn-update btn-tooltip" data-toggle="tooltip" data-placement="right" 
                            data-id="' . $aRow['id'] . '" data-tanggal_mulai="' . $aRow['tanggal_mulai'] . '" data-tanggal_selesai="' . $aRow['tanggal_selesai'] . '" data-keterangan="' . htmlspecialchars($aRow['keterangan']) . '">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-placement="right"
                            data-id="' . $aRow['id'] . '">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>';


        $output['aaData'][] = $row;
    }

    // Mengembalikan data dalam format JSON
    echo json_encode($output);
}
