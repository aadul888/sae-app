CREATE TABLE IF NOT EXISTS `commit_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `commit_hash` varchar(40) NOT NULL DEFAULT '',
  `commit_message` text,
  `commit_message_bahasa` text COMMENT 'Terjemahan pesan commit ke bahasa Indonesia umum',
  `author` varchar(100) NOT NULL DEFAULT '',
  `committed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `commit_hash` (`commit_hash`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
