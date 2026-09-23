<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use xdecaro\Component\Competitions\Administrator\Helper\LiveSyncHelper;
use xdecaro\Component\Competitions\Administrator\Service\OrganizationsIntegrationService;

final class TeamModel extends BaseAdminModel
{
    public function getTable($type = 'Team', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_xdecarocompetitions.team',
            'team',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    public function setApprovalStatus(&$pks, string $status): bool
    {
        $status = strtolower(trim($status));

        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Invalid team approval status: ' . $status);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $pks))));

        if (!$ids) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_NO_TEAMS_SELECTED'));
            return false;
        }

        $db = $this->getDatabase();
        $placeholders = [];

        foreach ($ids as $index => $id) {
            $placeholder = ':teamId' . $index;
            $placeholders[] = $placeholder;
        }

        if ($status === 'approved') {
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('id'),
                    $db->quoteName('name'),
                    $db->quoteName('federation_id'),
                ])
                ->from($db->quoteName('#__xdecarocompetitions_teams'))
                ->where($db->quoteName('id') . ' IN (' . implode(',', $placeholders) . ')')
                ->where($db->quoteName('state') . ' <> -2');

            foreach ($ids as $index => $id) {
                $query->bind(':teamId' . $index, $ids[$index], ParameterType::INTEGER);
            }

            $rows = $db->setQuery($query)->loadObjectList() ?: [];

