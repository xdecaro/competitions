<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
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
        try {
            OrganizationAssignmentHelper::validate($db, $roles);
            $db->transactionStart(); $started = true;
            if (!parent::save($data)) { $db->transactionRollback(); return false; }
            $id = (int) $this->getState($this->getName() . '.id');
            if ($id <= 0) throw new \RuntimeException('Season ID not available after save.');
            OrganizationAssignmentHelper::sync($db, '#__xdecarocompetitions_season_organizations', 'season_id', $id, $roles);
            $db->transactionCommit(); return true;
        } catch (\Throwable $e) {
            if ($started) { try { $db->transactionRollback(); } catch (\Throwable) {} }
            $this->setError($e->getMessage()); return false;
        }
    }

    protected function prepareTable($table): void { if (!$table->id && (int) $table->ordering === 0) $table->ordering = $table->getNextOrder(); }
}
