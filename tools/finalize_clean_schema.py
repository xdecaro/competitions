from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SQL = ROOT / "component/admin/sql/install.mysql.utf8mb4.sql"
SERVICE = ROOT / "component/admin/src/Service/CoreIntegrationService.php"
VALIDATOR = ROOT / "tools/validate_release.py"

sql = SQL.read_text(encoding="utf-8")

# Fold the former 0.10 fields directly into the canonical fresh table definitions.
old = "  `federation_id` INT UNSIGNED NOT NULL DEFAULT 0,\n  `name` VARCHAR(190) NOT NULL,"
new = "  `federation_id` INT UNSIGNED NOT NULL DEFAULT 0,\n  `team_type` VARCHAR(20) NOT NULL DEFAULT 'club',\n  `name` VARCHAR(190) NOT NULL,"
if old not in sql and "`team_type` VARCHAR(20)" not in sql:
    raise SystemExit("Unable to add team_type to canonical teams table")
sql = sql.replace(old, new, 1)

old = "  KEY `idx_competitions_teams_federation` (`federation_id`),\n  KEY `idx_competitions_teams_status`"
new = "  KEY `idx_competitions_teams_federation` (`federation_id`),\n  KEY `idx_competitions_teams_type` (`team_type`, `approval_status`, `state`),\n  KEY `idx_competitions_teams_status`"
if old not in sql and "idx_competitions_teams_type" not in sql:
    raise SystemExit("Unable to add team type index")
sql = sql.replace(old, new, 1)

old = "  `gender` VARCHAR(20) DEFAULT NULL,\n  `state` TINYINT NOT NULL DEFAULT 1,"
new = "  `gender` VARCHAR(20) DEFAULT NULL,\n  `scope_type` VARCHAR(20) NOT NULL DEFAULT 'international',\n  `participant_type` VARCHAR(20) NOT NULL DEFAULT 'club',\n  `local_area` VARCHAR(190) DEFAULT NULL,\n  `state` TINYINT NOT NULL DEFAULT 1,"
if old not in sql and "`scope_type` VARCHAR(20)" not in sql:
    raise SystemExit("Unable to add tournament scope columns")
sql = sql.replace(old, new, 1)

old = "  UNIQUE KEY `idx_competitions_tournaments_code` (`code`),\n  KEY `idx_competitions_tournaments_state`"
new = "  UNIQUE KEY `idx_competitions_tournaments_code` (`code`),\n  KEY `idx_competitions_tournaments_scope` (`scope_type`, `participant_type`),\n  KEY `idx_competitions_tournaments_state`"
if old not in sql and "idx_competitions_tournaments_scope" not in sql:
    raise SystemExit("Unable to add tournament scope index")
sql = sql.replace(old, new, 1)

old = "  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n  `article_id` INT UNSIGNED NOT NULL,\n  `team_id` INT UNSIGNED NOT NULL DEFAULT 0,"
new = "  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n  `match_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,\n  `article_id` INT UNSIGNED NOT NULL DEFAULT 0,\n  `team_id` INT UNSIGNED NOT NULL DEFAULT 0,"
if old not in sql and "`match_id` BIGINT UNSIGNED" not in sql:
    raise SystemExit("Unable to fold match_id into match_events")
sql = sql.replace(old, new, 1)

old = "  PRIMARY KEY (`id`),\n  KEY `idx_competitions_events_article` (`article_id`, `state`),"
new = "  PRIMARY KEY (`id`),\n  KEY `idx_competitions_events_match` (`match_id`, `state`),\n  KEY `idx_competitions_events_match_timeline` (`match_id`, `minute`, `extra_minute`, `ordering`),\n  KEY `idx_competitions_events_article` (`article_id`, `state`),"
if old not in sql and "idx_competitions_events_match_timeline" not in sql:
    raise SystemExit("Unable to add match event indexes")
sql = sql.replace(old, new, 1)

extra = r'''

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
'''

if "#__xdecarocompetitions_organizations" not in sql:
    sql = sql.rstrip() + extra + "\n"

# The canonical 1.0 SQL must describe the final schema directly, not replay old ALTER migrations.
if "ALTER TABLE" in sql.upper():
    raise SystemExit("Canonical 1.0 install SQL unexpectedly contains ALTER TABLE")

SQL.write_text(sql, encoding="utf-8")

service = SERVICE.read_text(encoding="utf-8")
service = service.replace(
    " * The historical Joomla component element remains com_xdecarocompetitions and must be\n * used in public entity references for upgrade compatibility.\n",
    " * Public cross-product references use the stable 1.x component identifier\n * com_xdecarocompetitions.\n",
)
SERVICE.write_text(service, encoding="utf-8")

validator = VALIDATOR.read_text(encoding="utf-8")
needle = '    if "#__xdecarocompetitions_" not in sql:\n        fail("fresh-install SQL does not use #__xdecarocompetitions_ tables")\n'
addition = needle + '''    required_schema_tokens = [
        "#__xdecarocompetitions_organizations",
        "#__xdecarocompetitions_tournament_organizations",
        "#__xdecarocompetitions_season_organizations",
        "#__xdecarocompetitions_matches",
        "#__xdecarocompetitions_zones",
        "#__xdecarocompetitions_zone_countries",
        "#__xdecarocompetitions_tournament_countries",
        "#__xdecarocompetitions_tournament_zones",
        "#__xdecarocompetitions_changes",
        "#__xdecarocompetitions_edit_sessions",
        "`team_type` VARCHAR(20)",
        "`scope_type` VARCHAR(20)",
        "`participant_type` VARCHAR(20)",
        "`match_id` BIGINT UNSIGNED",
    ]
    for token in required_schema_tokens:
        if token not in sql:
            fail(f"canonical fresh-install schema is missing {token}")
    if "ALTER TABLE" in sql.upper():
        fail("canonical 1.0 fresh-install schema must not replay legacy ALTER migrations")
'''
if needle not in validator:
    raise SystemExit("Unable to extend canonical schema validation")
validator = validator.replace(needle, addition, 1)
VALIDATOR.write_text(validator, encoding="utf-8")

print("Complete Competitions 1.0.0 fresh-install schema consolidated")
