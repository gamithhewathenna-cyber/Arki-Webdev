<?php
/** Under Construction page (Admin → Website Settings). @var string $siteName @var string $heading @var string $message @var array $header */
$logo = (string)($header['logo'] ?? '');
?><!DOCTYPE html>
<html lang="en">
<head>
<?php render('layout/head', ['pageTitle' => $siteName . ' – Under Construction']); ?>
</head>
<body class="construction">
<main class="construction__box">
    <div class="construction__logo"><?= picture($logo, (string)(($header['logo_alt'] ?? '') ?: $siteName), ['eager' => true]) ?: '<span class="site-header__wordmark">' . e($siteName) . '</span>' ?></div>
    <p class="eyebrow eyebrow--sm">Under Construction</p>
    <h1 class="construction__title"><?= lines($heading) ?></h1>
    <div class="construction__text"><?= paragraphs($message) ?></div>
</main>
</body>
</html>
