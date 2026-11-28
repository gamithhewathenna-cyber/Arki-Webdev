<?php
declare(strict_types=1);

/**
 * Storefront pages: product listings (/work/products/, /learn/products/,
 * /store/products/) and the product page (/product/?p=slug).
 * Everything shown comes from the product database; filter values are
 * built from what exists in the selected storefront.
 */
final class Shop
{
    public const PER_PAGE = 9;

    public static function store(?string $s): string
    {
        return in_array($s, ['work', 'learn', 'store'], true) ? $s : 'store';
    }

    /* =================================================================
     *  Listing
     * ================================================================= */

    public static function listing(string $store): void
    {
        Construction::enforce();
        $store = self::store($store);

        ContentRepository::sync('shop');
        $shop = ContentRepository::page('shop');

        // Section: Work and Learn pages are locked to their own products;
        // the Store page can switch between All Products, Work and Learn.
        $section = $store;
        if ($store === 'store' && in_array($_GET['section'] ?? '', ['work', 'learn'], true)) {
            $section = (string)$_GET['section'];
        }

        $options = ProductRepository::filterOptions($store, $section);
        $filters = self::readFilters($options);
        $filters['ui']['section'] = $store === 'store' && $section !== 'store' ? $section : '';
        if ($filters['ui']['section'] !== '') {
            $filters['active'] = true;
        }
        $sort = isset(ProductRepository::SORTS[$_GET['sort'] ?? '']) ? (string)$_GET['sort'] : 'featured';
        $page = max(1, (int)($_GET['page'] ?? 1));

        [$items, $total] = ProductRepository::listing($section, $filters['sql'], $sort, $page, self::PER_PAGE);
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        if ($page > $pages) {
            $page = $pages;
            [$items, $total] = ProductRepository::listing($section, $filters['sql'], $sort, $page, self::PER_PAGE);
        }

        $label = ProductRepository::STOREFRONTS[$store];
        render('layout/page', [
            'pageTitle'   => $label . ' Products – ' . Settings::get('site_name', 'ARKI Environments'),
            'bodyClass'   => 'page-shop page-shop--' . $store,
            'global'      => ContentRepository::page('global'),
            'sections'    => [],
            'content'     => 'shop/listing',
            'contentVars' => [
                'store'   => $store,
                'label'   => $label,
                'banner'  => $shop['banner_' . $store] ?? null,
                'cta'     => $shop['listing_cta'] ?? null,
                'options' => $options,
                'ui'      => $filters['ui'],
                'active'  => $filters['active'],
                'sort'    => $sort,
                'items'   => $items,
                'total'   => $total,
                'page'    => $page,
                'pages'   => $pages,
            ],
        ]);
    }

