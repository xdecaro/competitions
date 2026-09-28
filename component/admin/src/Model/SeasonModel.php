<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Table;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use xdecaro\Component\Competitions\Administrator\Helper\OrganizationAssignmentHelper;

final class SeasonModel extends BaseAdminModel
{
    private const ORGANIZATION_FIELDS = ['organizer_ids'=>'organizer','governing_body_ids'=>'governing_body','co_organizer_ids'=>'co_organizer','local_organizer_ids'=>'local_organizer','partner_ids'=>'partner'];

    public function getTable($type = 'Season', $prefix = 'Administrator', $config = []): Table { return parent::getTable($type, $prefix, $config); }
    public function getForm($data = [], $loadData = true) { return $this->loadForm('com_xdecarocompetitions.season', 'season', ['control'=>'jform','load_data'=>$loadData]); }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecarocompetitions.edit.season.data', []);
        if (!$data) {
            $data = $this->getItem();
            if (!empty($data->id)) {
                $assignments = OrganizationAssignmentHelper::load($this->getDatabase(), '#__xdecarocompetitions_season_organizations', 'season_id', (int) $data->id);
                foreach (self::ORGANIZATION_FIELDS as $field => $role) $data->{$field} = $assignments[$role] ?? [];
            }
        }
        return $data;
    }

    public function save($data): bool
    {
        $roles = [];
        foreach (self::ORGANIZATION_FIELDS as $field => $role) { $roles[$role] = OrganizationAssignmentHelper::normalizeIds($data[$field] ?? []); unset($data[$field]); }
        $db = $this->getDatabase(); $started = false;
        $existingId = (int) ($data['id'] ?? 0);
        $oldDates = null;

        try {
            OrganizationAssignmentHelper::validate($db, $roles);
            $oldDates = $existingId > 0 ? $this->loadSeasonDates($existingId) : null;
            $db->transactionStart(); $started = true;
            if (!parent::save($data)) { $db->transactionRollback(); return false; }
            $id = (int) $this->getState($this->getName() . '.id');
            if ($id <= 0) throw new \RuntimeException('Season ID not available after save.');
            OrganizationAssignmentHelper::sync($db, '#__xdecarocompetitions_season_organizations', 'season_id', $id, $roles);
            $db->transactionCommit();
            $started = false;

            $newDates = $this->loadSeasonDates($id);
            if ($oldDates !== null && $newDates !== null && $this->datesChanged($oldDates, $newDates)) {
                $this->dispatchSeasonDatesChanged($id, $newDates);
            }

            return true;
        } catch (\Throwable $e) {
            if ($started) { try { $db->transactionRollback(); } catch (\Throwable) {} }
            $this->setError($e->getMessage()); return false;
        }
    }

    private function loadSeasonDates(int $seasonId): ?array
    {
        if ($seasonId < 1) {
            return null;
        }

        $query = $this->getDatabase()->getQuery(true)
            ->select([
                $this->getDatabase()->quoteName('start_date'),
                $this->getDatabase()->quoteName('end_date'),
            ])
            ->from($this->getDatabase()->quoteName('#__xdecarocompetitions_seasons'))
            ->where($this->getDatabase()->quoteName('id') . ' = :season_id')
            ->bind(':season_id', $seasonId, \Joomla\Database\ParameterType::INTEGER);
        $row = $this->getDatabase()->setQuery($query, 0, 1)->loadAssoc();
        if (!is_array($row)) {
            return null;
        }

        return [
            'start_date' => ($row['start_date'] ?? null) !== null ? (string) $row['start_date'] : null,
            'end_date' => ($row['end_date'] ?? null) !== null ? (string) $row['end_date'] : null,
        ];
    }

    private function datesChanged(array $oldDates, array $newDates): bool
    {
        return ($oldDates['start_date'] ?? null) !== ($newDates['start_date'] ?? null)
            || ($oldDates['end_date'] ?? null) !== ($newDates['end_date'] ?? null);
    }

    private function dispatchSeasonDatesChanged(int $seasonId, array $dates): void
    {
        try {
            $eventName = 'onXdecaroCompetitionSeasonDatesChanged';
            $event = new Event($eventName, [
                'season_id' => $seasonId,
                'start_date' => $dates['start_date'] ?? null,
                'end_date' => $dates['end_date'] ?? null,
                'actor_user_id' => (int) (Factory::getApplication()->getIdentity()->id ?? 0),
            ]);
            Factory::getContainer()->get(DispatcherInterface::class)->dispatch($eventName, $event);
        } catch (\Throwable $e) {
            Log::add(
                'Competitions saved the season, but a season-date integration listener failed: ' . $e->getMessage(),
                Log::WARNING,
                'com_competitions'
            );
        }
    }

    protected function prepareTable($table): void { if (!$table->id && (int) $table->ordering === 0) $table->ordering = $table->getNextOrder(); }
}
