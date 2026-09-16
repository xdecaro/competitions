ALTER TABLE `#__xdecarocompetitions_players`
  ADD COLUMN `person_uuid` CHAR(36) NULL AFTER `id`,
  ADD UNIQUE KEY `uq_player_person_uuid` (`person_uuid`);

ALTER TABLE `#__xdecarocompetitions_rosters`
  ADD COLUMN `photo` VARCHAR(512) NULL AFTER `role`;
