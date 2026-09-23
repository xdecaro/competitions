<?php
namespace xdecaro\Component\Competitions\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use xdecaro\Component\Competitions\Administrator\Service\OrganizationsIntegrationService;

final class FederationModel extends BaseAdminModel
{
    public function getTable($type = 'Federation', $prefix = 'Administrator', $config = []): Table
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_xdecarocompetitions.federation', 'federation', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_xdecarocompetitions.edit.federation.data', []);

        if (!$data) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function save($data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $organizationUuid = strtolower(trim((string) ($data['organization_uuid'] ?? '')));
        $existingUuid = '';

        if ($id > 0) {
            $existing = $this->getItem($id);
            $existingUuid = strtolower(trim((string) ($existing->organization_uuid ?? '')));

            // Once a Competition federation is linked to its canonical
            // Organizations record, the relation is immutable from this form.
            // This prevents accidental federation swaps while preserving the
            // local Competition federation ID used by teams/history.
            if ($existingUuid !== '') {
                $organizationUuid = $existingUuid;
                $data['organization_uuid'] = $existingUuid;
            }
        }

        if ($organizationUuid !== '' && !$this->hasOrganizationUuidColumn()) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_LINK_SCHEMA_MISSING'));
            return false;
        }

        $integration = new OrganizationsIntegrationService();
        $available = $integration->isAvailable();

        if ($organizationUuid !== '') {
            if ($available) {
                try {
                    $organization = $integration->getFederation($organizationUuid);
                } catch (\Throwable $e) {
                    $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_ORGANIZATIONS_UNAVAILABLE'));
                    return false;
                }

                if (!$organization) {
                    $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_ORGANIZATION_INVALID'));
                    return false;
                }

                $data['organization_uuid'] = strtolower((string) $organization['uuid']);
                $data['name'] = mb_substr(trim((string) ($organization['name'] ?? '')), 0, 190);
                $data['short_name'] = $this->snapshotText($organization['code'] ?? null, 100);
                $data['logo'] = $this->snapshotText($organization['logo'] ?? null, 512);
                $data['website'] = $this->snapshotText($organization['website'] ?? null, 512);

                $email = trim((string) ($organization['email'] ?? ''));
                $data['email'] = $email !== '' && mb_strlen($email) <= 190 ? $email : null;
            } elseif ($id <= 0 || $organizationUuid !== $existingUuid) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_ORGANIZATIONS_UNAVAILABLE'));
                return false;
            }
        } elseif ($available && ($id <= 0 || $existingUuid !== '')) {
            $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_ORGANIZATION_REQUIRED'));
            return false;
        }

        if (!parent::save($data)) {
            return false;
        }

        if ($organizationUuid !== '') {
            $savedId = (int) $this->getState($this->getName() . '.id');

            if ($savedId <= 0 || !$this->persistOrganizationUuid($savedId, $organizationUuid)) {
                $this->setError(Text::_('COM_XDECAROCOMPETITIONS_ERROR_FEDERATION_LINK_NOT_PERSISTED'));
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
        $table = $this->getDatabase()->replacePrefix('#__xdecarocompetitions_federations');
        $columns = $this->getDatabase()->getTableColumns($table, false);

        return array_key_exists('organization_uuid', $columns);
    }

    private function persistOrganizationUuid(int $id, string $uuid): bool
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__xdecarocompetitions_federations'))
            ->set($db->quoteName('organization_uuid') . ' = :organizationUuid')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':organizationUuid', $uuid)
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query)->execute();

        $query = $db->getQuery(true)
            ->select($db->quoteName('organization_uuid'))
            ->from($db->quoteName('#__xdecarocompetitions_federations'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        return strtolower(trim((string) $db->setQuery($query, 0, 1)->loadResult())) === $uuid;
    }

    private function snapshotText(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }
}
