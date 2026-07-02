<?php
$sae_about_mode = $sae_about_mode ?? 'public';
$sae_about_site_name = trim(strip_tags($site_name ?? 'Smart Apps Education'));
$sae_about_email = trim((string)($site_email ?? ''));
$sae_about_phone = trim((string)($site_phone ?? ''));
$sae_about_address = trim((string)($site_address ?? ''));
$sae_about_base_url = rtrim((string)($base_url ?? './'), '/');
$sae_about_is_admin = $sae_about_mode === 'admin';
$sae_about_updated_at = date('d F Y');

$sae_about_default_tab = strtolower(trim((string)($sae_about_default_tab ?? 'tentang')));
$sae_about_requested_tab = strtolower(trim((string)($_GET['tab'] ?? $sae_about_default_tab)));
$sae_about_active_tab = in_array($sae_about_requested_tab, ['privasi', 'privacy', 'kebijakan', 'privasi-kebijakan'], true) ? 'privasi' : 'tentang';

$sae_about_home_href = $sae_about_is_admin ? './' : ($sae_about_base_url === '' ? './' : $sae_about_base_url . '/');
$sae_about_switch_href = $sae_about_is_admin ? '../tentang' : ($sae_about_base_url === '' ? './admin/' : $sae_about_base_url . '/admin/');
$sae_about_switch_label = $sae_about_is_admin ? 'Versi Publik' : 'Login Admin';
$sae_about_switch_icon = $sae_about_is_admin ? 'fas fa-home' : 'fas fa-user-shield';

$sae_about_contacts = [];
if ($sae_about_email !== '') {
    $sae_about_contacts[] = [
        'label' => 'Email',
        'value' => $sae_about_email,
        'href' => 'mailto:' . $sae_about_email,
        'icon' => 'fas fa-envelope'
    ];
}
if ($sae_about_phone !== '') {
    $sae_about_phone_clean = preg_replace('/[^0-9+]/', '', $sae_about_phone);
    $sae_about_contacts[] = [
        'label' => 'Telepon',
        'value' => $sae_about_phone,
        'href' => $sae_about_phone_clean !== '' ? 'tel:' . $sae_about_phone_clean : '#',
        'icon' => 'fas fa-phone-alt'
    ];
}
if ($sae_about_base_url !== '') {
    $sae_about_contacts[] = [
        'label' => 'Website',
        'value' => $sae_about_base_url,
        'href' => $sae_about_base_url,
        'icon' => 'fas fa-globe'
    ];
}
?>

