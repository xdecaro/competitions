<?php
defined('_JEXEC') or die;

$eyebrow = htmlspecialchars((string) ($displayData['eyebrow'] ?? ''), ENT_QUOTES, 'UTF-8');
$title = htmlspecialchars((string) ($displayData['title'] ?? ''), ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars((string) ($displayData['description'] ?? ''), ENT_QUOTES, 'UTF-8');
$modifier = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($displayData['class'] ?? ''));
?>
<header class="competitions-page-header<?= $modifier !== '' ? ' ' . $modifier : ''; ?>">
    <span class="competitions-page-header__eyebrow"><?= $eyebrow; ?></span>
    <h1 class="competitions-page-header__title"><?= $title; ?></h1>
    <?php if ($description !== '') : ?>
        <p class="competitions-page-header__description"><?= $description; ?></p>
    <?php endif; ?>
</header>
