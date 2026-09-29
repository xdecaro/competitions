CREATE TABLE IF NOT EXISTS `#__xdecarocompetitions_draw_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_id` INT UNSIGNED NOT NULL,
  `draw_id` INT UNSIGNED NOT NULL,
  `request_schema` VARCHAR(64) NOT NULL DEFAULT 'xdecaro.draw.request.v1',
  `result_schema` VARCHAR(64) NOT NULL DEFAULT 'xdecaro.draw.result.v1',
  `request_hash` CHAR(64) NOT NULL,
  `group_count` SMALLINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'ready',
  `state` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME DEFAULT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_competitions_draw_links_draw_id` (`draw_id`),
  KEY `idx_competitions_draw_links_season` (`season_id`, `state`, `id`),
  KEY `idx_competitions_draw_links_hash` (`season_id`, `request_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;