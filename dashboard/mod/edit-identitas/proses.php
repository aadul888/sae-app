<?php session_start();
if (!isset($_COOKIE['siswa'])) {
  header('location:../login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../../library/wilayah_indonesia.php';
  require_once '../../oauth/user.php';

  sae_ensure_user_region_columns($connection);

  // Debug helpers removed to keep responses concise and avoid generating debug files

  // Helper: consistent JSON responder
  function _respond($status, $message = '', $data = [])
  {
    if (!headers_sent()) header('Content-Type: application/json');
    $out = ['status' => $status, 'message' => $message];
    if (!empty($data)) $out['data'] = $data;
    // intentionally no debug logging here to keep responses minimal
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Helper: normalize values for comparison
  function _normalize_for_compare($field, $value)
  {
    $v = trim((string)$value);
    // numeric-like fields: compare digits only
    $numeric_fields = ['nik', 'no_kk', 'nik_ayah', 'nik_ibu', 'telp', 'telp_wali', 'kodepos'];
    if (in_array($field, $numeric_fields)) {
      return preg_replace('/\D+/', '', $v);
    }
    // NOTE: helpers exist at top-level so no nested definitions here.
    // date fields: try to normalize to YYYY-MM-DD
    if (in_array($field, ['tanggal_lahir', 'diterima_tanggal'])) {
      // attempt strtotime conversion, fallback to raw trimmed
      $t = strtotime($v);
      if ($t !== false) return date('Y-m-d', $t);
      return $v;
    }
    // string fields: collapse whitespace and lowercase for stable compare
    $v = preg_replace('/\s+/', ' ', $v);
    return mb_strtolower($v);
  }

  function _normalize_doc_status($status)
  {
    $s = strtolower(trim((string)$status));
    if (in_array($s, ['valid', 'disetujui', 'approved', 'ok'], true)) {
      return 'valid';
    }
    if (in_array($s, ['revisi', 'invalid', 'tidak valid', 'ditolak', 'reject', 'rejected'], true)) {
      return 'revisi';
    }
    return 'belum';
  }

  function _evaluate_berkas_gate($connection, $userId)
  {
    $result = [
      'ok' => false,
      'overall' => 'belum',
      'invalid' => [],
      'pending' => []
    ];

    $stmt = $connection->prepare("SELECT * FROM berkas WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!is_array($row)) {
      return $result;
    }

    // For student usulan, only KK and Ijazah are mandatory.
    $fields = ['kk', 'ijazah'];
    $hasPerItem = isset($row['kk_valid']);
    $hasUpload = false;

    foreach ($fields as $field) {
      $filename = trim((string)($row[$field] ?? ''));
      if ($filename === '') {
        $result['pending'][] = strtoupper($field);
        continue;
      }

      $hasUpload = true;
      $raw = $hasPerItem ? ($row[$field . '_valid'] ?? '') : ($row['validasi_berkas'] ?? '');
      $status = _normalize_doc_status($raw);
      if ($status === 'revisi') {
        $result['invalid'][] = strtoupper($field);
      } elseif ($status !== 'valid') {
        $result['pending'][] = strtoupper($field);
      }
    }

    if ($hasUpload && empty($result['invalid']) && empty($result['pending'])) {
      $result['ok'] = true;
      $result['overall'] = 'valid';
    } elseif (!empty($result['invalid'])) {
      $result['overall'] = 'revisi';
    }

    return $result;
  }

  // Normalize action: treat orangtua and wali submissions as identitas (all use unified flow)
  $action = isset($_GET['action']) ? $_GET['action'] : '';
  if (in_array($action, ['usulan_orangtua', 'usulan_wali'])) {
    $action = 'usulan_identitas';
  }

  if ($action === 'usulan_identitas') {
    $berkasGate = _evaluate_berkas_gate($connection, $data_user['user_id']);
    if (empty($berkasGate['ok'])) {
      $msgParts = ['Pengajuan dikunci. Dokumen wajib (KK dan Ijazah) harus valid terlebih dahulu.'];
      if (!empty($berkasGate['invalid'])) {
        $msgParts[] = 'Perlu revisi: ' . implode(', ', $berkasGate['invalid']) . '.';
      }
      if (!empty($berkasGate['pending'])) {
        $msgParts[] = 'Belum valid/lengkap: ' . implode(', ', $berkasGate['pending']) . '.';
      }
      _respond('error', implode(' ', $msgParts));
    }
  }

  switch ($action) {
    /* ---------- USULAN PERUBAHAN IDENTITAS ---------- */
    case 'usulan_identitas':

      $error = array();
      // If the client provided a perubahan id, treat this as an edit of an existing usulan.
      $perubahan_id = isset($_POST['perubahan_id']) ? anti_injection($_POST['perubahan_id']) : null;
      // For new submissions (no perubahan_id) block when there is another active usulan
      if (empty($perubahan_id)) {
        $cek_usulan = $connection->prepare("SELECT id, status_pengajuan FROM perubahan WHERE user_id = ? AND status_pengajuan NOT IN ('Disetujui','Ditolak') ORDER BY id DESC");
        $cek_usulan->bind_param('s', $data_user['user_id']);
        $cek_usulan->execute();
        $result_usulan = $cek_usulan->get_result();
        $ada_usulan_aktif = false;
        while ($row = $result_usulan->fetch_assoc()) {
          // Jika ada baris aktif (bukan Ditolak/Disetujui), blokir pengajuan baru
          _respond('error', 'Anda sudah memiliki usulan perubahan data yang sedang diproses atau menunggu verifikasi. Silakan tunggu hingga usulan sebelumnya selesai.');
          $cek_usulan->close();
          break 2;
        }
        $cek_usulan->close();
      }

      // Validasi data yang diterima
      // Include orangtua and wali related fields so a single submission can include all edits
      $fields = [
        'nama_lengkap',
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
        'provinsi_id',
        'provinsi',
        'kabupaten_kota_id',
        'kabupaten_kota',
        'kecamatan_id',
        'desa',
        'desa_id',
        'kecamatan',
        'kodepos',
        'telp',
        'sekolah_asal',
        'diterima_dikelas',
        'diterima_tanggal',
        'email'
        // parent/wali fields
        ,
        'nik_ayah',
        'nama_ayah',
        'pekerjaan_ayah',
        'nik_ibu',
        'nama_ibu',
        'pekerjaan_ibu',
        'nama_wali',
        'alamat_wali',
        'telp_wali',
        'pekerjaan_wali'
      ];


      $data_changes = [];
      $keterangan_full = []; // full mapping of field => ['old'=>..., 'new'=>...]
      $ada_perubahan = false;
      // Ambil data lama user
      $q_user = $connection->prepare("SELECT " . implode(",", $fields) . " FROM user WHERE user_id = ? LIMIT 1");
      $q_user->bind_param('s', $data_user['user_id']);
      $q_user->execute();
      $result_user = $q_user->get_result();
      $old_data = $result_user && $result_user->num_rows > 0 ? $result_user->fetch_assoc() : [];
      $q_user->close();
      foreach ($fields as $field) {
        $old_val = isset($old_data[$field]) ? trim($old_data[$field]) : '';
        // If the form submitted the field, take it; otherwise assume unchanged (use old)
        $new_val = isset($_POST[$field]) ? anti_injection($_POST[$field]) : $old_val;

        // Build full keterangan entry for every field
        $keterangan_full[$field] = ['old' => $old_val, 'new' => $new_val];

        // Determine whether this particular field actually changed
        $n_new = _normalize_for_compare($field, $new_val);
        $n_old = _normalize_for_compare($field, $old_val);
        if ($n_new !== $n_old) {
          $data_changes[$field] = ['old' => $old_val, 'new' => $new_val];
          $ada_perubahan = true;
        }
      }

      // Server-side required field enforcement (all fields except data wali)
      $required_fields = [
        'nama_lengkap',
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
        'provinsi',
        'kabupaten_kota',
        'desa',
        'kecamatan',
        'kodepos',
        'telp',
        'sekolah_asal',
        'diterima_dikelas',
        'diterima_tanggal',
        'nama_ayah',
        'pekerjaan_ayah',
        'nama_ibu',
        'pekerjaan_ibu'
      ];
      $label_map_required = [
        'nama_lengkap' => 'Nama Lengkap',
        'no_kk' => 'Nomor KK',
        'nik' => 'NIK',
        'jenis_kelamin' => 'Jenis Kelamin',
        'tempat_lahir' => 'Tempat Lahir',
        'tanggal_lahir' => 'Tanggal Lahir',
        'agama' => 'Agama',
        'status_keluarga' => 'Status dalam Keluarga',
        'anak_ke' => 'Anak Ke',
        'alamat' => 'Alamat',
        'rt' => 'RT',
        'rw' => 'RW',
        'provinsi' => 'Provinsi',
        'kabupaten_kota' => 'Kabupaten/Kota',
        'desa' => 'Desa/Kelurahan',
        'kecamatan' => 'Kecamatan',
        'kodepos' => 'Kode Pos',
        'telp' => 'Telp/HP',
        'sekolah_asal' => 'Asal Sekolah',
        'diterima_dikelas' => 'Diterima di Kelas',
        'diterima_tanggal' => 'Tanggal Diterima',
        'nama_ayah' => 'Nama Ayah',
        'pekerjaan_ayah' => 'Pekerjaan Ayah',
        'nama_ibu' => 'Nama Ibu',
        'pekerjaan_ibu' => 'Pekerjaan Ibu'
      ];
      foreach ($required_fields as $rf) {
        $val = isset($keterangan_full[$rf]['new']) ? trim((string)$keterangan_full[$rf]['new']) : '';
        if ($val === '') {
          $error[] = ($label_map_required[$rf] ?? $rf) . ' wajib diisi.';
        }
      }

      // debug logging removed

      // debug logging removed

      // Cek duplikasi untuk beberapa field. NOTE: per permintaan, jangan
      // cek duplikasi pada Nomor KK dan NIK orangtua (nik_ayah, nik_ibu)
      // karena anggota keluarga dapat berbagi KK / NIK orangtua.
      $unique_fields = [
        'nik' => 'NIK',
        'telp' => 'No. Telepon',
        'email' => 'Email',
        'telp_wali' => 'No. HP Wali'
      ];
      foreach ($unique_fields as $col => $label) {
        $newval = null;
        if (isset($data_changes[$col])) {
          // data_changes now stores ['old'=>..,'new'=>..]
          $newval = is_array($data_changes[$col]) && isset($data_changes[$col]['new']) ? $data_changes[$col]['new'] : $data_changes[$col];
        }
        if (!empty($newval)) {
          $cek = $connection->prepare("SELECT user_id FROM user WHERE $col = ? AND user_id != ? LIMIT 1");
          $cek->bind_param('ss', $newval, $data_user['user_id']);
          $cek->execute();
          $cek->store_result();
          if ($cek->num_rows > 0) {
            $error[] = "$label sudah terdaftar pada pengguna lain.";
          }
          $cek->close();
          // Validasi email wajib domain @smk.belajar.id (hanya tambahkan error sekali)
          if ($col === 'email' && isset($newval) && !empty($newval)) {
            $email = trim($newval);
            if (!preg_match('/^[^@\s]+@smk\.belajar\.id$/i', $email)) {
              if (!in_array('Email wajib menggunakan domain @smk.belajar.id', $error)) {
                $error[] = 'Email wajib menggunakan domain @smk.belajar.id';
              }
            }
          }
        }
      }

      // Validasi NIK, No KK, NIK Ayah, NIK Ibu tidak boleh sama
      $nik = isset($data_changes['nik']) ? (is_array($data_changes['nik']) ? $data_changes['nik']['new'] : $data_changes['nik']) : (isset($old_data['nik']) ? $old_data['nik'] : '');
      $no_kk = isset($data_changes['no_kk']) ? (is_array($data_changes['no_kk']) ? $data_changes['no_kk']['new'] : $data_changes['no_kk']) : (isset($old_data['no_kk']) ? $old_data['no_kk'] : '');
      // Ambil nik_ayah dan nik_ibu dari data_changes jika disubmit atau dari database
      $nik_ayah = isset($data_changes['nik_ayah']) ? (is_array($data_changes['nik_ayah']) ? $data_changes['nik_ayah']['new'] : $data_changes['nik_ayah']) : (isset($old_data['nik_ayah']) ? $old_data['nik_ayah'] : '');
      $nik_ibu = isset($data_changes['nik_ibu']) ? (is_array($data_changes['nik_ibu']) ? $data_changes['nik_ibu']['new'] : $data_changes['nik_ibu']) : (isset($old_data['nik_ibu']) ? $old_data['nik_ibu'] : '');
      $niks = [$nik, $no_kk, $nik_ayah, $nik_ibu];
      $nik_labels = ['NIK', 'Nomor KK', 'NIK Ayah', 'NIK Ibu'];
      for ($i = 0; $i < count($niks); $i++) {
        for ($j = $i + 1; $j < count($niks); $j++) {
          if (!empty($niks[$i]) && !empty($niks[$j]) && $niks[$i] === $niks[$j]) {
            $error[] = $nik_labels[$i] . ' tidak boleh sama dengan ' . $nik_labels[$j] . '.';
          }
        }
      }

      // Validasi format: semua NIK dan No KK harus tepat 16 digit angka
      $fixed16 = ['nik' => $nik, 'no_kk' => $no_kk, 'nik_ayah' => $nik_ayah, 'nik_ibu' => $nik_ibu];
      foreach ($fixed16 as $field => $val) {
        $digits = preg_replace('/\D+/', '', (string)$val);
        if ($val !== '' && strlen($digits) !== 16) {
          $label_map_err = ['nik' => 'NIK', 'no_kk' => 'Nomor KK', 'nik_ayah' => 'NIK Ayah', 'nik_ibu' => 'NIK Ibu'];
          $error[] = $label_map_err[$field] . ' harus terdiri dari 16 digit angka.';
        }
      }

      // Validasi format NIK Ayah dan NIK Ibu harus 16 digit angka jika diberikan
      $check_ayah = is_array($data_changes['nik_ayah'] ?? null) ? ($data_changes['nik_ayah']['new'] ?? '') : ($data_changes['nik_ayah'] ?? '');
      $check_ibu = is_array($data_changes['nik_ibu'] ?? null) ? ($data_changes['nik_ibu']['new'] ?? '') : ($data_changes['nik_ibu'] ?? '');
      foreach (['NIK Ayah' => $check_ayah, 'NIK Ibu' => $check_ibu] as $lbl => $val) {
        $d = preg_replace('/\D+/', '', (string)$val);
        if ($val !== '' && strlen($d) !== 16) {
          $error[] = $lbl . ' harus terdiri dari 16 digit angka.';
        }
      }

      // Validasi telepon: hanya angka
      $telp_val = isset($data_changes['telp']) ? (is_array($data_changes['telp']) ? $data_changes['telp']['new'] : $data_changes['telp']) : (isset($old_data['telp']) ? $old_data['telp'] : '');
      $telp_wali_val = isset($data_changes['telp_wali']) ? (is_array($data_changes['telp_wali']) ? $data_changes['telp_wali']['new'] : $data_changes['telp_wali']) : (isset($old_data['telp_wali']) ? $old_data['telp_wali'] : '');
      if ($telp_val !== '' && preg_match('/\D/', $telp_val)) $error[] = 'Nomor telepon hanya boleh berisi angka.';
      if ($telp_wali_val !== '' && preg_match('/\D/', $telp_wali_val)) $error[] = 'Nomor telepon wali hanya boleh berisi angka.';

      // Validasi email domain jika ada
      $email_val = isset($data_changes['email']) ? (is_array($data_changes['email']) ? $data_changes['email']['new'] : $data_changes['email']) : (isset($old_data['email']) ? $old_data['email'] : '');
      if ($email_val !== '' && !preg_match('/^[^@\s]+@smk\.belajar\.id$/i', trim($email_val))) {
        $error[] = 'Email wajib menggunakan domain @smk.belajar.id';
      }

      $berkasGate = _evaluate_berkas_gate($connection, $data_user['user_id']);
      if (empty($berkasGate['ok'])) {
        $error[] = 'Dokumen wajib (KK dan Ijazah) harus valid sebelum mengirim usulan perubahan data.';
      }
      if (empty($data_changes) || !$ada_perubahan) {
        $error[] = 'Tidak ada data yang diubah.';
      }

      $validasi_berkas = !empty($berkasGate['ok']) ? 'valid' : 'belum';

      // Determine if we should update an existing usulan or insert a new one
      $id_to_update = null;
      if (!empty($perubahan_id)) {
        // Verify the perubahan belongs to this user and is editable (not already Disetujui)
        $q = $connection->prepare("SELECT id, status_pengajuan FROM perubahan WHERE id = ? AND user_id = ? LIMIT 1");
        $q->bind_param('ss', $perubahan_id, $data_user['user_id']);
        $q->execute();
        $res = $q->get_result();
        if ($res && $res->num_rows > 0) {
          $r = $res->fetch_assoc();
          if ($r['status_pengajuan'] === 'Disetujui') {
            _respond('error', 'Usulan yang sudah disetujui tidak dapat diedit.');
          } else {
            $id_to_update = $r['id'];
          }
        } else {
          _respond('error', 'Usulan tidak ditemukan atau bukan milik Anda.');
        }
        $q->close();
      } else {
        // Existing behavior: allow updating the latest Ditolak usulan, but block others
        $cek_usulan = $connection->prepare("SELECT id, status_pengajuan FROM perubahan WHERE user_id = ? AND status_pengajuan NOT IN ('Disetujui') ORDER BY id DESC");
        $cek_usulan->bind_param('s', $data_user['user_id']);
        $cek_usulan->execute();
        $result_usulan = $cek_usulan->get_result();
        $id_usulan_ditolak = null;
        while ($row = $result_usulan->fetch_assoc()) {
          if ($row['status_pengajuan'] === 'Ditolak' && $id_usulan_ditolak === null) {
            $id_usulan_ditolak = $row['id'];
          } else if ($row['status_pengajuan'] !== 'Ditolak') {
            _respond('error', 'Anda sudah memiliki usulan perubahan data yang sedang diproses atau menunggu verifikasi. Silakan tunggu hingga usulan sebelumnya selesai.');
            $cek_usulan->close();
            break 2;
          }
        }
        $cek_usulan->close();
        if ($id_usulan_ditolak) $id_to_update = $id_usulan_ditolak;
      }

      if (empty($error)) {
        // Simpan data perubahan dalam kolom keterangan (tanpa kolom jenis_data)
        // Store the full keterangan (old/new for every canonical field)
        $keterangan = $connection->real_escape_string(json_encode($keterangan_full));
        // Build a short human-friendly summary (ringkasan) to speed up rendering
        $label_map = [
          'nama_lengkap' => 'Nama Lengkap',
          'no_kk' => 'Nomor KK',
          'nik' => 'NIK',
          'jenis_kelamin' => 'Jenis Kelamin',
          'tempat_lahir' => 'Tempat Lahir',
          'tanggal_lahir' => 'Tanggal Lahir',
          'agama' => 'Agama',
          'nik_ayah' => 'NIK Ayah',
          'nama_ayah' => 'Nama Ayah',
          'nik_ibu' => 'NIK Ibu',
          'nama_ibu' => 'Nama Ibu',
          'nama_wali' => 'Nama Wali',
          'telp_wali' => 'Telp Wali'
        ];
        $labels = [];
        foreach ($data_changes as $k => $v) {
          if (strpos($k, '_') === 0) continue;
          $labels[] = $label_map[$k] ?? ucwords(str_replace('_', ' ', $k));
        }
        $ringkasan = '';
        if (!empty($labels)) {
          $ringkasan = implode(', ', array_slice($labels, 0, 5));
          if (count($labels) > 5) $ringkasan .= ', ...';
        }
        $ringkasan = $connection->real_escape_string($ringkasan);
        $status_pengajuan = ($validasi_berkas === 'valid') ? 'Dalam Proses' : 'Berhasil Dikirim';
        if (!empty($id_to_update)) {
          // Update the specified usulan
          $update = $connection->query("UPDATE perubahan SET keterangan='$keterangan', ringkasan='$ringkasan', date_submitted=NOW(), status_pengajuan='$status_pengajuan' WHERE id='$id_to_update'");
          if ($update) {
            _respond('success', 'Usulan berhasil dikirim');
          } else {
            _respond('error', 'Data tidak berhasil diupdate!');
          }
        } else {
          // Insert baru
          $add = "INSERT INTO perubahan (user_id, keterangan, ringkasan, date_submitted, status_pengajuan) VALUES ('" .
            $connection->real_escape_string($data_user['user_id']) . "', '$keterangan', '$ringkasan', NOW(), '$status_pengajuan')";
          if ($connection->query($add) === false) {
            _respond('error', 'Data tidak berhasil disimpan!');
          } else {
            _respond('success', 'Usulan berhasil dikirim');
          }
        }
      } else {
        _respond('error', implode(", ", $error));
      }
      break;

    /* ---------- USULAN PERUBAHAN ORANGTUA ---------- */
    case 'usulan_orangtua':

      $error = array();
      // Cek apakah ada usulan aktif (status selain Disetujui/Ditolak) untuk user
      $cek_usulan = $connection->prepare("SELECT id, status_pengajuan FROM perubahan WHERE user_id = ? AND status_pengajuan NOT IN ('Disetujui','Ditolak') ORDER BY id DESC");
      $cek_usulan->bind_param('s', $data_user['user_id']);
      $cek_usulan->execute();
      $result_usulan = $cek_usulan->get_result();
      while ($row = $result_usulan->fetch_assoc()) {
        _respond('error', 'Anda sudah memiliki usulan perubahan data yang sedang diproses atau menunggu verifikasi. Silakan tunggu hingga usulan sebelumnya selesai.');
        $cek_usulan->close();
        break 2;
      }
      $cek_usulan->close();

      // Validasi data yang diterima
      $fields = ['nik_ayah', 'nama_ayah', 'pekerjaan_ayah', 'nik_ibu', 'nama_ibu', 'pekerjaan_ibu'];


      $data_changes = [];
      $ada_perubahan = false;
      // Ambil data lama user
      $q_user = $connection->prepare("SELECT " . implode(",", $fields) . " FROM user WHERE user_id = ? LIMIT 1");
      $q_user->bind_param('s', $data_user['user_id']);
      $q_user->execute();
      $result_user = $q_user->get_result();
      $old_data = $result_user && $result_user->num_rows > 0 ? $result_user->fetch_assoc() : [];
      $q_user->close();
      foreach ($fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
          $new_val = anti_injection($_POST[$field]);
          $old_val = isset($old_data[$field]) ? trim($old_data[$field]) : '';
          $n_new = _normalize_for_compare($field, $new_val);
          $n_old = _normalize_for_compare($field, $old_val);
          if ($n_new !== $n_old) {
            $data_changes[$field] = ['old' => $old_val, 'new' => $new_val];
            $ada_perubahan = true;
          }
        }
      }

      // NOTE: Perubahan: tidak melakukan pengecekan duplikasi pada NIK orangtua
      // terhadap data pengguna lain karena keluarga dapat memiliki NIK yang sama
      // pada orangtua atau berbagi Nomor KK.

      // Validasi NIK Ayah dan NIK Ibu tidak boleh sama, dan tidak boleh sama dengan NIK/No KK siswa
      // Ambil nik dan no_kk dari user
      $q_user_nik = $connection->prepare("SELECT nik, no_kk FROM user WHERE user_id = ? LIMIT 1");
      $q_user_nik->bind_param('s', $data_user['user_id']);
      $q_user_nik->execute();
      $q_user_nik->bind_result($nik_siswa, $no_kk_siswa);
      $q_user_nik->fetch();
      $q_user_nik->close();
      $nik_ayah = isset($data_changes['nik_ayah']) ? $data_changes['nik_ayah'] : '';
      $nik_ibu = isset($data_changes['nik_ibu']) ? $data_changes['nik_ibu'] : '';
      $niks = [
        'NIK Ayah' => $nik_ayah,
        'NIK Ibu' => $nik_ibu,
        'NIK Siswa' => $nik_siswa,
        'Nomor KK' => $no_kk_siswa
      ];
      $nik_keys = array_keys($niks);
      for ($i = 0; $i < count($nik_keys); $i++) {
        for ($j = $i + 1; $j < count($nik_keys); $j++) {
          if (!empty($niks[$nik_keys[$i]]) && !empty($niks[$nik_keys[$j]]) && $niks[$nik_keys[$i]] === $niks[$nik_keys[$j]]) {
            $error[] = $nik_keys[$i] . ' tidak boleh sama dengan ' . $nik_keys[$j] . '.';
          }
        }
      }

      // Validasi berkas wajib (KK dan Ijazah)
      $q_berkas = $connection->prepare("SELECT kk, ijazah FROM berkas WHERE user_id = ? LIMIT 1");
      $q_berkas->bind_param('s', $data_user['user_id']);
      $q_berkas->execute();
      $q_berkas->store_result();
      $kk = $ijazah = '';
      if ($q_berkas->num_rows > 0) {
        $q_berkas->bind_result($kk, $ijazah);
        $q_berkas->fetch();
      }
      $q_berkas->close();
      if (empty($kk) || empty($ijazah)) {
        $error[] = 'Berkas wajib (KK dan Ijazah) harus diupload terlebih dahulu.';
      }
      if (empty($data_changes) || !$ada_perubahan) {
        $error[] = 'Tidak ada data orang tua yang diubah.';
      }

      // Cek status validasi berkas
      $q_validasi = $connection->prepare("SELECT validasi_berkas FROM berkas WHERE user_id = ? LIMIT 1");
      $q_validasi->bind_param('s', $data_user['user_id']);
      $q_validasi->execute();
      $q_validasi->store_result();
      $validasi_berkas = '';
      if ($q_validasi->num_rows > 0) {
        $q_validasi->bind_result($validasi_berkas);
        $q_validasi->fetch();
      }
      $q_validasi->close();

      // Cek apakah ada usulan aktif selain yang ditolak
      $cek_usulan = $connection->prepare("SELECT id, status_pengajuan FROM perubahan WHERE user_id = ? AND status_pengajuan NOT IN ('Disetujui') ORDER BY id DESC");
      $cek_usulan->bind_param('s', $data_user['user_id']);
      $cek_usulan->execute();
      $result_usulan = $cek_usulan->get_result();
      $id_usulan_ditolak = null;
      while ($row = $result_usulan->fetch_assoc()) {
        if ($row['status_pengajuan'] === 'Ditolak' && $id_usulan_ditolak === null) {
          $id_usulan_ditolak = $row['id'];
        } else if ($row['status_pengajuan'] !== 'Ditolak') {
          _respond('error', 'Anda sudah memiliki usulan perubahan data yang sedang diproses atau menunggu verifikasi. Silakan tunggu hingga usulan sebelumnya selesai.');
          $cek_usulan->close();
          break 2;
        }
      }
      $cek_usulan->close();

      if (empty($error)) {
        // Simpan data perubahan dalam kolom keterangan (tanpa kolom jenis_data)
        // Untuk usulan orangtua, gunakan $data_changes (hanya field yang berubah)
        $keterangan = $connection->real_escape_string(json_encode($data_changes));
        // Build ringkasan
        $label_map = [
          'nik_ayah' => 'NIK Ayah',
          'nama_ayah' => 'Nama Ayah',
          'pekerjaan_ayah' => 'Pekerjaan Ayah',
          'nik_ibu' => 'NIK Ibu',
          'nama_ibu' => 'Nama Ibu',
          'pekerjaan_ibu' => 'Pekerjaan Ibu'
        ];
        $labels = [];
        foreach ($data_changes as $k => $v) {
          if (strpos($k, '_') === 0) continue;
          $labels[] = $label_map[$k] ?? ucwords(str_replace('_', ' ', $k));
        }
        $ringkasan = '';
        if (!empty($labels)) {
          $ringkasan = implode(', ', array_slice($labels, 0, 5));
          if (count($labels) > 5) $ringkasan .= ', ...';
        }
        $ringkasan = $connection->real_escape_string($ringkasan);
        $status_pengajuan = ($validasi_berkas === 'valid') ? 'Dalam Proses' : 'Berhasil Dikirim';
        if ($id_usulan_ditolak) {
          // Update usulan yang ditolak
          $update = $connection->query("UPDATE perubahan SET keterangan='$keterangan', ringkasan='$ringkasan', date_submitted=NOW(), status_pengajuan='$status_pengajuan' WHERE id='$id_usulan_ditolak'");
          if ($update) {
            _respond('success', 'Usulan berhasil dikirim');
          } else {
            _respond('error', 'Data tidak berhasil diupdate!');
          }
        } else {
          // Insert baru
          $add = "INSERT INTO perubahan (user_id, keterangan, ringkasan, date_submitted, status_pengajuan) VALUES ('" .
            $connection->real_escape_string($data_user['user_id']) . "', '$keterangan', '$ringkasan', NOW(), '$status_pengajuan')";

          if ($connection->query($add) === false) {
            _respond('error', 'Data tidak berhasil disimpan!');
          } else {
            _respond('success', 'Usulan berhasil dikirim');
          }
        }
      } else {
        _respond('error', implode(", ", $error));
      }
      break;

    /* ---------- USULAN PERUBAHAN WALI ---------- */
    case 'usulan_wali':

      $error = array();
      // Cek apakah ada usulan aktif (status selain Disetujui/Ditolak) untuk user
      $cek_usulan = $connection->prepare("SELECT id, status_pengajuan FROM perubahan WHERE user_id = ? AND status_pengajuan NOT IN ('Disetujui','Ditolak') ORDER BY id DESC");
      $cek_usulan->bind_param('s', $data_user['user_id']);
      $cek_usulan->execute();
      $result_usulan = $cek_usulan->get_result();
      while ($row = $result_usulan->fetch_assoc()) {
        _respond('error', 'Anda sudah memiliki usulan perubahan data yang sedang diproses atau menunggu verifikasi. Silakan tunggu hingga usulan sebelumnya selesai.');
        $cek_usulan->close();
        break 2;
      }
      $cek_usulan->close();

      // Validasi data yang diterima
      $fields = ['nama_wali', 'alamat_wali', 'telp_wali', 'pekerjaan_wali'];


      $data_changes = [];
      $ada_perubahan = false;
      // Ambil data lama user
      $q_user = $connection->prepare("SELECT " . implode(",", $fields) . " FROM user WHERE user_id = ? LIMIT 1");
      $q_user->bind_param('s', $data_user['user_id']);
      $q_user->execute();
      $result_user = $q_user->get_result();
      $old_data = $result_user && $result_user->num_rows > 0 ? $result_user->fetch_assoc() : [];
      $q_user->close();
      foreach ($fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
          $new_val = anti_injection($_POST[$field]);
          $old_val = isset($old_data[$field]) ? trim($old_data[$field]) : '';
          $n_new = _normalize_for_compare($field, $new_val);
          $n_old = _normalize_for_compare($field, $old_val);
          if ($n_new !== $n_old) {
            $data_changes[$field] = ['old' => $old_val, 'new' => $new_val];
            $ada_perubahan = true;
          }
        }
      }

      // debug logging removed

      // Cek duplikasi telp_wali
      if (!empty($data_changes['telp_wali'])) {
        $cek = $connection->prepare("SELECT user_id FROM user WHERE telp_wali = ? AND user_id != ? LIMIT 1");
        $cek->bind_param('ss', $data_changes['telp_wali'], $data_user['user_id']);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
          $error[] = "No. HP Wali sudah terdaftar pada pengguna lain.";
        }
        $cek->close();
        // Validasi telp_wali: hanya angka
        $val_telp_wali = is_array($data_changes['telp_wali']) ? ($data_changes['telp_wali']['new'] ?? '') : $data_changes['telp_wali'];
        if ($val_telp_wali !== '' && preg_match('/\D/', $val_telp_wali)) {
          $error[] = 'Nomor telepon wali hanya boleh berisi angka.';
        }
      }

      // Validasi berkas wajib (KK dan Ijazah)
      $q_berkas = $connection->prepare("SELECT kk, ijazah FROM berkas WHERE user_id = ? LIMIT 1");
      $q_berkas->bind_param('s', $data_user['user_id']);
      $q_berkas->execute();
      $q_berkas->store_result();
      $kk = $ijazah = '';
      if ($q_berkas->num_rows > 0) {
        $q_berkas->bind_result($kk, $ijazah);
        $q_berkas->fetch();
      }
      $q_berkas->close();
      if (empty($kk) || empty($ijazah)) {
        $error[] = 'Berkas wajib (KK dan Ijazah) harus diupload terlebih dahulu.';
      }
      if (empty($data_changes) || !$ada_perubahan) {
        $error[] = 'Tidak ada data wali yang diubah.';
      }

      // Cek status validasi berkas
      $q_validasi = $connection->prepare("SELECT validasi_berkas FROM berkas WHERE user_id = ? LIMIT 1");
      $q_validasi->bind_param('s', $data_user['user_id']);
      $q_validasi->execute();
      $q_validasi->store_result();
      $validasi_berkas = '';
      if ($q_validasi->num_rows > 0) {
        $q_validasi->bind_result($validasi_berkas);
        $q_validasi->fetch();
      }
      $q_validasi->close();

      // Cek apakah ada usulan aktif selain yang ditolak
      $cek_usulan = $connection->prepare("SELECT id, status_pengajuan FROM perubahan WHERE user_id = ? AND status_pengajuan NOT IN ('Disetujui') ORDER BY id DESC");
      $cek_usulan->bind_param('s', $data_user['user_id']);
      $cek_usulan->execute();
      $result_usulan = $cek_usulan->get_result();
      $id_usulan_ditolak = null;
      while ($row = $result_usulan->fetch_assoc()) {
        if ($row['status_pengajuan'] === 'Ditolak' && $id_usulan_ditolak === null) {
          $id_usulan_ditolak = $row['id'];
        } else if ($row['status_pengajuan'] !== 'Ditolak') {
          _respond('error', 'Anda sudah memiliki usulan perubahan data yang sedang diproses atau menunggu verifikasi. Silakan tunggu hingga usulan sebelumnya selesai.');
          $cek_usulan->close();
          break 2;
        }
      }
      $cek_usulan->close();

      if (empty($error)) {
        // Simpan data perubahan dalam kolom keterangan (tanpa kolom jenis_data)
        $keterangan = $connection->real_escape_string(json_encode($data_changes));
        // Build ringkasan
        $label_map = [
          'nama_wali' => 'Nama Wali',
          'alamat_wali' => 'Alamat Wali',
          'telp_wali' => 'Telp Wali',
          'pekerjaan_wali' => 'Pekerjaan Wali'
        ];
        $labels = [];
        foreach ($data_changes as $k => $v) {
          if (strpos($k, '_') === 0) continue;
          $labels[] = $label_map[$k] ?? ucwords(str_replace('_', ' ', $k));
        }
        $ringkasan = '';
        if (!empty($labels)) {
          $ringkasan = implode(', ', array_slice($labels, 0, 5));
          if (count($labels) > 5) $ringkasan .= ', ...';
        }
        $ringkasan = $connection->real_escape_string($ringkasan);
        $status_pengajuan = ($validasi_berkas === 'valid') ? 'Dalam Proses' : 'Berhasil Dikirim';
        if ($id_usulan_ditolak) {
          // Update usulan yang ditolak
          $update = $connection->query("UPDATE perubahan SET keterangan='$keterangan', ringkasan='$ringkasan', date_submitted=NOW(), status_pengajuan='$status_pengajuan' WHERE id='$id_usulan_ditolak'");
          if ($update) {
            _respond('success', 'Usulan berhasil dikirim');
          } else {
            _respond('error', 'Data tidak berhasil diupdate!');
          }
        } else {
          // Insert baru
          $add = "INSERT INTO perubahan (user_id, keterangan, ringkasan, date_submitted, status_pengajuan) VALUES ('" .
            $connection->real_escape_string($data_user['user_id']) . "', '$keterangan', '$ringkasan', NOW(), '$status_pengajuan')";

          if ($connection->query($add) === false) {
            _respond('error', 'Data tidak berhasil disimpan!');
          } else {
            _respond('success', 'Usulan berhasil dikirim');
          }
        }
      } else {
        _respond('error', implode(", ", $error));
      }
      break;

    /* ---------- HAPUS USULAN (OLEH PENGAJU) ---------- */
    case 'hapus_usulan':
      $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
      if (!$id) {
        _respond('error', 'ID usulan tidak valid.');
        break;
      }

      // Periksa kepemilikan dan status
      $stmt = $connection->prepare("SELECT user_id, status_pengajuan FROM perubahan WHERE id = ? LIMIT 1");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $res = $stmt->get_result();
      if (!$res || $res->num_rows == 0) {
        _respond('error', 'Usulan tidak ditemukan.');
        $stmt->close();
        break;
      }
      $row = $res->fetch_assoc();
      if ($row['user_id'] !== $data_user['user_id']) {
        _respond('error', 'Akses ditolak.');
        $stmt->close();
        break;
      }
      if (in_array($row['status_pengajuan'], ['Disetujui', 'Ditolak'])) {
        _respond('error', 'Usulan tidak dapat dihapus.');
        $stmt->close();
        break;
      }
      $stmt->close();

      $del = $connection->prepare("DELETE FROM perubahan WHERE id = ? LIMIT 1");
      $del->bind_param('i', $id);
      if ($del->execute()) {
        _respond('success', 'Usulan berhasil dihapus');
      } else {
        _respond('error', 'Gagal menghapus usulan: ' . $connection->error);
      }
      $del->close();
      break;
  }
}
