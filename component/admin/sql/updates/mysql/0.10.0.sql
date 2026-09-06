ALTER TABLE `#__dcl_tournaments`
  ADD COLUMN `scope_type` VARCHAR(20) NOT NULL DEFAULT 'international' AFTER `gender`,
  ADD COLUMN `participant_type` VARCHAR(20) NOT NULL DEFAULT 'club' AFTER `scope_type`,
  ADD COLUMN `local_area` VARCHAR(190) DEFAULT NULL AFTER `participant_type`,
  ADD KEY `idx_dcl_tournaments_scope` (`scope_type`, `participant_type`);

ALTER TABLE `#__dcl_teams`
  ADD COLUMN `team_type` VARCHAR(20) NOT NULL DEFAULT 'club' AFTER `federation_id`,
  ADD KEY `idx_dcl_teams_type` (`team_type`, `approval_status`, `state`);

CREATE TABLE IF NOT EXISTS `#__dcl_tournament_countries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` INT UNSIGNED NOT NULL,
  `country_id` INT UNSIGNED NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_tournament_country_unique` (`tournament_id`, `country_id`),
  KEY `idx_dcl_tournament_country_country` (`country_id`, `tournament_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_tournament_zones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` INT UNSIGNED NOT NULL,
  `zone_id` INT UNSIGNED NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_tournament_zone_unique` (`tournament_id`, `zone_id`),
  KEY `idx_dcl_tournament_zone_zone` (`zone_id`, `tournament_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_changes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(32) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(16) NOT NULL DEFAULT 'update',
  `changed_at` DATETIME NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `client_id` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dcl_changes_entity` (`entity_type`, `entity_id`, `id`),
  KEY `idx_dcl_changes_changed_at` (`changed_at`),
  KEY `idx_dcl_changes_client` (`client_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_edit_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(32) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `client_id` VARCHAR(64) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `touched_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_edit_session_unique` (`entity_type`, `entity_id`, `client_id`),
  KEY `idx_dcl_edit_session_touched` (`touched_at`),
  KEY `idx_dcl_edit_session_user` (`user_id`, `touched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
