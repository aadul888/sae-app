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
    /* ----------- SETUJUI ----------- */
    case 'setujui':
      $id = anti_injection($_POST['id']);
      // Ambil fullname admin
      $fullname_admin = null;
      $admin_id = null;
      if (isset($_COOKIE['ADMIN_KEY'])) {
        if (function_exists('epm_decode')) {
          $admin_id = anti_injection(epm_decode($_COOKIE['ADMIN_KEY']));
        } else {
          $admin_id = anti_injection($_COOKIE['ADMIN_KEY']);
        }
      }
      if ($admin_id) {
        $admin_query = $connection->query("SELECT fullname FROM admin WHERE admin_id='$admin_id' LIMIT 1");
        if ($admin_query && $admin_query->num_rows > 0) {
          $row_admin = $admin_query->fetch_assoc();
          if (!empty($row_admin['fullname'])) {
            $fullname_admin = $row_admin['fullname'];
          }
        }
      }
      if (!$fullname_admin) {
        $admin_username = isset($_COOKIE['username']) ? anti_injection($_COOKIE['username']) : null;
        if ($admin_username) {
          $admin_query = $connection->query("SELECT fullname FROM admin WHERE username='$admin_username' LIMIT 1");
          if ($admin_query && $admin_query->num_rows > 0) {
            $row_admin = $admin_query->fetch_assoc();
            if (!empty($row_admin['fullname'])) {
              $fullname_admin = $row_admin['fullname'];
            }
          }
        }
      }
      if (!$fullname_admin) {
        echo "Error: Admin tidak ditemukan di tabel admin atau fullname kosong. Pastikan admin_id atau username di cookie sama persis dengan data di tabel admin.";
        exit;
      }

      // Ambil data perubahan, hanya yang statusnya 'Berhasil Dikirim' atau 'Dalam Proses'
      $q = $connection->query("SELECT * FROM perubahan WHERE id='$id' AND (status_pengajuan='Berhasil Dikirim' OR status_pengajuan='Dalam Proses')");
      if ($q->num_rows > 0) {
        $d = $q->fetch_assoc();
        $user_id = $d['user_id'];
        // Cek validasi berkas siswa
        $berkas = $connection->query("SELECT validasi_berkas FROM berkas WHERE user_id='$user_id'");
        if ($berkas && $berkas->num_rows > 0) {
          $b = $berkas->fetch_assoc();
          if (!isset($b['validasi_berkas']) || $b['validasi_berkas'] !== 'valid') {
            echo "Berkas siswa belum valid. Silakan validasi berkas terlebih dahulu.";
            exit;
          }
        } else {
          echo "Berkas siswa belum ada. Silakan upload dan validasi berkas terlebih dahulu.";
          exit;
        }
        // 'jenis_data' column removed from table; do not reference it here
        $keterangan = json_decode($d['keterangan'], true);

        $update_fields = [];
        $allowed = [
          // Identitas
          'nama_lengkap',
          'nisn',
          'no_kk',
          'nik',
          'jenis_kelamin',
          'tempat_lahir',
          'tanggal_lahir',
          'agama',
          'status_keluarga',
          'anak_ke',
          'alamat',
          'rt',
          'rw',
          'desa',
          'kecamatan',
          'kodepos',
          'telp',
          'sekolah_asal',
          'diterima_dikelas',
          'diterima_tanggal',
          'email',
          // Orang tua
          'nik_ayah',
          'nama_ayah',
          'pekerjaan_ayah',
          'nik_ibu',
          'nama_ibu',
          'pekerjaan_ibu',
          // Wali
          'nama_wali',
          'alamat_wali',
          'telp_wali',
          'pekerjaan_wali'
        ];
        foreach ($allowed as $field) {
          if (array_key_exists($field, $keterangan)) {
            $val = $keterangan[$field];
            // If keterangan stores {old:..., new:...}, prefer the new value
            if (is_array($val)) {
              $val = isset($val['new']) ? $val['new'] : $val['old'];
            }
            $update_fields[] = "$field='" . $connection->real_escape_string($val) . "'";
          }
        }
        if (!empty($update_fields)) {
          $sql_update = "UPDATE user SET " . implode(", ", $update_fields) . " WHERE user_id='$user_id'";
          $update = $connection->query($sql_update);
        } else {
          echo "Tidak ada data yang diubah. Pastikan field usulan terisi dan sesuai.";
          exit;
        }

        if ($update) {
          $now = date('Y-m-d H:i:s');
          $alasan_disetujui = 'Disetujui oleh ' . $fullname_admin;
          // Set konfirmasi user ke 'Belum Konfirmasi'
          $connection->query("UPDATE user SET konfirmasi='Belum Konfirmasi' WHERE user_id='$user_id'");
          $connection->query("UPDATE perubahan SET status_pengajuan='Disetujui', alasan_penolakan='$alasan_disetujui', date_processed='$now', processed_by='$fullname_admin' WHERE id='$id'");
          echo "Perubahan berhasil disetujui.";
        } else {
          echo "Gagal memperbarui data user!";
        }
      } else {
        echo "Data tidak valid atau sudah diproses.";
      }
      break;

    /* ----------- TOLAK ----------- */
    case 'tolak':
      $id = anti_injection($_POST['id']);
      $alasan = anti_injection($_POST['alasan']);
      // Ambil fullname admin (ulang agar tidak tergantung scope sebelumnya)
      $fullname_admin = null;
      $admin_id = null;
      if (isset($_COOKIE['ADMIN_KEY'])) {
        if (function_exists('epm_decode')) {
          $admin_id = anti_injection(epm_decode($_COOKIE['ADMIN_KEY']));
        } else {
          $admin_id = anti_injection($_COOKIE['ADMIN_KEY']);
        }
      }
      if ($admin_id) {
        $admin_query = $connection->query("SELECT fullname FROM admin WHERE admin_id='$admin_id' LIMIT 1");
        if ($admin_query && $admin_query->num_rows > 0) {
          $row_admin = $admin_query->fetch_assoc();
          if (!empty($row_admin['fullname'])) {
            $fullname_admin = $row_admin['fullname'];
          }
        }
      }
      if (!$fullname_admin) {
        $admin_username = isset($_COOKIE['username']) ? anti_injection($_COOKIE['username']) : null;
        if ($admin_username) {
          $admin_query = $connection->query("SELECT fullname FROM admin WHERE username='$admin_username' LIMIT 1");
          if ($admin_query && $admin_query->num_rows > 0) {
            $row_admin = $admin_query->fetch_assoc();
            if (!empty($row_admin['fullname'])) {
              $fullname_admin = $row_admin['fullname'];
            }
          }
        }
      }
      if (!$fullname_admin) {
        echo "Error: Admin tidak ditemukan di tabel admin atau fullname kosong. Pastikan admin_id atau username di cookie sama persis dengan data di tabel admin.";
        exit;
      }
      $check = $connection->query("SELECT id, status_pengajuan FROM perubahan WHERE id='$id'");
      if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        if ($row['status_pengajuan'] === 'Berhasil Dikirim' || $row['status_pengajuan'] === 'Dalam Proses') {
          $now = date('Y-m-d H:i:s');
          $alasan_ditolak = 'Ditolak oleh ' . $fullname_admin . ' dengan alasan : ' . htmlspecialchars($alasan);
          $connection->query("UPDATE perubahan SET status_pengajuan='Ditolak', alasan_penolakan='$alasan_ditolak', date_processed='$now', processed_by='$fullname_admin' WHERE id='$id'");
          echo "Pengajuan berhasil ditolak.";
        } else {
          echo "Pengajuan sudah diproses sebelumnya (status: " . htmlspecialchars($row['status_pengajuan']) . ").";
        }
      } else {
        echo "Data tidak ditemukan.";
      }
      break;

    /* ----------- GET STATUS USULAN ----------- */
    case 'get_status':
      $status_file = __DIR__ . '/status.json';
      $res = ['closed' => false];
      if (file_exists($status_file)) {
        $c = @file_get_contents($status_file);
        $j = json_decode($c, true);
        if (is_array($j) && isset($j['closed'])) $res['closed'] = (bool)$j['closed'];
      }
      header('Content-Type: application/json');
      echo json_encode($res);
      break;

    /* ----------- TOGGLE STATUS USULAN (ADMIN) ----------- */
    case 'toggle_status':
      // Simple admin-only protection: require ADMIN_KEY cookie
      if (!isset($_COOKIE['ADMIN_KEY'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'unauthorized']);
        exit;
      }
      $status_file = __DIR__ . '/status.json';
      $closed = null;
      if (isset($_POST['closed'])) {
        $closed = (bool) intval($_POST['closed']);
      }
      $current = false;
      if (file_exists($status_file)) {
        $c = @file_get_contents($status_file);
        $j = json_decode($c, true);
        if (is_array($j) && isset($j['closed'])) $current = (bool)$j['closed'];
      }
      if ($closed === null) $closed = !$current;
      $new = ['closed' => (bool)$closed];
      @file_put_contents($status_file, json_encode($new));
      header('Content-Type: application/json');
      echo json_encode($new);
      break;

    default:
      echo "Aksi tidak dikenali.";
      break;

    case 'detail':
      $id = anti_injection($_POST['id']);
      $query = $connection->query("SELECT * FROM perubahan WHERE id='$id'");
      if ($query->num_rows > 0) {
        $data = $query->fetch_assoc();
        $user_id = $data['user_id'];
        $user_query = $connection->query("SELECT * FROM user WHERE user_id='$user_id'");
        if ($user_query->num_rows > 0) {
          $user = $user_query->fetch_assoc();
        } else {
          echo '<div class="alert alert-danger">Data user lama tidak ditemukan.</div>';
          exit;
        }
        // Robustly decode keterangan which might be JSON, double-encoded JSON, or already an array
        $raw_keterangan = $data['keterangan'];
        $keterangan = json_decode($raw_keterangan, true);
        if (!is_array($keterangan)) {
          // If first decode returns a string (double-encoded) or null, try again
          if (is_string($keterangan)) {
            $try = json_decode($keterangan, true);
            if (is_array($try)) $keterangan = $try;
          } else {
            // Try to decode the raw payload repeatedly (safety)
            $try2 = json_decode($raw_keterangan, true);
            if (is_array($try2)) $keterangan = $try2;
          }
        }
        if (!is_array($keterangan)) $keterangan = [];

        // Define field groups mapping (was missing) so detail.php can render all fields
        $fieldGroups = [
          'Identitas Siswa' => [
            'Nama Lengkap' => 'nama_lengkap',
            'NISN' => 'nisn',
            'No. KK' => 'no_kk',
            'NIK' => 'nik',
            'Jenis Kelamin' => 'jenis_kelamin',
            'Tempat Lahir' => 'tempat_lahir',
            'Tanggal Lahir' => 'tanggal_lahir',
            'Agama' => 'agama',
            'Status Keluarga' => 'status_keluarga',
            'Anak Ke' => 'anak_ke',
            'Alamat' => 'alamat',
            'RT' => 'rt',
            'RW' => 'rw',
            'Desa/Kelurahan' => 'desa',
            'Kecamatan' => 'kecamatan',
            'Kode Pos' => 'kodepos',
            'Telepon' => 'telp',
            'Sekolah Asal' => 'sekolah_asal',
            'Diterima di Kelas' => 'diterima_dikelas',
            'Tanggal Diterima' => 'diterima_tanggal',
            'Email' => 'email'
          ],
          'Data Orang Tua' => [
            'NIK Ayah' => 'nik_ayah',
            'Nama Ayah' => 'nama_ayah',
            'Pekerjaan Ayah' => 'pekerjaan_ayah',
            'NIK Ibu' => 'nik_ibu',
            'Nama Ibu' => 'nama_ibu',
            'Pekerjaan Ibu' => 'pekerjaan_ibu'
          ],
          'Data Wali' => [
            'Nama Wali' => 'nama_wali',
            'Alamat Wali' => 'alamat_wali',
            'Telepon Wali' => 'telp_wali',
            'Pekerjaan Wali' => 'pekerjaan_wali'
          ]
        ];

        // Include the detail template
        include 'detail.php';
      } else {
        echo '<div class="alert alert-warning">Data tidak ditemukan.</div>';
      }
      break;


    /* ----------- HAPUS ----------- */
    case 'hapus':
      $id = anti_injection($_POST['id']);

      // Cek apakah data ada
      $check = $connection->query("SELECT id FROM perubahan WHERE id='$id'");
      if ($check->num_rows > 0) {
        // Lakukan penghapusan
        $delete = $connection->query("DELETE FROM perubahan WHERE id='$id'");
        if ($delete) {
          echo "Data berhasil dihapus.";
        } else {
          echo "Gagal menghapus data.";
        }
      } else {
        echo "Data tidak ditemukan.";
      }
      break;

    case 'edit_catatan':
      $id = anti_injection($_POST['id']);
      $catatan = anti_injection($_POST['catatan']);

      $check = $connection->query("SELECT id FROM perubahan WHERE id='$id' AND status_pengajuan='Ditolak'");
      if ($check->num_rows > 0) {
        $update = $connection->query("UPDATE perubahan SET alasan_penolakan='$catatan' WHERE id='$id'");
        echo $update ? "Catatan berhasil diperbarui." : "Gagal memperbarui catatan.";
      } else {
        echo "Data tidak ditemukan atau status tidak sesuai.";
      }
      break;
  }
}
