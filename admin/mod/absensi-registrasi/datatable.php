<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    include('../../../library/function.php');

    $modul_id = 11;
    include __DIR__ . '/../check_role.php';
    if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
        echo json_encode(array(
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        ));
        exit;
    }

    // Kolom yang akan ditampilkan di DataTable
    $aColumns = ['user.user_id', 'user.nisn', 'user.nama_lengkap', 'kelas.nama_kelas', 'user.rfid'];
    $sIndexColumn = "user.user_id";
    $sTable = "user";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] = new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
        $sLimit = "LIMIT " . mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayStart']) . ", " .
            mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayLength']);
    }

    $sOrder = "ORDER BY user.nama_lengkap ASC";
    if (isset($_GET['iSortCol_0'])) {
        $sOrder = "ORDER BY user.nama_lengkap ASC";
    }

    // Hanya menampilkan siswa yang telah memiliki RFID
    $sWhere = "WHERE user.rfid IS NOT NULL AND user.rfid != ''";
    if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
        $sWhere .= " AND (";
        for ($i = 0; $i < count($aColumns); $i++) {
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $_GET['sSearch']) . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    // Tambahkan JOIN ke tabel kelas untuk mendapatkan nama_kelas
    $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . implode(", ", $aColumns) . "
        FROM $sTable
        LEFT JOIN kelas ON user.kelas = kelas.kelas_id
        $sWhere
        $sOrder
        $sLimit ";
    $rResult = mysqli_query($gaSql['link'], $sQuery);

    $sQuery = "SELECT FOUND_ROWS()";
    $rResultFilterTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];

    $sQuery = "SELECT COUNT(" . $sIndexColumn . ") FROM $sTable";
    $rResultTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultTotal = mysqli_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];

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

        $row[] = '<div class="text-center">' . $no . '</div>';
        $row[] = '<div class="text-center">' . $aRow['nisn'] . '</div>';
        $row[] = '<div class="text-center">' . strip_tags($aRow['nama_lengkap']) . '</div>';
        $row[] = '<div class="text-center">' . $aRow['nama_kelas'] . '</div>'; // Ubah dari kelas_id ke nama_kelas
        $row[] = '<div class="text-center"><b>' . $aRow['rfid'] . '</b></div>';
        $row[] = '<div class="text-center">
                    <a href="javascript:void(0)" class="table-action table-action-primary btn-update btn-tooltip" data-toggle="tooltip" data-placement="right"
                        data-userid="' . $aRow['user_id'] . '" data-nisn="' . $aRow['nisn'] . '" data-nama="' . strip_tags($aRow['nama_lengkap']) . '" data-rfid="' . $aRow['rfid'] . '">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-placement="right"
                        data-userid="' . $aRow['user_id'] . '" data-nisn="' . $aRow['nisn'] . '" data-nama="' . strip_tags($aRow['nama_lengkap']) . '" data-rfid="' . $aRow['rfid'] . '">
                        <i class="fas fa-trash"></i>
                    </a>
                  </div>';

        $output['aaData'][] = $row;
    }

    echo json_encode($output);
}
