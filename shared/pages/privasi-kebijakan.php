<?php
$sae_privacy_mode = $sae_privacy_mode ?? 'public';
$sae_privacy_site_name = trim(strip_tags($site_name ?? 'Smart Apps Education'));
$sae_privacy_email = trim((string)($site_email ?? ''));
$sae_privacy_phone = trim((string)($site_phone ?? ''));
$sae_privacy_updated_at = date('d F Y');
$sae_privacy_base_url = rtrim((string)($base_url ?? './'), '/');
$sae_privacy_is_admin = $sae_privacy_mode === 'admin';

$sae_privacy_home_href = $sae_privacy_is_admin ? './' : ($sae_privacy_base_url === '' ? './' : $sae_privacy_base_url . '/');
$sae_privacy_about_href = $sae_privacy_is_admin ? './tentang' : ($sae_privacy_base_url === '' ? './tentang' : $sae_privacy_base_url . '/tentang');
$sae_privacy_switch_href = $sae_privacy_is_admin ? '../privasi-kebijakan' : ($sae_privacy_base_url === '' ? './admin/' : $sae_privacy_base_url . '/admin/');
$sae_privacy_switch_label = $sae_privacy_is_admin ? 'Versi Publik' : 'Login Admin';
$sae_privacy_switch_icon = $sae_privacy_is_admin ? 'fas fa-home' : 'fas fa-user-shield';
?>

