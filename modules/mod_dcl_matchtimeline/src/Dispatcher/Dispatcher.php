<?php
/**
 * @package     Competitions
 * @subpackage  mod_dcl_matchtimeline
 */

namespace Xdecaro\Module\DclMatchTimeline\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $params = $data['params'];
        $input = $this->app->getInput();

        $matchId = (int) $params->get('match_id', 0);

        if (
            $matchId <= 0
            && $input->getCmd('option') === 'com_decarodcl'
            && $input->getCmd('view') === 'match'
        ) {
            $matchId = $input->getInt('id', 0);
        }

        $articleId = (int) $params->get('article_id', 0);

        if (
            $matchId <= 0
            && $articleId <= 0
            && $input->getCmd('option') === 'com_content'
            && $input->getCmd('view') === 'article'
        ) {
            $articleId = $input->getInt('id', 0);
        }

        $data['matchId'] = $matchId;
        $data['articleId'] = $articleId;
        $data['events'] = $matchId > 0
            ? $this->getEvents('match_id', $matchId)
            : ($articleId > 0 ? $this->getEvents('article_id', $articleId) : []);

        return $data;
    }

    private function getEvents(string $referenceColumn, int $referenceId): array
    {
        if (!in_array($referenceColumn, ['match_id', 'article_id'], true) || $referenceId <= 0) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('e.id'),
                $db->quoteName('e.match_id'),
                $db->quoteName('e.article_id'),
                $db->quoteName('e.team_id'),
                $db->quoteName('e.player_id'),
                $db->quoteName('e.player_name_override'),
                $db->quoteName('e.side'),
                $db->quoteName('e.event_type'),
                $db->quoteName('e.minute'),
                $db->quoteName('e.extra_minute'),
                $db->quoteName('e.period'),
                $db->quoteName('e.note'),
                $db->quoteName('p.first_name'),
                $db->quoteName('p.last_name'),
            ])
            ->from($db->quoteName('#__decarocompetitions_match_events', 'e'))
            ->leftJoin(
                $db->quoteName('#__decarocompetitions_players', 'p')
                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('e.player_id')
            )
            ->where($db->quoteName('e.' . $referenceColumn) . ' = :referenceId')
            ->where($db->quoteName('e.state') . ' = 1')
            ->bind(':referenceId', $referenceId, ParameterType::INTEGER)
            ->order([
                $db->quoteName('e.minute') . ' ASC',
                $db->quoteName('e.extra_minute') . ' ASC',
                $db->quoteName('e.ordering') . ' ASC',
                $db->quoteName('e.id') . ' ASC',
            ]);

        try {
            return $db->setQuery($query)->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            Log::add(
                'Competitions Match Timeline query failed: ' . $e->getMessage(),
                Log::WARNING,
                'dcl'
            );

            return [];
        }
    }
}
