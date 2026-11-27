<?php /** @var string $from */ ?>
<section class="section">
    <div class="container container--narrow text-center">
        <h1 class="heading heading--lg">Product not found</h1>
        <p class="prose prose--muted">This product doesn't exist or is no longer available.</p>
        <a class="btn btn--dark btn--sm" href="<?= e(listing_url($from)) ?>">Browse products</a>
    </div>
</section>
