<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$cards = [
    ['key' => 'organizations', 'label' => 'COM_XDECAROCOMPETITIONS_ORGANIZATIONS', 'link' => 'index.php?option=com_xdecarocompetitions&view=organizations'],
    ['key' => 'zones', 'label' => 'COM_XDECAROCOMPETITIONS_ZONES', 'link' => 'index.php?option=com_xdecarocompetitions&view=zones'],
    ['key' => 'countries', 'label' => 'COM_XDECAROCOMPETITIONS_COUNTRIES', 'link' => 'index.php?option=com_xdecarocompetitions&view=countries'],
    ['key' => 'federations', 'label' => 'COM_XDECAROCOMPETITIONS_FEDERATIONS', 'link' => 'index.php?option=com_xdecarocompetitions&view=federations'],
    ['key' => 'tournaments', 'label' => 'COM_XDECAROCOMPETITIONS_TOURNAMENTS', 'link' => 'index.php?option=com_xdecarocompetitions&view=tournaments'],
    ['key' => 'seasons', 'label' => 'COM_XDECAROCOMPETITIONS_SEASONS', 'link' => 'index.php?option=com_xdecarocompetitions&view=seasons'],
    ['key' => 'teams', 'label' => 'COM_XDECAROCOMPETITIONS_TEAMS', 'link' => 'index.php?option=com_xdecarocompetitions&view=teams'],
    ['key' => 'participations', 'label' => 'COM_XDECAROCOMPETITIONS_PARTICIPATIONS', 'link' => 'index.php?option=com_xdecarocompetitions&view=participations'],
    ['key' => 'players', 'label' => 'COM_XDECAROCOMPETITIONS_PLAYERS', 'link' => 'index.php?option=com_xdecarocompetitions&view=players'],
    ['key' => 'rosters', 'label' => 'COM_XDECAROCOMPETITIONS_ROSTERS', 'link' => 'index.php?option=com_xdecarocompetitions&view=rosters'],
    ['key' => 'matches', 'label' => 'COM_XDECAROCOMPETITIONS_MATCHES', 'link' => 'index.php?option=com_xdecarocompetitions&view=matches'],
    ['key' => 'events', 'label' => 'COM_XDECAROCOMPETITIONS_MATCH_EVENTS', 'link' => null],
];
?>
<div class="competitions-admin">
    <div class="competitions-dashboard-grid">
        <?php foreach ($cards as $card) : ?>
            <?php
            $content = '<strong class="competitions-dashboard-card__value">' . (int) ($this->counts[$card['key']] ?? 0) . '</strong>'
                . '<span class="competitions-dashboard-card__label">' . Text::_($card['label']) . '</span>'
                . '<span class="competitions-dashboard-card__hint">'
                . Text::_($card['link'] ? 'COM_XDECAROCOMPETITIONS_MANAGE' : 'COM_XDECAROCOMPETITIONS_COMING_SOON')
                . '</span>';
            ?>

            <?php if ($card['link']) : ?>
                <a class="competitions-dashboard-card" href="<?= Route::_($card['link']); ?>"><?= $content; ?></a>
            <?php else : ?>
                <div class="competitions-dashboard-card is-muted"><?= $content; ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info mt-4"><?= Text::_('COM_XDECAROCOMPETITIONS_DASHBOARD_PHASE_NOTE_080'); ?></div>
</div>
