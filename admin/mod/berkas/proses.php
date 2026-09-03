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
    case 'validasi_dokumen':
      // Per-document validation: each dokumen (kk, ijazah, akte, kip, kks, kis) gets its own status and keterangan
      error_log("Validasi per dokumen request: " . json_encode($_POST));

      $user_id = $_POST['user_id'] ?? '';
      $dokumen_data = $_POST['dokumen'] ?? []; // array of {jenis, status, keterangan}

      if (empty($user_id) || empty($dokumen_data) || !is_array($dokumen_data)) {
        echo "Data tidak valid.";
        exit;
      }

      // Cek apakah user_id ada
      $check_user = $connection->prepare("SELECT user_id FROM user WHERE user_id = ? LIMIT 1");
      $check_user->bind_param('s', $user_id);
      $check_user->execute();
      if ($check_user->get_result()->num_rows === 0) {
        $check_user->close();
        echo "User tidak ditemukan.";
        exit;
      }
      $check_user->close();

      $validasi_by = isset($admin_id) ? intval($admin_id) : null;
      $allowed_status = ['', 'valid', 'tidak_valid', 'revisi'];
      $allowed_jenis = ['kk', 'ijazah', 'akte', 'kip', 'kks', 'kis'];

      // Build SET clause for per-document columns
      $set_fields = [];
      $params = [];
      $types = '';

      foreach ($dokumen_data as $d) {
        $jenis = $d['jenis'] ?? '';
        $status_dok = $d['status'] ?? '';
        $ket_dok = trim($d['keterangan'] ?? '');

        if (!in_array($jenis, $allowed_jenis, true)) continue;
        if (!in_array($status_dok, $allowed_status, true)) continue;

        // Status column
        $set_fields[] = "`{$jenis}_valid` = ?";
        $params[] = $status_dok;
        $types .= 's';

        // Keterangan column (save only if not valid)
        $ket_save = ($status_dok === 'valid' || $status_dok === '') ? '' : $ket_dok;
        $set_fields[] = "`{$jenis}_keterangan` = ?";
        $params[] = $ket_save;
        $types .= 's';
      }

      if (empty($set_fields)) {
        echo "Tidak ada data dokumen yang valid.";
        exit;
      }

      // Auto-compute overall status
      // overall = 'valid' if all docs are 'valid', 'tidak_valid' if any is 'tidak_valid', 'revisi' if any is 'revisi', '' otherwise
      // (priority: tidak_valid > revisi > valid > '')
      $all_statuses = [];
      foreach ($dokumen_data as $d) {
        if (in_array($d['jenis'] ?? '', $allowed_jenis, true)) {
          $all_statuses[] = $d['status'] ?? '';
        }
      }
      $overall = '';
      if (in_array('tidak_valid', $all_statuses, true)) {
        $overall = 'tidak_valid';
      } elseif (in_array('revisi', $all_statuses, true)) {
        $overall = 'revisi';
      } elseif (!empty($all_statuses) && !in_array('', $all_statuses, true) && !in_array('tidak_valid', $all_statuses, true) && !in_array('revisi', $all_statuses, true)) {
        $overall = 'valid';
      }

      $set_fields[] = "`validasi_berkas` = ?";
      $params[] = $overall;
      $types .= 's';

      $set_fields[] = "`validasi_by` = ?";
      $params[] = $validasi_by;
      $types .= 'i';

      $set_fields[] = "`updated_at` = NOW()";

      // Check if record exists
      $check_berkas = $connection->prepare("SELECT berkas_id FROM berkas WHERE user_id = ? LIMIT 1");
      $check_berkas->bind_param('s', $user_id);
      $check_berkas->execute();
      $exists = $check_berkas->get_result()->num_rows > 0;
      $check_berkas->close();

      if ($exists) {
        $sql = "UPDATE berkas SET " . implode(", ", $set_fields) . " WHERE user_id = ?";
        $params[] = $user_id;
        $types .= 's';
        $stmt = $connection->prepare($sql);
        if (!$stmt) {
          echo "Error prepare: " . $connection->error;
          exit;
        }
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        $stmt->close();
      } else {
        // Insert new record
        $cols = ['user_id'];
        $vals = [$user_id];
        $col_types = 's';
        $insert_set = [];
        foreach ($set_fields as $sf) {
          if (preg_match('/^`?(\w+)`?\s*=\s*\?$/', $sf, $m)) {
            $insert_set[] = $m[1];
          }
        }
        // Find the values for each column from our params
        $insert_cols = implode(", ", array_map(function($c) { return "`$c`"; }, $insert_set));
        $placeholders = implode(", ", array_fill(0, count($insert_set), '?'));

        $sql = "INSERT INTO berkas (user_id, $insert_cols, created_at, updated_at) VALUES (?, $placeholders, NOW(), NOW())";
        $all_params = [$user_id];
        $all_types = 's';
        // Map params to insert_set order
        $param_idx = 0;
        foreach ($set_fields as $sf) {
          if (preg_match('/^`?(\w+)`?\s*=\s*\?$/', $sf, $m)) {
            $all_params[] = $params[$param_idx];
            $all_types .= $types[$param_idx];
            $param_idx++;
          }
        }

        $stmt = $connection->prepare($sql);
        if (!$stmt) {
          echo "Error prepare: " . $connection->error;
          exit;
        }
        $stmt->bind_param($all_types, ...$all_params);
        $success = $stmt->execute();
        $stmt->close();
      }

      if ($success) {
        error_log("Success: Per-document validation saved for user_id: $user_id");

        // If overall is 'valid', update status_pengajuan perubahan
        if ($overall === 'valid') {
          $q_update = $connection->prepare("UPDATE perubahan SET status_pengajuan = 'Dalam Proses' WHERE user_id = ? AND status_pengajuan = 'Berhasil Dikirim' ORDER BY id DESC LIMIT 1");
          $q_update->bind_param('s', $user_id);
          $q_update->execute();
          $q_update->close();
        }
        echo 'success';
      } else {
        error_log("Error: Gagal menyimpan validasi per dokumen - " . $connection->error);
        echo 'Gagal menyimpan validasi: ' . $connection->error;
      }
      exit;

    case 'validasi_berkas':
      // Legacy: keep for backward compatibility
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
      $stmt_db = $connection->prepare("DELETE FROM berkas WHERE user_id=?");
      $stmt_db->bind_param('s', $user_id);
      if ($stmt_db->execute()) {
        echo 'success';
      } else {
        echo 'Gagal hapus database: ' . $connection->error;
      }
      exit;
  }
}
