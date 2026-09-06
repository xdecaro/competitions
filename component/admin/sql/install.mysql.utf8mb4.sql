CREATE TABLE IF NOT EXISTS `#__dcl_countries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(190) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `iso2` CHAR(2) DEFAULT NULL,
  `iso3` CHAR(3) DEFAULT NULL,
  `entity_type` VARCHAR(32) NOT NULL DEFAULT 'country',
  `flag` VARCHAR(512) DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_countries_code` (`code`),
  KEY `idx_dcl_countries_name` (`name`),
  KEY `idx_dcl_countries_state` (`state`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__dcl_countries` (`name`, `code`, `iso2`, `iso3`, `entity_type`, `state`, `ordering`) VALUES
('Azerbaijan', 'AZE', 'AZ', 'AZE', 'country', 1, 1),
('Belgium', 'BEL', 'BE', 'BEL', 'country', 1, 2),
('Czech Republic', 'CZE', 'CZ', 'CZE', 'country', 1, 3),
('England', 'ENG', NULL, NULL, 'sport_territory', 1, 4),
('France', 'FRA', 'FR', 'FRA', 'country', 1, 5),
('Germany', 'GER', 'DE', 'DEU', 'country', 1, 6),
('Greece', 'GRE', 'GR', 'GRC', 'country', 1, 7),
('Hungary', 'HUN', 'HU', 'HUN', 'country', 1, 8),
('Israel', 'ISR', 'IL', 'ISR', 'country', 1, 9),
('Italy', 'ITA', 'IT', 'ITA', 'country', 1, 10),
('Netherlands', 'NED', 'NL', 'NLD', 'country', 1, 11),
('Norway', 'NOR', 'NO', 'NOR', 'country', 1, 12),
('Poland', 'POL', 'PL', 'POL', 'country', 1, 13),
('Portugal', 'POR', 'PT', 'PRT', 'country', 1, 14),
('Romania', 'ROU', 'RO', 'ROU', 'country', 1, 15),
('Serbia', 'SRB', 'RS', 'SRB', 'country', 1, 16),
('Spain', 'ESP', 'ES', 'ESP', 'country', 1, 17),
('Sweden', 'SWE', 'SE', 'SWE', 'country', 1, 18),
('Turkey', 'TUR', 'TR', 'TUR', 'country', 1, 19);

CREATE TABLE IF NOT EXISTS `#__dcl_federations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `country_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(190) NOT NULL,
  `short_name` VARCHAR(100) DEFAULT NULL,
  `logo` VARCHAR(512) DEFAULT NULL,
  `website` VARCHAR(512) DEFAULT NULL,
  `email` VARCHAR(190) DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_dcl_federations_country_id` (`country_id`),
  KEY `idx_dcl_federations_state` (`state`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_teams` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `federation_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(190) NOT NULL,
  `short_name` VARCHAR(100) DEFAULT NULL,
  `alias` VARCHAR(190) DEFAULT NULL,
  `logo` VARCHAR(512) DEFAULT NULL,
  `country_code` CHAR(3) DEFAULT NULL,
  `city` VARCHAR(190) DEFAULT NULL,
  `email` VARCHAR(190) DEFAULT NULL,
  `phone` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(512) DEFAULT NULL,
  `approval_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `approved_at` DATETIME DEFAULT NULL,
  `approved_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `rejection_reason` TEXT DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_teams_alias` (`alias`),
  KEY `idx_dcl_teams_owner` (`owner_user_id`),
  KEY `idx_dcl_teams_federation` (`federation_id`),
  KEY `idx_dcl_teams_status` (`approval_status`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_players` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_ref` VARCHAR(100) DEFAULT NULL,
  `first_name` VARCHAR(190) NOT NULL,
  `last_name` VARCHAR(190) NOT NULL,
  `birth_date` DATE DEFAULT NULL,
  `nationality_code` CHAR(3) DEFAULT NULL,
  `photo` VARCHAR(512) DEFAULT NULL,
  `approval_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `state` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_players_external_ref` (`external_ref`),
  KEY `idx_dcl_players_name` (`last_name`, `first_name`),
  KEY `idx_dcl_players_status` (`approval_status`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_tournaments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(190) NOT NULL,
  `code` VARCHAR(50) DEFAULT NULL,
  `discipline` VARCHAR(50) DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_tournaments_code` (`code`),
  KEY `idx_dcl_tournaments_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_seasons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(190) NOT NULL,
  `season_year` SMALLINT UNSIGNED DEFAULT NULL,
  `host_city` VARCHAR(190) DEFAULT NULL,
  `host_country_code` CHAR(3) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_dcl_seasons_tournament` (`tournament_id`),
  KEY `idx_dcl_seasons_year` (`season_year`),
  KEY `idx_dcl_seasons_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_participations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `team_id` INT UNSIGNED NOT NULL,
  `season_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
  `submitted_at` DATETIME DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `reviewed_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `review_note` TEXT DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_participation_team_season` (`team_id`, `season_id`),
  KEY `idx_dcl_participation_status` (`status`, `state`),
  KEY `idx_dcl_participation_season` (`season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_rosters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `participation_id` BIGINT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `player_id` BIGINT UNSIGNED NOT NULL,
  `shirt_number` SMALLINT UNSIGNED DEFAULT NULL,
  `role` VARCHAR(50) DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `review_note` TEXT DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_roster_participation_player` (`participation_id`, `player_id`),
  KEY `idx_dcl_roster_team` (`team_id`),
  KEY `idx_dcl_roster_player` (`player_id`),
  KEY `idx_dcl_roster_status` (`status`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_venues` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(190) NOT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(190) DEFAULT NULL,
  `country_code` CHAR(3) DEFAULT NULL,
  `map_url` VARCHAR(512) DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_dcl_venues_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_match_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `player_id` BIGINT UNSIGNED DEFAULT NULL,
  `player_name_override` VARCHAR(255) DEFAULT NULL,
  `side` VARCHAR(8) NOT NULL,
  `event_type` VARCHAR(32) NOT NULL,
  `minute` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `extra_minute` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `period` VARCHAR(32) DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_dcl_events_article` (`article_id`, `state`),
  KEY `idx_dcl_events_player` (`player_id`),
  KEY `idx_dcl_events_team` (`team_id`),
  KEY `idx_dcl_events_type` (`event_type`),
  KEY `idx_dcl_events_timeline` (`article_id`, `minute`, `extra_minute`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_coefficient_rules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(190) NOT NULL,
  `tournament_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `season_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `effective_from` DATE DEFAULT NULL,
  `effective_to` DATE DEFAULT NULL,
  `country_share_percent` DECIMAL(6,3) NOT NULL DEFAULT 20.000,
  `ranking_seasons` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `qualification_factor` DECIMAL(6,3) NOT NULL DEFAULT 0.500,
  `rules_json` MEDIUMTEXT DEFAULT NULL,
  `source_url` VARCHAR(1024) DEFAULT NULL,
  `source_date` DATE DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_dcl_coef_rules_scope` (`tournament_id`, `season_id`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_country_coefficients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_id` INT UNSIGNED NOT NULL,
  `federation_id` INT UNSIGNED NOT NULL,
  `pq_points` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `pc_points` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `pod_points` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `participating_clubs` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `coefficient` DECIMAL(14,6) NOT NULL DEFAULT 0,
  `rule_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `calculated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_country_coef_unique` (`season_id`, `federation_id`),
  KEY `idx_dcl_country_coef_value` (`season_id`, `coefficient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_club_coefficients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_id` INT UNSIGNED NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `federation_id` INT UNSIGNED NOT NULL,
  `pc_points` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `bonusq_points` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `pod_points` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `country_coefficient` DECIMAL(14,6) NOT NULL DEFAULT 0,
  `country_share` DECIMAL(14,6) NOT NULL DEFAULT 0,
  `coefficient` DECIMAL(14,6) NOT NULL DEFAULT 0,
  `rule_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `calculated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_club_coef_unique` (`season_id`, `team_id`),
  KEY `idx_dcl_club_coef_value` (`season_id`, `coefficient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__dcl_rankings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` INT UNSIGNED NOT NULL,
  `ranking_type` VARCHAR(16) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `season_end_id` INT UNSIGNED NOT NULL,
  `seasons_count` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `coefficient_total` DECIMAL(16,6) NOT NULL DEFAULT 0,
  `position` INT UNSIGNED DEFAULT NULL,
  `calculated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_dcl_rankings_unique` (`tournament_id`, `ranking_type`, `entity_id`, `season_end_id`),
  KEY `idx_dcl_rankings_order` (`tournament_id`, `ranking_type`, `season_end_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
