<?php
/**
 * Single product page.
 * @var array $p @var string $from @var bool $preview @var array $gallery @var array $avail
 * @var array $options @var array $variants @var array $specs @var array $downloads @var array $drawings
 * @var array $family @var array|null $familyRow @var array $related @var array $settings @var array|null $cta
 */
$listing = listing_url($from);
/** @var array|null $crumb the product's category in this storefront */
$catSlug = $crumb['slug'] ?? null;
$catName = $crumb['name'] ?? null;
$price = price_html($p);
$main = $gallery[0] ?? null;

// Information tabs – empty ones are left out.
$tech = array_filter([
    'Product Dimensions'       => $p['dimensions'] ?? '',
    'Product Weight'           => $p['weight'] ?? '',
    'Technical Details'        => $p['technical_details'] ?? '',
    'Assembly Information'     => $p['assembly_info'] ?? '',
    'Maintenance Instructions' => $p['maintenance'] ?? '',
], static fn($v) => trim((string)$v) !== '');
$tabs = array_filter([
    'description'    => trim(strip_tags((string)$p['description'])) !== '' ? 'Description' : null,
    'specifications' => ($specs || trim((string)$p['suitable_for']) !== '') ? 'Specifications' : null,
    'technical'      => ($tech || $drawings) ? 'Technical Information' : null,
    'downloads'      => $downloads ? 'Downloads' : null,
]);
$firstTab = array_key_first($tabs);
$videoEmbed = video_embed_url($p['video_url'] ?? '');
$videoFile = !empty($p['video_file']) && is_file(ROOT_PATH . '/' . $p['video_file']) ? url($p['video_file']) : null;
$hasVideo = $videoEmbed || $videoFile;
$showMedia = $p['sections']['media'] && ($hasVideo || !empty($p['collection_image']));
$fmtSize = static fn(int $b) => $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, (int)round($b / 1024)) . ' KB';
?>
<div class="product-page">
    <div class="container">
        <?php if ($preview): ?>
        <p class="product-preview-note">Preview – this product is <strong><?= e(ProductRepository::STATUSES[$p['status']] ?? $p['status']) ?></strong> and only visible to logged-in administrators.</p>
        <?php endif; ?>

        <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
                <li><a href="<?= e(url()) ?>">Home</a></li>
                <li><a href="<?= e($listing) ?>">Product</a></li>
                <?php if ($catSlug): ?><li><a href="<?= e(listing_url($from, ['cat' => [$catSlug]])) ?>"><?= e($catName) ?></a></li><?php endif; ?>
                <li aria-current="page"><?= e($p['name']) ?></li>
            </ol>
        </nav>

        <section class="product-hero">
            <div class="product-gallery<?= count($gallery) > 1 ? '' : ' product-gallery--single' ?>" data-gallery>
                <?php if (count($gallery) > 1): ?>
                <ul class="product-gallery__thumbs" aria-label="Product images">
                    <?php foreach ($gallery as $i => $g): ?>
                    <li><button type="button" class="product-gallery__thumb<?= $i === 0 ? ' is-active' : '' ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>"
                        data-src="<?= e(upload_url($g['path'])) ?>" data-webp="<?= e(webp_url($g['path'])) ?>" data-alt="<?= e($g['alt']) ?>">
                        <?= picture($g['path'], '') ?><span class="sr-only">Show image <?= $i + 1 ?></span>
                    </button></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <div class="product-gallery__main" data-gallery-main>
                    <?= $main ? picture($main['path'], $main['alt'], ['eager' => true]) : '<span class="product-gallery__empty">No image yet</span>' ?>
                </div>
            </div>

            <div class="product-info">
                <?php if (!empty($settings['eyebrow'])): ?><p class="eyebrow eyebrow--sm product-info__eyebrow"><?= e($settings['eyebrow']) ?></p><?php endif; ?>
                <h1 class="product-info__title"><?= e($p['name']) ?></h1>
                <?php if (!empty($p['short_description'])): ?><div class="product-info__short"><?= paragraphs($p['short_description']) ?></div><?php endif; ?>

                <dl class="product-facts">
                    <div><dt>SKU / Product Code</dt><dd data-variant-sku data-default="<?= e($p['sku']) ?>"><?= e($p['sku']) ?></dd></div>
                    <?php if ($price !== ''): ?>
                    <div><dt>Retail Price</dt><dd class="product-facts__price" data-variant-price><?= $price ?></dd></div>
                    <?php endif; ?>
                    <?php /* Agent pricing is never sent to the browser – login arrives in a later phase. */ ?>
                    <div><dt>Agent/Dealer Price</dt><dd class="product-facts__muted"><?= e(($settings['agent_label'] ?? '') ?: 'Login To View') ?></dd></div>
                    <div><dt>Stock Availability</dt><dd><span class="stock" data-variant-stock data-key="<?= e($avail['key']) ?>"><span class="stock__dot" aria-hidden="true"></span><span class="stock__label"><?= e($avail['label']) ?></span></span>
                        <?php if (!empty($p['lead_time']) && $avail['key'] !== 'in_stock'): ?><span class="product-facts__lead">Lead time: <?= e($p['lead_time']) ?></span><?php endif; ?></dd></div>
                </dl>

                <?php if ($options['size'] || $options['colour'] || $options['material']): ?>
                <div class="product-options" data-product-options>
                    <h2 class="product-options__title">Product options</h2>
                    <?php if ($options['size']): ?>
                    <fieldset class="product-option">
                        <legend>Size</legend>
                        <div class="product-option__sizes">
                            <?php foreach ($options['size'] as $t): ?>
                            <label class="size-pill"><input type="radio" name="opt_size" value="<?= (int)$t['id'] ?>"><span><?= e($t['name']) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <?php endif; ?>
                    <?php if ($options['colour']): ?>
                    <fieldset class="product-option">
                        <legend>Colors</legend>
                        <div class="product-option__swatches">
                            <?php foreach ($options['colour'] as $t):
                                $sw = !empty($t['image']) && is_file(ROOT_PATH . '/' . $t['image']) ? upload_url($t['image']) : ''; ?>
                            <label class="swatch" title="<?= e($t['name']) ?>">
                                <input type="radio" name="opt_colour" value="<?= (int)$t['id'] ?>">
                                <span class="swatch__chip<?= $sw ? ' swatch__chip--img' : '' ?>" data-hex="<?= e((string)$t['hex']) ?>"><?php if ($sw): ?><img src="<?= e($sw) ?>" alt="" loading="lazy"><?php endif; ?></span>
                                <span class="sr-only"><?= e($t['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <?php endif; ?>
                    <div class="product-option product-option--row">
                        <?php if ($options['material']): ?>
                        <label class="product-option__material"><span class="product-option__label">Material</span>
                            <span class="filter-select"><select name="opt_material">
                                <option value="">Select</option>
                                <?php foreach ($options['material'] as $t): ?><option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
                            </select><?= icon('chevron-down') ?></span>
                        </label>
                        <?php endif; ?>
                        <div class="qty" data-qty>
                            <label class="sr-only" for="qty">Quantity</label>
                            <input id="qty" type="number" min="1" max="999" value="1" inputmode="numeric">
                            <span class="qty__btns"><button type="button" data-qty-step="1" aria-label="More">▴</button><button type="button" data-qty-step="-1" aria-label="Less">▾</button></span>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="product-options product-options--qty">
                    <div class="qty" data-qty>
                        <label class="sr-only" for="qty">Quantity</label>
                        <input id="qty" type="number" min="1" max="999" value="1" inputmode="numeric">
                        <span class="qty__btns"><button type="button" data-qty-step="1" aria-label="More">▴</button><button type="button" data-qty-step="-1" aria-label="Less">▾</button></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php /* Quotes and wishlists arrive in a later phase: buttons shown, not active yet. */ ?>
                <div class="product-actions">
                    <button class="btn btn--dark product-actions__quote" type="button" disabled aria-disabled="true" title="Coming soon">Add To Quote</button>
                    <button class="btn product-actions__wish" type="button" disabled aria-disabled="true" title="Coming soon"><?= icon('heart') ?> Add To Wishlist</button>
                </div>
                <?php if ($variants): ?>
                <script type="application/json" id="product-variants"><?= json_encode($variants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($tabs): ?>
        <section class="product-tabs" data-tabs>
            <div class="product-tabs__list" role="tablist" aria-label="Product information">
                <?php foreach ($tabs as $k => $t): ?>
                <button class="product-tabs__tab" type="button" role="tab" id="tab-<?= $k ?>" aria-controls="panel-<?= $k ?>" aria-selected="<?= $k === $firstTab ? 'true' : 'false' ?>"<?= $k === $firstTab ? '' : ' tabindex="-1"' ?>><?= e($t) ?></button>
                <?php endforeach; ?>
            </div>

            <?php if (isset($tabs['description'])): ?>
            <div class="product-tabs__panel prose-rich" role="tabpanel" id="panel-description" aria-labelledby="tab-description"><?= HtmlSanitizer::clean($p['description']) ?></div>
            <?php endif; ?>

            <?php if (isset($tabs['specifications'])): ?>
            <div class="product-tabs__panel" role="tabpanel" id="panel-specifications" aria-labelledby="tab-specifications">
                <div class="spec-layout">
                    <?php if ($specs): ?>
                    <table class="spec-table">
                        <thead><tr><th scope="col">Specification</th><th scope="col">Details</th></tr></thead>
                        <tbody>
                            <?php foreach ($specs as $s): ?><tr><th scope="row"><?= e($s['name']) ?></th><td><?= lines($s['value']) ?></td></tr><?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <?php if (trim((string)$p['suitable_for']) !== ''): ?>
                    <aside class="suitable-box"><h3>Suitable For</h3><?= paragraphs($p['suitable_for']) ?></aside>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($tabs['technical'])): ?>
            <div class="product-tabs__panel" role="tabpanel" id="panel-technical" aria-labelledby="tab-technical">
                <?php if ($tech): ?>
                <dl class="tech-list">
                    <?php foreach ($tech as $label => $val): ?><div><dt><?= e($label) ?></dt><dd><?= paragraphs($val) ?></dd></div><?php endforeach; ?>
                </dl>
                <?php endif; ?>
                <?php if ($drawings): ?>
                <h3 class="tech-drawings__title">Technical Drawings</h3>
                <ul class="tech-drawings">
                    <?php foreach ($drawings as $dr): ?>
                    <li><a href="<?= e(upload_url($dr['path'])) ?>" target="_blank" rel="noopener"><?= picture($dr['path'], $dr['alt'] ?: 'Technical drawing') ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($tabs['downloads'])): ?>
            <div class="product-tabs__panel" role="tabpanel" id="panel-downloads" aria-labelledby="tab-downloads">
                <ul class="download-list">
                    <?php foreach ($downloads as $dl): $ext = strtoupper(pathinfo($dl['file_path'], PATHINFO_EXTENSION)); ?>
                    <li>
                        <a class="download" href="<?= e(upload_url($dl['file_path'])) ?>" download="<?= e($dl['file_name'] ?: basename($dl['file_path'])) ?>">
                            <span class="download__type"><?= e($ext) ?></span>
                            <span class="download__text"><strong><?= e($dl['title']) ?></strong><small><?= e(ProductRepository::DOC_TYPES[$dl['doc_type']] ?? 'Document') ?> · <?= e($fmtSize((int)$dl['file_size'])) ?></small></span>
                            <span class="download__go">Download</span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($family): ?>
        <section class="product-family" aria-labelledby="family-title">
            <h2 class="heading heading--lg" id="family-title"><?= e(($settings['family_heading'] ?? '') ?: 'Family Of Products') ?></h2>
            <?php if (!empty($familyRow['description'])): ?><div class="prose prose--muted prose--tight"><?= paragraphs($familyRow['description']) ?></div><?php endif; ?>
            <ul class="family-grid">
                <?php foreach ($family as $f): $fp = price_html($f); ?>
                <li class="family-card">
                    <a class="family-card__link" href="<?= e(product_url($f['slug'], $from)) ?>"><span class="sr-only"><?= e($f['name']) ?></span></a>
                    <div class="family-card__media"><?= picture($f['image'] ?? '', $f['name']) ?></div>
                    <div class="product-card__row">
                        <h3 class="product-card__name"><?= e($f['name']) ?></h3>
                        <?php if ($fp !== ''): ?><p class="product-card__price"><?= $fp ?></p><?php endif; ?>
                    </div>
                    <div class="product-card__row product-card__row--meta">
                        <p class="product-card__type"><?= e($f['type_name'] ?? '') ?></p>
                        <p class="product-card__sku"><?= e($f['sku']) ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <?php if ($showMedia): ?>
        <section class="product-media<?= ($hasVideo && !empty($p['collection_image'])) ? '' : ' product-media--single' ?>" aria-label="Product video and collection">
            <?php if ($hasVideo): ?>
            <div class="product-video" data-video<?= $videoFile ? ' data-video-src="' . e($videoFile) . '"' : '' ?><?= $videoEmbed ? ' data-video-embed="' . e($videoEmbed) . '"' : '' ?>>
                <?= picture($p['video_thumb'] ?: ($p['image'] ?? ''), $p['name'] . ' video') ?>
                <button class="product-video__play" type="button" aria-label="Play video"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="32" fill="#fff"/><path d="M26 20l18 12-18 12z" fill="#1d3557"/></svg></button>
            </div>
            <?php endif; ?>
            <?php if (!empty($p['collection_image'])): ?>
            <div class="product-collection"><?= picture($p['collection_image'], ($familyRow['name'] ?? $p['name']) . ' collection') ?></div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>

    <?php if ($cta) render('sections/cta', ['s' => $cta]); ?>

    <?php if ($related): ?>
    <section class="product-related" aria-labelledby="related-title">
        <div class="container">
            <h2 class="heading heading--lg" id="related-title"><?= e(($settings['related_heading'] ?? '') ?: 'Related Products') ?></h2>
            <?php if (!empty($settings['related_text'])): ?><div class="prose prose--muted prose--tight product-related__text"><?= paragraphs($settings['related_text']) ?></div><?php endif; ?>
            <?php if (!empty($settings['related_link_label'])): ?>
            <a class="text-link text-link--xs" href="<?= e(safe_url($settings['related_link_url'] ?? '')) ?>"><?= e($settings['related_link_label']) ?></a>
            <?php endif; ?>
            <div class="related-slider" data-slider>
                <button class="related-slider__nav" type="button" data-slider-prev aria-label="Previous products"><?= icon('chevron-left') ?></button>
                <ul class="related-slider__track scroller" data-slider-track data-drag-scroll tabindex="0" aria-label="Related products">
                    <?php foreach ($related as $r) render('shop/card', ['p' => $r, 'from' => $from]); ?>
                </ul>
                <button class="related-slider__nav" type="button" data-slider-next aria-label="Next products"><?= icon('chevron-right') ?></button>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>
