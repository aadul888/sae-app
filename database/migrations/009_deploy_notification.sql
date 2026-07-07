ALTER TABLE `setting`
  ADD COLUMN `last_deploy_at` datetime DEFAULT NULL AFTER `last_sync_at`,
  ADD COLUMN `last_deploy_commit` varchar(40) NOT NULL DEFAULT '' AFTER `last_deploy_at`;
