<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once __DIR__ . '/../../../library/config.php';
    require_once __DIR__ . '/../../../library/function.php';
    require_once __DIR__ . '/../../login/user.php';
    $modul_route = 'jurusan';
    include __DIR__ . '/../check_role.php';

    // Sync master jurusan dari rombel Dapodik.
    // kode_jurusan dipertahankan agar bisa disesuaikan manual oleh admin.
    $sync_sql = "
        INSERT INTO jurusan (jurusan_id, kode_jurusan, nama_jurusan, kategori_jurusan, kelompok_jurusan_id)
        SELECT DISTINCT
            CAST(TRIM(jurusan_id) AS UNSIGNED) AS jurusan_id,
            TRIM(jurusan_id) AS kode_jurusan,
            TRIM(jurusan_id_str) AS nama_jurusan,
            CASE
                WHEN CHAR_LENGTH(TRIM(jurusan_id)) <= 5 THEN 1
                ELSE 2
            END AS kategori_jurusan,
            CAST(LEFT(TRIM(jurusan_id), 5) AS UNSIGNED) AS kelompok_jurusan_id
        FROM sync_rombongan_belajar
        WHERE jurusan_id IS NOT NULL
          AND TRIM(jurusan_id) <> ''
          AND TRIM(jurusan_id) REGEXP '^[0-9]+$'
          AND jurusan_id_str IS NOT NULL
          AND TRIM(jurusan_id_str) <> ''
        ON DUPLICATE KEY UPDATE
            nama_jurusan = VALUES(nama_jurusan),
            kategori_jurusan = VALUES(kategori_jurusan),
            kelompok_jurusan_id = VALUES(kelompok_jurusan_id),
            kode_jurusan = IF(jurusan.kode_jurusan IS NULL OR TRIM(jurusan.kode_jurusan) = '', VALUES(kode_jurusan), jurusan.kode_jurusan)
    ";
    $connection->query($sync_sql);

    $aColumns = ['j.jurusan_id', 'j.kode_jurusan', 'j.nama_jurusan'];
    $gaSql = [];
    $gaSql['user'] = DB_USER;
    $gaSql['password'] = DB_PASSWD;
    $gaSql['db'] = DB_NAME;
    $gaSql['server'] = DB_HOST;
    $gaSql['link'] = new mysqli($gaSql['server'], $gaSql['user'], $gaSql['password'], $gaSql['db']);

    $sLimit = '';
    if (isset($_REQUEST['iDisplayStart']) && $_REQUEST['iDisplayLength'] != '-1') {
        $sLimit = 'LIMIT ' . intval($_REQUEST['iDisplayStart']) . ', ' . intval($_REQUEST['iDisplayLength']);
    }

    $sOrder = 'ORDER BY j.kategori_jurusan ASC, j.nama_jurusan ASC';

    $sWhere = "WHERE EXISTS (
        SELECT 1 FROM sync_rombongan_belajar sr
        WHERE sr.jurusan_id REGEXP '^[0-9]+$'
          AND CAST(sr.jurusan_id AS UNSIGNED) = j.jurusan_id
    )";

    if (!empty($_REQUEST['sSearch'])) {
        $search = mysqli_real_escape_string($gaSql['link'], $_REQUEST['sSearch']);
        $sWhere .= " AND (
            j.jurusan_id LIKE '%$search%'
            OR j.kode_jurusan LIKE '%$search%'
            OR j.nama_jurusan LIKE '%$search%'
            OR CASE WHEN j.kategori_jurusan = 1 THEN '10' ELSE '11-12' END LIKE '%$search%'
        )";
    }

    $sQuery = "
        SELECT
            j.jurusan_id,
            j.kode_jurusan,
            j.nama_jurusan,
            j.logo,
            j.kategori_jurusan,
            COUNT(DISTINCT u.user_id) AS jumlah_siswa
        FROM jurusan j
        LEFT JOIN user u
            ON u.jurusan_id = j.jurusan_id
            AND (u.status = '1' OR LOWER(u.status) = 'aktif')
        $sWhere
        GROUP BY j.jurusan_id, j.kode_jurusan, j.nama_jurusan, j.logo, j.kategori_jurusan
        $sOrder
        $sLimit
    ";

    $rResult = mysqli_query($gaSql['link'], $sQuery);

    $countQuery = "
        SELECT COUNT(*) AS total
        FROM jurusan j
        $sWhere
    ";
    $countResult = mysqli_query($gaSql['link'], $countQuery);
    $countRow = $countResult ? mysqli_fetch_assoc($countResult) : ['total' => 0];
    $iTotal = intval($countRow['total']);

    $output = [
        'iTotalRecords' => $iTotal,
        'iTotalDisplayRecords' => $iTotal,
        'recordsTotal' => $iTotal,
        'recordsFiltered' => $iTotal,
        'draw' => isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 1,
        'aaData' => [],
        'data' => []
    ];

    $no = 0;
    while ($aRow = mysqli_fetch_assoc($rResult)) {
        $no++;
        $row = [];
        $row[] = '<div class="text-center">' . $no . '</div>';
        $row[] = '<div class="text-center">' . htmlspecialchars($aRow['jurusan_id']) . '</div>';
        $tingkat_label = intval($aRow['kategori_jurusan']) === 1 ? '10' : '11-12';
        $tingkat_order = intval($aRow['kategori_jurusan']) === 1 ? 1 : 2;
        $row[] = '<div class="text-center" data-order="' . $tingkat_order . '">' . $tingkat_label . '</div>';
        $row[] = htmlspecialchars($aRow['kode_jurusan']);
        $row[] = htmlspecialchars($aRow['nama_jurusan']);
        $row[] = '<div class="text-center">' . intval($aRow['jumlah_siswa']) . '</div>';

        $logo_file = trim((string)$aRow['logo']);
        $jurusan_id = trim((string)$aRow['jurusan_id']);
        $kode_jurusan = trim((string)$aRow['kode_jurusan']);

        $candidates = [];
        if ($logo_file !== '') {
            $candidates[] = $logo_file;
            if (!str_ends_with(strtolower($logo_file), '.png')) {
                $candidates[] = $logo_file . '.png';
            }
        }
        if ($jurusan_id !== '') {
            $candidates[] = $jurusan_id . '.png';
        }
        if ($kode_jurusan !== '') {
            $candidates[] = $kode_jurusan . '.png';
        }

        $candidates = array_unique($candidates);
        $found_web_path = '';

        $content_dir = realpath(__DIR__ . '/../../../content');
        if (!$content_dir) {
            $content_dir = __DIR__ . '/../../../content';
        }

        foreach ($candidates as $cand) {
            $check_map = [
                $content_dir . '/assets/logo-jurusan/' . $cand => '../content/assets/logo-jurusan/' . $cand,
                $content_dir . '/logo-jurusan/' . $cand => '../content/logo-jurusan/' . $cand,
                $content_dir . '/' . $cand => '../content/' . $cand,
            ];
            foreach ($check_map as $fs_p => $web_p) {
                if (file_exists($fs_p)) {
                    $found_web_path = $web_p;
                    break 2;
                }
            }
        }

        if ($found_web_path !== '') {
            $row[] = '<div class="text-center"><img src="' . $found_web_path . '" alt="Logo" class="datatable-logo-jurusan" style="max-height:36px;max-width:36px;object-fit:contain;"></div>';
        } else {
            $row[] = '<div class="text-center"><span class="badge badge-subtle-secondary text-muted" style="font-size:11px;">No Logo</span></div>';
        }

        $aksi = '<span class="text-muted">-</span>';
        if ($data_role['modifikasi'] == 'Y') {
            $aksi = '<a href="javascript:void(0)" class="table-action table-action-primary btn-update btn-tooltip" data-toggle="tooltip" data-placement="right" title="Update Kode/Logo" data-id="' . htmlspecialchars($aRow['jurusan_id']) . '" data-kode="' . htmlspecialchars($aRow['kode_jurusan']) . '" data-name="' . htmlspecialchars($aRow['nama_jurusan']) . '"><i class="fas fa-edit"></i></a>';
        }
        $row[] = '<div class="text-center">' . $aksi . '</div>';

        $output['aaData'][] = $row;
    }

    $output['data'] = $output['aaData'];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
}
