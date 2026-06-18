<?php
session_start();
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

  $aColumns = [
    'berkas.berkas_id',
    'user.nisn',
    'user.nama_lengkap',
    'kelas.nama_kelas',
    'berkas.kk',
    'berkas.ijazah',
    'berkas.akte',
    'berkas.kip',
    'berkas.kks',
    'berkas.kis', // tambahkan kolom KIS
    'berkas.berkas_id' // kolom aksi
  ];
  $sIndexColumn = "berkas.berkas_id";
  $sTable = "berkas";

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  // Support request parameters from POST (DataTables default) or GET
  $req = $_REQUEST;
  $sLimit = "";
  $start = isset($req['iDisplayStart']) ? intval($req['iDisplayStart']) : (isset($req['start']) ? intval($req['start']) : 0);
  $length = isset($req['iDisplayLength']) ? intval($req['iDisplayLength']) : (isset($req['length']) ? intval($req['length']) : 25);
  if ($length != -1) {
    $sLimit = "LIMIT " . mysqli_real_escape_string($gaSql['link'], $start) . ", " .
      mysqli_real_escape_string($gaSql['link'], $length);
  }

  $sOrder = "ORDER BY
    CASE
      WHEN (berkas.validasi_berkas = '' OR berkas.validasi_berkas IS NULL) THEN 0
      WHEN (berkas.validasi_berkas = 'revisi') THEN 1
      WHEN (berkas.validasi_berkas = 'tidak_valid') THEN 2
      WHEN (berkas.validasi_berkas = 'valid') THEN 3
      ELSE 4
    END,
    COALESCE(berkas.updated_at, berkas.created_at) ASC,
    berkas.berkas_id ASC";

  // Support DataTables v1.10+ (search[value]) and legacy (sSearch)
  $searchValue = '';
  if (isset($req['search']) && is_array($req['search']) && isset($req['search']['value'])) {
    $searchValue = $req['search']['value'];
  } elseif (isset($req['sSearch']) && $req['sSearch'] !== '') {
    $searchValue = $req['sSearch'];
  }

  $sWhere = "";
  // Filter by user_id jika ada di request (GET/POST)
  if (isset($req['user_id']) && $req['user_id'] != '') {
    $user_id = mysqli_real_escape_string($gaSql['link'], $req['user_id']);
    $sWhere = "WHERE user.user_id='" . $user_id . "'";
  }
  // Jika tidak ada user_id, filter by search
  elseif (!empty($searchValue)) {
    $sWhere = "WHERE (";
    foreach ($aColumns as $col) {
      $sWhere .= "$col LIKE '%" . mysqli_real_escape_string($gaSql['link'], $searchValue) . "%' OR ";
    }
    $sWhere = substr($sWhere, 0, -4) . ")";
  }

  $kelas_id = '';
  $is_wali = false;
  if (isset($current_user) && (isset($current_user['ptk_id']) || isset($current_user['admin_id']))) {
    $ptk_id = isset($current_user['ptk_id']) ? $current_user['ptk_id'] : '';
    $admin_id = isset($current_user['admin_id']) ? $current_user['admin_id'] : '';

    if (!empty($ptk_id)) {
      $q_wali = $gaSql['link']->query("SELECT kelas_id FROM kelas WHERE wali_kelas_ptk_id='" . mysqli_real_escape_string($gaSql['link'], $ptk_id) . "' LIMIT 1");
      if ($q_wali && $r_w = $q_wali->fetch_assoc()) {
        $kelas_id = $r_w['kelas_id'];
        $is_wali = true; // current user is wali kelas (PTK)
      }
    }

    if ($kelas_id === '' && !empty($admin_id)) {
      $q_wali2 = $gaSql['link']->query("SELECT kelas_id FROM kelas WHERE wali_kelas_admin_id='" . mysqli_real_escape_string($gaSql['link'], $admin_id) . "' LIMIT 1");
      if ($q_wali2 && $r2 = $q_wali2->fetch_assoc()) {
        $kelas_id = $r2['kelas_id'];
        $is_wali = true; // current user is wali kelas (admin user assigned as wali)
      }
    }

    if ($kelas_id != '') {
      if ($sWhere == "") {
        $sWhere = "WHERE user.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
      } else {
        $sWhere .= " AND user.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
      }
    }
    // If no kelas found, do not block results here; explicit filter handled next.
  }

  // Explicit client-side filter: use provided kelas parameter (POST/GET has priority)
  if ((isset($req['kelas']) && $req['kelas'] != '')) {
    $req_kelas = mysqli_real_escape_string($gaSql['link'], $req['kelas']);
    $kelas_id = $req_kelas;
  }

  // Apply kelas filter if present (ensure we append correctly to existing $sWhere)
  if ($kelas_id != '') {
    if (strpos($sWhere, "user.kelas='") !== false) {
      // already present (from detection). If explicit override differs, append an AND clause.
      if (strpos($sWhere, "user.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'") === false) {
        $sWhere .= " AND user.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
      }
    } else {
      if ($sWhere == "") {
        $sWhere = "WHERE user.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
      } else {
        $sWhere .= " AND user.kelas='" . mysqli_real_escape_string($gaSql['link'], $kelas_id) . "'";
      }
    }
  }

  $sQuery = "
    SELECT SQL_CALC_FOUND_ROWS berkas.*, user.nisn, user.nama_lengkap, kelas.nama_kelas, admin.fullname AS validasi_admin
    FROM berkas
    LEFT JOIN user ON berkas.user_id = user.user_id
    LEFT JOIN kelas ON user.kelas = kelas.kelas_id
    LEFT JOIN admin ON berkas.validasi_by = admin.admin_id
    $sWhere
    $sOrder
    $sLimit
  ";

  $rResult = $gaSql['link']->query($sQuery);
  if (!$rResult) {
    $output = [
      "iTotalRecords" => 0,
      "iTotalDisplayRecords" => 0,
      "aaData" => []
    ];
    echo json_encode($output);
    exit;
  }
  $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
  $iTotal = $gaSql['link']->query("SELECT COUNT($sIndexColumn) FROM $sTable")->fetch_row()[0];

  // Hitung statistik status validasi berkas sesuai filter (kelas, search, user_id)
  $statusStat = [
    'total' => 0,
    'valid' => 0,
    'tidak_valid' => 0,
    'revisi' => 0,
    'belum' => 0
  ];
  // Query statistik harus join dan where sama dengan query utama agar filter kelas/search/user_id diterapkan
  $statWhere = $sWhere;
  $statJoin = "LEFT JOIN user ON berkas.user_id = user.user_id LEFT JOIN kelas ON user.kelas = kelas.kelas_id";
  $statQ = $gaSql['link']->query(
    "SELECT TRIM(LOWER(COALESCE(berkas.validasi_berkas, ''))) as status, COUNT(DISTINCT berkas.user_id) as jumlah
     FROM berkas $statJoin $statWhere GROUP BY TRIM(LOWER(COALESCE(berkas.validasi_berkas, '')));"
  );
  if ($statQ) {
    $total = 0;
    while ($r = $statQ->fetch_assoc()) {
      $s = $r['status'];
      $cnt = (int)$r['jumlah'];
      $total += $cnt;
      if ($s === 'valid') $statusStat['valid'] = $cnt;
      elseif ($s === 'tidak_valid') $statusStat['tidak_valid'] = $cnt;
      elseif ($s === 'revisi') $statusStat['revisi'] = $cnt;
      else $statusStat['belum'] = $cnt; // empty/null/other
    }
    $statusStat['total'] = $total;
  }

  // Prepare output supporting both legacy and DataTables 1.10+ response keys
  $output = [
    "draw" => isset($req['draw']) ? intval($req['draw']) : 0,
    "recordsTotal" => intval($iTotal),
    "recordsFiltered" => intval($iFilteredTotal),
    // legacy keys for backward compatibility
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => [],
    // statistik tambahan untuk UI
    "statusStat" => $statusStat,
    // flag untuk client: apakah user sekarang adalah wali kelas
    "isWaliKelas" => $is_wali ? true : false
  ];

  $no = $start + 1;
  $folder_path = '../content/berkas/';

  while ($row = $rResult->fetch_assoc()) {
    $data = [];
    $data[] = '<div class="text-center">' . $no++ . '</div>';
    $data[] = htmlspecialchars($row['nisn']);
    $data[] = '<b>' . htmlspecialchars($row['nama_lengkap']) . '</b>';
    $data[] = htmlspecialchars($row['nama_kelas']);
    // Kolom dokumen - tampilkan ikon checklist/X saja
    $dokumen = [];
    foreach (['kk', 'ijazah', 'akte', 'kip', 'kks', 'kis'] as $field) { // tambahkan 'kis'
      if (!empty($row[$field])) {
        $data[] = '<div class="text-center">'
          . '<i class="fas fa-check-circle text-success" style="font-size:18px;" title="Sudah upload"></i>'
          . '</div>';
        $dokumen[$field] = true;
      } else {
        $data[] = '<div class="text-center">'
          . '<i class="fas fa-times-circle text-danger" style="font-size:18px;" title="Belum upload"></i>'
          . '</div>';
        $dokumen[$field] = false;
      }
    }
    // Kolom status validasi sebagai badge
    $current_validasi = $row['validasi_berkas'] ?? '';
    $badge = '';
    switch ($current_validasi) {
      case 'valid':
        $badge = '<span class="badge badge-success">Valid</span>';
        break;
      case 'tidak_valid':
        $badge = '<span class="badge badge-danger">Tidak Valid</span>';
        break;
      case 'revisi':
        $badge = '<span class="badge badge-warning text-dark">Perlu Revisi</span>';
        break;
      default:
        $badge = '<span class="badge badge-secondary">Belum Divalidasi</span>';
    }

    // Tampilkan badge + info tanggal aksi terakhir dan admin yang memproses
    $admin_name = isset($row['validasi_admin']) && $row['validasi_admin'] ? $row['validasi_admin'] : '';
    $last_action_raw = $row['updated_at'] ?? '';
    $last_action = '';
    if (!empty($last_action_raw) && $last_action_raw !== '0000-00-00 00:00:00') {

      try {
        $tzLocal = date_default_timezone_get() ?: 'UTC';
        $now = time();

        // Interpretasi sebagai UTC
        $dtUtc = new DateTime($last_action_raw, new DateTimeZone('UTC'));
        $tsUtc = $dtUtc->getTimestamp();

        // Interpretasi sebagai lokal
        $dtLocal = new DateTime($last_action_raw, new DateTimeZone($tzLocal));
        $tsLocal = $dtLocal->getTimestamp();

        $diffUtc = abs($now - $tsUtc);
        $diffLocal = abs($now - $tsLocal);

        if ($diffUtc + 60 < $diffLocal) {
          // Jika interpretasi UTC jauh lebih masuk akal, konversi ke timezone lokal
          $dtUtc->setTimezone(new DateTimeZone($tzLocal));
          $dt = $dtUtc;
        } else {
          // Gunakan interpretasi lokal
          $dt = $dtLocal;
        }

        $last_action = tgl_ind($dt->format('Y-m-d H:i:s')) . ' ' . $dt->format('H:i');
      } catch (Exception $e) {
        // Fallback ke cara lama jika ada error parsing
        $last_action = tgl_ind($last_action_raw) . ' ' . date('H:i', strtotime($last_action_raw));
      }
    }

    $status_html = $badge;
    if ($last_action || $admin_name) {
      $status_html .= '<div class="small text-muted mt-1">';
      if ($last_action) {
        $status_html .= htmlspecialchars($last_action);
      }
      if ($admin_name) {
        $status_html .= (!empty($last_action) ? ' &middot; ' : '') . 'oleh <strong>' . htmlspecialchars($admin_name) . '</strong>';
      }
      $status_html .= '</div>';
    }
    $data[] = $status_html;
    // Kolom aksi: tombol lihat semua berkas dan hapus
    $dokumen_tersedia = [];
    foreach (["kk", "ijazah", "akte", "kip", "kks", "kis"] as $field) { // tambahkan 'kis'
      if (!empty($row[$field])) {
        $dokumen_tersedia[] = $field;
      }
    }
    $aksi = '<div class="text-center">';
    // Tombol lihat semua berkas (jika ada berkas yang diupload)
    if (!empty($dokumen_tersedia)) {
      $berkas_data = [];
      foreach ($dokumen_tersedia as $field) {
        $berkas_data[$field] = $row[$field];
      }
      // include existing keterangan so modal can prefill it
      $berkas_data['keterangan'] = $row['keterangan'] ?? '';
      $aksi .= '<a href="javascript:void(0)" class="table-action table-action-info btn-lihat-semua-berkas btn-tooltip" data-berkas="' . htmlspecialchars(json_encode($berkas_data)) . '" data-nama="' . htmlspecialchars($row['nama_lengkap']) . '" data-user="' . htmlspecialchars($row['user_id']) . '" data-validasi="' . htmlspecialchars($row['validasi_berkas']) . '" data-toggle="tooltip" title="Lihat Semua Berkas"><i class="fas fa-eye"></i></a>';
    }
    // Tombol hapus (hanya untuk non-wali)
    if (!$is_wali) {
      $aksi .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-hapus-berkas" data-name="' . strip_tags($row['nama_lengkap']) . '" data-id="' . strip_tags($row['user_id']) . '" data-dokumen="\"all\"" data-toggle="tooltip" title="Hapus Semua Berkas"><i class="fas fa-trash"></i></a>';
    }
    $aksi .= '</div>';
    $data[] = $aksi;
    $output['aaData'][] = $data;
  }

  // Also provide `data` key for DataTables 1.10+ compatibility
  $output['data'] = $output['aaData'];

  echo json_encode($output);
}
