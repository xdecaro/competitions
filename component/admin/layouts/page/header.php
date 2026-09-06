<?php
defined('_JEXEC') or die;

$eyebrow = htmlspecialchars((string) ($displayData['eyebrow'] ?? ''), ENT_QUOTES, 'UTF-8');
$title = htmlspecialchars((string) ($displayData['title'] ?? ''), ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars((string) ($displayData['description'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<header class="dcl-page-header">
    <span class="dcl-page-header__eyebrow"><?= $eyebrow; ?></span>
    <h1 class="dcl-page-header__title"><?= $title; ?></h1>
    <?php if ($description !== '') : ?>
        <p class="dcl-page-header__description"><?= $description; ?></p>
    <?php endif; ?>
</header>
