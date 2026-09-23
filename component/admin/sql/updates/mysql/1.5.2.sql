-- Competitions 1.5.2
-- Optional stable link from a Competition federation row to its canonical Organizations record.
-- Existing rows remain valid and unlinked; no data is deleted or rewritten.

ALTER TABLE `#__xdecarocompetitions_federations`
  ADD COLUMN `organization_uuid` CHAR(36) NULL AFTER `id`,
  ADD UNIQUE KEY `uq_competitions_federations_organization_uuid` (`organization_uuid`);
