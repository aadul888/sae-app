-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 13, 2026 at 10:54 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sae`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `status_masuk` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status_pulang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kehadiran` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_masuk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_pulang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'rfid',
  `manual_by` int DEFAULT NULL,
  `manual_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `approval_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'approved',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `absensi_edit_request`
--

CREATE TABLE `absensi_edit_request` (
  `id` int NOT NULL,
  `kelas_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `requested_by` int NOT NULL COMMENT 'user_id koordinator',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `responded_by` int DEFAULT NULL COMMENT 'admin_id',
  `responded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int UNSIGNED NOT NULL,
  `admin_id` int UNSIGNED DEFAULT NULL,
  `admin_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'login, logout, update, delete, create, deploy, migration',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int NOT NULL,
  `fullname` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gelar_depan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gelar_belakang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_login` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `time` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level_id` int NOT NULL,
  `ptk_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID PTK dari Dapodik - untuk linking dengan sync_gtk',
  `pengguna_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_status` enum('synced','manual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'manual' COMMENT 'synced=dari Dapodik, manual=input manual admin',
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu sinkronisasi terakhir dengan Dapodik',
  `ip` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `browser` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tugas_tambahan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `peran_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_ptk_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gtk_status_kepegawaian` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gtk_jabatan_ptk` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gtk_nip` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gtk_nuptk` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gtk_nik` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Default superadmin credentials (stored in database only, NOT in source code)
-- username: admin@sae.id
-- password: Admin543!
--
INSERT INTO `admin` (`admin_id`, `fullname`, `gelar_depan`, `gelar_belakang`, `username`, `phone`, `email`, `password`, `avatar`, `tanggal_login`, `last_login_ip`, `last_activity_at`, `time`, `status`, `level_id`, `ptk_id`, `pengguna_id`, `sync_status`, `last_sync_at`, `ip`, `browser`, `active`, `tugas_tambahan`, `peran_id_str`, `jenis_ptk_id_str`, `gtk_status_kepegawaian`, `gtk_jabatan_ptk`, `gtk_nip`, `gtk_nuptk`, `gtk_nik`, `created_at`, `updated_at`)
VALUES
(1, 'Administrator SAE', NULL, NULL, 'admin@sae.id', '', 'admin@sae.id', '$2y$10$/mDO.4HWUHZz.lk49zz5CuM7Hk1ctY9tCOJE6MRi9XSnGCXjFVUP2', 'avatar.jpg', NULL, NULL, NULL, '0', 'Offline', 1, NULL, NULL, 'manual', NULL, '127.0.0.1', 'System Install', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NOW());

-- --------------------------------------------------------

--
-- Table structure for table `agenda_edit_request`
--

CREATE TABLE `agenda_edit_request` (
  `id` int NOT NULL,
  `agenda_id` int NOT NULL COMMENT 'FK ke agenda_kelas',
  `kelas_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `catatan` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_by` int NOT NULL COMMENT 'user_id siswa',
  `responded_by` int DEFAULT NULL COMMENT 'admin_id',
  `responded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `agenda_jadwal`
--

CREATE TABLE `agenda_jadwal` (
  `jadwal_id` int NOT NULL,
  `kelas_id` int NOT NULL COMMENT 'FK ke tabel kelas',
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_ke` tinyint NOT NULL COMMENT '1-11',
  `mapel_id` int NOT NULL COMMENT 'FK ke agenda_mapel',
  `created_by` int DEFAULT NULL COMMENT 'user_id siswa yang input',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `agenda_kelas`
--

CREATE TABLE `agenda_kelas` (
  `agenda_id` int NOT NULL,
  `kelas_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_ke` tinyint NOT NULL,
  `mapel_id` int NOT NULL,
  `guru_id` int NOT NULL,
  `kehadiran_guru` enum('Hadir','Tidak Hadir','Tidak Hadir + Tugas') NOT NULL DEFAULT 'Hadir',
  `jumlah_siswa_hadir` int DEFAULT '0',
  `jumlah_siswa_tidak_hadir` int DEFAULT '0',
  `keterangan_materi` text,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `status` enum('aktif','pending_edit','dihapus') NOT NULL DEFAULT 'aktif',
  `created_by` int NOT NULL COMMENT 'user_id siswa',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `agenda_mapel`
--

CREATE TABLE `agenda_mapel` (
  `mapel_id` int NOT NULL,
  `nama_mapel` varchar(150) NOT NULL,
  `kode_mapel` varchar(20) DEFAULT NULL,
  `guru_id` int NOT NULL COMMENT 'FK ke tabel admin (level_id=2)',
  `aktif` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `berkas`
--

CREATE TABLE `berkas` (
  `berkas_id` int NOT NULL,
  `user_id` int NOT NULL,
  `kk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kk_valid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `kk_keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `akte` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `akte_valid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `akte_keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ijazah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ijazah_valid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `ijazah_keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kip_valid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `kip_keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kks_valid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `kks_keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kis` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kis_valid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `kis_keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `validasi_berkas` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validasi_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buku_tamu`
--

CREATE TABLE `buku_tamu` (
  `id` int NOT NULL,
  `guest_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `instansi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telepon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keperluan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `waktu_masuk` time NOT NULL,
  `waktu_keluar` time DEFAULT NULL,
  `status` enum('Aktif','Selesai','Batal') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `instansi_id` int DEFAULT NULL,
  `tujuan_id` int DEFAULT NULL,
  `survey_done` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buku_tamu_log`
--

CREATE TABLE `buku_tamu_log` (
  `id` int NOT NULL,
  `guest_table_id` int NOT NULL,
  `guest_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity` enum('CHECKIN','CHECKOUT','UPDATE','DELETE','SURVEY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buku_tamu_survey`
--

CREATE TABLE `buku_tamu_survey` (
  `id` int NOT NULL,
  `guest_table_id` int NOT NULL,
  `guest_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT '0',
  `pelayanan` tinyint(1) NOT NULL DEFAULT '0',
  `kecepatan` tinyint(1) NOT NULL DEFAULT '0',
  `kenyamanan` tinyint(1) NOT NULL DEFAULT '0',
  `komentar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commit_log`
--

CREATE TABLE `commit_log` (
  `id` int UNSIGNED NOT NULL,
  `commit_hash` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `commit_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `commit_message_bahasa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Terjemahan pesan commit ke bahasa Indonesia umum',
  `author` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `committed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deploy_records`
--

CREATE TABLE `deploy_records` (
  `id` int UNSIGNED NOT NULL,
  `version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `deployed_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `deployed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `e_izin`
--

CREATE TABLE `e_izin` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `jenis_izin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status_izin` enum('Menunggu','Disetujui','Ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu',
  `status_izin_wali` enum('Menunggu','Disetujui','Ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu',
  `alasan_penolakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `alasan_penolakan_wali` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_submitted` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `token_admin` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_security` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_wali` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `konfirmasi` enum('keluar','kembali','pulang') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time_keluar` datetime DEFAULT NULL COMMENT 'Waktu siswa keluar',
  `time_kembali` datetime DEFAULT NULL COMMENT 'Waktu siswa kembali',
  `time_pulang` datetime DEFAULT NULL COMMENT 'Waktu pulang'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hari_libur`
--

CREATE TABLE `hari_libur` (
  `id` int NOT NULL,
  `nama_libur` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `info`
--

CREATE TABLE `info` (
  `id` int UNSIGNED NOT NULL,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `konten` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tipe` enum('popup_admin','popup_siswa','running_text') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'popup_admin',
  `aktif` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_barang`
--

CREATE TABLE `inv_barang` (
  `barang_id` int NOT NULL,
  `kategori_id` int NOT NULL,
  `kode_barang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Kode unik barang',
  `nama_barang` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama barang/sarana',
  `satuan` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unit' COMMENT 'Unit, Buah, Set, Paket',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inv_barang`
--

INSERT INTO `inv_barang` (`barang_id`, `kategori_id`, `kode_barang`, `nama_barang`, `satuan`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 1, 'MBL-001', 'Meja Siswa', 'Unit', 'Meja belajar siswa', '2026-04-17 20:39:56', NULL),
(2, 1, 'MBL-002', 'Kursi Siswa', 'Unit', 'Kursi belajar siswa', '2026-04-17 20:39:56', NULL),
(3, 1, 'MBL-003', 'Meja Guru', 'Unit', 'Meja guru di depan kelas', '2026-04-17 20:39:56', NULL),
(4, 1, 'MBL-004', 'Kursi Guru', 'Unit', 'Kursi guru', '2026-04-17 20:39:56', NULL),
(5, 1, 'MBL-005', 'Lemari Kelas', 'Unit', 'Lemari penyimpanan kelas', '2026-04-17 20:39:56', NULL),
(6, 1, 'MBL-006', 'Papan Tulis', 'Unit', 'Papan tulis (whiteboard/blackboard)', '2026-04-17 20:39:56', NULL),
(7, 1, 'MBL-007', 'Rak Buku', 'Unit', 'Rak buku kelas', '2026-04-17 20:39:56', NULL),
(8, 2, 'ELK-001', 'LCD Proyektor', 'Unit', 'Proyektor kelas', '2026-04-17 20:39:56', NULL),
(9, 2, 'ELK-002', 'Kipas Angin', 'Unit', 'Kipas angin kelas', '2026-04-17 20:39:56', NULL),
(10, 2, 'ELK-003', 'AC', 'Unit', 'Air Conditioner', '2026-04-17 20:39:56', NULL),
(11, 2, 'ELK-004', 'Speaker Aktif', 'Unit', 'Speaker untuk audio', '2026-04-17 20:39:56', NULL),
(12, 2, 'ELK-005', 'Lampu Ruangan', 'Unit', 'Lampu penerangan kelas', '2026-04-17 20:39:56', NULL),
(13, 2, 'ELK-006', 'Stop Kontak', 'Unit', 'Stop kontak dinding', '2026-04-17 20:39:56', NULL),
(14, 3, 'ATK-001', 'Spidol Whiteboard', 'Buah', 'Spidol untuk papan tulis', '2026-04-17 20:39:56', NULL),
(15, 3, 'ATK-002', 'Penghapus Papan Tulis', 'Buah', 'Penghapus whiteboard', '2026-04-17 20:39:56', NULL),
(16, 3, 'ATK-003', 'Penggaris Panjang', 'Buah', 'Penggaris untuk papan tulis', '2026-04-17 20:39:56', NULL),
(17, 4, 'KBR-001', 'Sapu Lantai', 'Buah', 'Sapu untuk membersihkan kelas', '2026-04-17 20:39:56', NULL),
(18, 4, 'KBR-002', 'Pel Lantai', 'Buah', 'Alat pel lantai', '2026-04-17 20:39:56', NULL),
(19, 4, 'KBR-003', 'Tempat Sampah', 'Buah', 'Tempat sampah kelas', '2026-04-17 20:39:56', NULL),
(20, 4, 'KBR-004', 'Kemoceng', 'Buah', 'Kemoceng untuk membersihkan debu', '2026-04-17 20:39:56', NULL),
(21, 4, 'KBR-005', 'Ember', 'Buah', 'Ember untuk keperluan kebersihan', '2026-04-17 20:39:56', NULL),
(22, 5, 'BGN-001', 'Pintu Kelas', 'Unit', 'Pintu masuk kelas', '2026-04-17 20:39:56', NULL),
(23, 5, 'BGN-002', 'Jendela Kelas', 'Unit', 'Jendela ruangan', '2026-04-17 20:39:56', NULL),
(24, 5, 'BGN-003', 'Plafon', 'Unit', 'Langit-langit ruangan', '2026-04-17 20:39:56', NULL),
(25, 5, 'BGN-004', 'Dinding', 'Unit', 'Dinding ruangan', '2026-04-17 20:39:56', NULL),
(26, 5, 'BGN-005', 'Lantai', 'Unit', 'Lantai ruangan', '2026-04-17 20:39:56', NULL),
(27, 5, 'BGN-006', 'Atap', 'Unit', 'Atap bangunan', '2026-04-17 20:39:56', NULL),
(28, 6, 'KMN-001', 'Kunci Pintu', 'Buah', 'Kunci pintu kelas', '2026-04-17 20:39:56', NULL),
(29, 6, 'KMN-002', 'Gembok', 'Buah', 'Gembok kelas', '2026-04-17 20:39:56', NULL),
(30, 7, 'OLR-001', 'Bola Sepak', 'Buah', 'Bola sepak', '2026-04-17 20:39:56', NULL),
(31, 7, 'OLR-002', 'Bola Voli', 'Buah', 'Bola voli', '2026-04-17 20:39:56', NULL),
(32, 7, 'OLR-003', 'Bola Basket', 'Buah', 'Bola basket', '2026-04-17 20:39:56', NULL),
(33, 7, 'OLR-004', 'Net Voli', 'Buah', 'Net untuk voli', '2026-04-17 20:39:56', NULL),
(34, 7, 'OLR-005', 'Matras', 'Buah', 'Matras olahraga', '2026-04-17 20:39:56', NULL),
(35, 8, 'LAB-001', 'Komputer/PC', 'Unit', 'Komputer desktop', '2026-04-17 20:39:56', NULL),
(36, 8, 'LAB-002', 'Monitor', 'Unit', 'Monitor komputer', '2026-04-17 20:39:56', NULL),
(37, 8, 'LAB-003', 'Keyboard', 'Buah', 'Keyboard komputer', '2026-04-17 20:39:56', NULL),
(38, 8, 'LAB-004', 'Mouse', 'Buah', 'Mouse komputer', '2026-04-17 20:39:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inv_kategori`
--

CREATE TABLE `inv_kategori` (
  `kategori_id` int NOT NULL,
  `nama_kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Misal: Mebeler, Elektronik, Alat Tulis, Bangunan, Kebersihan',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inv_kategori`
--

INSERT INTO `inv_kategori` (`kategori_id`, `nama_kategori`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'Mebeler', 'Meja, kursi, lemari, rak buku, papan tulis', '2026-04-17 20:39:56', NULL),
(2, 'Elektronik', 'LCD Proyektor, AC, Kipas Angin, Speaker, TV', '2026-04-17 20:39:56', NULL),
(3, 'Alat Tulis & Perlengkapan', 'Spidol, penghapus, penggaris, dll', '2026-04-17 20:39:56', NULL),
(4, 'Kebersihan', 'Sapu, pel, tempat sampah, kemoceng', '2026-04-17 20:39:56', NULL),
(5, 'Bangunan & Ruangan', 'Pintu, jendela, plafon, dinding, lantai, atap', '2026-04-17 20:39:56', NULL),
(6, 'Keamanan', 'Kunci, gembok, alat pemadam, CCTV', '2026-04-17 20:39:56', NULL),
(7, 'Olahraga', 'Bola, matras, net, raket, dll', '2026-04-17 20:39:56', NULL),
(8, 'Laboratorium', 'Alat lab, bahan praktikum, dll', '2026-04-17 20:39:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inv_kelas`
--

CREATE TABLE `inv_kelas` (
  `inv_id` int NOT NULL,
  `kelas_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '0',
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat','Hilang') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Baik',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama file foto bukti',
  `user_id` int NOT NULL COMMENT 'Siswa yang menginput',
  `tahun_ajaran` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Misal: 2025/2026',
  `tanggal_input` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_laporan`
--

CREATE TABLE `inv_laporan` (
  `laporan_id` int NOT NULL,
  `kelas_id` int NOT NULL,
  `barang_id` int DEFAULT NULL COMMENT 'NULL jika laporan bangunan/umum',
  `jenis_laporan` enum('Kerusakan','Kehilangan','Kebutuhan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prioritas` enum('Rendah','Sedang','Tinggi','Urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sedang',
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Foto bukti',
  `status` enum('Menunggu','Diproses','Selesai','Ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu',
  `catatan_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Catatan tindak lanjut dari admin',
  `user_id` int NOT NULL COMMENT 'Siswa pelapor',
  `processed_by` int DEFAULT NULL COMMENT 'Admin yang memproses',
  `processed_at` datetime DEFAULT NULL,
  `tanggal_laporan` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inv_pinjam`
--

CREATE TABLE `inv_pinjam` (
  `pinjam_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `kelas_id` int DEFAULT NULL COMMENT 'Kelas peminjam',
  `user_id` int DEFAULT NULL COMMENT 'Siswa peminjam (opsional)',
  `nama_peminjam` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama peminjam manual jika bukan siswa',
  `jumlah_pinjam` int NOT NULL DEFAULT '1',
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `tanggal_dikembalikan` date DEFAULT NULL COMMENT 'Tanggal aktual pengembalian',
  `status` enum('Dipinjam','Dikembalikan','Terlambat','Hilang') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Dipinjam',
  `kondisi_awal` enum('Baik','Rusak Ringan','Rusak Berat') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Baik',
  `kondisi_kembali` enum('Baik','Rusak Ringan','Rusak Berat','Hilang') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `admin_id` int NOT NULL COMMENT 'Admin yang mencatat',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `izin`
--

CREATE TABLE `izin` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `jenis_izin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status_izin` enum('Menunggu','Disetujui','Ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu',
  `alasan_penolakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_submitted` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int NOT NULL,
  `hari` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time NOT NULL,
  `status` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id`, `hari`, `waktu_mulai`, `waktu_selesai`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Senin', '07:00:00', '15:00:00', 'Y', '2025-10-07 19:55:00', '2025-10-07 19:55:00'),
(2, 'Selasa', '07:30:00', '14:15:00', 'Y', '2025-10-07 19:55:00', '2026-04-21 23:53:33'),
(3, 'Rabu', '07:30:00', '14:15:00', 'Y', '2025-10-07 19:55:00', '2026-04-21 23:52:48'),
(4, 'Kamis', '07:30:00', '14:15:00', 'Y', '2025-10-07 19:55:00', '2026-04-21 23:53:16'),
(5, 'Jumat', '07:30:00', '14:15:00', 'Y', '2025-10-07 19:55:00', '2026-04-21 23:52:20'),
(6, 'Sabtu', '07:00:00', '12:00:00', 'N', '2025-10-07 19:55:00', '2026-04-21 23:51:36'),
(7, 'Minggu', '00:00:00', '00:00:00', 'N', '2025-10-07 19:56:07', '2025-10-07 20:02:09');

-- --------------------------------------------------------

--
-- Table structure for table `jurusan`
--

CREATE TABLE `jurusan` (
  `jurusan_id` int NOT NULL,
  `kode_jurusan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_jurusan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_jurusan` tinyint(1) NOT NULL DEFAULT '2',
  `kelompok_jurusan_id` int DEFAULT NULL,
  `logo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `kelas_id` int NOT NULL,
  `nama_kelas` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jurusan_id` int DEFAULT NULL,
  `rombongan_belajar_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'From sync_rombongan_belajar',
  `wali_kelas_ptk_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PTK ID of class teacher from sync',
  `wali_kelas_nama` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Name of class teacher from sync',
  `tingkat_pendidikan_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Education level ID',
  `tingkat_pendidikan_str` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Education level description',
  `semester_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Semester ID',
  `jenis_rombel` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Class group type',
  `jenis_rombel_str` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Class group type description',
  `kurikulum_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Curriculum ID',
  `kurikulum_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Curriculum description',
  `id_ruang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Room ID',
  `nama_ruang` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Room name',
  `moving_class` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Moving class flag',
  `sync_jurusan_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Jurusan ID from sync (different from local)',
  `sync_jurusan_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Jurusan name from sync',
  `total_anggota` int DEFAULT '0' COMMENT 'Total students in class',
  `total_pembelajaran` int DEFAULT '0' COMMENT 'Total learning activities',
  `sync_status` enum('active','inactive','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Sync status with Dapodik',
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Last synchronization timestamp',
  `created_from_sync` tinyint(1) DEFAULT '0' COMMENT 'Created from Dapodik sync'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kelulusan_history`
--

CREATE TABLE `kelulusan_history` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `nisn` varchar(32) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `action` enum('OPEN_ENVELOPE','DOWNLOAD_SKL') NOT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `meta_json` text,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kelulusan_ijazah`
--

CREATE TABLE `kelulusan_ijazah` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `nisn` varchar(32) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` datetime NOT NULL,
  `konfirmasi` enum('belum','sesuai','tidak_sesuai') NOT NULL DEFAULT 'belum',
  `konfirmasi_at` datetime DEFAULT NULL,
  `catatan_kesalahan` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kelulusan_settings`
--

CREATE TABLE `kelulusan_settings` (
  `id` tinyint UNSIGNED NOT NULL,
  `is_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `show_skl_to_user` enum('Y','N') NOT NULL DEFAULT 'Y',
  `allow_download_skl` enum('Y','N') NOT NULL DEFAULT 'Y',
  `open_at` datetime DEFAULT NULL,
  `close_at` datetime DEFAULT NULL,
  `countdown_to` datetime DEFAULT NULL,
  `announcement_text` text,
  `updated_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `kelulusan_settings`
--

INSERT INTO `kelulusan_settings` (`id`, `is_open`, `show_skl_to_user`, `allow_download_skl`, `open_at`, `close_at`, `countdown_to`, `announcement_text`, `updated_by`, `updated_at`) VALUES
(1, 'N', 'Y', 'Y', '2027-05-05 21:26:00', '2027-05-31 21:26:00', NULL, NULL, 4, '2026-05-25 21:26:55');

-- --------------------------------------------------------

--
-- Table structure for table `kelulusan_skl`
--

CREATE TABLE `kelulusan_skl` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `is_visible_to_user` enum('Y','N') NOT NULL DEFAULT 'Y',
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kelulusan_status`
--

CREATE TABLE `kelulusan_status` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('BELUM_DIPUTUSKAN','LULUS','LULUS_BERSYARAT','TIDAK_LULUS') NOT NULL DEFAULT 'BELUM_DIPUTUSKAN',
  `catatan` text,
  `diputuskan_oleh` int DEFAULT NULL,
  `diputuskan_pada` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `kriteria_pip`
--

CREATE TABLE `kriteria_pip` (
  `id` int NOT NULL,
  `nama_kriteria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `poin` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kriteria_pip`
--

INSERT INTO `kriteria_pip` (`id`, `nama_kriteria`, `deskripsi`, `poin`) VALUES
(1, 'Pemegang PKH/KPS/KKS', 'Kartu Merah Putih', 100),
(2, 'Siswa Miskin/Rentan Miskin', 'Kurang Mampu', 80),
(3, 'Yatim Piatu/Panti Asuhan/Panti Sosial', 'Ayah Meninggal', 100),
(4, 'Yatim Piatu/Panti Asuhan/Panti Sosial', 'Ibu Meninggal', 100),
(5, 'Memiliki KIP', 'Kartu Indonesia Pintar', 50);

-- --------------------------------------------------------

--
-- Table structure for table `level`
--

CREATE TABLE `level` (
  `level_id` int NOT NULL,
  `level_nama` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` enum('utama','tugas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'utama',
  `need_jurusan` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `level`
--

INSERT INTO `level` (`level_id`, `level_nama`, `tipe`, `need_jurusan`) VALUES
(1, 'Operator Sekolah', 'utama', 0),
(2, 'Guru', 'utama', 0),
(3, 'Tenaga Administrasi', 'utama', 0),
(4, 'Waka Kurikulum', 'tugas', 0),
(5, 'Waka Humas', 'tugas', 0),
(6, 'Waka Sarpras', 'tugas', 0),
(7, 'Waka Kesiswaan', 'tugas', 0),
(8, 'Kepala Program Keahlian', 'tugas', 1),
(9, 'Wali Kelas', 'tugas', 0),
(10, 'Guru Piket', 'tugas', 0),
(11, 'Security', 'tugas', 0),
(12, 'Toolman/Teknisi', 'tugas', 1),
(13, 'Kepala Sekolah', 'tugas', 0);

-- --------------------------------------------------------

--
-- Table structure for table `lokasi`
--

CREATE TABLE `lokasi` (
  `lokasi_id` int NOT NULL,
  `nama_lokasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `radius` int NOT NULL,
  `status` enum('aktif','nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int UNSIGNED NOT NULL DEFAULT '1',
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modul`
--

CREATE TABLE `modul` (
  `modul_id` int NOT NULL,
  `modul_route` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `modul_nama` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modul`
--

INSERT INTO `modul` (`modul_id`, `modul_route`, `modul_nama`) VALUES
(1, 'absensi-izin', 'Absensi Izin'),
(2, 'absensi-lokasi', 'Absensi Lokasi'),
(3, 'absensi-registrasi', 'Absensi Registrasi'),
(4, 'admin', 'Admin'),
(5, 'agenda-jadwal', 'Agenda Jadwal'),
(6, 'agenda-laporan', 'Agenda Laporan'),
(7, 'agenda-ref', 'Agenda Ref'),
(8, 'aktivitas', 'Aktivitas'),
(9, 'berkas', 'Berkas'),
(10, 'buku-tamu', 'Buku Tamu'),
(11, 'cetak-absensi', 'Cetak Absensi'),
(12, 'e-izin', 'E Izin'),
(13, 'edit-identitas', 'Edit Identitas'),
(14, 'guru', 'Guru'),
(15, 'guru-tidak-aktif', 'Guru Tidak Aktif'),
(16, 'hak-akses', 'Hak Akses'),
(17, 'history-pip', 'History Pip'),
(18, 'home', 'Home'),
(19, 'info', 'Info'),
(20, 'inv-kelas', 'Inv Kelas'),
(21, 'inv-master', 'Inv Master'),
(22, 'inv-pinjam', 'Inv Pinjam'),
(23, 'inv-report', 'Inv Report'),
(24, 'jadwal', 'Jadwal'),
(25, 'jurusan', 'Jurusan'),
(26, 'kelas', 'Kelas'),
(27, 'kriteria-pip', 'Kriteria Pip'),
(28, 'laporan-absensi', 'Laporan Absensi'),
(29, 'laporan-absensi-kelas', 'Laporan Absensi Kelas'),
(30, 'laporan-absensi-siswa', 'Laporan Absensi Siswa'),
(31, 'libur', 'Libur'),
(32, 'lisensi', 'Lisensi'),
(33, 'menu-siswa', 'Menu Siswa'),
(34, 'pembaharuan', 'Pembaharuan'),
(35, 'pembelajaran', 'Pembelajaran'),
(36, 'pengaturan', 'Pengaturan'),
(37, 'peserta-pkl', 'Peserta Pkl'),
(38, 'poin', 'Poin'),
(39, 'poin-panggil', 'Poin Panggil'),
(40, 'poin-sanggah', 'Poin Sanggah'),
(41, 'poin-tatib', 'Poin Tatib'),
(42, 'portal-gtk', 'Portal Gtk'),
(43, 'privasi-kebijakan', 'Privasi Kebijakan'),
(44, 'profile', 'Profile'),
(45, 'skl-history', 'Skl History'),
(46, 'skl-ijazah', 'Skl Ijazah'),
(47, 'skl-import', 'Skl Import'),
(48, 'skl-settings', 'Skl Settings'),
(49, 'skl-user', 'Skl User'),
(50, 'surat', 'Surat'),
(51, 'surat-arsip', 'Surat Arsip'),
(52, 'surat-index', 'Surat Index'),
(53, 'surat-keluar', 'Surat Keluar'),
(54, 'surat-masuk', 'Surat Masuk'),
(55, 'surat-setting', 'Surat Setting'),
(56, 'surat-template', 'Surat Template'),
(57, 'sync', 'Sync'),
(58, 'tamu-referensi', 'Tamu Referensi'),
(59, 'tentang', 'Tentang'),
(60, 'user', 'User'),
(61, 'user-tidak-aktif', 'User Tidak Aktif'),
(62, 'usulan-pip-diterima', 'Usulan Pip Diterima'),
(63, 'usulan-pip-ranking', 'Usulan Pip Ranking'),
(64, 'usulan-pip-semua', 'Usulan Pip Semua');

-- --------------------------------------------------------

--
-- Table structure for table `perubahan`
--

CREATE TABLE `perubahan` (
  `id` int NOT NULL,
  `user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_submitted` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_pengajuan` enum('Berhasil Dikirim','Dalam Proses','Disetujui','Ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Berhasil Dikirim',
  `alasan_penolakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_processed` datetime DEFAULT NULL,
  `processed_by` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ringkasan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pkl_peserta`
--

CREATE TABLE `pkl_peserta` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `nisn` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurusan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_kirim` enum('Pending','Terkirim','Gagal') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `response_msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poin_ayat`
--

CREATE TABLE `poin_ayat` (
  `ayat_id` int NOT NULL,
  `pasal_id` int NOT NULL,
  `kode_ayat` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Misal: Ayat 1, Ayat 2',
  `jenis_pelanggaran` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama pelanggaran',
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Penjelasan detail',
  `kategori` enum('Ringan','Sedang','Berat','Sangat Berat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ringan',
  `poin` int NOT NULL DEFAULT '0' COMMENT 'Jumlah poin pelanggaran',
  `urutan` int NOT NULL DEFAULT '0',
  `aktif` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poin_ayat`
--

INSERT INTO `poin_ayat` (`ayat_id`, `pasal_id`, `kode_ayat`, `jenis_pelanggaran`, `deskripsi`, `kategori`, `poin`, `urutan`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 1, 'Ayat 1', 'Terlambat masuk sekolah', NULL, 'Ringan', 10, 1, 'Y', '2026-04-20 01:12:43', NULL),
(2, 1, 'Ayat 2', 'Bolos/meninggalkan sekolah tanpa izin', NULL, 'Sedang', 25, 2, 'Y', '2026-04-20 01:12:43', NULL),
(3, 1, 'Ayat 3', 'Tidak masuk tanpa keterangan (alpha)', NULL, 'Sedang', 20, 3, 'Y', '2026-04-20 01:12:43', NULL),
(4, 1, 'Ayat 4', 'Tidak mengikuti upacara/apel', NULL, 'Ringan', 15, 4, 'Y', '2026-04-20 01:12:43', NULL),
(5, 1, 'Ayat 5', 'Tidur di kelas saat KBM', NULL, 'Ringan', 10, 5, 'Y', '2026-04-20 01:12:43', NULL),
(6, 2, 'Ayat 1', 'Tidak memakai seragam sesuai ketentuan', NULL, 'Ringan', 10, 1, 'Y', '2026-04-20 01:12:43', NULL),
(7, 2, 'Ayat 2', 'Rambut tidak rapi/diwarnai (putra)', NULL, 'Ringan', 15, 2, 'Y', '2026-04-20 01:12:43', NULL),
(8, 2, 'Ayat 3', 'Menggunakan aksesoris berlebihan', NULL, 'Ringan', 10, 3, 'Y', '2026-04-20 01:12:43', NULL),
(9, 2, 'Ayat 4', 'Memakai make-up berlebihan di sekolah', NULL, 'Ringan', 10, 4, 'Y', '2026-04-20 01:12:43', NULL),
(10, 3, 'Ayat 1', 'Berkata kasar/tidak sopan kepada guru', NULL, 'Sedang', 30, 1, 'Y', '2026-04-20 01:12:43', NULL),
(11, 3, 'Ayat 2', 'Membully/mengintimidasi siswa lain', NULL, 'Berat', 50, 2, 'Y', '2026-04-20 01:12:43', NULL),
(12, 3, 'Ayat 3', 'Membawa/menggunakan HP saat KBM tanpa izin', NULL, 'Ringan', 15, 3, 'Y', '2026-04-20 01:12:43', NULL),
(13, 3, 'Ayat 4', 'Merokok di lingkungan sekolah', NULL, 'Berat', 60, 4, 'Y', '2026-04-20 01:12:43', NULL),
(14, 3, 'Ayat 5', 'Pacaran di lingkungan sekolah', NULL, 'Sedang', 30, 5, 'Y', '2026-04-20 01:12:43', NULL),
(15, 4, 'Ayat 1', 'Merusak fasilitas sekolah', NULL, 'Berat', 50, 1, 'Y', '2026-04-20 01:12:43', NULL),
(16, 4, 'Ayat 2', 'Membuang sampah sembarangan', NULL, 'Ringan', 10, 2, 'Y', '2026-04-20 01:12:43', NULL),
(17, 4, 'Ayat 3', 'Membawa kendaraan tanpa SIM/tidak parkir tertib', NULL, 'Ringan', 15, 3, 'Y', '2026-04-20 01:12:43', NULL),
(18, 4, 'Ayat 4', 'Corat-coret dinding/meja/fasilitas sekolah', NULL, 'Sedang', 25, 4, 'Y', '2026-04-20 01:12:43', NULL),
(19, 5, 'Ayat 1', 'Membawa/mengonsumsi narkoba', NULL, 'Sangat Berat', 100, 1, 'Y', '2026-04-20 01:12:43', NULL),
(20, 5, 'Ayat 2', 'Membawa/mengonsumsi minuman keras', NULL, 'Sangat Berat', 100, 2, 'Y', '2026-04-20 01:12:43', NULL),
(21, 5, 'Ayat 3', 'Menyalahgunakan obat-obatan terlarang', NULL, 'Sangat Berat', 100, 3, 'Y', '2026-04-20 01:12:43', NULL),
(22, 6, 'Ayat 1', 'Berkelahi/tawuran', NULL, 'Sangat Berat', 100, 1, 'Y', '2026-04-20 01:12:43', NULL),
(23, 6, 'Ayat 2', 'Membawa senjata tajam/berbahaya', NULL, 'Sangat Berat', 100, 2, 'Y', '2026-04-20 01:12:43', NULL),
(24, 6, 'Ayat 3', 'Pencurian di lingkungan sekolah', NULL, 'Berat', 75, 3, 'Y', '2026-04-20 01:12:43', NULL),
(25, 6, 'Ayat 4', 'Pelecehan/kekerasan seksual', NULL, 'Sangat Berat', 100, 4, 'Y', '2026-04-20 01:12:43', NULL),
(26, 7, 'Ayat 1', 'Mencontek saat ujian', NULL, 'Sedang', 30, 1, 'Y', '2026-04-20 01:12:43', NULL),
(27, 7, 'Ayat 2', 'Memalsukan tanda tangan/surat', NULL, 'Berat', 50, 2, 'Y', '2026-04-20 01:12:43', NULL),
(28, 7, 'Ayat 3', 'Plagiarisme tugas', NULL, 'Sedang', 25, 3, 'Y', '2026-04-20 01:12:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `poin_panggil`
--

CREATE TABLE `poin_panggil` (
  `panggil_id` int NOT NULL,
  `user_id` int NOT NULL,
  `kelas_id` int NOT NULL,
  `alasan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Alasan pemanggilan',
  `total_poin` int NOT NULL DEFAULT '0',
  `jenis_panggilan` enum('Pemanggilan Orang Tua','Surat Peringatan','Skorsing','Dikeluarkan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pemanggilan Orang Tua',
  `tanggal_panggil` date NOT NULL,
  `tanggal_hadir` date DEFAULT NULL COMMENT 'Tanggal ortu hadir',
  `hasil_pertemuan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('Menunggu','Hadir','Tidak Hadir','Selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `tindakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Tindakan yang disepakati',
  `admin_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poin_pasal`
--

CREATE TABLE `poin_pasal` (
  `pasal_id` int NOT NULL,
  `kode_pasal` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Misal: Pasal 1, Pasal 2',
  `nama_pasal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Judul/Kategori pasal',
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `urutan` int NOT NULL DEFAULT '0',
  `aktif` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `poin_pasal`
--

INSERT INTO `poin_pasal` (`pasal_id`, `kode_pasal`, `nama_pasal`, `deskripsi`, `urutan`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 'Pasal 1', 'Kedisiplinan & Kehadiran', NULL, 1, 'Y', '2026-04-20 01:12:43', NULL),
(2, 'Pasal 2', 'Penampilan & Seragam', NULL, 2, 'Y', '2026-04-20 01:12:43', NULL),
(3, 'Pasal 3', 'Etika & Sopan Santun', NULL, 3, 'Y', '2026-04-20 01:12:43', NULL),
(4, 'Pasal 4', 'Ketertiban Lingkungan Sekolah', NULL, 4, 'Y', '2026-04-20 01:12:43', NULL),
(5, 'Pasal 5', 'Narkoba, Miras & Zat Berbahaya', NULL, 5, 'Y', '2026-04-20 01:12:43', NULL),
(6, 'Pasal 6', 'Kekerasan & Tindak Kriminal', NULL, 6, 'Y', '2026-04-20 01:12:43', NULL),
(7, 'Pasal 7', 'Pelanggaran Akademik', NULL, 7, 'Y', '2026-04-20 01:12:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `poin_pelanggaran`
--

CREATE TABLE `poin_pelanggaran` (
  `pelanggaran_id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'FK ke user (siswa)',
  `kelas_id` int NOT NULL,
  `ayat_id` int NOT NULL COMMENT 'FK ke poin_ayat',
  `semester_id` int DEFAULT NULL,
  `tanggal_kejadian` date NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan tambahan',
  `bukti` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Path file bukti',
  `poin_diberikan` int NOT NULL DEFAULT '0' COMMENT 'Bisa override dari master',
  `is_pengulangan` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT 'Apakah pelanggaran diulang',
  `jumlah_pengulangan` int NOT NULL DEFAULT '0' COMMENT 'Berapa kali sudah melanggar ayat yang sama',
  `status` enum('Aktif','Disanggah','Dikurangi','Dihapus') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Aktif',
  `perlu_tindak_lanjut` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `tindak_lanjut` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan tindak lanjut',
  `admin_id` int NOT NULL COMMENT 'Dicatat oleh admin',
  `notif_dibaca` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N' COMMENT 'Siswa sudah baca notifikasi',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poin_sanggah`
--

CREATE TABLE `poin_sanggah` (
  `sanggah_id` int NOT NULL,
  `pelanggaran_id` int NOT NULL COMMENT 'FK ke poin_pelanggaran',
  `user_id` int NOT NULL,
  `jenis_sanggah` enum('Penghapusan','Pengurangan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pengurangan',
  `alasan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Alasan/penjelasan siswa',
  `bukti_sanggah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Bukti pendukung',
  `kesepakatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Tugas/syarat yang harus dipenuhi',
  `poin_dikurangi` int NOT NULL DEFAULT '0',
  `status` enum('Menunggu','Disetujui','Ditolak','Selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Menunggu',
  `catatan_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `admin_id` int DEFAULT NULL COMMENT 'Admin yang memproses',
  `tanggal_pengajuan` date NOT NULL,
  `tanggal_proses` datetime DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL COMMENT 'Siswa selesai memenuhi syarat',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poin_semester`
--

CREATE TABLE `poin_semester` (
  `semester_id` int NOT NULL,
  `nama_semester` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Misal: Ganjil 2025/2026',
  `tahun_ajaran` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Misal: 2025/2026',
  `jenis` enum('Ganjil','Genap') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `is_aktif` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portal_access_log`
--

CREATE TABLE `portal_access_log` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `app_name` varchar(255) NOT NULL,
  `app_url` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `access_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `portal_apps`
--

CREATE TABLE `portal_apps` (
  `app_id` int NOT NULL,
  `app_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-globe',
  `custom_icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_category` enum('education','government','productivity','communication','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'other',
  `is_active` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Y',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `portal_apps`
--

INSERT INTO `portal_apps` (`app_id`, `app_name`, `app_url`, `app_icon`, `custom_icon`, `app_description`, `app_category`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(30, 'Dapodik', 'https://dapo.smakpal.sch.id', 'fas fa-globe', 'icon_1779885064.png', 'Data Pokok Pendidikan (Dapodik)', 'education', 'Y', 1, '2026-01-22 11:32:03', '2026-05-27 12:55:17'),
(31, 'PTK Datadik', 'https://ptk.datadik.kemendikdasmen.go.id', 'fas fa-globe', 'icon_1769143615.png', 'PTK Datadik (Pendidik dan Tenaga Kependidikan Data Pendidikan)', 'education', 'Y', 2, '2026-01-23 02:21:01', '2026-01-23 05:23:28'),
(32, 'eRaporSMK', 'https://rapor.smakpal.sch.id/', 'fas fa-globe', 'icon_1769144893.png', 'e-Rapor SMK berbasis web resmi dari Direktorat SMK Kemendikdasmen.', 'education', 'Y', 3, '2026-01-23 04:44:39', '2026-01-23 05:56:32'),
(33, 'Belajar.ID', 'https://belajar.id', 'fas fa-globe', 'icon_1769145064.png', 'Portal Akun Belajar.id', 'education', 'Y', 12, '2026-01-23 05:11:04', '2026-01-23 05:11:04'),
(34, 'CORETAX', 'https://coretaxdjp.pajak.go.id', 'fas fa-globe', 'icon_1769145147.png', 'Coretax (atau Coretax DJP) adalah sistem inti administrasi perpajakan', 'government', 'Y', 13, '2026-01-23 05:12:27', '2026-01-23 05:12:27'),
(35, 'e-Fungsional', 'https://siap.jabarprov.go.id/silajang-autologin', 'fas fa-globe', 'icon_1769145301.png', 'Aplikasi berbasis web e-Fungsional Jabar', 'government', 'Y', 16, '2026-01-23 05:15:01', '2026-01-23 05:35:48'),
(36, 'Info GTK', 'https://info.gtk.kemendikdasmen.go.id/', 'fas fa-globe', 'icon_1769145536.png', 'Info GTK adalah portal informasi elektronik resmi dari Kemendikbudristek', 'government', 'Y', 3, '2026-01-23 05:18:20', '2026-01-23 05:18:56'),
(37, 'e-Pangkat', 'https://siap.jabarprov.go.id/kepin/login', 'fas fa-globe', 'icon_1769146538.png', 'e-Pangkat Jabar', 'government', 'Y', 15, '2026-01-23 05:35:38', '2026-01-23 05:35:38'),
(38, 'JSA', 'https://smartasn.jabarprov.go.id', 'fas fa-camera', NULL, 'Absensi Digital ASN', 'government', 'Y', 17, '2026-01-23 05:37:48', '2026-01-23 05:37:48'),
(39, 'eMail Jabar', 'https://mail.jabarprov.go.id', 'fas fa-envelope', NULL, 'eMail ASN Jabar', 'government', 'Y', 18, '2026-01-23 05:40:19', '2026-01-23 05:40:19'),
(40, 'Ruang GTK', 'https://guru.kemendikdasmen.go.id', 'fas fa-globe', 'icon_1769146958.png', 'Ruang Guru dan Tenaga Kependidikan', 'education', 'Y', 19, '2026-01-23 05:42:38', '2026-01-23 05:42:38'),
(41, 'SIA ASN', 'https://asndigital.bkn.go.id', 'fas fa-globe', 'icon_1769147083.png', 'SIA ASN atau MyASN adalah Aplikasi BKN.', 'government', 'Y', 20, '2026-01-23 05:44:43', '2026-01-23 05:44:43'),
(42, 'SiapJabar', 'https://siap.jabarprov.go.id', 'fas fa-globe', 'icon_1769147320.png', 'SIAp Jabar (Sistem Informasi Aparatur Jawa Barat)', 'government', 'Y', 25, '2026-01-23 05:46:21', '2026-01-23 05:48:40'),
(43, 'SIDEBAR', 'https://sidebar.jabarprov.go.id', 'fas fa-globe', 'icon_1769147306.png', 'SIDEBAR Jabar adalah Sistem Informasi Dokumen Elektronik Jawa Barat.', 'government', 'Y', 24, '2026-01-23 05:47:13', '2026-01-23 05:48:26'),
(44, 'SIMPKB', 'https://paspor-gtk.simpkb.id', 'fas fa-globe', 'icon_1769147526.png', 'SIMPKB adalah Sistem Informasi Manajemen Pengembangan Keprofesian Berkelanjutan.', 'education', 'Y', 26, '2026-01-23 05:52:06', '2026-01-23 05:52:06'),
(45, 'SSO Jabar', 'https://dashboard.jabarprov.go.id', 'fas fa-globe', 'icon_1769147750.png', 'SSO Jabar (Single Sign-On Jawa Barat)', 'government', 'Y', 27, '2026-01-23 05:55:50', '2026-01-23 05:55:50'),
(46, 'TRK', 'https://kinerja.jabarprov.go.id', 'fas fa-globe', 'icon_1769147972.png', 'TRK Jabar adalah singkatan dari Tunjangan Remunerasi Kinerja, sebuah platform digital', 'government', 'Y', 29, '2026-01-23 05:57:52', '2026-01-23 05:59:32'),
(47, 'G-Mail', 'https://mail.google.com/', 'fas fa-globe', 'icon_1769148207.png', 'Gmail (Google Mail)', 'productivity', 'Y', 31, '2026-01-23 06:02:34', '2026-01-23 06:03:27');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int UNSIGNED NOT NULL,
  `action_ip_hash` varchar(64) NOT NULL,
  `action` varchar(64) NOT NULL,
  `expires_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` int NOT NULL,
  `level_id` int NOT NULL,
  `modul_id` int NOT NULL,
  `lihat` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modifikasi` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hapus` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `level_id`, `modul_id`, `lihat`, `modifikasi`, `hapus`) VALUES
(1014, 1, 1, 'Y', 'Y', 'Y'),
(1015, 1, 2, 'Y', 'Y', 'Y'),
(1016, 1, 3, 'Y', 'Y', 'Y'),
(1017, 1, 4, 'Y', 'Y', 'Y'),
(1018, 1, 5, 'Y', 'Y', 'Y'),
(1019, 1, 6, 'Y', 'Y', 'Y'),
(1020, 1, 7, 'Y', 'Y', 'Y'),
(1021, 1, 8, 'Y', 'Y', 'Y'),
(1022, 1, 9, 'Y', 'Y', 'Y'),
(1023, 1, 10, 'Y', 'Y', 'Y'),
(1024, 1, 11, 'Y', 'Y', 'Y'),
(1025, 1, 12, 'Y', 'Y', 'Y'),
(1026, 1, 13, 'Y', 'Y', 'Y'),
(1027, 1, 14, 'Y', 'Y', 'Y'),
(1028, 1, 15, 'Y', 'Y', 'Y'),
(1029, 1, 16, 'Y', 'Y', 'Y'),
(1030, 1, 17, 'Y', 'Y', 'Y'),
(1031, 1, 18, 'Y', 'Y', 'Y'),
(1032, 1, 19, 'Y', 'Y', 'Y'),
(1033, 1, 20, 'Y', 'Y', 'Y'),
(1034, 1, 21, 'Y', 'Y', 'Y'),
(1035, 1, 22, 'Y', 'Y', 'Y'),
(1036, 1, 23, 'Y', 'Y', 'Y'),
(1037, 1, 24, 'Y', 'Y', 'Y'),
(1038, 1, 25, 'Y', 'Y', 'Y'),
(1039, 1, 26, 'Y', 'Y', 'Y'),
(1040, 1, 27, 'Y', 'Y', 'Y'),
(1041, 1, 28, 'Y', 'Y', 'Y'),
(1042, 1, 29, 'Y', 'Y', 'Y'),
(1043, 1, 30, 'Y', 'Y', 'Y'),
(1044, 1, 31, 'Y', 'Y', 'Y'),
(1045, 1, 32, 'Y', 'Y', 'Y'),
(1046, 1, 33, 'Y', 'Y', 'Y'),
(1047, 1, 34, 'Y', 'Y', 'Y'),
(1048, 1, 35, 'Y', 'Y', 'Y'),
(1049, 1, 36, 'Y', 'Y', 'Y'),
(1050, 1, 37, 'Y', 'Y', 'Y'),
(1051, 1, 38, 'Y', 'Y', 'Y'),
(1052, 1, 39, 'Y', 'Y', 'Y'),
(1053, 1, 40, 'Y', 'Y', 'Y'),
(1054, 1, 41, 'Y', 'Y', 'Y'),
(1055, 1, 42, 'Y', 'Y', 'Y'),
(1056, 1, 43, 'Y', 'Y', 'Y'),
(1057, 1, 44, 'Y', 'Y', 'Y'),
(1058, 1, 45, 'Y', 'Y', 'Y'),
(1059, 1, 46, 'Y', 'Y', 'Y'),
(1060, 1, 47, 'Y', 'Y', 'Y'),
(1061, 1, 48, 'Y', 'Y', 'Y'),
(1062, 1, 49, 'Y', 'Y', 'Y'),
(1063, 1, 50, 'Y', 'Y', 'Y'),
(1064, 1, 51, 'Y', 'Y', 'Y'),
(1065, 1, 52, 'Y', 'Y', 'Y'),
(1066, 1, 53, 'Y', 'Y', 'Y'),
(1067, 1, 54, 'Y', 'Y', 'Y'),
(1068, 1, 55, 'Y', 'Y', 'Y'),
(1069, 1, 56, 'Y', 'Y', 'Y'),
(1070, 1, 57, 'Y', 'Y', 'Y'),
(1071, 1, 58, 'Y', 'Y', 'Y'),
(1072, 1, 59, 'Y', 'Y', 'Y'),
(1073, 1, 60, 'Y', 'Y', 'Y'),
(1074, 1, 61, 'Y', 'Y', 'Y'),
(1075, 1, 62, 'Y', 'Y', 'Y'),
(1076, 1, 63, 'Y', 'Y', 'Y'),
(1077, 1, 64, 'Y', 'Y', 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `security_log`
--

CREATE TABLE `security_log` (
  `id` int NOT NULL,
  `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `site_id` int NOT NULL,
  `site_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_phone` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_owner` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_logo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_logo2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_favicon` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_kop` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_url` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_email` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmail_host` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmail_username` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmail_password` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmail_port` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gmail_active` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `google_client_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `google_client_secret` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `google_client_active` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `maintenance_status` enum('open','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `sekolah_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_status` enum('unverified','active','suspended','expired','invalid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unverified',
  `license_school_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_npsn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expired_at` date DEFAULT NULL,
  `github_last_sha` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `github_last_check` datetime DEFAULT NULL,
  `api_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `github_update_available` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'N',
  `github_latest_sha` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `github_latest_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `github_latest_message` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'v20252.1',
  `deploy_count` int UNSIGNED NOT NULL DEFAULT '0',
  `last_deploy_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_deploy_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deploy_note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `last_deploy_at` datetime DEFAULT NULL,
  `last_deploy_commit` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nss` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `npsn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bentuk_pendidikan_id` int DEFAULT NULL,
  `bentuk_pendidikan_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_sekolah` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_sekolah_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_jalan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rt` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rw` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dusun` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desa_kelurahan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_wilayah` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_pos` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lintang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bujur` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_telepon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_fax` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_sks` tinyint(1) DEFAULT NULL,
  `kecamatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kabupaten_kota` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provinsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `installer_completed` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `statistik`
--

CREATE TABLE `statistik` (
  `statistik_id` int NOT NULL,
  `user_id` int NOT NULL,
  `jumlah` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_menu`
--

CREATE TABLE `student_menu` (
  `id` int UNSIGNED NOT NULL,
  `slug` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktif` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y',
  `position` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_menu`
--

INSERT INTO `student_menu` (`id`, `slug`, `label`, `aktif`, `position`, `created_at`, `updated_at`) VALUES
(1, 'home', 'Beranda', 'Y', 10, '2025-11-21 09:27:49', '2025-11-21 10:20:37'),
(2, 'nilai', 'Nilai', 'N', 20, '2025-11-21 09:27:49', '2025-11-21 10:56:49'),
(3, 'jadwal', 'Jadwal', 'N', 30, '2025-11-21 09:27:49', '2025-11-21 10:56:51'),
(4, 'absensi', 'Absensi', 'Y', 40, '2025-11-21 09:27:49', '2026-04-22 00:00:57'),
(5, 'berkas', 'Berkas', 'Y', 50, '2025-11-21 09:27:49', '2026-04-07 14:51:59'),
(6, 'applain', 'Lainnya', 'Y', 70, '2025-11-21 10:19:08', '2025-11-21 10:19:08'),
(7, 'cek-data-kelas', 'Cek Data Kelas', 'Y', 90, '2025-11-21 10:19:09', '2025-11-21 10:19:09'),
(8, 'e-izin', 'e-Izin', 'Y', 100, '2025-11-21 10:19:09', '2026-04-22 00:02:09'),
(9, 'edit-identitas', 'Edit Identitas', 'Y', 110, '2025-11-21 10:19:09', '2026-01-30 01:24:20'),
(10, 'ekpd', 'e-KPD', 'Y', 120, '2025-11-21 10:19:09', '2025-11-21 10:19:09'),
(11, 'faq', 'FAQ', 'Y', 130, '2025-11-21 10:19:09', '2025-11-21 10:19:09'),
(12, 'identitas', 'Identitas', 'Y', 140, '2025-11-21 10:19:09', '2026-01-21 06:56:27'),
(13, 'izin', 'Izin', 'Y', 150, '2025-11-21 10:19:09', '2026-04-22 00:02:09'),
(14, 'kelas-q', 'Kelas Q', 'Y', 160, '2025-11-21 10:19:09', '2025-11-21 10:19:09'),
(15, 'tata-tertib', 'Tata Tertib', 'Y', 170, '2025-11-21 10:19:09', '2025-11-21 10:19:09'),
(16, 'usulan-pip', 'Usulan PIP', 'Y', 180, '2025-11-21 10:19:09', '2026-05-29 02:05:18'),
(17, 'profile', 'Profil', 'Y', 1, '2026-02-02 11:56:56', '2026-02-02 12:20:47'),
(18, 'invetaris-kelas', 'Inventaris Kelas', 'Y', 20, '2026-04-17 20:39:56', '2026-04-17 20:39:56'),
(19, 'poin', 'Poin Pelanggaran', 'Y', 99, '2026-04-20 01:12:43', '2026-04-20 01:12:43'),
(20, 'catatan-pelanggaran', 'Poin', 'Y', 70, '2026-04-22 00:02:18', '2026-04-22 00:02:18'),
(21, 'absensi-kelas', 'Absensi Kelas', 'Y', 190, '2026-04-22 00:02:18', '2026-04-22 00:02:18'),
(22, 'agenda-kelas', 'Agenda Kelas', 'Y', 200, '2026-04-22 00:02:18', '2026-04-22 00:02:18'),
(23, 'e-ijazah', 'E-Ijazah', 'Y', 210, '2026-05-25 14:21:59', '2026-05-25 14:21:59');

-- --------------------------------------------------------

--
-- Table structure for table `surat_index`
--

CREATE TABLE `surat_index` (
  `id` int NOT NULL,
  `indeks` varchar(100) NOT NULL,
  `perihal` text NOT NULL,
  `kategori` varchar(100) NOT NULL DEFAULT '',
  `jenis_surat` varchar(50) NOT NULL DEFAULT 'Surat Keluar',
  `contoh_nomor` varchar(200) NOT NULL DEFAULT '',
  `format_template` longtext COMMENT 'Template Word disimpan sebagai base64 atau path file',
  `template_fields` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surat_index`
--

INSERT INTO `surat_index` (`id`, `indeks`, `perihal`, `kategori`, `jenis_surat`, `contoh_nomor`, `format_template`, `template_fields`, `created_at`, `updated_at`) VALUES
(11, 'KPG.01', 'Formasi Pegawai (Usulan dari Unit Kerja/SKPD)', 'Kepegawaian', 'Surat Usulan', '0001/KPG.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(12, 'KPG.01.01', 'Analisa Jabatan', 'Kepegawaian', 'Dokumen Administrasi', '0012/KPG.01.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(13, 'KPG.01.02', 'Beban Kerja', 'Kepegawaian', 'Dokumen Administrasi', '0013/KPG.01.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(14, 'KPG.02', 'Pengadaan Pegawai', 'Kepegawaian', 'Surat Usulan', '0014/KPG.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(15, 'KPG.02.04.02', 'Ijazah', 'Kepegawaian', 'Dokumen Administrasi', '0015/KPG.02.04.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(16, 'KPG.03', 'Pembinaan Pegawai', 'Kepegawaian', 'Surat Pembinaan', '0016/KPG.03-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(17, 'KPG.03.01.01', 'SK / Surat Izin', 'Kepegawaian', 'Surat Keputusan (SK)', '0017/KPG.03.01.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(18, 'KPG.03.03', 'Daftar Usul Penetapan Angka Kredit', 'Kepegawaian', 'Surat Usulan', '0018/KPG.03.03-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(19, 'KPG.03.04', 'Disiplin Pegawai', 'Kepegawaian', 'Surat Teguran', '0019/KPG.03.04-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(20, 'KPG.03.06', 'Penghargaan dan Tanda Jasa', 'Kepegawaian', 'Piagam / Sertifikat', '0020/KPG.03.06-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(21, 'KPG.04', 'Mutasi Pegawai', 'Kepegawaian', 'Surat Mutasi', '0021/KPG.04-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(22, 'KPG.04.01', 'Alih Status / Pindah Instansi / Mutasi', 'Kepegawaian', 'Surat Mutasi', '0022/KPG.04.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(23, 'KPG.05.01', 'Surat Izin Pernikahan / Perceraian', 'Kepegawaian', 'Surat Izin', '0023/KPG.05.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(24, 'KPG.05.03', 'Surat Penolakan Izin Pernikahan / Perceraian', 'Kepegawaian', 'Surat Penolakan', '0024/KPG.05.03-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(25, 'KPG.06', 'Usulan Kenaikan Pangkat / Golongan / Jabatan', 'Kepegawaian', 'Surat Usulan', '0025/KPG.06-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(26, 'KPG.11', 'Administrasi Pegawai', 'Kepegawaian', 'Dokumen Administrasi', '0026/KPG.11-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(27, 'KPG.11.01', 'Surat Perintah Dinas / Surat Tugas', 'Kepegawaian', 'Surat Tugas', '0027/KPG.11.01-SMKN1PGL', '1NlfuszmjNCfDrMZu1zkOha8R616qlStE', '[\n    {\n        \"name\": \"dasar\",\n        \"label\": \"Dasar\",\n        \"type\": \"text\"\n    },\n    {\n        \"name\": \"jabatan\",\n        \"label\": \"Jabatan\",\n        \"type\": \"text\"\n    },\n    {\n        \"name\": \"nama\",\n        \"label\": \"Nama\",\n        \"type\": \"text\"\n    },\n    {\n        \"name\": \"nip\",\n        \"label\": \"Nip\",\n        \"type\": \"text\"\n    },\n    {\n        \"name\": \"nomor\",\n        \"label\": \"Nomor\",\n        \"type\": \"text\"\n    },\n    {\n        \"name\": \"pangkat\",\n        \"label\": \"Pangkat\",\n        \"type\": \"text\"\n    }\n]', '2026-06-27 01:43:39', '2026-06-29 11:56:24'),
(28, 'KPG.11.02', 'Cuti Besar', 'Kepegawaian', 'Surat Cuti', '0028/KPG.11.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(29, 'KPG.11.03', 'Cuti Sakit / Bersalin / Tahunan', 'Kepegawaian', 'Surat Cuti', '0029/KPG.11.03-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(30, 'KPG.11.04', 'Cuti Alasan Penting', 'Kepegawaian', 'Surat Cuti', '0030/KPG.11.04-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(31, 'KPG.11.05', 'CLTN', 'Kepegawaian', 'Surat Cuti', '0031/KPG.11.05-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(32, 'KPG.12', 'Dokumentasi Identitas Pegawai', 'Kepegawaian', 'Dokumen Administrasi', '0032/KPG.12-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(33, 'KPG.12.01', 'Usulan Penetapan Karpeg/KPE/Karis/Karsu', 'Kepegawaian', 'Surat Usulan', '0033/KPG.12.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(34, 'KPG.14', 'Berkas Kenaikan Gaji Berkala', 'Kepegawaian', 'Dokumen Administrasi', '0034/KPG.14-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(35, 'KPG.15.02', 'Layanan Asuransi Pegawai/BPJS', 'Kepegawaian', 'Surat Pengantar', '0035/KPG.15.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(36, 'KPG.15.08', 'Pemberian Piagam Penghargaan/Sertifikat', 'Kepegawaian', 'Piagam / Sertifikat', '0036/KPG.15.08-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(37, 'KPG.16.01', 'Usulan Pemberhentian dan Penetapan Pensiun', 'Kepegawaian', 'Surat Usulan', '0037/KPG.16.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(38, 'KU.01', 'Rencana Anggaran Pendapatan dan Belanja', 'Keuangan', 'Dokumen Perencanaan', '0038/KU.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(39, 'KU.01.02', 'Penyusunan RKA-SKPD', 'Keuangan', 'Dokumen Perencanaan', '0039/KU.01.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(40, 'KU.02', 'Penyusunan Anggaran', 'Keuangan', 'Dokumen Perencanaan', '0040/KU.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(41, 'KU.03', 'Pelaksanaan Anggaran', 'Keuangan', 'Dokumen Administrasi', '0041/KU.03-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(42, 'KU.03.01', 'Surat Penyedia Dana (SPP/SPM/SP2D)', 'Keuangan', 'Dokumen Keuangan', '0042/KU.03.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(43, 'KU.03.03.05', 'Dana Alokasi Khusus (DAK)', 'Keuangan', 'Dokumen Keuangan', '0043/KU.03.03.05-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(44, 'KU.03.10.01', 'Belanja Pegawai', 'Keuangan', 'Dokumen Keuangan', '0044/KU.03.10.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(45, 'KU.03.10.02', 'Belanja Barang dan Jasa', 'Keuangan', 'Dokumen Keuangan', '0045/KU.03.10.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(46, 'KU.03.10.03', 'Belanja Modal', 'Keuangan', 'Dokumen Keuangan', '0046/KU.03.10.03-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(47, 'KU.03.11.02', 'Hibah', 'Keuangan', 'Surat Hibah', '0047/KU.03.11.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(48, 'KU.05', 'Dokumen Penatausahaan Keuangan', 'Keuangan', 'Dokumen Administrasi', '0048/KU.05-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(49, 'KU.05.01', 'Surat Penyedia Dana (SPD)', 'Keuangan', 'Surat Penyedia Dana', '0049/KU.05.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(50, 'KU.05.02', 'Surat Permohonan Pembayaran (SPP)', 'Keuangan', 'Surat Permohonan', '0050/KU.05.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(51, 'KU.05.03', 'Surat Perintah Membayar (SPM)', 'Keuangan', 'Surat Perintah', '0051/KU.05.03-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(52, 'KU.05.04', 'Surat Perintah Pencairan Dana (SP2D)', 'Keuangan', 'Surat Perintah', '0052/KU.05.04-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(53, 'KP.11.08', 'Rekomendasi', 'Kepegawaian', 'Surat Rekomendasi', '0053/KP.11.08-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(54, 'KS.02.23', 'Sertifikat', 'Akademik', 'Sertifikat', '0054/KS.02.23-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(55, 'PK.01', 'Kebijakan Bersifat Pengaturan', 'Akademik', 'Surat Edaran', '0055/PK.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(56, 'PK.01.02', 'MoU', 'Hubungan Industri', 'Memorandum of Understanding (MoU)', '0056/PK.01.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(57, 'PK.02.01', 'Kebijakan Bersifat Penetapan', 'Akademik', 'Surat Keputusan (SK)', '0057/PK.02.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(58, 'PK.03.01.04', 'Pendidikan Masyarakat', 'Akademik', 'Surat Pemberitahuan', '0058/PK.03.01.04-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(59, 'PK.03.01.05', 'Lomba / Penghargaan', 'Akademik', 'Piagam / Sertifikat', '0059/PK.03.01.05-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(60, 'PK.03.02.11', 'Pendidikan Khusus / PKLK', 'Akademik', 'Surat Undangan', '0060/PK.03.02.11-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(61, 'PK.03.01.15', 'Pendidik dan Tenaga Kependidikan', 'Akademik', 'Surat Tugas', '0061/PK.03.01.15-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(62, 'PK.03.01.16', 'Penghargaan Guru dan Tendik', 'Akademik', 'Piagam / Sertifikat', '0062/PK.03.01.16-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(63, 'PK.03.03.01', 'Kurikulum / Bimtek / Lomba', 'Akademik', 'Surat Undangan', '0063/PK.03.03.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(64, 'PK.03.03.02', 'BOS / Bantuan Siswa', 'Akademik', 'Surat Usulan', '0064/PK.03.03.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(65, 'PK.09.01', 'Pengembangan Profesi Pendidik', 'Akademik', 'Surat Tugas', '0065/PK.09.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(66, 'PK.09.01.02', 'Sertifikasi', 'Akademik', 'Sertifikat', '0066/PK.09.01.02-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(67, 'PK.11.01', 'Data Peserta Didik, Pendidik dan Tendik', 'Akademik', 'Dokumen Administrasi', '0067/PK.11.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(68, 'RT.05.01', 'Kendaraan Dinas', 'Sarana & Prasarana', 'Surat Izin', '0068/RT.05.01-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(69, 'RT.05.03', 'Telekomunikasi', 'Sarana & Prasarana', 'Dokumen Administrasi', '0069/RT.05.03-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39'),
(70, 'TU.01', 'Persuratan', 'Tata Usaha', 'Surat Umum', '0070/TU.01-SMKN1PGL', '', NULL, '2026-06-27 01:43:39', '2026-06-29 12:02:00'),
(71, 'TU.04', 'Rapat / Rakor', 'Tata Usaha', 'Surat Undangan', '0071/TU.04-SMKN1PGL', NULL, NULL, '2026-06-27 01:43:39', '2026-06-27 01:43:39');

-- --------------------------------------------------------

--
-- Table structure for table `surat_jenis`
--

CREATE TABLE `surat_jenis` (
  `id` int NOT NULL,
  `nama_jenis` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surat_jenis`
--

INSERT INTO `surat_jenis` (`id`, `nama_jenis`, `created_at`) VALUES
(1, 'Dokumen Administrasi', '2026-06-29 12:35:43'),
(2, 'Dokumen Keuangan', '2026-06-29 12:35:43'),
(3, 'Dokumen Perencanaan', '2026-06-29 12:35:43'),
(4, 'Memorandum of Understanding (MoU)', '2026-06-29 12:35:43'),
(5, 'Piagam / Sertifikat', '2026-06-29 12:35:43'),
(6, 'Sertifikat', '2026-06-29 12:35:43'),
(7, 'Surat Cuti', '2026-06-29 12:35:43'),
(8, 'Surat Edaran', '2026-06-29 12:35:43'),
(9, 'Surat Hibah', '2026-06-29 12:35:43'),
(10, 'Surat Izin', '2026-06-29 12:35:43'),
(11, 'Surat Keputusan (SK)', '2026-06-29 12:35:43'),
(12, 'Surat Mutasi', '2026-06-29 12:35:43'),
(13, 'Surat Pemberitahuan', '2026-06-29 12:35:43'),
(14, 'Surat Pembinaan', '2026-06-29 12:35:43'),
(15, 'Surat Pengantar', '2026-06-29 12:35:43'),
(16, 'Surat Penolakan', '2026-06-29 12:35:43'),
(17, 'Surat Penyedia Dana', '2026-06-29 12:35:43'),
(18, 'Surat Perintah', '2026-06-29 12:35:43'),
(19, 'Surat Permohonan', '2026-06-29 12:35:43'),
(20, 'Surat Rekomendasi', '2026-06-29 12:35:43'),
(21, 'Surat Teguran', '2026-06-29 12:35:43'),
(22, 'Surat Tugas', '2026-06-29 12:35:43'),
(23, 'Surat Umum', '2026-06-29 12:35:43'),
(24, 'Surat Undangan', '2026-06-29 12:35:43'),
(25, 'Surat Usulan', '2026-06-29 12:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `surat_kategori`
--

CREATE TABLE `surat_kategori` (
  `id` int NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `surat_kategori`
--

INSERT INTO `surat_kategori` (`id`, `nama_kategori`, `created_at`) VALUES
(1, 'Akademik', '2026-06-29 12:35:43'),
(2, 'Hubungan Industri', '2026-06-29 12:35:43'),
(3, 'Kepegawaian', '2026-06-29 12:35:43'),
(4, 'Keuangan', '2026-06-29 12:35:43'),
(5, 'Sarana & Prasarana', '2026-06-29 12:35:43'),
(6, 'Tata Usaha', '2026-06-29 12:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `surat_keluar`
--

CREATE TABLE `surat_keluar` (
  `id` int NOT NULL,
  `indeks_id` int DEFAULT NULL,
  `no_surat` varchar(200) NOT NULL,
  `tgl_surat` date DEFAULT NULL,
  `perihal` text,
  `tujuan` text,
  `lampiran` varchar(255) DEFAULT '',
  `isi_surat` longtext COMMENT 'HTML atau plaintext',
  `template_path` varchar(255) DEFAULT NULL,
  `template_fields_json` text,
  `drive_file_id` varchar(255) DEFAULT NULL,
  `drive_view_link` text,
  `status` enum('Draf','Terkirim','Batal') DEFAULT 'Draf',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `surat_setting`
--

CREATE TABLE `surat_setting` (
  `id` int NOT NULL,
  `kop_nama_sekolah` varchar(255) DEFAULT '',
  `kop_alamat` text,
  `google_credentials` varchar(255) DEFAULT '',
  `client_email` varchar(255) DEFAULT '',
  `drive_folder_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oauth_client_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oauth_client_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oauth_refresh_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `oauth_token_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `oauth_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spreadsheet_id` varchar(255) DEFAULT NULL,
  `spreadsheet_range` varchar(50) DEFAULT 'Sheet1',
  `kop_logo` varchar(255) DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `surat_template`
--

CREATE TABLE `surat_template` (
  `id` int UNSIGNED NOT NULL,
  `indeks_id` int UNSIGNED DEFAULT NULL,
  `indeks_surat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_surat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_pembuat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `variabel_tag` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `link_dokumen` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deskripsi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `html_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `drive_file_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `drive_file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_anggota_rombel`
--

CREATE TABLE `sync_anggota_rombel` (
  `anggota_rombel_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID anggota rombel unik dari Dapodik',
  `rombongan_belajar_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID rombongan belajar (FK)',
  `peserta_didik_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign key ke sync_peserta_didik.peserta_didik_id',
  `jenis_pendaftaran_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID jenis pendaftaran',
  `jenis_pendaftaran_id_str` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Jenis pendaftaran (Siswa baru, dll)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Anggota Rombongan Belajar (Siswa per Kelas)';

-- --------------------------------------------------------

--
-- Table structure for table `sync_gtk`
--

CREATE TABLE `sync_gtk` (
  `tahun_ajaran_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptk_terdaftar_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ptk_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ptk_induk` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_surat_tugas` date DEFAULT NULL,
  `nama` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat_lahir` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `agama_id` int DEFAULT NULL,
  `agama_id_str` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nuptk` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nik` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_ptk_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_ptk_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan_ptk_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan_ptk_id_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_kepegawaian_id` int DEFAULT NULL,
  `status_kepegawaian_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nip` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pendidikan_terakhir` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bidang_studi_terakhir` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pangkat_golongan_terakhir` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rwy_pend_formal` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of education history',
  `rwy_kepangkatan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of rank history',
  `is_sync_to_server_ptk_terdaftar` tinyint(1) DEFAULT '0',
  `is_sync_to_server_ptk` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_log`
--

CREATE TABLE `sync_log` (
  `id` int NOT NULL,
  `endpoint` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('success','failed','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_records` int DEFAULT '0',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log sinkronisasi dari Dapodik Loader';

-- --------------------------------------------------------

--
-- Table structure for table `sync_pembelajaran`
--

CREATE TABLE `sync_pembelajaran` (
  `pembelajaran_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID pembelajaran unik dari Dapodik',
  `rombongan_belajar_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID rombongan belajar (FK)',
  `mata_pelajaran_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID mata pelajaran dari Dapodik (bigint disimpan sebagai string)',
  `mata_pelajaran_id_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama mata pelajaran',
  `ptk_terdaftar_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID PTK terdaftar (guru)',
  `ptk_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID PTK (guru)',
  `nama_mata_pelajaran` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama mata pelajaran lengkap',
  `induk_pembelajaran_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID induk pembelajaran (jika ada)',
  `jam_mengajar_per_minggu` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Beban mengajar per minggu dalam jam',
  `status_di_kurikulum` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Status di kurikulum ID',
  `status_di_kurikulum_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Status di kurikulum (deskripsi)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pembelajaran per Rombongan Belajar (Mapel & Guru)';

-- --------------------------------------------------------

--
-- Table structure for table `sync_pengguna`
--

CREATE TABLE `sync_pengguna` (
  `pengguna_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID pengguna unik dari Dapodik - Primary identifier',
  `sekolah_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID sekolah - Relasi ke sync_sekolah.sekolah_id',
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Username untuk login - biasanya email atau NIP',
  `nama` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama lengkap pengguna sesuai Dapodik',
  `peran_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Role: Operator Sekolah, Guru, Siswa, dll',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted password hash dari Dapodik',
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Alamat lengkap pengguna (nullable)',
  `no_telepon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nomor telepon rumah/kantor (nullable)',
  `no_hp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nomor HP/WhatsApp utama',
  `ptk_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Foreign key ke sync_ptk/sync_gtk jika user adalah PTK',
  `peserta_didik_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Foreign key ke sync_peserta_didik jika user adalah siswa',
  `is_sync_to_server` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Data Pengguna dari Dapodik';

-- --------------------------------------------------------

--
-- Table structure for table `sync_peserta_didik`
--

CREATE TABLE `sync_peserta_didik` (
  `registrasi_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_pendaftaran_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_pendaftaran_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nipd` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_masuk_sekolah` date DEFAULT NULL,
  `sekolah_asal` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_jalan` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `peserta_didik_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nisn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_kelamin` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nik` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat_lahir` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `agama_id` int DEFAULT NULL,
  `agama_id_str` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_telepon_rumah` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_telepon_seluler` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_ayah` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_ayah_id` int DEFAULT NULL,
  `pekerjaan_ayah_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_ibu` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_ibu_id` int DEFAULT NULL,
  `pekerjaan_ibu_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_wali` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_wali_id` int DEFAULT NULL,
  `pekerjaan_wali_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anak_keberapa` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tinggi_badan` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `berat_badan` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anggota_rombel_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rombongan_belajar_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tingkat_pendidikan_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_rombel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kurikulum_id` int DEFAULT NULL,
  `kurikulum_id_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kebutuhan_khusus` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_sync_to_server` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_rombongan_belajar`
--

CREATE TABLE `sync_rombongan_belajar` (
  `rombongan_belajar_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID rombongan belajar unik dari Dapodik - Primary identifier',
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama rombel: X ATPH 1, XI IPA 2, dll',
  `tingkat_pendidikan_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID tingkat pendidikan',
  `tingkat_pendidikan_id_str` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama tingkat (Kelas 10, dll)',
  `semester_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID semester aktif',
  `jenis_rombel` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Jenis rombel ID',
  `jenis_rombel_str` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Jenis rombel (Kelas, dll)',
  `kurikulum_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID kurikulum yang digunakan',
  `kurikulum_id_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama kurikulum: SMK Merdeka Agribisnis Tanaman, dll',
  `id_ruang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID ruang kelas',
  `id_ruang_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama ruang kelas',
  `moving_class` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Status moving class (Ya/Tidak)',
  `ptk_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID wali kelas (PTK)',
  `ptk_id_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama wali kelas lengkap dari Dapodik',
  `jurusan_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID jurusan/bidang keahlian',
  `jurusan_id_str` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama jurusan lengkap',
  `is_sync_to_server` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Data Rombongan Belajar dari Dapodik';

-- --------------------------------------------------------

--
-- Table structure for table `sync_sekolah`
--

CREATE TABLE `sync_sekolah` (
  `sekolah_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nss` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `npsn` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bentuk_pendidikan_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bentuk_pendidikan_id_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_sekolah` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_sekolah_str` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_jalan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rt` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rw` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dusun` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desa_kelurahan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_wilayah` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_pos` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lintang` decimal(10,8) DEFAULT NULL,
  `bujur` decimal(11,8) DEFAULT NULL,
  `nomor_telepon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor_fax` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_sks` tinyint(1) DEFAULT '0',
  `kecamatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kabupaten_kota` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provinsi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_sync_to_server` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Data sekolah dari Dapodik';

-- --------------------------------------------------------

--
-- Table structure for table `tamu_instansi`
--

CREATE TABLE `tamu_instansi` (
  `id` int NOT NULL,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telepon` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tamu_tujuan`
--

CREATE TABLE `tamu_tujuan` (
  `id` int NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tamu_tujuan`
--

INSERT INTO `tamu_tujuan` (`id`, `nama`, `keterangan`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Rapat/Meeting', NULL, 'Y', '2026-06-04 08:58:34', '2026-06-04 08:58:34'),
(2, 'Konsultasi', NULL, 'Y', '2026-06-04 08:58:34', '2026-06-04 08:58:34'),
(3, 'Kunjungan Kerja', NULL, 'Y', '2026-06-04 08:58:34', '2026-06-04 08:58:34'),
(4, 'Penelitian', NULL, 'Y', '2026-06-04 08:58:34', '2026-06-04 08:58:34'),
(5, 'Magang/PKL', NULL, 'Y', '2026-06-04 08:58:34', '2026-06-04 08:58:34'),
(6, 'Wawancara', NULL, 'Y', '2026-06-04 08:58:34', '2026-06-04 08:58:34'),
(7, 'Pengantaran Barang', NULL, 'Y', '2026-06-04 08:58:34', '2026-06-04 08:58:34'),
(8, 'Lainnya', NULL, 'Y', '2026-06-04 08:58:34', '2026-06-04 08:58:34');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int NOT NULL,
  `registrasi_id` varchar(50) DEFAULT NULL,
  `peserta_didik_id` varchar(50) DEFAULT NULL,
  `jenis_pendaftaran_id` varchar(10) DEFAULT NULL,
  `jenis_pendaftaran_id_str` varchar(100) DEFAULT NULL,
  `tanggal_masuk_sekolah` date DEFAULT NULL,
  `no_kk` varchar(32) DEFAULT NULL,
  `nik` varchar(25) NOT NULL,
  `nipd` varchar(25) NOT NULL,
  `nisn` varchar(25) NOT NULL,
  `rfid` varchar(50) DEFAULT NULL,
  `jurusan_id` int DEFAULT NULL,
  `nama_lengkap` varchar(70) NOT NULL,
  `sekolah_asal` varchar(255) DEFAULT NULL,
  `tempat_lahir` varchar(30) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `agama_id` int DEFAULT NULL,
  `jenis_kelamin` varchar(10) NOT NULL,
  `status_keluarga` varchar(50) DEFAULT NULL,
  `tingkat` varchar(50) DEFAULT NULL,
  `kelas` varchar(20) NOT NULL,
  `wali_kelas` int DEFAULT NULL,
  `diterima_dikelas` varchar(50) DEFAULT NULL,
  `diterima_tanggal` date DEFAULT NULL,
  `nik_ayah` varchar(40) DEFAULT NULL,
  `nama_ayah` varchar(40) NOT NULL,
  `pekerjaan_ayah` varchar(40) NOT NULL,
  `pekerjaan_ayah_id` int DEFAULT NULL,
  `nik_ibu` varchar(40) DEFAULT NULL,
  `nama_ibu` varchar(40) NOT NULL,
  `nama_wali` varchar(100) DEFAULT NULL,
  `alamat_wali` varchar(255) DEFAULT NULL,
  `telp_wali` varchar(20) DEFAULT NULL,
  `pekerjaan_wali` varchar(100) DEFAULT NULL,
  `pekerjaan_wali_id` int DEFAULT NULL,
  `pekerjaan_ibu` varchar(40) NOT NULL,
  `pekerjaan_ibu_id` int DEFAULT NULL,
  `alamat` text NOT NULL,
  `rt` varchar(10) DEFAULT NULL,
  `rw` varchar(10) DEFAULT NULL,
  `provinsi_id` varchar(10) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kabupaten_kota_id` varchar(10) DEFAULT NULL,
  `kabupaten_kota` varchar(100) DEFAULT NULL,
  `kecamatan_id` varchar(10) DEFAULT NULL,
  `desa` varchar(100) DEFAULT NULL,
  `desa_id` varchar(10) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kodepos` varchar(10) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `password` varchar(200) DEFAULT NULL,
  `telp` varchar(15) NOT NULL,
  `nomor_telepon_rumah` varchar(20) DEFAULT NULL,
  `nomor_telepon_seluler` varchar(20) DEFAULT NULL,
  `telp_ortu` varchar(30) DEFAULT NULL,
  `anak_ke` varchar(5) NOT NULL,
  `anak_keberapa` varchar(10) DEFAULT NULL,
  `tinggi_badan` varchar(10) DEFAULT NULL,
  `berat_badan` varchar(10) DEFAULT NULL,
  `avatar` varchar(160) NOT NULL,
  `time` int NOT NULL,
  `date` date NOT NULL,
  `status` varchar(15) DEFAULT NULL,
  `konfirmasi` enum('Sesuai','Belum Sesuai','Belum Konfirmasi') DEFAULT 'Belum Konfirmasi',
  `konfirmasi_time` int DEFAULT '0',
  `konfirmasi_data` text,
  `koordinator` tinyint(1) NOT NULL DEFAULT '0',
  `rombongan_belajar_id` varchar(50) DEFAULT NULL COMMENT 'From sync_rombongan_belajar for current class',
  `semester_id` varchar(10) DEFAULT NULL,
  `anggota_rombel_id` varchar(50) DEFAULT NULL,
  `tingkat_pendidikan_id` varchar(10) DEFAULT NULL,
  `nama_rombel` varchar(100) DEFAULT NULL,
  `kurikulum_id` int DEFAULT NULL,
  `kurikulum_id_str` varchar(200) DEFAULT NULL,
  `kebutuhan_khusus` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `kelas_nama` varchar(100) DEFAULT NULL COMMENT 'Current class name from rombel',
  `rombel_sync_status` enum('active','inactive','pending') DEFAULT 'pending' COMMENT 'Rombel sync status',
  `rombel_last_sync` timestamp NULL DEFAULT NULL COMMENT 'Last rombel sync timestamp',
  `whatsapp_verified` tinyint(1) DEFAULT '0' COMMENT 'Status verifikasi WhatsApp: 0=belum, 1=sudah',
  `whatsapp_verified_at` datetime DEFAULT NULL COMMENT 'Waktu verifikasi WhatsApp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `user_app_credentials`
--

CREATE TABLE `user_app_credentials` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `app_id` int NOT NULL,
  `app_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `app_password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_active` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usulan_pip`
--

CREATE TABLE `usulan_pip` (
  `usulan_pip_id` int NOT NULL,
  `user_id` int NOT NULL,
  `nisn` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tempat_lahir` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `nik_ayah` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_ayah` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_ayah` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penghasilan_ayah` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nik_ibu` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_ibu` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_ibu` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penghasilan_ibu` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat_tinggal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_wali` varchar(70) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_wali` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pertanyaan_1` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Apakah penerima KPS/PKH?',
  `kks_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File upload KKS jika Ya',
  `no_kks` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pertanyaan_2` enum('Ya','Tidak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Apakah Punya KIP?',
  `kip_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'File upload KIP jika Ya',
  `no_kip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Diproses','Disetujui','Ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `alasan_usulan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_pengajuan` datetime DEFAULT CURRENT_TIMESTAMP,
  `poin` int UNSIGNED NOT NULL DEFAULT '0',
  `ranking_position` int DEFAULT NULL,
  `tanggal_update` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `dapodik_status` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'N' COMMENT 'Status konfirmasi input ke Dapodik: Y=Sudah, N=Belum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel untuk menyimpan usulan Program Indonesia Pintar (PIP)';

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_config`
--

CREATE TABLE `whatsapp_config` (
  `id` int NOT NULL,
  `api_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL endpoint API WhatsApp Gateway',
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'API Key untuk autentikasi',
  `status` enum('Y','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'N' COMMENT 'Status aktif WhatsApp Gateway (Y=Aktif, N=Nonaktif)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu dibuat',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu diupdate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurasi WhatsApp Gateway';

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_logs`
--

CREATE TABLE `whatsapp_logs` (
  `id` int NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nomor telepon tujuan',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Isi pesan',
  `activity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Jenis aktivitas (verifikasi_hp, reset_password, login_alert, dll)',
  `status` enum('pending','sent','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Status pengiriman',
  `response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Response dari API',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Pesan error jika gagal',
  `user_id` int DEFAULT NULL COMMENT 'ID user terkait',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu kirim'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log pengiriman pesan WhatsApp';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_absensi_user_id` (`user_id`),
  ADD KEY `idx_absensi_tanggal` (`tanggal`),
  ADD KEY `idx_absensi_user_tanggal` (`user_id`,`tanggal`),
  ADD KEY `idx_absensi_metode` (`metode`),
  ADD KEY `idx_absensi_approval_status` (`approval_status`);

--
-- Indexes for table `absensi_edit_request`
--
ALTER TABLE `absensi_edit_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kelas_tanggal` (`kelas_id`,`tanggal`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_log_admin_id` (`admin_id`),
  ADD KEY `idx_activity_log_action` (`action`),
  ADD KEY `idx_activity_log_created_at` (`created_at`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `idx_ptk_id` (`ptk_id`),
  ADD KEY `idx_sync_status` (`sync_status`),
  ADD KEY `idx_pengguna_id` (`pengguna_id`);

--
-- Indexes for table `agenda_edit_request`
--
ALTER TABLE `agenda_edit_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agenda` (`agenda_id`),
  ADD KEY `idx_kelas_tanggal` (`kelas_id`,`tanggal`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `agenda_jadwal`
--
ALTER TABLE `agenda_jadwal`
  ADD PRIMARY KEY (`jadwal_id`),
  ADD UNIQUE KEY `uq_kelas_hari_jam` (`kelas_id`,`hari`,`jam_ke`),
  ADD KEY `idx_mapel` (`mapel_id`);

--
-- Indexes for table `agenda_kelas`
--
ALTER TABLE `agenda_kelas`
  ADD PRIMARY KEY (`agenda_id`),
  ADD UNIQUE KEY `uq_kelas_tanggal_jam` (`kelas_id`,`tanggal`,`jam_ke`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_guru` (`guru_id`),
  ADD KEY `idx_mapel` (`mapel_id`),
  ADD KEY `idx_kehadiran` (`kehadiran_guru`);

--
-- Indexes for table `agenda_mapel`
--
ALTER TABLE `agenda_mapel`
  ADD PRIMARY KEY (`mapel_id`),
  ADD KEY `idx_guru` (`guru_id`),
  ADD KEY `idx_aktif` (`aktif`);

--
-- Indexes for table `berkas`
--
ALTER TABLE `berkas`
  ADD PRIMARY KEY (`berkas_id`);

--
-- Indexes for table `buku_tamu`
--
ALTER TABLE `buku_tamu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `guest_id` (`guest_id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_tanggal` (`tanggal_kunjungan`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `buku_tamu_log`
--
ALTER TABLE `buku_tamu_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guest_id` (`guest_id`),
  ADD KEY `idx_activity` (`activity`);

--
-- Indexes for table `buku_tamu_survey`
--
ALTER TABLE `buku_tamu_survey`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_survey_guest` (`guest_id`),
  ADD KEY `idx_survey_guest_table` (`guest_table_id`);

--
-- Indexes for table `commit_log`
--
ALTER TABLE `commit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commit_hash` (`commit_hash`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `deploy_records`
--
ALTER TABLE `deploy_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deployed_at` (`deployed_at`);

--
-- Indexes for table `e_izin`
--
ALTER TABLE `e_izin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status_izin` (`status_izin`),
  ADD KEY `idx_status_izin_wali` (`status_izin_wali`),
  ADD KEY `idx_time_keluar` (`time_keluar`),
  ADD KEY `idx_time_kembali` (`time_kembali`);

--
-- Indexes for table `hari_libur`
--
ALTER TABLE `hari_libur`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `info`
--
ALTER TABLE `info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipe` (`tipe`),
  ADD KEY `aktif` (`aktif`);

--
-- Indexes for table `inv_barang`
--
ALTER TABLE `inv_barang`
  ADD PRIMARY KEY (`barang_id`),
  ADD KEY `idx_kategori` (`kategori_id`);

--
-- Indexes for table `inv_kategori`
--
ALTER TABLE `inv_kategori`
  ADD PRIMARY KEY (`kategori_id`);

--
-- Indexes for table `inv_kelas`
--
ALTER TABLE `inv_kelas`
  ADD PRIMARY KEY (`inv_id`),
  ADD KEY `idx_kelas` (`kelas_id`),
  ADD KEY `idx_barang` (`barang_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_kondisi` (`kondisi`);

--
-- Indexes for table `inv_laporan`
--
ALTER TABLE `inv_laporan`
  ADD PRIMARY KEY (`laporan_id`),
  ADD KEY `idx_kelas` (`kelas_id`),
  ADD KEY `idx_barang` (`barang_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_jenis` (`jenis_laporan`);

--
-- Indexes for table `inv_pinjam`
--
ALTER TABLE `inv_pinjam`
  ADD PRIMARY KEY (`pinjam_id`),
  ADD KEY `idx_barang` (`barang_id`),
  ADD KEY `idx_kelas` (`kelas_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tgl_pinjam` (`tanggal_pinjam`);

--
-- Indexes for table `izin`
--
ALTER TABLE `izin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jurusan`
--
ALTER TABLE `jurusan`
  ADD PRIMARY KEY (`jurusan_id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`kelas_id`),
  ADD UNIQUE KEY `rombongan_belajar_id` (`rombongan_belajar_id`),
  ADD KEY `idx_kelas_rombongan_belajar_id` (`rombongan_belajar_id`),
  ADD KEY `idx_kelas_wali_kelas_ptk_id` (`wali_kelas_ptk_id`),
  ADD KEY `idx_kelas_sync_status` (`sync_status`),
  ADD KEY `idx_kelas_semester_tingkat` (`semester_id`,`tingkat_pendidikan_id`);

--
-- Indexes for table `kelulusan_history`
--
ALTER TABLE `kelulusan_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kelulusan_history_user` (`user_id`),
  ADD KEY `idx_kelulusan_history_action` (`action`),
  ADD KEY `idx_kelulusan_history_created` (`created_at`);

--
-- Indexes for table `kelulusan_ijazah`
--
ALTER TABLE `kelulusan_ijazah`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_kelulusan_ijazah_user` (`user_id`),
  ADD KEY `idx_kelulusan_ijazah_nisn` (`nisn`),
  ADD KEY `idx_kelulusan_ijazah_konfirmasi` (`konfirmasi`);

--
-- Indexes for table `kelulusan_settings`
--
ALTER TABLE `kelulusan_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kelulusan_skl`
--
ALTER TABLE `kelulusan_skl`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_kelulusan_skl_user` (`user_id`);

--
-- Indexes for table `kelulusan_status`
--
ALTER TABLE `kelulusan_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_kelulusan_status_user` (`user_id`),
  ADD KEY `idx_kelulusan_status_status` (`status`);

--
-- Indexes for table `kriteria_pip`
--
ALTER TABLE `kriteria_pip`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `level`
--
ALTER TABLE `level`
  ADD PRIMARY KEY (`level_id`);

--
-- Indexes for table `lokasi`
--
ALTER TABLE `lokasi`
  ADD PRIMARY KEY (`lokasi_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `migration` (`migration`);

--
-- Indexes for table `modul`
--
ALTER TABLE `modul`
  ADD PRIMARY KEY (`modul_id`);

--
-- Indexes for table `perubahan`
--
ALTER TABLE `perubahan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status_pengajuan` (`status_pengajuan`);

--
-- Indexes for table `pkl_peserta`
--
ALTER TABLE `pkl_peserta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pkl_nisn` (`nisn`),
  ADD KEY `idx_pkl_status` (`status_kirim`);

--
-- Indexes for table `poin_ayat`
--
ALTER TABLE `poin_ayat`
  ADD PRIMARY KEY (`ayat_id`),
  ADD KEY `fk_ayat_pasal` (`pasal_id`);

--
-- Indexes for table `poin_panggil`
--
ALTER TABLE `poin_panggil`
  ADD PRIMARY KEY (`panggil_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `poin_pasal`
--
ALTER TABLE `poin_pasal`
  ADD PRIMARY KEY (`pasal_id`);

--
-- Indexes for table `poin_pelanggaran`
--
ALTER TABLE `poin_pelanggaran`
  ADD PRIMARY KEY (`pelanggaran_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_kelas` (`kelas_id`),
  ADD KEY `idx_ayat` (`ayat_id`),
  ADD KEY `idx_semester` (`semester_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tanggal` (`tanggal_kejadian`);

--
-- Indexes for table `poin_sanggah`
--
ALTER TABLE `poin_sanggah`
  ADD PRIMARY KEY (`sanggah_id`),
  ADD KEY `idx_pelanggaran` (`pelanggaran_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `poin_semester`
--
ALTER TABLE `poin_semester`
  ADD PRIMARY KEY (`semester_id`);

--
-- Indexes for table `portal_access_log`
--
ALTER TABLE `portal_access_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_app` (`admin_id`,`app_name`),
  ADD KEY `idx_access_time` (`access_time`);

--
-- Indexes for table `portal_apps`
--
ALTER TABLE `portal_apps`
  ADD PRIMARY KEY (`app_id`),
  ADD UNIQUE KEY `unique_app_name` (`app_name`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_action_ip` (`action_ip_hash`,`expires_at`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `security_log`
--
ALTER TABLE `security_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`site_id`);

--
-- Indexes for table `statistik`
--
ALTER TABLE `statistik`
  ADD PRIMARY KEY (`statistik_id`);

--
-- Indexes for table `student_menu`
--
ALTER TABLE `student_menu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `position` (`position`);

--
-- Indexes for table `surat_index`
--
ALTER TABLE `surat_index`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_indeks` (`indeks`);

--
-- Indexes for table `surat_jenis`
--
ALTER TABLE `surat_jenis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_jenis` (`nama_jenis`);

--
-- Indexes for table `surat_kategori`
--
ALTER TABLE `surat_kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `surat_keluar`
--
ALTER TABLE `surat_keluar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_indeks` (`indeks_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `surat_setting`
--
ALTER TABLE `surat_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `surat_template`
--
ALTER TABLE `surat_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_indeks_id` (`indeks_id`),
  ADD KEY `idx_drive_file_id` (`drive_file_id`);

--
-- Indexes for table `sync_anggota_rombel`
--
ALTER TABLE `sync_anggota_rombel`
  ADD PRIMARY KEY (`anggota_rombel_id`),
  ADD KEY `idx_rombel` (`rombongan_belajar_id`),
  ADD KEY `idx_peserta_didik` (`peserta_didik_id`),
  ADD KEY `idx_jenis_pendaftaran` (`jenis_pendaftaran_id`),
  ADD KEY `idx_composite` (`rombongan_belajar_id`,`peserta_didik_id`),
  ADD KEY `idx_rombel_jenis` (`rombongan_belajar_id`,`jenis_pendaftaran_id`);

--
-- Indexes for table `sync_gtk`
--
ALTER TABLE `sync_gtk`
  ADD PRIMARY KEY (`ptk_terdaftar_id`),
  ADD KEY `idx_ptk_id` (`ptk_id`),
  ADD KEY `idx_nuptk` (`nuptk`),
  ADD KEY `idx_nik` (`nik`),
  ADD KEY `idx_nama` (`nama`),
  ADD KEY `idx_jenis_ptk` (`jenis_ptk_id`);

--
-- Indexes for table `sync_log`
--
ALTER TABLE `sync_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `endpoint` (`endpoint`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `sync_pembelajaran`
--
ALTER TABLE `sync_pembelajaran`
  ADD PRIMARY KEY (`pembelajaran_id`),
  ADD KEY `idx_rombel` (`rombongan_belajar_id`),
  ADD KEY `idx_mata_pelajaran` (`mata_pelajaran_id`),
  ADD KEY `idx_ptk_terdaftar` (`ptk_terdaftar_id`),
  ADD KEY `idx_ptk` (`ptk_id`),
  ADD KEY `idx_composite` (`rombongan_belajar_id`,`mata_pelajaran_id`),
  ADD KEY `idx_ptk_mapel` (`ptk_id`,`mata_pelajaran_id`);
ALTER TABLE `sync_pembelajaran` ADD FULLTEXT KEY `ft_mata_pelajaran` (`mata_pelajaran_id_str`,`nama_mata_pelajaran`);

--
-- Indexes for table `sync_pengguna`
--
ALTER TABLE `sync_pengguna`
  ADD PRIMARY KEY (`pengguna_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_sekolah_id` (`sekolah_id`),
  ADD KEY `idx_peran` (`peran_id_str`),
  ADD KEY `idx_ptk_id` (`ptk_id`),
  ADD KEY `idx_peserta_didik_id` (`peserta_didik_id`),
  ADD KEY `idx_sekolah_peran` (`sekolah_id`,`peran_id_str`),
  ADD KEY `idx_ptk_active` (`ptk_id`,`pengguna_id`),
  ADD KEY `idx_siswa_active` (`peserta_didik_id`,`pengguna_id`);

--
-- Indexes for table `sync_peserta_didik`
--
ALTER TABLE `sync_peserta_didik`
  ADD PRIMARY KEY (`peserta_didik_id`),
  ADD KEY `idx_nisn` (`nisn`),
  ADD KEY `idx_nama` (`nama`),
  ADD KEY `idx_rombongan_belajar_id` (`rombongan_belajar_id`);

--
-- Indexes for table `sync_rombongan_belajar`
--
ALTER TABLE `sync_rombongan_belajar`
  ADD PRIMARY KEY (`rombongan_belajar_id`),
  ADD KEY `idx_nama` (`nama`),
  ADD KEY `idx_tingkat` (`tingkat_pendidikan_id`),
  ADD KEY `idx_semester` (`semester_id`),
  ADD KEY `idx_jurusan` (`jurusan_id`),
  ADD KEY `idx_wali_kelas` (`ptk_id`),
  ADD KEY `idx_tingkat_jurusan` (`tingkat_pendidikan_id`,`jurusan_id`),
  ADD KEY `idx_kelas_nama_composite` (`nama`);
ALTER TABLE `sync_rombongan_belajar` ADD FULLTEXT KEY `ft_nama_rombel` (`nama`,`jurusan_id_str`);

--
-- Indexes for table `sync_sekolah`
--
ALTER TABLE `sync_sekolah`
  ADD PRIMARY KEY (`sekolah_id`),
  ADD KEY `npsn` (`npsn`);

--
-- Indexes for table `tamu_instansi`
--
ALTER TABLE `tamu_instansi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_instansi_nama` (`nama`),
  ADD KEY `idx_instansi_active` (`active`);

--
-- Indexes for table `tamu_tujuan`
--
ALTER TABLE `tamu_tujuan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tujuan_nama` (`nama`),
  ADD KEY `idx_tujuan_active` (`active`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `fk_user_jurusan` (`jurusan_id`),
  ADD KEY `idx_user_rombongan_belajar_id` (`rombongan_belajar_id`),
  ADD KEY `idx_user_nisn_rombel` (`nisn`,`rombongan_belajar_id`),
  ADD KEY `idx_user_rombel_sync_status` (`rombel_sync_status`);

--
-- Indexes for table `user_app_credentials`
--
ALTER TABLE `user_app_credentials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_admin_app` (`admin_id`,`app_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_app_id` (`app_id`);

--
-- Indexes for table `usulan_pip`
--
ALTER TABLE `usulan_pip`
  ADD PRIMARY KEY (`usulan_pip_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `nisn` (`nisn`),
  ADD KEY `idx_usulan_pip_ranking_position` (`ranking_position`);

--
-- Indexes for table `whatsapp_config`
--
ALTER TABLE `whatsapp_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone` (`phone_number`),
  ADD KEY `idx_activity` (`activity_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `absensi_edit_request`
--
ALTER TABLE `absensi_edit_request`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agenda_edit_request`
--
ALTER TABLE `agenda_edit_request`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agenda_jadwal`
--
ALTER TABLE `agenda_jadwal`
  MODIFY `jadwal_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agenda_kelas`
--
ALTER TABLE `agenda_kelas`
  MODIFY `agenda_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agenda_mapel`
--
ALTER TABLE `agenda_mapel`
  MODIFY `mapel_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `berkas`
--
ALTER TABLE `berkas`
  MODIFY `berkas_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buku_tamu`
--
ALTER TABLE `buku_tamu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buku_tamu_log`
--
ALTER TABLE `buku_tamu_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buku_tamu_survey`
--
ALTER TABLE `buku_tamu_survey`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commit_log`
--
ALTER TABLE `commit_log`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deploy_records`
--
ALTER TABLE `deploy_records`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `e_izin`
--
ALTER TABLE `e_izin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hari_libur`
--
ALTER TABLE `hari_libur`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `info`
--
ALTER TABLE `info`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_barang`
--
ALTER TABLE `inv_barang`
  MODIFY `barang_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `inv_kategori`
--
ALTER TABLE `inv_kategori`
  MODIFY `kategori_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inv_kelas`
--
ALTER TABLE `inv_kelas`
  MODIFY `inv_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_laporan`
--
ALTER TABLE `inv_laporan`
  MODIFY `laporan_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inv_pinjam`
--
ALTER TABLE `inv_pinjam`
  MODIFY `pinjam_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `izin`
--
ALTER TABLE `izin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jurusan`
--
ALTER TABLE `jurusan`
  MODIFY `jurusan_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `kelas_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=564;

--
-- AUTO_INCREMENT for table `kelulusan_history`
--
ALTER TABLE `kelulusan_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelulusan_ijazah`
--
ALTER TABLE `kelulusan_ijazah`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelulusan_skl`
--
ALTER TABLE `kelulusan_skl`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelulusan_status`
--
ALTER TABLE `kelulusan_status`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kriteria_pip`
--
ALTER TABLE `kriteria_pip`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `level`
--
ALTER TABLE `level`
  MODIFY `level_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `lokasi`
--
ALTER TABLE `lokasi`
  MODIFY `lokasi_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modul`
--
ALTER TABLE `modul`
  MODIFY `modul_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `perubahan`
--
ALTER TABLE `perubahan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pkl_peserta`
--
ALTER TABLE `pkl_peserta`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poin_ayat`
--
ALTER TABLE `poin_ayat`
  MODIFY `ayat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `poin_panggil`
--
ALTER TABLE `poin_panggil`
  MODIFY `panggil_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poin_pasal`
--
ALTER TABLE `poin_pasal`
  MODIFY `pasal_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `poin_pelanggaran`
--
ALTER TABLE `poin_pelanggaran`
  MODIFY `pelanggaran_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poin_sanggah`
--
ALTER TABLE `poin_sanggah`
  MODIFY `sanggah_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poin_semester`
--
ALTER TABLE `poin_semester`
  MODIFY `semester_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portal_access_log`
--
ALTER TABLE `portal_access_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portal_apps`
--
ALTER TABLE `portal_apps`
  MODIFY `app_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1079;

--
-- AUTO_INCREMENT for table `security_log`
--
ALTER TABLE `security_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting`
--
ALTER TABLE `setting`
  MODIFY `site_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `statistik`
--
ALTER TABLE `statistik`
  MODIFY `statistik_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_menu`
--
ALTER TABLE `student_menu`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `surat_index`
--
ALTER TABLE `surat_index`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `surat_jenis`
--
ALTER TABLE `surat_jenis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `surat_kategori`
--
ALTER TABLE `surat_kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `surat_keluar`
--
ALTER TABLE `surat_keluar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `surat_setting`
--
ALTER TABLE `surat_setting`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `surat_template`
--
ALTER TABLE `surat_template`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sync_log`
--
ALTER TABLE `sync_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tamu_instansi`
--
ALTER TABLE `tamu_instansi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tamu_tujuan`
--
ALTER TABLE `tamu_tujuan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_app_credentials`
--
ALTER TABLE `user_app_credentials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usulan_pip`
--
ALTER TABLE `usulan_pip`
  MODIFY `usulan_pip_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whatsapp_config`
--
ALTER TABLE `whatsapp_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inv_barang`
--
ALTER TABLE `inv_barang`
  ADD CONSTRAINT `fk_inv_barang_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `inv_kategori` (`kategori_id`) ON UPDATE CASCADE;

--
-- Constraints for table `inv_kelas`
--
ALTER TABLE `inv_kelas`
  ADD CONSTRAINT `fk_inv_kelas_barang` FOREIGN KEY (`barang_id`) REFERENCES `inv_barang` (`barang_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_kelas_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`kelas_id`) ON UPDATE CASCADE;

--
-- Constraints for table `inv_pinjam`
--
ALTER TABLE `inv_pinjam`
  ADD CONSTRAINT `fk_inv_pinjam_barang` FOREIGN KEY (`barang_id`) REFERENCES `inv_barang` (`barang_id`) ON UPDATE CASCADE;

--
-- Constraints for table `poin_ayat`
--
ALTER TABLE `poin_ayat`
  ADD CONSTRAINT `fk_ayat_pasal` FOREIGN KEY (`pasal_id`) REFERENCES `poin_pasal` (`pasal_id`) ON DELETE CASCADE;

--
-- Constraints for table `sync_pembelajaran`
--
ALTER TABLE `sync_pembelajaran`
  ADD CONSTRAINT `fk_pembelajaran_rombel` FOREIGN KEY (`rombongan_belajar_id`) REFERENCES `sync_rombongan_belajar` (`rombongan_belajar_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