            if (count($rows) !== count($ids)) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_APPROVAL_INVALID_SELECTION'));
                return false;
            }

            foreach ($rows as $row) {
                if ((int) ($row->federation_id ?? 0) <= 0) {
                    $name = trim((string) ($row->name ?? ''));

                    $this->setError(
                        Text::sprintf(
                            'COM_XDECAROCOMPETITIONS_ERROR_TEAM_APPROVAL_FEDERATION_REQUIRED',
                            $name !== '' ? $name : ('#' . (int) $row->id)
                        )
                    );
                    return false;
                }
            }
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__xdecarocompetitions_teams'))
            ->set($db->quoteName('approval_status') . ' = :approvalStatus')
            ->bind(':approvalStatus', $status);

        foreach ($ids as $index => $id) {
            $placeholder = ':updateTeamId' . $index;
            $updatePlaceholders[] = $placeholder;
            $query->bind($placeholder, $ids[$index], ParameterType::INTEGER);
        }

        $query->where($db->quoteName('id') . ' IN (' . implode(',', $updatePlaceholders) . ')');
        $db->setQuery($query)->execute();

        LiveSyncHelper::touchModified(
            $db,
            'team',
            $ids,
            (int) Factory::getApplication()->getIdentity()->id
        );

        foreach ($ids as $id) {
            LiveSyncHelper::record($db, 'team', $id, 'update');
        }

        $pks = $ids;

        return true;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecarocompetitions.edit.team.data', []);

        if (!$data) {
            $data = $this->getItem();
        }

        return $data;
    }

    protected function preprocessForm(Form $form, $data, $group = 'content'): void
    {
        parent::preprocessForm($form, $data, $group);

        if (!Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_xdecarocompetitions')) {
            $form->setFieldAttribute('state', 'disabled', 'true');
            $form->setFieldAttribute('state', 'readonly', 'true');
            $form->setFieldAttribute('approval_status', 'disabled', 'true');
            $form->setFieldAttribute('approval_status', 'readonly', 'true');
            $form->setFieldAttribute('owner_user_id', 'disabled', 'true');
            $form->setFieldAttribute('owner_user_id', 'readonly', 'true');
        }
    }

    public function save($data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $teamType = strtolower(trim((string) ($data['team_type'] ?? 'club'))) ?: 'club';
        $organizationUuid = strtolower(trim((string) ($data['organization_uuid'] ?? '')));
        $existingUuid = '';

        if ($id > 0) {
            $existing = $this->getItem($id);
            $existingUuid = strtolower(trim((string) ($existing->organization_uuid ?? '')));

            if ($existingUuid !== '') {
                // A linked club keeps its canonical Organizations identity.
                // The normal Competition editor cannot swap it to another club
                // or convert it into a national team.
                $organizationUuid = $existingUuid;
                $teamType = 'club';
                $data['organization_uuid'] = $existingUuid;
                $data['team_type'] = 'club';
            }
        }

        if ($organizationUuid !== '' && !$this->hasOrganizationUuidColumn()) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_LINK_SCHEMA_MISSING'));
            return false;
        }

        $integration = new OrganizationsIntegrationService();
        $available = $integration->isAvailable();

        if ($teamType === 'club') {
            if ($organizationUuid !== '') {
                if ($available) {
                    try {
                        $organization = $integration->getClub($organizationUuid);
                    } catch (\Throwable) {
                        $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_ORGANIZATIONS_UNAVAILABLE'));
                        return false;
                    }

                    if (!$organization) {
                        $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_ORGANIZATION_INVALID'));
                        return false;
                    }

                    $data['organization_uuid'] = strtolower((string) $organization['uuid']);
                    $data['name'] = mb_substr(trim((string) ($organization['name'] ?? '')), 0, 190);
                    $data['short_name'] = $this->snapshotText($organization['code'] ?? null, 100);
                    $data['logo'] = $this->snapshotText($organization['logo'] ?? null, 512);
                    $data['email'] = $this->snapshotText($organization['email'] ?? null, 190);
                    $data['phone'] = $this->snapshotText($organization['phone'] ?? null, 100);
                    $data['website'] = $this->snapshotText($organization['website'] ?? null, 512);

                    if (array_key_exists('city', $organization)) {
                        $data['city'] = $this->snapshotText($organization['city'], 190);
                    }

                    try {
                        $data['federation_id'] = $this->resolveClubFederationId(
                            $integration,
                            (string) $data['organization_uuid']
                        );
                    } catch (\RuntimeException) {
                        return false;
                    }
                } elseif ($id <= 0 || $organizationUuid !== $existingUuid) {
                    $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_ORGANIZATIONS_UNAVAILABLE'));
                    return false;
                }
            } elseif ($available && $id <= 0) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_ORGANIZATION_REQUIRED'));
                return false;
            }
        } else {
            // National representative teams remain Competition-native records;
            // they are not legal/organizational club identities.
            $organizationUuid = '';
            $data['organization_uuid'] = null;
        }

        if (!parent::save($data)) {
            return false;
        }

        if ($organizationUuid !== '') {
            $savedId = (int) $this->getState($this->getName() . '.id');

            if ($savedId <= 0 || !$this->persistOrganizationUuid($savedId, $organizationUuid)) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_LINK_NOT_PERSISTED'));
                return false;
            }
        }

        return true;
    }

    protected function prepareTable($table): void
    {
        if (!$table->id && (int) $table->ordering === 0) {
            $table->ordering = $table->getNextOrder();
        }
    }

    private function hasOrganizationUuidColumn(): bool
    {
        $table = $this->getDatabase()->replacePrefix('#__xdecarocompetitions_teams');
        $columns = $this->getDatabase()->getTableColumns($table, false);

        return array_key_exists('organization_uuid', $columns);
    }

    private function persistOrganizationUuid(int $id, string $uuid): bool
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__xdecarocompetitions_teams'))
            ->set($db->quoteName('organization_uuid') . ' = :organizationUuid')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':organizationUuid', $uuid)
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query)->execute();

        $query = $db->getQuery(true)
            ->select($db->quoteName('organization_uuid'))
            ->from($db->quoteName('#__xdecarocompetitions_teams'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        return strtolower(trim((string) $db->setQuery($query, 0, 1)->loadResult())) === $uuid;
    }

    private function resolveClubFederationId(
        OrganizationsIntegrationService $integration,
        string $clubUuid
    ): int {
        try {
            $affiliations = $integration->getActiveSportsFederations($clubUuid);
        } catch (\Throwable) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_AFFILIATIONS_UNAVAILABLE'));
            throw new \RuntimeException((string) $this->getError());
        }

        if (count($affiliations) > 1) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_TEAM_AFFILIATION_AMBIGUOUS'));
            throw new \RuntimeException((string) $this->getError());
        }

        if (!$affiliations) {
            return 0;
        }

        $targetUuid = strtolower(trim((string) ($affiliations[0]['target_uuid'] ?? '')));

        if ($targetUuid === '') {
            return 0;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__xdecarocompetitions_federations'))
            ->where($db->quoteName('organization_uuid') . ' = :organizationUuid')
            ->where($db->quoteName('state') . ' <> -2')
            ->bind(':organizationUuid', $targetUuid);

        return (int) $db->setQuery($query, 0, 1)->loadResult();
    }

    private function snapshotText(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }
}
