<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Throwable;

/** Optional bridges to public xdecaro component services. */
final class CrossProductIntegrationService
{
    public const COMPONENT = 'com_xdecarocompetitions';

    public function notificationsAvailable(): bool
    {
        return ComponentHelper::isEnabled('com_xdecaronotifications');
    }

    public function tasksAvailable(): bool
    {
        return ComponentHelper::isEnabled('com_xdecarotasks');
    }

    public function financeAvailable(): bool
    {
        return ComponentHelper::isEnabled('com_decarofinance');
    }

    public function publishNotification(array $data): ?int
    {
        if (!$this->notificationsAvailable()) {
            return null;
        }

        $data['source_component'] = self::COMPONENT;

        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
            if (!is_object($component) || !method_exists($component, 'getNotificationService')) {
                return null;
            }

            $service = $component->getNotificationService();
            return is_object($service) && method_exists($service, 'create')
                ? (int) $service->create($data)
                : null;
        } catch (Throwable $exception) {
            Log::add('Competitions notification bridge: ' . $exception->getMessage(), Log::WARNING, 'com_xdecarocompetitions.integration');
            return null;
        }
    }

    public function createTask(array $data, ?array $assignee = null, int $actorUserId = 0): ?int
    {
        if (!$this->tasksAvailable()) {
            return null;
        }

        $data['source_component'] = self::COMPONENT;

        try {
            $component = Factory::getApplication()->bootComponent('com_xdecarotasks');
            if (!is_object($component) || !method_exists($component, 'getTaskService')) {
                return null;
            }

            $service = $component->getTaskService();
            if (!is_object($service) || !method_exists($service, 'create')) {
                return null;
            }

            $taskId = (int) $service->create($data, max(0, $actorUserId));
            if ($taskId > 0 && is_array($assignee) && method_exists($service, 'assign')) {
                $type = trim((string) ($assignee['type'] ?? ''));
                $id = trim((string) ($assignee['id'] ?? ''));
                if ($type !== '' && $id !== '') {
                    $service->assign($taskId, $type, $id, max(0, $actorUserId), true);
                }
            }

            return $taskId > 0 ? $taskId : null;
        } catch (Throwable $exception) {
            Log::add('Competitions task bridge: ' . $exception->getMessage(), Log::WARNING, 'com_xdecarocompetitions.integration');
            return null;
        }
    }

    /**
     * Create the Finance obligation owned by one competition participation.
     *
     * Competitions owns the fee rule and amount. Finance owns persistence,
     * payment allocation and financial state. Repeating this call for the same
     * participation is idempotent through Finance's external_key contract.
     */
    public function createParticipationFeeObligation(
        int|string $participationId,
        int|string $teamId,
        float|string $amount,
        ?string $dueDate = null,
        string $currency = 'EUR',
        ?string $description = null,
        int $actorUserId = 0
    ): ?int {
        $participationId = $this->positiveIdentifier($participationId, 'participation');
        $teamId = $this->positiveIdentifier($teamId, 'team');

        return $this->withFinanceService(function (object $service) use ($participationId, $teamId, $amount, $dueDate, $currency, $description, $actorUserId): int {
            if (!method_exists($service, 'createObligation')) {
                throw new \RuntimeException('Finance createObligation API is unavailable.');
            }

            return (int) $service->createObligation([
                'external_key' => 'competitions:participation:' . $participationId . ':fee',
                'source_component' => self::COMPONENT,
                'source_entity' => 'participation',
                'source_id' => $participationId,
                'debtor_component' => self::COMPONENT,
                'debtor_entity' => 'team',
                'debtor_id' => $teamId,
                'kind' => 'participation_fee',
                'description' => $description,
                'amount' => $amount,
                'currency' => $currency,
                'due_date' => $dueDate,
            ], max(0, $actorUserId));
        });
    }

    public function getOrCreateTeamDepositAccount(int|string $teamId, string $currency = 'EUR'): ?int
    {
        $teamId = $this->positiveIdentifier($teamId, 'team');

        return $this->withFinanceService(function (object $service) use ($teamId, $currency): int {
            if (!method_exists($service, 'getOrCreateDepositAccount')) {
                throw new \RuntimeException('Finance deposit account API is unavailable.');
            }

            return (int) $service->getOrCreateDepositAccount(self::COMPONENT, 'team', $teamId, $currency);
        });
    }

    /** Credit a team's competition deposit/caution account. */
    public function creditTeamDeposit(
        int|string $teamId,
        float|string $amount,
        string $externalKey,
        ?string $description = null,
        string $currency = 'EUR',
        int $actorUserId = 0
    ): ?int {
        $teamId = $this->positiveIdentifier($teamId, 'team');
        $externalKey = $this->externalToken($externalKey);

        return $this->withFinanceService(function (object $service) use ($teamId, $amount, $externalKey, $description, $currency, $actorUserId): int {
            if (!method_exists($service, 'getOrCreateDepositAccount') || !method_exists($service, 'postDepositMovement')) {
                throw new \RuntimeException('Finance deposit API is unavailable.');
            }

            $accountId = (int) $service->getOrCreateDepositAccount(self::COMPONENT, 'team', $teamId, $currency);
            return (int) $service->postDepositMovement($accountId, 'credit', abs((float) $amount), [
                'external_key' => 'competitions:deposit:team:' . $teamId . ':credit:' . $externalKey,
                'description' => $description,
                'source_component' => self::COMPONENT,
                'source_entity' => 'team',
                'source_id' => $teamId,
            ], max(0, $actorUserId));
        });
    }

    /**
     * Debit a team's competition deposit for a domain-owned disciplinary cause.
     * The caller supplies the rule-derived amount and cause; no tariff lives here.
     */
    public function chargeTeamDeposit(
        int|string $teamId,
        string $sourceEntity,
        int|string $sourceId,
        string $cause,
        float|string $amount,
        ?string $description = null,
        string $currency = 'EUR',
        int $actorUserId = 0
    ): ?int {
        $teamId = $this->positiveIdentifier($teamId, 'team');
        $sourceId = $this->positiveIdentifier($sourceId, 'source');
        $sourceEntity = $this->entityToken($sourceEntity);
        $cause = $this->entityToken($cause);

        return $this->withFinanceService(function (object $service) use ($teamId, $sourceEntity, $sourceId, $cause, $amount, $description, $currency, $actorUserId): int {
            if (!method_exists($service, 'getOrCreateDepositAccount') || !method_exists($service, 'postDepositMovement')) {
                throw new \RuntimeException('Finance deposit API is unavailable.');
            }

            $accountId = (int) $service->getOrCreateDepositAccount(self::COMPONENT, 'team', $teamId, $currency);
            return (int) $service->postDepositMovement($accountId, 'debit', -abs((float) $amount), [
                'external_key' => 'competitions:deposit:team:' . $teamId . ':' . $sourceEntity . ':' . $sourceId . ':' . $cause,
                'description' => $description,
                'source_component' => self::COMPONENT,
                'source_entity' => $sourceEntity,
                'source_id' => $sourceId,
            ], max(0, $actorUserId));
        });
    }

    public function getTeamDepositBalance(int|string $teamId, string $currency = 'EUR'): ?float
    {
        $teamId = $this->positiveIdentifier($teamId, 'team');

        return $this->withFinanceService(function (object $service) use ($teamId, $currency): float {
            if (!method_exists($service, 'getOrCreateDepositAccount') || !method_exists($service, 'getDepositBalance')) {
                throw new \RuntimeException('Finance deposit query API is unavailable.');
            }

            $accountId = (int) $service->getOrCreateDepositAccount(self::COMPONENT, 'team', $teamId, $currency);
            return (float) $service->getDepositBalance($accountId);
        });
    }

    private function withFinanceService(callable $operation): mixed
    {
        if (!$this->financeAvailable()) {
            return null;
        }

        try {
            $component = Factory::getApplication()->bootComponent('com_decarofinance');
            if (!is_object($component) || !method_exists($component, 'getFinanceService')) {
                return null;
            }

            $service = $component->getFinanceService();
            return is_object($service) ? $operation($service) : null;
        } catch (Throwable $exception) {
            Log::add('Competitions Finance bridge: ' . $exception->getMessage(), Log::WARNING, 'com_xdecarocompetitions.integration');
            return null;
        }
    }

    private function positiveIdentifier(int|string $value, string $name): string
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value) || (int) $value < 1) {
            throw new InvalidArgumentException('Invalid ' . $name . ' identifier.');
        }

        return $value;
    }

    private function entityToken(string $value): string
    {
        $value = strtolower(trim($value));
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $value)) {
            throw new InvalidArgumentException('Invalid integration token.');
        }

        return $value;
    }

    private function externalToken(string $value): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 80 || !preg_match('/^[A-Za-z0-9._:-]+$/', $value)) {
            throw new InvalidArgumentException('Invalid external key token.');
        }

        return $value;
    }
}
