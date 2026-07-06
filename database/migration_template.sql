-- =============================================================
-- Migration: surat_template — Template Surat Berbasis Google Docs
-- Modul ID: 131
-- =============================================================

-- 1. Buat tabel surat_template (struktur baru)
CREATE TABLE IF NOT EXISTS `surat_template` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `indeks_surat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_surat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_pembuat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_dokumen` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `html_content` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drive_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_drive_file_id` (`drive_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Daftarkan modul (ON DUPLICATE KEY aman jika sudah ada)
INSERT INTO `modul` (`modul_id`, `modul_nama`) VALUES
  (131, 'Template Surat (Google Docs)')
ON DUPLICATE KEY UPDATE `modul_nama` = VALUES(`modul_nama`);

-- 3. Beri hak akses untuk level 1 (admin super) — role_id auto-increment
INSERT INTO `role` (`level_id`, `modul_id`, `lihat`, `modifikasi`, `hapus`) VALUES
  (1, 131, 'Y', 'Y', 'Y');
