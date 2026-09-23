<?php
namespace xdecaro\Component\Competitions\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
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

                $seen[$uuid] = true;
                $name = trim((string) ($organization['name'] ?? ''));
                $code = trim((string) ($organization['code'] ?? ''));
                $label = $code !== '' ? $name . ' (' . $code . ')' : $name;
                $options[] = HTMLHelper::_('select.option', $uuid, $label);
            }

            $current = strtolower(trim((string) $this->value));

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
}
