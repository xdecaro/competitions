<?php
namespace xdecaro\Component\Competitions\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

/** Competitions-owned, ACL-protected analytics read surface. */
final class AnalyticsSourceService
{
    public function __construct(private DatabaseInterface $db) {}

    public function getMetrics(): array
    {
        $this->assertAuthorised();
        return [
            ['key' => 'competitions.tournaments.total', 'label' => 'Tournaments'],
            ['key' => 'competitions.seasons.total', 'label' => 'Seasons'],
            ['key' => 'competitions.teams.total', 'label' => 'Teams'],
            ['key' => 'competitions.matches.total', 'label' => 'Matches'],
            ['key' => 'competitions.matches.scheduled', 'label' => 'Scheduled matches'],
            ['key' => 'competitions.participations.total', 'label' => 'Participations'],
        ];
    }

    public function getDatasets(): array
    {
        $this->assertAuthorised();
        return [
            ['key' => 'competitions.matches.upcoming', 'label' => 'Upcoming matches'],
            ['key' => 'competitions.rankings.top', 'label' => 'Top rankings'],
            ['key' => 'competitions.participations.by_status', 'label' => 'Participations by status'],
        ];
    }

    public function getMetric(string $key, array $context = []): array
    {
        $this->assertAuthorised();

        return match ($key) {
            'competitions.tournaments.total' => $this->countMetric('#__xdecarocompetitions_tournaments', 'Tournaments'),
            'competitions.seasons.total' => $this->countMetric('#__xdecarocompetitions_seasons', 'Seasons'),
            'competitions.teams.total' => $this->countMetric('#__xdecarocompetitions_teams', 'Teams'),
            'competitions.matches.total' => $this->countMetric('#__xdecarocompetitions_matches', 'Matches'),
            'competitions.matches.scheduled' => $this->scheduledMatchesMetric(),
            'competitions.participations.total' => $this->countMetric('#__xdecarocompetitions_participations', 'Participations'),
            default => throw new \InvalidArgumentException('Unknown Competitions analytics metric: ' . $key),
        };
    }

    public function getDataset(string $key, array $context = []): array
    {
        $this->assertAuthorised();
        $limit = max(1, min(500, (int) ($context['limit'] ?? 100)));

        if ($key === 'competitions.matches.upcoming') {
            $today = Factory::getDate('now', 'UTC')->format('Y-m-d');
            $query = $this->db->getQuery(true)
                ->select(['m.id', 'm.season_id', 'm.home_team_id', 'm.away_team_id', 'm.venue_id', 'm.match_date', 'm.kickoff_time', 'm.stage', 'm.round_name', 'm.status'])
                ->from($this->db->quoteName('#__xdecarocompetitions_matches', 'm'))
                ->where('m.state = 1')
                ->where("m.status='scheduled'")
                ->where('m.match_date >= :today')
                ->bind(':today', $today)
                ->order('m.match_date ASC, m.kickoff_time ASC, m.id ASC');
            return array_values((array) $this->db->setQuery($query, 0, $limit)->loadAssocList());
        }

        if ($key === 'competitions.rankings.top') {
            $query = $this->db->getQuery(true)
                ->select(['id', 'tournament_id', 'ranking_type', 'entity_id', 'season_end_id', 'seasons_count', 'coefficient_total', 'position', 'calculated_at'])
                ->from($this->db->quoteName('#__xdecarocompetitions_rankings'))
                ->where($this->db->quoteName('position') . ' IS NOT NULL')
                ->order($this->db->quoteName('position') . ' ASC, ' . $this->db->quoteName('coefficient_total') . ' DESC');
            return array_values((array) $this->db->setQuery($query, 0, $limit)->loadAssocList());
        }

        if ($key === 'competitions.participations.by_status') {
            $query = $this->db->getQuery(true)
                ->select([$this->db->quoteName('status'), 'COUNT(*) AS total'])
                ->from($this->db->quoteName('#__xdecarocompetitions_participations'))
                ->where($this->db->quoteName('state') . ' = 1')
                ->group($this->db->quoteName('status'))
                ->order('total DESC');
            return array_values((array) $this->db->setQuery($query, 0, $limit)->loadAssocList());
        }

        throw new \InvalidArgumentException('Unknown Competitions analytics dataset: ' . $key);
    }

    private function scheduledMatchesMetric(): array
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__xdecarocompetitions_matches'))
            ->where($this->db->quoteName('state') . ' = 1')
            ->where($this->db->quoteName('status') . ' = ' . $this->db->quote('scheduled'));
        return ['value' => (int) $this->db->setQuery($query)->loadResult(), 'label' => 'Scheduled matches'];
    }

    private function countMetric(string $table, string $label): array
    {
        $query = $this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName($table));
        if ($table !== '#__xdecarocompetitions_rankings') {
            $query->where($this->db->quoteName('state') . ' = 1');
        }
        return ['value' => (int) $this->db->setQuery($query)->loadResult(), 'label' => $label];
    }

    private function assertAuthorised(): void
    {
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.manage', 'com_xdecarocompetitions') && !$user->authorise('core.admin', 'com_xdecarocompetitions')) {
            throw new RuntimeException('Not authorised to read Competitions analytics.', 403);
        }
    }
}
