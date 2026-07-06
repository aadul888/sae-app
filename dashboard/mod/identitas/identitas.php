<?php
if (empty($connection)) {
  echo 'Koneksi tidak ditemukan';
  header('location:../');
  exit();
} else {
  if (isset($_COOKIE['siswa'])) {
    $siswa_id = $data_user['user_id'] ?? '';
    $data = [];
    if (!empty($siswa_id)) {
      $q = $connection->query("SELECT u.*, k.nama_kelas FROM user u LEFT JOIN kelas k ON u.kelas = k.kelas_id WHERE u.user_id='$siswa_id'");
      if ($q && $q->num_rows > 0) {
        $data = $q->fetch_assoc();
      }
    }
    // Cek foto avatar (prioritaskan kolom DB `avatar` yang mungkin berisi "file?t=timestamp")
    $foto_src = '../content/avatar/avatar.jpg';
    $nisn = $data['nisn'] ?? '';

    // Jika kolom avatar dari DB ada, gunakan itu (bentuk yang disimpan: "filename.ext?t=..." atau "filename.ext")
    if (!empty($data['avatar'])) {
      $avatar_db = $data['avatar'];
      // Ambil nama file tanpa query string untuk pengecekan filesystem
      $avatar_file = preg_replace('/\?.*/', '', $avatar_db);
      $avatar_path = '../content/avatar/' . $avatar_file;
      if (!empty($avatar_file) && file_exists($avatar_path)) {
        // Gunakan nilai DB utuh (termasuk ?t=...) sebagai src sehingga browser akan mem-bust cache
        $foto_src = '../content/avatar/' . $avatar_db;
      }
    }

    // Jika belum ditemukan dari DB, fallback ke file berdasarkan NISN (tambahkan filemtime sebagai cache-buster)
    if ($foto_src === '../content/avatar/avatar.jpg') {
      $foto_jpg = '../content/avatar/' . $nisn . '.jpg';
      $foto_png = '../content/avatar/' . $nisn . '.png';
      if (!empty($nisn)) {
        if (file_exists($foto_jpg)) {
          $foto_src = $foto_jpg . '?t=' . filemtime($foto_jpg);
        } elseif (file_exists($foto_png)) {
          $foto_src = $foto_png . '?t=' . filemtime($foto_png);
        }
      }
    }
    echo '
<!-- Header -->
<div class="header pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-12">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Identitas Siswa</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Page content -->
<div class="container-fluid mt--6">
  <div class="row justify-content-center g-3">';
    // Card Foto Siswa (responsive)
    echo '
    <div class="col-12 col-sm-8 col-md-4 mb-3">
      <div class="card shadow-sm h-100 student-photo-card">
        <div class="card-body text-center">
          <img src="' . $foto_src . '" alt="Foto Siswa" class="rounded mb-2 student-photo">
          <div class="mt-2 fw-bold student-name">' . htmlspecialchars($data['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') . '</div>
          <div class="text-muted student-info">NISN: <b>' . $data['nisn'] . '</b></div>
          <div class="text-muted student-info">NIS/NIPD: <b>' . $data['nipd'] . '</b></div>
          <div class="text-muted student-info">Kelas: <b>' . ($data['nama_kelas'] ?? '-') . '</b></div>';
    $status = (isset($data['status']) && ($data['status'] == 'Aktif' || strtolower($data['status']) == 'aktif')) ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Tidak Aktif</span>';
    echo '<div class="mt-2">Status: ' . $status . '</div>
        </div>
      </div>
    </div>
    ';
    echo '</div></div>';
    // HEADER BAR MIRIP GAMBAR, TAPI SESUAI PERMINTAAN
    echo '
    <div class="container-fluid mt-4">
      <div class="row">
        <div class="col-12">
          <div class="identitas-header-section-a">
            <span class="identitas-header-text">A. Identitas Peserta Didik</span>
          </div>
        </div>
      </div>
    </div>
    ';
    // IDENTITAS PESERTA DIDIK (striped, tanpa nomor, kolom label kecil)
    echo '
    <div class="container-fluid mt-3">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
          <div class="card shadow-sm mb-4">
            <div class="card-body p-2">
              <div class="table-responsive">
                <table class="table table-sm mb-0 identitas-striped identitas-table">
                  <tbody>
                    <tr><td class="identitas-label">Nama Lengkap</td><td class="identitas-value"><b>' . htmlspecialchars($data['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') . '</b></td></tr>
                    <tr><td class="identitas-label">Nomor KK</td><td class="identitas-value"><b>' . $data['no_kk'] . '</b></td></tr>
                    <tr><td class="identitas-label">NIK</td><td class="identitas-value"><b>' . $data['nik'] . '</b></td></tr>
                    <tr><td class="identitas-label">Jenis Kelamin</td><td class="identitas-value"><b>' . $data['jenis_kelamin'] . '</b></td></tr>
                    <tr><td class="identitas-label">Tempat Lahir, Tanggal Lahir</td><td class="identitas-value"><b>' . $data['tempat_lahir'] . ', ' . tgl_indo($data['tanggal_lahir']) . '</b></td></tr>
                    <tr><td class="identitas-label">Agama</td><td class="identitas-value"><b>' . $data['agama'] . '</b></td></tr>
                    <tr><td class="identitas-label">Status dalam keluarga</td><td class="identitas-value"><b>' . $data['status_keluarga'] . '</b></td></tr>
                    <tr><td class="identitas-label">Anak Ke</td><td class="identitas-value"><b>' . $data['anak_ke'] . '</b></td></tr>
                    <tr><td class="identitas-label">Alamat (Jl/Kp)</td><td class="identitas-value"><b>' . $data['alamat'] . '</b></td></tr>
                    <tr><td class="identitas-label">RT</td><td class="identitas-value"><b>' . $data['rt'] . '</b></td></tr>
                    <tr><td class="identitas-label">RW</td><td class="identitas-value"><b>' . $data['rw'] . '</b></td></tr>
                    <tr><td class="identitas-label">Provinsi</td><td class="identitas-value"><b>' . ($data['provinsi'] ?? '') . '</b></td></tr>
                    <tr><td class="identitas-label">Kabupaten/Kota</td><td class="identitas-value"><b>' . ($data['kabupaten_kota'] ?? '') . '</b></td></tr>
                    <tr><td class="identitas-label">Desa/Kelurahan</td><td class="identitas-value"><b>' . $data['desa'] . '</b></td></tr>
                    <tr><td class="identitas-label">Kecamatan</td><td class="identitas-value"><b>' . $data['kecamatan'] . '</b></td></tr>
                    <tr><td class="identitas-label">Kodepos</td><td class="identitas-value"><b>' . $data['kodepos'] . '</b></td></tr>
                    <tr><td class="identitas-label">Telp/HP</td><td class="identitas-value"><b>' . $data['telp'] . '</b></td></tr>
                    <tr><td class="identitas-label">Asal Sekolah</td><td class="identitas-value"><b>' . $data['sekolah_asal'] . '</b></td></tr>
                    <tr><td class="identitas-label">Diterima dikelas</td><td class="identitas-value"><b>' . $data['diterima_dikelas'] . '</b></td></tr>
                    <tr><td class="identitas-label">Diterima pada tanggal</td><td class="identitas-value"><b>' . tgl_indo($data['diterima_tanggal']) . '</b></td></tr>
                    <tr><td class="identitas-label">Email</td><td class="identitas-value"><b>' . $data['email'] . '</b></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    ';
    // HEADER ORANGTUA KANDUNG
    echo '
    <div class="container-fluid mt-2">
      <div class="row">
        <div class="col-12">
          <div class="identitas-header-section-b">
            <span class="identitas-header-text">B. Orangtua Kandung</span>
          </div>
        </div>
      </div>
    </div>
    ';
    // DATA ORANGTUA KANDUNG (striped, tanpa nomor, kolom label kecil)
    echo '
    <div class="container-fluid mt-3">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
          <div class="card shadow-sm mb-4">
            <div class="card-body p-2">
              <div class="table-responsive">
                <table class="table table-sm mb-0 identitas-striped identitas-table">
                  <tbody>
                    <tr><td class="identitas-label">NIK Ayah</td><td class="identitas-value"><b>' . $data['nik_ayah'] . '</b></td></tr>
                    <tr><td class="identitas-label">Nama Ayah Kandung</td><td class="identitas-value"><b>' . htmlspecialchars($data['nama_ayah'] ?? '', ENT_QUOTES, 'UTF-8') . '</b></td></tr>
                    <tr><td class="identitas-label">Pekerjaan Ayah</td><td class="identitas-value"><b>' . $data['pekerjaan_ayah'] . '</b></td></tr>
                    <tr><td class="identitas-label">NIK Ibu</td><td class="identitas-value"><b>' . $data['nik_ibu'] . '</b></td></tr>
                    <tr><td class="identitas-label">Nama Ibu Kandung</td><td class="identitas-value"><b>' . htmlspecialchars($data['nama_ibu'] ?? '', ENT_QUOTES, 'UTF-8') . '</b></td></tr>
                    <tr><td class="identitas-label">Pekerjaan Ibu</td><td class="identitas-value"><b>' . $data['pekerjaan_ibu'] . '</b></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    ';
    // HEADER WALI
    echo '
    <div class="container-fluid mt-2">
      <div class="row">
        <div class="col-12">
          <div class="identitas-header-section-c">
            <span class="identitas-header-text">C. Wali</span>
          </div>
        </div>
      </div>
    </div>
    ';
    // DATA WALI (striped, tanpa nomor, kolom label kecil)
    echo '
    <div class="container-fluid mt-3 mb-4">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
          <div class="card shadow-sm mb-4">
            <div class="card-body p-2">
              <div class="table-responsive">
                <table class="table table-sm mb-0 identitas-striped identitas-table">
                  <tbody>
                    <tr><td class="identitas-label">Nama Wali</td><td class="identitas-value"><b>' . htmlspecialchars($data['nama_wali'] ?? '', ENT_QUOTES, 'UTF-8') . '</b></td></tr>
                    <tr><td class="identitas-label">Alamat Wali</td><td class="identitas-value"><b>' . $data['alamat_wali'] . '</b></td></tr>
                    <tr><td class="identitas-label">Telp/HP Wali</td><td class="identitas-value"><b>' . $data['telp_wali'] . '</b></td></tr>
                    <tr><td class="identitas-label">Pekerjaan Wali</td><td class="identitas-value"><b>' . $data['pekerjaan_wali'] . '</b></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    ';

    // Form Konfirmasi di paling bawah
    // Ambil status konfirmasi dan waktu perubahan terakhir
    $konfirmasi_status = $data['konfirmasi'] ?? '';
    $konfirmasi_time = isset($data['konfirmasi_time']) ? intval($data['konfirmasi_time']) : 0;
    $konfirmasi_data = isset($data['konfirmasi_data']) ? $data['konfirmasi_data'] : '';

    $snapshot = $konfirmasi_data ? json_decode($konfirmasi_data, true) : [];
    $fields_to_check = [
      'nik',
      'no_kk',
      'nipd',
      'nisn',
      'nama_lengkap',
      'tempat_lahir',
      'tanggal_lahir',
      'jenis_kelamin',
      'kelas',
      'nik_ayah',
      'nama_ayah',
      'pekerjaan_ayah',
      'nik_ibu',
      'nama_ibu',
      'pekerjaan_ibu',
      'alamat',
      'provinsi',
      'kabupaten_kota',
      'email',
      'password',
      'telp',
      'anak_ke',
      'avatar',
      'time',
      'date',
      'status'
    ];
    $data_changed = false;
    if ($konfirmasi_time == 0 || empty($snapshot)) {
      $data_changed = true;
    } else {
      foreach ($fields_to_check as $field) {
        if (!array_key_exists($field, $snapshot) || !array_key_exists($field, $data) || $snapshot[$field] != $data[$field]) {
          $data_changed = true;
          break;
        }
      }
    }

    // Tombol konfirmasi tetap tampil, namun dikunci jika sudah konfirmasi
    $btn_disabled = ($konfirmasi_status !== 'Belum Konfirmasi') ? 'disabled' : '';
    $btn_text = ($konfirmasi_status === 'Sesuai') ? 'Sesuai' : (($konfirmasi_status === 'Belum Sesuai') ? 'Belum Sesuai' : 'Klik untuk Konfirmasi');
    $btn_class = ($konfirmasi_status === 'Sesuai') ? 'btn-success' : (($konfirmasi_status === 'Belum Sesuai') ? 'btn-danger' : 'btn-primary');
    $btn_data_toggle = ($konfirmasi_status === 'Belum Konfirmasi') ? 'data-bs-toggle="modal" data-bs-target="#modalKonfirmasi"' : '';
    echo '<div class="container-fluid mb-5">
      <div class="row justify-content-center">
        <div class="col-12 col-md-6">
          <div class="card shadow-sm confirmation-card">
            <div class="card-body">
              <div class="mb-2 fw-bold text-center confirmation-title">Pernyataan Konfirmasi Data</div>
              <div class="mb-3 text-center confirmation-description">Saya menyatakan bahwa data identitas yang saya input sudah <b>benar</b> dan <b>sesuai</b> dengan dokumen asli. Jika belum sesuai, silakan pilih "Belum Sesuai" dan lakukan perbaikan data.</div>
              <button type="button" class="btn ' . $btn_class . ' w-100" id="btnKonfirmasiData" data-konfirmasi="' . $konfirmasi_status . '" ' . $btn_data_toggle . ' ' . $btn_disabled . '>' . $btn_text . '</button>
            </div>
          </div>
        </div>
      </div>
    </div>';
    // Modal Konfirmasi
    echo '<div class="modal fade" id="modalKonfirmasi" tabindex="-1" aria-labelledby="modalKonfirmasiLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content identitas-modal">
          <form method="post" action="" id="formKonfirmasiIdentitas">
            <div class="modal-header identitas-modal-header">
              <h5 class="modal-title" id="modalKonfirmasiLabel">Konfirmasi Data Identitas</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body identitas-modal-body">
              <div class="mb-2">Pilih status konfirmasi data Anda:</div>
              <select name="konfirmasi" class="form-control mb-2" required>
                <option value="" disabled ' . (!isset($data['konfirmasi']) ? 'selected' : '') . '>-- Pilih Status --</option>
                <option value="Sesuai" ' . ((isset($data['konfirmasi']) && $data['konfirmasi'] == 'Sesuai') ? 'selected' : '') . '>Sesuai</option>
                <option value="Belum Sesuai" ' . ((isset($data['konfirmasi']) && $data['konfirmasi'] == 'Belum Sesuai') ? 'selected' : '') . '>Belum Sesuai</option>
              </select>
              <div id="konfirmasiError" class="text-danger konfirmasi-error">Silakan pilih status konfirmasi.</div>
            </div>
            <div class="modal-footer identitas-modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary">Simpan Konfirmasi</button>
            </div>
          </form>
        </div>
      </div>
    </div>';
  }
}
