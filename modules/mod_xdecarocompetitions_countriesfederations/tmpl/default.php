<?php
/**
 * @package     Competitions Countries & Federations
 * @subpackage  mod_xdecarocompetitions_countriesfederations
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$moduleId = 'competitions-cf-' . (int) $module->id;
?>
<div class="competitions-cf" id="<?= htmlspecialchars($moduleId, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($showSearch) : ?>
        <div class="competitions-cf__toolbar">
            <label class="visually-hidden" for="<?= htmlspecialchars($moduleId . '-search', ENT_QUOTES, 'UTF-8'); ?>">
                <?= Text::_('MOD_XDECAROCOMPETITIONS_COUNTRIESFEDERATIONS_SEARCH_LABEL'); ?>
            </label>
            <input
                class="competitions-cf__search"
                id="<?= htmlspecialchars($moduleId . '-search', ENT_QUOTES, 'UTF-8'); ?>"
                type="search"
                placeholder="<?= htmlspecialchars(Text::_('MOD_XDECAROCOMPETITIONS_COUNTRIESFEDERATIONS_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
                data-competitions-cf-search
            >
        </div>
    <?php endif; ?>

    <div class="competitions-cf__list" data-competitions-cf-list>
        <?php foreach ($countries as $country) : ?>
            <?php
            $searchText = trim($country->name . ' ' . $country->code . ' ' . implode(' ', array_map(
                static fn ($federation) => trim($federation->name . ' ' . $federation->short_name),
                $country->federations
            )));
            ?>
            <article class="competitions-cf__country" data-competitions-cf-item data-search="<?= htmlspecialchars(mb_strtolower($searchText), ENT_QUOTES, 'UTF-8'); ?>">
                <header class="competitions-cf__country-head">
                    <div class="competitions-cf__country-main">
                        <?php if ($country->flag !== '') : ?>
                            <?= HTMLHelper::_('cleanImageURL', $country->flag)->url ? '<img class="competitions-cf__flag" src="' . htmlspecialchars(HTMLHelper::_('cleanImageURL', $country->flag)->url, ENT_QUOTES, 'UTF-8') . '" alt="">' : ''; ?>
                        <?php endif; ?>
                        <div>
                            <h3 class="competitions-cf__country-name"><?= htmlspecialchars($country->name, ENT_QUOTES, 'UTF-8'); ?></h3>
                            <span class="competitions-cf__code"><?= htmlspecialchars($country->code, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                    <?php if (!$country->state) : ?>
                        <span class="competitions-cf__status"><?= Text::_('MOD_XDECAROCOMPETITIONS_COUNTRIESFEDERATIONS_INACTIVE'); ?></span>
                    <?php endif; ?>
                </header>

                <?php if ($country->federations) : ?>
                    <div class="competitions-cf__federations">
                        <?php foreach ($country->federations as $federation) : ?>
                            <div class="competitions-cf__federation">
                                <div class="competitions-cf__federation-main">
                                    <?php if ($federation->logo !== '') : ?>
                                        <?php $logo = HTMLHelper::_('cleanImageURL', $federation->logo); ?>
                                        <?php if (!empty($logo->url)) : ?>
                                            <img class="competitions-cf__logo" src="<?= htmlspecialchars($logo->url, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($federation->name, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if ($federation->short_name !== '') : ?>
                                            <span class="competitions-cf__short"><?= htmlspecialchars($federation->short_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="competitions-cf__meta">
                                    <?php if ($federation->website !== '') : ?>
                                        <a href="<?= htmlspecialchars($federation->website, ENT_QUOTES, 'UTF-8'); ?>" rel="noopener noreferrer" target="_blank">
                                            <?= Text::_('MOD_XDECAROCOMPETITIONS_COUNTRIESFEDERATIONS_WEBSITE'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$federation->state) : ?>
                                        <span class="competitions-cf__status"><?= Text::_('MOD_XDECAROCOMPETITIONS_COUNTRIESFEDERATIONS_INACTIVE'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="competitions-cf__empty"><?= Text::_('MOD_XDECAROCOMPETITIONS_COUNTRIESFEDERATIONS_NO_FEDERATION'); ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="competitions-cf__no-results" data-competitions-cf-empty hidden><?= Text::_('MOD_XDECAROCOMPETITIONS_COUNTRIESFEDERATIONS_NO_RESULTS'); ?></p>
</div>
