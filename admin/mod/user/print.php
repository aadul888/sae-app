<?php
require_once '../../../library/config.php';

// Tambahkan ini untuk memastikan koneksi menggunakan UTF-8
if (method_exists($connection, 'set_charset')) {
    $connection->set_charset('utf8');
}

if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
require_once '../../../library/function.php';

$kelas = '';
if(!empty($_GET['kelas'])){
  $kelas = $_GET['kelas'];
}

// active tahun pelajaran
$tahun = '';
$semester = '';
$q_tp = $connection->query("SELECT tahun, semester FROM tahun_pelajaran WHERE aktif = 'Y' LIMIT 1");
if($q_tp && $q_tp->num_rows > 0){
  $r_tp = $q_tp->fetch_assoc();
  $tahun = $r_tp['tahun'];
  $semester = $r_tp['semester'];
}

// kelas & wali
$nama_kelas = '';
$wali_nama = '';
if($kelas != ''){
  $stmt_k = $connection->prepare("SELECT k.nama_kelas, a.fullname AS wali FROM kelas k LEFT JOIN admin a ON k.nama_wali_kelas = a.admin_id WHERE k.kelas_id = ? LIMIT 1");
  $stmt_k->bind_param('s', $kelas);
  $stmt_k->execute();
  $q_k = $stmt_k->get_result();
  if($q_k && $q_k->num_rows > 0){
    $r_k = $q_k->fetch_assoc();
    $nama_kelas = $r_k['nama_kelas'];
    $wali_nama = $r_k['wali'];
  }
  $stmt_k->close();
}

