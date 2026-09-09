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
use xdecaro\Component\Competitions\Administrator\Service\CoreIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\CrossProductIntegrationService;
use xdecaro\Component\Competitions\Administrator\Service\MatchReminderService;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\xdecaro\\Component\\Competitions'));
        $container->registerServiceProvider(new MVCFactory('\\xdecaro\\Component\\Competitions'));

        $container->share(CoreIntegrationService::class, static fn (): CoreIntegrationService => new CoreIntegrationService());
        $container->share(CrossProductIntegrationService::class, static fn (): CrossProductIntegrationService => new CrossProductIntegrationService());
        $container->share(AnalyticsSourceService::class, static fn (Container $container): AnalyticsSourceService => new AnalyticsSourceService($container->get(DatabaseInterface::class)));
        $container->share(MatchReminderService::class, static fn (Container $container): MatchReminderService => new MatchReminderService($container->get(DatabaseInterface::class), $container->get(CrossProductIntegrationService::class)));

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new CompetitionsComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setCoreIntegrationService($container->get(CoreIntegrationService::class));
                $component->setCrossProductIntegrationService($container->get(CrossProductIntegrationService::class));
                $component->setAnalyticsSourceService($container->get(AnalyticsSourceService::class));
                $component->setMatchReminderService($container->get(MatchReminderService::class));
                return $component;
            }
        );
    }
};
