<?php
/**
 * @package     DCL Countries & Federations
 * @subpackage  mod_dcl_countriesfederations
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ModuleDispatcherFactoryInterface;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('Xdecaro\\Module\\DclCountriesFederations'));
        $container->registerServiceProvider(new Module());
    }
};
