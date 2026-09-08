CREATE TABLE IF NOT EXISTS `#__decarocompetitions_organizations` (
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

CREATE TABLE IF NOT EXISTS `#__decarocompetitions_tournament_organizations` (
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

CREATE TABLE IF NOT EXISTS `#__decarocompetitions_season_organizations` (
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

CREATE TABLE IF NOT EXISTS `#__decarocompetitions_matches` (
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

ALTER TABLE `#__decarocompetitions_match_events`
  ADD COLUMN `match_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`,
  MODIFY COLUMN `article_id` INT UNSIGNED NOT NULL DEFAULT 0,
  ADD KEY `idx_competitions_events_match` (`match_id`, `state`),
  ADD KEY `idx_competitions_events_match_timeline` (`match_id`, `minute`, `extra_minute`, `ordering`);

UPDATE `#__decarocompetitions_match_events` AS e
INNER JOIN `#__decarocompetitions_matches` AS m ON m.`article_id` = e.`article_id`
SET e.`match_id` = m.`id`
WHERE e.`match_id` = 0 AND e.`article_id` > 0;
