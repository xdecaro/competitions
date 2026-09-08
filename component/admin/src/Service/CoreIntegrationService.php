<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

/**
 * Optional adapter between Competitions and the public Core by xdecaro
 * cross-product reference contract.
 *
 * Public cross-product references use the stable 1.x component identifier
 * com_xdecarocompetitions.
 */
final class CoreIntegrationService
{
    private const COMPONENT = 'com_xdecarocompetitions';

    public function isAvailable(): bool
    {
        return class_exists(\xdecaro\Core\Integration\EntityReference::class)
            && class_exists(\xdecaro\Core\Integration\RelationReference::class);
    }

    public function createEntityReference(string $entity, int|string $id): object
    {
        $this->assertAvailable();

        return new \xdecaro\Core\Integration\EntityReference(
            self::COMPONENT,
            $entity,
            $id
        );
    }

    public function createRelationReference(
        string $sourceEntity,
        int|string $sourceId,
        string $targetComponent,
        string $targetEntity,
        int|string $targetId,
        string $relationType
    ): object {
        $this->assertAvailable();

        $source = new \xdecaro\Core\Integration\EntityReference(
            self::COMPONENT,
            $sourceEntity,
            $sourceId
        );

        $target = new \xdecaro\Core\Integration\EntityReference(
            $targetComponent,
            $targetEntity,
            $targetId
        );

        return new \xdecaro\Core\Integration\RelationReference(
            $source,
            $target,
            $relationType
        );
    }

    private function assertAvailable(): void
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException(
                'Core by xdecaro integration is unavailable. Install a compatible Core by xdecaro version before using cross-product references.'
            );
        }
    }
}
