<?php /** Forgot password. @var array $form @var bool $sent */ ?>
<section class="contact-hero account-hero">
    <div class="container contact-hero__inner">
        <div class="contact-hero__intro">
            <p class="contact-hero__tagline">My Account</p>
            <h1 class="contact-hero__title">Forgot Your Password?</h1>
            <div class="contact-hero__text account-hero__text">
                <p>Enter the email address of your account and we’ll send you a link to choose a new password.</p>
            </div>
        </div>
        <div class="contact-form-card">
            <h2 class="contact-form-card__title">Reset Password</h2>
            <?php if ($sent): ?>
            <div class="cf-notice cf-notice--ok" role="status" tabindex="-1" data-focus>
                <p>If an account exists for that email address, a password reset link has been prepared.</p>
                <p>Password emails are not switched on yet while the website is being built – if you need help logging in now, please <a href="<?= e(url('contact/')) ?>">contact us</a>.</p>
            </div>
            <p class="account-switch"><a href="<?= e(url('account/login/')) ?>">Back to log in</a></p>
            <?php else: ?>
            <?= form_notice($form) ?>
            <form class="contact-form" method="post" action="<?= e(url('account/forgot-password/')) ?>" novalidate>
                <?= csrf_field() ?>
                <?= form_field('email', 'Email Address', $form, ['type' => 'email', 'required' => true, 'autocomplete' => 'email']) ?>
                <button class="btn btn--dark contact-form__submit" type="submit">Send Reset Link</button>
                <p class="account-switch"><a href="<?= e(url('account/login/')) ?>">Back to log in</a></p>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>
