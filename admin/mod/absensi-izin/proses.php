<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $modul_id = 10;
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

    /* ----------- SETUJUI ----------- */
    case 'setujui':
      check_access('modifikasi');
      $id = anti_injection($_POST['id']);

      $q = $connection->query("SELECT * FROM izin WHERE id='$id' AND status_izin='Menunggu'");
      if ($q->num_rows > 0) {
        $connection->query("UPDATE izin SET status_izin='Disetujui', alasan_penolakan='' WHERE id='$id'");
        echo "Izin berhasil disetujui.";
      } else {
        echo "Data tidak ditemukan atau sudah diproses.";
      }
      break;

    /* ----------- TOLAK ----------- */
    case 'tolak':
      check_access('modifikasi');
      $id = anti_injection($_POST['id']);
      $alasan = anti_injection($_POST['alasan']);

      $check = $connection->query("SELECT id FROM izin WHERE id='$id' AND status_izin='Menunggu'");
      if ($check->num_rows > 0) {
        $connection->query("UPDATE izin SET status_izin='Ditolak', alasan_penolakan='$alasan' WHERE id='$id'");
        echo "Pengajuan izin ditolak.";
      } else {
        echo "Data tidak valid atau sudah diproses.";
      }
      break;

    /* ----------- DETAIL IZIN ----------- */
    case 'detail':
      check_access('lihat');
      $id = anti_injection($_POST['id']);
      $query = $connection->query("SELECT izin.*, user.nama_lengkap, user.nisn, kelas.nama_kelas FROM izin
        LEFT JOIN user ON izin.user_id = user.user_id
        LEFT JOIN kelas ON user.kelas = kelas.kelas_id
        WHERE izin.id='$id'");
      if ($query->num_rows > 0) {
        $data = $query->fetch_assoc();

        echo '
        <table class="table table-bordered">
          <tr><th>NISN</th><td>' . htmlspecialchars($data['nisn']) . '</td></tr>
          <tr><th>Nama Lengkap</th><td>' . htmlspecialchars($data['nama_lengkap']) . '</td></tr>
          <tr><th>Kelas</th><td>' . htmlspecialchars($data['nama_kelas']) . '</td></tr>
          <tr><th>Jenis Izin</th><td>' . htmlspecialchars($data['jenis_izin']) . '</td></tr>
          <tr><th>Tanggal</th><td>' . htmlspecialchars($data['tanggal_mulai']) . ' s.d ' . htmlspecialchars($data['tanggal_selesai']) . '</td></tr>
          <tr><th>Keterangan</th><td>' . nl2br(htmlspecialchars($data['keterangan'])) . '</td></tr>
        </table>

        <div class="form-group mt-3">
          <label for="catatan-penolakan">Catatan Penolakan (wajib diisi jika menolak):</label>
          <textarea class="form-control" id="catatan-penolakan" rows="3"></textarea>
        </div>

        <div class="text-right">
          <button type="button" class="btn btn-success" id="btn-setujui" data-id="' . $data['id'] . '">
            <i class="fas fa-check"></i> Setujui
          </button>
          <button type="button" class="btn btn-danger" id="btn-tolak" data-id="' . $data['id'] . '">
            <i class="fas fa-times"></i> Tolak
          </button>
        </div>';
      } else {
        echo '<div class="alert alert-warning">Data tidak ditemukan.</div>';
      }
      break;

    /* ----------- HAPUS ----------- */
    case 'hapus':
      check_access('hapus');
      $id = anti_injection($_POST['id']);
      $check = $connection->query("SELECT id FROM izin WHERE id='$id'");
      if ($check->num_rows > 0) {
        $delete = $connection->query("DELETE FROM izin WHERE id='$id'");
        echo $delete ? "Data izin berhasil dihapus." : "Gagal menghapus data.";
      } else {
        echo "Data tidak ditemukan.";
      }
      break;

    /* ----------- EDIT CATATAN ----------- */
    case 'edit_catatan':
      check_access('modifikasi');
      $id = anti_injection($_POST['id']);
      $catatan = anti_injection($_POST['catatan']);

      $check = $connection->query("SELECT id FROM izin WHERE id='$id' AND status_izin='Ditolak'");
      if ($check->num_rows > 0) {
        $update = $connection->query("UPDATE izin SET alasan_penolakan='$catatan' WHERE id='$id'");
        echo $update ? "Catatan berhasil diperbarui." : "Gagal memperbarui catatan.";
      } else {
        echo "Data tidak ditemukan atau status tidak sesuai.";
      }
      break;

    default:
      echo "Aksi tidak dikenali.";
      break;
  }
}
