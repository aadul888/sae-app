<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  include('../../../library/function.php');
  require_once '../../login/user.php';

  switch (@$_GET['action']) {
    case 'update_meta':
      $id = isset($_POST['id']) ? anti_injection($_POST['id']) : '';
      $kode_jurusan = isset($_POST['kode_jurusan']) ? trim(anti_injection($_POST['kode_jurusan'])) : '';

      if (empty($id)) {
        echo 'ID jurusan tidak ditemukan';
        exit;
      }
      if (empty($kode_jurusan)) {
        echo 'Kode jurusan tidak boleh kosong';
        exit;
      }

      $id_esc = mysqli_real_escape_string($connection, $id);
      $kode_esc = mysqli_real_escape_string($connection, $kode_jurusan);

      $cek = $connection->query("SELECT jurusan_id FROM jurusan WHERE jurusan_id='$id_esc' LIMIT 1");
      if (!$cek || $cek->num_rows === 0) {
        echo 'Data jurusan tidak ditemukan';
        exit;
      }

      $update_parts = ["kode_jurusan='$kode_esc'"];

      if (isset($_FILES['logo_jurusan']) && $_FILES['logo_jurusan']['error'] === 0) {
        $logo_folder = '../../../content/assets/logo-jurusan/';
        if (!file_exists($logo_folder)) {
          mkdir($logo_folder, 0777, true);
        }

        $file = $_FILES['logo_jurusan'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'png') {
          echo 'Logo jurusan harus berupa file PNG!';
          exit;
        }
        if ($file['size'] > 1048576) {
          echo 'Ukuran logo jurusan maksimal 1MB!';
          exit;
        }

        $logo_filename = $id . '.png';
        if (!move_uploaded_file($file['tmp_name'], $logo_folder . $logo_filename)) {
          echo 'Gagal upload logo jurusan!';
          exit;
        }

        $logo_db = mysqli_real_escape_string($connection, $logo_filename);
        $update_parts[] = "logo='$logo_db'";
      }

      $update = "UPDATE jurusan SET " . implode(', ', $update_parts) . " WHERE jurusan_id='$id_esc'";
      if ($connection->query($update) === false) {
        echo 'Gagal menyimpan perubahan jurusan';
      } else {
        echo 'success';
      }
      break;

    case 'add':
    case 'delete':
      echo 'Aksi tambah/edit/hapus jurusan dinonaktifkan. Data jurusan otomatis dari sinkronisasi Dapodik.';
      break;
  }
}