    /**
     * Read filters from the query string, accepting only values that exist
     * in this storefront. Returns SQL filters (ids), UI state (slugs) and
     * whether any filter is active.
     */
    private static function readFilters(array $o): array
    {
        $slugs = static function ($v): array {
            $v = is_array($v) ? $v : ($v === null || $v === '' ? [] : [$v]);
            return array_values(array_unique(array_filter(array_map(static fn($x) => is_string($x) ? substr(trim($x), 0, 160) : '', $v))));
        };
        $bySlug = static function (array $terms): array {
            $m = [];
            foreach ($terms as $t) $m[$t['slug']] = (int)$t['id'];
            return $m;
        };
        $catMap = [];
        foreach ($o['categories'] as $c) {
            $catMap[$c['slug']] = (int)$c['id'];
            foreach ($c['children'] as $ch) $catMap[$ch['slug']] = (int)$ch['id'];
        }

        $ui = ['q' => '', 'cat' => [], 'type' => [], 'avail' => [], 'material' => '', 'colour' => '', 'brand' => '', 'env' => '', 'min' => null, 'max' => null];
        $sql = [];

        $q = trim((string)($_GET['q'] ?? ''));
        if ($q !== '') {
            $ui['q'] = $sql['q'] = mb_substr($q, 0, 100);
        }
        foreach (['cat' => $catMap, 'type' => $bySlug($o['types'])] as $key => $map) {
            foreach ($slugs($_GET[$key] ?? null) as $s) {
                if (isset($map[$s])) {
                    $ui[$key][] = $s;
                    $sql[$key . '_ids'][] = $map[$s];
                }
            }
        }
        foreach ($slugs($_GET['avail'] ?? null) as $a) {
            if (in_array($a, ['in_stock', 'made_to_order'], true)) {
                $ui['avail'][] = $sql['avail'][] = $a;
            }
        }
        foreach (['material' => 'materials', 'colour' => 'colours', 'brand' => 'brands', 'env' => 'env'] as $key => $list) {
            $s = $slugs($_GET[$key] ?? null)[0] ?? '';
            $map = $bySlug($o[$list]);
            if ($s !== '' && isset($map[$s])) {
                $ui[$key] = $s;
                $sql[$key . '_id'] = $map[$s];
            }
        }
        foreach (['min', 'max'] as $k) {
            if (isset($_GET[$k]) && is_numeric($_GET[$k]) && (float)$_GET[$k] >= 0) {
                $ui[$k] = $sql[$k] = (int)$_GET[$k];
            }
        }
        // A range equal to the full span is no filter at all.
        if ($ui['min'] !== null && $ui['min'] <= $o['price_min']) { $ui['min'] = null; unset($sql['min']); }
        if ($ui['max'] !== null && $ui['max'] >= $o['price_max']) { $ui['max'] = null; unset($sql['max']); }

        return ['sql' => $sql, 'ui' => $ui, 'active' => (bool)$sql];
    }

    /** Query parameters of the current listing (for pagination links). */
    public static function query(array $ui, string $sort, int $page = 1): array
    {
        $q = [
            'q' => $ui['q'], 'cat' => $ui['cat'], 'type' => $ui['type'], 'avail' => $ui['avail'],
            'material' => $ui['material'], 'colour' => $ui['colour'], 'brand' => $ui['brand'], 'env' => $ui['env'],
            'min' => $ui['min'], 'max' => $ui['max'], 'sort' => $sort !== 'featured' ? $sort : '',
            'section' => $ui['section'] ?? '',
        ];
        if ($page > 1) $q['page'] = $page;
        return $q;
    }

    /* =================================================================
     *  Product page
     * ================================================================= */

