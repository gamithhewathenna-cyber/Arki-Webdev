<?php
/** Register – customer or agent. @var array $form @var string $type @var bool $done */
$agent = $type === 'agent';
$old = $form['old'];
?>
<section class="contact-hero account-hero">
    <div class="container contact-hero__inner">
        <div class="contact-hero__intro">
            <p class="contact-hero__tagline">My Account</p>
            <h1 class="contact-hero__title"><?= $agent ? 'Become An Agent.' : 'Join Us.' ?></h1>
            <div class="contact-hero__text account-hero__text">
                <?php if ($agent): ?>
                <p>Dealers, designers and resellers can apply for an agent account. Approved agents see agent / dealer pricing on every product.</p>
                <p>Your application is reviewed by our team – we’ll let you know once your account is approved.</p>
                <?php else: ?>
                <p>Create a customer account to keep your cart on any device and check out faster. Your account is ready to use straight away.</p>
                <?php endif; ?>
            </div>
            <div class="contact-direct">
                <h2 class="contact-direct__title">Already Registered?</h2>
                <div class="contact-direct__text"><p>Log in with your email address and password.</p></div>
                <div class="account-links"><a class="btn btn--dark btn--sm" href="<?= e(url('account/login/')) ?>">Log In</a></div>
            </div>
        </div>

        <div class="contact-form-card" id="register">
            <nav class="account-tabs" aria-label="Account type">
                <a class="account-tabs__tab<?= $agent ? '' : ' is-active' ?>" href="<?= e(url('account/register/')) ?>"<?= $agent ? '' : ' aria-current="page"' ?>>Customer</a>
                <a class="account-tabs__tab<?= $agent ? ' is-active' : '' ?>" href="<?= e(url('account/register/?type=agent')) ?>"<?= $agent ? ' aria-current="page"' : '' ?>>Agent / Dealer</a>
            </nav>
            <h2 class="contact-form-card__title"><?= $agent ? 'Agent Registration' : 'Create An Account' ?></h2>

            <?php if ($done): ?>
            <div class="cf-notice cf-notice--ok" role="status" tabindex="-1" data-focus>
                <p><strong>Thank you – your agent registration has been received.</strong></p>
                <p>Your account is pending approval. We’ll review your details and be in touch; once approved you can log in and see agent pricing.</p>
            </div>
            <p class="account-switch"><a href="<?= e(url()) ?>">Back to the website</a></p>
            <?php else: ?>
            <div class="contact-form-card__text"><p><?= $agent ? 'Tell us about you and your business. Fields marked * are required.' : 'Fields marked * are required.' ?></p></div>
            <?= form_notice($form) ?>
            <form class="contact-form" method="post" action="<?= e(url('account/register/' . ($agent ? '?type=agent' : ''))) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="<?= $agent ? 'agent' : 'customer' ?>">
                <div class="cf-grid">
                    <?= form_field('first_name', 'First Name', $form, ['required' => true, 'autocomplete' => 'given-name', 'max' => 100]) ?>
                    <?= form_field('last_name', 'Last Name', $form, ['required' => true, 'autocomplete' => 'family-name', 'max' => 100]) ?>
                    <?= form_field('email', 'Email Address', $form, ['type' => 'email', 'required' => true, 'autocomplete' => 'email']) ?>
                    <?= form_field('phone', 'Phone Number', $form, ['type' => 'tel', 'required' => $agent, 'autocomplete' => 'tel', 'max' => 40]) ?>
                </div>
                <?php if ($agent): ?>
                <div class="cf-grid">
                    <?= form_field('company', 'Company Name', $form, ['required' => true, 'autocomplete' => 'organization', 'max' => 150]) ?>
                    <?= form_field('abn', 'ABN', $form, ['autocomplete' => 'off', 'max' => 20]) ?>
                    <?= form_field('job_title', 'Your Role', $form, ['autocomplete' => 'organization-title', 'max' => 100]) ?>
                    <?= form_field('website', 'Company Website', $form, ['type' => 'url', 'autocomplete' => 'url', 'max' => 255]) ?>
                </div>
                <div class="cf-field cf-field--select">
                    <label class="sr-only" for="ff-state">State</label>
                    <select id="ff-state" name="state">
                        <option value="">State / territory</option>
                        <?php foreach (UserAuth::STATES as $st): ?><option value="<?= e($st) ?>"<?= ($old['state'] ?? '') === $st ? ' selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?>
                    </select><?= icon('chevron-down') ?>
                </div>
                <div class="cf-field cf-field--area cf-field--short">
                    <textarea id="ff-about" name="about" placeholder=" " rows="4" maxlength="3000"><?= e((string)($old['about'] ?? '')) ?></textarea>
                    <label for="ff-about">Tell us about your business (optional)</label>
                </div>
                <?php endif; ?>
                <div class="cf-grid">
                    <?= form_field('password', 'Password', $form, ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'max' => 200, 'help' => 'At least 8 characters, including a letter and a number.']) ?>
                    <?= form_field('password_confirm', 'Confirm Password', $form, ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'max' => 200]) ?>
                </div>
                <label class="cf-check<?= isset($form['errors']['terms']) ? ' is-invalid' : '' ?>"><input type="checkbox" name="terms" value="1"<?= !empty($old['terms']) ? ' checked' : '' ?> required><span>I agree to the terms and conditions and privacy policy. <span class="cf-req" aria-hidden="true">*</span></span></label>
                <?php if (isset($form['errors']['terms'])): ?><p class="cf-error">Please accept the terms and privacy policy.</p><?php endif; ?>
                <label class="cf-check"><input type="checkbox" name="newsletter" value="1"<?= !empty($old['newsletter']) ? ' checked' : '' ?>><span>I’d like to receive occasional updates from Arki.</span></label>
                <button class="btn btn--dark contact-form__submit" type="submit"><?= $agent ? 'Submit Application' : 'Create Account' ?></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>
