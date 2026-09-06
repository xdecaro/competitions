<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

/**
 * DCL component migration helper.
 *
 * This protects the upgrade path from the early 0.1.x schema where
 * #__dcl_federations could still use country_code without country_id.
 */
final class ComDecarodclInstallerScript
{
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

    private function migrateLegacySchema(): bool
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $table = $db->replacePrefix('#__dcl_federations');
            $columns = $db->getTableColumns($table, false);

            if (!isset($columns['country_id'])) {
                $query = 'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName('country_id')
                    . ' INT UNSIGNED NOT NULL DEFAULT 0 AFTER ' . $db->quoteName('id');
                $db->setQuery($query)->execute();
            }

            $keys = $db->getTableKeys($table);
            $hasIndex = false;

            foreach ($keys as $key) {
                if (($key->Key_name ?? '') === 'idx_dcl_federations_country_id') {
                    $hasIndex = true;
                    break;
                }
            }

            if (!$hasIndex) {
                $query = 'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD INDEX ' . $db->quoteName('idx_dcl_federations_country_id')
                    . ' (' . $db->quoteName('country_id') . ')';
                $db->setQuery($query)->execute();
            }

            $columns = $db->getTableColumns($table, false);

            if (isset($columns['country_code'])) {
                $countries = $db->replacePrefix('#__dcl_countries');
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
            Log::add('DCL component migration failed: ' . $e->getMessage(), Log::ERROR, 'dcl');
            return false;
        }
    }
}
