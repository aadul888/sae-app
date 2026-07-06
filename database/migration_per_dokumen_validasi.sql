-- Migration: Per-Document Validation for Berkas
-- Adds per-document validation status and keterangan columns

ALTER TABLE `berkas`
  ADD COLUMN `kk_valid` VARCHAR(20) DEFAULT '' AFTER `kk`,
  ADD COLUMN `kk_keterangan` TEXT DEFAULT NULL AFTER `kk_valid`,
  ADD COLUMN `ijazah_valid` VARCHAR(20) DEFAULT '' AFTER `ijazah`,
  ADD COLUMN `ijazah_keterangan` TEXT DEFAULT NULL AFTER `ijazah_valid`,
  ADD COLUMN `akte_valid` VARCHAR(20) DEFAULT '' AFTER `akte`,
  ADD COLUMN `akte_keterangan` TEXT DEFAULT NULL AFTER `akte_valid`,
  ADD COLUMN `kip_valid` VARCHAR(20) DEFAULT '' AFTER `kip`,
  ADD COLUMN `kip_keterangan` TEXT DEFAULT NULL AFTER `kip_valid`,
  ADD COLUMN `kks_valid` VARCHAR(20) DEFAULT '' AFTER `kks`,
  ADD COLUMN `kks_keterangan` TEXT DEFAULT NULL AFTER `kks_valid`,
  ADD COLUMN `kis_valid` VARCHAR(20) DEFAULT '' AFTER `kis`,
  ADD COLUMN `kis_keterangan` TEXT DEFAULT NULL AFTER `kis_valid`;
