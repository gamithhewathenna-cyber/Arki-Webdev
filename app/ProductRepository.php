<?php
declare(strict_types=1);

/**
 * Product queries.
 *
 * PUBLIC methods (storefront) never select agent_price – agent / dealer
 * pricing stays in the database and the CMS only. Admin methods are marked.
 *
 * Storefront rules: 'work' / 'learn' → only products assigned to it;
 * 'store' → every published product.
 */
final class ProductRepository
{
    public const STOREFRONTS = ['work' => 'Work', 'learn' => 'Learn', 'store' => 'Store'];
    public const STATUSES    = ['published' => 'Published', 'draft' => 'Draft', 'hidden' => 'Hidden'];
    public const STOCK       = ['in_stock' => 'In Stock', 'out_of_stock' => 'Out of Stock', 'made_to_order' => 'Made To Order'];
    public const CURRENCIES  = ['AUD' => 'AUD – Australian Dollar', 'NZD' => 'NZD – New Zealand Dollar', 'USD' => 'USD – US Dollar', 'EUR' => 'EUR – Euro', 'GBP' => 'GBP – British Pound'];
    public const DOC_TYPES   = ['brochure' => 'Product Brochure', 'spec_sheet' => 'Specification Sheet', 'assembly' => 'Assembly Instructions', 'technical' => 'Technical PDF', 'certificate' => 'Certificate', 'other' => 'Other'];
    public const SORTS       = ['featured' => 'Featured', 'newest' => 'Newest Products', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'name_asc' => 'Product Name: A–Z', 'name_desc' => 'Product Name: Z–A'];

    /** Columns safe to show publicly (no agent_price). */
    private const PUBLIC_COLS = 'p.id, p.name, p.slug, p.sku, p.brand_id, p.category_id, p.subcategory_id, p.type_id, p.env_rating_id, p.family_id, p.family_order,
        p.short_description, p.description, p.suitable_for, p.retail_price, p.sale_price, p.currency, p.price_from, p.image,
        p.video_url, p.video_file, p.video_thumb, p.collection_image, p.dimensions, p.weight, p.technical_details, p.assembly_info, p.maintenance,
        p.stock_manage, p.stock_qty, p.stock_status, p.lead_time, p.status, p.is_featured, p.is_new, p.is_bestseller, p.sort_order,
        p.related_mode, p.page_sections, p.cta_override, p.created_at';

    /** Columns needed for a product card. */
    private const CARD_COLS = 'p.id, p.name, p.slug, p.sku, p.image, p.retail_price, p.sale_price, p.currency, p.price_from,
        p.stock_manage, p.stock_qty, p.stock_status, p.is_new, p.is_bestseller, t.name AS type_name';

    private const PRICE_EXPR    = 'COALESCE(p.sale_price, p.retail_price)';
    private const IN_STOCK_EXPR = "(p.stock_status = 'in_stock' AND (p.stock_manage = 0 OR p.stock_qty > 0))";

    /* =================================================================
     *  Storefront (public)
     * ================================================================= */

