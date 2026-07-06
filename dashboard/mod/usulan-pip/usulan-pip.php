<?php if (empty($connection)) {
  echo 'Koneksi tidak ditemukan';
  header('location:../');
  exit();
} else {
  if (isset($_COOKIE['siswa'])) {
    $user_id = $data_user['user_id'] ?? '';

    // Batasi akses: status 'Aktif', konfirmasi 'Sesuai', validasi_berkas 'valid' dan cek file berkas lengkap
    $can_access = false;
    $berkas_row = null;
    if (!empty($user_id)) {
      // Ambil status, konfirmasi, dan avatar dari user
      $stmt = $connection->prepare("SELECT status, konfirmasi, avatar FROM user WHERE user_id = ? LIMIT 1");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->bind_result($status, $konfirmasi, $avatar_check);
      $stmt->fetch();
      $stmt->close();

      // Ambil data berkas (cek validasi dan file-file penting)
      $stmt2 = $connection->prepare("SELECT validasi_berkas, kk, akte, ijazah, kip, kks, kis FROM berkas WHERE user_id = ? LIMIT 1");
      $stmt2->bind_param("i", $user_id);
      $stmt2->execute();
      $stmt2->bind_result($validasi_berkas, $kk_chk, $akte_chk, $ijazah_chk, $kip_chk, $kks_chk, $kis_chk);
      if ($stmt2->fetch()) {
        $berkas_row = [
          'validasi_berkas' => $validasi_berkas,
          'kk' => $kk_chk,
          'akte' => $akte_chk,
          'ijazah' => $ijazah_chk,
          'kip' => $kip_chk,
          'kks' => $kks_chk,
          'kis' => $kis_chk,
        ];
      }
      $stmt2->close();

      // Syarat akses: user aktif + konfirmasi sesuai + validasi berkas = valid + avatar ada
      if (strtolower($status) === 'aktif' && strtolower($konfirmasi) === 'sesuai' && strtolower($berkas_row['validasi_berkas'] ?? '') === 'valid' && !empty($avatar_check)) {
        $can_access = true;
      }
    }
    if (!$can_access) {
      // Cek kondisi masing-masing requirement
      $foto_valid = !empty($avatar_check) && $avatar_check !== 'avatar.jpg';
      $identitas_valid = strtolower($status) === 'aktif' && strtolower($konfirmasi) === 'sesuai';
      $berkas_valid = strtolower($berkas_row['validasi_berkas'] ?? '') === 'valid';
      
      echo '
      <div class="container-fluid mt-5">
        <div class="row justify-content-center">
          <div class="col-lg-6 col-md-8">
            <div class="text-center mb-4">
              <h2 class="text-white mb-2"><i class="fas fa-shield-check"></i> Verifikasi Akses</h2>
              <p class="text-white">Silakan lengkapi persyaratan berikut untuk mengakses Usulan PIP</p>
            </div>
            
            <div class="verification-cards">
              <!-- Foto Profil Card -->
              <div class="verification-card '.($foto_valid ? 'valid' : 'invalid').'" data-requirement="foto">
                <div class="card-icon">
                  '.($foto_valid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>').'
                </div>
                <div class="card-content">
                  <h5 class="card-title">Foto Profil</h5>
                  <p class="card-description">'.($foto_valid ? 'Foto profil sudah tersedia dan valid' : 'Foto profil belum diunggah atau masih default').'</p>
                </div>
                <div class="card-status">
                  <span class="status-badge '.($foto_valid ? 'valid' : 'invalid').'">
                    '.($foto_valid ? '<i class="fas fa-check"></i> VALID' : '<i class="fas fa-times"></i> BELUM VALID').'
                  </span>
                </div>
              </div>

              <!-- Status Identitas Card -->
              <div class="verification-card '.($identitas_valid ? 'valid' : 'invalid').'" data-requirement="identitas">
                <div class="card-icon">
                  '.($identitas_valid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>').'
                </div>
                <div class="card-content">
                  <h5 class="card-title">Status Identitas</h5>
                  <p class="card-description">Status: <strong>'.ucfirst($status ?? 'Tidak Diketahui').'</strong><br>Konfirmasi: <strong>'.ucfirst($konfirmasi ?? 'Belum').'</strong></p>
                </div>
                <div class="card-status">
                  <span class="status-badge '.($identitas_valid ? 'valid' : 'invalid').'">
                    '.($identitas_valid ? '<i class="fas fa-check"></i> VALID' : '<i class="fas fa-times"></i> BELUM VALID').'
                  </span>
                </div>
              </div>

              <!-- Validasi Berkas Card -->
              <div class="verification-card '.($berkas_valid ? 'valid' : 'invalid').'" data-requirement="berkas">
                <div class="card-icon">
                  '.($berkas_valid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>').'
                </div>
                <div class="card-content">
                  <h5 class="card-title">Validasi Berkas</h5>
                  <p class="card-description">'.($berkas_valid ? 'Semua berkas sudah divalidasi dan lengkap' : 'Berkas belum lengkap atau belum divalidasi').'</p>
                </div>
                <div class="card-status">
                  <span class="status-badge '.($berkas_valid ? 'valid' : 'invalid').'">
                    '.($berkas_valid ? '<i class="fas fa-check"></i> VALID' : '<i class="fas fa-times"></i> BELUM VALID').'
                  </span>
                </div>
              </div>
            </div>

            <div class="text-center mt-4">
              <div class="alert alert-info d-inline-block mb-4" style="border-radius: 12px; border: none; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.1); color: #1565c0;">
                <i class="fas fa-info-circle" style="color: #1565c0;"></i> 
                <strong style="color: #0d47a1;">Catatan:</strong> <span style="color: #1565c0;">Semua persyaratan harus valid (centang hijau) untuk mengakses halaman Usulan PIP</span>
              </div>
              <br>
              <a href="./" class="btn btn-primary btn-lg" style="border-radius: 25px; padding: 12px 40px; box-shadow: 0 4px 15px rgba(0,123,255,0.3); font-weight: 600;">
                <i class="fas fa-home"></i> Kembali ke Beranda
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <style>
      .verification-cards {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 30px;
      }
      
      .verification-card {
        display: flex;
        align-items: center;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
      }
      
      .verification-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 6px;
        border-radius: 0 3px 3px 0;
      }
      
      .verification-card.valid {
        background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
        border-color: #28a745;
      }
      
      .verification-card.valid::before {
        background: #28a745;
      }
      
      .verification-card.invalid {
        background: linear-gradient(135deg, #ffeaea 0%, #f8d7da 100%);
        border-color: #dc3545;
      }
      
      .verification-card.invalid::before {
        background: #dc3545;
      }
      
      .verification-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.12);
      }
      
      .card-icon {
        font-size: 2.5rem;
        margin-right: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 60px;
      }
      
      .verification-card.valid .card-icon {
        color: #28a745;
      }
      
      .verification-card.invalid .card-icon {
        color: #dc3545;
      }
      
      .card-content {
        flex: 1;
        padding-right: 20px;
      }
      
      .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 8px;
        color: #2c3e50;
      }
      
      .card-description {
        font-size: 0.95rem;
        color: #6c757d;
        margin: 0;
        line-height: 1.4;
      }
      
      .card-status {
        display: flex;
        align-items: center;
      }
      
      .status-badge {
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: all 0.2s ease;
      }
      
      .status-badge.valid {
        background: #28a745;
        color: white;
      }
      
      .status-badge.invalid {
        background: #dc3545;
        color: white;
      }
      
      .status-badge:hover {
        transform: scale(1.05);
      }
      
      /* Responsive Design */
      @media (max-width: 768px) {
        .verification-card {
          flex-direction: column;
          text-align: center;
          padding: 20px;
        }
        
        .card-icon {
          margin-right: 0;
          margin-bottom: 15px;
          font-size: 2rem;
        }
        
        .card-content {
          padding-right: 0;
          margin-bottom: 15px;
        }
        
        .card-title {
          font-size: 1.1rem;
        }
        
        .card-description {
          font-size: 0.9rem;
        }
        
        .status-badge {
          padding: 6px 16px;
          font-size: 0.8rem;
        }
      }
      
      @media (max-width: 576px) {
        .verification-cards {
          gap: 15px;
        }
        
        .verification-card {
          padding: 15px;
        }
        
        .card-icon {
          font-size: 1.8rem;
          margin-bottom: 10px;
        }
      }
      </style>
      ';
      exit();
    }

    // Ambil data user dari database
    $user_data = [];
    if (!empty($user_id)) {
        $stmt = $connection->prepare("SELECT u.nisn, u.nama_lengkap, k.nama_kelas, u.tempat_lahir, u.tanggal_lahir, u.nik_ayah, u.nama_ayah, u.pekerjaan_ayah, u.nik_ibu, u.nama_ibu, u.pekerjaan_ibu, u.avatar, u.pekerjaan_ayah, u.pekerjaan_ibu, u.nama_wali, u.pekerjaan_wali FROM user u LEFT JOIN kelas k ON u.kelas = k.kelas_id WHERE u.user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($nisn, $nama_lengkap, $nama_kelas, $tempat_lahir, $tanggal_lahir, $nik_ayah, $nama_ayah, $pekerjaan_ayah, $nik_ibu, $nama_ibu, $pekerjaan_ibu, $avatar, $pekerjaan_ayah2, $pekerjaan_ibu2, $nama_wali, $pekerjaan_wali);
        if ($stmt->fetch()) {
          $user_data = [
            'nisn' => $nisn,
            'nama' => $nama_lengkap,
            'kelas' => $nama_kelas,
            'tempat_lahir' => $tempat_lahir,
            'tanggal_lahir' => $tanggal_lahir,
            'nik_ayah' => $nik_ayah,
            'nama_ayah' => $nama_ayah,
            'pekerjaan_ayah' => $pekerjaan_ayah,
            'nik_ibu' => $nik_ibu,
            'nama_ibu' => $nama_ibu,
            'pekerjaan_ibu' => $pekerjaan_ibu,
            'avatar' => $avatar,
            'nama_wali' => $nama_wali,
            'pekerjaan_wali' => $pekerjaan_wali
          ];
          // Status ayah/ibu meninggal jika pekerjaan = 'Sudah Meninggal' (konvensi di sistem)
          $user_data['status_ayah'] = (strtolower(trim($pekerjaan_ayah2)) === 'sudah meninggal') ? 'meninggal' : 'hidup';
          $user_data['status_ibu'] = (strtolower(trim($pekerjaan_ibu2)) === 'sudah meninggal') ? 'meninggal' : 'hidup';
        }
        $stmt->close();
    }

    // Ambil data berkas valid dari tabel berkas (dipakai untuk preview, sudah dijamin ada karena akses diperiksa)
    $berkas_data = [];
    if (!empty($user_id)) {
      $stmt = $connection->prepare("SELECT kk, akte, ijazah, kip, kks, kis FROM berkas WHERE user_id = ? AND validasi_berkas = 'valid' LIMIT 1");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->bind_result($kk, $akte, $ijazah, $kip, $kks, $kis);
      if ($stmt->fetch()) {
        $berkas_data = [
          'kk' => $kk,
          'akte' => $akte,
          'ijazah' => $ijazah,
          'kip' => $kip,
          'kks' => $kks,
          'kis' => $kis,
        ];
      }
      $stmt->close();
    }

    // Proses pesan
    $message = '';
    if (!empty($_GET['msg']) && $_GET['msg'] === 'success') {
      $message = '<div class="alert alert-success border-left-success shadow-sm persistent-alert" style="display: block !important;">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x mr-3 text-success"></i>
            <div>
              <h5 class="alert-heading mb-2"><i class="fas fa-thumbs-up"></i> Usulan Berhasil Dikirim!</h5>
              <p class="mb-2">Usulan PIP berhasil dikirim. Status: <b>Menunggu verifikasi</b>.</p>
              <p class="mb-0"><i class="fas fa-info-circle"></i> Tim admin akan memverifikasi usulan Anda dalam 1-3 hari kerja.</p>
            </div>
          </div>
          <div class="ml-3">
            <a href="./" class="btn btn-success btn-sm">
              <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
          </div>
        </div>
      </div>';
    } elseif (!empty($_GET['error'])) {
      $message = '<div class="alert alert-danger border-left-danger shadow-sm persistent-alert" style="display: block !important;">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x mr-3 text-danger"></i>
            <div>
              <h5 class="alert-heading mb-2"><i class="fas fa-times-circle"></i> Terjadi Kesalahan</h5>
              <p class="mb-0">' . htmlspecialchars($_GET['error']) . '</p>
            </div>
          </div>
          <div class="ml-3">
            <a href="./" class="btn btn-danger btn-sm">
              <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
          </div>
        </div>
      </div>';
    } elseif (!empty($_GET['msg'])) {
      $message = '<div class="alert alert-info border-left-info shadow-sm persistent-alert" style="display: block !important;">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x mr-3 text-info"></i>
            <div>
              <h5 class="alert-heading mb-2"><i class="fas fa-bell"></i> Informasi</h5>
              <p class="mb-0">' . htmlspecialchars($_GET['msg']) . '</p>
            </div>
          </div>
          <div class="ml-3">
            <a href="./" class="btn btn-info btn-sm">
              <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
          </div>
        </div>
      </div>';
    }

    // Cek status usulan PIP pada semester berjalan berdasarkan user_id
    $has_pending = false;
    $pending_html = '';
    $form_disabled_attr = '';
    // hitung semester sekarang (tetap ditampilkan untuk info)
    $semester_now = date('Y') . ((date('n') <= 6) ? ' Genap' : ' Ganjil');
    if (!empty($user_id)) {
      // Hanya cek usulan dengan status Pending atau Disetujui (tidak termasuk Ditolak)
      $stmt_semester = $connection->prepare("SELECT COUNT(*) FROM usulan_pip WHERE user_id = ? AND status IN ('Pending','Disetujui')");
      $stmt_semester->bind_param("i", $user_id);
      $stmt_semester->execute();
      $stmt_semester->bind_result($pending_count);
      $stmt_semester->fetch();
      $stmt_semester->close();
      if ($pending_count > 0) {
        $has_pending = true;
        $form_disabled_attr = 'disabled';
        $pending_html = '
        <div class="alert alert-warning border-left-warning shadow-sm persistent-alert" style="display: block !important;">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
              <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
              <div>
                <h5 class="alert-heading mb-2"><i class="fas fa-clock"></i> Usulan Semester Ini Sudah Ada</h5>
                <p class="mb-2">Anda sudah pernah mengajukan usulan PIP pada semester ini (<b>' . htmlspecialchars($semester_now) . '</b>).</p>
                <p class="mb-0"><i class="fas fa-info-circle"></i> Usulan hanya dapat dilakukan <b>1x per semester</b>. Formulir terkunci.</p>
              </div>
            </div>
            <div class="ml-3">
              <a href="./" class="btn btn-warning btn-sm">
                <i class="fas fa-home"></i> Kembali ke Beranda
              </a>
            </div>
          </div>
        </div>';
        // Ambil data usulan terakhir untuk ditampilkan pada form yang terkunci
        $usulan_saved = null;
  $stmt_us = $connection->prepare("SELECT usulan_pip_id, penghasilan_ayah, penghasilan_ibu, pertanyaan_1, kks_file, pertanyaan_2, kip_file, keterangan, status, tanggal_pengajuan, alasan_usulan, tempat_tinggal, no_kks, no_kip FROM usulan_pip WHERE user_id = ? ORDER BY tanggal_pengajuan DESC, usulan_pip_id DESC LIMIT 1");
        if ($stmt_us) {
          $stmt_us->bind_param('i', $user_id);
          $stmt_us->execute();
          $res_us = $stmt_us->get_result();
          if ($res_us && $res_us->num_rows) {
            $usulan_saved = $res_us->fetch_assoc();
          }
          $stmt_us->close();
        }
      }
      else {
        // Jika tidak ada pending, periksa apakah pengajuan terakhir ditolak agar bisa ditampilkan kepada siswa
        $stmt_last = $connection->prepare("SELECT usulan_pip_id, penghasilan_ayah, penghasilan_ibu, pertanyaan_1, no_kks, kks_file, pertanyaan_2, no_kip, kip_file, keterangan, status, tanggal_pengajuan, alasan_usulan, tempat_tinggal FROM usulan_pip WHERE user_id = ? ORDER BY tanggal_pengajuan DESC, usulan_pip_id DESC LIMIT 1");
        if ($stmt_last) {
          $stmt_last->bind_param('i', $user_id);
          $stmt_last->execute();
          $res_last = $stmt_last->get_result();
          if ($res_last && $res_last->num_rows) {
            $last = $res_last->fetch_assoc();
            if (isset($last['status']) && strtolower($last['status']) === 'ditolak') {
              $usulan_saved = $last; // prefill form with last rejected data
              $rejected_msg = htmlspecialchars($last['keterangan'] ?? 'Tidak ada catatan.');
              $rejected_date = htmlspecialchars($last['tanggal_pengajuan'] ?? '');
              $pending_html .= '<div class="alert alert-danger border-left-danger shadow-sm persistent-alert" style="display: block !important;">'
                . '<div class="d-flex align-items-center justify-content-between">'
                . '<div class="d-flex align-items-center">'
                . '<i class="fas fa-times-circle fa-2x mr-3"></i>'
                . '<div>'
                . '<h5 class="alert-heading mb-2"><i class="fas fa-exclamation-circle"></i> Pengajuan Sebelumnya Ditolak</h5>'
                . '<p class="mb-2">Pengajuan terakhir Anda pada <b>'. $rejected_date .'</b> ditolak.</p>'
                . '<p class="mb-0"><strong>Catatan:</strong> ' . $rejected_msg . '</p>'
                . '<p class="mb-0 mt-2"><i class="fas fa-info-circle"></i> Silakan perbaiki data lalu <b>Ajukan Ulang</b>.</p>'
                . '</div></div>'
                . '<div class="ml-3">'
                . '<a href="./" class="btn btn-danger btn-sm">'
                . '<i class="fas fa-home"></i> Kembali ke Beranda'
                . '</a></div></div></div>';
            }
          }
          $stmt_last->close();
        }
      }
    }

    // Definisi tombol submit (dinonaktifkan saat ada pending)
    $submit_button_html = '<button type="submit" class="btn btn-primary btn-block" ' . ($has_pending ? 'disabled' : '') . '>Ajukan Usulan PIP</button>';

    // Cek ketersediaan file KKS/KIP untuk mengunci pilihan "Ya" jika tidak ada berkas
    $kks_available = !empty($berkas_data['kks']) || !empty($usulan_saved['kks_file'] ?? '');
    $kip_available = !empty($berkas_data['kip']) || !empty($usulan_saved['kip_file'] ?? '');

    ?>
<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Usulan PIP</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6 pip-page-container">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card mb-4 pip-card">
        <div class="card-header">
          <h3 class="mb-0"><i class="fas fa-hand-holding-usd text-primary"></i> Form Usulan Program Indonesia Pintar (PIP)</h3>
        </div>
        <div class="card-body">
          <?php echo $message ?? ''; ?>
          <!-- Peringatan hanya 1x per semester -->
          <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle"></i> Usulan PIP dapat dilakukan jika tidak ada usulan yang sedang <b>Menunggu</b> atau sudah <b>Disetujui</b>. Usulan yang <b>Ditolak</b> dapat diajukan ulang.
          </div>
          <?php echo $pending_html; ?>

          <?php // Form tetap tampil, hanya dikunci jika $has_pending ?>
          <form method="post" action="mod/usulan-pip/proses.php?action=add_usulan" enctype="multipart/form-data" id="formUsulanPip"
            <?php if ($has_pending): ?>style="pointer-events:none;opacity:0.7;" aria-disabled="true" data-locked="1"<?php endif; ?>>
            
            <!-- Foto User -->
            <div class="section-title">
              <i class="fas fa-camera text-primary"></i> Foto Siswa
            </div>
            <div class="row mb-4 justify-content-center">
              <div class="col-12 text-center">
                <img src="../content/avatar/<?php echo htmlspecialchars($user_data['avatar'] ?? ''); ?>" alt="Foto Siswa" class="avatar-preview mb-2">
                <p class="text-muted small">Foto Profil Siswa</p>
                <div class="alert alert-info mt-3 d-inline-block text-left" style="max-width:400px;">
                  <i class="fas fa-lightbulb"></i> Foto profil diambil dari data yang sudah tersimpan di sistem.<br>
                  Jika perlu mengubah foto, silakan hubungi admin sekolah.
                </div>
              </div>
            </div>

            <!-- Identitas Murid -->
            <div class="section-title">
              <i class="fas fa-user-graduate text-success"></i> Identitas Siswa
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">NISN</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['nisn'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Nama Lengkap</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['nama'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Kelas</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['kelas'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Tempat, Tanggal Lahir</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars(($user_data['tempat_lahir'] ?? '') . (empty($user_data['tanggal_lahir']) ? '' : ', ' . date('d F Y', strtotime($user_data['tanggal_lahir'])))); ?>" readonly>
                </div>
              </div>
            </div>

            <!-- Identitas Orang Tua -->
            <div class="section-title mt-4">
              <i class="fas fa-users text-info"></i> Identitas Orang Tua Kandung
            </div>            
            <!-- Data Ayah -->
            <h6 class="text-primary font-weight-bold mb-3"><i class="fas fa-male"></i> Data Ayah</h6>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">NIK Ayah</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['nik_ayah'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Nama Ayah</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['nama_ayah'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Pekerjaan Ayah</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['pekerjaan_ayah'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Penghasilan Ayah per Bulan</label>
                    <?php
                    // Deteksi jika pekerjaan ayah menunjukkan tidak bekerja
                    $ayah_not_working = isset($user_data['pekerjaan_ayah']) && preg_match('/tidak/i', $user_data['pekerjaan_ayah']);
                    if (($user_data['status_ayah'] ?? '') == 'meninggal'): ?>
                    <input type="text" class="form-control" value="Tidak Berpenghasilan (Almarhum)" readonly style="background-color: #f8f9fa; color: #6c757d;">
                  <?php else:
                    if ($ayah_not_working):
                      // tampilkan readonly + hidden input agar nilai tetap tersubmit
                      ?>
                      <input type="text" class="form-control" value="Tidak Berpenghasilan (Tidak Bekerja)" readonly style="background-color: #f8f9fa; color: #6c757d;">
                      <input type="hidden" name="penghasilan_ayah" value="Tidak Berpenghasilan">
                    <?php else: ?>
                    <select class="form-control" name="penghasilan_ayah" <?php echo $form_disabled_attr; ?> required>
                      <option value="">-- Pilih Penghasilan --</option>
                      <?php
                        $opts = [
                          'Tidak Berpenghasilan','Kurang dari Rp. 500,000','Rp. 500,000 - Rp. 999,999','Rp. 1,000,000 - Rp. 1,999,999','Rp. 2,000,000 - Rp. 4,999,999','Rp. 5,000,000 - Rp. 20,000,000','Lebih dari Rp. 20,000,000'
                        ];
                        foreach ($opts as $o) {
                          $sel = '';
                          if (isset($usulan_saved['penghasilan_ayah']) && $usulan_saved['penghasilan_ayah'] === $o) {
                            $sel = ' selected';
                          }
                          echo '<option value="'.htmlspecialchars($o).'"'.$sel.'>'.htmlspecialchars($o).'</option>';
                        }
                      ?>
                    </select>
                  <?php endif; endif; ?>
                </div>
              </div>
            </div>

            <!-- Data Ibu -->
            <h6 class="text-pink font-weight-bold mb-3 mt-4"><i class="fas fa-female"></i> Data Ibu</h6>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">NIK Ibu</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['nik_ibu'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Nama Ibu</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['nama_ibu'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Pekerjaan Ibu</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['pekerjaan_ibu'] ?? ''); ?>" readonly>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="font-weight-bold">Penghasilan Ibu per Bulan</label>
                    <?php
                    // Deteksi jika pekerjaan ibu menunjukkan tidak bekerja
                    $ibu_not_working = isset($user_data['pekerjaan_ibu']) && preg_match('/tidak/i', $user_data['pekerjaan_ibu']);
                    if (($user_data['status_ibu'] ?? '') == 'meninggal'): ?>
                    <input type="text" class="form-control" value="Tidak Berpenghasilan (Almarhum)" readonly style="background-color: #f8f9fa; color: #6c757d;">
                  <?php else:
                    if ($ibu_not_working): ?>
                      <input type="text" class="form-control" value="Tidak Berpenghasilan (Tidak Bekerja)" readonly style="background-color: #f8f9fa; color: #6c757d;">
                      <input type="hidden" name="penghasilan_ibu" value="Tidak Berpenghasilan">
                    <?php else: ?>
                    <select class="form-control" name="penghasilan_ibu" <?php echo $form_disabled_attr; ?> required>
                      <option value="">-- Pilih Penghasilan --</option>
                      <?php
                        foreach ($opts as $o) {
                          $sel = '';
                          if (isset($usulan_saved['penghasilan_ibu']) && $usulan_saved['penghasilan_ibu'] === $o) {
                            $sel = ' selected';
                          }
                          echo '<option value="'.htmlspecialchars($o).'"'.$sel.'>'.htmlspecialchars($o).'</option>';
                        }
                      ?>
                    </select>
                  <?php endif; endif; ?>
                </div>
              </div>
            </div>
            
              <!-- Data Wali -->
                <div id="formWaliSection" style="display:none;">
                  <h6 class="text-info font-weight-bold mb-3 mt-4"><i class="fas fa-user-shield"></i> Data Wali</h6>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label class="font-weight-bold">Nama Wali</label>
                        <input type="text" class="form-control" name="nama_wali" value="<?php echo htmlspecialchars($user_data['nama_wali'] ?? ''); ?>" readonly>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label class="font-weight-bold">Pekerjaan Wali</label>
                        <input type="text" class="form-control" name="pekerjaan_wali" value="<?php echo htmlspecialchars($user_data['pekerjaan_wali'] ?? ''); ?>" readonly>
                      </div>
                    </div>
                  </div>
                </div>

            <!-- Pertanyaan KPS/PKH -->
            <div class="section-title mt-4">
              <i class="fas fa-question-circle text-warning"></i> Pertanyaan Pendukung
            </div>

              <!-- Pertanyaan Tempat Tinggal -->
              <div class="form-group">
                <label class="font-weight-bold">Tempat Tinggal Saat Ini <span class="text-danger">*</span></label>
                  <select class="form-control" name="tempat_tinggal" id="tempatTinggalSelect" required onchange="toggleFormWali()">
                    <option value="">-- Pilih Tempat Tinggal --</option>
                    <option value="Bersama orang tua" <?php echo (isset($usulan_saved['tempat_tinggal']) && $usulan_saved['tempat_tinggal']=='Bersama orang tua') ? 'selected' : '';?>>Bersama orang tua</option>
                    <option value="Wali" <?php echo (isset($usulan_saved['tempat_tinggal']) && $usulan_saved['tempat_tinggal']=='Wali') ? 'selected' : '';?>>Wali</option>
                    <option value="Kost" <?php echo (isset($usulan_saved['tempat_tinggal']) && $usulan_saved['tempat_tinggal']=='Kost') ? 'selected' : '';?>>Kost</option>
                    <option value="Asrama" <?php echo (isset($usulan_saved['tempat_tinggal']) && $usulan_saved['tempat_tinggal']=='Asrama') ? 'selected' : '';?>>Asrama</option>
                    <option value="Panti asuhan" <?php echo (isset($usulan_saved['tempat_tinggal']) && $usulan_saved['tempat_tinggal']=='Panti asuhan') ? 'selected' : '';?>>Panti asuhan</option>
                    <option value="Pesantren" <?php echo (isset($usulan_saved['tempat_tinggal']) && $usulan_saved['tempat_tinggal']=='Pesantren') ? 'selected' : '';?>>Pesantren</option>
                    <option value="Lainnya" <?php echo (isset($usulan_saved['tempat_tinggal']) && $usulan_saved['tempat_tinggal']=='Lainnya') ? 'selected' : '';?>>Lainnya</option>
                  </select>
              </div>
            
            <div class="form-group">
              <label class="font-weight-bold">Apakah penerima KPS/PKH? <span class="text-danger">*</span></label>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="penerima_kps" id="kps_ya" value="Y" <?php echo $form_disabled_attr; ?> onclick="toggleKPSForm(true)" <?php echo (isset($usulan_saved['pertanyaan_1']) && $usulan_saved['pertanyaan_1']=='Ya') ? 'checked' : '';?> <?php echo (!$kks_available ? 'disabled title="File KKS tidak tersedia. Hubungi admin."' : ''); ?> required>
                <label class="form-check-label" for="kps_ya">Ya, penerima KPS/PKH</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="penerima_kps" id="kps_tidak" value="N" <?php echo $form_disabled_attr; ?> onclick="toggleKPSForm(false)" <?php echo (isset($usulan_saved['pertanyaan_1']) && $usulan_saved['pertanyaan_1']=='Tidak') ? 'checked' : '';?> required>
                <label class="form-check-label" for="kps_tidak">Tidak</label>
              </div>
              <?php if (!$kks_available): ?>
                <small class="form-text text-danger">File KKS tidak ditemukan. Apabila memiliki silahkan unggah di berkas.</small>
              <?php endif; ?>
              
              <div id="kps_form" style="display: none;" class="mt-3 p-3 border rounded bg-light">
                <div class="alert alert-info">
                  <i class="fas fa-info-circle"></i> Berkas KKS (diambil dari berkas valid yang tersimpan di sistem)
                </div>
                <div class="form-group">
                  <label class="font-weight-bold">File KKS</label>
                  <?php
                    $base_berkas = '../content/berkas/';
                    $kks_file = $berkas_data['kks'] ?? ($usulan_saved['kks_file'] ?? '');
                    if (!empty($kks_file)) {
                      $kks_url = $base_berkas . htmlspecialchars($kks_file);
                      $kks_ext = strtolower(pathinfo($kks_file, PATHINFO_EXTENSION));
                      if (in_array($kks_ext, ['jpg','jpeg','png'])) {
                        // tampilkan gambar yang bisa diklik untuk dibesarkan
                        echo '<div id="kks_preview_container" class="berkas-preview"><a href="#" onclick="openPreviewImage(\'' . $kks_url . '\'); return false;"><img src="'.$kks_url.'" alt="KKS" style="max-width:120px;max-height:120px;border:1px solid #ccc;padding:4px;background:#fff;"></a><div class="small text-muted mt-1">'.htmlspecialchars($kks_file).'</div></div>';
                      } elseif ($kks_ext === 'pdf') {
                        // untuk pdf, buka preview PDF di modal
                        echo '<div id="kks_preview_container" class="berkas-preview"><a href="#" onclick="openPreviewPdf(\'' . $kks_url . '\'); return false;" class="btn btn-sm btn-primary"><i class="fas fa-file-pdf"></i> Lihat PDF</a><div class="small text-muted mt-1">'.htmlspecialchars($kks_file).'</div></div>';
                      } else {
                        echo '<div id="kks_preview_container" class="berkas-preview"><a href="'.$kks_url.'" target="_blank" class="btn btn-sm btn-secondary">Download</a><div class="small text-muted mt-1">'.htmlspecialchars($kks_file).'</div></div>';
                      }
                    } else {
                      echo '<div id="kks_preview_container" class="berkas-preview text-muted">Belum ada file KKS</div>';
                    }
                  ?>
                </div>
                <div class="form-group">
                  <label class="font-weight-bold">Nomor KKS <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="nomor_kks" id="nomor_kks" placeholder="Masukkan nomor KKS (6-16 huruf/angka)" pattern="[A-Z0-9]{6,16}" maxlength="16" autocomplete="off" style="text-transform:uppercase" value="<?php echo htmlspecialchars($usulan_saved['no_kks'] ?? ''); ?>">
                  <small class="form-text text-muted">Nomor KKS harus 6-16 karakter huruf/angka (kapital).</small>
                </div>
              </div>

            <!-- Pertanyaan KIP -->
            <div class="form-group mt-4">
              <label class="font-weight-bold">Apakah punya KIP? <span class="text-danger">*</span></label>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="punya_kip" id="kip_ya" value="Y" <?php echo $form_disabled_attr; ?> onclick="toggleKIPForm(true)" <?php echo (isset($usulan_saved['pertanyaan_2']) && $usulan_saved['pertanyaan_2']=='Ya') ? 'checked' : '';?> <?php echo (!$kip_available ? 'disabled title="File KIP tidak tersedia. Hubungi admin."' : ''); ?> required>
                <label class="form-check-label" for="kip_ya">Ya, punya KIP</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="punya_kip" id="kip_tidak" value="N" <?php echo $form_disabled_attr; ?> onclick="toggleKIPForm(false)" <?php echo (isset($usulan_saved['pertanyaan_2']) && $usulan_saved['pertanyaan_2']=='Tidak') ? 'checked' : '';?> required>
                <label class="form-check-label" for="kip_tidak">Tidak</label>
              </div>
              <?php if (!$kip_available): ?>
                <small class="form-text text-danger">File KIP tidak ditemukan. Apabila memiliki silahkan unggah di berkas.</small>
              <?php endif; ?>
              
              <div id="kip_form" style="display: none;" class="mt-3 p-3 border rounded bg-light">
                <div class="alert alert-info">
                  <i class="fas fa-info-circle"></i> Berkas KIP (diambil dari berkas valid yang tersimpan di sistem)
                </div>
                <div class="form-group">
                  <label class="font-weight-bold">File KIP</label>
                  <?php
                    $base_berkas = '../content/berkas/';
                    $kip_file = $berkas_data['kip'] ?? ($usulan_saved['kip_file'] ?? '');
                    if (!empty($kip_file)) {
                      $kip_url = $base_berkas . htmlspecialchars($kip_file);
                      $kip_ext = strtolower(pathinfo($kip_file, PATHINFO_EXTENSION));
                      if (in_array($kip_ext, ['jpg','jpeg','png'])) {
                        echo '<div id="kip_preview_container" class="berkas-preview"><a href="#" onclick="openPreviewImage(\'' . $kip_url . '\'); return false;"><img src="'.$kip_url.'" alt="KIP" style="max-width:120px;max-height:120px;border:1px solid #ccc;padding:4px;background:#fff;"></a><div class="small text-muted mt-1">'.htmlspecialchars($kip_file).'</div></div>';
                      } elseif ($kip_ext === 'pdf') {
                        echo '<div id="kip_preview_container" class="berkas-preview"><a href="#" onclick="openPreviewPdf(\'' . $kip_url . '\'); return false;" class="btn btn-sm btn-primary"><i class="fas fa-file-pdf"></i> Lihat PDF</a><div class="small text-muted mt-1">'.htmlspecialchars($kip_file).'</div></div>';
                      } else {
                        echo '<div id="kip_preview_container" class="berkas-preview"><a href="'.$kip_url.'" target="_blank" class="btn btn-sm btn-secondary">Download</a><div class="small text-muted mt-1">'.htmlspecialchars($kip_file).'</div></div>';
                      }
                    } else {
                      echo '<div id="kip_preview_container" class="berkas-preview text-muted">Belum ada file KIP</div>';
                    }
                  ?>
                </div>
                <div class="form-group">
                  <label class="font-weight-bold">Nomor KIP <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="nomor_kip" id="nomor_kip" placeholder="Masukkan nomor KIP (6-16 huruf/angka)" pattern="[A-Z0-9]{6,16}" maxlength="16" autocomplete="off" style="text-transform:uppercase" value="<?php echo htmlspecialchars($usulan_saved['no_kip'] ?? ''); ?>">
                  <small class="form-text text-muted">Nomor KIP harus 6-16 karakter huruf/angka (kapital).</small>
                </div>
              </div>
            </div>
            

            <!-- Alasan Usulan PIP -->

            <div class="form-group mt-4">
              <label class="font-weight-bold">Alasan Pengajuan Usulan PIP <span class="text-danger">*</span></label>
              <textarea class="form-control" name="alasan_usulan" rows="3" maxlength="255" placeholder="Jelaskan alasan pengajuan usulan PIP..." required <?php echo $form_disabled_attr; ?>><?php echo isset($usulan_saved['alasan_usulan']) ? htmlspecialchars($usulan_saved['alasan_usulan']) : ''; ?></textarea>
              <small class="form-text text-muted">Tuliskan alasan utama Anda mengajukan usulan PIP. Maksimal 255 karakter.</small>
              <?php if ($has_pending && isset($usulan_saved['alasan_usulan'])): ?>
                <div class="alert alert-secondary mt-2" style="font-size:0.95em;">
                  <strong>Alasan Usulan Terkirim:</strong><br>
                  <?php echo nl2br(htmlspecialchars($usulan_saved['alasan_usulan'])); ?>
                </div>
              <?php endif; ?>
            </div>

            <hr class="my-4">
            <?php echo $submit_button_html; ?>
          </form>

          <?php if ($has_pending): ?>
            <script src="mod/usulan-pip/usulan-pip.js"></script>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Spacer -->
  <div style="height:140px" aria-hidden="true"></div>
</div>

<!-- Tambahkan CSS untuk alert persistent -->
<style>
.persistent-alert {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 1000 !important;
    margin-bottom: 20px !important;
    animation: slideIn 0.5s ease-in-out;
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Prevent any hiding mechanisms */
.persistent-alert,
.persistent-alert.show,
.persistent-alert.fade,
.persistent-alert.alert-dismissible {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    transition: none !important;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.persistent-alert .close,
.persistent-alert .btn-close,
.persistent-alert [data-dismiss="alert"],
.persistent-alert [data-bs-dismiss="alert"] {
    display: none !important;
}

.persistent-alert .btn {
    white-space: nowrap;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
    border: none;
    padding: 8px 16px;
    font-size: 14px;
}

.persistent-alert .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.persistent-alert .alert-heading {
    font-weight: 600;
    margin-bottom: 8px;
}

.persistent-alert .fa-2x {
    font-size: 1.5em;
}

/* Custom border styling for different alert types */
.persistent-alert.alert-success {
    border-left: 5px solid #28a745;
    background: linear-gradient(90deg, rgba(40,167,69,0.1) 0%, rgba(40,167,69,0.05) 100%);
}

.persistent-alert.alert-warning {
    border-left: 5px solid #ffc107;
    background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0.05) 100%);
}

.persistent-alert.alert-danger {
    border-left: 5px solid #dc3545;
    background: linear-gradient(90deg, rgba(220,53,69,0.1) 0%, rgba(220,53,69,0.05) 100%);
}

.persistent-alert.alert-info {
    border-left: 5px solid #17a2b8;
    background: linear-gradient(90deg, rgba(23,162,184,0.1) 0%, rgba(23,162,184,0.05) 100%);
}

/* Responsive untuk mobile */
@media (max-width: 768px) {
    .persistent-alert .d-flex {
        flex-direction: column;
        align-items: stretch !important;
    }
    
    .persistent-alert .ml-3 {
        margin-left: 0 !important;
        margin-top: 15px !important;
        text-align: center;
    }
    
    .persistent-alert .btn {
        width: 100%;
    }
    
    .persistent-alert .fa-2x {
        font-size: 1.3em;
    }
}

/* Override semua kemungkinan Bootstrap alert classes */
.alert.fade,
.alert.show,
.alert.hide,
.alert.hiding {
    opacity: 1 !important;
    display: block !important;
}

/* Custom SweetAlert styling */
.swal-custom-popup {
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}

/* Page transition effect */
body {
    transition: opacity 0.3s ease;
}

/* Icon styling enhancements */
.persistent-alert .fas {
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
</style>

<!-- Script untuk mempertahankan alert -->
<script>
(function() {
    'use strict';
    
    let preservationActive = false;
    
    function forcePreservePersistentAlerts() {
        if (preservationActive) return;
        preservationActive = true;
        
        const alerts = document.querySelectorAll('.persistent-alert');
        alerts.forEach(function(alert) {
            // Remove any close buttons immediately
            const closeButtons = alert.querySelectorAll('.close, .btn-close, [data-dismiss="alert"], [data-bs-dismiss="alert"]');
            closeButtons.forEach(btn => {
                btn.remove();
                btn.parentNode && btn.parentNode.removeChild(btn);
            });
            
            // Force visibility styles
            alert.style.cssText += 'display: block !important; opacity: 1 !important; visibility: visible !important;';
            alert.classList.remove('fade', 'hide', 'hiding', 'collapse', 'collapsing');
            alert.classList.add('show');
            
            // Remove all event listeners
            const newAlert = alert.cloneNode(true);
            if (alert.parentNode) {
                alert.parentNode.replaceChild(newAlert, alert);
            }
        });
        
        preservationActive = false;
    }
    
    // Override semua method Bootstrap yang bisa menyembunyikan alert
    if (window.bootstrap) {
        if (window.bootstrap.Alert) {
            const originalClose = window.bootstrap.Alert.prototype.close;
            window.bootstrap.Alert.prototype.close = function() {
                if (this._element && (this._element.classList.contains('persistent-alert') || this._element.querySelector('.persistent-alert'))) {
                    return false;
                }
                return originalClose.call(this);
            };
        }
    }
    
    // Override jQuery methods yang bisa menyembunyikan alert
    if (window.jQuery) {
        const $ = window.jQuery;
        const originalHide = $.fn.hide;
        const originalFadeOut = $.fn.fadeOut;
        const originalRemove = $.fn.remove;
        
        $.fn.hide = function() {
            if (this.hasClass('persistent-alert') || this.find('.persistent-alert').length) {
                return this;
            }
            return originalHide.apply(this, arguments);
        };
        
        $.fn.fadeOut = function() {
            if (this.hasClass('persistent-alert') || this.find('.persistent-alert').length) {
                return this;
            }
            return originalFadeOut.apply(this, arguments);
        };
        
        $.fn.remove = function() {
            if (this.hasClass('persistent-alert') || this.find('.persistent-alert').length) {
                return this;
            }
            return originalRemove.apply(this, arguments);
        };
    }
    
    // Initial preservation ketika DOM ready
    document.addEventListener('DOMContentLoaded', forcePreservePersistentAlerts);
    
    // Preservation ketika window loaded
    window.addEventListener('load', forcePreservePersistentAlerts);
    
    // Monitor DOM changes dengan MutationObserver
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            let needsPreservation = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    const target = mutation.target;
                    if (target.classList && target.classList.contains('persistent-alert')) {
                        needsPreservation = true;
                    }
                }
                if (mutation.type === 'childList') {
                    needsPreservation = true;
                }
            });
            
            if (needsPreservation) {
                setTimeout(forcePreservePersistentAlerts, 1);
            }
        });
        
        observer.observe(document.body, {
            attributes: true,
            childList: true,
            subtree: true,
            attributeFilter: ['style', 'class']
        });
    }
    
    // Interval backup untuk memastikan alert tetap terlihat
    setInterval(forcePreservePersistentAlerts, 500);
    
    // Immediate execution
    forcePreservePersistentAlerts();
})();
</script>

<!-- Tambahkan overlay preview (modal ringan) -->
<div id="previewOverlay" role="dialog" aria-hidden="true" onclick="closePreviewModal()">
  <div id="previewCloseBtn" aria-hidden="true" onclick="closePreviewModal(); event.stopPropagation();">&times;</div>
  <div id="previewBox" onclick="event.stopPropagation()">
    <img id="previewImage" src="" alt="Preview" style="display:none;">
    <iframe id="previewPdf" src="" style="display:none;"></iframe>
  </div>
</div>


<?php
// Tutup blok if yang masih terbuka
  } // end if isset($_COOKIE['siswa'])
} // end else (connection check)
?>
