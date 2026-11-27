<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<!-- Development stage: keep out of search engines -->
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<meta name="googlebot" content="noindex, nofollow">
<meta name="theme-color" content="#f5f5f5">
<link rel="preload" href="<?= e(url('assets/fonts/noto-serif-latin-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
<?php $fav = Settings::get('favicon'); if ($fav): ?>
<link rel="icon" href="<?= e(upload_url($fav)) ?>">
<?php endif; ?>