// counts
$jumlah_laki = 0; $jumlah_perempuan = 0; $total_siswa = 0;
if($kelas != ''){
  $stmt_cnt = $connection->prepare("SELECT
    SUM(CASE WHEN LOWER(jenis_kelamin) LIKE 'l%' THEN 1 ELSE 0 END) AS laki,
    SUM(CASE WHEN LOWER(jenis_kelamin) LIKE 'p%' THEN 1 ELSE 0 END) AS perempuan,
    COUNT(*) AS total
    FROM user WHERE kelas = ? AND status = 'aktif'");
  $stmt_cnt->bind_param('s', $kelas);
  $stmt_cnt->execute();
  $q_cnt = $stmt_cnt->get_result();
  if($q_cnt && $q_cnt->num_rows > 0){
    $r_cnt = $q_cnt->fetch_assoc();
    $jumlah_laki = (int)$r_cnt['laki'];
    $jumlah_perempuan = (int)$r_cnt['perempuan'];
    $total_siswa = (int)$r_cnt['total'];
  }
  $stmt_cnt->close();
}

// Hitung identitas sesuai dan berkas valid untuk persentase
$persen_identitas = 0;
$persen_berkas = 0;
$jumlah_identitas_sesuai = 0;
$jumlah_berkas_valid = 0;
if($kelas != '' && $total_siswa > 0){
  $stmt_stats = $connection->prepare("SELECT
    SUM(CASE WHEN LOWER(u.konfirmasi) = 'sesuai' THEN 1 ELSE 0 END) AS identitas_sesuai,
    SUM(CASE WHEN b.validasi_berkas IS NOT NULL AND LOWER(b.validasi_berkas) = 'valid' THEN 1 ELSE 0 END) AS berkas_valid
    FROM user u
    LEFT JOIN berkas b ON u.user_id = b.user_id
    WHERE u.kelas = ? AND LOWER(u.status) = 'aktif'");
  $stmt_stats->bind_param('s', $kelas);
  $stmt_stats->execute();
  $q_stats = $stmt_stats->get_result();
  if($q_stats && $q_stats->num_rows > 0){
    $r_stats = $q_stats->fetch_assoc();
    $jumlah_identitas_sesuai = (int)$r_stats['identitas_sesuai'];
    $jumlah_berkas_valid = (int)$r_stats['berkas_valid'];
    if($total_siswa > 0){
      $persen_identitas = round(($jumlah_identitas_sesuai / $total_siswa) * 100, 1);
      $persen_berkas = round(($jumlah_berkas_valid / $total_siswa) * 100, 1);
    }
  }
  $stmt_stats->close();
}

// students
$query_user = "SELECT u.user_id, u.nisn, u.nama_lengkap, u.jenis_kelamin, u.konfirmasi AS identitas,
  b.berkas_id, b.kk, b.akte, b.ijazah, b.kip, b.kks, b.kis, b.validasi_berkas AS berkas_status
  FROM user u
  LEFT JOIN berkas b ON u.user_id = b.user_id
  WHERE u.kelas = '$kelas' AND u.status = 'aktif'
  ORDER BY u.nama_lengkap ASC";

// Debug: Add error checking
$result_user = $connection->query($query_user);
if (!$result_user) {
  if (function_exists('debug_log')) debug_log("Query error: " . $connection->error);
  if (function_exists('debug_log')) debug_log("Query: " . $query_user);
}

// Debug loop removed for production. Enable explicit logging in development as needed.

// adaptive sizing (try to fit one A4 page)
$font_size = '14px'; $td_padding = '12px'; $badge_font = '11px'; $page_margin = '15mm'; $compact_class = '';
if($total_siswa > 25){
  if($total_siswa <= 35){ $font_size='13px'; $td_padding='10px'; $badge_font='10px'; }
  elseif($total_siswa <= 50){ $font_size='12px'; $td_padding='8px'; $badge_font='10px'; }
  elseif($total_siswa <= 75){ $font_size='11px'; $td_padding='7px'; $badge_font='9px'; $page_margin='12mm'; }
  elseif($total_siswa <= 120){ $font_size='10px'; $td_padding='6px'; $badge_font='9px'; $page_margin='10mm'; $compact_class='compact'; }
  else { $font_size='9px'; $td_padding='5px'; $badge_font='8px'; $page_margin='8mm'; $compact_class='compact'; }
}

$site_name_esc = htmlspecialchars($site_name);
$nama_kelas_esc = htmlspecialchars($nama_kelas);
$wali_nama_esc = htmlspecialchars($wali_nama);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Data Murid - <?php echo $nama_kelas_esc ?></title>
  <link rel="icon" href="../../content/<?php echo $site_favicon ?>" type="image/png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --primary: #2563eb;
      --primary-light: #dbeafe;
      --success: #059669;
      --success-light: #dcfce7;
      --warning: #d97706;
      --warning-light: #fef3c7;
      --danger: #dc2626;
      --danger-light: #fee2e2;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-300: #d1d5db;
      --gray-400: #9ca3af;
      --gray-500: #6b7280;
      --gray-600: #4b5563;
      --gray-700: #374151;
      --gray-800: #1f2937;
      --gray-900: #111827;
      --font-size: <?php echo $font_size ?>;
      --td-padding: <?php echo $td_padding ?>;
      --badge-font-size: <?php echo $badge_font ?>;
    }
    
    * { box-sizing: border-box; }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      color: var(--gray-800);
      background: #fff;
      margin: 0;
      line-height: 1.5;
      font-size: var(--font-size);
    }
    
    .container {
      max-width: 100%;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    .header {
      background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
      color: white;
      padding: 24px 20px;
      margin-bottom: 24px;
      border-radius: 0;
    }
    
    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 20px;
    }
    
    .header-left h1 {
      margin: 0 0 8px 0;
      font-size: 24px;
      font-weight: 700;
      letter-spacing: -0.025em;
    }
    
    .header-left .school-name {
      font-size: 16px;
      opacity: 0.9;
      font-weight: 500;
    }
    
    .header-right {
      text-align: right;
      font-size: 13px;
      opacity: 0.9;
    }
    
    .info-section {
      background: var(--gray-50);
      border: 1px solid var(--gray-200);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    
    .info-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    
    .info-label {
      font-size: 12px;
      font-weight: 600;
      color: var(--gray-600);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .info-value {
      font-size: 16px;
      font-weight: 600;
      color: var(--gray-900);
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-top: 16px;
    }
    
    .stat-card {
      background: white;
      border: 1px solid var(--gray-200);
      border-radius: 8px;
      padding: 12px;
      text-align: center;
    }
    
    .stat-number {
      font-size: 20px;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 4px;
    }
    
    .stat-label {
      font-size: 11px;
      color: var(--gray-600);
      font-weight: 500;
    }
    
    .table-container {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
      border: 1px solid var(--gray-200);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: var(--font-size);
    }
    
    thead th {
      background: var(--gray-800);
      color: white;
      padding: var(--td-padding);
      text-align: left;
      font-weight: 600;
      font-size: calc(var(--font-size) - 1px);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: none;
    }
    
    thead th:first-child { border-top-left-radius: 12px; }
    thead th:last-child { border-top-right-radius: 12px; }
    
    tbody td {
      padding: var(--td-padding);
      border-bottom: 1px solid var(--gray-100);
      vertical-align: middle;
    }
    
    tbody tr {
      transition: background-color 0.15s ease;
    }
    
    tbody tr:nth-child(even) {
      background: var(--gray-50);
    }
    
    tbody tr:hover {
      background: var(--primary-light);
    }
    
    tbody tr:last-child td:first-child {
      border-bottom-left-radius: 12px;
    }
    
    tbody tr:last-child td:last-child {
      border-bottom-right-radius: 12px;
    }
    
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: var(--badge-font-size);
      font-weight: 600;
      text-transform: capitalize;
      letter-spacing: 0.025em;
      min-width: 80px;
      justify-content: center;
    }
    
    .badge.green {
      background: var(--success-light);
      color: var(--success);
      border: 1px solid var(--success);
    }
    
    .badge.red {
      background: var(--danger-light);
      color: var(--danger);
      border: 1px solid var(--danger);
    }
    
    .badge.orange {
      background: var(--warning-light);
      color: var(--warning);
      border: 1px solid var(--warning);
    }
    
    .badge.gray {
      background: var(--gray-100);
      color: var(--gray-600);
      border: 1px solid var(--gray-300);
    }
    
    .badge.black {
      background: var(--gray-800);
      color: white;
    }
    
    .no-data {
      text-align: center;
      padding: 40px 20px;
      color: var(--gray-500);
      font-style: italic;
    }
    
    .gender-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      font-weight: 700;
      font-size: 12px;
    }
    
    .gender-l {
      background: #dbeafe;
      color: #2563eb;
    }
    
    .gender-p {
      background: #fce7f3;
      color: #be185d;
    }
    
    @page {
      size: A4;
      margin: <?php echo $page_margin ?>;
    }
    
    @media print {
      body { margin: 0; }
      .header { border-radius: 0; }
      .table-container { box-shadow: none; }
      tbody tr:hover { background: inherit; }
    }
    
    .compact .info-section { padding: 16px; }
    .compact .header { padding: 16px 20px; }
    .compact .stat-card { padding: 8px; }
    .compact .stat-number { font-size: 16px; }
  </style>
  <script>
    window.onafterprint = function(){ window.close(); };
    window.print();
  </script>
