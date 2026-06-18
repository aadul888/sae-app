<?php
require_once '../../../library/config.php';

if (empty($connection)) {
    echo 'Koneksi tidak ditemukan';
    exit();
}

// Cek login siswa
if (!isset($_COOKIE['siswa'])) {
    echo 'Akses ditolak. Silakan login terlebih dahulu.';
    exit();
}

require_once '../../../library/function.php';

// Decrypt user ID dari cookie
$cookie_val = $_COOKIE['siswa'];
$decoded_id = convert('decrypt', $cookie_val);

if (!is_numeric($decoded_id)) {
    echo 'Session tidak valid';
    exit();
}

$user_id = intval($decoded_id);
$q_user = mysqli_query($connection, "SELECT kelas, koordinator FROM user WHERE user_id='$user_id' LIMIT 1");
$d_user = mysqli_fetch_assoc($q_user);

// Cek apakah user adalah koordinator kelas
if (!$d_user || ($d_user['koordinator'] != 1 && $d_user['koordinator'] != '1')) {
    echo 'Akses ditolak. Halaman ini hanya untuk koordinator kelas.';
    exit();
}

$kelas_id = trim($d_user['kelas']);

// Ambil data kelas dari tabel kelas berdasarkan kelas_id
$q_kelas = mysqli_query($connection, "SELECT * FROM kelas WHERE kelas_id='$kelas_id' LIMIT 1");
$d_kelas = mysqli_fetch_assoc($q_kelas);
$nama_kelas_full = isset($d_kelas['nama_kelas']) ? $d_kelas['nama_kelas'] : $kelas_id;
$wali_kelas_nama = isset($d_kelas['wali_kelas_nama']) ? $d_kelas['wali_kelas_nama'] : '-';

// Ambil data siswa di kelas yang sama
$query_user = "SELECT u.user_id, u.nisn, u.nama_lengkap, u.jenis_kelamin, u.konfirmasi AS identitas,
  b.berkas_id, b.kk, b.akte, b.ijazah, b.kip, b.kks, b.kis, b.validasi_berkas AS berkas_status
  FROM user u
  LEFT JOIN berkas b ON u.user_id = b.user_id
    WHERE u.kelas = '$kelas_id' AND u.status = 'Aktif'
    ORDER BY u.nama_lengkap ASC";

$result_user = $connection->query($query_user);

// Hitung statistik
$jml_laki = 0;
$jml_perempuan = 0;
$jml_total = 0;
$jml_valid = 0;

// Compute class quality using the same weighted method as admin > kelas
// (0.5 for konfirmasi being 'Sesuai' or 'Belum Sesuai', 0.5 for having berkas valid)
if ($result_user && $result_user->num_rows > 0) {
    $result_user->data_seek(0); // Reset pointer
    $jumlah_kualitas = 0.0;
    while ($row = $result_user->fetch_assoc()) {
        $jml_total++;
        if (strtolower(trim($row['jenis_kelamin'])) == 'laki-laki') $jml_laki++;
        if (strtolower(trim($row['jenis_kelamin'])) == 'perempuan') $jml_perempuan++;

        // konfirmasi valid jika bernilai 'Sesuai' atau 'Belum Sesuai'
        $konfirmasi_ok = false;
        if (isset($row['identitas'])) {
            $v = trim($row['identitas']);
            if ($v === 'Sesuai' || $v === 'Belum Sesuai') $konfirmasi_ok = true;
        }

        $score = 0.0;
        if ($konfirmasi_ok) $score += 0.5;

        // Cek apakah ada berkas valid untuk user ini
        $uid = intval($row['user_id']);
        $q_bv = mysqli_query($connection, "SELECT 1 FROM berkas b WHERE b.user_id='$uid' AND b.validasi_berkas='valid' LIMIT 1");
        if ($q_bv && mysqli_num_rows($q_bv) > 0) {
            $score += 0.5;
        }

        $jumlah_kualitas += $score;
    }
    $persen_valid = $jml_total > 0 ? round(($jumlah_kualitas / $jml_total) * 100, 1) : 0;
} else {
    $persen_valid = 0;
}

// Reset result pointer for processing
if ($result_user) $result_user->data_seek(0);

// Prepare filename
$clean_kelas = preg_replace('/[^a-zA-Z0-9]/', '_', $nama_kelas_full);
$filename = "Data_Siswa_Kelas_" . $clean_kelas . "_" . date('Y-m-d_H-i') . ".pdf";

// Ambil informasi sistem
$query_system = "SELECT * FROM setting LIMIT 1";
$result_system = $connection->query($query_system);
if ($result_system && $result_system->num_rows > 0) {
    $system_info = $result_system->fetch_assoc();
    $app_name = $system_info['site_name'] ?? 'SAEV4 System';
    $app_logo = $system_info['site_logo'] ?? 'logoweb.png';
} else {
    $app_name = 'SAEV4 System';
    $app_logo = 'logoweb.png';
}

