<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

    // Get admin info for filtering
    $level_id = '';
    $tugas_csv = '';
    $admin_identifier = '';
    
    if (!empty($_COOKIE['ADMIN_KEY'])) {
        $admin_id = @epm_decode($_COOKIE['ADMIN_KEY']);
        $admin_id = anti_injection($admin_id);
        if (!empty($admin_id)) {
            $admin_identifier = $admin_id;
            $qadm = "SELECT level_id, tugas_tambahan FROM admin WHERE admin_id='" . intval($admin_id) . "' LIMIT 1";
            $radm = $connection->query($qadm);
            if ($radm && $radm->num_rows > 0) {
                $adm_row = $radm->fetch_assoc();
                $level_id = isset($adm_row['level_id']) ? $adm_row['level_id'] : '';
                $tugas_csv = isset($adm_row['tugas_tambahan']) ? $adm_row['tugas_tambahan'] : '';
            }
        }
    }
    
    // Fallback to cookie values if DB lookup did not yield values
    if ($level_id === '') {
        $level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
    }
    if ($tugas_csv === '' && !empty($_COOKIE['tugas_tambahan'])) {
        $tugas_csv = $_COOKIE['tugas_tambahan'];
    }

    // Build kelas filter for wali kelas
    $kelas_filter = '';
    $wali_kelas_ids = array();
    
    // Deteksi wali kelas: cek apakah level_id atau tugas_tambahan mengandung level Wali Kelas (9)
    $all_levels = array();
    if ($level_id !== '') $all_levels[] = intval($level_id);
    if (!empty($tugas_csv)) {
        $parts = preg_split('/\s*,\s*/', trim($tugas_csv));
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') $all_levels[] = intval($p);
        }
    }
    $all_levels = array_values(array_unique($all_levels));
    $is_wali_kelas = in_array(9, $all_levels);
    
    // Jika admin adalah wali kelas, filter data hanya kelas yang diwali
    if ($is_wali_kelas && $admin_identifier !== '') {
        // Cari kelas dari tabel kelas berdasarkan nama_wali_kelas
        $q_ck = $connection->query("SELECT kelas_id FROM kelas WHERE nama_wali_kelas='" . $connection->real_escape_string($admin_identifier) . "' OR wali_kelas_nama='" . $connection->real_escape_string($admin_identifier) . "'");
        if ($q_ck && $q_ck->num_rows > 0) {
            while ($r_ck = $q_ck->fetch_assoc()) {
                if (!empty($r_ck['kelas_id'])) $wali_kelas_ids[] = intval($r_ck['kelas_id']);
            }
        }
        
        // Jika belum ada, cari dari tabel user berdasarkan wali_kelas
        if (empty($wali_kelas_ids)) {
            $q_u2 = $connection->query("SELECT DISTINCT kelas FROM user WHERE wali_kelas = " . intval($admin_identifier) . " AND kelas IS NOT NULL AND kelas <> ''");
            if ($q_u2 && $q_u2->num_rows > 0) {
                while ($r2 = $q_u2->fetch_assoc()) {
                    if (!empty($r2['kelas'])) $wali_kelas_ids[] = intval($r2['kelas']);
                }
            }
        }
    }

    if (!empty($wali_kelas_ids)) {
        $in = implode(',', array_map('intval', array_values(array_unique($wali_kelas_ids))));
        if ($in !== '') $kelas_filter = " AND u.kelas IN (" . $in . ")";
    }

    // Columns from usulan_pip table to show in admin list
    // Select from usulan_pip joined with user and kelas to get avatar (foto) and nama_kelas
    $aColumns = [
        "u_p.usulan_pip_id AS usulan_pip_id",
        "u.avatar AS avatar",
        "u.nisn AS nisn",
        "u.nama_lengkap AS nama_lengkap",
        "k.nama_kelas AS nama_kelas",
        "u_p.status AS status",
        "u_p.poin AS poin",
        "u_p.tanggal_pengajuan AS tanggal_pengajuan"
    ];
    $sIndexColumn = "u_p.usulan_pip_id";
    $sTable = "usulan_pip u_p LEFT JOIN user u ON u_p.user_id = u.user_id LEFT JOIN kelas k ON u.kelas = k.kelas_id";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    // Accept either GET or POST (DataTables may use POST depending on configuration)
    $req = &$_REQUEST;
    
    // Handle modern DataTables pagination parameters
    $start = isset($req['start']) ? intval($req['start']) : (isset($req['iDisplayStart']) ? intval($req['iDisplayStart']) : 0);
    $length = isset($req['length']) ? intval($req['length']) : (isset($req['iDisplayLength']) ? intval($req['iDisplayLength']) : 25);
    
    if ($length != -1) {
        $sLimit = "LIMIT " . mysqli_real_escape_string($gaSql['link'], $start) . ", " .
            mysqli_real_escape_string($gaSql['link'], $length);
    }

    $sOrder = "ORDER BY {$sIndexColumn} ASC";
    if (isset($req['iSortCol_0'])) {
        $sOrder = "ORDER BY {$sIndexColumn} ASC";
        for ($i = 0; $i < intval($req['iSortingCols']); $i++) {
            if ($req['bSortable_' . intval($req['iSortCol_' . $i])] == "true") {
                $sOrder .= $aColumns[intval($req['iSortCol_' . $i])] . "
                    " . mysqli_real_escape_string($gaSql['link'], $req['sSortDir_' . $i]) . ", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY usulan_id ASC") {
            $sOrder = "ORDER BY usulan_id ASC";
        }
    }

    $sWhere = "";
    // Handle modern DataTables search parameters
    $searchValue = '';
    if (isset($req['search']['value']) && !empty($req['search']['value'])) {
        $searchValue = $req['search']['value'];
    } elseif (isset($req['sSearch']) && !empty($req['sSearch'])) {
        $searchValue = $req['sSearch'];
    }
    
    if ($searchValue != "") {
        $sWhere = "WHERE (";
        // Use original column names without aliases for WHERE clause
        $searchColumns = [
            "u_p.usulan_pip_id",
            "u.nisn", 
            "u.nama_lengkap",
            "k.nama_kelas",
            "u_p.status"
        ];
        for ($i = 0; $i < count($searchColumns); $i++) {
            $sWhere .= $searchColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $searchValue) . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    // Individual column search - use original column names
    $searchColumns = [
        "u_p.usulan_pip_id",
        "u.avatar",
        "u.nisn", 
        "u.nama_lengkap",
        "k.nama_kelas",
        "u_p.status",
        "u_p.poin",
        "u_p.tanggal_pengajuan"
    ];
    
    for ($i = 0; $i < count($searchColumns); $i++) {
        $columnSearchValue = "";
        if (isset($req['bSearchable_' . $i]) && $req['bSearchable_' . $i] == "true" && !empty($req['sSearch_' . $i])) {
            $columnSearchValue = $req['sSearch_' . $i];
        }
        
        if ($columnSearchValue != "") {
            if ($sWhere == "") {
                $sWhere = "WHERE ";
            } else {
                $sWhere .= " AND ";
            }
            $sWhere .= $searchColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $columnSearchValue) . "%' ";
        }
    }

    // Force datatable to only show rows with accepted status
    // Accept both 'Disetujui' and 'Diterima' in case the DB stores either term
    $acceptedCondition = "u_p.status IN ('Disetujui','Diterima')";
    if ($sWhere == "") {
        $sWhere = "WHERE " . $acceptedCondition;
    } else {
        $sWhere .= " AND " . $acceptedCondition;
    }
    
    // Apply kelas filter for wali kelas
    if ($kelas_filter !== '') {
        $sWhere .= $kelas_filter;
    }
    
    // Filter hanya siswa yang memiliki avatar valid
    $sWhere .= " AND u.avatar IS NOT NULL AND u.avatar != '' AND u.avatar != 'avatar.jpg'";

    $sQuery = " SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) . "
        FROM $sTable
        $sWhere
        $sOrder
        $sLimit ";
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
    
    // total records in base table with same filters applied
    $totalQuery = "SELECT COUNT(*) AS cnt FROM usulan_pip u_p LEFT JOIN user u ON u_p.user_id = u.user_id WHERE u_p.status='Disetujui'";
    if ($kelas_filter !== '') {
        $totalQuery .= $kelas_filter;
    }
    $totalQ = mysqli_query($gaSql['link'], $totalQuery);
    $totalRow = mysqli_fetch_assoc($totalQ);
    $iTotal = intval($totalRow['cnt'] ?? 0);
    $iFiltered = intval($aResultFilterTotal[0] ?? $iTotal);

    $no = 0;
    while ($aRow = mysqli_fetch_assoc($rResult)) {
        $no++;
        $row = array();

        // helper to pick first existing column from candidates
        $pick = function ($arr, $candidates, $default = '') {
            foreach ($candidates as $c) {
                if (isset($arr[$c]) && $arr[$c] !== null && $arr[$c] !== '') return $arr[$c];
            }
            return $default;
        };

        // No
        $row[] = '<div class="text-center">' . $no . '</div>';

        // Foto: follow user module convention
        // If avatar is NULL or equals 'avatar.jpg' show default avatar.jpg, otherwise use the stored filename
        $avatar = isset($aRow['avatar']) ? trim($aRow['avatar']) : '';
        $nisnVal = trim($aRow['nisn'] ?? '');
        $photoHtml = '<div class="text-center">-</div>';
        if ($avatar === '' || $avatar === null || $avatar === 'avatar.jpg') {
            $src = '../content/avatar/avatar.jpg';
            $photoHtml = '<div class="text-center"><img src="' . htmlspecialchars($src) . '" alt="foto" style="height:48px;width:48px;object-fit:cover;border-radius:4px" /></div>';
        } else {
            $src = '../content/avatar/' . $avatar;
            $photoHtml = '<div class="text-center"><img src="' . htmlspecialchars($src) . '" alt="foto" style="height:48px;width:48px;object-fit:cover;border-radius:4px" /></div>';
        }
        $row[] = $photoHtml;

        // NISN
        $nisn = htmlspecialchars($aRow['nisn'] ?? '');
        $row[] = $nisn;

        // Nama / Identitas
        $row[] = htmlspecialchars($aRow['nama_lengkap'] ?? '');

        // Kelas (nama_kelas from join)
        $row[] = htmlspecialchars($aRow['nama_kelas'] ?? '');

        // Status badge: normalize to three states -> Pending, Diterima, Ditolak
        $statusHtml = '';
        $statusVal = trim($aRow['status'] ?? '');
        // Treat both 'Disetujui' and 'Diproses' as accepted ('Diterima') for display
        if ($statusVal === 'Pending' || $statusVal === '') {
            $statusHtml = '<span class="badge badge-warning">Pending</span>';
        } elseif (in_array($statusVal, ['Disetujui', 'Diproses', 'Diterima'], true)) {
            $statusHtml = '<span class="badge badge-success">Diterima</span>';
        } elseif ($statusVal === 'Ditolak') {
            $statusHtml = '<span class="badge badge-danger">Ditolak</span>';
        } else {
            // fallback: show the raw value (safe-escaped)
            $statusHtml = '<span class="badge badge-secondary">' . htmlspecialchars($statusVal) . '</span>';
        }

        // If rejected, ensure keterangan already contains rejection reason (some schemas store reason in keterangan)
        // Avoid referencing `alasan_penolakan` column if it doesn't exist in this schema.
        if ($statusVal === 'Ditolak') {
            // If keterangan is empty, leave it as '-' placeholder; otherwise it will already show the reason.
            if ($keterangan === '') {
                $keterangan = '<em>Tidak ada keterangan</em>';
            }
        }

    // Status cell (badge only)
    $row[] = $statusHtml;

        // Poin (centered)
        $poinVal = isset($aRow['poin']) ? intval($aRow['poin']) : 0;
        $row[] = '<div>' . $poinVal . '</div>';

        // Actions: view (open modal) + reset + delete
        $encId = convert('encrypt', $aRow['usulan_pip_id']);
        $viewBtn = '<a href="javascript:void(0)" class="table-action table-action-view btn-tooltip btn-view mr-2" data-toggle="tooltip" data-placement="top" title="Lihat" data-id="' . $encId . '"><i class="fas fa-search"></i></a>';
        $resetBtn = '<a href="javascript:void(0)" class="table-action table-action-reset btn-tooltip btn-stts mr-2" data-toggle="tooltip" data-placement="top" title="Reset ke Pending" data-id="' . $encId . '" data-status="-"><i class="fas fa-undo"></i></a>';
        $row[] = '<div class="text-left">' . $viewBtn . $resetBtn . $delBtn . '</div>';

        $output['data'][] = $row;
    }
    // include DataTables meta keys
    $draw = isset($req['draw']) ? intval($req['draw']) : (isset($req['sEcho']) ? intval($req['sEcho']) : 1);
    
    $final = [
        'data' => $output['data'],
        'recordsTotal' => $iTotal,
        'recordsFiltered' => $iFiltered,
        'draw' => $draw
    ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($final);
}
