-- Competitions 1.5.7
-- Optional stable link from a Competition club team to its canonical Organizations club.
-- Existing teams remain valid and unlinked; no team data is deleted or rewritten.

ALTER TABLE `#__xdecarocompetitions_teams`
  ADD COLUMN `organization_uuid` CHAR(36) NULL AFTER `id`,
  ADD UNIQUE KEY `uq_competitions_teams_organization_uuid` (`organization_uuid`);
