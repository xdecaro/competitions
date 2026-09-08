<?php
namespace Xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Xdecaro\Component\Competitions\Administrator\Helper\LanguageHelper;
use Xdecaro\Component\Competitions\Administrator\Helper\TournamentScopeHelper;

final class ZoneModel extends BaseAdminModel
{
    public function getTable($type = 'Zone', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_xdecarocompetitions.zone', 'zone', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecarocompetitions.edit.zone.data', []);

        if (!$data) {
            $data = $this->getItem();

            if (!empty($data->id)) {
                $db = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select($db->quoteName('country_id'))
                    ->from($db->quoteName('#__xdecarocompetitions_zone_countries'))
                    ->where($db->quoteName('zone_id') . ' = :zoneId')
                    ->order($db->quoteName('ordering') . ' ASC')
                    ->bind(':zoneId', $data->id, ParameterType::INTEGER);

                $data->country_ids = array_map('intval', $db->setQuery($query)->loadColumn() ?: []);
            }
        }

        return $data;
    }

    protected function preprocessForm(Form $form, $data, $group = 'content'): void
    {
        parent::preprocessForm($form, $data, $group);

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_xdecarocompetitions')) {
            $form->setFieldAttribute('state', 'disabled', 'true');
            $form->setFieldAttribute('state', 'readonly', 'true');
        }
    }

    public function save($data): bool
    {
        $countryIds = $this->normalizeIds($data['country_ids'] ?? []);
        unset($data['country_ids']);

        $db = $this->getDatabase();
        $started = false;

        try {
            $this->validateCountries($countryIds);

            $db->transactionStart();
            $started = true;

            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }

            $zoneId = (int) $this->getState($this->getName() . '.id');

            if ($zoneId <= 0) {
                throw new \RuntimeException('Zone ID not available after save.');
            }

            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__xdecarocompetitions_zone_countries'))
                ->where($db->quoteName('zone_id') . ' = :zoneId')
                ->bind(':zoneId', $zoneId, ParameterType::INTEGER);
            $db->setQuery($query)->execute();

            if ($countryIds) {
                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__xdecarocompetitions_zone_countries'))
                    ->columns([
                        $db->quoteName('zone_id'),
                        $db->quoteName('country_id'),
                        $db->quoteName('ordering'),
                    ]);

                foreach ($countryIds as $ordering => $countryId) {
                    $query->values(
                        (int) $zoneId . ', '
                        . (int) $countryId . ', '
                        . (int) ($ordering + 1)
                    );
                }

                $db->setQuery($query)->execute();
            }

            // A Zone can be used by multiple tournaments. Validate against the
            // newly written membership before commit so a country removal cannot
            // silently invalidate an existing participation or host country. The
            // transaction rolls both the Zone and mapping changes back on failure.
            $query = $db->getQuery(true)
                ->select('DISTINCT ' . $db->quoteName('tournament_id'))
                ->from($db->quoteName('#__xdecarocompetitions_tournament_zones'))
                ->where($db->quoteName('zone_id') . ' = :zoneId')
                ->bind(':zoneId', $zoneId, ParameterType::INTEGER);

            foreach (array_map('intval', $db->setQuery($query)->loadColumn() ?: []) as $tournamentId) {
                TournamentScopeHelper::assertExistingParticipationsCompatible($db, $tournamentId);
                TournamentScopeHelper::assertExistingSeasonHostsCompatible($db, $tournamentId);
            }

            $db->transactionCommit();

            return true;
        } catch (\Throwable $e) {
            if ($started) {
                try {
                    $db->transactionRollback();
                } catch (\Throwable) {
                }
            }

            $this->setError($e->getMessage());

            return false;
        }
    }

    protected function prepareTable($table): void
    {
        if (!$table->id && (int) $table->ordering === 0) {
            $table->ordering = $table->getNextOrder();
        }
    }

    private function normalizeIds($values): array
    {
        $ids = [];

        foreach ((array) $values as $value) {
            $id = (int) $value;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function validateCountries(array $countryIds): void
    {
        if (!$countryIds) {
            return;
        }

        $db = $this->getDatabase();
        $placeholders = [];
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__xdecarocompetitions_countries'))
            ->where($db->quoteName('state') . ' <> -2');

        foreach ($countryIds as $index => $countryId) {
            $placeholder = ':country' . $index;
            $placeholders[] = $placeholder;
            $query->bind($placeholder, $countryIds[$index], ParameterType::INTEGER);
        }

        $query->where($db->quoteName('id') . ' IN (' . implode(',', $placeholders) . ')');

        if ((int) $db->setQuery($query)->loadResult() !== count($countryIds)) {
            LanguageHelper::load();
            throw new \RuntimeException(Text::_('COM_XDECAROCOMPETITIONS_ERROR_ZONE_COUNTRY_INVALID'));
        }
    }
}
