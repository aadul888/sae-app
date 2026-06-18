<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    include('../../../library/function.php');
    // Load current user info (sets $current_user) if available
    // path: admin/mod/user/datatable.php -> admin/login/user.php => ../../login/user.php
    if (file_exists(__DIR__ . '/../../login/user.php')) {
        require_once __DIR__ . '/../../login/user.php';
    }
    require_once '../../../library/phpqrcode/qrlib.php';

    $aColumns = ['user_id', 'nisn', 'nama_lengkap', 'tanggal_lahir', 'jenis_kelamin', 'kelas', 'avatar', 'status', 'konfirmasi', 'telp', 'koordinator'];
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

    $sOrder = "ORDER BY nama_lengkap ASC";
    if (isset($_GET['iSortCol_0'])) {
        $sOrder = "ORDER BY nama_lengkap ASC";
        for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
            if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . "
                    " . mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_' . $i]) . ", ";
            }
        }

        $sOrder = substr_replace($sOrder, "", -2);
        if ($sOrder == "ORDER BY nama_lengkap ASC") {
            $sOrder = "ORDER BY nama_lengkap ASC";
        }
    }

    // Selalu filter status = 'Aktif' paling awal
    $sWhere = "WHERE status = 'Aktif'";
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
    // Try to detect if the current user is a wali (class guardian) by matching
    // either `kelas.wali_kelas_ptk_id` or `kelas.wali_kelas_admin_id`. This
    // avoids relying on a specific `level_id` value — any admin who is set as
    // wali in the `kelas` table will be detected and the results will be
    // restricted to their class. An explicit `POST['kelas']` (from the
    // client filter) still overrides and is applied afterwards.
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

        // If a kelas was found for this user, restrict results to that kelas.
        if ($kelas_id != '') {
            if ($sWhere == "") {
                $sWhere = "WHERE kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
            } else {
                $sWhere .= " AND kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
            }
        }
        // If no kelas found, do not automatically block results here — an
        // explicit `kelas` parameter will be handled below. This prevents
        // accidentally returning zero rows for admins who are not recorded as
        // wali but may still need broader access.
    }

    // Explicit client-side filter: use provided kelas parameter (POST has priority)
    if ((isset($_POST['kelas']) && $_POST['kelas'] != '') || (isset($_GET['kelas']) && $_GET['kelas'] != '')) {
        $req_kelas = isset($_POST['kelas']) ? mysqli_real_escape_string($gaSql['link'], $_POST['kelas']) : mysqli_real_escape_string($gaSql['link'], $_GET['kelas']);
        if (!empty($req_kelas)) {
            $kelas_id = $req_kelas;
        }
    }

    // Apply kelas filter if determined (explicit filter overrides the detected wali kelas)
    if ($kelas_id != '') {
        // Remove any previous kelas filter added from wali-detection to avoid
        // duplicating conditions — rebuild the kelas clause safely.
        // Ensure we still keep the base status condition already in $sWhere.
        // Append the kelas filter.
        if (strpos($sWhere, "kelas='") !== false) {
            // kelas already included (from wali detection), keep as-is
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
            if ($sWhere == "") {
                $sWhere = "WHERE ";
            } else {
                $sWhere .= " AND ";
            }
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $_GET['sSearch_' . $i]) . "%' ";
        }
    }

    $sQuery = " SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) . "
        FROM $sTable
        $sWhere
        $sOrder
        $sLimit ";
    $rResult = mysqli_query($gaSql['link'], $sQuery);
    
    if (!$rResult) {
        error_log("Main Query Error: " . mysqli_error($gaSql['link']));
        error_log("Main Query: " . $sQuery);
        // Return empty result jika query gagal
        echo json_encode(array(
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array(),
            "statusStat" => array(
                'total' => 0,
                'identitas_sesuai' => 0,
                'belum_konfirmasi' => 0,
                'identitas_belum_sesuai' => 0,
                'berkas_valid' => 0,
                'berkas_belum' => 0
            )
        ));
        exit;
    }

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

    // Compute summary statistics (use same $sWhere so stats reflect current filters)
    $statusStat = array(
        'total' => 0,
        'identitas_sesuai' => 0,
        'belum_konfirmasi' => 0,
        'identitas_belum_sesuai' => 0,
        'berkas_valid' => 0,
        'berkas_belum' => 0
    );

    // Build stats query - join berkas to check validasi_berkas
    // Ekstrak filter dari $sWhere tanpa kata WHERE dan sesuaikan untuk subquery
    $filterCondForSubquery = '';
    if (strpos($sWhere, 'WHERE') === 0) {
        $baseFilter = trim(substr($sWhere, 5));
        // Replace column references untuk subquery (hapus duplikasi status dan sesuaikan alias)
        $baseFilter = str_replace("status = 'Aktif'", "", $baseFilter);
        $baseFilter = str_replace("kelas=", "u2.kelas=", $baseFilter); // untuk subquery pertama
        $baseFilter = preg_replace('/\s+AND\s+AND\s+/', ' AND ', $baseFilter); // bersihkan AND ganda
        $baseFilter = preg_replace('/^\s*AND\s+/', '', $baseFilter); // hapus AND di awal
        $baseFilter = preg_replace('/\s+AND\s*$/', '', $baseFilter); // hapus AND di akhir
        $filterCondForSubquery = trim($baseFilter);
    }
    
    $filterCondForSubquery2 = $filterCondForSubquery;
    if (!empty($filterCondForSubquery2)) {
        $filterCondForSubquery2 = str_replace("u2.kelas=", "u3.kelas=", $filterCondForSubquery2);
    }
    
    // Hitung user yang benar-benar punya berkas valid (tanpa duplikat, tanpa user tanpa berkas)
    $statsQuery = "SELECT 
        COUNT(DISTINCT u.user_id) AS total,
        COUNT(DISTINCT CASE WHEN TRIM(LOWER(u.konfirmasi)) = 'sesuai' THEN u.user_id END) AS identitas_sesuai,
        COUNT(DISTINCT CASE WHEN TRIM(LOWER(u.konfirmasi)) = 'belum sesuai' THEN u.user_id END) AS identitas_belum_sesuai,
        COUNT(DISTINCT CASE WHEN TRIM(LOWER(u.konfirmasi)) = 'belum konfirmasi' THEN u.user_id END) AS belum_konfirmasi,
        (
            SELECT COUNT(DISTINCT b1.user_id)
            FROM berkas b1
            INNER JOIN user u2 ON u2.user_id = b1.user_id
            WHERE TRIM(LOWER(b1.validasi_berkas)) = 'valid'
                AND u2.status = 'Aktif'
                " . (!empty($filterCondForSubquery) ? "AND (" . $filterCondForSubquery . ")" : "") . "
        ) AS berkas_valid,
        (
            SELECT COUNT(DISTINCT u3.user_id)
            FROM user u3
            WHERE u3.status = 'Aktif'
                " . (!empty($filterCondForSubquery2) ? "AND (" . $filterCondForSubquery2 . ")" : "") . "
                AND NOT EXISTS (
                    SELECT 1 FROM berkas b3 WHERE b3.user_id = u3.user_id AND TRIM(LOWER(b3.validasi_berkas)) = 'valid'
                )
        ) AS berkas_belum
        FROM user u
        $sWhere
    ";

    $rStats = mysqli_query($gaSql['link'], $statsQuery);
    if (!$rStats) {
        // Log error untuk debugging
        error_log("Stats Query Error: " . mysqli_error($gaSql['link']));
        error_log("Stats Query: " . $statsQuery);
        // Set default values jika query gagal
        $statusStat = array(
            'total' => 0,
            'identitas_sesuai' => 0,
            'belum_konfirmasi' => 0,
            'identitas_belum_sesuai' => 0,
            'berkas_valid' => 0,
            'berkas_belum' => 0
        );
    } elseif ($rowStats = mysqli_fetch_assoc($rStats)) {
        $statusStat['total'] = isset($rowStats['total']) ? (int)$rowStats['total'] : 0;
        $statusStat['identitas_sesuai'] = isset($rowStats['identitas_sesuai']) ? (int)$rowStats['identitas_sesuai'] : 0;
        $statusStat['belum_konfirmasi'] = isset($rowStats['belum_konfirmasi']) ? (int)$rowStats['belum_konfirmasi'] : 0;
        $statusStat['identitas_belum_sesuai'] = isset($rowStats['identitas_belum_sesuai']) ? (int)$rowStats['identitas_belum_sesuai'] : 0;
        $statusStat['berkas_valid'] = isset($rowStats['berkas_valid']) ? (int)$rowStats['berkas_valid'] : 0;
        $statusStat['berkas_belum'] = isset($rowStats['berkas_belum']) ? (int)$rowStats['berkas_belum'] : 0;
    }

    // attach to output so client can read it
    $output['statusStat'] = $statusStat;

    $no = 0;
    while ($aRow = mysqli_fetch_array($rResult)) {
        $no++;
        extract($aRow);
        $row = array();

        $query_kelas = "SELECT nama_kelas FROM kelas WHERE kelas_id='$aRow[kelas]'";
        $result_kelas = $gaSql['link']->query($query_kelas);
        $data_kelas = null;
        if ($result_kelas && $result_kelas->num_rows > 0) {
            $data_kelas = $result_kelas->fetch_assoc();
        }
        $nama_kelas = ($data_kelas && isset($data_kelas['nama_kelas'])) ? $data_kelas['nama_kelas'] : '-';

        // Determine avatar to display.
        // Prefer the stored `avatar` column (it may contain a cache-busting query string like '?t=...').
        // For filesystem checks we strip the query string; for the image `src` we use the stored value so browsers will reload when the timestamp changes.
        $avatar = '<img src="../content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';
        if (!empty($aRow['avatar']) && $aRow['avatar'] != 'avatar.jpg') {
            $stored_avatar = strip_tags($aRow['avatar']);
            $stored_file = preg_replace('/\?.*/', '', $stored_avatar);
            if (file_exists('../../../content/avatar/' . $stored_file)) {
                $avatar = '<img src="../content/avatar/' . $stored_avatar . '" class="imaged w100 rounded" height="50">';
            }
        }
        // If no stored avatar file found, fall back to nisn.png if present
        if ($avatar === '<img src="../content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">') {
            $nisn_file = '../../../content/avatar/' . strip_tags($aRow['nisn']) . '.png';
            if (file_exists($nisn_file)) {
                $avatar = '<img src="../content/avatar/' . strip_tags($aRow['nisn']) . '.png" class="imaged w100 rounded" height="50">';
            }
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

        /** Buat Qrcode Otomatis dan sertakan timestamp pada URL untuk cache-busting */
        $qrcode_file = '../../../content/qrcode/' . strip_tags($aRow['nisn']) . '.jpg';
        if (!file_exists($qrcode_file)) {
            $codeContents = '' . $site_url . '/' . strip_tags($aRow['nisn']) . '';
            $tempdir    = '../../../content/qrcode/';
            $namafile   = '' . strip_tags($aRow['nisn']) . '.jpg';
            $quality    = 'QR_ECLEVEL_Q'; //ada 4 pilihan, L (Low), M(Medium), Q(Good), H(High)
            $ukuran     = 10; //batasan 1 paling kecil, 10 paling besar
            $padding    = 1;
            QRCode::png($codeContents, $tempdir . $namafile, $quality, $ukuran, $padding);
        }
        // Prepare a src/href with file modification time as query param so datatable shows latest QR image
        $qrcode_src = '../content/qrcode/' . strip_tags($aRow['nisn']) . '.jpg';
        if (file_exists($qrcode_file)) {
            $qrcode_src .= '?t=' . filemtime($qrcode_file);
        }

        // Build row data (remove the incorrect for loop)
        $onlick = "','";
        $onlick = explode(",", $onlick);

        $row[] = '<div class="text-center">' . $no . '</div>';
        $avatar_zoom_href = '../content/avatar/avatar.jpg';
        if (!empty($aRow['avatar']) && $aRow['avatar'] != 'avatar.jpg') {
            $stored_avatar_zoom = strip_tags($aRow['avatar']);
            $stored_file_zoom = preg_replace('/\?.*/', '', $stored_avatar_zoom);
            if (file_exists('../../../content/avatar/' . $stored_file_zoom)) {
                $avatar_zoom_href = '../content/avatar/' . $stored_avatar_zoom;
            }
        }
        if ($avatar_zoom_href === '../content/avatar/avatar.jpg') {
            $nisn_zoom_file = '../../../content/avatar/' . strip_tags($aRow['nisn']) . '.png';
            if (file_exists($nisn_zoom_file)) {
                $avatar_zoom_href = '../content/avatar/' . strip_tags($aRow['nisn']) . '.png';
            }
        }

        $row[] = '<div class="text-center"><a class="open-popup-link" href="' . $avatar_zoom_href . '" title="' . strip_tags($aRow['nama_lengkap']) . '">' . $avatar . '</a></div>';
        $row[] = '<div class="text-center">
                                        <a class="open-popup-link" href="' . $qrcode_src . '" title="' . strip_tags($aRow['nama_lengkap']) . '">
                                            <img src="' . $qrcode_src . '" class="imaged w100 rounded" height="50">
                                        </a>
                                </div>';
        $row[] = '' . strip_tags($aRow['nisn']) . '';
        $row[] = '<b>' . strip_tags($aRow['nama_lengkap']) . '</b>' . ((isset($aRow['koordinator']) && $aRow['koordinator'] == 1) ? ' <span class="badge badge-info">Koordinator</span>' : '');
        $row[] = strip_tags($aRow['jenis_kelamin']);
        $row[] = strip_tags($nama_kelas);

        // Kolom status dengan aksi toggle
        $active = '<label class="custom-toggle" style="display:inline-block">'
            . '<input type="checkbox" class="btn-active active' . $aRow['user_id'] . '" data-id="' . $aRow['user_id'] . '" data-active="' . (strtolower($aRow['status']) == 'aktif' ? 'Y' : 'N') . '"' . (strtolower($aRow['status']) == 'aktif' ? ' checked' : '') . '>'
            . '<span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>'
            . '</label>';
        $row[] = '<div class="text-center">' . $active . '</div>';

        // Ganti tanggal lahir dengan kontak WA
        $telp = preg_replace('/[^0-9]/', '', $aRow['telp']);
        if (strlen($telp) > 7) {
            // Pastikan format nomor WA Indonesia
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
        // Tambahkan warna putih pada teks badge WA
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

        if (isset($current_user) && isset($current_user['level_id']) && intval($current_user['level_id']) === 1) {
            // Level 1: tampilkan semua aksi
            $kartu_onclick = "openKartuModal('" . strip_tags($aRow['user_id']) . "', '" . strip_tags($aRow['nisn']) . "')";
            $row[] = '<div class="text-center">
                <a href="javascript:void(0)" onClick="location.href=' . $onlick[0] . '?mod=user&op=profile&id=' . epm_encode($aRow['user_id']) . '' . $onlick[1] . ';" class="table-action table-action-warning btn-tooltip" data-toggle="tooltip" title="Profil Lengkap">
                    <i class="fas fa-user-check"></i>
                </a>
                    <a href="javascript:void(0)" onclick="' . $kartu_onclick . '" class="table-action table-action-secondary btn-tooltip" data-toggle="tooltip" title="Preview Kartu">
                        <i class="fas fa-id-card"></i>
                    </a>
                <a href="javascript:void(0)" onClick="location.href=' . $onlick[0] . '?mod=user&op=update&id=' . epm_encode($aRow['user_id']) . '' . $onlick[1] . ';" class="table-action table-action-primary btn-tooltip" data-toggle="tooltip" title="Edit">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="javascript:void(0)" class="table-action table-action-info btn-tooltip btn-reset-password-simple" data-toggle="tooltip" data-id="' . strip_tags(epm_encode($aRow['user_id'])) . '" data-name="' . strip_tags($aRow['nama_lengkap']) . '" title="Reset Password (Langsung)">
                    <i class="fas fa-key"></i>
                </a>
                <a href="javascript:void(0)" class="table-action table-action-warning btn-tooltip btn-reset-password-wa" data-toggle="tooltip" data-id="' . strip_tags(epm_encode($aRow['user_id'])) . '" data-name="' . strip_tags($aRow['nama_lengkap']) . '" title="Reset Password + WhatsApp">
                    <i class="fas fa-mobile-alt"></i>
                </a>
                <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-name="' . strip_tags($aRow['nama_lengkap']) . '" data-id="' . strip_tags(epm_encode($aRow['user_id'])) . '" title="Hapus">
                    <i class="fas fa-trash"></i>
                </a>
                <a href="javascript:void(0)" class="table-action table-action-success btn-tooltip btn-koordinator" data-toggle="tooltip" data-id="' . strip_tags(epm_encode($aRow['user_id'])) . '" data-name="' . strip_tags($aRow['nama_lengkap']) . '" title="Jadikan Koordinator">
                    <i class="fas fa-user-tie"></i>
                </a>
                <!-- Tombol baru: Reset Konfirmasi -->
                <a href="javascript:void(0)" class="table-action table-action-dark btn-tooltip btn-reset-konfirmasi" data-toggle="tooltip" data-id="' . strip_tags(epm_encode($aRow['user_id'])) . '" data-name="' . strip_tags($aRow['nama_lengkap']) . '" title="Reset Konfirmasi">
                    <i class="fas fa-undo-alt"></i>
                </a>
            </div>';
        } else {
            // Level lain: hanya tombol profil
            $kartu_onclick = "openKartuModal('" . strip_tags($aRow['user_id']) . "', '" . strip_tags($aRow['nisn']) . "')";
            $row[] = '<div class="text-center">
                <a href="javascript:void(0)" onClick="location.href=' . $onlick[0] . '?mod=user&op=profile&id=' . epm_encode($aRow['user_id']) . '' . $onlick[1] . ';" class="table-action table-action-warning btn-tooltip" data-toggle="tooltip" title="Profil Lengkap">
                    <i class="fas fa-user-check"></i>
                </a>
                <a href="javascript:void(0)" onclick="' . $kartu_onclick . '" class="table-action table-action-secondary btn-tooltip" data-toggle="tooltip" title="Preview Kartu">
                    <i class="fas fa-id-card"></i>
                </a>
            </div>';
        }
        $output['aaData'][] = $row;
    }
    echo json_encode($output);
}
