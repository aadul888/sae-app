<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    // Start output buffering to prevent any accidental output (templates/notices) from breaking JSON
    if (!ob_get_level()) ob_start();
    // Hide notices/warnings from being printed to the response (they would break JSON)
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    require_once '../../../library/config.php';
    include('../../../library/function.php');
    $aColumns = ['tingkat_pendidikan_id', 'nama_kelas', 'sync_jurusan_str', 'wali_kelas_nama']; // Kualitas tidak perlu di search
    $sIndexColumn = "kelas_id";
    $sTable = "kelas";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;
    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);
    $sLimit = "";
    if (isset($_REQUEST['iDisplayStart']) && $_REQUEST['iDisplayLength'] != '-1') {
        $sLimit = "LIMIT " . mysqli_real_escape_string($gaSql['link'], $_REQUEST['iDisplayStart']) . ", " .
            mysqli_real_escape_string($gaSql['link'], $_REQUEST['iDisplayLength']);
    }
    $sOrder = "ORDER BY tingkat_pendidikan_id ASC, nama_kelas ASC";
    if (isset($_REQUEST['iSortCol_0'])) {
        // Bangun daftar klausa ORDER BY secara aman
        $orderClauses = array();
        for ($i = 0; $i < intval($_REQUEST['iSortingCols']); $i++) {
            $colIndex = intval($_REQUEST['iSortCol_' . $i]);
            if (isset($_REQUEST['bSortable_' . $colIndex]) && $_REQUEST['bSortable_' . $colIndex] == "true") {
                // Ambil nama kolom dari definisi kolom yang diizinkan
                $colName = $aColumns[$colIndex];
                $dir = mysqli_real_escape_string($gaSql['link'], $_REQUEST['sSortDir_' . $i]);
                $dir = (strtoupper($dir) === 'DESC') ? 'DESC' : 'ASC';
                $orderClauses[] = $colName . ' ' . $dir;
            }
        }
        if (count($orderClauses) > 0) {
            $sOrder = 'ORDER BY ' . implode(', ', $orderClauses);
        } else {
            // fallback ke tingkat dan nama_kelas jika tidak ada klausa yang valid
            $sOrder = 'ORDER BY tingkat_pendidikan_id ASC, nama_kelas ASC';
        }
    }
    $sWhere = "";
    if (isset($_REQUEST['sSearch']) && $_REQUEST['sSearch'] != "") {
        $sWhere = "WHERE (";
        for ($i = 0; $i < count($aColumns); $i++) {
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $_REQUEST['sSearch']) . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }
    for ($i = 0; $i < count($aColumns); $i++) {
        if (isset($_REQUEST['bSearchable_' . $i]) && $_REQUEST['bSearchable_' . $i] == "true" && $_REQUEST['sSearch_' . $i] != '') {
            if ($sWhere == "") {
                $sWhere = "WHERE ";
            } else {
                $sWhere .= " AND ";
            }
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $_REQUEST['sSearch_' . $i]) . "%' ";
        }
    }
    // Query utama: tampilkan kelas read-only dari hasil sinkronisasi Dapodik
    $sQuery = "SELECT `k`.`kelas_id`, `k`.`nama_kelas`, `k`.`tingkat_pendidikan_id`, `k`.`tingkat_pendidikan_str`, `k`.`sync_jurusan_str`, `k`.`wali_kelas_ptk_id` AS `wali_kelas_id`, `k`.`wali_kelas_nama` AS `wali_kelas`, COALESCE(`ars`.`jumlah_siswa`, 0) AS `jumlah_siswa`,
            SUM(
                (
                    (CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 1 ELSE 0 END) * 0.5
                    +
                    (CASE WHEN EXISTS (SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND b.validasi_berkas = 'valid') THEN 1 ELSE 0 END) * 0.5
                )
            ) AS jumlah_kualitas
            FROM kelas k
            LEFT JOIN (
                SELECT rombongan_belajar_id, COUNT(DISTINCT anggota_rombel_id) AS jumlah_siswa
                FROM sync_anggota_rombel
                GROUP BY rombongan_belajar_id
            ) ars ON ars.rombongan_belajar_id = k.rombongan_belajar_id
            /* join only active users so jumlah_siswa/jumlah_kualitas reflect active students */
            LEFT JOIN user u ON k.kelas_id = u.kelas AND (u.status = '1' OR LOWER(u.status) = 'aktif')
            /* Note: do NOT join admin table for wali; take wali values from kelas table only */
            GROUP BY k.kelas_id, ars.jumlah_siswa $sOrder $sLimit";
    $rResult = mysqli_query($gaSql['link'], $sQuery);
    if ($rResult === false) {
        // clear any previous output and return JSON error with the SQL for debugging
        if (ob_get_level()) {
            ob_clean();
        }
        $output = array(
            "error" => true,
            "message" => "Query Error: " . mysqli_error($gaSql['link']),
            "query" => $sQuery
        );
        // also log the query server-side
        error_log('datatable.php query error: ' . mysqli_error($gaSql['link']) . '\nSQL: ' . $sQuery);
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode($output);
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit;
    }
    // Karena kita tidak menggunakan server-side processing lengkap, gunakan jumlah baris hasil sebagai filtered total
    $iFilteredTotal = mysqli_num_rows($rResult);
    // Query untuk total semua kelas
    $sQuery = "SELECT COUNT(" . $sIndexColumn . ") FROM   $sTable";
    $rResultTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultTotal = mysqli_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];
    // Wali kelas diambil dari tabel kelas per-row (tidak mengambil list admin di sini)
    $wali_options = array();
    $output = array(
        // legacy/server-side keys for compatibility
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        // modern DataTables keys
        "recordsTotal" => $iTotal,
        "recordsFiltered" => $iFilteredTotal,
        "draw" => isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 1,
        "aaData" => array(),
        "data" => array()
    );
    $no = 0;
    while ($aRow = mysqli_fetch_array($rResult)) {
        $no++;
        $row = array();
        $row[] = '<div class="text-center">' . $no . '</div>'; // NO
        $tingkat_display = !empty($aRow['tingkat_pendidikan_str']) ? $aRow['tingkat_pendidikan_str'] : $aRow['tingkat_pendidikan_id'];
        $row[] = '<div class="text-center">' . htmlspecialchars((string)$tingkat_display) . '</div>'; // TINGKAT
        $row[] = '<div class="text-center">' . htmlspecialchars((string)$aRow['nama_kelas']) . '</div>'; // KELAS
        $jurusan_display = !empty($aRow['sync_jurusan_str']) ? $aRow['sync_jurusan_str'] : '-';
        $row[] = '<div class="text-center">' . htmlspecialchars($jurusan_display) . '</div>';
        // Tampilkan wali kelas sebagai teks (tidak dapat dipilih)
        $row[] = '<div>' . htmlspecialchars($aRow['wali_kelas']) . '</div>';
        $row[] = strip_tags($aRow['jumlah_siswa']); // JUMLAH
        // Kolom Kualitas
        $persen = 0;
        if ($aRow['jumlah_siswa'] > 0) {
            $persen = round(($aRow['jumlah_kualitas'] / $aRow['jumlah_siswa']) * 100, 1);
        }
        $row[] = '<div class="text-center"><span class="badge badge-info">' . $persen . '%</span></div>';
        $row[] = '<div class="text-center"><span class="badge badge-light">Otomatis dari Dapodik</span></div>';
        $output['aaData'][] = $row;
    }
    // clear any accidental output before sending JSON
    if (ob_get_level()) {
        ob_clean();
    }
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    // Mirror aaData into data for newer DataTables versions
    $output['data'] = $output['aaData'];
    echo json_encode($output);
    if (ob_get_level()) {
        ob_end_flush();
    }
}
