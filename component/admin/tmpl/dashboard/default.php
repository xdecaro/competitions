<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$cards = [
    ['key' => 'countries', 'label' => 'COM_DECARODCL_COUNTRIES', 'link' => 'index.php?option=com_decarodcl&view=countries'],
    ['key' => 'federations', 'label' => 'COM_DECARODCL_FEDERATIONS', 'link' => 'index.php?option=com_decarodcl&view=federations'],
    ['key' => 'tournaments', 'label' => 'COM_DECARODCL_TOURNAMENTS', 'link' => 'index.php?option=com_decarodcl&view=tournaments'],
    ['key' => 'seasons', 'label' => 'COM_DECARODCL_SEASONS', 'link' => 'index.php?option=com_decarodcl&view=seasons'],
    ['key' => 'teams', 'label' => 'COM_DECARODCL_TEAMS', 'link' => null],
    ['key' => 'players', 'label' => 'COM_DECARODCL_PLAYERS', 'link' => null],
    ['key' => 'participations', 'label' => 'COM_DECARODCL_PARTICIPATIONS', 'link' => null],
    ['key' => 'events', 'label' => 'COM_DECARODCL_MATCH_EVENTS', 'link' => null],
];
?>
<div class="dcl-admin">
    <div class="dcl-admin__intro"><h2><?= Text::_('COM_DECARODCL_DASHBOARD_TITLE'); ?></h2><p><?= Text::_('COM_DECARODCL_DASHBOARD_DESC'); ?></p></div>
    <div class="dcl-dashboard-grid">
        <?php foreach ($cards as $card) : ?>
            <?php $content = '<strong class="dcl-dashboard-card__value">' . (int) ($this->counts[$card['key']] ?? 0) . '</strong><span class="dcl-dashboard-card__label">' . Text::_($card['label']) . '</span><span class="dcl-dashboard-card__hint">' . Text::_($card['link'] ? 'COM_DECARODCL_MANAGE' : 'COM_DECARODCL_COMING_SOON') . '</span>'; ?>
            <?php if ($card['link']) : ?><a class="dcl-dashboard-card" href="<?= Route::_($card['link']); ?>"><?= $content; ?></a><?php else : ?><div class="dcl-dashboard-card is-muted"><?= $content; ?></div><?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="alert alert-info mt-4"><?= Text::_('COM_DECARODCL_DASHBOARD_PHASE_NOTE'); ?></div>
</div>
