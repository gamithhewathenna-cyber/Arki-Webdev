<?php
/** @var string $pageTitle @var string $bodyClass @var array $global @var array $sections */
?><!DOCTYPE html>
<html lang="en">
<head>
<?php render('layout/head', ['pageTitle' => $pageTitle]); ?>
</head>
<body class="<?= e($bodyClass) ?>">
<?php if (Construction::enabled() && Auth::check()): /* only administrators ever get past the holding page */ ?>
<div class="admin-preview-bar" role="note">Under Construction Mode is on – visitors see the holding page. You are previewing as an administrator.<a href="<?= e(url('admin/settings.php')) ?>">Change</a></div>
<?php endif; ?>
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
<?php render('layout/popup'); /* Promo popup – CMS → Promo Popup */ ?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
