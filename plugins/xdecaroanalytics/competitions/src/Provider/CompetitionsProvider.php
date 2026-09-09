<?php
namespace xdecaro\Plugin\Xdecaroanalytics\Competitions\Provider;

defined('_JEXEC') or die;

use xdecaro\Component\Analytics\Administrator\Contract\AnalyticsProviderInterface;
use xdecaro\Component\Competitions\Administrator\Service\AnalyticsSourceService;

final class CompetitionsProvider implements AnalyticsProviderInterface
{
    public function __construct(private AnalyticsSourceService $source) {}
    public function getKey(): string { return 'competitions'; }
    public function getLabel(): string { return 'Competitions'; }
    public function getMetrics(): array { return $this->source->getMetrics(); }
    public function getDatasets(): array { return $this->source->getDatasets(); }
    public function getMetric(string $key, array $context = []): array { return $this->source->getMetric($key, $context); }
    public function getDataset(string $key, array $context = []): array { return $this->source->getDataset($key, $context); }
}
