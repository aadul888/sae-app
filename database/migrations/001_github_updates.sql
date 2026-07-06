-- Migration: Add GitHub update tracking columns to setting table
-- Run this once against saev5 database

ALTER TABLE `setting`
  ADD COLUMN IF NOT EXISTS `github_last_sha` VARCHAR(40) DEFAULT NULL AFTER `license_expired_at`,
  ADD COLUMN IF NOT EXISTS `github_last_check` DATETIME DEFAULT NULL AFTER `github_last_sha`,
  ADD COLUMN IF NOT EXISTS `github_update_available` ENUM('Y','N') DEFAULT 'N' AFTER `github_last_check`,
  ADD COLUMN IF NOT EXISTS `github_latest_sha` VARCHAR(40) DEFAULT NULL AFTER `github_update_available`,
  ADD COLUMN IF NOT EXISTS `github_latest_url` VARCHAR(500) DEFAULT NULL AFTER `github_latest_sha`,
  ADD COLUMN IF NOT EXISTS `github_latest_message` VARCHAR(500) DEFAULT NULL AFTER `github_latest_url`;

UPDATE `setting` SET
  `github_last_sha` = `github_last_sha`,
  `github_last_check` = `github_last_check`,
  `github_update_available` = COALESCE(`github_update_available`, 'N'),
  `github_latest_sha` = `github_latest_sha`,
  `github_latest_url` = `github_latest_url`,
  `github_latest_message` = `github_latest_message`;
