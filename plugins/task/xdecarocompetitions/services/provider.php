<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use xdecaro\Plugin\Task\Xdecarocompetitions\Extension\Xdecarocompetitions;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, $container->lazy(
            Xdecarocompetitions::class,
            static fn (): Xdecarocompetitions => new Xdecarocompetitions((array) PluginHelper::getPlugin('task', 'xdecarocompetitions'))
        ));
    }
};