</head>
<body class="<?php echo $compact_class ?>">

  <div class="header">
    <div class="header-content">
      <div class="header-left">
        <h1>Data Murid</h1>
        <div class="school-name"><?php echo $site_name_esc ?></div>
      </div>
      <div class="header-right">
        <div><strong>Tahun Pelajaran:</strong> <?php echo htmlspecialchars($tahun) ?><?php if(!empty($semester)) echo ' - Semester '.htmlspecialchars($semester) ?></div>
        <div><strong>Dicetak:</strong> <?php echo date('d/m/Y H:i') ?></div>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="info-section">
      <div class="info-item">
        <div class="info-label">Kelas</div>
        <div class="info-value"><?php echo $nama_kelas_esc ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Wali Kelas</div>
        <div class="info-value"><?php echo $wali_nama_esc ?></div>
      </div>
      
      <div style="grid-column: 1 / -1;">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-number"><?php echo intval($total_siswa) ?></div>
            <div class="stat-label">Total Siswa</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo intval($jumlah_laki) ?></div>
            <div class="stat-label">Laki-laki</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?php echo intval($jumlah_perempuan) ?></div>
            <div class="stat-label">Perempuan</div>
          </div>
        </div>
      </div>
      <div style="grid-column: 1 / -1; margin-top:18px;">
        <div style="display:flex; gap:16px; flex-wrap:wrap;">
          <div class="stat-card" style="flex:1; min-width:180px; background:#f8fafc;">
            <div style="font-size:15px; font-weight:600; color:#059669; margin-bottom:2px;">
              <?php echo $persen_identitas ?>%
            </div>
            <div style="font-size:12px; color:#444;">
              Identitas Sesuai<br>
              <span style="font-size:11px; color:#888;">
                <?php echo $jumlah_identitas_sesuai ?> dari <?php echo $total_siswa ?> siswa
              </span>
            </div>
          </div>
          <div class="stat-card" style="flex:1; min-width:180px; background:#f8fafc;">
            <div style="font-size:15px; font-weight:600; color:#2563eb; margin-bottom:2px;">
              <?php echo $persen_berkas ?>%
            </div>
            <div style="font-size:12px; color:#444;">
              Berkas Valid<br>
              <span style="font-size:11px; color:#888;">
                <?php echo $jumlah_berkas_valid ?> dari <?php echo $total_siswa ?> siswa
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th style="width:50px;text-align:center">No</th>
            <th style="width:120px">NISN</th>
            <th>Nama Lengkap</th>
            <th style="width:60px;text-align:center">JK</th>
            <th style="width:120px;text-align:center">Identitas</th>
            <th style="width:120px;text-align:center">Berkas</th>
            <th style="width:100px;text-align:center">Kehadiran</th>
            <th style="width:80px;text-align:center">Poin</th>
          </tr>
        </thead>
        <tbody>
          <?php if($result_user && $result_user->num_rows > 0): $no=1; while($d = $result_user->fetch_assoc()):
            // Debug: Check if data exists
            $nisn = isset($d['nisn']) && $d['nisn'] !== null ? htmlspecialchars(trim($d['nisn'])) : '-';
            $nama = isset($d['nama_lengkap']) && $d['nama_lengkap'] !== null ? htmlspecialchars(trim($d['nama_lengkap'])) : 'Nama tidak tersedia';
            $jk = isset($d['jenis_kelamin']) && $d['jenis_kelamin'] !== null ? htmlspecialchars(trim($d['jenis_kelamin'])) : '-';
            
            // Check if nama is still empty after processing
            if (empty($nama) || $nama === '') {
                $nama = 'Data nama kosong';
            }
            
            $identitas = '-'; 
            if(isset($d['identitas']) && $d['identitas'] !== null && trim($d['identitas']) !== '') {
                $identitas = htmlspecialchars(trim($d['identitas']));
            }

            // berkas mapping
            $berkas = 'Belum'; $has_files = false;
            if(!empty($d['berkas_id'])){
              if(!empty($d['kk']) || !empty($d['akte']) || !empty($d['ijazah']) || !empty($d['kip']) || !empty($d['kks']) || !empty($d['kis'])) $has_files = true;
            }
            if(!$has_files) $berkas = 'Belum';
            else {
              $vs = isset($d['berkas_status']) ? trim($d['berkas_status']) : '';
              if($vs === '') $berkas = 'Belum Validasi';
              else{
                switch(strtolower($vs)){
                  case 'valid': $berkas = 'Valid'; break;
                  case 'tidak_valid': $berkas = 'Tidak Valid'; break;
                  case 'revisi': $berkas = 'Perlu Revisi'; break;
                  default: $berkas = ucfirst($vs);
                }
              }
            }

            $kehadiran = '-'; 
            if(isset($d['kehadiran_count'])){ 
                $kc = intval($d['kehadiran_count']); 
                $kehadiran = $kc > 0 ? $kc : '-'; 
            }
            $poin = '-';

            // badges
            $identitas_badge = 'gray';
            switch(strtolower($identitas)){
              case 'sesuai': $identitas_badge='green'; break;
              case 'belum sesuai': $identitas_badge='red'; break;
              case 'belum konfirmasi': $identitas_badge='orange'; break;
              default: $identitas_badge = ($identitas==='-') ? 'gray' : 'black';
            }
            $berkas_badge = 'gray';
            switch(strtolower($berkas)){
              case 'valid': $berkas_badge='green'; break;
              case 'belum validasi': $berkas_badge='orange'; break;
              case 'tidak valid': $berkas_badge='red'; break;
              case 'perlu revisi': $berkas_badge='orange'; break;
              case 'belum': $berkas_badge='gray'; break;
              default: $berkas_badge='black';
            }
            $kehadiran_badge = ($kehadiran==='-') ? 'gray' : 'green';
            $poin_badge = ($poin==='-') ? 'gray' : 'black';
          ?>
          <tr>
            <td style="text-align:center;font-weight:600"><?php echo $no++ ?></td>
            <td style="font-family:monospace"><?php echo $nisn ?></td>
            <td style="font-weight:500"><?php echo $nama ?></td>
            <td style="text-align:center">
              <span class="gender-badge gender-<?php echo strtolower($jk) ?>"><?php echo $jk ?></span>
            </td>
            <td style="text-align:center"><span class="badge <?php echo $identitas_badge ?>"><?php echo $identitas ?></span></td>
            <td style="text-align:center"><span class="badge <?php echo $berkas_badge ?>"><?php echo $berkas ?></span></td>
            <td style="text-align:center"><span class="badge <?php echo $kehadiran_badge ?>"><?php echo $kehadiran ?></span></td>
            <td style="text-align:center"><span class="badge <?php echo $poin_badge ?>"><?php echo $poin ?></span></td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="8" class="no-data">
            <?php 
            if ($result_user === false) {
                echo "Error dalam query database: <br><b>" . $connection->error . "</b><br><br>";
                echo "<small>Query: <code>" . htmlspecialchars($query_user) . "</code></small>";
            } else if ($result_user->num_rows == 0) {
                echo "Tidak ada data siswa untuk kelas ini";
            } else {
                echo "Tidak dapat menampilkan data siswa";
            }
            ?>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>

