<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../library/config.php';
require_once'../../../library/function.php';
require_once'../../login/user.php';

$modul_id = 19;
include __DIR__ . '/../check_role.php';

function check_access($type)
{
  global $data_role;
  if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
    echo 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.';
    exit;
  }
}

if (!function_exists('lap_siswa_finalize_absensi_lintas_hari')) {
  function lap_siswa_finalize_absensi_lintas_hari($connection)
  {
    $today = date('Y-m-d');
    $hari_map = [
      'Sunday' => 'Minggu',
      'Monday' => 'Senin',
      'Tuesday' => 'Selasa',
      'Wednesday' => 'Rabu',
      'Thursday' => 'Kamis',
      'Friday' => 'Jumat',
      'Saturday' => 'Sabtu'
    ];

    $stmt = $connection->prepare("SELECT DISTINCT tanggal FROM absensi WHERE tanggal < ? AND jam_masuk IS NOT NULL AND (jam_pulang IS NULL OR jam_pulang='' OR jam_pulang='00:00:00') AND status_masuk IN ('Tepat Waktu','Terlambat')");
    if (!$stmt) {
      return;
    }
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($result && ($row = $result->fetch_assoc())) {
      $tanggal = $row['tanggal'];
      $hari_en = date('l', strtotime($tanggal));
      $hari = isset($hari_map[$hari_en]) ? $hari_map[$hari_en] : $hari_en;
      $jam_pulang = '23:59:00';

      $jadwal_stmt = $connection->prepare("SELECT waktu_selesai FROM jadwal WHERE hari=? AND status='Y' LIMIT 1");
      if ($jadwal_stmt) {
        $jadwal_stmt->bind_param('s', $hari);
        $jadwal_stmt->execute();
        $jadwal_result = $jadwal_stmt->get_result();
        if ($jadwal_result && $jadwal_result->num_rows > 0) {
          $jadwal_row = $jadwal_result->fetch_assoc();
          if (!empty($jadwal_row['waktu_selesai'])) {
            $jam_pulang = $jadwal_row['waktu_selesai'];
          }
        }
        $jadwal_stmt->close();
      }

      $update_stmt = $connection->prepare("UPDATE absensi SET jam_pulang=?, status_pulang='Pulang Cepat', kehadiran=CASE WHEN kehadiran IS NULL OR kehadiran='' OR LOWER(kehadiran)='hadir' THEN 'Lupa absen pulang' ELSE kehadiran END, updated_at=NOW() WHERE tanggal=? AND jam_masuk IS NOT NULL AND (jam_pulang IS NULL OR jam_pulang='' OR jam_pulang='00:00:00') AND status_masuk IN ('Tepat Waktu','Terlambat')");
      if ($update_stmt) {
        $update_stmt->bind_param('ss', $jam_pulang, $tanggal);
        $update_stmt->execute();
        $update_stmt->close();
      }
    }
    $stmt->close();
  }
}

lap_siswa_finalize_absensi_lintas_hari($connection);

