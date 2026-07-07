<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once '../../../library/function.php';

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
    $rawColumns = array_map(function($c) {
        $parts = preg_split('/\s+AS\s+/i', $c);
        return trim($parts[0]);
    }, $aColumns);
    $sIndexColumn = "u_p.usulan_pip_id";
    $sTable = "usulan_pip u_p LEFT JOIN user u ON u_p.user_id = u.user_id LEFT JOIN kelas k ON u.kelas = k.kelas_id";

    $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

    $req = &$_REQUEST;

    $start = isset($req['start']) ? intval($req['start']) : (isset($req['iDisplayStart']) ? intval($req['iDisplayStart']) : 0);
    $length = isset($req['length']) ? intval($req['length']) : (isset($req['iDisplayLength']) ? intval($req['iDisplayLength']) : 25);
    $sLimit = ($length != -1) ? "LIMIT " . intval($start) . ", " . intval($length) : "";

    $sOrder = "ORDER BY u_p.tanggal_pengajuan DESC, {$sIndexColumn} DESC";
    if (!empty($req['order']) && is_array($req['order'])) {
        $orderClauses = [];
        foreach ($req['order'] as $ord) {
            $colIdx = isset($ord['column']) ? intval($ord['column']) : null;
            $dir = (isset($ord['dir']) && strtolower($ord['dir']) === 'desc') ? 'DESC' : 'ASC';
            if ($colIdx !== null && isset($rawColumns[$colIdx])) {
                $orderClauses[] = $rawColumns[$colIdx] . ' ' . $dir;
            }
        }
        if (!empty($orderClauses)) $sOrder = 'ORDER BY ' . implode(', ', $orderClauses);
    }

    $sWhere = "";
    $globalSearch = '';
    if (isset($req['search']['value'])) $globalSearch = $req['search']['value'];
    elseif (isset($req['sSearch'])) $globalSearch = $req['sSearch'];
    $globalSearch = trim($globalSearch);
    if ($globalSearch !== '') {
        $escaped = mysqli_real_escape_string($gaSql['link'], $globalSearch);
        $parts = [];
        foreach ($rawColumns as $col) {
            $parts[] = $col . " LIKE '%" . $escaped . "%'";
        }
        $sWhere = "WHERE (" . implode(' OR ', $parts) . ")";
    }

    $conditions = [];
    if (!empty($req['status'])) {
        $conditions[] = "u_p.status = '" . mysqli_real_escape_string($gaSql['link'], $req['status']) . "'";
    }
    if (!empty($req['kelas'])) {
        $conditions[] = "u.kelas = '" . mysqli_real_escape_string($gaSql['link'], $req['kelas']) . "'";
    }
    if (!empty($conditions)) {
        $combined = implode(' AND ', $conditions);
        $sWhere = $sWhere !== '' ? $sWhere . ' AND (' . $combined . ')' : 'WHERE (' . $combined . ')';
    }

    $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . implode(", ", $aColumns) . " FROM $sTable $sWhere $sOrder $sLimit";
    $rResult = mysqli_query($gaSql['link'], $sQuery);

    $output = ['data' => []];
    if ($rResult === false) {
        $output['error'] = 'DB error: ' . mysqli_error($gaSql['link']);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($output); exit;
    }

    $rFiltered = mysqli_query($gaSql['link'], "SELECT FOUND_ROWS()");
    $iFiltered = intval(mysqli_fetch_row($rFiltered)[0] ?? 0);
    $rTotal = mysqli_query($gaSql['link'], "SELECT COUNT(*) FROM $sTable");
    $iTotal = intval(mysqli_fetch_row($rTotal)[0] ?? 0);

    $no = intval($start);
    while ($aRow = mysqli_fetch_assoc($rResult)) {
        $no++;
        $row = [];

        $row[] = '<div class="text-center">' . $no . '</div>';

        $avatar = trim($aRow['avatar'] ?? '');
        $src = ($avatar === '' || $avatar === 'avatar.jpg') ? '../content/avatar/avatar.jpg' : '../content/avatar/' . $avatar;
        $row[] = '<div class="text-center"><img src="' . htmlspecialchars($src) . '" alt="foto" style="height:40px;width:40px;object-fit:cover;border-radius:4px"></div>';

        $row[] = htmlspecialchars($aRow['nisn'] ?? '');
        $row[] = htmlspecialchars($aRow['nama_lengkap'] ?? '');
        $row[] = htmlspecialchars($aRow['nama_kelas'] ?? '-');

        $statusVal = trim($aRow['status'] ?? '');
        if ($statusVal === 'Pending' || $statusVal === '') {
            $statusBadge = '<span class="badge badge-warning">Pending</span>';
        } elseif ($statusVal === 'Diproses') {
            $statusBadge = '<span class="badge badge-info">Diproses</span>';
        } elseif ($statusVal === 'Disetujui') {
            $statusBadge = '<span class="badge badge-success">Disetujui</span>';
        } elseif ($statusVal === 'Ditolak') {
            $statusBadge = '<span class="badge badge-danger">Ditolak</span>';
        } else {
            $statusBadge = '<span class="badge badge-secondary">' . htmlspecialchars($statusVal) . '</span>';
        }
        $row[] = $statusBadge;

        $ket = htmlspecialchars(strip_tags($aRow['keterangan'] ?? ''));
        if (mb_strlen($ket) > 100) $ket = mb_substr($ket, 0, 100) . '...';
        $row[] = $ket ?: '-';

        $tgl = $aRow['tanggal_pengajuan'] ?? '';
        $row[] = $tgl ? date('d/m/Y', strtotime($tgl)) : '-';

        $enc = function_exists('convert') ? convert('encrypt', $aRow['usulan_pip_id']) : $aRow['usulan_pip_id'];
        $row[] = '<div class="text-center"><a href="javascript:void(0)" class="table-action table-action-view btn-view-history" title="Lihat Detail" data-id="' . $enc . '"><i class="fas fa-search"></i></a></div>';

        $output['data'][] = $row;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'data' => $output['data'],
        'recordsTotal' => $iTotal,
        'recordsFiltered' => $iFiltered,
        'draw' => isset($req['draw']) ? intval($req['draw']) : 0
    ]);
}
