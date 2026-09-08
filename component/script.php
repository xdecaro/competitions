<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * Competitions component schema migration helper.
 *
 * Fresh installations use #__decarocompetitions_* tables. During an update,
 * older DCL-prefixed tables are renamed before Joomla applies schema updates.
 */
final class ComDecarodclInstallerScript
{
    public function preflight(string $type, $parent): bool
    {
        if ($type !== 'update') {
            return true;
        }

        return $this->migrateLegacyTablePrefix();
    }

    public function install($parent): bool
    {
        return $this->migrateLegacySchema();
    }

    public function update($parent): bool
    {
        return $this->migrateLegacySchema();
    }

    public function uninstall($parent): bool
    {
        return true;
    }

    private function migrateLegacyTablePrefix(): bool
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $sitePrefix = $db->getPrefix();
            $legacyPrefix = $sitePrefix . 'dcl_';
            $currentPrefix = $sitePrefix . 'decarocompetitions_';
            $tables = $db->getTableList();
            $existing = array_fill_keys($tables, true);
            $renames = [];

            foreach ($tables as $table) {
                if (!str_starts_with($table, $legacyPrefix)) {
                    continue;
                }

                $suffix = substr($table, strlen($legacyPrefix));
                $target = $currentPrefix . $suffix;

                if (isset($existing[$target])) {
                    Log::add(
                        'Competitions database migration stopped because both legacy and current tables exist for ' . $suffix,
                        Log::ERROR,
                        'competitions'
                    );

                    return false;
                }

                $renames[] = $db->quoteName($table) . ' TO ' . $db->quoteName($target);
            }

            if ($renames !== []) {
                $db->setQuery('RENAME TABLE ' . implode(', ', $renames))->execute();
            }

            return true;
        } catch (Throwable $e) {
            Log::add('Competitions table-prefix migration failed: ' . $e->getMessage(), Log::ERROR, 'competitions');

            return false;
        }
    }

    private function migrateLegacySchema(): bool
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $table = $db->replacePrefix('#__decarocompetitions_federations');
            $columns = $db->getTableColumns($table, false);

            if (!isset($columns['country_id'])) {
                $query = 'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName('country_id')
                    . ' INT UNSIGNED NOT NULL DEFAULT 0 AFTER ' . $db->quoteName('id');
                $db->setQuery($query)->execute();
            }

            $keys = $db->getTableKeys($table);
            $hasIndex = false;
            $legacyIndex = 'idx_' . 'dcl_' . 'federations_country_id';

            foreach ($keys as $key) {
                $keyName = (string) ($key->Key_name ?? '');

                if ($keyName === 'idx_competitions_federations_country_id' || $keyName === $legacyIndex) {
                    $hasIndex = true;
                    break;
                }
            }

            if (!$hasIndex) {
                $query = 'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD INDEX ' . $db->quoteName('idx_competitions_federations_country_id')
                    . ' (' . $db->quoteName('country_id') . ')';
                $db->setQuery($query)->execute();
            }

            $columns = $db->getTableColumns($table, false);

            if (isset($columns['country_code'])) {
                $countries = $db->replacePrefix('#__decarocompetitions_countries');
                $query = 'UPDATE ' . $db->quoteName($table) . ' AS f'
                    . ' INNER JOIN ' . $db->quoteName($countries) . ' AS c'
                    . ' ON (UPPER(c.' . $db->quoteName('code') . ') = UPPER(f.' . $db->quoteName('country_code') . ')'
                    . ' OR UPPER(c.' . $db->quoteName('iso3') . ') = UPPER(f.' . $db->quoteName('country_code') . '))'
                    . ' SET f.' . $db->quoteName('country_id') . ' = c.' . $db->quoteName('id')
                    . ' WHERE f.' . $db->quoteName('country_id') . ' = 0'
                    . ' AND f.' . $db->quoteName('country_code') . ' IS NOT NULL'
                    . ' AND f.' . $db->quoteName('country_code') . " <> ''";
                $db->setQuery($query)->execute();
            }

            return true;
        } catch (Throwable $e) {
            Log::add('Competitions component migration failed: ' . $e->getMessage(), Log::ERROR, 'competitions');

            return false;
        }
    }
}
