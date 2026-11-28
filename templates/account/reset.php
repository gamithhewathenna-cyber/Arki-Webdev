<?php /** Set a new password from a reset link. @var array $form @var string $token @var bool $valid */ ?>
<section class="contact-hero account-hero">
    <div class="container contact-hero__inner">
        <div class="contact-hero__intro">
            <p class="contact-hero__tagline">My Account</p>
            <h1 class="contact-hero__title">Choose A New Password.</h1>
        </div>
        <div class="contact-form-card">
            <h2 class="contact-form-card__title">New Password</h2>
            <?php if (!$valid): ?>
            <div class="cf-notice cf-notice--error" role="alert"><p>This reset link is invalid or has expired.</p></div>
            <p class="account-switch"><a href="<?= e(url('account/forgot-password/')) ?>">Request a new link</a></p>
            <?php else: ?>
            <?= form_notice($form) ?>
            <form class="contact-form" method="post" action="<?= e(url('account/reset-password/')) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="t" value="<?= e($token) ?>">
                <?= form_field('password', 'New Password', $form, ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'max' => 200, 'help' => 'At least 8 characters, including a letter and a number.']) ?>
                <?= form_field('password_confirm', 'Confirm New Password', $form, ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'max' => 200]) ?>
                <button class="btn btn--dark contact-form__submit" type="submit">Save New Password</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>
