<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use RuntimeException;
use Throwable;

final class PlayerbulkModel extends BaseDatabaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function getAvailablePeople(string $search = ''): array
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_xdecarocompetitions');
            if (!is_object($component) || !method_exists($component, 'getPeopleIntegrationService')) {
                throw new RuntimeException('People integration unavailable.');
            }

            $people = $component->getPeopleIntegrationService()->searchPeople(trim($search), 200);
        } catch (Throwable $e) {
            throw new RuntimeException('People search is unavailable: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        if (!$people) {
            return [];
        }

        $db = $this->getDatabase();
        $linked = array_fill_keys(array_filter(array_map(
            static fn ($uuid): string => strtolower(trim((string) $uuid)),
            (array) $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('person_uuid'))
                    ->from($db->quoteName('#__xdecarocompetitions_players'))
                    ->where($db->quoteName('person_uuid') . ' IS NOT NULL')
            )->loadColumn()
        )), true);

        $available = [];
        foreach ($people as $person) {
            $uuid = strtolower(trim((string) ($person['uuid'] ?? '')));
            if ($uuid === '' || isset($linked[$uuid])) {
                continue;
            }

            $available[] = $person;
        }

        return $available;
    }
}
