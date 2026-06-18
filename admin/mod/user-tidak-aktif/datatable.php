<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    include('../../../library/function.php');
    // Load current user info if available (sets $current_user)
    if (file_exists(__DIR__ . '/../../login/user.php')) {
        require_once __DIR__ . '/../../login/user.php';
    }
    require_once '../../../library/phpqrcode/qrlib.php';

    $aColumns = ['user_id', 'nisn', 'nama_lengkap', 'tanggal_lahir', 'jenis_kelamin', 'kelas', 'avatar', 'status', 'konfirmasi', 'status', 'telp', 'koordinator'];
    $sIndexColumn = "user_id";
    $sTable = "user";
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;

    $gaSql['link'] =  new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = "";
    if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
        $sLimit = "LIMIT " . mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayStart']) . ", " .
            mysqli_real_escape_string($gaSql['link'], $_GET['iDisplayLength']);
    }

    $sOrder = "ORDER BY user_id ASC";
    if (isset($_GET['iSortCol_0'])) {
        $sOrder = "ORDER BY user_id ASC";
        for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
            if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . "
                    " . mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_' . $i]) . ", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY user_id ASC") {
            $sOrder = "ORDER BY user_id ASC";
        }
    }

    $sWhere = "";

    // Selalu filter status = 'Tidak Aktif' paling awal
    $sWhere = "WHERE status = 'Tidak Aktif'";

    // Pencarian global
    if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
        $sWhere .= " AND (";
        for ($i = 0; $i < count($aColumns); $i++) {
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $_GET['sSearch']) . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

    // Filter kelas (POST dari AJAX atau GET dari export/print)
    // Detect wali by matching kelas.wali_kelas_ptk_id or kelas.wali_kelas_admin_id
    // to the current user; apply kelas restriction when found. Explicit
    // client-provided `kelas` (POST) overrides detection.
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

        if ($kelas_id === '' && !empty($admin_id)) {
            $q_wali2 = $gaSql['link']->query("SELECT kelas_id FROM kelas WHERE wali_kelas_admin_id='" . mysqli_real_escape_string($gaSql['link'], $admin_id) . "' LIMIT 1");
            if ($q_wali2 && $r2 = $q_wali2->fetch_assoc()) {
                $kelas_id = $r2['kelas_id'];
            }
        }

        if ($kelas_id != '') {
            if ($sWhere == "") {
                $sWhere = "WHERE kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
            } else {
                $sWhere .= " AND kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
            }
        }
        // If no kelas found, do not block results here; explicit filter handled next.
    }

    // Explicit client-side filter: use provided kelas parameter (POST has priority)
    if ((isset($_POST['kelas']) && $_POST['kelas'] != '') || (isset($_GET['kelas']) && $_GET['kelas'] != '')) {
        $req_kelas = isset($_POST['kelas']) ? mysqli_real_escape_string($gaSql['link'], $_POST['kelas']) : mysqli_real_escape_string($gaSql['link'], $_GET['kelas']);
        if (!empty($req_kelas)) {
            $kelas_id = $req_kelas;
        }
    }

    // Apply kelas filter if present (explicit override will replace/double-check)
    if ($kelas_id != '') {
        if (strpos($sWhere, "kelas='") !== false) {
            // kelas already included (from detection) — if explicit override differs,
            // replace it by rebuilding the clause (keep it simple: append AND with explicit value).
            if (strpos($sWhere, "kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'") === false) {
                $sWhere .= " AND kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
            }
        } else {
            if ($sWhere == "") {
                $sWhere = "WHERE kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
            } else {
                $sWhere .= " AND kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
            }
        }
    }

    // Kolom pencarian individual
    for ($i = 0; $i < count($aColumns); $i++) {
        if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
            $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $_GET['sSearch_' . $i]) . "%' ";
        }
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
        // "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );

    $no = 0;
    while ($aRow = mysqli_fetch_array($rResult)) {
        $no++;
        extract($aRow);
        $row = array();

        $query_kelas = "SELECT nama_kelas FROM kelas WHERE kelas_id='$aRow[kelas]'";
        $result_kelas = $connection->query($query_kelas);
        $data_kelas = $result_kelas->fetch_assoc();

        if ($aRow['avatar'] == NULL or $aRow['avatar'] == 'avatar.jpg') {
            $avatar = '<img src="../content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';
        } else {
            $avatar = '
            <a class="open-popup-link" href="../content/avatar/' . strip_tags($aRow['avatar']) . '">
                <img src="../content/avatar/' . strip_tags($aRow['avatar']) . '" class="imaged w100 rounded" height="50">
            </a>';
        }


        // Perbaiki toggle active agar sesuai dengan kolom 'status' (Aktif/Tidak Aktif)
        if (strtolower($aRow['status']) == 'aktif') {
            $active = '<label class="custom-toggle" style="display:inline-block">
            <input type="checkbox" class="btn-active active' . $aRow['user_id'] . '" data-id="' . $aRow['user_id'] . '" data-active="Aktif" checked>
                <span class="custom-toggle-slider rounded-circle" data-label-off="Tidak Aktif" data-label-on="Aktif"></span>
          </label>';
        } else {
            $active = '<label class="custom-toggle" style="display:inline-block">
            <input type="checkbox" class="btn-active active' . $aRow['user_id'] . '"  data-id="' . $aRow['user_id'] . '"  data-active="Tidak Aktif">
            <span class="custom-toggle-slider rounded-circle" data-label-off="Tidak Aktif" data-label-on="Aktif"></span>
          </label>';
        }

        /** Buat Qrcode Otomatis */
        if (file_exists('../../../content/qrcode/' . strip_tags($aRow['nisn']) . '.jpg')) {
            //echo 'QR code ada';
        } else {
            /* --  End Random Karakter ---- */
            $codeContents = '' . $site_url . '/' . strip_tags($aRow['nisn']) . '';
            $tempdir    = '../../../content/qrcode/';
            $namafile   = '' . strip_tags($aRow['nisn']) . '.jpg';
            $quality    = 'QR_ECLEVEL_Q'; //ada 4 pilihan, L (Low), M(Medium), Q(Good), H(High)
            $ukuran     = 10; //batasan 1 paling kecil, 10 paling besar
            $padding    = 1;
            QRCode::png($codeContents, $tempdir . $namafile, $quality, $ukuran, $padding);
        }

        // Build row only once per user
        $row[] = '<div class="text-center">' . $no . '</div>';
        $row[] = '<div class="text-center">' . $avatar . ' </div>';
        $row[] = '<div class="text-center">
                        <a class="open-popup-link" href="../content/qrcode/' . strip_tags($aRow['nisn']) . '.jpg" title="' . strip_tags($aRow['nama_lengkap']) . '">
                            <img src="../content/qrcode/' . strip_tags($aRow['nisn']) . '.jpg" class="imaged w100 rounded" height="50">
                        </a>
                </div>';
        $row[] = '' . strip_tags($aRow['nisn']) . '';
        $row[] = '<b>' . strip_tags($aRow['nama_lengkap']) . '</b>' . ((isset($aRow['koordinator']) && $aRow['koordinator'] == 1) ? ' <span class="badge badge-info">Koordinator</span>' : '');
        $row[] = strip_tags($aRow['jenis_kelamin']);
        $row[] = strip_tags($data_kelas['nama_kelas']);

        // Kolom status dengan badge warna
        // Kolom status dengan aksi toggle
        $active = '<label class="custom-toggle" style="display:inline-block">'
            . '<input type="checkbox" class="btn-active active' . $aRow['user_id'] . '" data-id="' . $aRow['user_id'] . '" data-active="' . (strtolower($aRow['status']) == 'aktif' ? 'Y' : 'N') . '"' . (strtolower($aRow['status']) == 'aktif' ? ' checked' : '') . '>'
            . '<span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>'
            . '</label>';
        $row[] = '<div class="text-center">' . $active . '</div>';

        // Kontak WA
        $telp = preg_replace('/[^0-9]/', '', $aRow['telp']);
        if (strlen($telp) > 7) {
            if (substr($telp, 0, 1) == '0') {
                $wa = '62' . substr($telp, 1);
            } elseif (substr($telp, 0, 2) == '62') {
                $wa = $telp;
            } else {
                $wa = '62' . $telp;
            }
            $wa_link = 'https://wa.me/' . $wa;
            $kontak_html = '<a href="' . $wa_link . '" target="_blank" class="badge badge-success"><i class="fab fa-whatsapp"></i> ' . htmlspecialchars($telp) . '</a>';
        } else {
            $kontak_html = htmlspecialchars($telp);
        }
        $kontak_html = str_replace('badge-success', 'badge-success" ', $kontak_html);
        $row[] = $kontak_html;

        // Konfirmasi badge warna
        $konfirmasi = strip_tags($aRow['konfirmasi']);
        if ($konfirmasi == 'Sesuai') {
            $konfirmasi_html = '<span class="badge badge-success" >Sesuai</span>';
        } elseif ($konfirmasi == 'Belum Sesuai') {
            $konfirmasi_html = '<span class="badge badge-danger" >Belum Sesuai</span>';
        } elseif ($konfirmasi == '' || $konfirmasi == 'Belum Konfirmasi') {
            $konfirmasi_html = '<span class="badge badge-primary" style="color:#fff">Belum Konfirmasi</span>';
        } else {
            $konfirmasi_html = '<span class="badge badge-secondary" >' . $konfirmasi . '</span>';
        }
        $row[] = '<div class="text-center">' . $konfirmasi_html . '</div>';

        $onlick = "','";
        $onlick = explode(",", $onlick);

        if (isset($current_user) && isset($current_user['level_id']) && intval($current_user['level_id']) === 1) {
            // Level 1: tampilkan semua aksi
            $row[] = '<div class="text-center">
                <a href="javascript:void(0)" onClick="location.href=' . $onlick[0] . 'user?op=profile&id=' . epm_encode($aRow['user_id']) . '' . $onlick[1] . ';" class="table-action table-action-warning btn-tooltip" data-toggle="tooltip" title="Profil Lengkap">
                    <i class="fas fa-user-check"></i>
                </a>
                <a href="javascript:void(0)" onClick="location.href=' . $onlick[0] . 'user?op=update&id=' . epm_encode($aRow['user_id']) . '' . $onlick[1] . ';" class="table-action table-action-primary btn-tooltip" data-toggle="tooltip" title="Edit">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-reset-password" data-toggle="tooltip" data-id="' . strip_tags(epm_encode($aRow['user_id'])) . '" data-name="' . strip_tags($aRow['nama_lengkap']) . '" title="Reset Password">
                    <i class="fas fa-key"></i>
                </a>
                <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-name="' . strip_tags($aRow['nama_lengkap']) . '" data-id="' . strip_tags(epm_encode($aRow['user_id'])) . '" title="Hapus">
                    <i class="fas fa-trash"></i>
                </a>
                <a href="javascript:void(0)" class="table-action table-action-success btn-tooltip btn-koordinator" data-toggle="tooltip" data-id="' . strip_tags(epm_encode($aRow['user_id'])) . '" data-name="' . strip_tags($aRow['nama_lengkap']) . '" title="Jadikan Koordinator">
                    <i class="fas fa-user-tie"></i>
                </a>
            </div>';
        } else {
            // Level lain: hanya tombol profil
            $row[] = '<div class="text-center">
                <a href="javascript:void(0)" onClick="location.href=' . $onlick[0] . 'user?op=profile&id=' . epm_encode($aRow['user_id']) . '' . $onlick[1] . ';" class="table-action table-action-warning btn-tooltip" data-toggle="tooltip" title="Profil Lengkap">
                    <i class="fas fa-user-check"></i>
                </a>
            </div>';
        }

        $output['aaData'][] = $row;
    }
    echo json_encode($output);
}
