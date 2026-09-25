<?php
declare(strict_types=1);

$root = dirname(__DIR__);

$service = file_get_contents($root . '/component/admin/src/Service/OrganizationsIntegrationService.php');
$form = file_get_contents($root . '/component/admin/forms/federation.xml');
$model = file_get_contents($root . '/component/admin/src/Model/FederationModel.php');
$table = file_get_contents($root . '/component/admin/src/Table/FederationTable.php');
$field = file_get_contents($root . '/component/admin/src/Field/FederationOrganizationField.php');
$view = file_get_contents($root . '/component/admin/src/View/Federation/HtmlView.php');
$template = file_get_contents($root . '/component/admin/tmpl/federation/edit.php');
$asset = file_get_contents($root . '/component/media/joomla.asset.json');
$script = file_get_contents($root . '/component/media/js/federation-edit.js');
$schema = file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migration = file_get_contents($root . '/component/admin/sql/updates/mysql/1.5.2.sql');

$checks = [
    [str_contains($service, "bootComponent('com_xdecaroorganizations')"), 'Organizations must be booted through Joomla'],
    [str_contains($service, 'getOrganizationProviderService'), 'Organizations public provider must be used'],
    [str_contains($service, "'type' => 'federation'"), 'Organizations search must be limited to federation type'],
    [!str_contains($service, '#__xdecaroorganizations_'), 'Competitions must not read Organizations private tables'],
    [str_contains($form, 'type="FederationOrganization"'), 'Federation form must use the Organizations picker'],
    [str_contains($field, 'getLinkedOrganizationUuids'), 'Federation picker must load already linked Organizations UUIDs'],
    [str_contains($field, "#__xdecarocompetitions_federations"), 'Federation picker duplicate filter must use the local Competitions federation table'],
    [str_contains($field, 'isset($linked[$uuid]) && $uuid !== $current'), 'Federation picker must hide already linked Organizations federations while preserving its current value'],
    [str_contains($field, 'getCountrySportsCodesByIso2'), 'Federation picker must load Competitions national sports codes'],
    [str_contains($field, "\$organization['country_code']"), 'Federation picker labels must consume Organizations country_code'],
    [str_contains($field, "\$label .= ' — ' . \$countryCode"), 'Federation picker must append the national sports code to the label'],
    [str_contains($form, 'COM_XDECAROCOMPETITIONS_SELECT_COUNTRY'), 'Country must have an explicit empty selection'],
    [!preg_match('/name="name"[^>]*required="true"/', $form), 'Canonical linked federation name must not block client validation'],
    [str_contains($model, 'public function save($data): bool'), 'FederationModel save signature must match BaseAdminModel'],
    [str_contains($model, "\$data['organization_uuid']"), 'Federation model must persist the stable Organizations UUID'],
    [str_contains($model, "\$organization['name']"), 'Federation model must refresh canonical name snapshot'],
    [str_contains($model, "\$organization['code']"), 'Federation model must refresh canonical code snapshot'],
    [str_contains($model, "\$organization['country_code']"), 'Federation model must consume the canonical Organizations country code'],
    [str_contains($model, 'resolveCountryIdFromIso2'), 'Federation model must derive the local country from Organizations ISO alpha-2'],
    [str_contains($model, "\$data['country_id'] = \$countryId"), 'Derived Organizations country must override the local federation country on save'],
    [str_contains($model, 'syncUndeterminedLinkedClubs'), 'Saving a linked federation must refresh undetermined linked clubs automatically'],
    [str_contains($model, "\$db->quoteName('federation_id') . ' = 0'"), 'Automatic federation refresh must target only undetermined clubs'],
    [str_contains($model, 'getActiveSportsFederations($clubUuid)'), 'Automatic federation refresh must use the Organizations sports-affiliation provider'],
    [str_contains($model, 'count($affiliations) !== 1'), 'Automatic federation refresh must not guess ambiguous affiliations'],
    [str_contains($model, "\$targetUuid !== \$federationUuid"), 'Automatic federation refresh must only update clubs affiliated to the saved federation'],
    [str_contains($model, "\$db->quoteName('country_code') . ' = :countryCode'"), 'Automatic federation refresh must sync the team country snapshot'],
    [str_contains($model, "LiveSyncHelper::record(\$db, 'team', \$teamId, 'update')"), 'Automatic federation refresh must notify live-sync clients'],
    [str_contains($model, "\$db->quoteName('iso2')"), 'Federation country mapping must use the local Competitions ISO alpha-2 field'],
    [str_contains($model, 'getOrganizationCountryMap'), 'Federation editor must expose a UUID-to-country map'],
    [str_contains($view, 'com_xdecarocompetitions.federationEdit'), 'Federation view must publish the country map to JavaScript'],
    [str_contains($template, "useScript('com_xdecarocompetitions.federation-edit')"), 'Federation editor must load its country sync asset'],
    [str_contains($template, 'data-federation-country-derived'), 'Federation editor must show when the country is derived'],
    [str_contains($asset, 'com_xdecarocompetitions.federation-edit'), 'Federation editor asset must be registered'],
    [is_file($root . '/component/media/js/federation-edit.js'), 'Federation editor script must live in Joomla media/js'],
    [!is_file($root . '/component/media/federation-edit.js'), 'Legacy root federation editor script must not remain'],
    [str_contains($asset, '"uri":"com_xdecarocompetitions/federation-edit.js"'), 'Federation editor asset URI must omit the duplicate /js/ segment'],
    [str_contains($script, 'countryMap'), 'Federation editor JavaScript must consume the country map'],
    [str_contains($script, 'country.disabled = true'), 'Automatically derived country must not be manually editable'],
    [str_contains($script, 'country.required = false'), 'Automatically derived country must not be blocked by client-side required validation'],
    [str_contains($template, 'data-federation-country-shadow'), 'Derived country must have a hidden submission field'],
    [str_contains($script, 'shadow.disabled = false'), 'Derived country shadow must be submitted while the visible selector is locked'],
    [str_contains($model, "if (\$existingUuid !== '')"), 'Existing canonical federation link must be immutable from the edit form'],
    [str_contains($model, "\$organizationUuid = \$existingUuid"), 'Existing canonical federation UUID must be preserved server-side'],
    [str_contains(file_get_contents($root . '/component/admin/tmpl/federation/edit.php'), '$showLinkPicker = $this->organizationsAvailable && !$isLinked;'), 'Link picker must disappear after a federation is linked'],
    [str_contains(file_get_contents($root . '/component/admin/tmpl/federation/edit.php'), 'COM_XDECAROCOMPETITIONS_CREATE_FEDERATION_IN_ORGANIZATIONS'), 'New federation editor must offer creation in Organizations'],

    [str_contains($table, 'uq_competitions_federations_organization_uuid') || str_contains($schema, 'uq_competitions_federations_organization_uuid'), 'Federation Organizations link must be unique'],
    [str_contains($schema, '`organization_uuid` CHAR(36) DEFAULT NULL'), 'Fresh install schema must include organization_uuid'],
    [str_contains($migration, 'ADD COLUMN `organization_uuid` CHAR(36) NULL'), 'Upgrade migration must add organization_uuid'],
    [str_contains($migration, 'ADD UNIQUE KEY `uq_competitions_federations_organization_uuid`'), 'Upgrade migration must add the unique UUID index'],
    [!preg_match('/DROP\s+(TABLE|COLUMN)|TRUNCATE\s+TABLE|DELETE\s+FROM/i', $migration), 'Federation migration must be non-destructive'],
];

foreach ($checks as [$ok, $message]) {
    if (!$ok) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

echo "Organizations federation contract OK\n";
