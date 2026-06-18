<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    include('../../../library/function.php');
    // Columns for pembaharuan: id, version, release_date, mandatory, pembaharuan, perbaikan, download_link
    $aColumns = ['id', 'version', 'release_date', 'mandatory', 'pembaharuan', 'perbaikan', 'download_link'];
    $sIndexColumn = "id";
    $sTable = "pembaharuan";
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

    $sOrder = "ORDER BY release_date DESC";
    if (isset($_GET['iSortCol_0'])) {
        // Bangun daftar klausa ORDER BY secara aman
        $orderClauses = array();
        for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
            $colIndex = intval($_GET['iSortCol_' . $i]);
            if (isset($_GET['bSortable_' . $colIndex]) && $_GET['bSortable_' . $colIndex] == "true") {
                // Ambil nama kolom dari definisi kolom yang diizinkan
                $colName = $aColumns[$colIndex];
                $dir = mysqli_real_escape_string($gaSql['link'], $_GET['sSortDir_' . $i]);
                $dir = (strtoupper($dir) === 'DESC') ? 'DESC' : 'ASC';
                $orderClauses[] = $colName . ' ' . $dir;
            }
        }

        if (count($orderClauses) > 0) {
            $sOrder = 'ORDER BY ' . implode(', ', $orderClauses);
        } else {
            // fallback ke release_date jika tidak ada klausa yang valid
            $sOrder = 'ORDER BY release_date DESC';
        }
    }

    $sWhere = "";
    if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
        // If there's already a WHERE (from type filter), start with AND
        $sWhereSearchPrefix = ($sWhere == "") ? "WHERE (" : "";
        $sWhere = ($sWhere == "") ? "WHERE (" : $sWhere . " AND (";
        for ($i = 0; $i < count($aColumns); $i++) {
            $sWhere .= $aColumns[$i] . " LIKE '%" . mysqli_real_escape_string($gaSql['link'], $_GET['sSearch']) . "%' OR ";
        }
        $sWhere = substr_replace($sWhere, "", -3);
        $sWhere .= ')';
    }

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

        // Query utama: fetch pembaharuan entries (respect optional filters)
            $sQuery = "SELECT id, version, release_date, mandatory, pembaharuan, perbaikan, download_link FROM pembaharuan " . $sWhere . " $sOrder $sLimit";
        $rResult = mysqli_query($gaSql['link'], $sQuery);

    // Filtered and total counts
    $sQuery = "SELECT COUNT(" . $sIndexColumn . ") FROM $sTable " . $sWhere;
    $rResultFilterTotal = mysqli_query($gaSql['link'], $sQuery);
    $aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];
    $iTotal = $iFilteredTotal;

    // No extra lookup tables required for pembaharuan

    $output = array(
        // "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );

    $no = 0;
    while ($aRow = mysqli_fetch_array($rResult)) {
        $no++;
        $row = array();
    $row[] = '<div class="text-center">' . $no . '</div>'; // NO
    $row[] = '<div><strong>' . htmlspecialchars($aRow['version']) . '</strong></div>'; // Version
    $row[] = '<div>' . htmlspecialchars($aRow['release_date']) . '</div>'; // Release date
    $row[] = '<div class="text-center">' . ($aRow['mandatory'] == 'Y' ? '<span class="badge badge-danger">Wajib</span>' : '<span class="badge badge-secondary">Opsional</span>') . '</div>';
        // Render pembaharuan and perbaikan combined into a single 'Deskripsi' column
        $format_list = function($txt) {
            $txt = trim($txt);
            if ($txt === '') return '';
            $lines = preg_split('/\r?\n/', $txt);
            $html = '<ul style="margin:0;padding-left:18px">';
            foreach ($lines as $ln) {
                $ln = trim($ln);
                if ($ln !== '') $html .= '<li>' . htmlspecialchars($ln, ENT_QUOTES) . '</li>';
            }
            $html .= '</ul>';
            return $html;
        };
        $pb_html = $format_list(isset($aRow['pembaharuan']) ? $aRow['pembaharuan'] : '');
        $pr_html = $format_list(isset($aRow['perbaikan']) ? $aRow['perbaikan'] : '');
        $desc_html = '';
        if ($pb_html !== '') {
            $desc_html .= '<div>' . $pb_html . '</div>';
        }
        if ($pr_html !== '') {
            $desc_html .= '<div style="margin-top:6px"><small class="text-muted"><strong>Perbaikan:</strong></small>' . $pr_html . '</div>';
        }
        if ($desc_html === '') $desc_html = '-';
        $row[] = $desc_html;
        $link = $aRow['download_link'] ? '<a href="' . htmlspecialchars($aRow['download_link']) . '" target="_blank">Unduh</a>' : '-';
        $row[] = '<div class="text-center">' . $link . '</div>';
        // include data attributes for pembaharuan/perbaikan so modal can populate full text
        $row[] = '<div class="text-center">
                    <a href="javascript:void(0)" class="table-action table-action-primary btn-update btn-tooltip" title="Edit" data-id="' . $aRow['id'] . '" data-version="' . htmlspecialchars($aRow['version'], ENT_QUOTES) . '" data-release="' . htmlspecialchars($aRow['release_date'], ENT_QUOTES) . '" data-mandatory="' . $aRow['mandatory'] . '" data-link="' . htmlspecialchars($aRow['download_link'], ENT_QUOTES) . '" data-pembaharuan="' . htmlspecialchars($aRow['pembaharuan'], ENT_QUOTES) . '" data-perbaikan="' . htmlspecialchars($aRow['perbaikan'], ENT_QUOTES) . '">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" title="Hapus" data-id="' . epm_encode($aRow['id']) . '" data-name="' . htmlspecialchars($aRow['version'], ENT_QUOTES) . '">
                        <i class="fas fa-trash"></i>
                    </a>
              </div>';
        $output['aaData'][] = $row;
    }
    echo json_encode($output);
}
