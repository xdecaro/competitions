<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/** Generates idempotent upcoming-match alerts/tasks for an explicitly configured manager. */
final class MatchReminderService
{
    public function __construct(private DatabaseInterface $db, private CrossProductIntegrationService $integration) {}

    public function process(?int $days = null, int $limit = 200): array
    {
        $params = ComponentHelper::getParams('com_xdecarocompetitions');
        $managerUserId = max(0, (int) $params->get('integration_manager_user_id', 0));
        $days = max(1, min(30, $days ?? (int) $params->get('integration_match_reminder_days', 3)));
        $limit = max(1, min(500, $limit));
        $stats = ['items' => 0, 'notifications' => 0, 'tasks' => 0, 'skipped' => 0];

        if ($managerUserId < 1) {
            return $stats;
        }

        [$today, $until] = $this->window($days);
        foreach ($this->rows($today, $until, $limit) as $row) {
            ++$stats['items'];
            $date = (string) $row['match_date'];
            $time = trim((string) ($row['kickoff_time'] ?? ''));
            $when = $date . ($time !== '' ? ' ' . substr($time, 0, 5) : '');
            $title = 'Upcoming competition match #' . (int) $row['id'];
            $message = $title . ' scheduled for ' . $when . '.';
            $externalBase = 'competition-match-upcoming:' . (int) $row['id'] . ':' . $date;
            $priority = $date === $today ? 'high' : 'normal';

            $notificationId = null;
            if ((int) $params->get('integration_notifications', 1) === 1) {
                $notificationId = $this->integration->publishNotification([
                    'recipient_type' => 'user',
                    'recipient_id' => (string) $managerUserId,
                    'category' => 'competitions',
                    'priority' => $priority,
                    'title' => $title,
                    'message' => $message,
                    'source_entity' => 'match',
                    'source_id' => (string) $row['id'],
                    'external_key' => $externalBase . ':notification',
                    'payload' => ['match_id' => (int) $row['id'], 'match_date' => $date, 'kickoff_time' => $time],
                ]);
            }
            if ($notificationId !== null) { ++$stats['notifications']; }

            $taskId = null;
            if ((int) $params->get('integration_tasks', 1) === 1) {
                $taskId = $this->integration->createTask([
                    'title' => 'Prepare match #' . (int) $row['id'],
                    'description' => $message,
                    'priority' => $priority,
                    'due_at' => $date . ' 09:00:00',
                    'source_entity' => 'match',
                    'source_id' => (string) $row['id'],
                    'external_key' => 'competition-match-prepare:' . (int) $row['id'] . ':' . $date,
                ], ['type' => 'user', 'id' => (string) $managerUserId]);
            }
            if ($taskId !== null) { ++$stats['tasks']; }
            if ($notificationId === null && $taskId === null) { ++$stats['skipped']; }
        }

        return $stats;
    }

    private function rows(string $today, string $until, int $limit): array
    {
        $query = $this->db->getQuery(true)
            ->select(['m.id', 'm.match_date', 'm.kickoff_time'])
            ->from($this->db->quoteName('#__xdecarocompetitions_matches', 'm'))
            ->where('m.state = 1')
            ->where("m.status='scheduled'")
            ->where('m.match_date BETWEEN :today AND :until')
            ->bind(':today', $today)
            ->bind(':until', $until)
            ->order('m.match_date ASC, m.kickoff_time ASC, m.id ASC');
        return array_values((array) $this->db->setQuery($query, 0, $limit)->loadAssocList());
    }

    private function window(int $days): array
    {
        return [Factory::getDate('now', 'UTC')->format('Y-m-d'), Factory::getDate('+' . $days . ' days', 'UTC')->format('Y-m-d')];
    }
}
