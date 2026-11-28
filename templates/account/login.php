<?php /** Log in. @var array $form @var string $next */ ?>
<section class="contact-hero account-hero">
    <div class="container contact-hero__inner">
        <div class="contact-hero__intro">
            <p class="contact-hero__tagline">My Account</p>
            <h1 class="contact-hero__title">Welcome Back.</h1>
            <div class="contact-hero__text account-hero__text">
                <p>Log in to see your cart and account details. Approved agents and dealers also see their agent pricing.</p>
            </div>
            <div class="contact-direct">
                <h2 class="contact-direct__title">New Here?</h2>
                <div class="contact-direct__text"><p>Create a customer account in a minute, or apply for an agent / dealer account.</p></div>
                <div class="account-links">
                    <a class="btn btn--dark btn--sm" href="<?= e(url('account/register/')) ?>">Create An Account</a>
                    <a class="text-link text-link--xs" href="<?= e(url('account/register/?type=agent')) ?>">Register as an agent</a>
                </div>
            </div>
        </div>

        <div class="contact-form-card" id="login">
            <h2 class="contact-form-card__title">Log In</h2>
            <div class="contact-form-card__text"><p>Use the email address and password of your account.</p></div>
            <?= form_notice($form) ?>
            <form class="contact-form" method="post" action="<?= e(url('account/login/')) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <?= form_field('email', 'Email Address', $form, ['type' => 'email', 'required' => true, 'autocomplete' => 'email']) ?>
                <?= form_field('password', 'Password', $form, ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'max' => 200]) ?>
                <div class="account-row">
                    <label class="cf-check"><input type="checkbox" name="remember" value="1"<?= !empty($form['old']['remember']) ? ' checked' : '' ?>><span>Remember me</span></label>
                    <a class="text-link text-link--xs" href="<?= e(url('account/forgot-password/')) ?>">Forgot password?</a>
                </div>
                <button class="btn btn--dark contact-form__submit" type="submit">Log In</button>
                <p class="account-switch">Don’t have an account? <a href="<?= e(url('account/register/')) ?>">Create an account</a></p>
            </form>
        </div>
    </div>
</section>
