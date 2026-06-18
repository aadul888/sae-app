<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  include('../../../library/function.php');
  // mPDF autoload will be attempted when export is requested (via Composer autoload in project root)

  switch (@$_GET['action']) {
    /* ---------- ADD / UPDATE pembaharuan ---------- */
    case 'add':
      $error = array();
      $id = isset($_POST['id']) ? anti_injection($_POST['id']) : '';
  $version = isset($_POST['version']) ? anti_injection($_POST['version']) : '';
  $release_date = isset($_POST['release_date']) ? anti_injection($_POST['release_date']) : '';
  $mandatory = isset($_POST['mandatory']) ? anti_injection($_POST['mandatory']) : 'N';
  $download_link = isset($_POST['download_link']) ? anti_injection($_POST['download_link']) : '';
  $pembaharuan = isset($_POST['pembaharuan']) ? anti_injection($_POST['pembaharuan']) : '';
  $perbaikan = isset($_POST['perbaikan']) ? anti_injection($_POST['perbaikan']) : '';

      if (empty($version)) {
        $error[] = 'Versi tidak boleh kosong';
      }
      if (empty($release_date)) {
        $error[] = 'Tanggal rilis tidak boleh kosong';
      }

      if (empty($error)) {
        if (empty($id)) {
          // insert
          $insert = "INSERT INTO pembaharuan(version, release_date, mandatory, download_link, pembaharuan, perbaikan) VALUES('$version','$release_date','$mandatory','{$download_link}','{$pembaharuan}','{$perbaikan}')";
          if ($connection->query($insert) === false) {
            echo 'Data tidak berhasil disimpan!';
            die($connection->error . __LINE__);
          } else {
            echo 'success';
          }
        } else {
          // update
          $update = "UPDATE pembaharuan SET version='$version', release_date='$release_date', mandatory='$mandatory', download_link='{$download_link}', pembaharuan='{$pembaharuan}', perbaikan='{$perbaikan}' WHERE id='$id'";
          if ($connection->query($update) === false) {
            die($connection->error . __LINE__);
          } else {
            echo 'success';
          }
        }
      } else {
        foreach ($error as $values) {
          echo "$values\n";
        }
      }

      break;
    /* --------------- Export PDF ------------*/
    case 'export_pdf':
      error_reporting(E_ALL); ini_set('display_errors', 1);
      try {
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
        $mpdf->SetTitle('Export Pembaharuan');
        $html = '<h2 style="text-align:center;">Daftar Pembaharuan Aplikasi</h2>';
        $html .= '<table border="1" cellpadding="6" style="width:100%; border-collapse:collapse; font-size:12px;">';
        $html .= '<thead><tr style="background:#f1f1f1;"><th>No</th><th>ID</th><th>Versi</th><th>Tanggal Rilis</th><th>Wajib</th><th>Link</th><th>Deskripsi</th></tr></thead><tbody>';
        $query = "SELECT id, version, release_date, mandatory, download_link, description FROM pembaharuan ORDER BY release_date DESC";
        $result = $connection->query($query);
        $no = 1;
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td style="text-align:center;">' . $no++ . '</td>';
            $html .= '<td style="text-align:center;">' . $row['id'] . '</td>';
            $html .= '<td>' . htmlspecialchars($row['version']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['release_date']) . '</td>';
            $html .= '<td style="text-align:center;">' . ($row['mandatory']=='Y' ? 'Ya' : 'Tidak') . '</td>';
            $html .= '<td>' . ($row['download_link'] ? htmlspecialchars($row['download_link']) : '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['description']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $mpdf->WriteHTML($html);
        $mpdf->Output('pembaharuan_export.pdf', 'D');
        exit;
      } catch (Error $e) {
        echo '<div style="color:red;font-weight:bold;padding:20px;">Error: mPDF belum terinstall. Silakan install dengan <code>composer require mpdf/mpdf</code> di folder project Anda.</div>';
        exit;
      } catch (Exception $e) {
        echo '<div style="color:red;font-weight:bold;padding:20px;">Gagal generate PDF: ' . htmlspecialchars($e->getMessage()) . '</div>';
        exit;
      }
      break;

   /* --------------- Delete ------------*/
    case 'delete':
      $id = anti_injection(epm_decode($_POST['id']));
      $deleted = "DELETE FROM pembaharuan WHERE id='$id'";
      if ($connection->query($deleted) === true) {
        echo 'success';
      } else {
        echo 'Data tidak berhasil dihapus.!';
      }
      break;
  }
}