<style>
  .sae-privacy-page {
    color: #0f172a;
  }

  .sae-privacy-page .sae-privacy-surface {
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.09);
    border-radius: 24px;
    box-shadow: 0 22px 46px rgba(15, 23, 42, 0.08);
    overflow: hidden;
  }

  .sae-privacy-page .sae-privacy-hero {
    padding: 2rem;
    background:
      radial-gradient(circle at top right, rgba(15, 118, 110, 0.11), transparent 38%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }

  .sae-privacy-page .sae-privacy-kicker {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .5rem .9rem;
    border-radius: 999px;
    background: rgba(15, 76, 129, 0.10);
    color: #0f4c81;
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .sae-privacy-page .sae-privacy-title {
    margin: 1rem 0 .85rem;
    font-size: clamp(2rem, 3vw, 3rem);
    line-height: 1.1;
    font-weight: 900;
    color: #0f172a;
  }

  .sae-privacy-page .sae-privacy-lead {
    margin: 0;
    max-width: 62ch;
    font-size: 1.04rem;
    line-height: 1.85;
    color: #334155;
    font-weight: 500;
  }

  .sae-privacy-page .sae-privacy-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    margin-top: 1rem;
  }

  .sae-privacy-page .sae-privacy-meta span {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .68rem 1rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.05);
    color: #334155;
    font-weight: 700;
  }

  .sae-privacy-page .sae-privacy-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: .75rem;
    margin-top: 1rem;
  }

  .sae-privacy-page .sae-privacy-btn {
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

  .sae-privacy-page .sae-privacy-btn:hover {
    transform: translateY(-1px);
    color: #0f172a;
    text-decoration: none;
    box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
  }

  .sae-privacy-page .sae-privacy-btn-primary {
    background: linear-gradient(135deg, #123c69 0%, #2a6f97 100%);
    border-color: transparent;
    color: #fff;
  }

  .sae-privacy-page .sae-privacy-btn-primary:hover {
    color: #fff;
  }

  .sae-privacy-page .sae-privacy-body {
    padding: 1rem;
  }

  .sae-privacy-page .sae-privacy-section {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 20px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    padding: 1.4rem;
    margin-bottom: 1rem;
  }

  .sae-privacy-page .sae-privacy-section:last-child {
    margin-bottom: 0;
  }

  .sae-privacy-page .sae-privacy-section h2 {
    color: #0f172a;
    font-size: 1.18rem;
    font-weight: 800;
    margin-bottom: .8rem;
  }

  .sae-privacy-page .sae-privacy-copy,
  .sae-privacy-page .sae-privacy-list li {
    color: #475569;
    line-height: 1.85;
    font-weight: 500;
  }

  .sae-privacy-page .sae-privacy-copy {
    margin-bottom: 0;
  }

  .sae-privacy-page .sae-privacy-list {
    margin: 0;
    padding-left: 1.15rem;
  }

  .sae-privacy-page .sae-privacy-list li + li {
    margin-top: .55rem;
  }

  .sae-privacy-page .sae-privacy-list strong,
  .sae-privacy-page .sae-privacy-section a {
    color: #0f4c81;
    font-weight: 800;
  }

  .sae-privacy-page .sae-privacy-section a:hover {
    color: #0f766e;
    text-decoration: none;
  }

  @media (max-width: 991.98px) {
    .sae-privacy-page .sae-privacy-actions {
      justify-content: flex-start;
    }
  }

  @media (max-width: 767.98px) {
    .sae-privacy-page .sae-privacy-hero {
      padding: 1.35rem;
    }

    .sae-privacy-page .sae-privacy-meta,
    .sae-privacy-page .sae-privacy-actions {
      flex-direction: column;
      align-items: stretch;
    }

    .sae-privacy-page .sae-privacy-btn {
      width: 100%;
    }

    .sae-privacy-page .sae-privacy-body {
      padding-left: 0;
      padding-right: 0;
    }
  }
</style>

<?php if ($sae_privacy_is_admin): ?>
  <div class="header bg-primary pb-6">
    <div class="container-fluid">
      <div class="header-body">
        <div class="row align-items-center py-4">
          <div class="col-lg-8 col-12">
            <h6 class="h2 text-white d-inline-block mb-0">Privasi &amp; Kebijakan</h6>
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
              <ol class="breadcrumb breadcrumb-links breadcrumb-dark mb-0">
                <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Privasi &amp; Kebijakan</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="container-fluid mt--6 sae-privacy-page">
<?php else: ?>
  <div class="sae-landing sae-privacy-page">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-11">
<?php endif; ?>

        <div class="sae-privacy-surface">
          <section class="sae-privacy-hero">
            <div class="row align-items-center">
              <div class="col-12 col-lg-8">
                <span class="sae-privacy-kicker"><i class="fas fa-user-shield"></i> Privasi &amp; Kebijakan</span>
                <h1 class="sae-privacy-title">Kebijakan Privasi <?php echo htmlspecialchars($sae_privacy_site_name); ?></h1>
                <p class="sae-privacy-lead">Halaman ini menjelaskan bagaimana aplikasi mengumpulkan, menggunakan, menyimpan, melindungi, dan membagikan data sesuai kebutuhan layanan administrasi sekolah yang berjalan saat ini.</p>
                <div class="sae-privacy-meta">
                  <span><i class="fas fa-sync-alt"></i>Diperbarui: <?php echo htmlspecialchars($sae_privacy_updated_at); ?></span>
                  <span><i class="fas fa-lock"></i>Berlaku untuk modul publik, dashboard siswa, dan panel admin</span>
                </div>
              </div>
              <div class="col-12 col-lg-4 mt-4 mt-lg-0">
                <div class="sae-privacy-actions">
                  <a href="<?php echo htmlspecialchars($sae_privacy_home_href); ?>" class="sae-privacy-btn sae-privacy-btn-primary"><i class="fas fa-home"></i><?php echo $sae_privacy_is_admin ? 'Dashboard Admin' : 'Kembali ke Home'; ?></a>
                  <a href="<?php echo htmlspecialchars($sae_privacy_about_href); ?>" class="sae-privacy-btn"><i class="fas fa-info-circle"></i>Tentang Aplikasi</a>
                  <a href="<?php echo htmlspecialchars($sae_privacy_switch_href); ?>"<?php echo $sae_privacy_is_admin ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> class="sae-privacy-btn"><i class="<?php echo htmlspecialchars($sae_privacy_switch_icon); ?>"></i><?php echo htmlspecialchars($sae_privacy_switch_label); ?></a>
                </div>
              </div>
            </div>
          </section>

          <div class="sae-privacy-body">
            <div class="sae-privacy-section">
              <h2>1. Ruang lingkup layanan</h2>
              <p class="sae-privacy-copy">Aplikasi ini digunakan untuk mendukung administrasi sekolah, termasuk pengelolaan kehadiran, data siswa, dokumen, tata tertib, inventaris, izin, agenda, pelaporan, sinkronisasi data pendidikan, serta layanan digital lain yang diaktifkan oleh pengelola sistem.</p>
            </div>

            <div class="sae-privacy-section">
              <h2>2. Data yang dapat dikumpulkan</h2>
              <ul class="sae-privacy-list">
                <li>Data identitas pengguna seperti nama, NISN, kelas, jurusan, jenis kelamin, status akun, dan informasi profil terkait sekolah.</li>
                <li>Data operasional seperti riwayat absensi, jam masuk-pulang, status izin, pelanggaran, inventaris, aktivitas dashboard, dan log sinkronisasi.</li>
                <li>Dokumen yang diunggah seperti KK, Akte, Ijazah, KIP, KKS, KIS, dan berkas pendukung lain sesuai kebutuhan administrasi.</li>
                <li>Data teknis dan keamanan seperti cookie sesi, token CSRF, log aktivitas, alamat IP, dan catatan integrasi yang diperlukan untuk menjaga keamanan layanan.</li>
                <li>Data tambahan yang bersifat kondisional seperti foto absensi, nomor telepon orang tua, data lokasi untuk absensi berbasis lokasi, serta data login dari Google OAuth atau integrasi SSO bila fitur tersebut digunakan.</li>
              </ul>
            </div>

            <div class="sae-privacy-section">
              <h2>3. Tujuan penggunaan data</h2>
              <ul class="sae-privacy-list">
                <li>Memverifikasi identitas dan hak akses pengguna pada modul yang tersedia.</li>
                <li>Menyediakan layanan administrasi sekolah secara digital dan terdokumentasi.</li>
                <li>Menghasilkan laporan, rekap, dan bahan evaluasi operasional sekolah.</li>
                <li>Mendukung proses validasi dokumen, kedisiplinan, layanan siswa, dan komunikasi administrasi.</li>
                <li>Menyokong proses sinkronisasi dan integrasi dengan sistem resmi atau layanan pihak ketiga yang diaktifkan sekolah.</li>
              </ul>
            </div>

            <div class="sae-privacy-section">
              <h2>4. Integrasi dan pembagian data</h2>
              <p class="sae-privacy-copy" style="margin-bottom:.6rem;">Dalam konfigurasi saat ini, sistem dapat terhubung dengan layanan atau mekanisme berikut sesuai aktivasi sekolah:</p>
              <ul class="sae-privacy-list">
                <li><strong>Dapodik / sinkronisasi pendidikan</strong> untuk kebutuhan kesesuaian dan pertukaran data pendidikan.</li>
                <li><strong>WhatsApp Gateway</strong> untuk notifikasi tertentu seperti verifikasi nomor, pemberitahuan, atau alert sistem.</li>
                <li><strong>Email SMTP</strong> untuk pengiriman notifikasi dan kebutuhan akun.</li>
                <li><strong>Google OAuth</strong> untuk autentikasi masuk menggunakan akun Google.</li>
                <li><strong>Single Sign-On (SSO)</strong> untuk perpindahan autentikasi ke sistem sekolah lain yang terhubung.</li>
              </ul>
              <p class="sae-privacy-copy" style="margin-top:.75rem;">Data hanya dibagikan sejauh diperlukan untuk fungsi layanan tersebut dan sesuai pengaturan yang diaktifkan oleh pengelola sistem.</p>
            </div>

            <div class="sae-privacy-section">
              <h2>5. Penyimpanan, keamanan, dan retensi data</h2>
              <ul class="sae-privacy-list">
                <li>Data disimpan pada server aplikasi dan basis data yang dikelola oleh pengelola sistem atau pihak yang ditunjuk.</li>
                <li>Sistem menggunakan kontrol keamanan seperti session management, token CSRF, pembatasan hak akses berbasis peran, validasi input, dan pencatatan aktivitas tertentu.</li>
                <li>Dokumen dan file pendukung disimpan pada struktur direktori aplikasi sesuai kategori layanan masing-masing.</li>
                <li>Lamanya penyimpanan data mengikuti kebutuhan operasional sekolah, kewajiban administrasi, kebijakan internal, dan ketentuan yang berlaku.</li>
              </ul>
            </div>

            <div class="sae-privacy-section">
              <h2>6. Hak pengguna dan tanggung jawab penggunaan</h2>
              <ul class="sae-privacy-list">
                <li>Pengguna berhak meminta koreksi data yang tidak sesuai melalui operator atau pengelola sistem sekolah.</li>
                <li>Pengguna bertanggung jawab menjaga kerahasiaan akun, kata sandi, serta perangkat yang digunakan untuk mengakses aplikasi.</li>
                <li>Penggunaan aplikasi hanya diperbolehkan untuk keperluan yang sah, relevan dengan layanan sekolah, dan tidak melanggar aturan internal.</li>
                <li>Penyalahgunaan layanan, percobaan akses tanpa izin, atau manipulasi data dapat dikenai pembatasan akses dan tindak lanjut oleh pengelola sistem.</li>
              </ul>
            </div>

            <div class="sae-privacy-section">
              <h2>7. Perubahan kebijakan</h2>
              <p class="sae-privacy-copy">Kebijakan ini dapat diperbarui sewaktu-waktu mengikuti pengembangan aplikasi, perubahan proses bisnis sekolah, integrasi baru, atau kebutuhan kepatuhan. Versi terbaru akan dipublikasikan pada halaman ini dan berlaku sejak tanggal pembaruan ditampilkan.</p>
            </div>

            <div class="sae-privacy-section">
              <h2>8. Kontak pengelola</h2>
              <p class="sae-privacy-copy" style="margin-bottom:.6rem;">Untuk pertanyaan terkait privasi, kebijakan penggunaan, koreksi data, atau pelaporan masalah layanan, silakan hubungi pengelola sistem sekolah melalui kontak yang tersedia.</p>
              <ul class="sae-privacy-list">
                <?php if ($sae_privacy_email !== ''): ?>
                  <li>Email: <a href="mailto:<?php echo htmlspecialchars($sae_privacy_email); ?>"><?php echo htmlspecialchars($sae_privacy_email); ?></a></li>
                <?php endif; ?>
                <?php if ($sae_privacy_phone !== ''): ?>
                  <li>Telepon: <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $sae_privacy_phone)); ?>"><?php echo htmlspecialchars($sae_privacy_phone); ?></a></li>
                <?php endif; ?>
                <li>Halaman utama aplikasi: <a href="<?php echo htmlspecialchars($sae_privacy_home_href); ?>"><?php echo htmlspecialchars($sae_privacy_is_admin ? './' : rtrim($sae_privacy_home_href, '/')); ?></a></li>
              </ul>
            </div>
          </div>
        </div>

<?php if ($sae_privacy_is_admin): ?>
  </div>
<?php else: ?>
      </div>
    </div>
  </div>
<?php endif; ?>