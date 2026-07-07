<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    // PENTING: Detect columns SEBELUM menentukan $aColumns
    $has_ranking_column = false;
    $has_dapodik_column = false;
    
    $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . mysqli_real_escape_string($gaSql['link'], $gaSql['db']) . "' AND TABLE_NAME='usulan_pip' AND COLUMN_NAME IN ('ranking_position','dapodik_status')";
    $checkRes = mysqli_query($gaSql['link'], $checkSql);
    if ($checkRes) {
        while ($row = mysqli_fetch_assoc($checkRes)) {
            if ($row['COLUMN_NAME'] === 'ranking_position') {
                $has_ranking_column = true;
            }
            if ($row['COLUMN_NAME'] === 'dapodik_status') {
                $has_dapodik_column = true;
            }
        }
    }

    // Columns from usulan_pip table to show in admin ranking list
    // select poin and necessary fields; order of columns should match the table header
    $aColumns = [
        "u_p.usulan_pip_id AS usulan_pip_id",
        "u.avatar AS avatar",
        "u.nisn AS nisn",
        "u.nama_lengkap AS nama_lengkap",
        "k.nama_kelas AS nama_kelas",
        "u_p.status AS status",
        "u_p.poin AS poin",
        "u_p.ranking_position AS ranking_position"
    ];
    
    // Add dapodik_status column if it exists
    if ($has_dapodik_column) {
        $aColumns[] = "u_p.dapodik_status AS dapodik_status";
    } else {
        $aColumns[] = "'N' AS dapodik_status";
    }
    
    $sIndexColumn = "u_p.usulan_pip_id";
    $sTable = "usulan_pip u_p LEFT JOIN user u ON u_p.user_id = u.user_id LEFT JOIN kelas k ON u.kelas = k.kelas_id";

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

    // Default ordering: respect manual `ranking_position` when set, otherwise order by poin desc
    if ($has_ranking_column) {
        $sOrder = "ORDER BY (CASE WHEN u_p.ranking_position IS NULL THEN 1000000 ELSE u_p.ranking_position END) ASC, u_p.poin DESC, u_p.tanggal_pengajuan ASC";
    } else {
        // fallback when column not present
        $sOrder = "ORDER BY u_p.poin DESC, u_p.tanggal_pengajuan ASC";
    }
    if (isset($req['iSortCol_0'])) {
        $orderParts = [];
        $sortingCols = intval($req['iSortingCols']);
        for ($i = 0; $i < $sortingCols; $i++) {
            $colIdx = intval($req['iSortCol_' . $i]);
            $dir = (isset($req['sSortDir_' . $i]) && strtoupper($req['sSortDir_' . $i]) === 'DESC') ? 'DESC' : 'ASC';
            if (isset($aColumns[$colIdx])) {
                $colExpr = $aColumns[$colIdx];
                $parts = preg_split('/\s+AS\s+/i', $colExpr);
                $orderParts[] = $parts[0] . ' ' . $dir;
            }
        }
        if (count($orderParts)) {
            $sOrder = 'ORDER BY ' . implode(', ', $orderParts);
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
            "u_p.status",
            "u_p.dapodik_status"
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
        "u_p.ranking_position",
        "u_p.dapodik_status"
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

    // Filter kelas
    if ((isset($_POST['kelas']) && $_POST['kelas'] != '') || (isset($_GET['kelas']) && $_GET['kelas'] != '')) {
        $kelas_filter = isset($_POST['kelas']) ? $_POST['kelas'] : $_GET['kelas'];
        $kelas_filter = mysqli_real_escape_string($gaSql['link'], $kelas_filter);
        $kelasCondition = "u.kelas = '" . $kelas_filter . "'";
        if ($sWhere == "") {
            $sWhere = "WHERE " . $kelasCondition;
        } else {
            $sWhere .= " AND " . $kelasCondition;
        }
    }

    // Filter dapodik (hanya jika kolom ada)
    if ((isset($_POST['dapodik']) && $_POST['dapodik'] != '') || (isset($_GET['dapodik']) && $_GET['dapodik'] != '')) {
        $dapodik_filter = isset($_POST['dapodik']) ? $_POST['dapodik'] : $_GET['dapodik'];
        $dapodik_filter = mysqli_real_escape_string($gaSql['link'], $dapodik_filter);
        
        if ($has_dapodik_column) {
            $dapodikCondition = "COALESCE(u_p.dapodik_status, 'N') = '" . $dapodik_filter . "'";
        } else {
            // Jika kolom belum ada, semua dianggap 'N'
            $dapodikCondition = "'" . $dapodik_filter . "' = 'N'";
        }
        
        if ($sWhere == "") {
            $sWhere = "WHERE " . $dapodikCondition;
        } else {
            $sWhere .= " AND " . $dapodikCondition;
        }
    }

    // Filter status
    $status_filter = isset($_POST['status']) ? $_POST['status'] : (isset($_GET['status']) ? $_GET['status'] : '');
    if ($status_filter != '' && $status_filter != 'all') {
        $status_filter = mysqli_real_escape_string($gaSql['link'], $status_filter);
        $statusCondition = "u_p.status = '" . $status_filter . "'";
        if ($sWhere == "") {
            $sWhere = "WHERE " . $statusCondition;
        } else {
            $sWhere .= " AND " . $statusCondition;
        }
    } else if ($status_filter != 'all') {
        // Default: hanya tampilkan yang disetujui jika tidak ada filter atau filter kosong
        $acceptedCondition = "u_p.status IN ('Disetujui','Diterima')";
        if ($sWhere == "") {
            $sWhere = "WHERE " . $acceptedCondition;
        } else {
            $sWhere .= " AND " . $acceptedCondition;
        }
    }

    // Cek akses berdasarkan level dan deteksi wali kelas
    $current_level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
    $current_tugas_csv = '';
    if (!empty($_COOKIE['ADMIN_KEY'])) {
        $_tmp_aid = @epm_decode($_COOKIE['ADMIN_KEY']);
        $_tmp_aid = anti_injection($_tmp_aid);
        if (!empty($_tmp_aid)) {
            $q_tmp = $gaSql['link']->query("SELECT level_id, tugas_tambahan FROM admin WHERE admin_id='" . intval($_tmp_aid) . "' LIMIT 1");
            if ($q_tmp && $q_tmp->num_rows > 0) {
                $r_tmp = $q_tmp->fetch_assoc();
                $current_level_id = isset($r_tmp['level_id']) ? $r_tmp['level_id'] : $current_level_id;
                $current_tugas_csv = isset($r_tmp['tugas_tambahan']) ? $r_tmp['tugas_tambahan'] : '';
            }
        }
    }
    // Build semua level admin (utama + tugas)
    $all_admin_levels = array();
    if ($current_level_id !== '') $all_admin_levels[] = intval($current_level_id);
    if (!empty($current_tugas_csv)) {
        $parts = preg_split('/\s*,\s*/', trim($current_tugas_csv));
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') $all_admin_levels[] = intval($p);
        }
    }
    $all_admin_levels = array_values(array_unique($all_admin_levels));
    
    $is_superadmin = in_array(1, $all_admin_levels);
    // Deteksi wali kelas berdasarkan level 9 (Wali Kelas) di level utama atau tugas tambahan
    $is_wali_kelas = in_array(9, $all_admin_levels);
    
    // Cek permission modifikasi dari role table untuk modul ranking (31)
    $can_modify = false;
    if (count($all_admin_levels) > 0) {
        $in_levels = implode(',', array_map('intval', $all_admin_levels));
        $q_perm = $gaSql['link']->query("SELECT modifikasi FROM role WHERE modul_id=31 AND level_id IN ($in_levels) AND modifikasi='Y' LIMIT 1");
        if ($q_perm && $q_perm->num_rows > 0) $can_modify = true;
    }
    
    // Load current user info untuk deteksi wali kelas
    if (file_exists(__DIR__ . '/../../login/user.php')) {
        require_once __DIR__ . '/../../login/user.php';
    }
    
    // Auto-detect kelas berdasarkan wali kelas (menggunakan logika dari modul user)
    $kelas_id = '';
    if (isset($current_user) && (isset($current_user['ptk_id']) || isset($current_user['admin_id']))) {
        $ptk_id = isset($current_user['ptk_id']) ? $current_user['ptk_id'] : '';
        $admin_id = isset($current_user['admin_id']) ? $current_user['admin_id'] : '';

        if (!empty($ptk_id)) {
            $q_wali = $gaSql['link']->query("SELECT kelas_id FROM kelas WHERE wali_kelas_ptk_id='" . mysqli_real_escape_string($gaSql['link'], $ptk_id) . "' LIMIT 1");
            if ($q_wali && $r_w = $q_wali->fetch_assoc()) {
                $kelas_id = $r_w['kelas_id'];
            }
        }

        // fallback: try matching by admin_id in case kelas stores admin id as wali
        if ($kelas_id === '' && !empty($admin_id)) {
            $q_wali2 = $gaSql['link']->query("SELECT kelas_id FROM kelas WHERE wali_kelas_admin_id='" . mysqli_real_escape_string($gaSql['link'], $admin_id) . "' LIMIT 1");
            if ($q_wali2 && $r2 = $q_wali2->fetch_assoc()) {
                $kelas_id = $r2['kelas_id'];
            }
        }
    }

    if ($is_wali_kelas && !$is_superadmin) {
        if ($kelas_id != '') {
            // Wali kelas terdeteksi - filter hanya siswa dari kelas yang diwali
            $sWhere .= " AND u.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
        } else {
            // Wali kelas tapi tidak terdeteksi kelasnya, tampilkan data kosong
            $sWhere .= " AND 1=0";
        }
    }
    // Jika bukan wali kelas (atau superadmin), tampilkan semua data

    // Build SELECT column list; if ranking column missing return NULL placeholder to keep column count
    $selectCols = str_replace(" , ", " ", implode(", ", $aColumns));
    if (!$has_ranking_column) {
        $selectCols = str_replace(", u_p.ranking_position AS ranking_position", ", NULL AS ranking_position", $selectCols);
    }
    if (!$has_dapodik_column) {
        $selectCols = str_replace(", u_p.dapodik_status AS dapodik_status", ", 'N' AS dapodik_status", $selectCols);
    }
    $sQuery = " SELECT SQL_CALC_FOUND_ROWS " . $selectCols . "\n        FROM $sTable\n        $sWhere\n        $sOrder\n        $sLimit ";
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
    $totalQ = mysqli_query($gaSql['link'], "SELECT COUNT(*) AS cnt FROM usulan_pip WHERE status='Disetujui'");
    $totalRow = mysqli_fetch_assoc($totalQ);
    $iTotal = intval($totalRow['cnt'] ?? 0);
    $iFiltered = intval($aResultFilterTotal[0] ?? $iTotal);

    $no = 0;
    // Use the start variable from pagination parameters for proper ranking calculation
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

        // Rank (calculate based on DataTables offset)
        $row[] = '<div class="text-center">' . ($start + $no) . '</div>';

        // Set Posisi (hanya untuk wali kelas)
        $poinVal = isset($aRow['poin']) ? intval($aRow['poin']) : 0;
        $rankingPos = isset($aRow['ranking_position']) && $aRow['ranking_position'] !== null ? intval($aRow['ranking_position']) : null;
        $controls = '';
        
        // Wali kelas atau admin dengan modifikasi=Y bisa mengatur ranking
        $can_manage_ranking = ($is_wali_kelas || $can_modify);
        
        if ($can_manage_ranking) {
            $controls .= '<div class="btn-group btn-group-sm" role="group">';
            $controls .= '<button class="btn btn-outline-secondary btn-move-up" title="Pindah ke atas" data-id="' . htmlspecialchars($aRow['usulan_pip_id']) . '"><i class="fas fa-arrow-up"></i></button>';
            $controls .= '<button class="btn btn-outline-secondary btn-move-down" title="Pindah ke bawah" data-id="' . htmlspecialchars($aRow['usulan_pip_id']) . '"><i class="fas fa-arrow-down"></i></button>';
            $controls .= '<button class="btn btn-outline-primary btn-set-pos" data-id="' . htmlspecialchars($aRow['usulan_pip_id']) . '">Set Posisi</button>';
            $controls .= '</div>';
        }
        
        $posLabel = $rankingPos !== null ? '<small class="text-muted">#' . $rankingPos . '</small> ' : '';
        $positionDisplay = '<div class="text-center">';
        if ($can_manage_ranking) {
            $positionDisplay .= '<div>' . $posLabel . '</div><div>' . $controls . '</div>';
        } else {
            $positionDisplay .= '<div>' . ($posLabel ?: '-') . '</div>';
        }
        $positionDisplay .= '</div>';
        $row[] = $positionDisplay;

        // Foto: follow user module convention
        // If avatar is NULL or equals 'avatar.jpg' show default avatar.jpg, otherwise use the stored filename
        $avatar = isset($aRow['avatar']) ? trim($aRow['avatar']) : '';
        $nisnVal = trim($aRow['nisn'] ?? '');
        $photoHtml = '<div>-</div>';
        if ($avatar === '' || $avatar === null || $avatar === 'avatar.jpg') {
            $src = '../content/avatar/avatar.jpg';
            $photoHtml = '<div><img src="' . htmlspecialchars($src) . '" alt="foto" style="height:48px;width:48px;object-fit:cover;border-radius:4px" /></div>';
        } else {
            $src = '../content/avatar/' . $avatar;
            $photoHtml = '<div><img src="' . htmlspecialchars($src) . '" alt="foto" style="height:48px;width:48px;object-fit:cover;border-radius:4px" /></div>';
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

        // Poin (hanya nilai poin, kontrol sudah dipindah ke kolom Set Posisi)
        $row[] = '<div>' . $poinVal . '</div>';

        // Kolom Dapodik - hanya superadmin dan admin yang bisa mengubah status
        $dapodikStatus = isset($aRow['dapodik_status']) ? $aRow['dapodik_status'] : 'N';
        $isChecked = ($dapodikStatus === 'Y') ? 'checked' : '';
        
        $dapodikHtml = '<div>';
        if ($can_modify || $is_superadmin) {
            // Checkbox aktif untuk admin dengan permission modifikasi
            $dapodikHtml .= '<div class="custom-control custom-checkbox">';
            $dapodikHtml .= '<input type="checkbox" class="custom-control-input dapodik-checkbox" id="dapodik_' . $aRow['usulan_pip_id'] . '" data-id="' . $aRow['usulan_pip_id'] . '" ' . $isChecked . '>';
            $dapodikHtml .= '<label class="custom-control-label" for="dapodik_' . $aRow['usulan_pip_id'] . '" title="Konfirmasi input ke Dapodik"></label>';
            $dapodikHtml .= '</div>';
            error_log('Generated checkbox HTML: ' . $dapodikHtml);
        } else {
            // Hanya tampilan read-only untuk level lain (wali kelas, dll)
            if ($dapodikStatus === 'Y') {
                $dapodikHtml .= '<span class="badge badge-success"><i class="fas fa-check"></i> Sudah</span>';
            } else {
                $dapodikHtml .= '<span class="badge badge-secondary"><i class="fas fa-times"></i> Belum</span>';
            }
        }
        $dapodikHtml .= '</div>';
        $row[] = $dapodikHtml;

        // Action column: view details (keeps column count consistent with table header)
        $encId = function_exists('convert') ? @convert('encrypt', $aRow['usulan_pip_id']) : $aRow['usulan_pip_id'];
        $viewBtn = '<a href="javascript:void(0)" class="table-action table-action-view btn-tooltip btn-view mr-2" data-toggle="tooltip" data-placement="top" title="Lihat" data-id="' . $encId . '"><i class="fas fa-search"></i></a>';
        $row[] = '<div class="text-left">' . $viewBtn . '</div>';

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
