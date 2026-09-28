ALTER TABLE `#__xdecarocompetitions_seasons`
  ADD COLUMN `workflow_status` VARCHAR(32) NOT NULL DEFAULT 'draft' AFTER `end_date`;
