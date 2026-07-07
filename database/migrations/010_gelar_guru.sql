-- 010_gelar_guru.sql
-- Tambah kolom gelar_depan & gelar_belakang jika belum ada (idempotent)
SET @db = (SELECT DATABASE());

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'gelar_depan');
SET @sql = IF(@exist = 0, 'ALTER TABLE `admin` ADD COLUMN `gelar_depan` varchar(50) DEFAULT NULL AFTER `fullname`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'gelar_belakang');
SET @sql = IF(@exist = 0, 'ALTER TABLE `admin` ADD COLUMN `gelar_belakang` varchar(50) DEFAULT NULL AFTER `gelar_depan`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
