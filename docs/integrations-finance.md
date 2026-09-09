# Competitions ↔ Finance integration

Competitions 1.3.0 integrates optionally with Finance 1.2.0 through the public Joomla component service only.

## Ownership boundary

Competitions owns the competition reason and rule-derived amount for participation fees, sanctions and other competition charges. Finance owns financial persistence, obligation/payment state, allocation, deposit/caution accounts and append-only financial movements.

Core owns only shared integration contracts/capability discovery and contains no competition or finance business logic.

## Provider boundary

Competitions discovers Finance at runtime:

```php
$financeComponent = Factory::getApplication()->bootComponent('com_decarofinance');
$financeService = $financeComponent->getFinanceService();
```

Competitions does not import Finance PHP classes and does not access `#__decarofinance_*` tables.

## Supported operations

`CrossProductIntegrationService` exposes:

- `createParticipationFeeObligation()`
- `getOrCreateTeamDepositAccount()`
- `creditTeamDeposit()`
- `chargeTeamDeposit()`
- `getTeamDepositBalance()`

Stable `external_key` values make participation fees and deposit movements idempotent. The caller always supplies monetary amounts and disciplinary causes; this bridge contains no tariff table or sport-specific sanction amount.

## Failure behavior

Finance is optional. If Finance is absent/disabled, Finance bridge methods return `null`.

If Finance is installed/enabled but its public provider is incompatible or a Finance operation fails, the exception is logged and propagated. Financial writes are never silently downgraded to best-effort behavior.

## Runtime validation

CI installs Competitions 1.3.0 on Joomla 6.1.3, verifies the Finance-absent fallback, installs the published Finance 1.2.0 package by pinned SHA-256, then exercises:

1. idempotent participation-fee creation;
2. stable team deposit account creation;
3. idempotent EUR 500 deposit credit;
4. idempotent EUR 25 disciplinary debit;
5. resulting EUR 475 balance.
