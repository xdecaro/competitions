<?php
namespace xdecaro\Plugin\Task\Xdecarocompetitions\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Throwable;
use xdecaro\Component\Competitions\Administrator\Extension\CompetitionsComponent;

final class Xdecarocompetitions extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    protected const TASKS_MAP = [
        'xdecarocompetitions.match-reminders' => [
            'langConstPrefix' => 'PLG_TASK_XDECAROCOMPETITIONS_MATCH_REMINDERS',
            'form' => 'reminders',
            'method' => 'processReminders',
        ],
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList' => 'advertiseRoutines',
            'onExecuteTask' => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    protected function processReminders(ExecuteTaskEvent $event): int
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_xdecarocompetitions');
            if (!$component instanceof CompetitionsComponent) {
                throw new \RuntimeException('Competitions component is unavailable.');
            }

            $params = $event->getArgument('params');
            $component->getMatchReminderService()->process((int) ($params->days ?? 3), (int) ($params->limit ?? 200));
            return Status::OK;
        } catch (Throwable $exception) {
            $this->logTask('Competitions match reminders: ' . $exception->getMessage(), 'error');
            return Status::KNOCKOUT;
        }
    }
}
