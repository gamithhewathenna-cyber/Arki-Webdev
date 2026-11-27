<?php /** @var string $error @var string $siteName */ ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Private preview – <?= e($siteName) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
</head>
<body class="gate">
<main class="gate__box">
    <p class="eyebrow eyebrow--sm">Private preview</p>
    <h1 class="heading heading--lg"><?= e($siteName) ?></h1>
    <p class="gate__note">This website is under development. Enter the preview password to continue.</p>
    <?php if ($error): ?><p class="gate__error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="gate__form" autocomplete="off">
        <?= csrf_field() ?>
        <label class="sr-only" for="dev_password">Password</label>
        <input id="dev_password" name="dev_password" type="password" required autofocus placeholder="Password">
        <button class="btn btn--dark" type="submit">Enter</button>
    </form>
</main>
</body>
</html>
