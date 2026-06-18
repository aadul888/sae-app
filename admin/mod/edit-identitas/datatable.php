<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  include('../../../library/function.php');

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
      berkas.validasi_berkas AS berkas_validasi
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

  // Hitung statistik status
  $statusStat = [
    'total' => 0,
    'disetujui' => 0,
    'ditolak' => 0,
    'berhasil' => 0,
    'proses' => 0
  ];
  $statQuery = $gaSql['link']->query("SELECT status_pengajuan, COUNT(*) as jumlah FROM perubahan GROUP BY status_pengajuan");
  if ($statQuery) {
    while ($row = $statQuery->fetch_assoc()) {
      $status = strtolower($row['status_pengajuan']);
      $statusStat['total'] += (int)$row['jumlah'];
      if ($status == 'disetujui') $statusStat['disetujui'] = (int)$row['jumlah'];
      elseif ($status == 'ditolak') $statusStat['ditolak'] = (int)$row['jumlah'];
      elseif ($status == 'berhasil dikirim') $statusStat['berhasil'] = (int)$row['jumlah'];
      elseif ($status == 'dalam proses') $statusStat['proses'] = (int)$row['jumlah'];
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
    // Compute display status according to berkas validation rules:
    // - If status_pengajuan is final (Disetujui or Ditolak), keep it.
    // - Otherwise, map berkas.validasi_berkas as follows:
    //     'valid'         => 'Dalam Proses'
    //     any other value => 'Berhasil Dikirim' (includes null / 'tidak valid' / 'perlu revisi')
    $display_status = $aRow['status_pengajuan'];
    $status_lower = strtolower(trim((string)$aRow['status_pengajuan']));
    if ($status_lower !== 'disetujui' && $status_lower !== 'ditolak') {
      $berkas_val = isset($aRow['berkas_validasi']) ? strtolower(trim((string)$aRow['berkas_validasi'])) : '';
      if ($berkas_val === 'valid') {
        $display_status = 'Dalam Proses';
      } else {
        $display_status = 'Berhasil Dikirim';
      }
    }

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