switch (@$_GET['action']){
case 'dropdown':
  check_access('lihat');
  if (empty($_POST['kelas'])) {
    $kelas = '';
  } else {
    $kelas = anti_injection($_POST['kelas']);
  }

$query_siswa = "SELECT user_id,nama_lengkap FROM user WHERE kelas='$kelas'";
$result_siswa = $connection->query($query_siswa);
if($result_siswa->num_rows > 0) {
  while($data_siswa = $result_siswa->fetch_assoc()){
    echo'<option value="'.$data_siswa['user_id'].'">'.strip_tags($data_siswa['nama_lengkap']).'</option>';
  }
}else{
  echo'<option value="">Data tidak ditemukan</option>';
}


break;
case 'filtering':
  check_access('lihat');
  $warna      = '';
  $background = '';
  $jumlah_libur = 0;
  $jumlah_libur_nasional = 0;
  $jumlah_izin = 0;
  
  if(isset($_POST['bulan']) OR isset($_POST['tahun'])){
    $bulan    = anti_injection($_POST['bulan']);
    $tahun    = anti_injection($_POST['tahun']);
  } else{
    $bulan    = date ("m");
    $tahun    = date("Y");
  }
  
  /** Filter Siswa */
  if(isset($_POST['siswa'])){
    $siswa  = anti_injection($_POST['siswa']);
  } else {
    $siswa = '';
  }
  
  /** Pemetaan hari (English -> Indonesia) */
  $hari_map = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
  ];
  
  $hari       = date("d");
  $jumlahhari = date("t", mktime(0,0,0,$bulan,1,$tahun)); // bulan dengan aman
  echo'
  <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
    <thead class="thead-light">
      <tr>
        <th class="text-center" width="10">No</th>
        <th>Tanggal</th>
        <th>Siswa</th>
        <th>Kelas</th>
        <th class="text-center">Foto Masuk</th>
        <th>Masuk</th>
        <th class="text-center">Foto Pulang</th>
        <th>Pulang</th>
        <th class="text-center">Status</th>
      </tr>
    </thead>
  <tbody>';
  
  for ($d=1;$d<=$jumlahhari;$d++) {
    // format tanggal aman (leading zero)
    $tanggal  = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
    $hari_inggris = date('l', strtotime($tanggal));
    $hari_ind = isset($hari_map[$hari_inggris]) ? $hari_map[$hari_inggris] : $hari_inggris;
  
    // Cek hari libur nasional
    $is_libur = false;
    $libur_ket = '';
    $query_libur_nasional = "SELECT * FROM hari_libur WHERE tanggal_mulai <= '$tanggal' AND tanggal_selesai >= '$tanggal' LIMIT 1";
    $result_libur_nasional = $connection->query($query_libur_nasional);
    if($result_libur_nasional && $result_libur_nasional->num_rows > 0){
      $is_libur = true;
      $data_libur = $result_libur_nasional->fetch_assoc();
      $libur_ket = trim($data_libur['nama_libur'] . ( !empty($data_libur['keterangan']) ? ' ('.$data_libur['keterangan'].')' : ''));
      $jumlah_libur_nasional++;
    }
  
    // Cek izin siswa (jalankan query)
    $is_izin = false;
    $izin_ket = '';
    if(!empty($siswa)){
      $query_izin = "SELECT * FROM izin WHERE user_id='$siswa' AND status_izin IN ('Disetujui','disetujui') AND tanggal_mulai <= '$tanggal' AND tanggal_selesai >= '$tanggal' LIMIT 1";
      $result_izin = $connection->query($query_izin);
      if($result_izin && $result_izin->num_rows > 0){
        $is_izin = true;
        $data_izin = $result_izin->fetch_assoc();
        $izin_ket = !empty($data_izin['keterangan']) ? $data_izin['keterangan'] : 'Izin';
        $jumlah_izin++;
      }
    }
  
    // Cek jadwal hari kerja berdasarkan status di tabel jadwal (hari dalam bahasa Indonesia)
    $query_jadwal = "SELECT * FROM jadwal WHERE hari='". $connection->real_escape_string($hari_ind) ."' AND status='Y' LIMIT 1";
    $result_jadwal = $connection->query($query_jadwal);
  
    $is_hari_kerja = false;
    if ($result_jadwal && $result_jadwal->num_rows > 0) {
      $is_hari_kerja = true;
    }
  
    // Penentuan status dan styling berdasarkan prioritas
    if($is_libur){
      $warna = '#ffffff';
      $background = '#dc3545';
      $status = '<span class="badge" style="background:#dc3545;color:#fff;">'.$libur_ket.'</span>';
    }
    elseif($is_izin){
      $warna = '#000';
      $background = '#ffc107';
      $status = '<span class="badge" style="background:#ffc107;color:#000;">'.$izin_ket.'</span>';
    }
    elseif(!$is_hari_kerja){
      $warna = '#fff';
      $background = '#dc3545';
      $status = '<span class="badge" style="background:#dc3545;color:#fff;">Libur (Tidak ada jadwal)</span>';
      $jumlah_libur++;
    }else{
      // Hari kerja normal (status Y di tabel jadwal)
      $warna = '#111111';
      $background = 'transparent';
      $status = '-';
    }
  
    // Simpan styling untuk hari ini
    $hari_background = $background;
    $hari_warna = $warna;
    $hari_status = $status;
  
    if(isset($_POST['bulan']) OR isset($_POST['tahun']) OR isset($_POST['siswa'])){
      // safety re-read (tetap gunakan sanitized values)
      $siswa  = anti_injection($_POST['siswa']);
      $bulan    = anti_injection($_POST['bulan']);
      $tahun    = anti_injection($_POST['tahun']);
      $filter = "WHERE absensi.tanggal='$tanggal' AND MONTH(absensi.tanggal)='$bulan' AND YEAR(absensi.tanggal)='$tahun' AND absensi.user_id='$siswa'";
  
      $query_absensi = "SELECT absensi.*, user.nama_lengkap, user.kelas, user.nisn, k.nama_kelas FROM absensi
        INNER JOIN user ON absensi.user_id = user.user_id 
        LEFT JOIN kelas k ON user.kelas = k.kelas_id $filter LIMIT 1";
      $result_absensi = $connection->query($query_absensi);
      if($result_absensi && $result_absensi->num_rows > 0){
        $row = $result_absensi->fetch_assoc();
  
        // Foto masuk
        if (!empty($row['foto_masuk']) && file_exists('../../../content/capture/'.$row['foto_masuk'])) {
          $foto_masuk = '<a href="../content/capture/'.htmlspecialchars($row['foto_masuk']).'" class="open-popup-link"><img src="../content/capture/'.htmlspecialchars($row['foto_masuk']).'" class="rounded" height="16" style="width:auto;max-width:40px;"></a>';
        } else {
          $foto_masuk = '<img src="../content/thumbnail.jpg" class="rounded" height="16" style="width:auto;max-width:40px;">';
        }
        // Foto pulang
        if (!empty($row['foto_pulang']) && file_exists('../../../content/capture/'.$row['foto_pulang'])) {
          $foto_pulang = '<a href="../content/capture/'.htmlspecialchars($row['foto_pulang']).'" class="open-popup-link"><img src="../content/capture/'.htmlspecialchars($row['foto_pulang']).'" class="rounded" height="16" style="width:auto;max-width:40px;"></a>';
        } else {
          $foto_pulang = '<img src="../content/thumbnail.jpg" class="rounded" height="16" style="width:auto;max-width:40px;">';
        }
  
        // Status masuk
        $status_masuk = '-';
        $badge_color_masuk = '#adb5bd';
        if (!empty($row['status_masuk'])) {
          $status_masuk_txt = strtoupper($row['status_masuk']);
          if ($status_masuk_txt == 'TEPAT WAKTU') $badge_color_masuk = '#51cf66';
          elseif ($status_masuk_txt == 'TELAT' || $status_masuk_txt == 'TERLAMBAT') $badge_color_masuk = '#ffd43b';
          elseif ($status_masuk_txt == 'IZIN' || $status_masuk_txt == 'LIBUR') $badge_color_masuk = '#fa5252';
          elseif ($status_masuk_txt == 'PULANG CEPAT') $badge_color_masuk = '#ff922b';
          $status_masuk = '<span class="badge" style="background:'.$badge_color_masuk.';color:#222;">'.$status_masuk_txt.'</span>';
        }
        // Status pulang
        $status_pulang = '-';
        $badge_color_pulang = '#adb5bd';
        if (!empty($row['status_pulang'])) {
          $status_pulang_txt = strtoupper($row['status_pulang']);
          if ($status_pulang_txt == 'TEPAT WAKTU') $badge_color_pulang = '#51cf66';
          elseif ($status_pulang_txt == 'PULANG CEPAT') $badge_color_pulang = '#ff922b';
          elseif ($status_pulang_txt == 'IZIN' || $status_pulang_txt == 'LIBUR') $badge_color_pulang = '#fa5252';
          elseif ($status_pulang_txt == 'TELAT' || $status_pulang_txt == 'TERLAMBAT') $badge_color_pulang = '#ffd43b';
          $status_pulang = '<span class="badge" style="background:'.$badge_color_pulang.';color:#222;">'.$status_pulang_txt.'</span>';
        }
  
        // Status kehadiran
        $status_kehadiran = '<span class="badge badge-info">'.htmlspecialchars($row['kehadiran']).'</span>';
  
        // Untuk hari kerja normal, jangan beri background merah
        if($is_hari_kerja && !$is_libur && !$is_izin){
          echo '<tr>';
        } else {
          echo '<tr style="background:'.$hari_background.';color:'.$hari_warna.'">';
        }
        echo '<td>'.$d.'</td>';
        echo '<td>'.tanggal_ind($row['tanggal']).'</td>';
        echo '<td>'.$row['nama_lengkap'].'</td>';
        echo '<td>'.(!empty($row['nama_kelas']) ? $row['nama_kelas'] : $row['kelas']).'</td>';
        echo '<td class="text-center">'.$foto_masuk.'</td>';
        echo '<td>'.htmlspecialchars($row['jam_masuk']).' '.$status_masuk.'</td>';
        echo '<td class="text-center">'.$foto_pulang.'</td>';
        echo '<td>'.htmlspecialchars($row['jam_pulang']).' '.$status_pulang.'</td>';
        echo '<td class="text-center">'.$status_kehadiran.'</td>';
        echo '</tr>';
      } else {
        echo '<tr style="background:'.$hari_background.';color:'.$hari_warna.'">';
        echo '<td>'.$d.'</td>';
        echo '<td>'.tanggal_ind($tanggal).'</td>';
        echo '<td>-</td>';
        echo '<td>-</td>';
        echo '<td class="text-center"><img src="../content/thumbnail.jpg" class="rounded" height="16" style="width:auto;max-width:40px;"></td>';
        echo '<td>-</td>';
        echo '<td class="text-center"><img src="../content/thumbnail.jpg" class="rounded" height="16" style="width:auto;max-width:40px;"></td>';
        echo '<td>-</td>';
        echo '<td class="text-center">'.$hari_status.'</td>';
        echo '</tr>';
      }
    }else{
      echo'Silahkan Pilih Filter';
    }
  
  }
  echo'
  </tbody>
  </table>';
  
  // ======================================================
  // Statistik
  // ======================================================
  
  // Filter untuk query statistik
  $filter_jumlah = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun' AND user_id='$siswa'";
  
  // Hitung Hadir (Tepat Waktu + Terlambat)
  $query_hadir = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk IN ('Tepat Waktu', 'Terlambat','TELAT','TERLAMBAT')";
  $result_hadir = $connection->query($query_hadir);
  $hadir_count = $result_hadir ? (int)$result_hadir->fetch_assoc()['jumlah'] : 0;
  
  // Hitung Terlambat
  $query_telat = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk IN ('Terlambat','TELAT','TERLAMBAT')";
  $result_telat = $connection->query($query_telat);
  $terlambat_count = $result_telat ? (int)$result_telat->fetch_assoc()['jumlah'] : 0;
  
  // Hitung Izin (dari tabel absensi)
  $query_izin_absensi = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk IN ('Izin','IZIN')";
  $result_izin_absensi = $connection->query($query_izin_absensi);
  $izin_absensi_count = $result_izin_absensi ? (int)$result_izin_absensi->fetch_assoc()['jumlah'] : 0;
  
  // Hitung total hari kerja berdasarkan jadwal di bulan tersebut
  $total_hari_kerja = 0;
  for ($d=1;$d<=$jumlahhari;$d++) {
    $tanggal_check = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
    $hari_check_eng = date('l', strtotime($tanggal_check));
    $hari_check = isset($hari_map[$hari_check_eng]) ? $hari_map[$hari_check_eng] : $hari_check_eng;
  
    // Cek apakah hari libur nasional
    $query_libur_check = "SELECT 1 FROM hari_libur WHERE tanggal_mulai <= '$tanggal_check' AND tanggal_selesai >= '$tanggal_check' LIMIT 1";
    $result_libur_check = $connection->query($query_libur_check);
    $is_libur_nasional = ($result_libur_check && $result_libur_check->num_rows > 0);
  
    // Cek jadwal hari kerja berdasarkan status (hari bahasa Indonesia)
    $query_jadwal_check = "SELECT 1 FROM jadwal WHERE hari='". $connection->real_escape_string($hari_check) ."' AND status='Y' LIMIT 1";
    $result_jadwal_check = $connection->query($query_jadwal_check);
    $is_hari_kerja = ($result_jadwal_check && $result_jadwal_check->num_rows > 0);
  
    // Jika bukan hari libur nasional dan ada jadwal aktif, maka hari kerja
    if (!$is_libur_nasional && $is_hari_kerja) {
      $total_hari_kerja++;
    }
  }
  
  $total_izin = $izin_absensi_count + $jumlah_izin;
  $alpha = $total_hari_kerja - $hadir_count - $total_izin;
  if ($alpha < 0) $alpha = 0;
  
  // debug (opsional)
  // echo "<!-- DEBUG: total_hari_kerja=$total_hari_kerja, hadir=$hadir_count, izin_absensi=$izin_absensi_count, jumlah_izin=$jumlah_izin, alpha=$alpha -->";
  
  echo'
  <div class="card-body">
    <div class="row">
        <div class="col-md-3">
          <p>Hadir : <span class="badge badge-success">'.$hadir_count.'</span></p>
        </div>
  
        <div class="col-md-3">
            <p>Terlambat : <span class="badge badge-warning">'.$terlambat_count.'</span></p>
        </div>
          
        <div class="col-md-3">
          <p>Alpha : <span class="badge badge-danger">'.$alpha.'</span></p>
        </div>
  
        <div class="col-md-3">
          <p>Izin : <span class="badge badge-info">'.$total_izin.'</span></p>
        </div>
    </div>
  </div>';?>
    <script type="text/javascript">
    $(".load-data .datatable").dataTable({
      "iDisplayLength":35,
      "aLengthMenu": [[35, 40, 50, -1], [35, 40, 50, "All"]],
      "fnDrawCallback": function () {
                $('.open-popup-link').magnificPopup({
                type: 'image',
                removalDelay: 300,
                mainClass: 'mfp-fade',
                    gallery: {
                        enabled: true
                    },
                    zoom: {
                        enabled: true,
                        duration: 300,
                        easing: 'ease-in-out',
                        opener: function (openerElement) {
                            return openerElement.is('img') ? openerElement : openerElement.find('img');
                        }
                    }
                });
            },
      language: {
          paginate: {
            previous: "<i class='fas fa-angle-left'>",
            next: "<i class='fas fa-angle-right'>"
          }
        },
  });
  $(".open-popup-link").magnificPopup({type:"image"});
  </script>

<?php
break;
}}