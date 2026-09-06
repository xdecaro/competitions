<?php
/**
 * @package     DCL Match Timeline
 * @subpackage  mod_dcl_matchtimeline
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Xdecaro\\Module\\DclMatchTimeline'));
        $container->registerServiceProvider(new Module());
    }
};
