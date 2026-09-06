CREATE TABLE IF NOT EXISTS `#__dcl_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `action` VARCHAR(64) NOT NULL,
  `entity_type` VARCHAR(64) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `context_json` MEDIUMTEXT DEFAULT NULL,
  `ip_hash` CHAR(64) DEFAULT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dcl_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_dcl_audit_user` (`user_id`, `created`),
  KEY `idx_dcl_audit_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
