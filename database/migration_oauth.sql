-- =============================================================
-- Migration: OAuth 2.0 untuk Google Drive (ganti Service Account)
-- Menambahkan kolom untuk OAuth Client ID, Secret, Refresh Token
-- =============================================================

ALTER TABLE `surat_setting`
  ADD COLUMN `oauth_client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `drive_folder_id`,
  ADD COLUMN `oauth_client_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `oauth_client_id`,
  ADD COLUMN `oauth_refresh_token` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `oauth_client_secret`,
  ADD COLUMN `oauth_token_json` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `oauth_refresh_token`,
  ADD COLUMN `oauth_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `oauth_token_json`;

-- Run via: mysql -u root saev5 < database/migration_oauth.sql
