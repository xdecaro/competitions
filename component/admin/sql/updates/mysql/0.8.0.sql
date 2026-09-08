CREATE TABLE IF NOT EXISTS `#__decarocompetitions_zones` (
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

CREATE TABLE IF NOT EXISTS `#__decarocompetitions_zone_countries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `zone_id` INT UNSIGNED NOT NULL,
  `country_id` INT UNSIGNED NOT NULL,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_competitions_zone_country_unique` (`zone_id`, `country_id`),
  KEY `idx_competitions_zone_country_zone` (`zone_id`, `ordering`),
  KEY `idx_competitions_zone_country_country` (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__decarocompetitions_countries` (`name`, `code`, `iso2`, `iso3`, `entity_type`, `state`, `ordering`) VALUES
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

INSERT IGNORE INTO `#__decarocompetitions_zones` (`organization_id`, `name`, `code`, `state`, `ordering`) VALUES
(0, 'African zone', 'AFR', 1, 1),
(0, 'Asian zone', 'ASIA', 1, 2),
(0, 'European zone', 'EUR', 1, 3),
(0, 'North, Central American and Caribbean zone', 'NCAC', 1, 4),
(0, 'Oceania zone', 'OCE', 1, 5),
(0, 'South American zone', 'SAM', 1, 6);

INSERT IGNORE INTO `#__decarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'CMR', 'NGA', 'RSA')
FROM `#__decarocompetitions_zones` AS z
INNER JOIN `#__decarocompetitions_countries` AS c ON c.`code` IN ('CMR', 'NGA', 'RSA')
WHERE z.`organization_id` = 0 AND z.`code` = 'AFR';

INSERT IGNORE INTO `#__decarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'AUS', 'CHN', 'JPN', 'KOR', 'THA')
FROM `#__decarocompetitions_zones` AS z
INNER JOIN `#__decarocompetitions_countries` AS c ON c.`code` IN ('AUS', 'CHN', 'JPN', 'KOR', 'THA')
WHERE z.`organization_id` = 0 AND z.`code` = 'ASIA';

INSERT IGNORE INTO `#__decarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'ENG', 'FRA', 'GER', 'ITA', 'NED', 'NOR', 'SCO', 'ESP', 'SWE')
FROM `#__decarocompetitions_zones` AS z
INNER JOIN `#__decarocompetitions_countries` AS c ON c.`code` IN ('ENG', 'FRA', 'GER', 'ITA', 'NED', 'NOR', 'SCO', 'ESP', 'SWE')
WHERE z.`organization_id` = 0 AND z.`code` = 'EUR';

INSERT IGNORE INTO `#__decarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'CAN', 'JAM', 'USA')
FROM `#__decarocompetitions_zones` AS z
INNER JOIN `#__decarocompetitions_countries` AS c ON c.`code` IN ('CAN', 'JAM', 'USA')
WHERE z.`organization_id` = 0 AND z.`code` = 'NCAC';

INSERT IGNORE INTO `#__decarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'NZL')
FROM `#__decarocompetitions_zones` AS z
INNER JOIN `#__decarocompetitions_countries` AS c ON c.`code` IN ('NZL')
WHERE z.`organization_id` = 0 AND z.`code` = 'OCE';

INSERT IGNORE INTO `#__decarocompetitions_zone_countries` (`zone_id`, `country_id`, `ordering`)
SELECT z.`id`, c.`id`, FIELD(c.`code`, 'ARG', 'BRA', 'CHI')
FROM `#__decarocompetitions_zones` AS z
INNER JOIN `#__decarocompetitions_countries` AS c ON c.`code` IN ('ARG', 'BRA', 'CHI')
WHERE z.`organization_id` = 0 AND z.`code` = 'SAM';
