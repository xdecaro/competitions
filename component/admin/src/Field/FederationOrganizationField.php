<?php
namespace xdecaro\Component\Competitions\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Throwable;
use xdecaro\Component\Competitions\Administrator\Service\OrganizationsIntegrationService;

final class FederationOrganizationField extends ListField
{
    protected $type = 'FederationOrganization';

    protected function getOptions(): array
    {
        $options = [
            HTMLHelper::_(
                'select.option',
                '',
                Text::_('COM_XDECAROCOMPETITIONS_SELECT_ORGANIZATION_FEDERATION')
            ),
        ];

        $service = new OrganizationsIntegrationService();
        $current = strtolower(trim((string) $this->value));
        $linked = $this->getLinkedOrganizationUuids();

        try {
            if (!$service->isAvailable()) {
                return $options;
            }

            $seen = [];

            foreach ($service->searchFederations('', 200) as $organization) {
                $uuid = strtolower(trim((string) ($organization['uuid'] ?? '')));

                if ($uuid === '') {
                    continue;
                }

                if (isset($linked[$uuid]) && $uuid !== $current) {
                    continue;
                }

                $seen[$uuid] = true;
                $name = trim((string) ($organization['name'] ?? ''));
                $code = trim((string) ($organization['code'] ?? ''));
                $label = $code !== '' ? $name . ' (' . $code . ')' : $name;
                $options[] = HTMLHelper::_('select.option', $uuid, $label);
            }

            if ($current !== '' && !isset($seen[$current])) {
                $organization = $service->getFederation($current);

                if ($organization) {
                    $name = trim((string) ($organization['name'] ?? ''));
                    $code = trim((string) ($organization['code'] ?? ''));
                    $label = $code !== '' ? $name . ' (' . $code . ')' : $name;
                    $options[] = HTMLHelper::_('select.option', $current, $label);
                }
            }
        } catch (Throwable) {
            // Keep the empty option. The form view will fall back to local snapshots.
        }

        return array_merge(parent::getOptions(), $options);
    }

    /**
     * @return array<string, true>
     */
    private function getLinkedOrganizationUuids(): array
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('organization_uuid'))
                ->from($db->quoteName('#__xdecarocompetitions_federations'))
                ->where($db->quoteName('organization_uuid') . ' IS NOT NULL')
                ->where($db->quoteName('organization_uuid') . " <> ''");

            $linked = [];

            foreach ($db->setQuery($query)->loadColumn() ?: [] as $uuid) {
                $uuid = strtolower(trim((string) $uuid));

                if ($uuid !== '') {
                    $linked[$uuid] = true;
                }
            }

            return $linked;
        } catch (Throwable) {
            // Keep the picker usable if the local federation table is unavailable.
            return [];
        }
    }
}
