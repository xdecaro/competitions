CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_countries` (
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
  UNIQUE KEY `idx_competitions_countries_code` (`code`),
  KEY `idx_competitions_countries_name` (`name`),
  KEY `idx_competitions_countries_state` (`state`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__xdecarocompetitions_countries` (`name`, `code`, `iso2`, `iso3`, `entity_type`, `state`, `ordering`) VALUES
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

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_federations` (
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
  KEY `idx_competitions_federations_country_id` (`country_id`),
  KEY `idx_competitions_federations_state` (`state`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_teams` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `federation_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `team_type` VARCHAR(20) NOT NULL DEFAULT 'club',
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
  UNIQUE KEY `idx_competitions_teams_alias` (`alias`),
  KEY `idx_competitions_teams_owner` (`owner_user_id`),
  KEY `idx_competitions_teams_federation` (`federation_id`),
  KEY `idx_competitions_teams_type` (`team_type`, `approval_status`, `state`),
  KEY `idx_competitions_teams_status` (`approval_status`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_players` (
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
  UNIQUE KEY `idx_competitions_players_external_ref` (`external_ref`),
  KEY `idx_competitions_players_name` (`last_name`, `first_name`),
  KEY `idx_competitions_players_status` (`approval_status`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_tournaments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(190) NOT NULL,
  `code` VARCHAR(50) DEFAULT NULL,
  `discipline` VARCHAR(50) DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT NULL,
  `scope_type` VARCHAR(20) NOT NULL DEFAULT 'international',
  `participant_type` VARCHAR(20) NOT NULL DEFAULT 'club',
  `local_area` VARCHAR(190) DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_tournaments_code` (`code`),
  KEY `idx_competitions_tournaments_scope` (`scope_type`, `participant_type`),
  KEY `idx_competitions_tournaments_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_seasons` (
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
  KEY `idx_competitions_seasons_tournament` (`tournament_id`),
  KEY `idx_competitions_seasons_year` (`season_year`),
  KEY `idx_competitions_seasons_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_participations` (
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
  UNIQUE KEY `idx_competitions_participation_team_season` (`team_id`, `season_id`),
  KEY `idx_competitions_participation_status` (`status`, `state`),
  KEY `idx_competitions_participation_season` (`season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_rosters` (
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
  UNIQUE KEY `idx_competitions_roster_participation_player` (`participation_id`, `player_id`),
  KEY `idx_competitions_roster_team` (`team_id`),
  KEY `idx_competitions_roster_player` (`player_id`),
  KEY `idx_competitions_roster_status` (`status`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_venues` (
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
  KEY `idx_competitions_venues_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_match_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `match_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `article_id` INT UNSIGNED NOT NULL DEFAULT 0,
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
  KEY `idx_competitions_events_match` (`match_id`, `state`),
  KEY `idx_competitions_events_match_timeline` (`match_id`, `minute`, `extra_minute`, `ordering`),
  KEY `idx_competitions_events_article` (`article_id`, `state`),
  KEY `idx_competitions_events_player` (`player_id`),
  KEY `idx_competitions_events_team` (`team_id`),
  KEY `idx_competitions_events_type` (`event_type`),
  KEY `idx_competitions_events_timeline` (`article_id`, `minute`, `extra_minute`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_coefficient_rules` (
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
  KEY `idx_competitions_coef_rules_scope` (`tournament_id`, `season_id`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_country_coefficients` (
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
  UNIQUE KEY `idx_competitions_country_coef_unique` (`season_id`, `federation_id`),
  KEY `idx_competitions_country_coef_value` (`season_id`, `coefficient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_club_coefficients` (
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
  UNIQUE KEY `idx_competitions_club_coef_unique` (`season_id`, `team_id`),
  KEY `idx_competitions_club_coef_value` (`season_id`, `coefficient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_rankings` (
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
  UNIQUE KEY `idx_competitions_rankings_unique` (`tournament_id`, `ranking_type`, `entity_id`, `season_end_id`),
  KEY `idx_competitions_rankings_order` (`tournament_id`, `ranking_type`, `season_end_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `action` VARCHAR(64) NOT NULL,
  `entity_type` VARCHAR(64) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `context_json` MEDIUMTEXT DEFAULT NULL,
  `ip_hash` CHAR(64) DEFAULT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_competitions_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_competitions_audit_user` (`user_id`, `created`),
  KEY `idx_competitions_audit_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_organizations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(190) NOT NULL,
  `short_name` VARCHAR(100) DEFAULT NULL,
  `country_id` INT UNSIGNED NOT NULL DEFAULT 0,
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
  KEY `idx_competitions_organizations_country` (`country_id`),
  KEY `idx_competitions_organizations_name` (`name`),
  KEY `idx_competitions_organizations_state` (`state`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_tournament_organizations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` INT UNSIGNED NOT NULL,
  `organization_id` INT UNSIGNED NOT NULL,
  `role` VARCHAR(32) NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_tournament_org_unique` (`tournament_id`, `organization_id`, `role`),
  KEY `idx_competitions_tournament_org_role` (`tournament_id`, `role`, `ordering`),
  KEY `idx_competitions_tournament_org_organization` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_season_organizations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_id` INT UNSIGNED NOT NULL,
  `organization_id` INT UNSIGNED NOT NULL,
  `role` VARCHAR(32) NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_season_org_unique` (`season_id`, `organization_id`, `role`),
  KEY `idx_competitions_season_org_role` (`season_id`, `role`, `ordering`),
  KEY `idx_competitions_season_org_organization` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_matches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_id` INT UNSIGNED NOT NULL,
  `home_team_id` INT UNSIGNED NOT NULL,
  `away_team_id` INT UNSIGNED NOT NULL,
  `venue_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `article_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `match_date` DATE DEFAULT NULL,
  `kickoff_time` TIME DEFAULT NULL,
  `stage` VARCHAR(100) DEFAULT NULL,
  `group_name` VARCHAR(100) DEFAULT NULL,
  `round_name` VARCHAR(100) DEFAULT NULL,
  `matchday` SMALLINT UNSIGNED DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'scheduled',
  `home_score` SMALLINT UNSIGNED DEFAULT NULL,
  `away_score` SMALLINT UNSIGNED DEFAULT NULL,
  `home_score_extra` SMALLINT UNSIGNED DEFAULT NULL,
  `away_score_extra` SMALLINT UNSIGNED DEFAULT NULL,
  `home_penalties` SMALLINT UNSIGNED DEFAULT NULL,
  `away_penalties` SMALLINT UNSIGNED DEFAULT NULL,
  `winner_team_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `attendance` INT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_competitions_matches_season_status` (`season_id`, `status`, `state`),
  KEY `idx_competitions_matches_date` (`match_date`, `kickoff_time`),
  KEY `idx_competitions_matches_home_team` (`home_team_id`, `season_id`),
  KEY `idx_competitions_matches_away_team` (`away_team_id`, `season_id`),
  KEY `idx_competitions_matches_venue` (`venue_id`),
  KEY `idx_competitions_matches_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_zones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(190) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME DEFAULT NULL,
  `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_zones_scope_code` (`organization_id`, `code`),
  KEY `idx_competitions_zones_organization` (`organization_id`),
  KEY `idx_competitions_zones_state` (`state`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_zone_countries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `zone_id` INT UNSIGNED NOT NULL,
  `country_id` INT UNSIGNED NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_zone_country_unique` (`zone_id`, `country_id`),
  KEY `idx_competitions_zone_country_zone` (`zone_id`, `ordering`),
  KEY `idx_competitions_zone_country_country` (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_tournament_countries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` INT UNSIGNED NOT NULL,
  `country_id` INT UNSIGNED NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_tournament_country_unique` (`tournament_id`, `country_id`),
  KEY `idx_competitions_tournament_country_country` (`country_id`, `tournament_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_tournament_zones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` INT UNSIGNED NOT NULL,
  `zone_id` INT UNSIGNED NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_tournament_zone_unique` (`tournament_id`, `zone_id`),
  KEY `idx_competitions_tournament_zone_zone` (`zone_id`, `tournament_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_changes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(32) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(16) NOT NULL DEFAULT 'update',
  `changed_at` DATETIME NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL DEFAULT 0,
  `client_id` VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_competitions_changes_entity` (`entity_type`, `entity_id`, `id`),
  KEY `idx_competitions_changes_changed_at` (`changed_at`),
  KEY `idx_competitions_changes_client` (`client_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_edit_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(32) NOT NULL,
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `client_id` VARCHAR(64) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `touched_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_edit_session_unique` (`entity_type`, `entity_id`, `client_id`),
  KEY `idx_competitions_edit_session_touched` (`touched_at`),
  KEY `idx_competitions_edit_session_user` (`user_id`, `touched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__xdecarocompetitions_countries` (`name`, `code`, `iso2`, `iso3`, `entity_type`, `state`, `ordering`) VALUES
('Cameroon', 'CMR', 'CM', 'CMR', 'country', 1, 20),
('Nigeria', 'NGA', 'NG', 'NGA', 'country', 1, 21),
('South Africa', 'RSA', 'ZA', 'ZAF', 'country', 1, 22),
('Australia', 'AUS', 'AU', 'AUS', 'country', 1, 23),
('China PR', 'CHN', 'CN', 'CHN', 'country', 1, 24),
('Japan', 'JPN', 'JP', 'JPN', 'country', 1, 25),
('Korea Republic', 'KOR', 'KR', 'KOR', 'country', 1, 26),
('Thailand', 'THA', 'TH', 'THA', 'country', 1, 27),
('Scotland', 'SCO', NULL, NULL, 'sport_territory', 1, 28),
('Canada', 'CAN', 'CA', 'CAN', 'country', 1, 29),
('Jamaica', 'JAM', 'JM', 'JAM', 'country', 1, 30),
('USA', 'USA', 'US', 'USA', 'country', 1, 31),
('New Zealand', 'NZL', 'NZ', 'NZL', 'country', 1, 32),
('Argentina', 'ARG', 'AR', 'ARG', 'country', 1, 33),
('Brazil', 'BRA', 'BR', 'BRA', 'country', 1, 34),
('Chile', 'CHI', 'CL', 'CHL', 'country', 1, 35);

INSERT IGNORE INTO `#__xdecarocompetitions_zones` (`organization_id`, `name`, `code`, `state`, `ordering`) VALUES
(0, 'African zone', 'AFR', 1, 1),
(0, 'Asian zone', 'ASIA', 1, 2),
(0, 'European zone', 'EUR', 1, 3),
(0, 'North, Central American and Caribbean zone', 'NCAC', 1, 4),
(0, 'Oceania zone', 'OCE', 1, 5),
(0, 'South American zone', 'SAM', 1, 6);

INSERT IGNORE INTO `#__xdecarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'CMR', 'NGA', 'RSA')
FROM `#__xdecarocompetitions_zones` AS z
INNER JOIN `#__xdecarocompetitions_countries` AS c ON c.`code` IN ('CMR', 'NGA', 'RSA')
WHERE z.`organization_id` = 0 AND z.`code` = 'AFR';

INSERT IGNORE INTO `#__xdecarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'AUS', 'CHN', 'JPN', 'KOR', 'THA')
FROM `#__xdecarocompetitions_zones` AS z
INNER JOIN `#__xdecarocompetitions_countries` AS c ON c.`code` IN ('AUS', 'CHN', 'JPN', 'KOR', 'THA')
WHERE z.`organization_id` = 0 AND z.`code` = 'ASIA';

INSERT IGNORE INTO `#__xdecarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'ENG', 'FRA', 'GER', 'ITA', 'NED', 'NOR', 'SCO', 'ESP', 'SWE')
FROM `#__xdecarocompetitions_zones` AS z
INNER JOIN `#__xdecarocompetitions_countries` AS c ON c.`code` IN ('ENG', 'FRA', 'GER', 'ITA', 'NED', 'NOR', 'SCO', 'ESP', 'SWE')
WHERE z.`organization_id` = 0 AND z.`code` = 'EUR';

INSERT IGNORE INTO `#__xdecarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'CAN', 'JAM', 'USA')
FROM `#__xdecarocompetitions_zones` AS z
INNER JOIN `#__xdecarocompetitions_countries` AS c ON c.`code` IN ('CAN', 'JAM', 'USA')
WHERE z.`organization_id` = 0 AND z.`code` = 'NCAC';

INSERT IGNORE INTO `#__xdecarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'NZL')
FROM `#__xdecarocompetitions_zones` AS z
INNER JOIN `#__xdecarocompetitions_countries` AS c ON c.`code` IN ('NZL')
WHERE z.`organization_id` = 0 AND z.`code` = 'OCE';

INSERT IGNORE INTO `#__xdecarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'ARG', 'BRA', 'CHI')
FROM `#__xdecarocompetitions_zones` AS z
INNER JOIN `#__xdecarocompetitions_countries` AS c ON c.`code` IN ('ARG', 'BRA', 'CHI')
WHERE z.`organization_id` = 0 AND z.`code` = 'SAM';

