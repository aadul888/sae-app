# 🏫 Smart Apps Education (SAE) v5 — School Administration E-System

### Sistem Administrasi Sekolah Digital Terintegrasi

> Solusi lengkap untuk manajemen kehadiran, tata tertib, inventaris, dan administrasi sekolah berbasis web — dirancang khusus untuk kebutuhan sekolah menengah di Indonesia.

---

## 📋 Daftar Isi

1. [Tentang Sistem](#-tentang-sistem)
2. [Spesifikasi Teknis](#-spesifikasi-teknis)
3. [Pembaruan Terbaru (Mei 2026)](#-pembaruan-terbaru-mei-2026)
4. [Fitur](#-fitur)
5. [Modul Publik (Halaman Depan)](#-modul-publik-halaman-depan)
6. [Dashboard Murid](#-dashboard-murid)
7. [Panel Admin](#-panel-admin--46-modul-inti)
8. [Hak Akses & Level Pengguna](#-hak-akses--level-pengguna)
9. [Tugas Tambahan (Role Khusus)](#-tugas-tambahan-role-khusus)
10. [Integrasi Pihak Ketiga](#-integrasi-pihak-ketiga)
11. [Sistem Notifikasi](#-sistem-notifikasi)
12. [API & Sinkronisasi Data](#-api--sinkronisasi-data)
13. [Ekspor & Cetak Laporan](#-ekspor--cetak-laporan)
14. [Manajemen File & Dokumen](#-manajemen-file--dokumen)
15. [Keamanan Sistem](#-keamanan-sistem)
16. [Pengaturan Sistem](#-pengaturan-sistem)
17. [Kebutuhan Server](#-kebutuhan-server)
18. [Keunggulan Kompetitif](#-keunggulan-kompetitif)
19. [Screenshot & Demo](#-screenshot--demo)
20. [Paket & Lisensi](#-paket--lisensi)
21. [Kontak & Dukungan](#-kontak--dukungan)

---

## 📖 Tentang Sistem

**SAE v5 (School Administration E-System)** adalah platform manajemen sekolah berbasis web yang komprehensif dan terintegrasi. Dibangun dengan arsitektur modular, sistem ini mencakup seluruh aspek operasional sekolah — mulai dari **absensi real-time dengan RFID**, **sistem poin tata tertib**, **manajemen inventaris kelas**, hingga **Program Indonesia Pintar (PIP)**.

Sistem ini dirancang khusus untuk konteks pendidikan Indonesia dengan integrasi langsung ke **Dapodik** (Data Pokok Pendidikan) dan mendukung standar administrasi Kementerian Pendidikan.

### Siapa yang membutuhkan?

| Target Pengguna | Kebutuhan |
|:---|:---|
| **SMK** | Manajemen kehadiran, tata tertib, inventaris |
| **Sekolah dengan sistem RFID** | Absensi otomatis berbasis kartu |
| **Sekolah penerima PIP** | Pengelolaan dan ranking usulan PIP |
| **Sekolah yang ingin go-digital** | Mengganti administrasi manual ke sistem digital |

---

## ⚙️ Spesifikasi Teknis

| Komponen | Teknologi |
|:---|:---|
| **Backend** | PHP 7.4+ / PHP 8.x |
| **Database** | MySQL / MariaDB |
| **Frontend** | HTML5, CSS3, JavaScript ES6, Argon Dashboard (Bootstrap 4) + Bootstrap 5 pada halaman tertentu |
| **UI/UX** | Responsive Design, Glass-morphism, Dark/Light Mode (terutama pada modul publik tertentu) |
| **Autentikasi** | Session/Cookie + CSRF token pada alur/form yang menerapkan validasi token |
| **API** | RESTful API dengan `X-API-Key` dan fallback `Authorization: Bearer` |
| **QR Code** | PHPQRCode Library |
| **PDF** | Built-in PDF Generator |
| **Email** | PHPMailer (SMTP) |
| **OAuth** | Google OAuth 2.0 |
| **Notifikasi** | WhatsApp Gateway API |
| **Sinkronisasi** | Dapodik Sync Engine |
| **SSO** | Single Sign-On untuk integrasi antar-sistem |
| **Enkripsi** | AES-based Cookie Encryption |

---

## 🆕 Pembaruan Terbaru (Mei 2026)

Berikut ringkasan revisi antarmuka dan sinkronisasi yang sudah diterapkan pada rilis terbaru:

### 1. Konsistensi UI Modul Admin
- Tampilan desktop untuk modul **Admin**, **Menu Siswa**, dan **Pembaharuan** kini mengikuti pola visual modul **User**.
- Posisi toolbar aksi di header tabel distandarkan agar konsisten di seluruh modul.
- Kontrol DataTables pada mode desktop dirapikan: **Show entries di kiri** dan **Search di kanan**.

### 2. Penyelarasan Gaya Global (CSS)
- Styling modul terkait dipusatkan ke stylesheet global admin untuk memudahkan pemeliharaan.
- Struktur class modul diseragamkan agar mudah dipakai ulang pada modul baru.
- Pendekatan scoped-style dipertahankan untuk menghindari konflik antar modul.

### 3. Modul Sinkronisasi (Dapodik & PKL)
- Halaman **Koneksi Dapodik Langsung** telah dipulihkan dan distabilkan.
- Halaman **Kirim Data ke PKL** dirapikan (layout, statistik, dan aksi kirim data) dengan class global.
- Inline CSS lokal yang tidak perlu sudah dibersihkan agar lebih maintainable.

### 4. Perbaikan Stabilitas Tampilan
- Penyesuaian responsif desktop dilakukan tanpa mengubah perilaku mobile yang sudah berjalan.
- Konsistensi spacing, alignment, dan komponen header tabel diperbaiki lintas modul utama.

---

## 🌟 Fitur

Fitur SAE v5 disusun mengikuti urutan **sidebar admin** agar mudah dipetakan ke implementasi modul. Total mencakup **46 modul inti admin**, ditambah Dashboard, utilitas, dan dashboard murid.

### 1. Dashboard & Portal
- **Dashboard**: ringkasan statistik utama, notifikasi, dan quick action operasional harian.
- **Portal GTK**: akses guru/tenaga kependidikan untuk data kelas dan aktivitas pembelajaran.

### 2. Administrasi (8 modul)
- **Murid Aktif**, **Murid Tidak Aktif**, **Guru Aktif**, **Guru Tidak Aktif**.
- **Berkas/Dokumen Murid**, **Usulan Perubahan Data**, **Jurusan**, **Kelas/Rombel**.
- Fokus: master data pengguna, validasi berkas, dan struktur akademik sekolah.

### 3. Absensi Digital (9 modul)
- **Kelola Izin Absensi**, **Registrasi RFID**, **Jadwal**, **Hari Libur**, **Lokasi Absen**, **Cetak Absensi Manual**.
- Sub-laporan: **Laporan Absensi Hari Ini**, **Per Kelas**, **Per Murid**.
- Mendukung RFID, geofencing GPS, dan rekap kehadiran multi-format.

### 4. E-Izin (1 modul)
- **E-Izin** untuk pengajuan, verifikasi, dan persetujuan izin elektronik berbasis tautan/QR.

### 5. Kurikulum (8 modul)
- **Pembelajaran**.
- Sub Agenda Kelas: **Referensi Agenda**, **Jadwal Kelas**, **Laporan Agenda**.
- Sub Kelulusan: **Pengaturan Rilis**, **Import SKL**, **History Kelulusan**, **E-Ijazah**.
- Fokus: manajemen kegiatan belajar sampai distribusi dokumen kelulusan.

### 6. Kesiswaan (9 modul)
- Sub PIP: **Kriteria PIP**, **Usulan PIP Semua**, **Usulan PIP Diterima**, **Usulan PIP Ranking**, **Riwayat PIP**.
- Sub Tata Tertib: **Ayat & Pasal**, **Data Pelanggaran**, **Pemanggilan**, **Sanggahan**.
- Fokus: pembinaan siswa, bantuan PIP, disiplin, dan proses banding.

### 7. Sarpras (4 modul)
- **Referensi Data**, **Inventaris Kelas**, **Peminjaman Inventaris**, **Laporan Inventaris**.
- Mencakup siklus inventaris dari master barang sampai monitoring kondisi.

### 8. Hubin
- **Hubungan Industri** disiapkan pada sidebar dan berstatus **segera hadir**.

### 9. Pengaturan (6 modul)
- **Pengaturan Web**, **Admin**, **Hak Akses**, **Menu/Fitur Murid**, **Pemberitahuan**, **Sinkronisasi Data**.
- Mencakup konfigurasi sistem, RBAC, aktivasi menu murid, dan integrasi sinkronisasi eksternal.

### 10. Utilitas
- **Tentang**, **Privasi & Kebijakan**, **Keluar** untuk informasi sistem, kebijakan, dan manajemen sesi.

### 11. Dashboard Murid (19 modul)
- **Home + 18 modul murid** tersedia sesuai pengaturan **Menu/Fitur Murid** di admin.
- Modul utama meliputi identitas, berkas, absensi, izin, tata tertib, poin, usulan PIP, agenda/kelas, inventaris kelas, FAQ, profil, dan aplikasi lainnya.

---

## 🌐 Modul Publik (Halaman Depan)

Halaman yang dapat diakses **tanpa login**:

| Modul | Deskripsi |
|:---|:---|
| **🏠 Home** | Landing page dengan statistik sekolah (jumlah siswa, sebaran kelas, gender) |
| **🔐 Login** | Login via NISN, username, atau email — mendukung Google OAuth |
| **📟 Absensi RFID** | Tampilan kiosk RFID dengan status kehadiran real-time per kelas |
| **📊 Realtime Dapodik** | Dashboard kualitas data Dapodik (jumlah siswa, konfirmasi, validasi) |
| **📅 Agenda** | Jadwal kehadiran guru di kelas hari ini |
| **🔍 Cek NISN** | Cari data siswa berdasarkan NISN (foto, QR code, kelas, status) |
| **📝 Buku Tamu** | Pencatatan dan statistik tamu sekolah |

---

## 🎓 Dashboard Murid

Dashboard murid adalah portal personal siswa yang dapat diakses setelah login. Modul tersedia di `dashboard/mod/` dan visibilitasnya dikontrol secara dinamis oleh admin melalui menu **Menu/Fitur Murid**. Saat ini terdapat **18 modul + Home** yang tersedia.

### 🏠 Beranda (Home)

Halaman utama dashboard murid menampilkan:
- **Modal Pembaharuan** — popup otomatis berisi catatan rilis/update sistem terbaru
- **Ringkasan Kehadiran** — rekap hadir, izin, sakit, alpha siswa
- **Ringkasan Poin Pelanggaran** — total poin dan status terkini
- **Shortcut Menu** — akses cepat ke modul-modul utama
- **Form Nomor Telepon Orang Tua** — wajib diisi jika belum tersedia (modal enforced)
- **Informasi Kelas & Tahun Pelajaran Aktif**

### 📋 Daftar Modul Dashboard Murid

| No | Modul | Slug | Fungsi |
|:--:|:---|:---|:---|
| 1 | **🏠 Home** | `home` | Dashboard utama dengan ringkasan kehadiran, poin, pembaharuan, dan shortcut menu |
| 2 | **👤 Identitas** | `identitas` | Menampilkan biodata lengkap siswa, data keluarga, dan informasi akademik dasar |
| 3 | **📁 Berkas** | `berkas` | Upload dan pengecekan dokumen siswa: KK, Akte, Ijazah, KIP, KKS, KIS |
| 4 | **📋 Absensi** | `absensi` | Riwayat kehadiran pribadi, status harian, dan rekap per periode |
| 5 | **📝 Izin** | `izin` | Monitoring pengajuan izin dan sakit yang sudah dibuat siswa |
| 6 | **✏️ Edit Identitas** | `edit-identitas` | Pengajuan perubahan data ketika data siswa belum sesuai atau perlu revisi |
| 7 | **📖 Tata Tertib** | `tata-tertib` | Daftar lengkap tata tertib, pasal, ayat, dan bobot poin pelanggarannya |
| 8 | **⚠️ Poin** | `poin` | Rekap poin pelanggaran, detail setiap kejadian, dan status sanggahan |
| 9 | **💳 Usulan PIP** | `usulan-pip` | Pengajuan dan pemantauan status usulan Program Indonesia Pintar |
| 10 | **👥 Kelas Q** | `kelas-q` | Ringkasan data kelas dan akses cepat ke informasi rombongan belajar |
| 11 | **📨 E-Izin** | `e-izin` | Form pengajuan izin digital dengan tautan/QR untuk proses verifikasi |
| 12 | **✅ E-KPD** | `ekpd` | Konfirmasi dan validasi data pribadi siswa secara mandiri |
| 13 | **📅 Agenda Kelas** | `agenda-kelas` | Lihat agenda dan jadwal kegiatan kelas yang dijadwalkan oleh guru |
| 14 | **📊 Absensi Kelas** | `absensi-kelas` | Overview kehadiran seluruh siswa pada level kelas |
| 15 | **🧾 Cek Data Kelas** | `cek-data-kelas` | Verifikasi data kelas dan cetak dokumen terkait data rombongan belajar |
| 16 | **🏷️ Inventaris Kelas** | `invetaris-kelas` | Informasi inventaris yang tercatat dan ditempatkan di kelas siswa |
| 17 | **❓ FAQ** | `faq` | Pusat pertanyaan umum dan bantuan penggunaan sistem |
| 18 | **👤 Profile Saya** | `profile` | Pengaturan profil akun siswa, ubah foto, dan ubah password |
| 19 | **🧩 Aplikasi Lainnya** | `applain` | Launcher ke aplikasi terkait: e-PKL, e-KPD lama, dan layanan sekolah lain |

> **💡 Catatan:** Status aktif/nonaktif setiap modul dikendalikan dari menu **Menu/Fitur Murid** di panel admin. Modul yang dinonaktifkan akan menampilkan halaman "Tutup Sementara" saat diakses siswa.

---

## 🛡️ Panel Admin — 46 Modul Inti

Daftar berikut disusun berdasarkan struktur menu pada **sidebar admin** yang sesungguhnya, mencakup semua grup, sub-grup, dan modul navigasi. Selain **46 modul inti**, admin memiliki **Dashboard** sebagai halaman masuk utama dan link utilitas di bawah sidebar (**Tentang**, **Privasi & Kebijakan**, **Keluar**).

Visibilitas setiap modul dikendalikan oleh sistem **RBAC** (Role-Based Access Control) — hanya modul yang diizinkan untuk level/role pengguna saja yang tampil di sidebar.

---

### 🔷 A. Dashboard & Portal

| Modul | URL | Fungsi |
|:---|:---|:---|
| **Dashboard** | `./` | Halaman masuk utama — ringkasan statistik, grafik kehadiran, notifikasi, dan pintasan akses cepat |
| **Portal GTK** | `./portal-gtk` | Portal khusus guru dan tenaga kependidikan — akses cepat ke data kelas dan presensi |

---

### 🔷 B. Administrasi (8 modul)

Kelola seluruh data pengguna sistem — murid, guru, dokumen, dan struktur akademik.

| Modul | URL | Fungsi |
|:---|:---|:---|
| **Murid Aktif** | `./user` | Kelola data murid aktif: tambah, edit, hapus, reset password, export, dan lihat detail profil |
| **Murid Tidak Aktif** | `./user-tidak-aktif` | Kelola data murid nonaktif, lulus, atau pindah sekolah |
| **Guru Aktif** | `./guru` | Kelola data guru dan tenaga kependidikan yang masih aktif |
| **Guru Tidak Aktif** | `./guru-tidak-aktif` | Kelola data guru yang sudah tidak aktif atau pensiun |
| **Berkas/Dokumen Murid** | `./berkas` | Validasi dokumen murid (KK, Akte, Ijazah, KIP, KKS, KIS) — tampilkan badge notifikasi jumlah yang belum divalidasi |
| **Usulan Perubahan Data** | `./edit-identitas` | Tinjau dan setujui/tolak pengajuan perubahan identitas dari murid — badge jumlah pengajuan aktif |
| **Jurusan** | `./jurusan` | Kelola jurusan atau program keahlian yang tersedia di sekolah |
| **Kelas/Rombel** | `./kelas` | Kelola kelas dan rombongan belajar — nama kelas, jurusan, wali kelas, dan kapasitas |

---

### 🔷 C. Absensi Digital (9 modul)

Sistem absensi lengkap — dari konfigurasi hingga laporan — dengan dukungan RFID, GPS, foto, dan izin.

| Modul | URL | Fungsi |
|:---|:---|:---|
| **Kelola Izin Absensi** | `./absensi-izin` | Approve atau tolak pengajuan izin/sakit absensi dari murid |
| **Registrasi RFID** | `./absensi-registrasi` | Daftarkan kartu atau tag RFID ke akun murid untuk absensi otomatis |
| **Jadwal** | `./jadwal` | Atur jadwal jam masuk, jam pulang, dan toleransi keterlambatan per hari |
| **Hari Libur** | `./libur` | Kelola kalender hari libur nasional dan libur sekolah |
| **Lokasi Absen** | `./absensi-lokasi` | Konfigurasi titik koordinat GPS dan radius geofencing untuk absensi berbasis lokasi |
| **Cetak Absensi Manual** | `./cetak-absensi` | Cetak form absensi manual untuk digunakan oleh guru piket atau wali kelas |

**Sub-grup: Laporan Absensi**

| Modul | URL | Fungsi |
|:---|:---|:---|
| **↳ Hari Ini** | `./laporan-absensi` | Rekap kehadiran seluruh murid hari ini — filter per kelas, lihat hadir/izin/sakit/alpha |
| **↳ Per Kelas** | `./laporan-absensi-kelas` | Rekap absensi berdasarkan kelas dalam rentang tanggal — export PDF/Excel |
| **↳ Per Murid** | `./laporan-absensi-siswa` | Rekap absensi individual murid — riwayat lengkap per semester atau rentang tanggal |

---

### 🔷 D. E-Izin (1 modul)

| Modul | URL | Fungsi |
|:---|:---|:---|
| **E-Izin** | `./e-izin` | Kelola pengajuan izin elektronik dari murid — verifikasi via QR/tautan, kelola status persetujuan |

---

### 🔷 E. Kurikulum (8 modul)

Mengelola data pembelajaran, agenda kelas, dan proses kelulusan siswa.

| Modul | URL | Fungsi |
|:---|:---|:---|
| **Pembelajaran** | `./pembelajaran` | Pengelolaan data mata pelajaran dan referensi pembelajaran per kelas/jurusan |

**Sub-grup: Agenda Kelas**

| Modul | URL | Fungsi |
|:---|:---|:---|
| **↳ Referensi Agenda** | `./agenda-ref` | Kelola master/referensi jenis agenda yang dapat digunakan pada jadwal kelas |
| **↳ Jadwal Kelas** | `./agenda-jadwal` | Atur dan publikasikan jadwal agenda kelas — guru masuk, materi, dan status kehadiran |
| **↳ Laporan Agenda** | `./agenda-laporan` | Monitoring dan laporan realisasi agenda kelas — lihat mana yang terlaksana/tidak |

**Sub-grup: Kelulusan**

| Modul | URL | Fungsi |
|:---|:---|:---|
| **↳ Pengaturan Rilis** | `./skl-settings` | Atur waktu dan status publikasi pengumuman kelulusan/SKL untuk murid |
| **↳ Import SKL** | `./skl-import` | Import data Surat Keterangan Lulus dari file — mapping ke data murid yang bersangkutan |
| **↳ History Kelulusan** | `./skl-history` | Riwayat proses dan log publikasi kelulusan per tahun ajaran |
| **↳ E-Ijazah** | `./skl-ijazah` | Pengelolaan dan distribusi dokumen ijazah digital per murid |

---

### 🔷 F. Kesiswaan (9 modul)

Menugelola program dan data kesiswaan — Program Indonesia Pintar (PIP) dan sistem tata tertib.

**Sub-grup: Program Indonesia Pintar (PIP)**

| Modul | URL | Fungsi |
|:---|:---|:---|
| **↳ Kriteria** | `./kriteria-pip` | Konfigurasi bobot dan kriteria penilaian untuk ranking usulan PIP |
| **↳ Usulan Semua** | `./usulan-pip-semua` | Lihat semua pengajuan PIP dari murid dengan status lengkap |
| **↳ Usulan Diterima** | `./usulan-pip-diterima` | Kelola daftar usulan PIP yang sudah disetujui/diterima |
| **↳ Usulan Ranking** | `./usulan-pip-ranking` | Ranking otomatis usulan PIP berdasarkan skor kriteria |
| **↳ Riwayat PIP** | `./history-pip` | Riwayat status pencairan dan pemrosesan PIP per murid per periode |

**Sub-grup: Tata Tertib**

| Modul | URL | Fungsi |
|:---|:---|:---|
| **↳ Ayat & Pasal** | `./poin-tatib` | Master aturan tata tertib — kelola pasal, ayat, kategori pelanggaran, dan bobot poin |
| **↳ Data Pelanggaran** | `./poin` | Input dan monitoring pelanggaran murid — rekap poin per murid dan per periode |
| **↳ Pemanggilan** | `./poin-panggil` | Kelola surat pemanggilan orang tua/wali murid yang melanggar batas poin |
| **↳ Sanggahan** | `./poin-sanggah` | Tindak lanjut pengajuan sanggahan/banding dari murid atas catatan pelanggaran |

---

### 🔷 G. Sarpras (4 modul)

Manajemen sarana dan prasarana sekolah — inventaris, peminjaman, dan laporan kondisi barang.

| Modul | URL | Fungsi |
|:---|:---|:---|
| **Referensi Data** | `./inv-master` | Master kategori, jenis, dan referensi inventaris sekolah |
| **Inventaris Kelas** | `./inv-kelas` | Penempatan dan pengelolaan inventaris yang berada di setiap kelas |
| **Peminjaman Inventaris** | `./inv-pinjam` | Tracking peminjaman peralatan — catat peminjam, tanggal pinjam, dan pengembalian |
| **Laporan Inventaris** | `./inv-report` | Rekap kondisi inventaris seluruh kelas — Baik / Rusak Ringan / Rusak Berat / Hilang |

---

### 🔷 H. Hubin *(Segera Hadir)*

Menu **Hubungan Industri** saat ini dalam tahap pengembangan dan belum dapat diakses.

---

### 🔷 I. Pengaturan (6 modul)

Konfigurasi sistem, manajemen akun admin, hak akses, dan sinkronisasi data.

| Modul | URL | Fungsi |
|:---|:---|:---|
| **Pengaturan Web** | `./pengaturan` | Konfigurasi identitas situs (nama, logo, favicon, URL, email, telepon, alamat, timezone), mode maintenance, tahun pelajaran, SMTP, WhatsApp Gateway, Google OAuth, dan upload |
| **Admin** | `./admin` | Kelola akun administrator — tambah, edit, reset password, dan atur level/role |
| **Hak Akses** | `./hak-akses` | Pengaturan permission granular per modul per level — lihat, modifikasi, hapus |
| **Menu/Fitur Murid** | `./menu-siswa` | Aktifkan atau nonaktifkan modul yang tampil di dashboard murid, atur posisi menu |
| **Pemberitahuan** | `./pembaharuan` | Kelola catatan pembaharuan/rilis sistem yang muncul sebagai popup di dashboard murid |
| **Sinkronisasi Data** | `./sync` | Sinkronisasi data dari Dapodik dan kirim data ke sistem PKL — monitor status sync |

---

### 🔷 J. Utilitas (di bawah sidebar)

| Halaman | URL | Fungsi |
|:---|:---|:---|
| **Tentang** | `./tentang` | Informasi versi sistem, pembuat, dan kredit aplikasi |
| **Privasi & Kebijakan** | `./privasi-kebijakan` | Kebijakan privasi dan ketentuan penggunaan sistem |
| **Keluar** | `./logout` | Logout dan hapus sesi admin |

---

## 👥 Hak Akses & Level Pengguna

SAE v5 menggunakan **Role-Based Access Control (RBAC)** dengan 3 level utama dan 9 tugas tambahan:

### Level Utama

| Level | Nama | Akses |
|:---:|:---|:---|
| **1** | **Superadmin** | Akses penuh ke **seluruh 46 modul inti admin** beserta Dashboard, Portal GTK, dan seluruh utilitas sistem |
| **2** | **Guru** | Akses terbatas ke dashboard, Portal GTK, dan modul yang ditugaskan |
| **3** | **Tenaga Administrasi** | Manajemen siswa, berkas, absensi, dan operasional administrasi |

### Matriks Permission

Setiap level memiliki 3 jenis izin per modul:

| Permission | Kode | Deskripsi |
|:---|:---:|:---|
| **Lihat** | 👁️ | Hanya bisa melihat data |
| **Modifikasi** | ✏️ | Bisa menambah dan mengubah data |
| **Hapus** | 🗑️ | Bisa menghapus data |

> Admin dapat mengatur permission secara granular melalui modul **Hak Akses** — memilih modul mana yang bisa diakses oleh level tertentu beserta jenis izinnya.

---

## 🔧 Tugas Tambahan (Role Khusus)

Selain level utama, admin/guru dapat memiliki **tugas tambahan** yang memberikan akses ke modul spesifik:

| Level | Role | Deskripsi | Modul Utama |
|:---:|:---|:---|:---|
| **4** | **Waka Kurikulum** | Wakil Kepala Bidang Kurikulum | Jadwal, Agenda, Laporan Absensi |
| **5** | **Waka Humas** | Wakil Kepala Bidang Humas | Hubungan masyarakat, integrasi Pra-SPMB |
| **6** | **Waka Sarpras** | Wakil Kepala Bidang Sarana & Prasarana | Inventaris Master, Inv Kelas, Inv Pinjam, Inv Report |
| **7** | **Waka Kesiswaan** | Wakil Kepala Bidang Kesiswaan | Poin Tata Tertib, E-Izin, PIP, Pemanggilan |
| **8** | **Kepala Program Keahlian** | Ketua Jurusan/Kompetensi Keahlian | Siswa per jurusan, data spesifik program |
| **9** | **Wali Kelas** | Penanggungjawab kelas tertentu | Siswa di kelasnya, absensi, izin, poin, berkas |
| **10** | **Guru Piket** | Guru bertugas harian | Absensi harian, registrasi kehadiran |
| **11** | **Security** | Petugas keamanan | Absensi pintu gerbang, monitoring |
| **12** | **Toolman / Teknisi** | Petugas teknis jurusan | Inventaris per jurusan, peminjaman alat |

> **💡 Multi-Role:** Satu pengguna dapat memiliki **beberapa tugas tambahan** sekaligus (disimpan sebagai CSV). Contoh: Seorang guru bisa menjadi Wali Kelas sekaligus Guru Piket.

### Akses Wali Kelas (Contoh Tampilan)

Ketika login sebagai Wali Kelas, dashboard menampilkan:
- 📋 Daftar kelas yang diampu
- 👥 Foto dan data siswa per kelas
- 📊 Statistik siswa (L/P)
- ⚡ Quick access ke absensi, izin, dan poin kelas

---

## 🔗 Integrasi Pihak Ketiga

### 1. 🟢 WhatsApp Gateway
- Kirim notifikasi otomatis via WhatsApp
- Event trigger: **verifikasi HP**, **reset password**, **login alert**, **notifikasi umum**
- Auto-reply untuk pesan masuk
- Normalisasi nomor (0xxx → 62xxx)
- Logging aktivitas pengiriman (pending → sent/failed)
- Konfigurasi lengkap di panel admin

### 2. 🔵 Google OAuth 2.0
- Login menggunakan akun Google
- Registrasi otomatis dari data Google (nama, email)
- Integrasi di halaman login siswa dan dashboard

### 3. 🔄 Dapodik Sync
- Sinkronisasi data **GTK** (Guru & Tenaga Kependidikan)
- Sinkronisasi data **Peserta Didik**
- Sinkronisasi data **Rombel** (Rombongan Belajar)
- Sinkronisasi data **Sekolah**
- Mapping field: PTK ID, NUPTK, NIK, NISN
- Batch processing dengan database transaction

### 4. 🔑 SSO (Single Sign-On)
- Integrasi SSO ke sistem PKL (Praktik Kerja Lapangan)
- Redirect otomatis dengan token terenkripsi
- Shared authentication antar aplikasi

### 5. 📧 Email (SMTP)
- Kirim email via PHPMailer
- Reset password, notifikasi, dan laporan
- Konfigurasi SMTP lengkap di panel admin

---

## 📢 Sistem Notifikasi

| Channel | Event | Detail |
|:---|:---|:---|
| **WhatsApp** | Verifikasi Nomor HP | Kode verifikasi dikirim ke orang tua |
| **WhatsApp** | Reset Password | Link/kode reset dikirim via WA |
| **WhatsApp** | Login Alert | Notifikasi ke orang tua saat siswa login |
| **WhatsApp** | Notifikasi Umum | Pengumuman dari admin |
| **Email** | Reset Password | Email berisi link reset |
| **In-App** | Pengumuman Sistem | Modal popup saat login dashboard |
| **In-App** | Update Versi | Notifikasi pembaharuan sistem |

---

## 🔌 API & Sinkronisasi Data

### RESTful API

| Aspek | Detail |
|:---|:---|
| **Autentikasi** | Bearer Token via header `X-API-Key` |
| **Format Response** | JSON |
| **Validasi** | Server-side validation untuk semua endpoint |
| **Logging** | Activity dan error logging otomatis |

### Endpoint Utama

| Endpoint | Method | Fungsi |
|:---|:---:|:---|
| `/api/sync.php` | POST | Router utama sinkronisasi data |
| `/api/receive-data.php` | POST | Terima data dari sistem eksternal |
| `/api/endpoints/sync_data.php` | POST | Sync data Dapodik |
| `/api/whatsapp-webhook.php` | POST | Terima pesan masuk WhatsApp |

### Data Handler

| Handler | Fungsi |
|:---|:---|
| `GtkHandler` | Sync data guru & tenaga kependidikan |
| `PenggunaHandler` | Sync data pengguna/admin |
| `PesertaDidikHandler` | Sync data siswa |
| `RombelHandler` | Sync data rombongan belajar |
| `SekolahHandler` | Sync data profil sekolah |

> Semua handler mendukung **batch processing**, **upsert operations** (INSERT ON DUPLICATE KEY UPDATE), dan **database transactions** untuk integritas data.

---

## 📤 Ekspor & Cetak Laporan

| Jenis Laporan | Format | Deskripsi |
|:---|:---:|:---|
| **Absensi Harian** | PDF / Excel / Print | Kehadiran per hari dengan filter kelas |
| **Absensi Per Siswa** | PDF / Excel | Rekap kehadiran individual |
| **Absensi Per Kelas** | PDF / Excel | Rekap kehadiran seluruh kelas |
| **Laporan Pelanggaran** | PDF | Daftar pelanggaran dan poin |
| **Laporan Inventaris** | PDF | Status inventaris per kelas |
| **Daftar Usulan PIP** | PDF | Ranking dan status PIP |
| **QR Code Siswa** | Image (JPG) | QR code berdasarkan NISN |

> Filter yang tersedia: **Kelas**, **Bulan**, **Tahun**, **Rentang Tanggal**, **Jurusan**

---

## 📂 Manajemen File & Dokumen

### Struktur Penyimpanan

```
content/
├── avatar/              → Foto profil siswa (NISN.jpg)
├── berkas/
│   ├── KK/              → Kartu Keluarga
│   ├── Akte/            → Akte Kelahiran
│   ├── Ijazah/          → Ijazah sebelumnya
│   ├── KIP/             → Kartu Indonesia Pintar
│   ├── KIS/             → Kartu Indonesia Sehat
│   └── Usulan-pip/      → Dokumen pengajuan PIP
├── qrcode/              → QR Code per siswa (NISN.jpg)
├── capture/             → Foto capture kamera (absensi)
├── pelanggaran/         → Bukti foto pelanggaran
├── agenda/              → File agenda/jadwal
├── icon-apps/           → Ikon aplikasi
└── sound/               → Audio notifikasi/alert
```

### Fitur Upload
- Validasi tipe file otomatis
- Fallback avatar (default jika belum upload)
- Organisasi otomatis berdasarkan NISN/kategori

---

## 🔒 Keamanan Sistem

| Fitur | Implementasi |
|:---|:---|
| **CSRF Protection** | Token generation & validation di setiap form |
| **Session Management** | Secure cookie dengan enkripsi AES |
| **SQL Injection Prevention** | Prepared statements pada API inti/sinkronisasi dan validasi input server-side |
| **Input Validation** | Server-side validation untuk semua input |
| **Role-Based Access** | Granular permission per modul per level |
| **HTTPS Redirect** | Auto-redirect ke HTTPS di production |
| **Data Encryption** | Enkripsi cookie dan data sensitif |
| **Error Handling** | Halaman error bersih (401, 404, maintenance) |
| **Activity Logging** | Log aktivitas WhatsApp, sync, dan modifikasi |
| **Password Policy** | Kebijakan kompleksitas (min. 6 karakter + kombinasi karakter) diterapkan pada alur ubah/reset password utama |

---

## ⚙️ Pengaturan Sistem

Admin dapat mengonfigurasi **8 aspek** melalui panel pengaturan:

| Tab | Opsi yang Tersedia |
|:---|:---|
| **🌐 Pengaturan Web** | Nama situs, logo, favicon, URL, email, telepon, alamat, timezone |
| **🔧 Buka/Tutup Sistem** | Mode maintenance ON/OFF dengan pesan kustom |
| **📅 Tahun Pelajaran** | Tahun ajaran aktif dan semester |
| **📱 Tambah Menu** | Buat menu kustom untuk siswa |
| **📧 Email Config** | SMTP server, port, credentials, enkripsi |
| **💬 WhatsApp Gateway** | URL API, API key, auto-reply, enable/disable |
| **🔑 Google OAuth** | Client ID, Client Secret, callback URL |
| **📁 File Upload** | Direktori upload, batasan tipe file |

---

## 💻 Kebutuhan Server

### Minimum

| Komponen | Spesifikasi |
|:---|:---|
| **Web Server** | Apache 2.4+ / Nginx 1.18+ |
| **PHP** | 7.4 atau lebih tinggi |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ |
| **RAM** | 1 GB |
| **Storage** | 2 GB (tanpa file upload) |
| **SSL** | Disarankan untuk production |

### Rekomendasi

| Komponen | Spesifikasi |
|:---|:---|
| **PHP** | 8.0 - 8.2 |
| **RAM** | 2 GB+ |
| **Storage** | 10 GB+ (termasuk file upload) |
| **SSL** | Let's Encrypt / SSL Komersial |
| **OS** | Ubuntu 22.04 / AlmaLinux 9 |

### Kompatibilitas

- ✅ XAMPP / WAMP / MAMP / Laragon
- ✅ cPanel / Plesk / DirectAdmin
- ✅ VPS (DigitalOcean, Vultr, Linode, dll)
- ✅ Shared Hosting (dengan PHP 7.4+ dan MySQL)

---

## 🏆 Keunggulan Kompetitif

| Aspek | SAE v5 | Sistem Lain |
|:---|:---:|:---:|
| Absensi RFID + Foto | ✅ | ❌ Biasanya hanya salah satu |
| Integrasi Dapodik | ✅ | ❌ Jarang tersedia |
| WhatsApp Notifikasi | ✅ | ⚠️ Terbatas |
| 12 Level Akses | ✅ | ⚠️ 2-3 level saja |
| Sistem Poin Tata Tertib | ✅ | ❌ Tidak ada |
| Manajemen PIP | ✅ | ❌ Tidak ada |
| Inventaris Kelas | ✅ | ❌ Tidak ada |
| Google OAuth | ✅ | ⚠️ Jarang |
| API RESTful | ✅ | ❌ Tidak ada |
| Buku Tamu Digital | ✅ | ❌ Tidak ada |
| SSO Antar Sistem | ✅ | ❌ Tidak ada |
| Multi-Role per User | ✅ | ❌ 1 role saja |
| Ekspor PDF + Excel | ✅ | ⚠️ PDF saja |
| Responsive Mobile | ✅ | ⚠️ Terbatas |

### Highlight

- 🎯 **46 modul inti admin di sidebar** — terstruktur sesuai alur kerja sekolah
- 🔄 **Sinkronisasi Dapodik** — tidak perlu input data ganda
- 📱 **Notifikasi orang tua via WhatsApp** — engagement tinggi
- 🔐 **12 level akses** dengan multi-role — sesuai struktur sekolah nyata
- 📊 **Dashboard real-time** — monitoring kualitas data dan kehadiran
- 🖨️ **Multi-format export** — PDF, Excel, dan Print langsung
- ⚡ **Setup cepat** — kompatibel dengan shared hosting

---

## 📸 Screenshot & Demo

> *(Tambahkan screenshot di bawah ini)*

| Halaman | Preview |
|:---|:---:|
| Landing Page | `screenshot-landing.png` |
| Dashboard Admin | `screenshot-admin.png` |
| Dashboard Murid | `screenshot-murid.png` |
| Absensi RFID | `screenshot-rfid.png` |
| Laporan Kehadiran | `screenshot-laporan.png` |
| Poin Tata Tertib | `screenshot-poin.png` |
| Panel Pengaturan | `screenshot-pengaturan.png` |

> **Demo Online:** *(Tambahkan URL demo)*

---

## 📦 Paket & Lisensi

| Paket | Detail |
|:---|:---|
| **🥉 Basic** | Sistem inti + 1x instalasi + dokumentasi |
| **🥈 Standard** | Basic + integrasi WhatsApp + Dapodik Sync + 3 bulan support |
| **🥇 Premium** | Standard + kustomisasi + training + 12 bulan support + update |

### Termasuk dalam Semua Paket:
- ✅ Source code lengkap
- ✅ Database SQL siap pakai
- ✅ Dokumentasi instalasi
- ✅ Panduan penggunaan
- ✅ Free update minor

### Add-on (Opsional):
- 🔧 Instalasi di server klien
- 📚 Training admin & guru (online/onsite)
- 🎨 Kustomisasi desain/branding sekolah
- 🔄 Integrasi sistem lain (PPDB, E-Rapor, dll)
- 📞 Support prioritas (WhatsApp/Telegram)

---

## 📞 Kontak & Dukungan

| Channel | Detail |
|:---|:---|
| **Website** | *(Tambahkan URL)* |
| **Email** | *(Tambahkan email)* |
| **WhatsApp** | *(Tambahkan nomor)* |
| **Demo** | *(Tambahkan URL demo)* |

---

## 📊 Ringkasan Angka

| Metrik | Jumlah |
|:---:|:---:|
| **Total Modul Admin** | 46 modul inti + Dashboard + 3 utilitas (Tentang, Privasi, Keluar) |
| **Total Modul Murid** | 18 modul + Home (19 total) |
| **Total Modul Publik** | 7 |
| **Level Akses** | 12 |
| **Tabel Database** | 30+ |
| **API Handler** | 5 |
| **Integrasi** | 5 (WhatsApp, Google, Dapodik, SSO, Email) |
| **Format Ekspor** | 3 (PDF, Excel, Print) |
| **Jenis Dokumen** | 6 (KK, Akte, Ijazah, KIP, KKS, KIS) |

---

<p align="center">
  <strong>SAE v5</strong> — Sistem Administrasi Sekolah Terlengkap untuk Era Digital 🇮🇩
</p>

---

*Dokumen ini dibuat secara otomatis berdasarkan analisis kode sumber SAE v5. Terakhir diperbarui: Mei 2026.*
