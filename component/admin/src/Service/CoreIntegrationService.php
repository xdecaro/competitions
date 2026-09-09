<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use xdecaro\Core\Integration\Capability;
use xdecaro\Core\Integration\CapabilityRegistry;

final class CoreIntegrationService
{
    public const COMPONENT = 'com_xdecarocompetitions';
    public const MINIMUM_CORE_VERSION = '1.3.0';

    public function isAvailable(): bool
    {
        return class_exists(\xdecaro\Core\Version::class)
            && version_compare((string) \xdecaro\Core\Version::VERSION, self::MINIMUM_CORE_VERSION, '>=')
            && class_exists(\xdecaro\Core\Integration\EntityReference::class)
            && class_exists(\xdecaro\Core\Integration\RelationReference::class);
    }

    public function hasCapabilityRegistry(): bool
    {
        return class_exists(Capability::class) && class_exists(CapabilityRegistry::class);
    }

    /** @return array<int,Capability> */
    public function getCapabilities(): array
    {
        if (!$this->hasCapabilityRegistry()) { return []; }
        return [
            new Capability(self::COMPONENT, 'competitions.analytics.provider', '1'),
            new Capability(self::COMPONENT, 'competitions.notifications.bridge', '1'),
            new Capability(self::COMPONENT, 'competitions.tasks.bridge', '1'),
            new Capability(self::COMPONENT, 'competitions.finance.bridge', '1'),
            new Capability(self::COMPONENT, 'competitions.match-reminders', '1'),
        ];
    }

    public function registerCapabilities(CapabilityRegistry $registry): void { $registry->registerMany($this->getCapabilities()); }

    public function createEntityReference(string $entity, int|string $id): object
    {
        $this->assertAvailable();
        return new \xdecaro\Core\Integration\EntityReference(self::COMPONENT, $entity, $id);
    }

    public function createRelationReference(string $sourceEntity, int|string $sourceId, string $targetComponent, string $targetEntity, int|string $targetId, string $relationType): object
    {
        $this->assertAvailable();
        return new \xdecaro\Core\Integration\RelationReference(
            new \xdecaro\Core\Integration\EntityReference(self::COMPONENT, $sourceEntity, $sourceId),
            new \xdecaro\Core\Integration\EntityReference($targetComponent, $targetEntity, $targetId),
            $relationType
        );
    }

    private function assertAvailable(): void
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Core by xdecaro 1.3.0 or later is required for Competitions cross-product references.');
        }
    }
}