// Generate HTML content for PDF
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Data Siswa Kelas - <?php echo htmlspecialchars($nama_kelas_full) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
            line-height: 1.4;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #495057;
            margin: 15px 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 3px;
        }

        .header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 12px 20px;
            text-align: center;
        }

        .stats-section {
            width: 100%;
            margin-bottom: 15px;
            display: table;
            table-layout: fixed;
        }

        .stat-item {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
            padding: 14px 8px;
            background: white;
            border: 1px solid #dee2e6;
            box-sizing: border-box;
            font-size: 13px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            width: 25%;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 6px;
            color: #2c3e50;
            line-height: 1;
        }

        .stat-label {
            font-size: 10px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }

        .header .kelas-info {
            font-size: 11px;
            margin: 2px 0;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        th,
        td {
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #dee2e6;
            font-size: 9px;
        }

        th {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            color: white;
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e9ecef;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            color: #ffffff;
            min-width: 80px;
            text-align: center;
            box-shadow: none;
        }

        .badge-green {
            background: #28a745;
        }

        .badge-red {
            background: #dc3545;
        }

        .badge-orange {
            background: #fd7e14;
        }

        .badge-gray {
            background: #6c757d;
        }

        .header .logo {
            width: 18px;
            height: auto;
            margin-bottom: 8px;
            display: inline-block;
        }

        .header h1 {
            margin: 0;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .header .kelas-info {
            font-size: 22px;
            margin: 10px 0 4px 0;
            font-weight: bold;
            color: #fff;
            letter-spacing: 1px;
        }

        .header p {
            margin: 1px 0 0 0;
            font-size: 10px;
        }

        .header .app-name {
            font-size: 10px;
            margin-bottom: 1px;
            font-weight: normal;
            opacity: 0.9;
        }

        .gender-badge {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 18px;
            font-weight: bold;
            font-size: 9px;
            color: #ffffff;
        }

        .gender-laki-laki {
            background: #3b82f6;
            color: #ffffff;
        }

        .gender-perempuan {
            background: #be185d;
            color: #ffffff;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #6c757d;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <?php
        $logo_path = "../../../content/" . $app_logo;
        if (file_exists($logo_path)) {
            // Force a reasonable display size via inline style so PDF renderer respects it
            echo '<img src="' . $logo_path . '" alt="Logo" class="logo" style="width:120px;height:auto;display:block;margin:0 auto 8px;">';
        }
        ?>
        <div class="app-name"><?php echo htmlspecialchars($app_name) ?></div>
        <div class="kelas-info"><?php echo htmlspecialchars($nama_kelas_full) ?> | <?php echo htmlspecialchars($wali_kelas_nama) ?></div>
        <h1>LAPORAN DATA SISWA KELAS</h1>
        <p>Dicetak pada: <?php echo date('d/m/Y H:i') ?> WIB</p>
    </div>

    <div class="section-title">📊 Statistik Kelas</div>
    <table class="stats-section" style="margin-bottom:15px;width:100%;table-layout:fixed;border-collapse:separate;border-spacing:12px 0;">
        <tr>
            <td class="stat-item">
                <div class="stat-number" style="color: #3b82f6;"><?php echo $jml_laki ?></div>
                <div class="stat-label">Laki-laki</div>
            </td>
            <td class="stat-item">
                <div class="stat-number" style="color: #be185d;"><?php echo $jml_perempuan ?></div>
                <div class="stat-label">Perempuan</div>
            </td>
            <td class="stat-item">
                <div class="stat-number" style="color: #059669;"><?php echo $jml_total ?></div>
                <div class="stat-label">Total Siswa</div>
            </td>
            <td class="stat-item">
                <div class="stat-number" style="color: #0284c7;"><?php echo $persen_valid ?>%</div>
                <div class="stat-label">Data Valid</div>
            </td>
        </tr>
    </table>

    <div class="section-title">📋 Daftar Siswa</div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">No</th>
                <th style="width: 100px;">NISN</th>
                <th>Nama Lengkap</th>
                <th style="width: 50px; text-align: center;">JK</th>
                <th style="width: 100px; text-align: center;">Status Identitas</th>
                <th style="width: 100px; text-align: center;">Status Berkas</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result_user && $result_user->num_rows > 0): $no = 1;
                while ($d = $result_user->fetch_assoc()):
                    $nisn = isset($d['nisn']) && $d['nisn'] !== null ? htmlspecialchars(trim($d['nisn'])) : '-';
                    $nama = isset($d['nama_lengkap']) && $d['nama_lengkap'] !== null ? htmlspecialchars(trim($d['nama_lengkap'])) : 'Nama tidak tersedia';
                    $jk = isset($d['jenis_kelamin']) && $d['jenis_kelamin'] !== null ? htmlspecialchars(trim($d['jenis_kelamin'])) : '-';

                    if (empty($nama) || $nama === '') {
                        $nama = 'Data nama kosong';
                    }

                    $identitas = '-';
                    if (isset($d['identitas']) && $d['identitas'] !== null && trim($d['identitas']) !== '') {
                        $identitas = htmlspecialchars(trim($d['identitas']));
                    }

                    // berkas mapping
                    $berkas = 'Belum Upload';
                    $has_files = false;
                    if (!empty($d['berkas_id'])) {
                        if (!empty($d['kk']) || !empty($d['akte']) || !empty($d['ijazah']) || !empty($d['kip']) || !empty($d['kks']) || !empty($d['kis'])) $has_files = true;
                    }
                    if (!$has_files) $berkas = 'Belum Upload';
                    else {
                        $vs = isset($d['berkas_status']) ? trim($d['berkas_status']) : '';
                        if ($vs === '') $berkas = 'Menunggu Validasi';
                        else {
                            switch (strtolower($vs)) {
                                case 'valid':
                                    $berkas = 'Valid';
                                    break;
                                case 'tidak_valid':
                                    $berkas = 'Tidak Valid';
                                    break;
                                case 'revisi':
                                    $berkas = 'Perlu Revisi';
                                    break;
                                default:
                                    $berkas = ucfirst($vs);
                            }
                        }
                    }

                    // badges
                    $identitas_badge = 'gray';
                    switch (strtolower($identitas)) {
                        case 'sesuai':
                            $identitas_badge = 'green';
                            break;
                        case 'belum sesuai':
                            $identitas_badge = 'red';
                            break;
                        case 'belum konfirmasi':
                            $identitas_badge = 'orange';
                            break;
                        default:
                            $identitas_badge = ($identitas === '-') ? 'gray' : 'orange';
                    }

                    $berkas_badge = 'gray';
                    switch (strtolower($berkas)) {
                        case 'valid':
                            $berkas_badge = 'green';
                            break;
                        case 'menunggu validasi':
                            $berkas_badge = 'orange';
                            break;
                        case 'tidak valid':
                            $berkas_badge = 'red';
                            break;
                        case 'perlu revisi':
                            $berkas_badge = 'orange';
                            break;
                        case 'belum upload':
                            $berkas_badge = 'gray';
                            break;
                        default:
                            $berkas_badge = 'orange';
                    }
            ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;"><?php echo $no++ ?></td>
                        <td style="font-family: monospace;"><?php echo $nisn ?></td>
                        <td style="font-weight: 500;"><?php echo $nama ?></td>
                        <td style="text-align: center;">
                            <span class="gender-badge gender-<?php echo strtolower($jk) ?>"><?php echo $jk === 'Laki-laki' ? 'L' : ($jk === 'Perempuan' ? 'P' : $jk) ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-<?php echo $identitas_badge ?>"><?php echo $identitas ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-<?php echo $berkas_badge ?>"><?php echo $berkas ?></span>
                        </td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #6c757d;">
                        <?php
                        if ($result_user === false) {
                            echo "Error dalam query database";
                        } else if ($result_user->num_rows == 0) {
                            echo "Tidak ada data siswa untuk kelas ini";
                        } else {
                            echo "Tidak dapat menampilkan data siswa";
                        }
                        ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <strong><?php echo htmlspecialchars($site_name) ?></strong><br>
        Sistem Aplikasi Edukatif - Data dicetak pada <?php echo date('d/m/Y H:i:s') ?> WIB
    </div>
</body>

</html>
<?php
$html_content = ob_get_clean();

// Set headers untuk download PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Menggunakan MPDF library
try {
    require_once '../../../library/PDF/autoload.php';

    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'orientation' => 'P',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_header' => 9,
        'margin_footer' => 9
    ]);

    $mpdf->WriteHTML($html_content);
    $mpdf->Output($filename, 'D'); // 'D' untuk download

} catch (Exception $e) {
    // Fallback jika MPDF gagal
    header('Content-Type: text/html; charset=UTF-8');
    echo '<div style="padding: 20px; text-align: center; font-family: Arial;">';
    echo '<h3>Generating PDF...</h3>';
    echo '<p>Jika download tidak dimulai otomatis, <a href="javascript:window.print()">klik disini untuk print</a></p>';
    echo '</div>';
    echo $html_content;
    echo '<script>
        setTimeout(function() {
            window.print();
        }, 1000);
    </script>';
}
?>