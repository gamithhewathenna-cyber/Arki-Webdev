<?php
/**
 * Shopping cart. @var array $form @var array $lines @var float $subtotal @var string $currency
 * @var bool $unavailable @var bool $isAgent @var array|null $user
 */
$count = array_sum(array_column(array_filter($lines, static fn($l) => $l['available']), 'qty'));
?>
<section class="cart">
    <div class="container">
        <header class="cart__head">
            <h1 class="heading heading--xl">Shopping Cart</h1>
            <p class="cart__count"><?= $count ?> item<?= $count === 1 ? '' : 's' ?></p>
        </header>
        <?= form_notice($form) ?>

        <?php if (!$lines): ?>
        <div class="cart-empty">
            <p class="cart-empty__title">Your cart is empty.</p>
            <a class="btn btn--dark btn--sm" href="<?= e(listing_url('store')) ?>">Continue Shopping</a>
        </div>
        <?php else: ?>
        <div class="cart__layout">
            <form class="cart-items" method="post" action="<?= e(url('cart/')) ?>" id="cart-form">
                <?= csrf_field() ?>
                <input type="hidden" name="do" value="update">
                <div class="cart-items__head" aria-hidden="true"><span>Product</span><span>Price</span><span>Quantity</span><span>Total</span></div>
                <ul class="cart-list">
                    <?php foreach ($lines as $l): $href = product_url($l['slug']); ?>
                    <li class="cart-line<?= $l['available'] ? '' : ' is-unavailable' ?>">
                        <a class="cart-line__media" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true"><?= picture($l['image'] ?? '', '') ?></a>
                        <div class="cart-line__info">
                            <a class="cart-line__name" href="<?= e($href) ?>"><?= e($l['name']) ?></a>
                            <p class="cart-line__meta"><?= e($l['sku']) ?><?php foreach ($l['options'] as $k => $v): ?> · <?= e($k) ?>: <?= e($v) ?><?php endforeach; ?></p>
                            <?php if (!$l['available']): ?><p class="cart-line__warn">No longer available – please remove it.</p><?php endif; ?>
                            <?php if ($l['agent_price']): ?><p class="cart-line__agent">Agent price</p><?php endif; ?>
                        </div>
                        <p class="cart-line__price"><span class="cart-line__label">Price</span><?= $l['price'] !== null ? e(money($l['price'], $l['currency'])) : 'On request' ?></p>
                        <div class="cart-line__qty">
                            <span class="cart-line__label">Quantity</span>
                            <div class="cart-qty">
                                <button type="submit" name="step" value="<?= $l['id'] ?>:-1" aria-label="One less <?= e($l['name']) ?>">−</button>
                                <input type="number" name="qty[<?= $l['id'] ?>]" value="<?= $l['qty'] ?>" min="0" max="999" inputmode="numeric" aria-label="Quantity of <?= e($l['name']) ?>" data-cart-qty>
                                <button type="submit" name="step" value="<?= $l['id'] ?>:1" aria-label="One more <?= e($l['name']) ?>">+</button>
                            </div>
                        </div>
                        <p class="cart-line__total"><span class="cart-line__label">Total</span><?= $l['line'] !== null && $l['available'] ? e(money($l['line'], $l['currency'])) : '–' ?></p>
                        <button class="cart-line__remove" type="submit" form="remove-<?= $l['id'] ?>" aria-label="Remove <?= e($l['name']) ?>" title="Remove"><?= icon('close') ?></button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="cart-items__foot">
                    <a class="text-link text-link--xs" href="<?= e(listing_url('store')) ?>">← Continue Shopping</a>
                    <button class="btn btn--sm cart-update" type="submit">Update Cart</button>
                </div>
            </form>
            <?php foreach ($lines as $l): ?>
            <form id="remove-<?= $l['id'] ?>" method="post" action="<?= e(url('cart/')) ?>" hidden><?= csrf_field() ?><input type="hidden" name="do" value="remove"><input type="hidden" name="item" value="<?= $l['id'] ?>"></form>
            <?php endforeach; ?>

            <aside class="cart-summary" aria-label="Order summary">
                <h2 class="cart-summary__title">Order Summary</h2>
                <dl class="cart-summary__rows">
                    <div><dt>Subtotal</dt><dd><?= e(money($subtotal, $currency)) ?></dd></div>
                    <div><dt>Shipping</dt><dd>Calculated at checkout</dd></div>
                    <div class="cart-summary__total"><dt>Total</dt><dd><?= e(money($subtotal, $currency)) ?></dd></div>
                </dl>
                <p class="cart-summary__note"><?= $isAgent ? 'Agent / dealer prices applied.' : 'Prices shown in ' . e($currency) . '.' ?><?= $unavailable ? ' Unavailable items are not included.' : '' ?></p>
                <?php /* Checkout comes in a later phase: the button is shown, not active yet. */ ?>
                <button class="btn btn--dark cart-summary__checkout" type="button" disabled aria-disabled="true" title="Checkout is coming soon">Proceed To Checkout</button>
                <p class="cart-summary__soon">Online checkout is coming soon. To order now, <a href="<?= e(url('contact/')) ?>">send us an enquiry</a>.</p>
                <a class="btn cart-summary__continue" href="<?= e(listing_url('store')) ?>">Continue Shopping</a>
                <?php if (!$user): ?><p class="cart-summary__login"><a href="<?= e(url('account/login/?next=' . rawurlencode(url('cart/')))) ?>">Log in</a> to keep your cart on any device.</p><?php endif; ?>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</section>
