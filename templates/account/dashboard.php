<?php
/** My account. @var array $form @var array $u */
$isAgent = $u['account_type'] === 'agent';
$cartCount = Cart::count();
?>
<section class="contact-hero account-hero">
    <div class="container contact-hero__inner">
        <div class="contact-hero__intro">
            <p class="contact-hero__tagline">My Account</p>
            <h1 class="contact-hero__title">Hello, <?= e($u['first_name']) ?>.</h1>
            <div class="contact-hero__text account-hero__text">
                <p><?= $isAgent ? 'You are signed in as an approved agent – agent / dealer prices are shown on products and in your cart.' : 'You are signed in to your customer account.' ?></p>
            </div>
            <dl class="account-facts">
                <div><dt>Account</dt><dd><?= e(UserAuth::TYPES[$u['account_type']] ?? $u['account_type']) ?><?= $isAgent && $u['company'] ? ' · ' . e($u['company']) : '' ?></dd></div>
                <div><dt>Email</dt><dd><?= e($u['email']) ?></dd></div>
                <div><dt>Member since</dt><dd><?= e(date('j F Y', strtotime($u['created_at']))) ?></dd></div>
            </dl>
            <div class="account-links">
                <a class="btn btn--dark btn--sm" href="<?= e(url('cart/')) ?>">View Cart<?= $cartCount ? ' (' . $cartCount . ')' : '' ?></a>
                <a class="text-link text-link--xs" href="<?= e(listing_url('store')) ?>">Browse products</a>
                <form method="post" action="<?= e(url('account/logout/')) ?>" class="account-logout"><?= csrf_field() ?><button class="text-link text-link--xs" type="submit">Log out</button></form>
            </div>
        </div>

        <div class="account-cards">
            <div class="contact-form-card" id="details">
                <h2 class="contact-form-card__title">My Details</h2>
                <?= form_notice($form) ?>
                <form class="contact-form" method="post" action="<?= e(url('account/')) ?>#details" novalidate>
                    <?= csrf_field() ?><input type="hidden" name="do" value="profile">
                    <div class="cf-grid">
                        <?= form_field('first_name', 'First Name', $form, ['required' => true, 'autocomplete' => 'given-name', 'max' => 100, 'value' => $form['old']['first_name'] ?? $u['first_name']]) ?>
                        <?= form_field('last_name', 'Last Name', $form, ['required' => true, 'autocomplete' => 'family-name', 'max' => 100, 'value' => $form['old']['last_name'] ?? $u['last_name']]) ?>
                    </div>
                    <?= form_field('phone', 'Phone Number', $form, ['type' => 'tel', 'autocomplete' => 'tel', 'max' => 40, 'value' => $form['old']['phone'] ?? (string)$u['phone']]) ?>
                    <label class="cf-check"><input type="checkbox" name="newsletter" value="1"<?= $u['newsletter'] ? ' checked' : '' ?>><span>I’d like to receive occasional updates from Arki.</span></label>
                    <p class="cf-help">To change your email address or company details, please <a href="<?= e(url('contact/')) ?>">contact us</a>.</p>
                    <button class="btn btn--dark contact-form__submit" type="submit">Save Details</button>
                </form>
            </div>

            <div class="contact-form-card" id="password">
                <h2 class="contact-form-card__title">Change Password</h2>
                <?= form_notice($form, 'form_pw') ?>
                <form class="contact-form" method="post" action="<?= e(url('account/')) ?>#password" novalidate>
                    <?= csrf_field() ?><input type="hidden" name="do" value="password">
                    <?= form_field('current_password', 'Current Password', $form, ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'max' => 200]) ?>
                    <div class="cf-grid">
                        <?= form_field('password', 'New Password', $form, ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'max' => 200]) ?>
                        <?= form_field('password_confirm', 'Confirm New Password', $form, ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'max' => 200]) ?>
                    </div>
                    <p class="cf-help">At least 8 characters, including a letter and a number.</p>
                    <button class="btn btn--dark contact-form__submit" type="submit">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</section>
