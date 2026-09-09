<?php
namespace xdecaro\Plugin\Xdecaroanalytics\Competitions\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use xdecaro\Component\Analytics\Administrator\Event\RegisterProvidersEvent;
use xdecaro\Component\Competitions\Administrator\Extension\CompetitionsComponent;
use xdecaro\Plugin\Xdecaroanalytics\Competitions\Provider\CompetitionsProvider;

final class Competitions extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return class_exists(RegisterProvidersEvent::class) ? [RegisterProvidersEvent::NAME => 'registerProviders'] : [];
    }

    public function registerProviders(RegisterProvidersEvent $event): void
    {
        $component = Factory::getApplication()->bootComponent('com_xdecarocompetitions');
        if ($component instanceof CompetitionsComponent) {
            $event->getRegistry()->register(new CompetitionsProvider($component->getAnalyticsSourceService()));
        }
    }
}