<style>
  .sae-about-page {
    color: #0f172a;
  }

  .sae-about-page .sae-about-surface {
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.09);
    border-radius: 24px;
    box-shadow: 0 22px 46px rgba(15, 23, 42, 0.08);
    overflow: hidden;
  }

  .sae-about-page .sae-about-hero {
    padding: 2rem;
    background:
      radial-gradient(circle at top right, rgba(14, 116, 144, 0.12), transparent 36%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }

  .sae-about-page .sae-about-kicker {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem .9rem;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.10);
    color: #1d4ed8;
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .sae-about-page .sae-about-title {
    margin: 1rem 0 .85rem;
    font-size: clamp(2rem, 3vw, 3rem);
    line-height: 1.1;
    font-weight: 900;
    color: #0f172a;
  }

  .sae-about-page .sae-about-lead {
    margin: 0;
    max-width: 62ch;
    font-size: 1.04rem;
    line-height: 1.85;
    color: #334155;
    font-weight: 500;
  }

  .sae-about-page .sae-about-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    margin-top: 1.3rem;
  }

  .sae-about-page .sae-about-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    padding: .9rem 1.2rem;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.14);
    background: #fff;
    color: #0f172a;
    font-weight: 700;
    text-decoration: none;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }

  .sae-about-page .sae-about-btn:hover {
    transform: translateY(-1px);
    color: #0f172a;
    text-decoration: none;
    box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
  }

  .sae-about-page .sae-about-btn-primary {
    background: linear-gradient(135deg, #0f4c81 0%, #0f766e 100%);
    border-color: transparent;
    color: #fff;
  }

  .sae-about-page .sae-about-btn-primary:hover {
    color: #fff;
  }

  .sae-about-page .sae-about-tabs-wrap {
    padding: 0 1rem;
  }

  .sae-about-page .sae-about-tabs.nav-pills {
    background: rgba(148, 163, 184, 0.12);
    border-radius: 18px;
    padding: .35rem;
    gap: .35rem;
  }

  .sae-about-page .sae-about-tabs .nav-item {
    flex: 1 1 0;
  }

  .sae-about-page .sae-about-tabs .nav-link {
    border-radius: 14px;
    font-size: .9rem;
    font-weight: 700;
    text-align: center;
    color: #334155;
    padding: .7rem .85rem;
    border: 0;
    background: transparent;
  }

  .sae-about-page .sae-about-tabs .nav-link:hover {
    color: #1e293b;
    background: rgba(255, 255, 255, 0.7);
  }

  .sae-about-page .sae-about-tabs .nav-link.active {
    color: #fff;
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.26);
  }

  .sae-about-page .sae-about-tab-content {
    padding: 1rem;
  }

  .sae-about-page .sae-about-main-grid,
  .sae-about-page .sae-about-feature-grid {
    display: grid;
    gap: 1rem;
  }

  .sae-about-page .sae-about-main-grid {
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
  }

  .sae-about-page .sae-about-feature-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .sae-about-page .sae-about-card,
  .sae-about-page .sae-about-feature,
  .sae-about-page .sae-about-privacy-section {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 20px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    padding: 1.35rem;
  }

  .sae-about-page .sae-about-card-title,
  .sae-about-page .sae-about-feature h4,
  .sae-about-page .sae-about-privacy-section h2 {
    color: #0f172a;
    font-weight: 800;
  }

  .sae-about-page .sae-about-copy,
  .sae-about-page .sae-about-feature p,
  .sae-about-page .sae-about-note,
  .sae-about-page .sae-about-meta,
  .sae-about-page .sae-about-privacy-copy,
  .sae-about-page .sae-about-privacy-list li {
    color: #475569;
    line-height: 1.8;
    font-weight: 500;
  }

  .sae-about-page .sae-about-copy {
    font-size: 1rem;
  }

  .sae-about-page .sae-about-list {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .sae-about-page .sae-about-list li {
    display: flex;
    gap: .85rem;
    align-items: flex-start;
    padding: .95rem 0;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  }

  .sae-about-page .sae-about-list li:last-child {
    border-bottom: 0;
    padding-bottom: 0;
  }

  .sae-about-page .sae-about-list i {
    margin-top: .18rem;
    color: #2563eb;
  }

  .sae-about-page .sae-about-list strong {
    display: block;
    color: #0f172a;
    font-size: 1.04rem;
    margin-bottom: .2rem;
  }

  .sae-about-page .sae-about-list span,
  .sae-about-page .sae-about-list div,
  .sae-about-page .sae-about-list a {
    color: #475569;
    line-height: 1.8;
  }

  .sae-about-page .sae-about-list a,
  .sae-about-page .sae-about-privacy-section a,
  .sae-about-page .sae-about-privacy-list strong {
    color: #0f4c81;
    font-weight: 800;
  }

  .sae-about-page .sae-about-list a:hover,
  .sae-about-page .sae-about-privacy-section a:hover {
    color: #0f766e;
    text-decoration: none;
  }

  .sae-about-page .sae-about-note-card {
    margin-top: 1rem;
  }

  .sae-about-page .sae-about-privacy-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .7rem;
    margin-bottom: 1rem;
  }

  .sae-about-page .sae-about-privacy-meta span {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .64rem .92rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.05);
    color: #334155;
    font-weight: 700;
  }

  .sae-about-page .sae-about-privacy-list {
    margin: 0;
    padding-left: 1.15rem;
  }

  .sae-about-page .sae-about-privacy-list li + li {
    margin-top: .55rem;
  }

  @media (max-width: 991.98px) {
    .sae-about-page .sae-about-main-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 767.98px) {
    .sae-about-page .sae-about-hero {
      padding: 1.35rem;
    }

    .sae-about-page .sae-about-tab-content,
    .sae-about-page .sae-about-tabs-wrap {
      padding-left: 0;
      padding-right: 0;
    }

    .sae-about-page .sae-about-feature-grid {
      grid-template-columns: 1fr;
    }

    .sae-about-page .sae-about-actions,
    .sae-about-page .sae-about-privacy-meta {
      flex-direction: column;
      align-items: stretch;
    }

    .sae-about-page .sae-about-btn {
      width: 100%;
    }
  }
