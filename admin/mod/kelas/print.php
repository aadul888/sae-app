<?php
require_once '../../../library/config.php';


if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/function.php';
  require_once '../../assets/vendor/autoload.php';


  if (!function_exists('get_kelas_stats')) {
    function get_kelas_stats($connection)
    {
      $sql = "SELECT k.kelas_id, k.nama_kelas, k.jurusan_id, j.nama_jurusan, k.wali_kelas_ptk_id AS wali_kelas_id, k.wali_kelas_nama AS wali_kelas, COUNT(DISTINCT u.user_id) AS jumlah_siswa,
        SUM(
          (
            (CASE WHEN (u.konfirmasi = 'Sesuai' OR u.konfirmasi = 'Belum Sesuai') THEN 1 ELSE 0 END) * 0.5
            +
            (CASE WHEN EXISTS (SELECT 1 FROM berkas b WHERE b.user_id = u.user_id AND b.validasi_berkas = 'valid') THEN 1 ELSE 0 END) * 0.5
          )
        ) AS jumlah_kualitas
        FROM kelas k
        /* Only include active users so printed stats match dashboard/datatable */
        LEFT JOIN user u ON k.kelas_id = u.kelas AND (u.status = '1' OR LOWER(u.status) = 'aktif')
        LEFT JOIN jurusan j ON k.jurusan_id = j.jurusan_id
        GROUP BY k.kelas_id
        ORDER BY k.nama_kelas ASC";

      $res = $connection->query($sql);
      if (!$res) {
        return array('error' => true, 'message' => $connection->error, 'query' => $sql);
      }

      $rows = array();
      while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
      }

      return $rows;
    }
  }

  $result_rows = get_kelas_stats($connection);
  // Jika query gagal, tampilkan error
  if (isset($result_rows['error']) && $result_rows['error'] === true) {
    die('Query Error: ' . $result_rows['message']);
  }

  // Reuse the user/print.php styling and layout for consistency
  // Build a simple data array from the query results
  $kelas_list = [];
  if (is_array($result_rows) && count($result_rows) > 0) {
    foreach ($result_rows as $row) {
      $jumlah = intval($row['jumlah_siswa']);
      // jumlah_kualitas sekarang bisa bernilai desimal karena bobot 50/50
      $kualitas = floatval($row['jumlah_kualitas']);
      $persen = 0;

      if ($jumlah > 0) {
        $persen = round(($kualitas / $jumlah) * 100, 1);
      }

      $kelas_list[] = [
        'id' => $row['kelas_id'],
        'nama' => $row['nama_kelas'],
        'wali' => $row['wali_kelas'] ?: 'Belum ada',
        'jumlah' => $jumlah,
        'kualitas' => $kualitas,
        'persen' => $persen,
        // Debug values (float)
        'debug_konfirmasi' => $kualitas
      ];
    }
  } else {
    // Jika tidak ada data, buat data dummy untuk testing
    $kelas_list[] = [
      'id' => 'TEST',
      'nama' => 'Kelas Test',
      'wali' => 'Guru Test',
      'jumlah' => 30,
      'kualitas' => 25,
      'persen' => 83.3,
      'debug_konfirmasi' => 25
    ];
  }

  // Prepare template variables
  $site_name_esc = htmlspecialchars($site_name);
  $site_favicon_esc = htmlspecialchars($site_favicon);

  // Generate PDF using mPDF
  try {
    $mpdf = new \Mpdf\Mpdf([
      'format' => 'A4',
      'margin_left' => 15,
      'margin_right' => 15,
      'margin_top' => 20,
      'margin_bottom' => 20,
      'margin_header' => 10,
      'margin_footer' => 10
    ]);

    $mpdf->SetTitle('Progres Data Kelas - ' . $site_name_esc);

    // Build HTML content for PDF
    $html = '
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
      
      body { 
        font-family: "Inter", "Segoe UI", -apple-system, BlinkMacSystemFont, sans-serif; 
        font-size: 11px; 
        color: #0f172a; 
        line-height: 1.5;
        background: #ffffff;
      }
      
      .header { 
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #06b6d4 100%); 
        color: white; 
        padding: 32px 24px; 
        margin-bottom: 32px; 
        text-align: center; 
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
      }
      
      .header h1 { 
        margin: 0 0 12px 0; 
        font-size: 32px; 
        font-weight: 700; 
        letter-spacing: -0.025em;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      
      .header-info { 
        font-size: 16px; 
        opacity: 0.95; 
        font-weight: 400;
        letter-spacing: 0.01em;
      }
      
      .print-info { 
        text-align: right; 
        margin-bottom: 24px; 
        font-size: 10px; 
        color: #64748b; 
        font-weight: 500;
        padding: 12px 16px;
        background: #f8fafc;
        border-radius: 8px;
        border-left: 4px solid #6366f1;
      }
      
      .table-wrapper {
        background: #ffffff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
      }
      
      table { 
        width: 100%; 
        border-collapse: collapse; 
        font-size: 11px;
      }
      
      thead th { 
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%); 
        color: white; 
        padding: 16px 12px; 
        text-align: left; 
        font-weight: 600; 
        font-size: 10px; 
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #475569;
      }
      
      tbody tr:nth-child(even) {
        background: #f8fafc;
      }
      
      tbody tr:hover {
        background: #f1f5f9;
      }
      
      tbody td { 
        padding: 14px 12px; 
        border-bottom: 1px solid #e2e8f0; 
        font-size: 11px;
        vertical-align: middle;
      }
      
      .badge { 
        display: inline-block !important;
        padding: 8px 12px !important; 
        border-radius: 15px !important; 
        font-size: 10px !important; 
        font-weight: bold !important;
        text-align: center !important;
        color: white !important;
        min-width: 50px !important;
        vertical-align: middle !important;
      }
      
      .badge.green { 
        background-color: #059669 !important;
      }
      
      .badge.orange { 
        background-color: #d97706 !important;
      }
      
      .badge.red { 
        background-color: #dc2626 !important;
      }
      
      .badge.gray { 
        background-color: #6b7280 !important;
      }
      
      .text-center { text-align: center; }
      
      .font-mono { font-family: "SF Mono", "Monaco", "Cascadia Code", "Roboto Mono", monospace; }
      
      .font-semibold { font-weight: 600; }
      
      .no-data { 
        text-align: center; 
        padding: 48px 24px; 
        color: #64748b; 
        font-style: italic;
        font-size: 14px;
        background: #f8fafc;
      }
      
      .row-number {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 10px;
        box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
      }
      
      .kelas-id {
        background: #f1f5f9;
        padding: 4px 8px;
        border-radius: 6px;
        font-family: "SF Mono", monospace;
        font-weight: 500;
        color: #475569;
        border: 1px solid #e2e8f0;
      }
      
    </style>
    
    <div class="header">
      <h1>Progres Data Kelas</h1>
      <div class="header-info">' . $site_name_esc . '</div>
    </div>
    
    <div class="print-info">
      <strong>Dicetak:</strong> ' . date('d F Y, H:i') . ' WIB
    </div>
    
    <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th style="width:10%;" class="text-center">No</th>
          <th style="width:20%;">Nama Kelas</th>
          <th style="width:50%;">Nama Wali Kelas</th>
          <th style="width:10%;" class="text-center">Jumlah</th>
          <th style="width:10%;" class="text-center">Kualitas</th>
        </tr>
      </thead>
      <tbody>';

    if (!empty($kelas_list)) {
      $no = 1;
      foreach ($kelas_list as $k) {
        // determine badge color: red <50, orange 50-80, green >80; gray when no students
        $pct = floatval($k['persen']);
        $jumlah_siswa = intval($k['jumlah']);

        if ($jumlah_siswa === 0) {
          $badge_class = 'gray';
          $badge_text = 'N/A';
          $badge_style = 'background-color: #6b7280; color: white; padding: 8px 12px; border-radius: 15px; font-size: 10px; font-weight: bold; min-width: 50px; text-align: center; display: inline-block;';
        } elseif ($pct < 50) {
          $badge_class = 'red';
          $badge_text = $k['persen'] . '%';
          $badge_style = 'background-color: #dc2626; color: white; padding: 8px 12px; border-radius: 15px; font-size: 10px; font-weight: bold; min-width: 50px; text-align: center; display: inline-block;';
        } elseif ($pct <= 80) {
          $badge_class = 'orange';
          $badge_text = $k['persen'] . '%';
          $badge_style = 'background-color: #d97706; color: white; padding: 8px 12px; border-radius: 15px; font-size: 10px; font-weight: bold; min-width: 50px; text-align: center; display: inline-block;';
        } else {
          $badge_class = 'green';
          $badge_text = $k['persen'] . '%';
          $badge_style = 'background-color: #059669; color: white; padding: 8px 12px; border-radius: 15px; font-size: 10px; font-weight: bold; min-width: 50px; text-align: center; display: inline-block;';
        }

        $html .= '
        <tr>
          <td class="text-center font-semibold">' . $no++ . '</td>
          <td class="font-semibold">' . htmlspecialchars($k['nama']) . '</td>
          <td>' . htmlspecialchars($k['wali']) . '</td>
          <td class="text-center font-semibold">' . $jumlah_siswa . '</td>
          <td class="text-center"><span style="' . $badge_style . '">' . $badge_text . '</span></td>
        </tr>';
      }
    } else {
      $html .= '
      <tr>
        <td colspan="5" class="no-data">
          <div style="font-size: 16px; margin-bottom: 8px;">📋</div>
          Data kelas tidak ditemukan
        </td>
      </tr>';
    }

    $html .= '
      </tbody>
    </table>
    </div>';

    $mpdf->WriteHTML($html);

    // Set filename with timestamp
    $filename = 'Progres_SAE_per_Kelas_' . date('Y-m-d_H-i-s') . '.pdf';

    // Output PDF for download
    $mpdf->Output($filename, 'D');
    exit;
  } catch (Exception $e) {
    // Fallback if mPDF fails - show error message
    echo '<div style="color:red;font-weight:bold;padding:20px;text-align:center;">';
    echo '<h3>Gagal Generate PDF</h3>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Pastikan mPDF sudah terinstall dengan command: <code>composer require mpdf/mpdf</code></p>';
    echo '<a href="javascript:history.back();" style="color:#2563eb;">← Kembali</a>';
    echo '</div>';
    exit;
  }
}
