-- =====================================================================
--  HUBIN (Hubungan Industri) module schema  -- added for SAE v5
--  Submodules: Buku Tamu, Referensi Tamu, Tarik Peserta PKL
--  Compatible with MySQL/MariaDB, PHP 8.3.
--  Safe to run multiple times (IF NOT EXISTS / INSERT ... ON DUPLICATE).
-- =====================================================================

-- ---------------------------------------------------------------------
-- Referensi: master Instansi (reusable, dapat dipilih ulang oleh tamu)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tamu_instansi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) NOT NULL,
  `jenis` varchar(40) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `active` varchar(2) NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_instansi_nama` (`nama`),
  KEY `idx_instansi_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Referensi: master Tujuan / Keperluan kunjungan
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tamu_tujuan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `active` varchar(2) NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tujuan_nama` (`nama`),
  KEY `idx_tujuan_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Buku tamu (dibuat juga on-demand oleh module/tamu/proses.php). Schema
-- lengkap di sini sebagai sumber kebenaran.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `buku_tamu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_id` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `instansi` varchar(150) NOT NULL,
  `instansi_id` int(11) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `keperluan` varchar(100) NOT NULL,
  `tujuan_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `waktu_masuk` time NOT NULL,
  `waktu_keluar` time DEFAULT NULL,
  `status` enum('Aktif','Selesai','Batal') DEFAULT 'Aktif',
  `survey_done` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_guest_id` (`guest_id`),
  KEY `idx_tanggal` (`tanggal_kunjungan`),
  KEY `idx_status` (`status`),
  KEY `idx_instansi_id` (`instansi_id`),
  KEY `idx_tujuan_id` (`tujuan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Log aktivitas buku tamu (CHECKIN/CHECKOUT/UPDATE/DELETE/SURVEY)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `buku_tamu_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_table_id` int(11) NOT NULL,
  `guest_id` varchar(50) NOT NULL,
  `activity` enum('CHECKIN','CHECKOUT','UPDATE','DELETE','SURVEY') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_guest_id` (`guest_id`),
  KEY `idx_activity` (`activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Survey kepuasan pelayanan (diisi tamu setelah checkout)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `buku_tamu_survey` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_table_id` int(11) NOT NULL,
  `guest_id` varchar(50) NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 0,
  `pelayanan` tinyint(1) NOT NULL DEFAULT 0,
  `kecepatan` tinyint(1) NOT NULL DEFAULT 0,
  `kenyamanan` tinyint(1) NOT NULL DEFAULT 0,
  `komentar` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_survey_guest` (`guest_id`),
  KEY `idx_survey_guest_table` (`guest_table_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Daftar peserta PKL yang ditarik & dikirim ke aplikasi e-PKL
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pkl_peserta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `nisn` varchar(25) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `status_kirim` enum('Pending','Terkirim','Gagal') NOT NULL DEFAULT 'Pending',
  `response_msg` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pkl_nisn` (`nisn`),
  KEY `idx_pkl_status` (`status_kirim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Seed menu modul (Hubin submenus). modul_id 49,50,51.
-- ---------------------------------------------------------------------
INSERT INTO `modul` (`modul_id`, `modul_nama`) VALUES
  (49, 'Buku Tamu'),
  (50, 'Referensi Tamu'),
  (51, 'Tarik Peserta PKL')
ON DUPLICATE KEY UPDATE `modul_nama` = VALUES(`modul_nama`);

-- ---------------------------------------------------------------------
-- Seed beberapa tujuan default (hanya jika tabel masih kosong)
-- ---------------------------------------------------------------------
INSERT INTO `tamu_tujuan` (`nama`)
SELECT v.nama FROM (
  SELECT 'Rapat/Meeting' AS nama UNION ALL
  SELECT 'Konsultasi' UNION ALL
  SELECT 'Kunjungan Kerja' UNION ALL
  SELECT 'Penelitian' UNION ALL
  SELECT 'Magang/PKL' UNION ALL
  SELECT 'Wawancara' UNION ALL
  SELECT 'Pengantaran Barang' UNION ALL
  SELECT 'Lainnya'
) v
WHERE NOT EXISTS (SELECT 1 FROM `tamu_tujuan` LIMIT 1);
