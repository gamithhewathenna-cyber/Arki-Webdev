<?php
/** Contact page: intro + contact buttons (left) and enquiry form (right). @var array $s */
$d = $s['data'];
$form = ContactForm::state();
$err = $form['errors'];
$old = $form['old'];
$topics = ContactForm::topics($d);
$val = static fn(string $k) => e((string)($old[$k] ?? ''));
$field = static function (string $name, string $label, string $type, bool $required, array $err, callable $val, string $auto) {
    $id = 'cf-' . $name;
    $bad = isset($err[$name]);
    return '<div class="cf-field' . ($bad ? ' is-invalid' : '') . '">'
        . '<input id="' . $id . '" name="' . $name . '" type="' . $type . '" placeholder=" " value="' . $val($name) . '"'
        . ($required ? ' required aria-required="true"' : '') . ' autocomplete="' . $auto . '"'
        . ($bad ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '') . ' maxlength="' . ($type === 'email' ? 190 : 100) . '">'
        . '<label for="' . $id . '">' . e($label) . ($required ? ' <span class="cf-req" aria-hidden="true">*</span>' : '') . '</label>'
        . ($bad ? '<p class="cf-error" id="' . $id . '-err">' . e($err[$name]) . '</p>' : '')
        . '</div>';
};
?>
<section class="contact-hero">
    <div class="container contact-hero__inner">
        <div class="contact-hero__intro">
            <?php if (!empty($d['tagline'])): ?><p class="contact-hero__tagline"><?= e($d['tagline']) ?></p><?php endif; ?>
            <h1 class="contact-hero__title"><?= lines(($d['heading'] ?? '') ?: 'Contact Us') ?></h1>
            <?php if (!empty($d['body'])): ?><div class="contact-hero__text"><?= paragraphs($d['body']) ?></div><?php endif; ?>

            <?php if (!empty($d['direct_heading']) || !empty($d['direct_text']) || $s['items']): ?>
            <div class="contact-direct">
                <?php if (!empty($d['direct_heading'])): ?><h2 class="contact-direct__title"><?= e($d['direct_heading']) ?></h2><?php endif; ?>
                <?php if (!empty($d['direct_text'])): ?><div class="contact-direct__text"><?= paragraphs($d['direct_text']) ?></div><?php endif; ?>
                <?php if ($s['items']): ?>
                <ul class="contact-buttons">
                    <?php foreach ($s['items'] as $it): $c = $it['data']; $tag = !empty($c['url']) ? 'a' : 'div'; ?>
                    <li class="<?= ($c['width'] ?? 'full') === 'half' ? 'is-half' : 'is-full' ?>">
                        <<?= $tag ?> class="contact-button"<?= $tag === 'a' ? ' href="' . e(safe_url($c['url'])) . '"' . link_attrs($c['url']) : '' ?>>
                            <span class="contact-button__icon"><?= icon(in_array($c['icon'] ?? '', ['email', 'phone', 'location', 'link'], true) ? $c['icon'] : 'link') ?></span>
                            <span class="contact-button__text">
                                <?php if (!empty($c['label'])): ?><small><?= e($c['label']) ?></small><?php endif; ?>
                                <strong><?= e($c['value'] ?? '') ?></strong>
                            </span>
                        </<?= $tag ?>>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="contact-form-card" id="contact-form">
            <?php if (!empty($d['form_heading'])): ?><h2 class="contact-form-card__title"><?= e($d['form_heading']) ?></h2><?php endif; ?>
            <?php if (!empty($d['form_text'])): ?><div class="contact-form-card__text"><?= paragraphs($d['form_text']) ?></div><?php endif; ?>

            <?php if ($form['sent']): ?>
            <div class="cf-notice cf-notice--ok" role="status" tabindex="-1" data-focus>
                <?= paragraphs(($d['success_message'] ?? '') ?: 'Thank you – your message has been sent.') ?>
            </div>
            <?php elseif ($err): ?>
            <div class="cf-notice cf-notice--error" role="alert" tabindex="-1" data-focus>
                <p><?= e($err['form'] ?? 'Please check the highlighted fields and try again.') ?></p>
            </div>
            <?php endif; ?>

            <form class="contact-form" method="post" action="<?= e(url('contact/')) ?>#contact-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="contact_form" value="1">
                <?php /* Honeypot – hidden from people, bots fill it in */ ?>
                <div class="cf-hp" aria-hidden="true"><label for="cf-website">Website</label><input id="cf-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>

                <div class="cf-grid">
                    <?= $field('first_name', 'First Name', 'text', true, $err, $val, 'given-name') ?>
                    <?= $field('last_name', 'Last Name', 'text', true, $err, $val, 'family-name') ?>
                    <?= $field('email', 'Email Address', 'email', true, $err, $val, 'email') ?>
                    <?= $field('phone', 'Phone Number', 'tel', false, $err, $val, 'tel') ?>
                </div>

                <?php if ($topics): ?>
                <div class="cf-field cf-field--select">
                    <label class="sr-only" for="cf-topic">I’m enquiring about</label>
                    <select id="cf-topic" name="topic">
                        <option value="">I’m enquiring about…</option>
                        <?php foreach ($topics as $t): ?>
                        <option value="<?= e($t) ?>"<?= ($old['topic'] ?? '') === $t ? ' selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= icon('chevron-down') ?>
                </div>
                <?php endif; ?>

                <div class="cf-field cf-field--area<?= isset($err['message']) ? ' is-invalid' : '' ?>">
                    <textarea id="cf-message" name="message" placeholder=" " rows="7" maxlength="5000" required aria-required="true"<?= isset($err['message']) ? ' aria-invalid="true" aria-describedby="cf-message-err"' : '' ?>><?= $val('message') ?></textarea>
                    <label for="cf-message">Your message <span class="cf-req" aria-hidden="true">*</span></label>
                    <?php if (isset($err['message'])): ?><p class="cf-error" id="cf-message-err"><?= e($err['message']) ?></p><?php endif; ?>
                </div>

                <?php if (!empty($d['newsletter_label'])): ?>
                <label class="cf-check"><input type="checkbox" name="newsletter" value="1"<?= !empty($old['newsletter']) ? ' checked' : '' ?>><span><?= e($d['newsletter_label']) ?></span></label>
                <?php endif; ?>

                <button class="btn btn--dark contact-form__submit" type="submit"><?= e(($d['button_label'] ?? '') ?: 'Send Enquiry') ?></button>
            </form>
        </div>
    </div>
</section>
