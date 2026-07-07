<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

    // Columns from usulan_pip table to show in admin list
    // Select from usulan_pip joined with user and kelas to get avatar (foto) and nama_kelas
    $aColumns = [
        "u_p.usulan_pip_id AS usulan_pip_id",
        "u.avatar AS avatar",
        "u.nisn AS nisn",
        "u.nama_lengkap AS nama_lengkap",
        "k.nama_kelas AS nama_kelas",
        "u_p.status AS status",
        "u_p.keterangan AS keterangan",
        "u_p.tanggal_pengajuan AS tanggal_pengajuan"
    ];
    // Build a parallel array of raw column expressions (without AS) for ORDER and WHERE
    $rawColumns = array_map(function($c) {
        $parts = preg_split('/\s+AS\s+/i', $c);
        return trim($parts[0]);
    }, $aColumns);
    $sIndexColumn = "u_p.usulan_pip_id";
    $sTable = "usulan_pip u_p LEFT JOIN user u ON u_p.user_id = u.user_id LEFT JOIN kelas k ON u.kelas = k.kelas_id";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    // Accept either GET or POST (DataTables legacy or 1.10+ parameter names)
    $req = &$_REQUEST;

    // Paging: prefer modern `start`/`length`, fallback to legacy `iDisplayStart`/`iDisplayLength`
    $start = isset($req['start']) ? intval($req['start']) : (isset($req['iDisplayStart']) ? intval($req['iDisplayStart']) : 0);
    $length = isset($req['length']) ? intval($req['length']) : (isset($req['iDisplayLength']) ? intval($req['iDisplayLength']) : 25);
    if ($length != -1) {
        $sLimit = "LIMIT " . intval($start) . ", " . intval($length);
    }

    // Ordering: handle modern `order` array or legacy `iSortCol_0` parameters
    // Default ordering: primary by status in specific sequence, then by oldest tanggal_pengajuan, fallback by index
    $sOrder = "ORDER BY FIELD(u_p.status, 'Pending','Diproses','Ditolak','Disetujui'), u_p.tanggal_pengajuan ASC, {$sIndexColumn} ASC";
    if (!empty($req['order']) && is_array($req['order'])) {
        $orderClauses = [];
        foreach ($req['order'] as $ord) {
            $colIdx = isset($ord['column']) ? intval($ord['column']) : null;
            $dir = isset($ord['dir']) && strtolower($ord['dir']) === 'desc' ? 'DESC' : 'ASC';
            if ($colIdx !== null && isset($rawColumns[$colIdx])) {
                $colExpr = $rawColumns[$colIdx];
                // Custom sort for status column: Pending, Diproses, Ditolak, Disetujui
                if (preg_match('/\bstatus\b/i', $colExpr)) {
                    $orderClauses[] = "FIELD($colExpr, 'Pending','Diproses','Ditolak','Disetujui') " . $dir;
                } else {
                    $orderClauses[] = $colExpr . ' ' . $dir;
                }
            }
        }
        if (!empty($orderClauses)) $sOrder = 'ORDER BY ' . implode(', ', $orderClauses);
    } elseif (isset($req['iSortCol_0'])) {
        $orderClauses = [];
        for ($i = 0; $i < intval($req['iSortingCols']); $i++) {
            $colIdx = intval($req['iSortCol_' . $i]);
            $dir = isset($req['sSortDir_' . $i]) && strtolower($req['sSortDir_' . $i]) === 'desc' ? 'DESC' : 'ASC';
            if (isset($rawColumns[$colIdx])) {
                $colExpr = $rawColumns[$colIdx];
                if (preg_match('/\bstatus\b/i', $colExpr)) {
                    $orderClauses[] = "FIELD($colExpr, 'Pending','Diproses','Ditolak','Disetujui') " . $dir;
                } else {
                    $orderClauses[] = $colExpr . ' ' . $dir;
                }
            }
        }
        if (!empty($orderClauses)) $sOrder = 'ORDER BY ' . implode(', ', $orderClauses);
    }

    // Filtering: global search (modern: `search[value]`), fallback legacy `sSearch`
    $sWhere = "";
    $globalSearch = '';
    if (isset($req['search']) && is_array($req['search']) && isset($req['search']['value'])) {
        $globalSearch = $req['search']['value'];
    } elseif (isset($req['sSearch'])) {
        $globalSearch = $req['sSearch'];
    }
    $globalSearch = trim($globalSearch);
    if ($globalSearch !== '') {
        $escaped = mysqli_real_escape_string($gaSql['link'], $globalSearch);
        $sWhere = "WHERE (";
        foreach ($rawColumns as $col) {
            $sWhere .= $col . " LIKE '%" . $escaped . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    // Filter kelas
    $kelasFilter = '';
    if ((isset($_POST['kelas']) && $_POST['kelas'] != '') || (isset($_GET['kelas']) && $_GET['kelas'] != '')) {
        $kelas_id = isset($_POST['kelas']) ? $_POST['kelas'] : $_GET['kelas'];
        $kelas_id = mysqli_real_escape_string($gaSql['link'], $kelas_id);
        $kelasFilter = "u.kelas = '" . $kelas_id . "'";
    }

    // Filter status
    $statusFilter = '';
    if ((isset($_POST['status']) && $_POST['status'] != '') || (isset($_GET['status']) && $_GET['status'] != '')) {
        $status_val = isset($_POST['status']) ? $_POST['status'] : $_GET['status'];
        $status_val = mysqli_real_escape_string($gaSql['link'], $status_val);
        $statusFilter = "u_p.status = '" . $status_val . "'";
    }

    // Combine filters
    $filterConditions = array_filter([$kelasFilter, $statusFilter]);
    if (!empty($filterConditions)) {
        $combinedFilters = implode(' AND ', $filterConditions);
        if ($sWhere !== '') {
            $sWhere .= ' AND (' . $combinedFilters . ')';
        } else {
            $sWhere = 'WHERE (' . $combinedFilters . ')';
        }
    }

    // Column-specific search: modern `columns[i][search][value]` or legacy `sSearch_i`
    if (isset($req['columns']) && is_array($req['columns'])) {
        foreach ($req['columns'] as $i => $col) {
            if (!empty($col['search']['value'])) {
                $val = mysqli_real_escape_string($gaSql['link'], $col['search']['value']);
                if ($sWhere == '') $sWhere = 'WHERE '; else $sWhere .= ' AND ';
                if (isset($rawColumns[$i])) $sWhere .= $rawColumns[$i] . " LIKE '%" . $val . "%' ";
            }
        }
    } else {
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($req['bSearchable_' . $i]) && $req['bSearchable_' . $i] == "true" && !empty($req['sSearch_' . $i])) {
                $val = mysqli_real_escape_string($gaSql['link'], $req['sSearch_' . $i]);
                if ($sWhere == '') $sWhere = 'WHERE '; else $sWhere .= ' AND ';
                $sWhere .= $rawColumns[$i] . " LIKE '%" . $val . "%' ";
            }
        }
    }

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
    // For total records, use base query with filters applied (without LIMIT)
    $totalQuery = "SELECT COUNT(*) AS cnt FROM $sTable";
    if ($sWhere !== '') {
        // Apply same WHERE conditions but without LIMIT for total count
        $totalQuery .= ' ' . $sWhere;
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

        // Keterangan base
        $keterangan = trim(strip_tags($aRow['keterangan'] ?? ''));

        // Status badge: normalize to three states -> Pending, Diterima, Ditolak
        $statusHtml = '';
        $statusVal = trim($aRow['status'] ?? '');
        // Treat both 'Disetujui' and 'Diproses' as accepted ('Diterima') for display
        if ($statusVal === 'Pending' || $statusVal === '') {
            $statusHtml = '<span class="badge badge-warning">Pending</span>';
        } elseif (in_array($statusVal, ['Disetujui', 'Diproses'], true)) {
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

        // Keterangan (may include rejection reason)
        // Show a short preview with a "selengkapnya" toggle if the text is long
        $fullText = $keterangan;
        $maxLen = 150;
        if (mb_strlen($fullText) > $maxLen) {
            $short = mb_substr($fullText, 0, $maxLen);
            $shortEsc = htmlspecialchars($short, ENT_QUOTES, 'UTF-8');
            $fullEsc = htmlspecialchars($fullText, ENT_QUOTES, 'UTF-8');
            $previewHtml = '<div class="keterangan-preview" data-full="' . $fullEsc . '">'
                         . nl2br($shortEsc)
                         . '... <a href="#" class="keterangan-toggle">selengkapnya</a>'
                         . '</div>';
        } else {
            $previewHtml = nl2br(htmlspecialchars($fullText, ENT_QUOTES, 'UTF-8'));
        }
        $row[] = $previewHtml;

        // Actions: view (open modal) + delete
        $viewBtn = '<a href="javascript:void(0)" class="table-action table-action-view btn-tooltip btn-view mr-2" data-toggle="tooltip" data-placement="top" title="Lihat" data-id="' . convert('encrypt', $aRow['usulan_pip_id']) . '"><i class="fas fa-search"></i></a>';
        $delBtn = '<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-placement="right" title="Hapus" data-id="' . convert('encrypt', $aRow['usulan_pip_id']) . '"><i class="fas fa-trash"></i></a>';
        $row[] = '<div class="text-left">' . $viewBtn . $delBtn . '</div>';

        $output['data'][] = $row;
    }
    
    // Calculate statistics berdasarkan filter yang sama
    $statsWhere = str_replace('SQL_CALC_FOUND_ROWS', '', $sWhere);
    if ($statsWhere === '') {
        $statsWhere = 'WHERE 1=1';
    }
    $statsQuery = "SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN u_p.status='Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN u_p.status='Disetujui' THEN 1 ELSE 0 END) AS disetujui,
        SUM(CASE WHEN u_p.status='Ditolak' THEN 1 ELSE 0 END) AS ditolak
      FROM $sTable $statsWhere";
    $statsResult = mysqli_query($gaSql['link'], $statsQuery);
    $stats = ['total' => 0, 'pending' => 0, 'disetujui' => 0, 'ditolak' => 0];
    if ($statsResult && $statsRow = mysqli_fetch_assoc($statsResult)) {
        $stats = [
            'total' => intval($statsRow['total'] ?? 0),
            'pending' => intval($statsRow['pending'] ?? 0),
            'disetujui' => intval($statsRow['disetujui'] ?? 0),
            'ditolak' => intval($statsRow['ditolak'] ?? 0)
        ];
    }
    
    // include DataTables meta keys
    $final = [
        'data' => $output['data'],
        'recordsTotal' => $iTotal,
        'recordsFiltered' => $iFiltered,
        'draw' => isset($req['draw']) ? intval($req['draw']) : 0,
        'stats' => $stats
    ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($final);
}
