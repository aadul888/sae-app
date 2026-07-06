-- =============================================================
-- Migration: Google Drive Integration for surat-template
-- Adds Drive folder config to surat_setting and drive_file_id to surat_template
-- =============================================================

-- 1. Add drive_folder_id to surat_setting
ALTER TABLE `surat_setting`
  ADD COLUMN `drive_folder_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `client_email`;

-- 2. Add drive fields to surat_template
ALTER TABLE `surat_template`
  ADD COLUMN `drive_file_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `html_content`,
  ADD COLUMN `drive_file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `drive_file_id`,
  ADD INDEX `idx_drive_file_id` (`drive_file_id`);
