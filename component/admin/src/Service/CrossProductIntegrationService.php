<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Throwable;

/** Optional bridge to public Notifications and Tasks component services. */
final class CrossProductIntegrationService
{
    public const COMPONENT = 'com_xdecarocompetitions';

    public function notificationsAvailable(): bool
    {
        return ComponentHelper::isEnabled('com_xdecaronotifications');
    }

    public function tasksAvailable(): bool
    {
        return ComponentHelper::isEnabled('com_xdecarotasks');
    }

    public function publishNotification(array $data): ?int
    {
        if (!$this->notificationsAvailable()) {
            return null;
        }

        $data['source_component'] = self::COMPONENT;

        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
            if (!is_object($component) || !method_exists($component, 'getNotificationService')) {
                return null;
            }

            $service = $component->getNotificationService();
            return is_object($service) && method_exists($service, 'create')
                ? (int) $service->create($data)
                : null;
        } catch (Throwable $exception) {
            Log::add('Competitions notification bridge: ' . $exception->getMessage(), Log::WARNING, 'com_xdecarocompetitions.integration');
            return null;
        }
    }

    public function createTask(array $data, ?array $assignee = null, int $actorUserId = 0): ?int
    {
        if (!$this->tasksAvailable()) {
            return null;
        }

        $data['source_component'] = self::COMPONENT;

        try {
            $component = Factory::getApplication()->bootComponent('com_xdecarotasks');
            if (!is_object($component) || !method_exists($component, 'getTaskService')) {
                return null;
            }

            $service = $component->getTaskService();
            if (!is_object($service) || !method_exists($service, 'create')) {
                return null;
            }

            $taskId = (int) $service->create($data, max(0, $actorUserId));
            if ($taskId > 0 && is_array($assignee) && method_exists($service, 'assign')) {
                $type = trim((string) ($assignee['type'] ?? ''));
                $id = trim((string) ($assignee['id'] ?? ''));
                if ($type !== '' && $id !== '') {
                    $service->assign($taskId, $type, $id, max(0, $actorUserId), true);
                }
            }

            return $taskId > 0 ? $taskId : null;
        } catch (Throwable $exception) {
            Log::add('Competitions task bridge: ' . $exception->getMessage(), Log::WARNING, 'com_xdecarocompetitions.integration');
            return null;
        }
    }
}
