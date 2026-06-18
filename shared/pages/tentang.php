<?php
$sae_about_mode = $sae_about_mode ?? 'public';
$sae_about_site_name = trim(strip_tags($site_name ?? 'Smart Apps Education'));
$sae_about_email = trim((string)($site_email ?? ''));
$sae_about_phone = trim((string)($site_phone ?? ''));
$sae_about_address = trim((string)($site_address ?? ''));
$sae_about_base_url = rtrim((string)($base_url ?? './'), '/');
$sae_about_is_admin = $sae_about_mode === 'admin';

$sae_about_home_href = $sae_about_is_admin ? './' : ($sae_about_base_url === '' ? './' : $sae_about_base_url . '/');
$sae_about_privacy_href = $sae_about_is_admin ? './privasi-kebijakan' : ($sae_about_base_url === '' ? './privasi-kebijakan' : $sae_about_base_url . '/privasi-kebijakan');
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
    max-width: 60ch;
    font-size: 1.06rem;
    line-height: 1.85;
    color: #334155;
    font-weight: 500;
  }

  .sae-about-page .sae-about-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    margin-top: 1.4rem;
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

  .sae-about-page .sae-about-stat-grid,
  .sae-about-page .sae-about-feature-grid,
  .sae-about-page .sae-about-main-grid {
    display: grid;
    gap: 1rem;
  }

  .sae-about-page .sae-about-stat-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .sae-about-page .sae-about-main-grid {
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
    padding: 1rem;
  }

  .sae-about-page .sae-about-feature-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .sae-about-page .sae-about-card,
  .sae-about-page .sae-about-stat,
  .sae-about-page .sae-about-feature {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 20px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
  }

  .sae-about-page .sae-about-card {
    padding: 1.4rem;
    height: 100%;
  }

  .sae-about-page .sae-about-card-title,
  .sae-about-page .sae-about-feature h4 {
    color: #0f172a;
    font-weight: 800;
  }

  .sae-about-page .sae-about-copy,
  .sae-about-page .sae-about-feature p,
  .sae-about-page .sae-about-note,
  .sae-about-page .sae-about-meta {
    color: #475569;
    line-height: 1.8;
  }

  .sae-about-page .sae-about-copy {
    font-size: 1rem;
    font-weight: 500;
  }

  .sae-about-page .sae-about-stat,
  .sae-about-page .sae-about-feature {
    padding: 1.15rem;
    height: 100%;
  }

  .sae-about-page .sae-about-stat strong {
    display: block;
    margin-bottom: .35rem;
    color: #0f172a;
    font-size: 1.06rem;
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

  .sae-about-page .sae-about-list a {
    color: #0f4c81;
    font-weight: 700;
    word-break: break-word;
  }

  .sae-about-page .sae-about-list a:hover {
    color: #0f766e;
    text-decoration: none;
  }

  .sae-about-page .sae-about-note-card {
    margin: 0 1rem 1rem;
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

    .sae-about-page .sae-about-main-grid,
    .sae-about-page .sae-about-note-card {
      margin: 0;
      padding-left: 0;
      padding-right: 0;
    }

    .sae-about-page .sae-about-stat-grid,
    .sae-about-page .sae-about-feature-grid {
      grid-template-columns: 1fr;
    }

    .sae-about-page .sae-about-actions {
      flex-direction: column;
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
                <span class="sae-about-kicker"><i class="fas fa-layer-group"></i> Tentang Aplikasi</span>
                <h1 class="sae-about-title"><?php echo htmlspecialchars($sae_about_site_name); ?></h1>
                <p class="sae-about-lead">Platform administrasi sekolah digital yang mengintegrasikan absensi, tata tertib, dokumen, inventaris, layanan siswa, agenda, sinkronisasi data, dan panel kerja sekolah dalam satu alur yang lebih rapi dan mudah dipantau.</p>
                <div class="sae-about-actions">
                  <a href="<?php echo htmlspecialchars($sae_about_home_href); ?>" class="sae-about-btn sae-about-btn-primary"><i class="fas fa-home"></i><?php echo $sae_about_is_admin ? 'Dashboard Admin' : 'Kembali ke Home'; ?></a>
                  <a href="<?php echo htmlspecialchars($sae_about_privacy_href); ?>" class="sae-about-btn"><i class="fas fa-user-shield"></i>Privasi &amp; Kebijakan</a>
                  <a href="<?php echo htmlspecialchars($sae_about_switch_href); ?>"<?php echo $sae_about_is_admin ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> class="sae-about-btn"><i class="<?php echo htmlspecialchars($sae_about_switch_icon); ?>"></i><?php echo htmlspecialchars($sae_about_switch_label); ?></a>
                </div>
              </div>
              <div class="col-12 col-lg-4 mt-4 mt-lg-0">
                <div class="sae-about-stat-grid">
                  <div class="sae-about-stat">
                    <strong>Publik &amp; Internal</strong>
                    <div class="sae-about-meta">Mendukung halaman publik, dashboard siswa, dan panel admin dengan alur yang saling terhubung.</div>
                  </div>
                  <div class="sae-about-stat">
                    <strong>Terintegrasi</strong>
                    <div class="sae-about-meta">Siap terhubung dengan Dapodik, WhatsApp, email, OAuth, dan SSO sesuai aktivasi sekolah.</div>
                  </div>
                </div>
              </div>
            </div>
          </section>

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

<?php if ($sae_about_is_admin): ?>
  </div>
<?php else: ?>
      </div>
    </div>
  </div>
<?php endif; ?>