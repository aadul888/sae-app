<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    include('../../../library/function.php');

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
        $logo_fs_path = realpath(dirname(__FILE__) . '/../../../content/assets/logo-jurusan/' . $logo_file);
        $logo_dir = realpath(dirname(__FILE__) . '/../../../content/assets/logo-jurusan/');
        if ($logo_file && $logo_fs_path && strpos($logo_fs_path, $logo_dir) === 0 && file_exists($logo_fs_path)) {
            $logo_path = '../content/assets/logo-jurusan/' . $logo_file;
        } else {
            $logo_path = './content/assets/logo-jurusan/default.png';
        }

        $row[] = '<div class="text-center"><img src="' . $logo_path . '" alt="Logo" class="datatable-logo-jurusan"></div>';

        $row[] = '<div class="text-center">
            <a href="javascript:void(0)" class="table-action table-action-primary btn-update btn-tooltip" data-toggle="tooltip" data-placement="right" title="Update Kode/Logo" data-id="' . htmlspecialchars($aRow['jurusan_id']) . '" data-kode="' . htmlspecialchars($aRow['kode_jurusan']) . '" data-name="' . htmlspecialchars($aRow['nama_jurusan']) . '">
                <i class="fas fa-edit"></i>
            </a>
        </div>';

        $output['aaData'][] = $row;
    }

    $output['data'] = $output['aaData'];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
}
