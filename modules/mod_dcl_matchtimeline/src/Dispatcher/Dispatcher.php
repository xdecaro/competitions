<?php
/**
 * @package     DCL Match Timeline
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

        $articleId = (int) $data['params']->get('article_id', 0);

        if ($articleId <= 0) {
            $input = $this->app->getInput();

            if ($input->getCmd('option') === 'com_content' && $input->getCmd('view') === 'article') {
                $articleId = $input->getInt('id', 0);
            }
        }

        $data['articleId'] = $articleId;
        $data['events'] = $articleId > 0 ? $this->getEvents($articleId) : [];

        return $data;
    }

    private function getEvents(int $articleId): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('e.id'),
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
            ->from($db->quoteName('#__dcl_match_events', 'e'))
            ->leftJoin(
                $db->quoteName('#__dcl_players', 'p')
                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('e.player_id')
            )
            ->where($db->quoteName('e.article_id') . ' = :articleId')
            ->where($db->quoteName('e.state') . ' = 1')
            ->bind(':articleId', $articleId, ParameterType::INTEGER)
            ->order([
                $db->quoteName('e.minute') . ' ASC',
                $db->quoteName('e.extra_minute') . ' ASC',
                $db->quoteName('e.ordering') . ' ASC',
                $db->quoteName('e.id') . ' ASC',
            ]);

        try {
            $db->setQuery($query);

            return $db->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            Log::add('DCL Match Timeline query failed: ' . $e->getMessage(), Log::WARNING, 'dcl');

            return [];
        }
    }
}