</style>

<?php if ($sae_about_is_admin): ?>
  <div class="header bg-primary pb-6">
    <div class="container-fluid">
      <div class="header-body">
        <div class="row align-items-center py-4">
          <div class="col-lg-8 col-12">
            <h6 class="h2 text-white d-inline-block mb-0">Tentang Aplikasi</h6>
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
              <ol class="breadcrumb breadcrumb-links breadcrumb-dark mb-0">
                <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Tentang Aplikasi</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="container-fluid mt--6 sae-about-page">
<?php else: ?>
  <div class="sae-landing sae-about-page">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-11">
<?php endif; ?>

        <div class="sae-about-surface">
          <section class="sae-about-hero">
            <div class="row align-items-center">
              <div class="col-12 col-lg-8">
                <span class="sae-about-kicker"><i class="fas fa-layer-group"></i> Informasi Aplikasi</span>
                <h1 class="sae-about-title"><?php echo htmlspecialchars($sae_about_site_name); ?></h1>
                <p class="sae-about-lead">Platform administrasi sekolah digital yang mengintegrasikan absensi, tata tertib, dokumen, inventaris, layanan siswa, agenda, sinkronisasi data, dan panel kerja sekolah dalam satu alur yang lebih rapi dan mudah dipantau.</p>
                <div class="sae-about-actions">
                  <a href="<?php echo htmlspecialchars($sae_about_home_href); ?>" class="sae-about-btn sae-about-btn-primary"><i class="fas fa-home"></i><?php echo $sae_about_is_admin ? 'Dashboard Admin' : 'Kembali ke Home'; ?></a>
                  <a href="#tab-privasi-kebijakan" class="sae-about-btn js-about-switch-tab" data-tab-target="privasi"><i class="fas fa-user-shield"></i>Privasi &amp; Kebijakan</a>
                  <a href="<?php echo htmlspecialchars($sae_about_switch_href); ?>"<?php echo $sae_about_is_admin ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> class="sae-about-btn"><i class="<?php echo htmlspecialchars($sae_about_switch_icon); ?>"></i><?php echo htmlspecialchars($sae_about_switch_label); ?></a>
                </div>
              </div>
              <div class="col-12 col-lg-4 mt-4 mt-lg-0">
                <div class="sae-about-privacy-meta">
                  <span><i class="fas fa-sync-alt"></i>Diperbarui: <?php echo htmlspecialchars($sae_about_updated_at); ?></span>
                  <span><i class="fas fa-lock"></i>Mencakup modul publik, dashboard siswa, dan panel admin</span>
                </div>
              </div>
            </div>
          </section>

          <div class="sae-about-tabs-wrap pt-3">
            <ul class="nav nav-pills nav-fill sae-about-tabs" role="tablist" aria-label="Tab informasi aplikasi">
              <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $sae_about_active_tab === 'tentang' ? 'active' : ''; ?>" id="tablink-tentang" data-toggle="tab" href="#tab-tentang-aplikasi" role="tab" aria-controls="tab-tentang-aplikasi" aria-selected="<?php echo $sae_about_active_tab === 'tentang' ? 'true' : 'false'; ?>">
                  <i class="fas fa-info-circle mr-1"></i>Tentang Aplikasi
                </a>
              </li>
              <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $sae_about_active_tab === 'privasi' ? 'active' : ''; ?>" id="tablink-privasi" data-toggle="tab" href="#tab-privasi-kebijakan" role="tab" aria-controls="tab-privasi-kebijakan" aria-selected="<?php echo $sae_about_active_tab === 'privasi' ? 'true' : 'false'; ?>">
                  <i class="fas fa-user-shield mr-1"></i>Privasi &amp; Kebijakan
                </a>
              </li>
            </ul>
          </div>

          <div class="tab-content sae-about-tab-content" id="tentangTabContent">
            <div class="tab-pane fade <?php echo $sae_about_active_tab === 'tentang' ? 'show active' : ''; ?>" id="tab-tentang-aplikasi" role="tabpanel" aria-labelledby="tablink-tentang">
              <div class="sae-about-main-grid">
                <div class="sae-about-card">
                  <h3 class="sae-about-card-title h4 mb-3">Apa yang dikerjakan sistem ini</h3>
                  <p class="sae-about-copy mb-3">Aplikasi ini dirancang untuk merapikan proses operasional sekolah yang sebelumnya tersebar di banyak pekerjaan manual. Fokus utamanya adalah menjaga data siswa, data kelas, kehadiran, pelanggaran, inventaris, dokumen, dan layanan administratif agar tersusun konsisten dan mudah dipantau.</p>
                  <ul class="sae-about-list">
                    <li>
                      <i class="fas fa-check-circle"></i>
                      <div>
                        <strong>Absensi digital multi-mode</strong>
                        <div>Mendukung absensi RFID, input manual, bukti foto, jam masuk-pulang, izin, dan pemantauan real-time.</div>
                      </div>
                    </li>
                    <li>
                      <i class="fas fa-check-circle"></i>
                      <div>
                        <strong>Layanan siswa yang terstruktur</strong>
                        <div>Siswa dapat mengakses profil, absensi, berkas, e-izin, agenda kelas, poin pelanggaran, inventaris, hingga usulan PIP.</div>
                      </div>
                    </li>
                    <li>
                      <i class="fas fa-check-circle"></i>
                      <div>
                        <strong>Panel admin berbasis peran</strong>
                        <div>Superadmin, guru, tenaga administrasi, dan tugas tambahan memiliki akses sesuai kebutuhan kerja masing-masing.</div>
                      </div>
                    </li>
                    <li>
                      <i class="fas fa-check-circle"></i>
                      <div>
                        <strong>Pelaporan dan sinkronisasi</strong>
                        <div>Tersedia cetak laporan, ekspor data, serta sinkronisasi ke sumber data pendidikan dan integrasi pendukung sekolah.</div>
                      </div>
                    </li>
                  </ul>
                </div>

                <div class="sae-about-card">
                  <h3 class="sae-about-card-title h4 mb-3">Ruang lingkup fitur saat ini</h3>
                  <div class="sae-about-feature-grid">
                    <div class="sae-about-feature">
                      <h4 class="h6 mb-2">Manajemen Kehadiran</h4>
                      <p class="mb-0">Absensi siswa, izin, laporan harian, rekap kelas, dan koreksi data.</p>
                    </div>
                    <div class="sae-about-feature">
                      <h4 class="h6 mb-2">Tata Tertib</h4>
                      <p class="mb-0">Poin pelanggaran, sanggah, pemanggilan, serta monitoring semester.</p>
                    </div>
                    <div class="sae-about-feature">
                      <h4 class="h6 mb-2">Administrasi Berkas</h4>
                      <p class="mb-0">Upload dan validasi KK, Akte, Ijazah, KIP, KKS, dan KIS.</p>
                    </div>
                    <div class="sae-about-feature">
                      <h4 class="h6 mb-2">Inventaris &amp; PIP</h4>
                      <p class="mb-0">Inventaris kelas, peminjaman alat, kriteria PIP, ranking, dan riwayat usulan.</p>
                    </div>
                  </div>
                </div>

                <div class="sae-about-card">
                  <h3 class="sae-about-card-title h4 mb-3">Akses berdasarkan pengguna</h3>
                  <ul class="sae-about-list">
                    <li>
                      <i class="fas fa-user-cog"></i>
                      <div>
                        <strong>Superadmin</strong>
                        <div>Mengelola konfigurasi sistem, hak akses, data master, sinkronisasi, dan keseluruhan modul utama.</div>
                      </div>
                    </li>
                    <li>
                      <i class="fas fa-chalkboard-teacher"></i>
                      <div>
                        <strong>Guru &amp; Wali Kelas</strong>
                        <div>Memantau data kelas, kehadiran, izin, poin siswa, dan agenda pembelajaran.</div>
                      </div>
                    </li>
                    <li>
                      <i class="fas fa-user-tie"></i>
                      <div>
                        <strong>Tenaga Administrasi</strong>
                        <div>Mengurus data siswa, dokumen, laporan operasional, dan pekerjaan administratif sekolah.</div>
                      </div>
                    </li>
                    <li>
                      <i class="fas fa-user-graduate"></i>
                      <div>
                        <strong>Siswa</strong>
                        <div>Mengakses dashboard pribadi untuk absensi, izin, profil, berkas, agenda kelas, dan layanan siswa.</div>
                      </div>
                    </li>
                  </ul>
                </div>

                <div class="sae-about-card">
                  <h3 class="sae-about-card-title h4 mb-3">Informasi kontak aplikasi</h3>
                  <?php if (!empty($sae_about_contacts) || $sae_about_address !== ''): ?>
                    <ul class="sae-about-list">
                      <?php foreach ($sae_about_contacts as $sae_about_contact): ?>
                        <li>
                          <i class="<?php echo htmlspecialchars($sae_about_contact['icon']); ?>"></i>
                          <div>
                            <strong><?php echo htmlspecialchars($sae_about_contact['label']); ?></strong>
                            <div><a href="<?php echo htmlspecialchars($sae_about_contact['href']); ?>"<?php echo $sae_about_contact['label'] === 'Website' ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo htmlspecialchars($sae_about_contact['value']); ?></a></div>
                          </div>
                        </li>
                      <?php endforeach; ?>
                      <?php if ($sae_about_address !== ''): ?>
                        <li>
                          <i class="fas fa-map-marker-alt"></i>
                          <div>
                            <strong>Alamat</strong>
                            <div><?php echo htmlspecialchars($sae_about_address); ?></div>
                          </div>
                        </li>
                      <?php endif; ?>
                    </ul>
                  <?php else: ?>
                    <p class="sae-about-note mb-0">Informasi kontak belum dilengkapi pada pengaturan sistem. Halaman ini tetap dapat diakses dari area publik maupun admin untuk menjelaskan fungsi utama aplikasi.</p>
                  <?php endif; ?>
                </div>
              </div>

              <div class="sae-about-card sae-about-note-card">
                <h3 class="sae-about-card-title h4 mb-2">Catatan penggunaan</h3>
                <p class="sae-about-note mb-0">Informasi pada halaman ini menyesuaikan implementasi sistem yang aktif saat ini. Detail fitur, integrasi, dan akses dapat berubah mengikuti kebijakan sekolah, konfigurasi modul, dan pembaruan aplikasi yang diterapkan pengelola sistem.</p>
              </div>
            </div>

            <div class="tab-pane fade <?php echo $sae_about_active_tab === 'privasi' ? 'show active' : ''; ?>" id="tab-privasi-kebijakan" role="tabpanel" aria-labelledby="tablink-privasi">
              <div class="sae-about-privacy-section">
                <h2 class="h5 mb-3">1. Ruang lingkup layanan</h2>
                <p class="sae-about-privacy-copy mb-0">Aplikasi ini digunakan untuk mendukung administrasi sekolah, termasuk pengelolaan kehadiran, data siswa, dokumen, tata tertib, inventaris, izin, agenda, pelaporan, sinkronisasi data pendidikan, serta layanan digital lain yang diaktifkan oleh pengelola sistem.</p>
              </div>

              <div class="sae-about-privacy-section mt-3">
                <h2 class="h5 mb-3">2. Data yang dapat dikumpulkan</h2>
                <ul class="sae-about-privacy-list">
                  <li>Data identitas pengguna seperti nama, NISN, kelas, jurusan, jenis kelamin, status akun, dan informasi profil terkait sekolah.</li>
                  <li>Data operasional seperti riwayat absensi, jam masuk-pulang, status izin, pelanggaran, inventaris, aktivitas dashboard, dan log sinkronisasi.</li>
                  <li>Dokumen yang diunggah seperti KK, Akte, Ijazah, KIP, KKS, KIS, dan berkas pendukung lain sesuai kebutuhan administrasi.</li>
                  <li>Data teknis dan keamanan seperti cookie sesi, token CSRF, log aktivitas, alamat IP, dan catatan integrasi yang diperlukan untuk menjaga keamanan layanan.</li>
                  <li>Data tambahan kondisional seperti foto absensi, nomor telepon orang tua, data lokasi absensi, serta data login Google OAuth atau SSO bila fitur digunakan.</li>
                </ul>
              </div>

              <div class="sae-about-privacy-section mt-3">
                <h2 class="h5 mb-3">3. Tujuan penggunaan data</h2>
                <ul class="sae-about-privacy-list">
                  <li>Memverifikasi identitas dan hak akses pengguna pada modul yang tersedia.</li>
                  <li>Menyediakan layanan administrasi sekolah secara digital dan terdokumentasi.</li>
                  <li>Menghasilkan laporan, rekap, dan bahan evaluasi operasional sekolah.</li>
                  <li>Mendukung proses validasi dokumen, kedisiplinan, layanan siswa, dan komunikasi administrasi.</li>
                  <li>Menyokong sinkronisasi dan integrasi dengan sistem resmi atau layanan pihak ketiga yang diaktifkan sekolah.</li>
                </ul>
              </div>

              <div class="sae-about-privacy-section mt-3">
                <h2 class="h5 mb-3">4. Integrasi dan pembagian data</h2>
                <ul class="sae-about-privacy-list">
                  <li><strong>Dapodik / sinkronisasi pendidikan</strong> untuk kebutuhan kesesuaian dan pertukaran data pendidikan.</li>
                  <li><strong>WhatsApp Gateway</strong> untuk notifikasi tertentu seperti verifikasi nomor dan pemberitahuan sistem.</li>
                  <li><strong>Email SMTP</strong> untuk pengiriman notifikasi dan kebutuhan akun.</li>
                  <li><strong>Google OAuth</strong> untuk autentikasi masuk menggunakan akun Google.</li>
                  <li><strong>Single Sign-On (SSO)</strong> untuk perpindahan autentikasi ke sistem sekolah lain yang terhubung.</li>
                </ul>
                <p class="sae-about-privacy-copy mt-2 mb-0">Data hanya dibagikan sejauh diperlukan untuk fungsi layanan dan sesuai pengaturan yang diaktifkan oleh pengelola sistem.</p>
              </div>

              <div class="sae-about-privacy-section mt-3">
                <h2 class="h5 mb-3">5. Penyimpanan, keamanan, dan retensi data</h2>
                <ul class="sae-about-privacy-list">
                  <li>Data disimpan pada server aplikasi dan basis data yang dikelola oleh pengelola sistem atau pihak yang ditunjuk.</li>
                  <li>Sistem menggunakan session management, token CSRF, pembatasan hak akses berbasis peran, validasi input, dan pencatatan aktivitas tertentu.</li>
                  <li>Dokumen dan file pendukung disimpan pada struktur direktori aplikasi sesuai kategori layanan masing-masing.</li>
                  <li>Lama penyimpanan data mengikuti kebutuhan operasional sekolah, kebijakan internal, dan ketentuan yang berlaku.</li>
                </ul>
              </div>

              <div class="sae-about-privacy-section mt-3">
                <h2 class="h5 mb-3">6. Hak pengguna dan tanggung jawab penggunaan</h2>
                <ul class="sae-about-privacy-list">
                  <li>Pengguna berhak meminta koreksi data yang tidak sesuai melalui operator atau pengelola sistem sekolah.</li>
                  <li>Pengguna bertanggung jawab menjaga kerahasiaan akun, kata sandi, serta perangkat yang digunakan untuk mengakses aplikasi.</li>
                  <li>Penggunaan aplikasi hanya diperbolehkan untuk keperluan yang sah dan relevan dengan layanan sekolah.</li>
                  <li>Penyalahgunaan layanan atau percobaan akses tanpa izin dapat dikenai pembatasan akses dan tindak lanjut oleh pengelola sistem.</li>
                </ul>
              </div>

              <div class="sae-about-privacy-section mt-3">
                <h2 class="h5 mb-3">7. Perubahan kebijakan</h2>
                <p class="sae-about-privacy-copy mb-0">Kebijakan ini dapat diperbarui sewaktu-waktu mengikuti pengembangan aplikasi, perubahan proses bisnis sekolah, integrasi baru, atau kebutuhan kepatuhan. Versi terbaru akan dipublikasikan pada halaman ini dan berlaku sejak tanggal pembaruan ditampilkan.</p>
              </div>

              <div class="sae-about-privacy-section mt-3">
                <h2 class="h5 mb-3">8. Kontak pengelola</h2>
                <p class="sae-about-privacy-copy" style="margin-bottom:.6rem;">Untuk pertanyaan terkait privasi, kebijakan penggunaan, koreksi data, atau pelaporan masalah layanan, silakan hubungi pengelola sistem sekolah melalui kontak yang tersedia.</p>
                <ul class="sae-about-privacy-list">
                  <?php if ($sae_about_email !== ''): ?>
                    <li>Email: <a href="mailto:<?php echo htmlspecialchars($sae_about_email); ?>"><?php echo htmlspecialchars($sae_about_email); ?></a></li>
                  <?php endif; ?>
                  <?php if ($sae_about_phone !== ''): ?>
                    <li>Telepon: <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $sae_about_phone)); ?>"><?php echo htmlspecialchars($sae_about_phone); ?></a></li>
                  <?php endif; ?>
                  <li>Halaman utama aplikasi: <a href="<?php echo htmlspecialchars($sae_about_home_href); ?>"><?php echo htmlspecialchars($sae_about_is_admin ? './' : rtrim($sae_about_home_href, '/')); ?></a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>

<?php if ($sae_about_is_admin): ?>
  </div>
<?php else: ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
(function () {
  if (typeof jQuery === 'undefined') {
    return;
  }

  var $ = jQuery;

  function setTabFromValue(tabValue) {
    var target = tabValue === 'privasi' ? '#tab-privasi-kebijakan' : '#tab-tentang-aplikasi';
    var $link = $('a[href="' + target + '"][data-toggle="tab"]');
    if ($link.length) {
      $link.tab('show');
      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', '#'+ (tabValue === 'privasi' ? 'privasi' : 'tentang'));
      }
    }
  }

  $('.js-about-switch-tab').on('click', function (event) {
    event.preventDefault();
    setTabFromValue($(this).data('tab-target') === 'privasi' ? 'privasi' : 'tentang');
  });

  $('a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
    var href = ($(event.target).attr('href') || '').toLowerCase();
    if (window.history && window.history.replaceState) {
      var hash = href === '#tab-privasi-kebijakan' ? '#privasi' : '#tentang';
      window.history.replaceState(null, '', hash);
    }
  });

  var hash = (window.location.hash || '').toLowerCase();
  if (hash === '#privasi' || hash === '#tab-privasi-kebijakan') {
    setTabFromValue('privasi');
  }
})();
</script>
