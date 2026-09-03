<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once('../../../library/function.php');
  require_once '../../login/user.php';

  $modul_id = 11;
  include __DIR__ . '/../check_role.php';

  function check_access($type)
  {
    global $data_role;
    if (!isset($data_role[$type]) || $data_role[$type] != 'Y') {
      echo 'Akses ditolak: Anda tidak memiliki hak akses yang diperlukan.';
      exit;
    }
  }

  switch (@$_GET['action']) {

      /* ---------- ADD / UPDATE RFID ---------- */
    case 'add':
      check_access('modifikasi');
      $error = array();
      // Ambil NISN dari hidden input jika ada, jika tidak dari input utama
      $nisn = isset($_POST['nisn_hidden']) && $_POST['nisn_hidden'] ? anti_injection($_POST['nisn_hidden']) : anti_injection($_POST['nisn']);
      $rfid = anti_injection($_POST['rfid']);  // Ambil RFID baru dari form

      if (empty($nisn)) {
        $error[] = 'Silakan pilih siswa!';
      }
      if (empty($rfid)) {
        $error[] = 'RFID tidak boleh kosong!';
      }

      if (empty($error)) {
        // Update RFID berdasarkan NISN
        $update = "UPDATE user SET rfid='$rfid' WHERE nisn='$nisn'";
        if ($connection->query($update) === false) {
          echo 'RFID tidak berhasil diperbarui!';
        } else {
          echo 'success';
        }
      } else {
        foreach ($error as $values) {
          echo "$values\n";
        }
      }
      break;

      /* --------------- DELETE RFID ------------*/
    case 'delete':
      check_access('hapus');
      $nisn = anti_injection($_POST['nisn']);
      if (empty($nisn)) {
        echo 'NISN tidak ditemukan!';
        exit;
      }
      $delete = "UPDATE user SET rfid=NULL WHERE nisn='$nisn'";
      if ($connection->query($delete) === true) {
        echo 'success';
      } else {
        echo 'RFID tidak berhasil dihapus!';
      }
      break;
  }
}