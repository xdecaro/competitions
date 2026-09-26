<?php
namespace xdecaro\Component\Competitions\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use RuntimeException;
use xdecaro\Component\Competitions\Administrator\Service\AnalyticsSourceService;
use xdecaro\Component\Competitions\Administrator\Service\CompetitionPhotoService;
use xdecaro\Component\Competitions\Administrator\Service\CoreIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\CrossProductIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\MatchReminderService;
use xdecaro\Component\Competitions\Administrator\Service\PeopleIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\OrganizationsIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\PersonHistoryService;
use xdecaro\Component\Competitions\Administrator\Service\PublicBuilderDataService;

/** Public, provider-owned service surface for optional xdecaro integrations. */
final class CompetitionsComponent extends MVCComponent
{
    private ?CoreIntegrationService $core = null;
    private ?CrossProductIntegrationService $crossProduct = null;
    private ?AnalyticsSourceService $analytics = null;
    private ?MatchReminderService $matchReminders = null;
    private ?PeopleIntegrationService $people = null;
    private ?OrganizationsIntegrationService $organizations = null;
    private ?CompetitionPhotoService $photos = null;
    private ?PersonHistoryService $personHistory = null;
    private ?PublicBuilderDataService $publicBuilderData = null;

    public function setCoreIntegrationService(CoreIntegrationService $service): void { $this->core = $service; }
    public function setCrossProductIntegrationService(CrossProductIntegrationService $service): void { $this->crossProduct = $service; }
    public function setAnalyticsSourceService(AnalyticsSourceService $service): void { $this->analytics = $service; }
    public function setMatchReminderService(MatchReminderService $service): void { $this->matchReminders = $service; }
    public function setPeopleIntegrationService(PeopleIntegrationService $service): void { $this->people = $service; }
    public function setOrganizationsIntegrationService(OrganizationsIntegrationService $service): void { $this->organizations = $service; }
    public function setCompetitionPhotoService(CompetitionPhotoService $service): void { $this->photos = $service; }
    public function setPersonHistoryService(PersonHistoryService $service): void { $this->personHistory = $service; }
    public function setPublicBuilderDataService(PublicBuilderDataService $service): void { $this->publicBuilderData = $service; }

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

    public function getPeopleIntegrationService(): PeopleIntegrationService
    {
        return $this->people ?? throw new RuntimeException('Competitions People integration service is unavailable.');
    }

    public function getOrganizationsIntegrationService(): OrganizationsIntegrationService
    {
        return $this->organizations ?? throw new RuntimeException('Competitions Organizations integration service is unavailable.');
    }

    public function getCompetitionPhotoService(): CompetitionPhotoService
    {
        return $this->photos ?? throw new RuntimeException('Competitions photo service is unavailable.');
    }

    public function getPersonHistoryService(): PersonHistoryService
    {
        return $this->personHistory ?? throw new RuntimeException('Competitions person history service is unavailable.');
    }

    public function getPublicBuilderDataService(): PublicBuilderDataService
    {
        return $this->publicBuilderData ?? throw new RuntimeException('Competitions public builder data service is unavailable.');
    }
}
