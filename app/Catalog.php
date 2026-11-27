<?php
declare(strict_types=1);

/**
 * Centrally managed product lists ("terms") and product families.
 *
 * All lists live in one table (product_terms) with a `taxonomy` column, so a
 * new list type only needs an entry in TAXONOMIES – no new table or admin page.
 */
final class Catalog
{
    /**
     * Taxonomy key => settings.
     *   label / single  admin wording
     *   nested          categories: top level + subcategories (parent_id)
     *   hex             colours: HEX code + swatch image
     *   image           brands: logo
     *   option          offered as a product option (size / colour / material)
     */
    public const TAXONOMIES = [
        'category'   => ['label' => 'Categories',            'single' => 'Category',             'nested' => true],
        'type'       => ['label' => 'Product Types',         'single' => 'Product Type'],
        'brand'      => ['label' => 'Brands',                'single' => 'Brand', 'image' => ['w' => 600, 'h' => 300, 'fit' => true]],
        'material'   => ['label' => 'Materials',             'single' => 'Material', 'option' => true],
        'colour'     => ['label' => 'Colours',               'single' => 'Colour', 'option' => true, 'hex' => true, 'image' => ['w' => 200, 'h' => 200]],
        'size'       => ['label' => 'Sizes',                 'single' => 'Size', 'option' => true],
        'env_rating' => ['label' => 'Environmental Ratings', 'single' => 'Environmental Rating'],
    ];

    /** Product column that holds a single term of this taxonomy (null = linked via product_term_links). */
    public const COLUMN = [
        'category'   => 'category_id',
        'type'       => 'type_id',
        'brand'      => 'brand_id',
        'env_rating' => 'env_rating_id',
    ];

    private static array $cache = [];

    public static function valid(string $taxonomy): bool
    {
        return isset(self::TAXONOMIES[$taxonomy]);
    }

    /** All terms of a taxonomy, ordered (categories: parents first, each followed by its children). */
    public static function terms(string $taxonomy, bool $activeOnly = false): array
    {
        $key = $taxonomy . ($activeOnly ? ':a' : '');
        if (!isset(self::$cache[$key])) {
            $rows = Database::all(
                'SELECT * FROM product_terms WHERE taxonomy = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, name',
                [$taxonomy]
            );
            if (!empty(self::TAXONOMIES[$taxonomy]['nested'])) {
                $parents = array_filter($rows, static fn($r) => $r['parent_id'] === null);
                $ordered = [];
                foreach ($parents as $p) {
                    $p['depth'] = 0;
                    $ordered[] = $p;
                    foreach ($rows as $c) {
                        if ((int)$c['parent_id'] === (int)$p['id']) {
                            $c['depth'] = 1;
                            $ordered[] = $c;
                        }
                    }
                }
                $rows = $ordered;
            }
            self::$cache[$key] = $rows;
        }
        return self::$cache[$key];
    }

    /** id => name map (for selects). */
    public static function options(string $taxonomy, ?bool $topLevel = null): array
    {
        $out = [];
        foreach (self::terms($taxonomy) as $t) {
            if ($topLevel === true && $t['parent_id'] !== null) continue;
            if ($topLevel === false && $t['parent_id'] === null) continue;
            $out[(int)$t['id']] = $t['name'];
        }
        return $out;
    }

    public static function term(int $id): ?array
    {
        return $id > 0 ? Database::one('SELECT * FROM product_terms WHERE id = ?', [$id]) : null;
    }

    public static function termBySlug(string $taxonomy, string $slug): ?array
    {
        return Database::one('SELECT * FROM product_terms WHERE taxonomy = ? AND slug = ?', [$taxonomy, $slug]);
    }

