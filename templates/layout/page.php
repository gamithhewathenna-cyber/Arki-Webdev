<?php
/** @var string $pageTitle @var string $bodyClass @var array $global @var array $sections */
?><!DOCTYPE html>
<html lang="en">
<head>
<?php render('layout/head', ['pageTitle' => $pageTitle]); ?>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php $hasBanner = isset($sections['banner']); ?>
<?php render('layout/header', ['s' => $global['header'] ?? null, 'nav' => $global['navigation'] ?? null, 'overlay' => $hasBanner]); ?>

<main id="main">
<?php foreach ($sections as $key => $section): ?>
<?php render('sections/' . preg_replace('/[^a-z0-9_\-]/', '', (string)$key), ['s' => $section, 'hasBanner' => $hasBanner, 'pageKey' => $pageKey ?? 'home']); ?>
<?php endforeach; ?>
<?php if (!empty($content)) render($content, $contentVars ?? []); /* product listing / product page */ ?>
</main>

<?php render('layout/footer', [
    's'        => $global['footer'] ?? null,
    'links'    => $global['footer_links'] ?? null,
    'contacts' => $global['footer_contacts'] ?? null,
]); ?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
