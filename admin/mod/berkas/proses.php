<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  switch (@$_GET['action']) {
    case 'validasi_berkas':
      // Log untuk debugging
      error_log("Validasi berkas request: " . json_encode($_POST));

      $user_id = $_POST['user_id'] ?? '';
      $status = $_POST['status'] ?? '';
      // Optional free-text note from admin about validation
      $keterangan = trim($_POST['keterangan'] ?? '');
      $allowed = ['', 'valid', 'tidak_valid', 'revisi'];

      // Validasi input
      if (empty($user_id)) {
        error_log("Error: User ID kosong");
        echo "User ID tidak boleh kosong.";
        exit;
      }

      if (!in_array($status, $allowed, true)) {
        error_log("Error: Status tidak valid - " . $status);
        echo "Status tidak valid: " . htmlspecialchars($status);
        exit;
      }

      // Cek apakah user_id ada di database
      $check_user = $connection->prepare("SELECT user_id FROM user WHERE user_id = ? LIMIT 1");
      $check_user->bind_param('s', $user_id);
      $check_user->execute();
      $result = $check_user->get_result();
      if ($result->num_rows === 0) {
        $check_user->close();
        echo "User tidak ditemukan.";
        exit;
      }
      $check_user->close();

      // Cek apakah record berkas sudah ada
      $check_berkas = $connection->prepare("SELECT berkas_id FROM berkas WHERE user_id = ? LIMIT 1");
      $check_berkas->bind_param('s', $user_id);
      $check_berkas->execute();
      $result_berkas = $check_berkas->get_result();

      // Determine admin who performed validation (from included user.php)
      $validasi_by = isset($admin_id) ? intval($admin_id) : null;

      // If status is empty (belum divalidasi) or 'valid', clear keterangan per request
      $keterangan_to_save = ($status === '' || $status === 'valid') ? '' : $keterangan;

      if ($result_berkas->num_rows > 0) {
        // Update existing record: set status, admin and optional note
        $update = $connection->prepare("UPDATE berkas SET validasi_berkas = ?, validasi_by = ?, keterangan = ?, updated_at = NOW() WHERE user_id = ?");
        $update->bind_param('siss', $status, $validasi_by, $keterangan_to_save, $user_id);
        $success = $update->execute();
        $update->close();
      } else {
        // Insert new record including admin and note
        $insert = $connection->prepare("INSERT INTO berkas (user_id, validasi_berkas, validasi_by, keterangan, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $insert->bind_param('siss', $user_id, $status, $validasi_by, $keterangan_to_save);
        $success = $insert->execute();
        $insert->close();
      }
      $check_berkas->close();

      if ($success) {
        error_log("Success: Status validasi berkas updated untuk user_id: $user_id, status: $status");

        // Jika status validasi_berkas menjadi 'valid', update status_pengajuan perubahan
        if ($status === 'valid') {
          $q_update = $connection->prepare("UPDATE perubahan SET status_pengajuan = 'Dalam Proses' WHERE user_id = ? AND status_pengajuan = 'Berhasil Dikirim' ORDER BY id DESC LIMIT 1");
          $q_update->bind_param('s', $user_id);
          $update_result = $q_update->execute();
          error_log("Perubahan status update result: " . ($update_result ? 'success' : 'failed'));
          $q_update->close();
        }
        echo 'success';
      } else {
        error_log("Error: Gagal menyimpan status validasi - " . $connection->error);
        echo 'Gagal menyimpan status validasi: ' . $connection->error;
      }
      exit;

    case 'delete_berkas':
      $user_id = $_POST['user_id'] ?? '';
      if (!$user_id) {
        echo "Data tidak valid.";
        exit;
      }
      // Ambil nama file lama untuk semua jenis
      $allowed_jenis = ['kk', 'ijazah', 'akte', 'kip', 'kks', 'kis']; // tambahkan 'kis'
      $sql = "SELECT " . implode(", ", $allowed_jenis) . " FROM berkas WHERE user_id = ? LIMIT 1";
      $q = $connection->prepare($sql);
      $q->bind_param('s', $user_id);
      $q->execute();
      $result = $q->get_result();
      $row = $result ? $result->fetch_assoc() : [];
      $q->close();
      // Hapus file fisik di content/berkas
      $folder_berkas = '../../../content/berkas/';
      foreach ($allowed_jenis as $j) {
        if (!empty($row[$j])) {
          $file_path = $folder_berkas . $row[$j];
          if (file_exists($file_path)) {
            @unlink($file_path);
          }
        }
      }
      // Hapus record di database
      $q_delete = $connection->query("DELETE FROM berkas WHERE user_id = '" . $connection->real_escape_string($user_id) . "'");
      if ($q_delete) {
        echo 'success';
      } else {
        echo 'Gagal hapus database: ' . $connection->error;
      }
      exit;
  }
}
