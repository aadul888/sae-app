<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    include('../../../library/function.php');
    require_once '../../login/user.php';

    $aColumns = ['admin_id', 'fullname', 'email', 'phone', 'level_id', 'tugas_tambahan', 'status', 'avatar', 'active'];
    $sIndexColumn = "admin_id";
    $sTable = "admin";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    $start = $_POST['start'] ?? $_GET['iDisplayStart'] ?? 0;
    $length = $_POST['length'] ?? $_GET['iDisplayLength'] ?? 25;

    if ($length != '-1') {
        $sLimit = "LIMIT " . mysqli_real_escape_string($gaSql['link'], $start) . ", " .
            mysqli_real_escape_string($gaSql['link'], $length);
    }

    $sOrder = "ORDER BY admin_id DESC";
    $order_column = $_POST['order'][0]['column'] ?? $_GET['iSortCol_0'] ?? 0;
    $order_dir = $_POST['order'][0]['dir'] ?? $_GET['sSortDir_0'] ?? 'desc';
    $order_dir = (strtolower($order_dir) === 'asc') ? 'ASC' : 'DESC';

    if (isset($order_column) && isset($aColumns[$order_column])) {
        $sOrder = "ORDER BY " . $aColumns[$order_column] . " " . $order_dir;
    }

    $sWhere = "";
    $search_value = $_POST['search']['value'] ?? $_GET['sSearch'] ?? '';

    if (!empty($search_value)) {
        $sWhere = "WHERE (";
        for ($i = 0; $i < count($aColumns); $i++) {
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $search_value) . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    // Individual column search - simplified

    // Filter by Level Utama
    $filter_level = isset($_POST['filter_level']) ? mysqli_real_escape_string($gaSql['link'], $_POST['filter_level']) : '';
    $filter_tugas = isset($_POST['filter_tugas']) ? mysqli_real_escape_string($gaSql['link'], $_POST['filter_tugas']) : '';

    if (!empty($filter_level) || !empty($filter_tugas)) {
        $conditions = [];
        if (!empty($sWhere)) {
            // Extract existing WHERE conditions (remove the "WHERE" keyword)
            $existingCond = preg_replace('/^WHERE\s+/i', '', $sWhere);
            $conditions[] = $existingCond;
        }
        if (!empty($filter_level)) {
            $conditions[] = "level_id = '$filter_level'";
        }
        if (!empty($filter_tugas)) {
            $conditions[] = "tugas_tambahan REGEXP '(^|,)$filter_tugas(:[0-9]+)?(,|$)'";
        }
        $sWhere = "WHERE " . implode(' AND ', $conditions);
    }

    $sQuery = " SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) . "
        FROM $sTable
        $sWhere
        $sOrder
        $sLimit ";
    $rResult = mysqli_query($gaSql['link'], $sQuery);

    $sQuery = "SELECT FOUND_ROWS()";
    $rResultFilterTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];

    $sQuery = "SELECT COUNT(" . $sIndexColumn . ") FROM   $sTable";
    $rResultTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultTotal = mysqli_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];

    $output = array(
        "draw" => intval($_POST['draw'] ?? $_GET['sEcho'] ?? 1),
        "recordsTotal" => $iTotal,
        "recordsFiltered" => $iFilteredTotal,
        "data" => array()
    );

    // Preload all level names
    $levelMap = [];
    $rLevel = mysqli_query($gaSql['link'], "SELECT level_id, level_nama, tipe FROM level");
    while ($lRow = mysqli_fetch_assoc($rLevel)) {
        $levelMap[$lRow['level_id']] = $lRow;
    }

    $no = 0;
    while ($aRow = mysqli_fetch_array($rResult)) {
        $no++;
        extract($aRow);
        $row = array();
        $onlick = "','";
        $onlick = explode(",", $onlick);

        if ($aRow['avatar'] == NULL or $aRow['avatar'] == 'avatar.jpg') {
            $avatar = '<img src="./assets/avatar/avatar.jpg" class="imaged rounded" width="50" height="50"">';
        } else {
            $avatar = '<img src="./assets/avatar/' . strip_tags($aRow['avatar']) . '" class="imaged rounded" width="50" height="50">';
        }

        if ($aRow['active'] == 'Y') {
            if ($current_user['admin_id'] == $aRow['admin_id']) {
                $active = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-active" data-active="Y" disabled checked>
                    <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
            </label>';
            } else {
                $active = '<label class="custom-toggle" style="display:inline-block">
                <input type="checkbox" class="btn-active active' . $aRow['admin_id'] . '" data-id="' . $aRow['admin_id'] . '" data-active="Y" checked>
                    <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
            </label>';
            }
        } else {
            $active = '<label class="custom-toggle" style="display:inline-block">
            <input type="checkbox" class="btn-active active' . $aRow['admin_id'] . '"  data-id="' . $aRow['admin_id'] . '"  data-active="N">
            <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
          </label>';
        }

        if ($aRow['status'] == 'Online') {
            $status = '<small class="badge badge-dot text-info" style="font-size:13px;"><i class="bg-success"></i>Online</small>';
        } else {
            $status = '<small class="badge badge-dot" style="font-size:13px;"><i class="bg-danger"></i>Offline</small>';
        }

        $button = '<a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-forgot" data-name="' . strip_tags($aRow['fullname']) . '" data-id="' . strip_tags(epm_encode($aRow['admin_id'])) . '" data-toggle="tooltip" title="Resset Password">
                <i class="fas fa-key"></i>
            </a>

            <a href="javascript:void(0)" onClick="location.href=' . $onlick[0] . './admin?op=update&id=' . epm_encode($aRow['admin_id']) . '' . $onlick[1] . ';" class="table-action table-action-primary btn-tooltip" data-toggle="tooltip" title="Edit">
                <i class="fas fa-edit"></i>
            </a>';
        if ($current_user['admin_id'] == $aRow['admin_id']) {
            $button_hapus = '
            <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-error" data-toggle="tooltip" title="Hapus">
                <i class="fas fa-trash"></i>
            </a>';
        } else {
            $button_hapus = '
            <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-name="' . strip_tags($aRow['fullname']) . '" data-id="' . strip_tags(epm_encode($aRow['admin_id'])) . '" title="Hapus">
                <i class="fas fa-trash"></i>
                    </a>';
        }

        for ($i = 1; $i < count($aColumns); $i++) {
            // Resolve Level Utama
            $levelUtama = '-';
            if (!empty($aRow['level_id']) && isset($levelMap[$aRow['level_id']])) {
                $levelUtama = htmlspecialchars($levelMap[$aRow['level_id']]['level_nama']);
            }

            // Resolve Tugas Tambahan (bisa lebih dari 1)
            $tugasTambahanHtml = '-';
            if (!empty($aRow['tugas_tambahan'])) {
                $tugasItems = explode(',', $aRow['tugas_tambahan']);
                $badges = [];
                foreach ($tugasItems as $item) {
                    $parts = explode(':', trim($item));
                    $lid = trim($parts[0]);
                    if (isset($levelMap[$lid])) {
                        $badges[] = '<span class="badge badge-primary mr-1 mb-1">' . htmlspecialchars($levelMap[$lid]['level_nama']) . '</span>';
                    }
                }
                if (!empty($badges)) {
                    $tugasTambahanHtml = implode(' ', $badges);
                }
            }

            if ($current_user['admin_id'] == $aRow['admin_id']) {
                $row[] = '<div class="text-center text-info">' . $no . '</div>';
                $row[] = '<div class="text-center text-info">' . $avatar . '</div>';
                $row[] = '<span class="text-info">' . strip_tags($aRow['fullname']) . '<br>' . $status . '</span>';
                $row[] = '<span class="text-info">' . strip_tags($aRow['email']) . '</span>';
                $row[] = '<span class="text-info">' . strip_tags($aRow['phone']) . '</span>';
                $row[] = '<span class="text-info">' . $levelUtama . '</span>';
                $row[] = '<span class="text-info">' . $tugasTambahanHtml . '</span>';
                $row[] = '<div class="text-center">' . $active . '</div>';
                $row[] = '<div class="text-center">
                       ' . $button . '
                       ' . $button_hapus . '
                     </div>';
            } else {
                $row[] = '<div class="text-center">' . $no . '</div>';
                $row[] = '<div class="text-center">' . $avatar . '</div>';
                $row[] = '' . strip_tags($aRow['fullname']) . '<br>' . $status . '';
                $row[] = strip_tags($aRow['email']);
                $row[] = strip_tags($aRow['phone']);
                $row[] = $levelUtama;
                $row[] = $tugasTambahanHtml;
                $row[] = '<div class="text-center">' . $active . '</div>';
                $row[] = '<div class="text-center">
                       ' . $button . '
                       ' . $button_hapus . '
                     </div>';
            }
        }
        $output['data'][] = $row;
    }
    echo json_encode($output);
}
