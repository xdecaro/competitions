<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use xdecaro\Plugin\Xdecaroanalytics\Competitions\Extension\Competitions;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, $container->lazy(
            Competitions::class,
            static fn (): Competitions => new Competitions((array) PluginHelper::getPlugin('xdecaroanalytics', 'competitions'))
        ));
    }
};