    /** WHERE clause + params for a storefront and a set of filters. */
    private static function where(string $store, array $f): array
    {
        $w = ["p.status = 'published'"];
        $a = [];
        if ($store === 'work' || $store === 'learn') {
            $w[] = 'EXISTS (SELECT 1 FROM product_storefronts s WHERE s.product_id = p.id AND s.storefront = ?)';
            $a[] = $store;
        }
        if (($f['q'] ?? '') !== '') {
            $w[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ?)';
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
            array_push($a, $like, $like, $like);
        }
        if (!empty($f['cat_ids'])) {
            // In the category itself or in one of its subcategories.
            $in = implode(',', array_map('intval', $f['cat_ids']));
            $w[] = "EXISTS (SELECT 1 FROM product_term_links cl JOIN product_terms ct ON ct.id = cl.term_id
                            WHERE cl.product_id = p.id AND (ct.id IN ($in) OR ct.parent_id IN ($in)))";
        }
        if (!empty($f['type_ids'])) {
            $w[] = 'p.type_id IN (' . implode(',', array_map('intval', $f['type_ids'])) . ')';
        }
        if (!empty($f['brand_id'])) {
            $w[] = 'p.brand_id = ' . (int)$f['brand_id'];
        }
        if (!empty($f['env_id'])) {
            $w[] = 'p.env_rating_id = ' . (int)$f['env_id'];
        }
        foreach (['material_id' => 'material_id', 'colour_id' => 'colour_id'] as $k => $vcol) {
            if (!empty($f[$k])) {
                $id = (int)$f[$k];
                $w[] = "(EXISTS (SELECT 1 FROM product_term_links l WHERE l.product_id = p.id AND l.term_id = $id)
                        OR EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.$vcol = $id))";
            }
        }
        if (!empty($f['avail'])) {
            $parts = [];
            if (in_array('in_stock', $f['avail'], true)) $parts[] = self::IN_STOCK_EXPR;
            if (in_array('made_to_order', $f['avail'], true)) $parts[] = "p.stock_status = 'made_to_order'";
            if ($parts) $w[] = '(' . implode(' OR ', $parts) . ')';
        }
        if (isset($f['min']) && $f['min'] !== null) {
            $w[] = self::PRICE_EXPR . ' >= ?';
            $a[] = $f['min'];
        }
        if (isset($f['max']) && $f['max'] !== null) {
            $w[] = self::PRICE_EXPR . ' <= ?';
            $a[] = $f['max'];
        }
        return [implode(' AND ', $w), $a];
    }

    private static function orderBy(string $sort): string
    {
        return match ($sort) {
            'newest'     => 'p.created_at DESC, p.id DESC',
            'price_asc'  => self::PRICE_EXPR . ' IS NULL, ' . self::PRICE_EXPR . ' ASC, p.name',
            'price_desc' => self::PRICE_EXPR . ' IS NULL, ' . self::PRICE_EXPR . ' DESC, p.name',
            'name_asc'   => 'p.name ASC',
            'name_desc'  => 'p.name DESC',
            default      => 'p.is_featured DESC, p.sort_order ASC, p.id DESC',
        };
    }

    /** @return array{0: array, 1: int} [cards, total] */
    public static function listing(string $store, array $filters, string $sort, int $page, int $perPage): array
    {
        [$where, $args] = self::where($store, $filters);
        $total = (int)Database::value("SELECT COUNT(*) FROM products p WHERE $where", $args);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::all(
            'SELECT ' . self::CARD_COLS . " FROM products p LEFT JOIN product_terms t ON t.id = p.type_id
             WHERE $where ORDER BY " . self::orderBy($sort) . ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset,
            $args
        );
        return [$rows, $total];
    }

    /** Products for the Store page "Featured Products" section. */
    public static function highlighted(string $store, string $source, int $limit): array
    {
        [$where, $args] = self::where($store, []);
        $extra = match ($source) {
            'new'        => ' AND p.is_new = 1',
            'bestseller' => ' AND p.is_bestseller = 1',
            'featured'   => ' AND p.is_featured = 1',
            default      => '',
        };
        $order = $source === 'newest' ? self::orderBy('newest') : 'p.sort_order ASC, p.id DESC';
        return Database::all(
            'SELECT ' . self::CARD_COLS . " FROM products p LEFT JOIN product_terms t ON t.id = p.type_id
             WHERE $where$extra ORDER BY $order LIMIT " . (int)$limit,
            $args
        );
    }

    /** Filter values that actually have products in this storefront. */
    public static function filterOptions(string $store): array
    {
        [$where, $args] = self::where($store, []);
        $ids = "SELECT p.id FROM products p WHERE $where";
        $col = static fn(string $c): string => "SELECT DISTINCT p.$c FROM products p WHERE $where AND p.$c IS NOT NULL";

        // This storefront's own category tree, limited to categories that have products here.
        $cats = Database::all(
            "SELECT * FROM product_terms WHERE taxonomy = ? AND is_active = 1
             AND id IN (SELECT l.term_id FROM product_term_links l WHERE l.product_id IN ($ids)) ORDER BY sort_order, name",
            array_merge([Catalog::categoryTax($store)], $args)
        );
        // Make sure parents of used subcategories are listed.
        $have = array_map('intval', array_column($cats, 'id'));
        foreach ($cats as $c) {
            if ($c['parent_id'] !== null && !in_array((int)$c['parent_id'], $have, true)) {
                if ($p = Catalog::term((int)$c['parent_id'])) {
                    $cats[] = $p;
                    $have[] = (int)$p['id'];
                }
            }
        }
        $tree = [];
        foreach ($cats as $c) {
            if ($c['parent_id'] === null) {
                $c['children'] = array_values(array_filter($cats, static fn($x) => $x['parent_id'] !== null && (int)$x['parent_id'] === (int)$c['id']));
                $tree[] = $c;
            }
        }
        usort($tree, static fn($a, $b) => [$a['sort_order'], $a['name']] <=> [$b['sort_order'], $b['name']]);

        $simple = static function (string $tax, string $column) use ($col, $args): array {
            $sub = $col($column);
            return Database::all(
                "SELECT * FROM product_terms WHERE taxonomy = ? AND is_active = 1 AND id IN ($sub) ORDER BY sort_order, name",
                array_merge([$tax], $args)
            );
        };
        $linked = static fn(string $tax, string $vcol) => Database::all(
            "SELECT * FROM product_terms t WHERE t.taxonomy = ? AND t.is_active = 1 AND (
                t.id IN (SELECT l.term_id FROM product_term_links l WHERE l.product_id IN ($ids))
                OR t.id IN (SELECT v.$vcol FROM product_variants v WHERE v.$vcol IS NOT NULL AND v.product_id IN ($ids)))
             ORDER BY t.sort_order, t.name",
            array_merge([$tax], $args, $args)
        );
        $bounds = Database::one('SELECT MIN(' . self::PRICE_EXPR . ') AS lo, MAX(' . self::PRICE_EXPR . ") AS hi FROM products p WHERE $where", $args);

        return [
            'categories' => $tree,
            'types'      => $simple('type', 'type_id'),
            'brands'     => $simple('brand', 'brand_id'),
            'env'        => $simple('env_rating', 'env_rating_id'),
            'materials'  => $linked('material', 'material_id'),
            'colours'    => $linked('colour', 'colour_id'),
            'price_min'  => (int)floor((float)($bounds['lo'] ?? 0)),
            'price_max'  => (int)ceil((float)($bounds['hi'] ?? 0)),
        ];
    }

    /** One product by slug (published only, unless $preview for logged-in admins). */
    public static function bySlug(string $slug, bool $preview = false): ?array
    {
        $p = Database::one(
            'SELECT ' . self::PUBLIC_COLS . ', b.name AS brand_name, t.name AS type_name, er.name AS env_name
             FROM products p
             LEFT JOIN product_terms b  ON b.id  = p.brand_id
             LEFT JOIN product_terms t  ON t.id  = p.type_id
             LEFT JOIN product_terms er ON er.id = p.env_rating_id
             WHERE p.slug = ?' . ($preview ? '' : " AND p.status = 'published'"),
            [$slug]
        );
        if ($p) {
            $p['sections'] = self::pageSections($p);
        }
        return $p;
    }

    /** The product's main category in a storefront (top-level name + slug), for the breadcrumb. */
    public static function primaryCategory(int $id, string $store): ?array
    {
        return Database::one(
            'SELECT COALESCE(pp.name, t.name) AS name, COALESCE(pp.slug, t.slug) AS slug
             FROM product_term_links l
             JOIN product_terms t ON t.id = l.term_id AND t.taxonomy = ?
             LEFT JOIN product_terms pp ON pp.id = t.parent_id
             WHERE l.product_id = ?
             ORDER BY COALESCE(pp.sort_order, t.sort_order), t.sort_order LIMIT 1',
            [Catalog::categoryTax($store), $id]
        );
    }

    /** ADMIN: ids of all category terms (any storefront) a product is in. */
    public static function categoryIds(int $id): array
    {
        return array_map('intval', array_column(Database::all(
            "SELECT l.term_id FROM product_term_links l JOIN product_terms t ON t.id = l.term_id AND t.taxonomy LIKE 'cat\\_%' WHERE l.product_id = ?",
            [$id]
        ), 'term_id'));
    }

    public static function storefronts(int $id): array
    {
        return array_column(Database::all('SELECT storefront FROM product_storefronts WHERE product_id = ?', [$id]), 'storefront');
    }

    public static function images(int $id, string $kind = 'gallery'): array
    {
        return Database::all('SELECT id, path, alt FROM product_images WHERE product_id = ? AND kind = ? ORDER BY sort_order, id', [$id, $kind]);
    }

    public static function specs(int $id): array
    {
        return Database::all('SELECT id, name, value FROM product_specs WHERE product_id = ? ORDER BY sort_order, id', [$id]);
    }

    public static function downloads(int $id): array
    {
        return Database::all('SELECT id, title, doc_type, file_path, file_name, file_size FROM product_downloads WHERE product_id = ? ORDER BY sort_order, id', [$id]);
    }

    /** Option terms assigned to a product, grouped: ['size' => [...], 'colour' => [...], 'material' => [...]]. */
    public static function optionTerms(int $id): array
    {
        $out = ['size' => [], 'colour' => [], 'material' => []];
        foreach (Database::all(
            'SELECT t.* FROM product_term_links l JOIN product_terms t ON t.id = l.term_id WHERE l.product_id = ? AND t.is_active = 1 ORDER BY t.sort_order, t.name',
            [$id]
        ) as $t) {
            if (isset($out[$t['taxonomy']])) $out[$t['taxonomy']][] = $t;
        }
        return $out;
    }

    /** Variants for the storefront – no agent prices. */
    public static function publicVariants(int $id): array
    {
        return Database::all(
            'SELECT id, size_id, colour_id, material_id, sku, retail_price, image, stock_qty, availability FROM product_variants WHERE product_id = ? ORDER BY sort_order, id',
            [$id]
        );
    }

    /** Other published products of the same family. */
    public static function familyMembers(array $p): array
    {
        if (empty($p['family_id'])) return [];
        return Database::all(
            'SELECT ' . self::CARD_COLS . " FROM products p LEFT JOIN product_terms t ON t.id = p.type_id
             JOIN product_families f ON f.id = p.family_id AND f.is_active = 1
             WHERE p.family_id = ? AND p.id <> ? AND p.status = 'published' ORDER BY p.family_order, p.name",
            [$p['family_id'], $p['id']]
        );
    }

    /** Related products: manual list, or automatic by category / type. Never includes the product itself. */
    public static function related(array $p, int $limit = 10): array
    {
        if (($p['related_mode'] ?? 'auto') === 'manual') {
            return Database::all(
                'SELECT ' . self::CARD_COLS . " FROM product_related r JOIN products p ON p.id = r.related_id
                 LEFT JOIN product_terms t ON t.id = p.type_id
                 WHERE r.product_id = ? AND p.id <> ? AND p.status = 'published' ORDER BY r.sort_order LIMIT " . (int)$limit,
                [$p['id'], $p['id']]
            );
        }
        // Automatic: products sharing a category (in any storefront) first, then the same product type.
        $shared = "EXISTS (SELECT 1 FROM product_term_links l1
                           JOIN product_terms c1 ON c1.id = l1.term_id AND c1.taxonomy LIKE 'cat\\_%'
                           JOIN product_term_links l2 ON l2.term_id = l1.term_id
                           WHERE l1.product_id = p.id AND l2.product_id = ?)";
        return Database::all(
            'SELECT ' . self::CARD_COLS . " FROM products p LEFT JOIN product_terms t ON t.id = p.type_id
             WHERE p.status = 'published' AND p.id <> ?
               AND ($shared OR (p.type_id IS NOT NULL AND p.type_id = ?))
             ORDER BY $shared DESC, p.is_featured DESC, p.sort_order LIMIT " . (int)$limit,
            [$p['id'], $p['id'], $p['type_id'] ?? 0, $p['id']]
        );
    }

    /** Availability of a product (or variant): ['key' => …, 'label' => …]. */
    public static function availability(array $p): array
    {
        $status = (string)($p['stock_status'] ?? 'in_stock');
        if ($status === 'made_to_order') {
            $key = 'made_to_order';
        } elseif ($status === 'out_of_stock' || (!empty($p['stock_manage']) && (int)$p['stock_qty'] <= 0)) {
            $key = 'out_of_stock';
        } else {
            $key = 'in_stock';
        }
        return ['key' => $key, 'label' => self::STOCK[$key]];
    }

    /** Which optional sections of the product page are switched on. */
    public static function pageSections(array $p): array
    {
        $defaults = ['family' => 1, 'media' => 1, 'related' => 1, 'cta' => 1];
        $saved = json_decode((string)($p['page_sections'] ?? ''), true);
        return is_array($saved) ? array_map('intval', $saved + $defaults) : $defaults;
    }

    /* =================================================================
     *  Admin (CMS only – may include agent prices)
     * ================================================================= */

    /** ADMIN: full product row. */
    public static function adminFind(int $id): ?array
    {
        return $id > 0 ? Database::one('SELECT * FROM products WHERE id = ?', [$id]) : null;
    }

    /** ADMIN: variants including agent prices. */
    public static function adminVariants(int $id): array
    {
        return Database::all('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order, id', [$id]);
    }

    /** ADMIN: product table with filters. @return array{0: array, 1: int} */
    public static function adminList(array $f, int $page, int $perPage): array
    {
        $w = ['1 = 1'];
        $a = [];
        if (($f['q'] ?? '') !== '') {
            $w[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
            array_push($a, $like, $like);
        }
        if (in_array($f['store'] ?? '', ['work', 'learn'], true)) {
            $w[] = 'EXISTS (SELECT 1 FROM product_storefronts s WHERE s.product_id = p.id AND s.storefront = ?)';
            $a[] = $f['store'];
        } elseif (($f['store'] ?? '') === 'store_only') {
            $w[] = 'NOT EXISTS (SELECT 1 FROM product_storefronts s WHERE s.product_id = p.id)';
        }
        if (!empty($f['category'])) {
            $w[] = 'EXISTS (SELECT 1 FROM product_term_links cl JOIN product_terms ct ON ct.id = cl.term_id
                            WHERE cl.product_id = p.id AND (ct.id = ? OR ct.parent_id = ?))';
            array_push($a, (int)$f['category'], (int)$f['category']);
        }
        if (!empty($f['brand'])) {
            $w[] = 'p.brand_id = ?';
            $a[] = (int)$f['brand'];
        }
        if (isset(self::STATUSES[$f['status'] ?? ''])) {
            $w[] = 'p.status = ?';
            $a[] = $f['status'];
        } elseif (($f['status'] ?? '') === 'featured') {
            $w[] = 'p.is_featured = 1';
        }
        switch ($f['stock'] ?? '') {
            case 'in_stock':      $w[] = self::IN_STOCK_EXPR; break;
            case 'made_to_order': $w[] = "p.stock_status = 'made_to_order'"; break;
            case 'out_of_stock':  $w[] = "(p.stock_status = 'out_of_stock' OR (p.stock_status = 'in_stock' AND p.stock_manage = 1 AND p.stock_qty <= 0))"; break;
            case 'low':           $w[] = 'p.stock_manage = 1 AND p.stock_qty <= p.low_stock_threshold'; break;
        }
        $where = implode(' AND ', $w);
        $total = (int)Database::value("SELECT COUNT(*) FROM products p WHERE $where", $a);
        $rows = Database::all(
            "SELECT p.*, b.name AS brand_name, t.name AS type_name,
                    (SELECT GROUP_CONCAT(s.storefront ORDER BY s.storefront) FROM product_storefronts s WHERE s.product_id = p.id) AS stores,
                    (SELECT GROUP_CONCAT(CONCAT(CASE ct.taxonomy WHEN 'cat_work' THEN 'Work' WHEN 'cat_learn' THEN 'Learn' ELSE 'Store' END, ': ',
                                                IF(cp.id IS NULL, '', CONCAT(cp.name, ' › ')), ct.name)
                                         ORDER BY ct.taxonomy DESC, cp.sort_order, ct.sort_order SEPARATOR '\n')
                       FROM product_term_links cl
                       JOIN product_terms ct ON ct.id = cl.term_id AND ct.taxonomy LIKE 'cat\\_%'
                       LEFT JOIN product_terms cp ON cp.id = ct.parent_id
                      WHERE cl.product_id = p.id) AS categories
             FROM products p
             LEFT JOIN product_terms b ON b.id = p.brand_id
             LEFT JOIN product_terms t ON t.id = p.type_id
             WHERE $where ORDER BY p.sort_order, p.id DESC LIMIT " . (int)$perPage . ' OFFSET ' . (int)max(0, ($page - 1) * $perPage),
            $a
        );
        return [$rows, $total];
    }

    /** ADMIN: create an empty product row and return its id. */
    public static function create(string $name, string $sku): int
    {
        $slug = Catalog::uniqueSlug('products', $name);
        Database::run('UPDATE products SET sort_order = sort_order + 1');
        Database::run(
            "INSERT INTO products (name, slug, sku, status, sort_order, page_sections, created_at, updated_at)
             VALUES (?, ?, ?, 'draft', 1, '{\"family\":1,\"media\":1,\"related\":1,\"cta\":1}', NOW(), NOW())",
            [$name, $slug, $sku]
        );
        return Database::lastId();
    }

    /** ADMIN: update whitelisted columns of a product. */
    public static function update(int $id, array $cols): void
    {
        static $allowed = [
            'name', 'slug', 'sku', 'brand_id', 'category_id', 'subcategory_id', 'type_id', 'env_rating_id', 'family_id', 'family_order',
            'short_description', 'description', 'suitable_for', 'retail_price', 'agent_price', 'sale_price', 'currency', 'price_from',
            'image', 'video_url', 'video_file', 'video_thumb', 'collection_image', 'dimensions', 'weight', 'technical_details',
            'assembly_info', 'maintenance', 'stock_manage', 'stock_qty', 'stock_status', 'low_stock_threshold', 'lead_time', 'status',
            'is_featured', 'is_new', 'is_bestseller', 'sort_order', 'related_mode', 'page_sections', 'cta_override',
        ];
        $cols = array_intersect_key($cols, array_flip($allowed));
        if (!$cols) return;
        $set = implode(', ', array_map(static fn($c) => "`$c` = ?", array_keys($cols)));
        Database::run("UPDATE products SET $set, updated_at = NOW() WHERE id = ?", array_merge(array_values($cols), [$id]));
    }

    public static function skuTaken(string $sku, int $exceptId = 0): bool
    {
        return (int)Database::value('SELECT COUNT(*) FROM products WHERE sku = ? AND id <> ?', [$sku, $exceptId]) > 0;
    }

    /** ADMIN: copy a product (and everything attached) as a hidden draft. Returns the new id. */
    public static function duplicate(int $id): ?int
    {
        $p = self::adminFind($id);
        if (!$p) return null;
        $sku = $p['sku'] . '-COPY';
        for ($i = 2; self::skuTaken($sku); $i++) {
            $sku = $p['sku'] . '-COPY' . $i;
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $new = self::create($p['name'] . ' (Copy)', $sku);
            $copy = $p;
            unset($copy['id'], $copy['slug'], $copy['sku'], $copy['name'], $copy['created_at'], $copy['updated_at'], $copy['sort_order']);
            $copy['status'] = 'draft';
            self::update($new, $copy);
            foreach (['product_storefronts' => 'storefront', 'product_term_links' => 'term_id'] as $table => $col) {
                Database::run("INSERT INTO $table (product_id, $col) SELECT ?, $col FROM $table WHERE product_id = ?", [$new, $id]);
            }
            Database::run('INSERT INTO product_images (product_id, kind, path, alt, sort_order) SELECT ?, kind, path, alt, sort_order FROM product_images WHERE product_id = ?', [$new, $id]);
            Database::run('INSERT INTO product_specs (product_id, name, value, sort_order) SELECT ?, name, value, sort_order FROM product_specs WHERE product_id = ?', [$new, $id]);
            Database::run('INSERT INTO product_downloads (product_id, title, doc_type, file_path, file_name, file_size, sort_order) SELECT ?, title, doc_type, file_path, file_name, file_size, sort_order FROM product_downloads WHERE product_id = ?', [$new, $id]);
            Database::run('INSERT INTO product_variants (product_id, size_id, colour_id, material_id, sku, retail_price, agent_price, image, stock_qty, availability, sort_order) SELECT ?, size_id, colour_id, material_id, sku, retail_price, agent_price, image, stock_qty, availability, sort_order FROM product_variants WHERE product_id = ?', [$new, $id]);
            Database::run('INSERT INTO product_related (product_id, related_id, sort_order) SELECT ?, related_id, sort_order FROM product_related WHERE product_id = ?', [$new, $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $new;
    }

    /** ADMIN: delete a product and any files no longer used anywhere. */
    public static function delete(int $id): void
    {
        $p = self::adminFind($id);
        if (!$p) return;
        $files = array_filter([$p['image'], $p['video_file'], $p['video_thumb'], $p['collection_image']]);
        $override = json_decode((string)$p['cta_override'], true);
        if (!empty($override['image'])) $files[] = $override['image'];
        foreach (self::images($id, 'gallery') as $r) $files[] = $r['path'];
        foreach (self::images($id, 'drawing') as $r) $files[] = $r['path'];
        foreach (self::downloads($id) as $r) $files[] = $r['file_path'];
        foreach (self::adminVariants($id) as $r) if ($r['image']) $files[] = $r['image'];
        Database::run('DELETE FROM products WHERE id = ?', [$id]); // children cascade
        foreach (array_unique($files) as $f) {
            Uploader::deleteIfUnused($f);
        }
    }

    /** ADMIN: products for selects (id => "Name (SKU)"). */
    public static function adminOptions(int $exceptId = 0): array
    {
        $out = [];
        foreach (Database::all('SELECT id, name, sku FROM products WHERE id <> ? ORDER BY name', [$exceptId]) as $r) {
            $out[(int)$r['id']] = $r['name'] . ' (' . $r['sku'] . ')';
        }
        return $out;
    }
}
