<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Xdecaro\Plugin\System\Decarodcl\Extension\Decarodcl;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            static function (Container $container): PluginInterface {
                return new Decarodcl(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'decarodcl')
                );
            }
        );
    }
};
