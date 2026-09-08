<?php
/**
 * @package     DCL Countries & Federations
 * @subpackage  mod_dcl_countriesfederations
 */

namespace Xdecaro\Module\DclCountriesFederations\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;

final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $params = $data['params'];
        $showInactive = (bool) $params->get('show_inactive', 0);
        $showEmptyCountries = (bool) $params->get('show_empty_countries', 1);

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('c.id', 'country_id'),
                $db->quoteName('c.name', 'country_name'),
                $db->quoteName('c.code', 'country_code'),
                $db->quoteName('c.flag', 'country_flag'),
                $db->quoteName('c.state', 'country_state'),
                $db->quoteName('f.id', 'federation_id'),
                $db->quoteName('f.name', 'federation_name'),
                $db->quoteName('f.short_name', 'federation_short_name'),
                $db->quoteName('f.logo', 'federation_logo'),
                $db->quoteName('f.website', 'federation_website'),
                $db->quoteName('f.email', 'federation_email'),
                $db->quoteName('f.state', 'federation_state'),
            ])
            ->from($db->quoteName('#__decarocompetitions_countries', 'c'))
            ->leftJoin(
                $db->quoteName('#__decarocompetitions_federations', 'f')
                . ' ON ' . $db->quoteName('f.country_id') . ' = ' . $db->quoteName('c.id')
                . ($showInactive ? '' : ' AND ' . $db->quoteName('f.state') . ' = 1')
            )
            ->order([
                $db->quoteName('c.ordering') . ' ASC',
                $db->quoteName('c.name') . ' ASC',
                $db->quoteName('f.ordering') . ' ASC',
                $db->quoteName('f.name') . ' ASC',
            ]);

        if (!$showInactive) {
            $query->where($db->quoteName('c.state') . ' = 1');
        }

        if (!$showEmptyCountries) {
            $query->where($db->quoteName('f.id') . ' IS NOT NULL');
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        $countries = [];

        foreach ($rows as $row) {
            $countryId = (int) $row->country_id;

            if (!isset($countries[$countryId])) {
                $countries[$countryId] = (object) [
                    'id' => $countryId,
                    'name' => (string) $row->country_name,
                    'code' => (string) $row->country_code,
                    'flag' => (string) ($row->country_flag ?? ''),
                    'state' => (int) $row->country_state,
                    'federations' => [],
                ];
            }

            if ($row->federation_id !== null) {
                $countries[$countryId]->federations[] = (object) [
                    'id' => (int) $row->federation_id,
                    'name' => (string) $row->federation_name,
                    'short_name' => (string) ($row->federation_short_name ?? ''),
                    'logo' => (string) ($row->federation_logo ?? ''),
                    'website' => (string) ($row->federation_website ?? ''),
                    'email' => (string) ($row->federation_email ?? ''),
                    'state' => (int) ($row->federation_state ?? 0),
                ];
            }
        }

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle(
            'mod_dcl_countriesfederations.site',
            'media/mod_dcl_countriesfederations/css/site.css',
            [],
            ['version' => '0.3.3']
        );
        $wa->registerAndUseScript(
            'mod_dcl_countriesfederations.site',
            'media/mod_dcl_countriesfederations/js/site.js',
            [],
            ['version' => '0.3.3'],
            ['defer' => true]
        );

        $data['countries'] = array_values($countries);
        $data['showSearch'] = (bool) $params->get('show_search', 1);

        return $data;
    }
}