    public static function product(): void
    {
        Construction::enforce();

        $from = self::store($_GET['from'] ?? 'store');
        $slug = substr(trim((string)($_GET['p'] ?? '')), 0, 220);
        $preview = Auth::check();                 // admins can preview drafts / hidden products
        $p = $slug !== '' ? ProductRepository::bySlug($slug, $preview) : null;

        ContentRepository::sync('shop');
        $shop = ContentRepository::page('shop');
        $global = ContentRepository::page('global');

        if (!$p) {
            http_response_code(404);
            render('layout/page', [
                'pageTitle' => 'Product not found – ' . Settings::get('site_name', 'ARKI Environments'),
                'bodyClass' => 'page-product page-product--missing',
                'global' => $global, 'sections' => [],
                'content' => 'shop/not-found', 'contentVars' => ['from' => $from],
            ]);
            return;
        }

        $id = (int)$p['id'];
        // Product page gallery = the gallery images (4:5); the square main image is
        // used only when there are none (it stays the image on product cards).
        $gallery = [];
        foreach (ProductRepository::images($id, 'gallery') as $g) {
            $gallery[] = ['path' => $g['path'], 'alt' => $g['alt'] ?: $p['name']];
        }
        if (!$gallery && !empty($p['image'])) {
            $gallery[] = ['path' => $p['image'], 'alt' => $p['name']];
        }

        $options = ProductRepository::optionTerms($id);
        $variants = ProductRepository::publicVariants($id);
        // Variant option values that aren't assigned as options still need to be selectable.
        foreach (['size' => 'size_id', 'colour' => 'colour_id', 'material' => 'material_id'] as $tax => $col) {
            $have = array_map('intval', array_column($options[$tax], 'id'));
            foreach ($variants as $v) {
                if ($v[$col] && !in_array((int)$v[$col], $have, true) && ($t = Catalog::term((int)$v[$col]))) {
                    $options[$tax][] = $t;
                    $have[] = (int)$t['id'];
                }
            }
        }

        $settings = $shop['product_page']['data'] ?? [];
        render('layout/page', [
            'pageTitle'   => $p['name'] . ' – ' . Settings::get('site_name', 'ARKI Environments'),
            'bodyClass'   => 'page-product',
            'global'      => $global,
            'sections'    => [],
            'content'     => 'shop/product',
            'contentVars' => [
                'p'         => $p,
                'from'      => $from,
                'preview'   => $preview && $p['status'] !== 'published',
                'gallery'   => $gallery,
                'crumb'     => ProductRepository::primaryCategory($id, $from),
                'avail'     => ProductRepository::availability($p),
                'options'   => $options,
                'variants'  => self::variantJson($p, $variants),
                'specs'     => ProductRepository::specs($id),
                'downloads' => ProductRepository::downloads($id),
                'drawings'  => ProductRepository::images($id, 'drawing'),
                'family'    => $p['sections']['family'] ? ProductRepository::familyMembers($p) : [],
                'familyRow' => $p['family_id'] ? Catalog::family((int)$p['family_id']) : null,
                'related'   => $p['sections']['related'] ? ProductRepository::related($p) : [],
                'settings'  => $settings,
                'cta'       => $p['sections']['cta'] ? self::productCta($p, $shop['product_cta'] ?? null) : null,
            ],
        ]);
    }

    /** Variant data for the page script – retail prices only, never agent prices. */
    private static function variantJson(array $p, array $variants): array
    {
        $out = [];
        foreach ($variants as $v) {
            $price = $v['retail_price'] !== null
                ? price_html(['retail_price' => $v['retail_price'], 'currency' => $p['currency'], 'price_from' => $p['price_from']])
                : null;
            $avail = null;
            if ($v['availability']) {
                $avail = ProductRepository::availability(['stock_status' => $v['availability'], 'stock_manage' => 0]);
            } elseif ($v['stock_qty'] !== null && !empty($p['stock_manage'])) {
                $avail = ProductRepository::availability(['stock_status' => 'in_stock', 'stock_manage' => 1, 'stock_qty' => $v['stock_qty']]);
            }
            $img = $v['image'] && is_file(ROOT_PATH . '/' . $v['image']) ? $v['image'] : null;
            $out[] = [
                'size'     => $v['size_id'] ? (int)$v['size_id'] : null,
                'colour'   => $v['colour_id'] ? (int)$v['colour_id'] : null,
                'material' => $v['material_id'] ? (int)$v['material_id'] : null,
                'sku'      => $v['sku'] ?: null,
                'price'    => $price,
                'avail'    => $avail,
                'image'    => $img ? url($img) : null,
                'webp'     => $img ? (webp_url($img) ?: null) : null,
            ];
        }
        return $out;
    }

    /** Product CTA: global default, with the product's own override fields on top. */
    private static function productCta(array $p, ?array $section): ?array
    {
        $data = $section['data'] ?? [];
        $override = json_decode((string)($p['cta_override'] ?? ''), true);
        if (is_array($override) && !empty($override['enabled'])) {
            foreach ($override as $k => $v) {
                if ($k !== 'enabled' && is_string($v) && trim($v) !== '') {
                    $data[$k] = $v;
                }
            }
            return ['data' => $data];
        }
        return $section ? ['data' => $data] : null; // hidden in Storefront Settings → no banner
    }
}
