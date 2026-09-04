<?php session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once __DIR__ . '/../../../library/config.php';
  require_once __DIR__ . '/../../../library/function.php';
  require_once __DIR__ . '/../../login/user.php';
  $modul_route = 'jurusan';
  include __DIR__ . '/../check_role.php';

  switch (@$_GET['action']) {
    case 'update_meta':
      if ($data_role['modifikasi'] != 'Y') {
        echo 'Anda tidak punya akses tambah/edit.';
        exit;
      }
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

      if (isset($_FILES['logo_jurusan']) && $_FILES['logo_jurusan']['error'] === UPLOAD_ERR_OK) {
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
        $saved = content_upload($file['tmp_name'], $logo_filename, 'assets/logo-jurusan');
        if (!$saved) {
          echo 'Gagal upload logo jurusan! Periksa permission folder content di server hosting.';
          exit;
        }

        // Pastikan kolom logo menggunakan LONGTEXT agar muat menampung gambar base64 (hingga 16MB+)
        if (str_starts_with($saved, 'data:')) {
          @$connection->query("ALTER TABLE jurusan MODIFY COLUMN logo LONGTEXT NULL");
          $logo_db = mysqli_real_escape_string($connection, $saved);
        } else {
          $logo_db = mysqli_real_escape_string($connection, $logo_filename);
        }
        $update_parts[] = "logo='$logo_db'";
      } else if (isset($_FILES['logo_jurusan']) && $_FILES['logo_jurusan']['error'] !== UPLOAD_ERR_NO_FILE) {
        echo 'Terjadi kesalahan saat mengunggah file logo (Error code: ' . $_FILES['logo_jurusan']['error'] . ')';
        exit;
      }

      $update = "UPDATE jurusan SET " . implode(', ', $update_parts) . " WHERE jurusan_id='$id_esc'";
      if ($connection->query($update) === false) {
        echo 'Gagal menyimpan perubahan jurusan: ' . mysqli_error($connection);
      } else {
        echo 'success';
      }
      break;

    case 'add':
      if ($data_role['modifikasi'] != 'Y') {
        echo 'Anda tidak punya akses tambah/edit.';
        break;
      }
      echo 'Aksi tambah jurusan dinonaktifkan. Data jurusan otomatis dari sinkronisasi Dapodik.';
      break;

    case 'delete':
      if ($data_role['hapus'] != 'Y') {
        echo 'Anda tidak punya akses hapus.';
        break;
      }
      echo 'Aksi hapus jurusan dinonaktifkan. Data jurusan otomatis dari sinkronisasi Dapodik.';
      break;
  }
}
