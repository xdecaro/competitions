<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use xdecaro\Component\Competitions\Administrator\Helper\OrganizationAssignmentHelper;
use xdecaro\Component\Competitions\Administrator\Helper\TournamentScopeHelper;

final class TournamentModel extends BaseAdminModel
{
    private const ORGANIZATION_FIELDS = ['organizer_ids'=>'organizer','governing_body_ids'=>'governing_body','co_organizer_ids'=>'co_organizer','partner_ids'=>'partner'];

    public function getTable($type = 'Tournament', $prefix = 'Administrator', $config = []): Table { return parent::getTable($type, $prefix, $config); }
    public function getForm($data = [], $loadData = true) { return $this->loadForm('com_xdecarocompetitions.tournament', 'tournament', ['control'=>'jform','load_data'=>$loadData]); }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecarocompetitions.edit.tournament.data', []);

        if (!$data) {
            $data = $this->getItem();

            if (!empty($data->id)) {
                $db = $this->getDatabase();
                $assignments = OrganizationAssignmentHelper::load($db, '#__xdecarocompetitions_tournament_organizations', 'tournament_id', (int) $data->id);

                foreach (self::ORGANIZATION_FIELDS as $field => $role) {
                    $data->{$field} = $assignments[$role] ?? [];
                }

                $scope = TournamentScopeHelper::load($db, (int) $data->id);
                $data->country_id = (int) ($scope['country_ids'][0] ?? 0);
                $data->zone_ids = $scope['zone_ids'];
            }
        }

        return $data;
    }

    public function save($data): bool
    {
        $roles = [];

        foreach (self::ORGANIZATION_FIELDS as $field => $role) {
            $roles[$role] = OrganizationAssignmentHelper::normalizeIds($data[$field] ?? []);
            unset($data[$field]);
        }

        $countryId = (int) ($data['country_id'] ?? 0);
        $zoneIds = TournamentScopeHelper::normalizeIds($data['zone_ids'] ?? []);
        unset($data['country_id'], $data['zone_ids']);

        $db = $this->getDatabase();
        $started = false;

        try {
            OrganizationAssignmentHelper::validate($db, $roles);
            $scope = TournamentScopeHelper::validateSelection(
                $db,
                (string) ($data['scope_type'] ?? 'international'),
                (string) ($data['participant_type'] ?? 'club'),
                $countryId,
                $zoneIds
            );

            $data['scope_type'] = $scope['scope_type'];
            $data['participant_type'] = $scope['participant_type'];
            $data['local_area'] = $scope['scope_type'] === 'local'
                ? (trim((string) ($data['local_area'] ?? '')) ?: null)
                : null;

            $db->transactionStart();
            $started = true;

            if (!parent::save($data)) {
                $db->transactionRollback();
                return false;
            }

            $id = (int) $this->getState($this->getName() . '.id');

            if ($id <= 0) {
                throw new \RuntimeException('Tournament ID not available after save.');
            }

            OrganizationAssignmentHelper::sync($db, '#__xdecarocompetitions_tournament_organizations', 'tournament_id', $id, $roles);
            TournamentScopeHelper::sync(
                $db,
                $id,
                $scope['scope_type'],
                $scope['country_id'],
                $scope['zone_ids']
            );
            TournamentScopeHelper::assertExistingParticipationsCompatible($db, $id);
            TournamentScopeHelper::assertExistingSeasonHostsCompatible($db, $id);

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
}
