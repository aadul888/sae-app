<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once('../../../library/function.php');
  require_once '../../login/user.php';
  include('../../../library/PHPMailer/PHPMailerAutoload.php');

  $iB              = getBrowser();
  $browser          = $iB['name'] . ' ' . $iB['version'];
  $ip              = $_SERVER['REMOTE_ADDR'];
  $time_online     = time();

  switch (@$_GET['action']) {

    /* ---------- ADD  ---------- */
    case 'add':
      $error = array();

      if (empty($_POST['fullname'])) {
        $error[] = 'Nama Lengkap tidak boleh kosong';
      } else {
        $fullname = anti_injection($_POST['fullname']);
      }

      if (empty($_POST['username'])) {
        $error[] = 'Username tidak boleh kosong';
      } else {
        $username = anti_injection($_POST['username']);
      }

      if (empty($_POST['phone'])) {
        $error[] = 'No. email tidak boleh kosong';
      } else {
        $phone = anti_injection($_POST['phone']);
      }

      if (empty($_POST['email'])) {
        $error[] = 'Email tidak boleh kosong';
      } else {
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
          $error[] = "Email yang Anda masukan tidak valid";
        } else {
          $email = htmlentities(strip_tags($_POST['email']));
        }
      }

      if (empty($_POST['password'])) {
        $error[] = 'Password tidak boleh kosong';
      } else {
        $password = htmlentities(strip_tags($_POST['password']));
        $password = password_hash($password, PASSWORD_DEFAULT);
      }

      if (empty($_POST['level'])) {
        $error[] = 'Level tidak boleh kosong';
      } else {
        $level = anti_injection($_POST['level']);
      }

      if (empty($_POST['active'])) {
        $active = 'N';
      } else {
        $active = 'Y';
      }


      if (empty($error)) {

        // Proses tugas tambahan (multiselect, format: level_id atau level_id:jurusan_id)
        $tugas_tambahan = '';
        if (isset($_POST['tugas_tambahan']) && is_array($_POST['tugas_tambahan'])) {
          $tugas_tambahan_arr = array_filter($_POST['tugas_tambahan'], function($v) { return preg_match('/^\d+(:\d+)?$/', $v); });
          $tugas_tambahan = implode(',', $tugas_tambahan_arr);
        }

        $stmt_chk = $connection->prepare("SELECT email FROM admin WHERE email=? LIMIT 1");
        $stmt_chk->bind_param('s', $email);
        $stmt_chk->execute();
        $stmt_chk->store_result();
        if ($stmt_chk->num_rows < 1) {
          $stmt_chk->close();
          $stmt_ins = $connection->prepare("INSERT INTO admin (fullname, username, phone, email, password, avatar, registrasi_date, tanggal_login, time, status, level_id, tugas_tambahan, ip, browser, active) VALUES (?, ?, ?, ?, ?, 'avatar.jpg', ?, ?, ?, 'Offline', ?, ?, ?, ?, ?)");
          $reg_date = "$date $time";
          $stmt_ins->bind_param('ssssssssissss', $fullname, $username, $phone, $email, $password, $reg_date, $reg_date, $time_online, $level, $tugas_tambahan, $ip, $browser, $active);
          if ($stmt_ins->execute()) {
            $stmt_ins->close();
            echo 'success';
          } else {
            $stmt_ins->close();
            die($connection->error . __LINE__);
          }
        } else {
          $stmt_chk->close();
          echo 'Sepertinya Email "' . $email . '" sudah terdaftar!';
        }
      } else {
        foreach ($error as $key => $values) {
          echo "$values\n";
        }
      }

      /* -------------- Update ----------*/
      break;
    case 'update':
      $error = array();
      if (empty($_POST['id'])) {
        $error[] = 'ID tidak ditemukan';
      } else {
        $id = epm_decode($_POST['id']);
      }

      if (empty($_POST['fullname'])) {
        $error[] = 'Nama Lengkap tidak boleh kosong';
      } else {
        $fullname = $_POST['fullname'];
      }

      if (empty($_POST['username'])) {
        $error[] = 'Username tidak boleh kosong';
      } else {
        $username = $_POST['username'];
      }

      if (empty($_POST['phone'])) {
        $error[] = 'No. email tidak boleh kosong';
      } else {
        $phone = $_POST['phone'];
      }

      if (empty($_POST['email'])) {
        $error[] = 'Email tidak boleh kosong';
      } else {
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
          $error[] = "Email yang Anda masukan tidak valid";
        } else {
          $email = $_POST['email'];
        }
      }

      if (empty($_POST['level'])) {
        $error[] = 'Level tidak boleh kosong';
      } else {
        $level = $_POST['level'];
      }

      if (empty($_POST['active'])) {
        $active = 'N';
      } else {
        $active = 'Y';
      }

      // Proses tugas tambahan (multiselect)
      $tugas_tambahan = '';
      if (isset($_POST['tugas_tambahan']) && is_array($_POST['tugas_tambahan'])) {
        $tugas_tambahan_arr = array_filter($_POST['tugas_tambahan'], function($v) { return preg_match('/^\d+(:\d+)?$/', $v); });
        $tugas_tambahan = implode(',', $tugas_tambahan_arr);
      }

      if (empty($error)) {
        $stmt_upd = $connection->prepare("UPDATE admin SET fullname=?, username=?, phone=?, email=?, level_id=?, tugas_tambahan=?, active=? WHERE admin_id=?");
        $stmt_upd->bind_param('ssssssss', $fullname, $username, $phone, $email, $level, $tugas_tambahan, $active, $id);
        if ($stmt_upd->execute()) {
          $stmt_upd->close();
          echo 'success';
        } else {
          $stmt_upd->close();
          die($connection->error . __LINE__);
        }
      } else {
        foreach ($error as $key => $values) {
          echo "$values\n";
        }
      }
      break;

      /* ----------------- Forgot/Resset Password -----------*/
      /** Setactive user */
      
    case 'forgot':
      $id = epm_decode($_POST['id']);
      $new_password = password_hash('123456', PASSWORD_DEFAULT);
      $stmt = $connection->prepare("UPDATE admin SET password=? WHERE admin_id=?");
      $stmt->bind_param('ss', $new_password, $id);
      if ($stmt->execute()) {
        $stmt->close();
        echo 'success';
      } else {
        $stmt->close();
        echo 'Gagal mereset password.';
      }
      break;
    case 'active':
      $id = $_POST['id'];
      $active = $_POST['active'];
      $stmt = $connection->prepare("UPDATE admin SET active=? WHERE admin_id=?");
      $stmt->bind_param('ss', $active, $id);
      if ($stmt->execute()) {
        $stmt->close();
        echo 'success';
      } else {
        $stmt->close();
        echo 'error';
        die($connection->error . __LINE__);
      }
      break;
    case 'delete':
      $id = epm_decode($_POST['id']);
      /* Script Delete Foto Lama dan Qr Code ------------*/
      $stmt_foto = $connection->prepare("SELECT avatar FROM admin WHERE admin_id=?");
      $stmt_foto->bind_param('s', $id);
      $stmt_foto->execute();
      $result = $stmt_foto->get_result();
      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt_foto->close();
        $avatar_delete = $row['avatar'];
        $tmpfile_avatar = "./assets/avatar/" . $avatar_delete;
        if (file_exists("./assets/avatar/$avatar_delete")) {
          if ($avatar_delete == 'avatar.jpg') {
            /**avatar default tidak kehapus */
          } else {
            /** avatar udah diubah maka hapus */
            unlink($tmpfile_avatar);
          }
        }
      } else { $stmt_foto->close(); }
      /* Script Delete Data ------------*/
      $stmt_del = $connection->prepare("DELETE FROM admin WHERE admin_id=?");
      $stmt_del->bind_param('s', $id);
      if ($stmt_del->execute()) {
        $stmt_del->close();
        echo 'success';
      } else {
        $stmt_del->close();
        echo 'Data tidak berhasil dihapus.!';
        die($connection->error . __LINE__);
      }
      break;
      /* Script Delete Data ------------*/
      $deleted  = "DELETE FROM admin WHERE admin_id='$id'";
      if ($connection->query($deleted) === true) {
        echo 'success';
      } else {
        //tidak berhasil
        echo 'Data tidak berhasil dihapus.!';
        die($connection->error . __LINE__);
      }

      break;

    // ----------- Import Admin -----------
    case 'import':
      $logFile = __DIR__ . '/import_admin_debug.log';
      if (defined('APP_DEBUG') && APP_DEBUG) {
        file_put_contents($logFile, "\n==== " . date('Y-m-d H:i:s') . " ====" . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, "POST: " . print_r($_POST, 1) . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, "FILES: " . print_r($_FILES, 1) . PHP_EOL, FILE_APPEND);
      }
      if (isset($_FILES['importFile']) && $_FILES['importFile']['error'] == 0) {
        if (!file_exists($_FILES['importFile']['tmp_name'])) {
          file_put_contents($logFile, "File upload tidak ditemukan di server." . PHP_EOL, FILE_APPEND);
          echo 'File upload tidak ditemukan di server.';
          exit;
        }
        file_put_contents($logFile, "File upload ditemukan: " . $_FILES['importFile']['tmp_name'] . PHP_EOL, FILE_APPEND);
        $autoloadPath = '../../../admin/assets/vendor/autoload.php';
        $bootstrapPath = '../../../admin/assets/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $autoloaded = false;
        if (file_exists($autoloadPath)) {
          require_once $autoloadPath;
          file_put_contents($logFile, "Berhasil require autoload.php" . PHP_EOL, FILE_APPEND);
          $autoloaded = true;
        } elseif (file_exists($bootstrapPath)) {
          require_once $bootstrapPath;
          file_put_contents($logFile, "Berhasil require Bootstrap.php" . PHP_EOL, FILE_APPEND);
          $autoloaded = true;
        } else {
          file_put_contents($logFile, "Gagal menemukan autoload.php maupun Bootstrap.php" . PHP_EOL, FILE_APPEND);
          echo 'Gagal menemukan autoload.php maupun Bootstrap.php';
          exit;
        }
        $fileTmpPath = $_FILES['importFile']['tmp_name'];
        try {
          $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
        } catch (Exception $e) {
          file_put_contents($logFile, "Gagal membaca file Excel: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
          echo 'Gagal membaca file Excel: ' . $e->getMessage();
          exit;
        }
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        file_put_contents($logFile, "Rows: " . print_r($rows, 1) . PHP_EOL, FILE_APPEND);
        if (empty($rows) || !isset($rows[0])) {
          file_put_contents($logFile, "File Excel kosong atau tidak terbaca." . PHP_EOL, FILE_APPEND);
          echo 'File Excel kosong atau tidak terbaca.';
          exit;
        }
        $header = array_map('strtolower', $rows[0]);
        $expected_columns = ['fullname', 'email', 'username', 'password', 'phone', 'level'];
        foreach ($expected_columns as $col) {
          if (!in_array($col, $header)) {
            file_put_contents($logFile, "Kolom $col tidak ditemukan. Header: " . implode(', ', $header) . PHP_EOL, FILE_APPEND);
            echo 'Kolom ' . strtoupper($col) . ' wajib ada di file. Kolom ditemukan: ' . implode(', ', $header);
            exit;
          }
        }
        $fullname_idx = array_search('fullname', $header);
        $email_idx = array_search('email', $header);
        $username_idx = array_search('username', $header);
        $password_idx = array_search('password', $header);
        $phone_idx = array_search('phone', $header);
        $level_idx = array_search('level', $header);
        $data = [];
        for ($i = 1; $i < count($rows); $i++) {
          $fullname = trim($rows[$i][$fullname_idx]);
          $email = trim($rows[$i][$email_idx]);
          $username = trim($rows[$i][$username_idx]);
          $phone = trim($rows[$i][$phone_idx]);
          $level = trim($rows[$i][$level_idx]);
          if ($fullname == '' || $email == '' || $username == '' || $phone == '' || $level == '') continue;
          $password_val = '';
          if ($password_idx !== false) {
            $password_val = trim($rows[$i][$password_idx]);
          }
          $row_data = array(
            'fullname' => $connection->real_escape_string($fullname),
            'email' => $connection->real_escape_string($email),
            'username' => $connection->real_escape_string($username),
            'phone' => $connection->real_escape_string($phone),
            'level_id' => $connection->real_escape_string($level),
            'avatar' => 'avatar.jpg',
            'registrasi_date' => date('Y-m-d H:i:s'),
            'tanggal_login' => date('Y-m-d H:i:s'),
            'time' => time(),
            'status' => 'Offline',
            'ip' => $ip,
            'browser' => $browser,
            'active' => 'Y'
          );
          // Password hanya diupdate jika diisi di Excel, jika kosong tetap pakai password lama
          if ($password_val != '') {
            $row_data['password'] = password_hash($password_val, PASSWORD_DEFAULT);
          }
          $data[] = $row_data;
        }
        file_put_contents($logFile, "Data siap insert: " . print_r($data, 1) . PHP_EOL, FILE_APPEND);
        if (count($data) == 0) {
          file_put_contents($logFile, "Tidak ada data valid untuk diimport." . PHP_EOL, FILE_APPEND);
          echo 'Tidak ada data valid untuk diimport.';
          exit;
        }
        $connection->begin_transaction();
        try {
          $batchSize = 20;
          $import_columns = array_keys($data[0]);
          $col_sql = '`' . implode('`,`', $import_columns) . '`';
          $rowCount = count($data);
          for ($i = 0; $i < $rowCount; $i += $batchSize) {
            $batch = array_slice($data, $i, $batchSize);
            $values = [];
            foreach ($batch as $row) {
              $vals = [];
              foreach ($import_columns as $col) {
                if (array_key_exists($col, $row)) {
                  $vals[] = "'" . $connection->real_escape_string($row[$col]) . "'";
                } else {
                  $vals[] = "NULL";
                }
              }
              $values[] = '(' . implode(',', $vals) . ')';
            }
            // Build update SQL: update semua field kecuali email, dan password hanya jika diisi
            $update_sql = [];
            foreach ($import_columns as $col) {
              if ($col == 'email') continue;
              if ($col == 'password') {
                $update_sql[] = "`password`=IF(VALUES(`password`) IS NULL, `password`, VALUES(`password`))";
              } else {
                $update_sql[] = "`$col`=VALUES(`$col`)";
              }
            }
            $update_sql_str = implode(',', $update_sql);
            $sql = "INSERT INTO admin ($col_sql) VALUES " . implode(',', $values) .
              " ON DUPLICATE KEY UPDATE $update_sql_str";
            $qres = $connection->query($sql);
            if ($qres === false) {
              file_put_contents($logFile, "SQL ERROR: " . $connection->error . PHP_EOL, FILE_APPEND);
              throw new Exception($connection->error);
            }
          }
          $connection->commit();
          file_put_contents($logFile, "Import berhasil." . PHP_EOL, FILE_APPEND);
          echo 'Import berhasil.';
        } catch (Exception $e) {
          $connection->rollback();
          file_put_contents($logFile, "Gagal import: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
          echo 'Gagal import: ' . $e->getMessage();
        }
      } else {
        file_put_contents($logFile, "File tidak ditemukan atau error upload." . PHP_EOL, FILE_APPEND);
        echo 'File tidak ditemukan atau error upload.';
      }
      break;
  }
}
