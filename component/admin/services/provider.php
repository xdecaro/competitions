<?php
defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use xdecaro\Component\Competitions\Administrator\Extension\CompetitionsComponent;
use xdecaro\Component\Competitions\Administrator\Service\AnalyticsSourceService;
use xdecaro\Component\Competitions\Administrator\Service\CompetitionPhotoService;
use xdecaro\Component\Competitions\Administrator\Service\CoreIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\CrossProductIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\MatchReminderService;
use xdecaro\Component\Competitions\Administrator\Service\OrganizationsIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\PeopleIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\PersonHistoryService;
use xdecaro\Component\Competitions\Administrator\Service\PublicBuilderDataService;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\xdecaro\\Component\\Competitions'));
        $container->registerServiceProvider(new MVCFactory('\\xdecaro\\Component\\Competitions'));

        $container->share(CoreIntegrationService::class, static fn (): CoreIntegrationService => new CoreIntegrationService());
        $container->share(CrossProductIntegrationService::class, static fn (): CrossProductIntegrationService => new CrossProductIntegrationService());
        $container->share(AnalyticsSourceService::class, static fn (Container $container): AnalyticsSourceService => new AnalyticsSourceService($container->get(DatabaseInterface::class)));
        $container->share(MatchReminderService::class, static fn (Container $container): MatchReminderService => new MatchReminderService($container->get(DatabaseInterface::class), $container->get(CrossProductIntegrationService::class)));
        $container->share(PeopleIntegrationService::class, static fn (): PeopleIntegrationService => new PeopleIntegrationService());
        $container->share(OrganizationsIntegrationService::class, static fn (): OrganizationsIntegrationService => new OrganizationsIntegrationService());
        $container->share(CompetitionPhotoService::class, static fn (): CompetitionPhotoService => new CompetitionPhotoService());
        $container->share(PersonHistoryService::class, static fn (Container $container): PersonHistoryService => new PersonHistoryService($container->get(DatabaseInterface::class)));
        $container->share(PublicBuilderDataService::class, static fn (Container $container): PublicBuilderDataService => new PublicBuilderDataService($container->get(DatabaseInterface::class)));

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new CompetitionsComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setCoreIntegrationService($container->get(CoreIntegrationService::class));
                $component->setCrossProductIntegrationService($container->get(CrossProductIntegrationService::class));
                $component->setAnalyticsSourceService($container->get(AnalyticsSourceService::class));
                $component->setMatchReminderService($container->get(MatchReminderService::class));
                $component->setPeopleIntegrationService($container->get(PeopleIntegrationService::class));
                $component->setOrganizationsIntegrationService($container->get(OrganizationsIntegrationService::class));
                $component->setCompetitionPhotoService($container->get(CompetitionPhotoService::class));
                $component->setPersonHistoryService($container->get(PersonHistoryService::class));
                $component->setPublicBuilderDataService($container->get(PublicBuilderDataService::class));
                return $component;
            }
        );
    }
};
