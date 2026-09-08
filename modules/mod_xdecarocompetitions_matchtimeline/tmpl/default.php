<?php
/**
 * @package     Competitions
 * @subpackage  mod_xdecarocompetitions_matchtimeline
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/** @var array $events */
/** @var int $matchId */
/** @var int $articleId */
/** @var Joomla\Registry\Registry $params */

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle(
    'mod_xdecarocompetitions_matchtimeline.site',
    'media/mod_xdecarocompetitions_matchtimeline/css/site.css',
    [],
    ['version' => 'auto']
);

$iconMap = [
    'goal' => ['class' => 'is-goal', 'label' => Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_EVENT_GOAL'), 'symbol' => '⚽'],
    'penalty_goal' => ['class' => 'is-goal', 'label' => Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_EVENT_PENALTY_GOAL'), 'symbol' => '⚽'],
    'own_goal' => ['class' => 'is-own-goal', 'label' => Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_EVENT_OWN_GOAL'), 'symbol' => '⚽'],
    'yellow_card' => ['class' => 'is-yellow-card', 'label' => Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_EVENT_YELLOW_CARD'), 'symbol' => ''],
    'red_card' => ['class' => 'is-red-card', 'label' => Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_EVENT_RED_CARD'), 'symbol' => ''],
    'substitution' => ['class' => 'is-substitution', 'label' => Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_EVENT_SUBSTITUTION'), 'symbol' => '↔'],
];

if (!$events) {
    if ((int) $params->get('empty_message', 0) === 1) {
        echo '<div class="competitions-match-timeline__empty">'
            . htmlspecialchars(Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_NO_EVENTS'), ENT_QUOTES, 'UTF-8')
            . '</div>';
    }

    return;
}
?>
<div class="competitions-match-timeline" data-match-id="<?= (int) $matchId; ?>" data-article-id="<?= (int) $articleId; ?>">
    <?php if ((int) $params->get('show_title', 1) === 1) : ?>
        <h3 class="competitions-match-timeline__title"><?= htmlspecialchars(Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <?php endif; ?>

    <ol class="competitions-match-timeline__list">
        <?php foreach ($events as $event) : ?>
            <?php
            $side = $event->side === 'away' ? 'away' : 'home';
            $type = $iconMap[$event->event_type] ?? [
                'class' => 'is-generic',
                'label' => ucfirst(str_replace('_', ' ', (string) $event->event_type)),
                'symbol' => '•',
            ];

            $playerName = trim(
                trim((string) ($event->first_name ?? ''))
                . ' '
                . trim((string) ($event->last_name ?? ''))
            );

            if ($playerName === '') {
                $playerName = trim((string) ($event->player_name_override ?? ''));
            }

            if ($playerName === '') {
                $playerName = Text::_('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_UNKNOWN_PLAYER');
            }

            $minute = (int) $event->minute;
            $extraMinute = (int) $event->extra_minute;
            $minuteLabel = $extraMinute > 0
                ? $minute . '+' . $extraMinute . "'"
                : $minute . "'";
            ?>
            <li class="competitions-match-timeline__item is-<?= htmlspecialchars($side, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="competitions-match-timeline__side competitions-match-timeline__side--home">
                    <?php if ($side === 'home') : ?>
                        <span class="competitions-match-timeline__player"><?= htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="competitions-match-timeline__event <?= htmlspecialchars($type['class'], ENT_QUOTES, 'UTF-8'); ?>" role="img" aria-label="<?= htmlspecialchars($type['label'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($type['symbol'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>

                <time class="competitions-match-timeline__minute" aria-label="<?= htmlspecialchars(Text::sprintf('MOD_XDECAROCOMPETITIONS_MATCHTIMELINE_MINUTE_LABEL', $minuteLabel), ENT_QUOTES, 'UTF-8'); ?>">
                    <?= htmlspecialchars($minuteLabel, ENT_QUOTES, 'UTF-8'); ?>
                </time>

                <div class="competitions-match-timeline__side competitions-match-timeline__side--away">
                    <?php if ($side === 'away') : ?>
                        <span class="competitions-match-timeline__event <?= htmlspecialchars($type['class'], ENT_QUOTES, 'UTF-8'); ?>" role="img" aria-label="<?= htmlspecialchars($type['label'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($type['symbol'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="competitions-match-timeline__player"><?= htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
