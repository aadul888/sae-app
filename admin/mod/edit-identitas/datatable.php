<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  include('../../../library/function.php');

  function sae_norm_doc_status($status)
  {
    $s = strtolower(trim((string)$status));
    if (in_array($s, ['valid', 'disetujui', 'approved', 'ok'], true)) {
      return 'valid';
    }
    if (in_array($s, ['revisi', 'invalid', 'tidak_valid', 'tidak valid', 'ditolak', 'reject', 'rejected'], true)) {
      return 'tidak_valid';
    }
    return 'tidak_valid';
  }

  function sae_display_status_from_berkas($statusPengajuan, $row, $hasPerItem)
  {
    $finalStatus = strtolower(trim((string)$statusPengajuan));
    if ($finalStatus === 'disetujui' || $finalStatus === 'ditolak') {
      return $statusPengajuan;
    }

    // Status proses usulan mengikuti validasi dokumen wajib: KK dan Ijazah.
    $docFields = ['kk', 'ijazah'];
    $hasInvalid = false;

    foreach ($docFields as $field) {
      $filename = trim((string)($row[$field] ?? ''));
      if ($filename === '') {
        $hasInvalid = true;
        continue;
      }

      $rawStatus = $hasPerItem ? ($row[$field . '_valid'] ?? '') : ($row['berkas_validasi'] ?? '');
      $normalized = sae_norm_doc_status($rawStatus);
      if ($normalized !== 'valid') {
        $hasInvalid = true;
      }
    }

    if ($hasInvalid) {
      return 'Berhasil Dikirim';
    }
    return 'Dalam Proses';
  }

  $aColumns = [
    'perubahan.id',
    'perubahan.user_id',
    'user.nisn',
    'user.nama_lengkap',
    'kelas.nama_kelas',
    // removed 'perubahan.jenis_data' — column deleted from table
    'perubahan.keterangan',
    'perubahan.date_submitted',
    'perubahan.status_pengajuan',
    'perubahan.alasan_penolakan',
    'perubahan.date_processed',
    'perubahan.processed_by',
    // Semua field identitas
    'user.no_kk',
    'user.nik',
    'user.jenis_kelamin',
    'user.tempat_lahir',
    'user.tanggal_lahir',
    'user.agama',
    'user.status_keluarga',
    'user.anak_ke',
    'user.alamat',
    'user.rt',
    'user.rw',
    'user.desa',
    'user.kecamatan',
    'user.kodepos',
    'user.telp',
    'user.sekolah_asal',
    'user.diterima_dikelas',
    'user.diterima_tanggal',
    'user.email',
    // Semua field orangtua
    'user.nik_ayah',
    'user.nama_ayah',
    'user.pekerjaan_ayah',
    'user.nik_ibu',
    'user.nama_ibu',
    'user.pekerjaan_ibu',
    // Semua field wali
    'user.nama_wali',
    'user.alamat_wali',
    'user.telp_wali',
    'user.pekerjaan_wali',
  ];
  $sIndexColumn = "perubahan.id";
  $sTable = "perubahan";

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  $hasPerItemValidation = false;
  $checkColumns = $gaSql['link']->query("SHOW COLUMNS FROM berkas LIKE 'kk_valid'");
  if ($checkColumns && $checkColumns->num_rows > 0) {
    $hasPerItemValidation = true;
  }

  // LIMIT (support both legacy iDisplayStart/iDisplayLength and modern start/length)
  $sLimit = "";
  $start = null;
  $length = null;
  if (isset($_REQUEST['iDisplayStart'])) {
    $start = $_REQUEST['iDisplayStart'];
  } elseif (isset($_REQUEST['start'])) {
    $start = $_REQUEST['start'];
  }
  if (isset($_REQUEST['iDisplayLength'])) {
    $length = $_REQUEST['iDisplayLength'];
  } elseif (isset($_REQUEST['length'])) {
    $length = $_REQUEST['length'];
  }
  if ($start !== null && $length !== null && $length != '-1') {
    $sLimit = "LIMIT " . intval($start) . ", " . intval($length);
  }

  // ORDER
  $sOrder = "ORDER BY FIELD(perubahan.status_pengajuan, 'Dalam Proses', 'Berhasil Dikirim', 'Disetujui', 'Ditolak'), perubahan.date_processed DESC, perubahan.date_submitted ASC";

  // WHERE / Search (support legacy sSearch and modern search[value])
  $sWhere = "";
  $searchValue = '';
  if (isset($_REQUEST['sSearch'])) {
    $searchValue = $_REQUEST['sSearch'];
  } elseif (isset($_REQUEST['search']) && is_array($_REQUEST['search']) && isset($_REQUEST['search']['value'])) {
    $searchValue = $_REQUEST['search']['value'];
  }
  if ($searchValue !== '') {
    $safeSearch = mysqli_real_escape_string($gaSql['link'], $searchValue);
    $sWhere = "WHERE (";
    for ($i = 0; $i < count($aColumns); $i++) {
      $sWhere .= $aColumns[$i] . " LIKE '%" . $safeSearch . "%' OR ";
    }
    $sWhere = substr_replace($sWhere, "", -3);
    $sWhere .= ')';
  }


  // QUERY
  $perItemSelect = '';
  if ($hasPerItemValidation) {
    $perItemSelect = ",
      berkas.kk_valid,
      berkas.ijazah_valid,
      berkas.akte_valid,
      berkas.kip_valid,
      berkas.kks_valid,
      berkas.kis_valid";
  }

  $sQuery = "
    SELECT SQL_CALC_FOUND_ROWS 
      perubahan.id,
      perubahan.user_id,
      user.nisn,
      user.nama_lengkap,
      kelas.nama_kelas,
      -- perubahan.jenis_data removed (column deleted)
      perubahan.keterangan,
      perubahan.date_submitted,
      perubahan.status_pengajuan,
      perubahan.alasan_penolakan,
      perubahan.date_processed,
      perubahan.processed_by,
      berkas.validasi_berkas AS berkas_validasi,
      berkas.kk,
      berkas.akte,
      berkas.ijazah,
      berkas.kip,
      berkas.kks,
      berkas.kis
      $perItemSelect
    FROM perubahan 
    LEFT JOIN user ON perubahan.user_id = user.user_id
    LEFT JOIN kelas ON user.kelas = kelas.kelas_id
    LEFT JOIN berkas ON perubahan.user_id = berkas.user_id
    $sWhere
    $sOrder
    $sLimit
    ";

  $rResult = $gaSql['link']->query($sQuery);

  // TOTALS
  $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
  $iTotal = $gaSql['link']->query("SELECT COUNT($sIndexColumn) FROM $sTable")->fetch_row()[0];

  // Hitung statistik status berbasis validasi berkas total
  $statusStat = [
    'total' => 0,
    'disetujui' => 0,
    'ditolak' => 0,
    'berhasil' => 0,
    'proses' => 0
  ];
  $statusSelectExtra = '';
  if ($hasPerItemValidation) {
    $statusSelectExtra = ', berkas.kk_valid, berkas.ijazah_valid, berkas.akte_valid, berkas.kip_valid, berkas.kks_valid, berkas.kis_valid';
  }
  $statQuery = $gaSql['link']->query("SELECT perubahan.status_pengajuan, berkas.validasi_berkas, berkas.kk, berkas.akte, berkas.ijazah, berkas.kip, berkas.kks, berkas.kis $statusSelectExtra FROM perubahan LEFT JOIN berkas ON perubahan.user_id = berkas.user_id");
  if ($statQuery) {
    while ($row = $statQuery->fetch_assoc()) {
      $display = sae_display_status_from_berkas($row['status_pengajuan'], $row, $hasPerItemValidation);
      $status = strtolower(trim((string)$display));
      $statusStat['total']++;
      if ($status === 'disetujui') $statusStat['disetujui']++;
      elseif ($status === 'ditolak') $statusStat['ditolak']++;
      elseif ($status === 'berhasil dikirim') $statusStat['berhasil']++;
      elseif ($status === 'dalam proses') $statusStat['proses']++;
    }
  }

  $output = array(
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => array(),
    "statusStat" => $statusStat
  );

  $no = (isset($start) && is_numeric($start)) ? intval($start) + 1 : 1;
  while ($aRow = $rResult->fetch_assoc()) {
    $display_status = sae_display_status_from_berkas($aRow['status_pengajuan'], $aRow, $hasPerItemValidation);

    $badge = ($display_status == 'Disetujui') ? 'success' : (($display_status == 'Ditolak') ? 'danger' : (($display_status == 'Dalam Proses') ? 'primary' : 'warning'));

    $catatanButton = '';
    $editCatatanButton = '';
    if ($aRow['status_pengajuan'] == 'Ditolak' && !empty($aRow['alasan_penolakan'])) {
      $catatanButton = '
        <a href="javascript:void(0)" class="btn-view-catatan btn-tooltip" data-id="' . $aRow['id'] . '" data-catatan="' . htmlspecialchars($aRow['alasan_penolakan']) . '" title="Lihat Catatan">
          <i class="fas fa-comment text-warning"></i>
        </a>';
      $editCatatanButton = '
        <a href="javascript:void(0)" class="btn-edit-catatan btn-tooltip" data-id="' . $aRow['id'] . '" data-catatan="' . htmlspecialchars($aRow['alasan_penolakan']) . '" title="Edit Catatan">
          <i class="fas fa-edit text-primary"></i>
        </a>';
    }

    $row = array();
    $row[] = '<div class="text-center">' . $no++ . '</div>';
    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['nisn']) . '</div>';
    $row[] = '<b>' . '<div class="text-center">' . htmlspecialchars($aRow['nama_lengkap']) . '</div>' . '</b>';
    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['nama_kelas']) . '</div>';
    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['date_submitted']) . '</div>';
    $row[] = '<div class="text-center">' . htmlspecialchars($aRow['date_processed']) . '</div>';
    // Kolom catatan/berkas: jika jenis_data identitas/orangtua/wali dan ada file di keterangan, tampilkan tombol preview
    $catatan_html = '<div class="text-center" style="word-break:break-word;max-width:180px;white-space:pre-line;">' . htmlspecialchars($aRow['alasan_penolakan']) . '</div>';
    // Cek jika ada file di keterangan (misal upload dokumen perubahan)
    $keterangan = $aRow['keterangan'];
    // 'jenis_data' column removed from 'perubahan' table; do not reference it
    $preview_btn = '';
    if ($keterangan) {
      $keterangan_arr = @json_decode($keterangan, true);
      if (is_array($keterangan_arr)) {
        // Cek key dokumen/file (misal: dokumen, file, berkas, atau array dengan ekstensi gambar/pdf)
        foreach ($keterangan_arr as $key => $val) {
          if (is_string($val) && preg_match('/\.(jpg|jpeg|png|gif|bmp|webp|pdf)$/i', $val)) {
            $filename = htmlspecialchars($val);
            $preview_btn .= '<button type="button" class="btn btn-sm btn-success btn-lihat-berkas ml-1" data-filename="' . $filename . '" title="Lihat Berkas"><i class="fas fa-file-alt"></i></button>';
          }
        }
      }
    }
    if ($preview_btn) {
      $catatan_html .= '<div class="mt-1">' . $preview_btn . '</div>';
    }
    $row[] = $catatan_html;
    $row[] = '<div class="text-center">' . '<span class="badge badge-' . $badge . '">' . htmlspecialchars($display_status) . '</span>';
    $row[] =
      '<div class="text-center">'
      . '<a href="javascript:void(0)" class="btn-view-detail btn-tooltip mx-1" data-id="' . $aRow['id'] . '" title="Lihat Detail">'
      . '<i class="fas fa-search text-info"></i>'
      . '</a>'
      . (!empty($catatanButton) ? '<span class="mx-1">' . $catatanButton . '</span>' : '')
      . (!empty($editCatatanButton) ? '<span class="mx-1">' . $editCatatanButton . '</span>' : '')
      . '<a href="javascript:void(0)" class="btn-delete btn-tooltip mx-1" data-id="' . $aRow['id'] . '" title="Hapus">'
      . '<i class="fas fa-trash text-danger"></i>'
      . '</a>'
      . '</div>';

    $output['aaData'][] = $row;
  }

  echo json_encode($output);
}
