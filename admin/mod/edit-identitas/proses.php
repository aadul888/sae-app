<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../../library/wilayah_indonesia.php';
  require_once '../../login/user.php';

  sae_ensure_user_region_columns($connection);

  function sae_get_berkas_document_map()
  {
    return [
      'kk' => 'Kartu Keluarga',
      'akte' => 'Akta Kelahiran',
      'ijazah' => 'Ijazah/SKHUN',
      'kip' => 'KIP (Kartu Indonesia Pintar)',
      'kks' => 'KKS (Kartu Keluarga Sejahtera)',
      'kis' => 'KIS (Kartu Indonesia Sehat)'
    ];
  }

  function sae_normalize_berkas_item_status($status)
  {
    $s = strtolower(trim((string)$status));
    if ($s === 'valid' || $s === 'disetujui' || $s === 'approved' || $s === 'ok') {
      return 'valid';
    }
    if ($s === 'revisi' || $s === 'invalid' || $s === 'tidak_valid' || $s === 'tidak valid' || $s === 'ditolak' || $s === 'reject' || $s === 'rejected') {
      return 'tidak_valid';
    }
    return 'tidak_valid';
  }

  function sae_evaluate_berkas_validation($berkasRow)
  {
    $docMap = sae_get_berkas_document_map();
    $requiredDocs = ['kk', 'ijazah'];
    $hasPerItemColumns = false;
    $items = [];
    $counts = [
      'valid' => 0,
      'tidak_valid' => 0
    ];

    foreach ($docMap as $field => $label) {
      $filename = trim((string)($berkasRow[$field] ?? ''));
      $statusField = $field . '_valid';
      $keteranganField = $field . '_keterangan';

      if (array_key_exists($statusField, $berkasRow)) {
        $hasPerItemColumns = true;
      }

      if ($filename === '') {
        $status = 'tidak_valid';
      } else {
        $rawStatus = array_key_exists($statusField, $berkasRow)
          ? ($berkasRow[$statusField] ?? '')
          : ($berkasRow['validasi_berkas'] ?? '');
        $status = sae_normalize_berkas_item_status($rawStatus);
      }

      $counts[$status]++;
      $items[$field] = [
        'field' => $field,
        'label' => $label,
        'filename' => $filename,
        'status' => $status,
        'keterangan' => trim((string)($berkasRow[$keteranganField] ?? ''))
      ];
    }

    $overall = 'valid';
    foreach ($requiredDocs as $requiredDoc) {
      if (!isset($items[$requiredDoc]) || $items[$requiredDoc]['status'] !== 'valid') {
        $overall = 'tidak_valid';
        break;
      }
    }

    return [
      'has_per_item' => $hasPerItemColumns,
      'items' => $items,
      'counts' => $counts,
      'required_docs' => $requiredDocs,
      'overall' => $overall
    ];
  }

  switch (@$_GET['action']) {
    /* ----------- SETUJUI ----------- */
    case 'setujui':
      $id = (int)$_POST['id'];  // intval via prepared stmt param later
      // Ambil fullname admin
      $fullname_admin = null;
      $admin_id = null;
      if (isset($_COOKIE['ADMIN_KEY'])) {
        $admin_id = (int) epm_decode($_COOKIE['ADMIN_KEY']);
      }
      if ($admin_id > 0) {
        $stmt = $connection->prepare("SELECT fullname FROM admin WHERE admin_id=? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param('i', $admin_id);
          $stmt->execute();
          $rs = $stmt->get_result();
          if ($rs && $row = $rs->fetch_assoc()) {
            if (!empty($row['fullname'])) $fullname_admin = $row['fullname'];
          }
          $stmt->close();
        }
      }
      if (!$fullname_admin) {
        $admin_username = isset($_COOKIE['username']) ? trim($_COOKIE['username']) : '';
        if ($admin_username !== '') {
          $stmt = $connection->prepare("SELECT fullname FROM admin WHERE username=? LIMIT 1");
          if ($stmt) {
            $stmt->bind_param('s', $admin_username);
            $stmt->execute();
            $rs = $stmt->get_result();
            if ($rs && $row = $rs->fetch_assoc()) {
              if (!empty($row['fullname'])) $fullname_admin = $row['fullname'];
            }
            $stmt->close();
          }
        }
      }
      if (!$fullname_admin) {
        echo "Error: Admin tidak ditemukan di tabel admin atau fullname kosong. Pastikan admin_id atau username di cookie sama persis dengan data di tabel admin.";
        exit;
      }

      // Ambil data perubahan, hanya yang statusnya 'Berhasil Dikirim' atau 'Dalam Proses'
      $stmt = $connection->prepare("SELECT * FROM perubahan WHERE id=? AND (status_pengajuan='Berhasil Dikirim' OR status_pengajuan='Dalam Proses')");
      if (!$stmt) { echo "Data tidak valid atau sudah diproses."; exit; }
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $rs = $stmt->get_result();
      $stmt->close();
      if ($rs && $rs->num_rows > 0) {
        $d = $rs->fetch_assoc();
        $user_id = $d['user_id'];
        // Cek validasi berkas siswa (per-item with fallback global)
        $stmt2 = $connection->prepare("SELECT * FROM berkas WHERE user_id=?");
        if ($stmt2) {
          $stmt2->bind_param('i', $user_id);
          $stmt2->execute();
          $rb = $stmt2->get_result();
          $stmt2->close();
        } else {
          $rb = false;
        }
        if ($rb && $rb->num_rows > 0) {
          $b = $rb->fetch_assoc();
          $berkasEval = sae_evaluate_berkas_validation($b);

          // Proses usulan admin hanya boleh lanjut jika dokumen wajib valid.
          $requiredDocs = ['kk', 'ijazah'];
          $invalidDocs = [];
          foreach ($requiredDocs as $docKey) {
            if (!isset($berkasEval['items'][$docKey])) {
              $invalidDocs[] = strtoupper($docKey);
              continue;
            }
            $item = $berkasEval['items'][$docKey];
            if (trim((string)$item['filename']) === '') {
              $invalidDocs[] = $item['label'];
              continue;
            }
            if ($item['status'] !== 'valid') {
              $invalidDocs[] = $item['label'];
            }
          }

          if (!empty($invalidDocs)) {
            echo "Usulan tidak dapat diproses. Dokumen wajib (KK dan Ijazah) harus valid. Dokumen bermasalah: " . implode(', ', $invalidDocs) . ".";
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
          'nama_lengkap', 'nisn', 'no_kk', 'nik', 'jenis_kelamin', 'tempat_lahir',
          'tanggal_lahir', 'agama', 'status_keluarga', 'anak_ke', 'alamat', 'rt', 'rw',
          'provinsi_id', 'provinsi', 'kabupaten_kota_id', 'kabupaten_kota', 'kecamatan_id',
          'desa', 'desa_id', 'kecamatan', 'kodepos', 'telp', 'sekolah_asal', 'diterima_dikelas',
          'diterima_tanggal', 'email', 'nik_ayah', 'nama_ayah', 'pekerjaan_ayah', 'nik_ibu',
          'nama_ibu', 'pekerjaan_ibu', 'nama_wali', 'alamat_wali', 'telp_wali', 'pekerjaan_wali'
        ];
        $params = [];
        $types = '';
        foreach ($allowed as $field) {
          if (array_key_exists($field, $keterangan)) {
            $val = $keterangan[$field];
            if (is_array($val)) $val = isset($val['new']) ? $val['new'] : $val['old'];
            $update_fields[] = "$field=?";
            $params[] = (string)$val;
            $types .= 's';
          }
        }
        if (!empty($update_fields)) {
          $params[] = $user_id;
          $types .= 'i';
          $sql_update = "UPDATE user SET " . implode(", ", $update_fields) . " WHERE user_id=?";
          $update_stmt = $connection->prepare($sql_update);
          if ($update_stmt) {
            $update_stmt->bind_param($types, ...$params);
            $update_ok = $update_stmt->execute();
            $update_stmt->close();
          } else {
            $update_ok = false;
          }
        } else {
          echo "Tidak ada data yang diubah. Pastikan field usulan terisi dan sesuai.";
          exit;
        }

        if ($update_ok) {
          $now = date('Y-m-d H:i:s');
          $alasan_disetujui = 'Disetujui oleh ' . $fullname_admin;
          // Set konfirmasi user ke 'Belum Konfirmasi'
          $st1 = $connection->prepare("UPDATE user SET konfirmasi='Belum Konfirmasi' WHERE user_id=?");
          if ($st1) { $st1->bind_param('i', $user_id); $st1->execute(); $st1->close(); }
          $st2 = $connection->prepare("UPDATE perubahan SET status_pengajuan='Disetujui', alasan_penolakan=?, date_processed=?, processed_by=? WHERE id=?");
          if ($st2) { $st2->bind_param('sssi', $alasan_disetujui, $now, $fullname_admin, $id); $st2->execute(); $st2->close(); }
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
      $id = (int)$_POST['id'];
      $alasan = trim((string)$_POST['alasan']);
      // Ambil fullname admin (ulang agar tidak tergantung scope sebelumnya)
      $fullname_admin = null;
      $admin_id = null;
      if (isset($_COOKIE['ADMIN_KEY'])) {
        $admin_id = (int) epm_decode($_COOKIE['ADMIN_KEY']);
      }
      if ($admin_id > 0) {
        $stmt = $connection->prepare("SELECT fullname FROM admin WHERE admin_id=? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param('i', $admin_id);
          $stmt->execute();
          $rs = $stmt->get_result();
          if ($rs && $row = $rs->fetch_assoc()) {
            if (!empty($row['fullname'])) $fullname_admin = $row['fullname'];
          }
          $stmt->close();
        }
      }
      if (!$fullname_admin) {
        $admin_username = isset($_COOKIE['username']) ? trim($_COOKIE['username']) : '';
        if ($admin_username !== '') {
          $stmt = $connection->prepare("SELECT fullname FROM admin WHERE username=? LIMIT 1");
          if ($stmt) {
            $stmt->bind_param('s', $admin_username);
            $stmt->execute();
            $rs = $stmt->get_result();
            if ($rs && $row = $rs->fetch_assoc()) {
              if (!empty($row['fullname'])) $fullname_admin = $row['fullname'];
            }
            $stmt->close();
          }
        }
      }
      if (!$fullname_admin) {
        echo "Error: Admin tidak ditemukan di tabel admin atau fullname kosong. Pastikan admin_id atau username di cookie sama persis dengan data di tabel admin.";
        exit;
      }
      $stmt = $connection->prepare("SELECT id, status_pengajuan FROM perubahan WHERE id=?");
      if (!$stmt) { echo "Data tidak ditemukan."; exit; }
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $rs = $stmt->get_result();
      $stmt->close();
      if ($rs && $rs->num_rows > 0) {
        $row = $rs->fetch_assoc();
        if ($row['status_pengajuan'] === 'Berhasil Dikirim' || $row['status_pengajuan'] === 'Dalam Proses') {
          $now = date('Y-m-d H:i:s');
          $alasan_ditolak = 'Ditolak oleh ' . $fullname_admin . ' dengan alasan : ' . htmlspecialchars($alasan);
          $st = $connection->prepare("UPDATE perubahan SET status_pengajuan='Ditolak', alasan_penolakan=?, date_processed=?, processed_by=? WHERE id=?");
          if ($st) {
            $st->bind_param('sssi', $alasan_ditolak, $now, $fullname_admin, $id);
            $st->execute();
            $st->close();
          }
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
      $id = (int)$_POST['id'];
      $stmt = $connection->prepare("SELECT * FROM perubahan WHERE id=?");
      if (!$stmt) { echo '<div class="alert alert-warning">Data tidak ditemukan.</div>'; exit; }
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $rs = $stmt->get_result();
      $stmt->close();
      if ($rs && $rs->num_rows > 0) {
        $data = $rs->fetch_assoc();
        $user_id = $data['user_id'];
        $st2 = $connection->prepare("SELECT * FROM user WHERE user_id=?");
        if ($st2) {
          $st2->bind_param('i', $user_id);
          $st2->execute();
          $ru = $st2->get_result();
          $st2->close();
        } else {
          $ru = false;
        }
        if ($ru && $ru->num_rows > 0) {
          $user = $ru->fetch_assoc();
        } else {
          echo '<div class="alert alert-danger">Data user lama tidak ditemukan.</div>';
          exit;
        }
        // Robustly decode keterangan
        $raw_keterangan = $data['keterangan'];
        $keterangan = json_decode($raw_keterangan, true);
        if (!is_array($keterangan)) {
          if (is_string($keterangan)) {
            $try = json_decode($keterangan, true);
            if (is_array($try)) $keterangan = $try;
          } else {
            $try2 = json_decode($raw_keterangan, true);
            if (is_array($try2)) $keterangan = $try2;
          }
        }
        if (!is_array($keterangan)) $keterangan = [];

        $display_status_pengajuan = $data['status_pengajuan'];
        $status_lower = strtolower(trim((string)$display_status_pengajuan));
        if ($status_lower !== 'disetujui' && $status_lower !== 'ditolak') {
          $st3 = $connection->prepare("SELECT * FROM berkas WHERE user_id=? LIMIT 1");
          if ($st3) {
            $st3->bind_param('i', $user_id);
            $st3->execute();
            $rb = $st3->get_result();
            $st3->close();
          } else {
            $rb = false;
          }
          if ($rb && $rb->num_rows > 0) {
            $berkas_status_row = $rb->fetch_assoc();
            $berkas_status_eval = sae_evaluate_berkas_validation($berkas_status_row);
            $display_status_pengajuan = ($berkas_status_eval['overall'] === 'valid') ? 'Dalam Proses' : 'Berhasil Dikirim';
          } else {
            $display_status_pengajuan = 'Berhasil Dikirim';
          }
        }

        $fieldGroups = [
          'Identitas Siswa' => [
            'Nama Lengkap' => 'nama_lengkap', 'NISN' => 'nisn', 'No. KK' => 'no_kk',
            'NIK' => 'nik', 'Jenis Kelamin' => 'jenis_kelamin', 'Tempat Lahir' => 'tempat_lahir',
            'Tanggal Lahir' => 'tanggal_lahir', 'Agama' => 'agama', 'Status Keluarga' => 'status_keluarga',
            'Anak Ke' => 'anak_ke', 'Alamat' => 'alamat', 'RT' => 'rt', 'RW' => 'rw',
            'Provinsi' => 'provinsi', 'Kabupaten/Kota' => 'kabupaten_kota', 'Desa/Kelurahan' => 'desa',
            'Kecamatan' => 'kecamatan', 'Kode Pos' => 'kodepos', 'Telepon' => 'telp',
            'Sekolah Asal' => 'sekolah_asal', 'Diterima di Kelas' => 'diterima_dikelas',
            'Tanggal Diterima' => 'diterima_tanggal', 'Email' => 'email'
          ],
          'Data Orang Tua' => [
            'NIK Ayah' => 'nik_ayah', 'Nama Ayah' => 'nama_ayah', 'Pekerjaan Ayah' => 'pekerjaan_ayah',
            'NIK Ibu' => 'nik_ibu', 'Nama Ibu' => 'nama_ibu', 'Pekerjaan Ibu' => 'pekerjaan_ibu'
          ],
          'Data Wali' => [
            'Nama Wali' => 'nama_wali', 'Alamat Wali' => 'alamat_wali', 'Telepon Wali' => 'telp_wali',
            'Pekerjaan Wali' => 'pekerjaan_wali'
          ]
        ];

        include 'detail.php';
      } else {
        echo '<div class="alert alert-warning">Data tidak ditemukan.</div>';
      }
      break;


    /* ----------- HAPUS ----------- */
    case 'hapus':
      $id = (int)$_POST['id'];

      $stmt = $connection->prepare("SELECT id FROM perubahan WHERE id=?");
      if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $rs = $stmt->get_result();
        $stmt->close();
      } else { $rs = false; }
      if ($rs && $rs->num_rows > 0) {
        $st2 = $connection->prepare("DELETE FROM perubahan WHERE id=?");
        if ($st2) {
          $st2->bind_param('i', $id);
          $st2->execute();
          $st2->close();
          echo "Data berhasil dihapus.";
        } else {
          echo "Gagal menghapus data.";
        }
      } else {
        echo "Data tidak ditemukan.";
      }
      break;

    case 'edit_catatan':
      $id = (int)$_POST['id'];
      $catatan = (string)$_POST['catatan'];

      $stmt = $connection->prepare("SELECT id FROM perubahan WHERE id=? AND status_pengajuan='Ditolak'");
      if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $rs = $stmt->get_result();
        $stmt->close();
      } else { $rs = false; }
      if ($rs && $rs->num_rows > 0) {
        $st2 = $connection->prepare("UPDATE perubahan SET alasan_penolakan=? WHERE id=?");
        if ($st2) {
          $st2->bind_param('si', $catatan, $id);
          $ok = $st2->execute();
          $st2->close();
          echo $ok ? "Catatan berhasil diperbarui." : "Gagal memperbarui catatan.";
        } else {
          echo "Gagal memperbarui catatan.";
        }
      } else {
        echo "Data tidak ditemukan atau status tidak sesuai.";
      }
      break;
  }
}
