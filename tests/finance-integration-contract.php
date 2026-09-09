<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$crossPath = $root . '/component/admin/src/Service/CrossProductIntegrationService.php';
$corePath = $root . '/component/admin/src/Service/CoreIntegrationService.php';

$fail = static function (string $message): never {
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
};

if (!is_file($crossPath) || !is_file($corePath)) {
    $fail('Competitions integration service files are missing.');
}

$cross = (string) file_get_contents($crossPath);
$core = (string) file_get_contents($corePath);

foreach ([
    "bootComponent('com_decarofinance')",
    'getFinanceService',
    'createParticipationFeeObligation',
    'getOrCreateTeamDepositAccount',
    'creditTeamDeposit',
    'chargeTeamDeposit',
    'getTeamDepositBalance',
    "'source_component' => self::COMPONENT",
    "'debtor_component' => self::COMPONENT",
    "'source_entity' => 'participation'",
    "'debtor_entity' => 'team'",
    'if (!$this->financeAvailable())',
    'throw $exception',
    'Log::ERROR',
] as $token) {
    if (!str_contains($cross, $token)) {
        $fail('Finance bridge contract missing token: ' . $token);
    }
}

if (!str_contains($core, 'competitions.finance.bridge')) {
    $fail('Core capability competitions.finance.bridge is missing.');
}

foreach (['#__decarofinance_', 'Xdecaro\\Component\\Decarofinance', 'xdecaro\\Component\\Decarofinance'] as $forbidden) {
    if (str_contains($cross, $forbidden)) {
        $fail('Forbidden direct Finance coupling found: ' . $forbidden);
    }
}

fwrite(STDOUT, "Competitions Finance integration contract OK\n");