    /** Create or update a term. Returns its id. */
    public static function saveTerm(?int $id, string $taxonomy, array $d): int
    {
        $slug = self::uniqueSlug('product_terms', $d['slug'] !== '' ? $d['slug'] : $d['name'], $id, 'taxonomy = ' . Database::pdo()->quote($taxonomy));
        $params = [$d['name'], $slug, $d['parent_id'], $d['hex'], $d['image'], $d['is_active'] ? 1 : 0];
        if ($id) {
            Database::run('UPDATE product_terms SET name = ?, slug = ?, parent_id = ?, hex = ?, image = ?, is_active = ?, updated_at = NOW() WHERE id = ?', array_merge($params, [$id]));
            return $id;
        }
        $next = (int)Database::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_terms WHERE taxonomy = ?', [$taxonomy]);
        Database::run(
            'INSERT INTO product_terms (name, slug, parent_id, hex, image, is_active, taxonomy, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            array_merge($params, [$taxonomy, $next])
        );
        return Database::lastId();
    }

    /** Delete a term and clear it from products (subcategories move up to top level). */
    public static function deleteTerm(int $id): void
    {
        $t = self::term($id);
        if (!$t) return;
        $col = self::COLUMN[$t['taxonomy']] ?? null;
        if ($col) {
            Database::run("UPDATE products SET $col = NULL WHERE $col = ?", [$id]);
        }
        if ($t['taxonomy'] === 'category') {
            Database::run('UPDATE products SET subcategory_id = NULL WHERE subcategory_id = ?', [$id]);
            Database::run('UPDATE product_terms SET parent_id = NULL WHERE parent_id = ?', [$id]);
        }
        foreach (['size_id', 'colour_id', 'material_id'] as $vc) {
            Database::run("UPDATE product_variants SET $vc = NULL WHERE $vc = ?", [$id]);
        }
        Database::run('DELETE FROM product_terms WHERE id = ?', [$id]); // term links cascade
    }

    /** Number of products using each term of a taxonomy: [term_id => count]. */
    public static function usage(string $taxonomy): array
    {
        $col = self::COLUMN[$taxonomy] ?? null;
        if ($taxonomy === 'category') {
            $rows = Database::all('SELECT t.id, (SELECT COUNT(*) FROM products p WHERE p.category_id = t.id OR p.subcategory_id = t.id) AS n FROM product_terms t WHERE t.taxonomy = ?', [$taxonomy]);
        } elseif ($col) {
            $rows = Database::all("SELECT $col AS id, COUNT(*) AS n FROM products WHERE $col IS NOT NULL GROUP BY $col");
        } else {
            $rows = Database::all('SELECT l.term_id AS id, COUNT(*) AS n FROM product_term_links l JOIN product_terms t ON t.id = l.term_id AND t.taxonomy = ? GROUP BY l.term_id', [$taxonomy]);
        }
        return array_column($rows, 'n', 'id');
    }

    /* ---------------- families ---------------- */

    public static function families(bool $activeOnly = false): array
    {
        return Database::all('SELECT * FROM product_families' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, name');
    }

    public static function family(int $id): ?array
    {
        return $id > 0 ? Database::one('SELECT * FROM product_families WHERE id = ?', [$id]) : null;
    }

    /* ---------------- helpers ---------------- */

    public static function slugify(string $s): string
    {
        $s = strtolower(trim($s));
        $s = strtr($s, ['&' => ' and ', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'á' => 'a', 'à' => 'a', 'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ñ' => 'n', 'ç' => 'c']);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim((string)$s, '-') ?: 'item';
    }

    /** Slug that is unique in $table (optionally within an extra SQL condition). */
    public static function uniqueSlug(string $table, string $source, ?int $exceptId = null, string $extraWhere = ''): string
    {
        $table = in_array($table, ['product_terms', 'product_families', 'products'], true) ? $table : 'products';
        $base = substr(self::slugify($source), 0, 150);
        $slug = $base;
        for ($i = 2; ; $i++) {
            $sql = "SELECT COUNT(*) FROM $table WHERE slug = ?" . ($exceptId ? ' AND id <> ' . (int)$exceptId : '') . ($extraWhere !== '' ? ' AND ' . $extraWhere : '');
            if ((int)Database::value($sql, [$slug]) === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i;
        }
    }
}
