<?php
declare(strict_types=1);

$root = dirname(__DIR__);

$service = file_get_contents($root . '/component/admin/src/Service/OrganizationsIntegrationService.php');
$form = file_get_contents($root . '/component/admin/forms/federation.xml');
$model = file_get_contents($root . '/component/admin/src/Model/FederationModel.php');
$table = file_get_contents($root . '/component/admin/src/Table/FederationTable.php');
$schema = file_get_contents($root . '/component/admin/sql/install.mysql.utf8mb4.sql');
$migration = file_get_contents($root . '/component/admin/sql/updates/mysql/1.5.2.sql');

$checks = [
    [str_contains($service, "bootComponent('com_xdecaroorganizations')"), 'Organizations must be booted through Joomla'],
    [str_contains($service, 'getOrganizationProviderService'), 'Organizations public provider must be used'],
    [str_contains($service, "'type' => 'federation'"), 'Organizations search must be limited to federation type'],
    [!str_contains($service, '#__xdecaroorganizations_'), 'Competitions must not read Organizations private tables'],
    [str_contains($form, 'type="FederationOrganization"'), 'Federation form must use the Organizations picker'],
    [str_contains($form, 'COM_XDECAROCOMPETITIONS_SELECT_COUNTRY'), 'Country must have an explicit empty selection'],
    [!preg_match('/name="name"[^>]*required="true"/', $form), 'Canonical linked federation name must not block client validation'],
    [str_contains($model, 'public function save($data): bool'), 'FederationModel save signature must match BaseAdminModel'],
    [str_contains($model, "\$data['organization_uuid']"), 'Federation model must persist the stable Organizations UUID'],
    [str_contains($model, "\$organization['name']"), 'Federation model must refresh canonical name snapshot'],
    [str_contains($model, "\$organization['code']"), 'Federation model must refresh canonical code snapshot'],
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
