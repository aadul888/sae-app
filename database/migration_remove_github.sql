-- Migration: Remove GitHub tracking columns (replaced by pembaharuan table)
-- Run this after confirming no code references github_* columns

-- MySQL 8.4: ALTER TABLE DROP COLUMN IF EXISTS not supported.
-- Check existence first, then drop.

SELECT COUNT(*) INTO @cnt FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='setting'
    AND COLUMN_NAME IN ('github_last_sha','github_last_check','github_update_available',
                        'github_latest_sha','github_latest_url','github_latest_message');

SET @sql = IF(@cnt > 0,
  'ALTER TABLE `setting`
    DROP COLUMN `github_last_sha`,
    DROP COLUMN `github_last_check`,
    DROP COLUMN `github_update_available`,
    DROP COLUMN `github_latest_sha`,
    DROP COLUMN `github_latest_url`,
    DROP COLUMN `github_latest_message`',
  'SELECT "Columns already removed" AS status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
