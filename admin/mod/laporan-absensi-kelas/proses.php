<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../library/config.php';
include('../../../library/function.php');
require_once'../../login/user.php';

$modul_id = 18;
include __DIR__ . '/../check_role.php';

function check_access($type)
{
  global $data_role;
  if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
    echo 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.';
    exit;
  }
}

if (!function_exists('lap_kelas_finalize_absensi_lintas_hari')) {
  function lap_kelas_finalize_absensi_lintas_hari($connection)
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

lap_kelas_finalize_absensi_lintas_hari($connection);

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
echo'<option value="">Semua Siswa</option>';
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

/** Filter Kelas */
if(!empty($_POST['kelas'])){
  $kelas  = anti_injection($_POST['kelas']);
  $filter_siswa = "WHERE user.kelas='$kelas'";
  $pagination   = "WHERE user.kelas='$kelas'";
} else {
  if($current_user['level'] == '2'){
    $filter_siswa = "WHERE user.admin_id='$current_user[admin_id]'";
    $pagination   = "WHERE user.admin_id='$current_user[admin_id]'";
  } elseif($current_user['level'] == '4'){
    $filter_siswa = "WHERE user.nama_pembimbing_perusahaan='$current_user[admin_id]'";
    $pagination   = "WHERE user.nama_pembimbing_perusahaan='$current_user[admin_id]'";
  } else {
    $filter_siswa = "";
    $pagination   = "";
  }
} 

$hari       = date("d");
$jumlahhari = date("t",mktime(0,0,0,$bulan,$hari,$tahun));
$kolom = $jumlahhari * 2;

// --- NEW: cache flags per tanggal (jadwal off atau hari_libur) ---
$col_flags = []; // key = 'YYYY-MM-DD' => ['marked'=>bool,'reason'=>string,'background'=>color,'color'=>color]

// helper mapping hari number -> Indonesian day name (sesuaikan bila beda di DB)
$hari_map = [
  1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
  5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
];

for ($d=1;$d<=$jumlahhari;$d++) {
  $tanggal  = sprintf('%04d-%02d-%02d', (int)$tahun, (int)$bulan, (int)$d);
  // default
  $col_flags[$tanggal] = ['marked' => false, 'reason' => '', 'background' => '#f6f9fc', 'color' => '#111111'];

  // 1) cek jadwal untuk hari ini (jadwal.hari menyimpan nama hari, contoh 'Senin')
  $weekday = (int)date('N', strtotime($tanggal));
  $hari_text = $hari_map[$weekday] ?? date('D', strtotime($tanggal));
  $q_jad = $connection->prepare("SELECT status FROM jadwal WHERE hari = ? LIMIT 1");
  if ($q_jad) {
    $q_jad->bind_param("s", $hari_text);
    $q_jad->execute();
    $r_jad = $q_jad->get_result();
    if ($r_jad && $r_jad->num_rows > 0) {
      $jad = $r_jad->fetch_assoc();
      if (isset($jad['status']) && strtoupper($jad['status']) === 'N') {
        $col_flags[$tanggal]['marked'] = true;
        $col_flags[$tanggal]['reason'] = 'Tidak ada jadwal';
        $col_flags[$tanggal]['background'] = '#FFCCCC';
        $col_flags[$tanggal]['color'] = '#111111';
      }
    }
    $q_jad->close();
  }

  // 2) cek hari_libur (range tanggal_mulai..tanggal_selesai)
  $q_hl = $connection->prepare("SELECT nama_libur, keterangan FROM hari_libur WHERE ? BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
  if ($q_hl) {
    $q_hl->bind_param("s", $tanggal);
    $q_hl->execute();
    $r_hl = $q_hl->get_result();
    if ($r_hl && $r_hl->num_rows > 0) {
      $hl = $r_hl->fetch_assoc();
      $col_flags[$tanggal]['marked'] = true;
      $col_flags[$tanggal]['reason'] = !empty($hl['nama_libur']) ? $hl['nama_libur'] : ($hl['keterangan'] ?? 'Hari Libur');
      $col_flags[$tanggal]['background'] = '#FF0000';
      $col_flags[$tanggal]['color'] = '#ffffff';
    }
    $q_hl->close();
  }

  // 3) existing weekend / libur_nasional check (tambahkan pengaruh ke col_flags jika belum marked)
  $hari_libur = date('D',strtotime($tanggal));
  // existing logic to detect Sat/Sun via libur table
  $query_sabtu ="SELECT libur_hari FROM libur WHERE libur_hari='Sabtu' AND active='Y'";
  $result_sabtu= $connection->query($query_sabtu);
  $sabtu = $result_sabtu && $result_sabtu->num_rows>0 ? 'Sat' : '';

  $query_minggu ="SELECT libur_hari FROM libur WHERE libur_hari='Minggu' AND active='Y'";
  $result_minggu = $connection->query($query_minggu);
  $minggu = $result_minggu && $result_minggu->num_rows>0 ? 'Sun' : '';

  if ($hari_libur == $sabtu OR $hari_libur == $minggu) {
    $col_flags[$tanggal]['marked'] = true;
    $col_flags[$tanggal]['reason'] = 'Libur (Akhir Pekan)';
    $col_flags[$tanggal]['background'] = '#FF0000';
    $col_flags[$tanggal]['color'] = '#ffffff';
  } else {
    $query_libur  = "SELECT libur_tanggal,keterangan FROM libur_nasional WHERE libur_tanggal='$tanggal'";
    $result_libur = $connection->query($query_libur);
    if($result_libur && $result_libur->num_rows > 0){
      $data_libur = $result_libur->fetch_assoc();
      $col_flags[$tanggal]['marked'] = true;
      $col_flags[$tanggal]['reason'] = strip_tags($data_libur['keterangan'] ?? 'Libur Nasional');
      $col_flags[$tanggal]['background'] = '#FF0000';
      $col_flags[$tanggal]['color'] = '#ffffff';
    }
  }
}

// sekarang buat header menggunakan $col_flags
echo'
<div class="table-responsive" style="overflow-x: auto!important;">
<table class="table align-items-center table-bordered datatable" style="width:100%">
  <thead class="thead-light">
    <tr>
      <th rowspan="3" width="40" class="text-center" style="vertical-align: middle;">No</th>
      <th rowspan="3" style="vertical-align: middle;">Nama Siswa</th>
      <th rowspan="3" style="vertical-align: middle;">Kelas</th>
      <th class="text-center" colspan="'.$kolom.'">'.ambilbulan($month).'</th>
      <th class="text-center" colspan="4">Keterangan</th>
    </tr>
    <tr>';
    for ($d=1;$d<=$jumlahhari;$d++) {
      $tanggal  = sprintf('%04d-%02d-%02d', (int)$tahun, (int)$bulan, (int)$d);
      $bg = $col_flags[$tanggal]['background'];
      $color = $col_flags[$tanggal]['color'];
      $label = $col_flags[$tanggal]['marked'] ? htmlspecialchars($col_flags[$tanggal]['reason']) : date('D', strtotime($tanggal));
      echo'
        <th width="50" colspan="2" class="text-center" style="background:'.$bg.';color:'.$color.'">'.$label.'<br>'.$d.'</th>';
    }
    echo'
      <th width="50" rowspan="2" class="text-center">H</th>
      <th width="50" rowspan="2" class="text-center">T</th>
      <th width="50" rowspan="2" class="text-center">A</th>
      <th width="50" rowspan="2" class="text-center">I</th>
      </tr>

      <tr>';
      for ($d=1;$d<=$jumlahhari;$d++) {
        echo'
        <th class="text-center">Masuk</th>
        <th class="text-center">Pulang</th>';
      }
      echo'
      </tr>
  </thead>
<tbody>';
    $limit=30; 
    $no =0;
    if(isset($_GET['halaman'])){
    $halaman = mysqli_real_escape_string($connection,$_GET['halaman']);}
    else{$halaman = 1;} $offset = ($halaman - 1) * $limit;

    $query_siswa ="SELECT user.user_id,user.nama_lengkap,kelas.nama_kelas FROM user
    INNER JOIN kelas  ON user.kelas = kelas.kelas_id $filter_siswa ORDER BY user.user_id ASC LIMIT $offset, $limit";
    $result_siswa = $connection->query($query_siswa);
    if($result_siswa->num_rows > 0){
      while ($data_siswa = $result_siswa->fetch_assoc()){$no++;
       echo'
      <tr>
        <td class="text-center">'.$no.'</td>
        <td width="150">'.strip_tags($data_siswa['nama_lengkap']).'</td>
        <td width="150">'.strip_tags($data_siswa['nama_kelas']).'</td>';
        for ($d=1;$d<=$jumlahhari;$d++){
          $tanggal  = sprintf('%04d-%02d-%02d', (int)$tahun, (int)$bulan, (int)$d);
          $filter = "WHERE tanggal='$tanggal' AND MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND user_id='$data_siswa[user_id]'";
          $td_style = '';
          // apply column-level mark (e.g., hari_libur or jadwal off) to cell style
          if (!empty($col_flags[$tanggal]['marked'])) {
            $td_style = ' style="background:'.$col_flags[$tanggal]['background'].';color:'.$col_flags[$tanggal]['color'].';"';
          }

          $query_absen ="SELECT tanggal,jam_masuk,jam_pulang,status_masuk,status_pulang,kehadiran FROM absensi $filter";
          $result_absen = $connection->query($query_absen);
            if($result_absen->num_rows > 0){
              $data_absen = $result_absen->fetch_assoc();

              if($data_absen['status_masuk']=='Tepat Waktu'){
                $status_masuk ='<span class="badge badge-success">H</span>';
              }elseif($data_absen['status_masuk']=='Terlambat'){
                  $status_masuk ='<span class="badge badge-warning">T</span>';
              }else{
                if($data_absen['kehadiran'] =='Izin' || $data_absen['status_masuk']=='Izin'){
                  $status_masuk ='<span class="badge badge-info">I</span>';
                }else{
                  $status_masuk ='<span class="badge badge-danger">A</span>';
                }

              }

              if(empty($data_absen['jam_pulang']) || $data_absen['jam_pulang']=='00:00:00'){
                $status_pulang='';
                $jam_pulang = '-';
              }else{
                if($data_absen['status_pulang']=='Pulang'){
                    $status_pulang ='<span class="badge badge-success">P</span>';
                }elseif($data_absen['status_pulang']=='Pulang Cepat'){
                    $status_pulang ='<span class="badge badge-warning">PC</span>';
                }else{
                    $status_pulang ='';
                }
                $jam_pulang = $data_absen['jam_pulang'];
              }

            echo'
              <td class="text-center"'.$td_style.'>'.$data_absen['jam_masuk'].'<br>'.$status_masuk.'</td>
              <td class="text-center"'.$td_style.'>'.$jam_pulang.'<br>'.$status_pulang.'</td>';
            }else{
              // jika tidak ada absensi, cek apakah ada izin yang mencakup tanggal ini (HANYA yang sudah Disetujui)
              $q_izin = "
                SELECT id, status_izin
                FROM izin
                WHERE user_id = '" . mysqli_real_escape_string($connection, $data_siswa['user_id']) . "'
                  AND status_izin = 'Disetujui'
                  AND '$tanggal' BETWEEN LEAST(tanggal_mulai, tanggal_selesai) AND GREATEST(tanggal_mulai, tanggal_selesai)
                LIMIT 1
              ";
              $res_izin = $connection->query($q_izin);
              if ($res_izin && $res_izin->num_rows > 0) {
                $row_izin = $res_izin->fetch_assoc();
                $status_masuk = '<span class="badge badge-info">I</span>';
                echo '
                  <td class="text-center"'.$td_style.'>-<br>' . $status_masuk . '</td>
                  <td class="text-center"'.$td_style.'>-</td>';
              } else {
                // kosong/tidak hadir; still apply td_style if holiday/jadwal off
                echo'
                <td class="text-center"'.$td_style.'><i class="fas fa-times"></i></td>
                <td class="text-center"'.$td_style.'><i class="fas fa-times"></i></td>';
              }
            }
          }

          $filter_jumlah = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun' AND user_id='$data_siswa[user_id]'";

            // Hitung Hadir (Tepat Waktu + Terlambat)
            $query_hadir  = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk IN ('Tepat Waktu', 'Terlambat')";
            $result_hadir = $connection->query($query_hadir);
            $hadir_count = $result_hadir ? $result_hadir->fetch_assoc()['jumlah'] : 0;

            // Hitung Terlambat
            $query_telat  = "SELECT COUNT(*) as jumlah FROM absensi WHERE $filter_jumlah AND status_masuk='Terlambat'";
            $result_telat = $connection->query($query_telat);
            $terlambat_count = $result_telat ? $result_telat->fetch_assoc()['jumlah'] : 0;

            // Hitung Izin: gabungkan izin di tabel absensi + jumlah hari dari tabel izin yang jatuh di bulan ini
            // (a) Izin yang tercatat di tabel absensi pada bulan ini (distinct tanggal)
            $query_izin_absen  = "SELECT COUNT(DISTINCT tanggal) as jumlah FROM absensi WHERE $filter_jumlah AND (status_masuk='Izin' OR kehadiran='Izin')";
            $result_izin_absen = $connection->query($query_izin_absen);
            $izin_count_absensi = $result_izin_absen ? intval($result_izin_absen->fetch_assoc()['jumlah']) : 0;

            // (b) Hitung total hari izin dari tabel izin yang jatuh pada bulan ini (overlap dengan bulan)
            // normalisasi rentang tanggal (LEAST/GREATEST) dan pastikan perhitungan tidak negatif
            $start_month = $tahun . '-' . str_pad($bulan,2,'0',STR_PAD_LEFT) . '-01';
            $end_month = $tahun . '-' . str_pad($bulan,2,'0',STR_PAD_LEFT) . '-' . date('t', strtotime($start_month));

            // Hitung total hari izin overlap dengan bulan ini (HANYA Disetujui)
            $q_izin_days_total = "
              SELECT COALESCE(SUM(
                GREATEST(0,
                  DATEDIFF(
                    LEAST(GREATEST(tanggal_selesai, tanggal_mulai), '$end_month'),
                    GREATEST(LEAST(tanggal_mulai, tanggal_selesai), '$start_month')
                  ) + 1
                )
              ),0) AS jumlah
              FROM izin
              WHERE user_id = '" . mysqli_real_escape_string($connection, $data_siswa['user_id']) . "'
                AND status_izin = 'Disetujui'
                AND LEAST(tanggal_mulai, tanggal_selesai) <= '$end_month'
                AND GREATEST(tanggal_mulai, tanggal_selesai) >= '$start_month'
            ";
              $res_izin_days_total = $connection->query($q_izin_days_total);
              $izin_days_total = $res_izin_days_total && $res_izin_days_total->num_rows ? intval($res_izin_days_total->fetch_assoc()['jumlah']) : 0;
 
            // Hitung overlap hari izin (Disetujui) yang juga tercatat di absensi (distinct tanggal)
            $q_izin_overlap_absen = "
              SELECT COUNT(DISTINCT a.tanggal) AS jumlah
              FROM izin i
              JOIN absensi a ON a.user_id = i.user_id
                AND a.tanggal BETWEEN GREATEST(LEAST(i.tanggal_mulai, i.tanggal_selesai), '$start_month') 
                                 AND LEAST(GREATEST(i.tanggal_mulai, i.tanggal_selesai), '$end_month')
              WHERE i.user_id = '" . mysqli_real_escape_string($connection, $data_siswa['user_id']) . "'
                AND i.status_izin = 'Disetujui'
                AND LEAST(i.tanggal_mulai, i.tanggal_selesai) <= '$end_month'
                AND GREATEST(i.tanggal_mulai, i.tanggal_selesai) >= '$start_month'
            ";
              $res_izin_overlap = $connection->query($q_izin_overlap_absen);
              $overlap_days = $res_izin_overlap && $res_izin_overlap->num_rows ? intval($res_izin_overlap->fetch_assoc()['jumlah']) : 0;

            // Hari izin eksklusif dalam bulan ini (tidak punya absensi) -> dihitung sebagai izin
            $izin_days_exclusive = $izin_days_total - $overlap_days;
            if ($izin_days_exclusive < 0) $izin_days_exclusive = 0;

            // Total izin pada bulan ini = izin yang tercatat di absensi + hari izin eksklusif dari tabel izin
            $izin_count = $izin_count_absensi + $izin_days_exclusive;

            // Hitung Alpha (total hari kerja - hadir - izin)
            $total_hari_kerja = $jumlahhari - $jumlah_libur - $jumlah_libur_nasional;
            $alpha = $total_hari_kerja - $hadir_count - $izin_count;
            if ($alpha < 0) $alpha = 0;

          echo'
          <td width="50" class="text-center"><span class="badge badge-success">'.$hadir_count.'</span></td>
          <th width="50" class="text-center"><span class="badge badge-warning">'.$terlambat_count.'</span></td>
          <th width="50" class="text-center"><span class="badge badge-danger">'.$alpha.'</span></td>
          <th width="50" class="text-center"><span class="badge badge-info">'.$izin_count.'</span></td>
      </tr>';

      }

    echo'
      </tbody>
    </table>
  </div>
        <nav>
          <ul class="pagination justify-content-center mt-3">';
          $query_pagination = "SELECT COUNT(user_id) AS jumData FROM user $pagination";
          $result_pagination = $connection->query($query_pagination);
            $data  = $result_pagination->fetch_assoc();
            $jumData = $data['jumData'];
            $jumPage = ceil($jumData/$limit);
                //menampilkan link << Previou
                if ($halaman > 1){echo '<li class="page-item"><a class="page-link btn-pagination" href="javascript:void(0);" data-id="'.($halaman-1).'">«</a></li>';}
                //menampilkan urutan paging
                    for($i = 1; $i <= $jumPage; $i++){
                //mengurutkan agar yang tampil i+3 dan i-3
                    if ((($i >= $halaman - 1) && ($i <= $halaman + 4)) || ($i == 1) || ($i == $jumPage)){
                        if($i==$jumPage && $halaman <= $jumPage-4)
                            echo'<li class="disabled"><a href="javascript:void(0);">..</a></li>';
                            if ($i == $halaman) echo '<li class="page-item active"><a class="page-link btn-pagination"href="javascript:void(0);" data-id="'.$i.'">'.$i.'</a></li>';
                            else echo '<li class="page-item"><a class="page-link btn-pagination"  href="javascript:void(0)" data-id="'.$i.'">'.$i.'</a></li>';

                    if($i==1 && $halaman >= 4) echo '<li class="disabled"><a href="#">..</a></li>';

                }}

                //menampilkan link Next >>
                if ($halaman < $jumPage){echo'<li class="page-item"><a class="page-link btn-pagination" href="javascript:void(0);" data-id="'.($halaman+1).'">»</a></li>';
                }

          echo'
          </ul>
        </nav>';
  
    }else{
      echo'Tidak Ada data siswa';
    }

break;
}}?>