<?php
namespace xdecaro\Component\Competitions\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use RuntimeException;
use xdecaro\Component\Competitions\Administrator\Service\AnalyticsSourceService;
use xdecaro\Component\Competitions\Administrator\Service\CoreIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\CrossProductIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\MatchReminderService;

/** Public, provider-owned service surface for optional xdecaro integrations. */
final class CompetitionsComponent extends MVCComponent
{
    private ?CoreIntegrationService $core = null;
    private ?CrossProductIntegrationService $crossProduct = null;
    private ?AnalyticsSourceService $analytics = null;
    private ?MatchReminderService $matchReminders = null;

    public function setCoreIntegrationService(CoreIntegrationService $service): void { $this->core = $service; }
    public function setCrossProductIntegrationService(CrossProductIntegrationService $service): void { $this->crossProduct = $service; }
    public function setAnalyticsSourceService(AnalyticsSourceService $service): void { $this->analytics = $service; }
    public function setMatchReminderService(MatchReminderService $service): void { $this->matchReminders = $service; }

    public function getCoreIntegrationService(): CoreIntegrationService
    {
        return $this->core ?? throw new RuntimeException('Competitions Core integration service is unavailable.');
    }

    public function getCrossProductIntegrationService(): CrossProductIntegrationService
    {
        return $this->crossProduct ?? throw new RuntimeException('Competitions cross-product integration service is unavailable.');
    }

    public function getAnalyticsSourceService(): AnalyticsSourceService
    {
        return $this->analytics ?? throw new RuntimeException('Competitions Analytics source service is unavailable.');
    }

    public function getMatchReminderService(): MatchReminderService
    {
        return $this->matchReminders ?? throw new RuntimeException('Competitions match reminder service is unavailable.');
    }
}
