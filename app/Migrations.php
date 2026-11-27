<?php
declare(strict_types=1);

/**
 * One-time content updates for databases that were imported before a
 * feature existed. Each runs once and is recorded in settings
 * ('migrations_done'), so later CMS edits are never overwritten.
 */
final class Migrations
{
    public static function run(): void
    {
        $done = json_decode((string)Settings::get('migrations_done', '[]'), true) ?: [];
        $ran = false;

        foreach (self::list() as $name => $fn) {
            if (in_array($name, $done, true)) {
                continue;
            }
            try {
                $fn();
            } catch (Throwable $e) {
                error_log('Migration ' . $name . ' failed: ' . $e->getMessage());
                continue; // retried on the next request
            }
            $done[] = $name;
            $ran = true;
        }
        if ($ran) {
            Settings::set('migrations_done', json_encode($done));
        }
    }

    private static function list(): array
    {
        return [
            // Link the homepage hero cards to their new pages.
            '2026-11-27-link-work-card'  => static fn() => self::linkHeroCard('Work', 'work/'),
            '2026-11-27-link-learn-card' => static fn() => self::linkHeroCard('Learn', 'learn/'),
            '2026-11-27-link-store-card' => static fn() => self::linkHeroCard('Store', 'store/'),

            // Product catalogue: tables + demo products (app/schema/products_db.php).
            '2026-11-28-products-schema' => static function (): void {
                foreach (self::statements((string)require APP_PATH . '/schema/products_db.php') as $sql) {
                    Database::pdo()->exec($sql);
                }
            },

            // One category tree per storefront (app/schema/categories_db.php).
            // Listed before the link update, so a new install links cards straight to the new categories.
            '2026-11-28-storefront-categories' => static function (): void {
                foreach (['work', 'learn', 'store'] as $store) {
                    ContentRepository::sync($store); // category cards must exist before they are linked
                }
                foreach (self::statements((string)require APP_PATH . '/schema/categories_db.php') as $sql) {
                    Database::pdo()->exec($sql);
                }
            },

            // Point the storefront pages' links at the new product listings.
            '2026-11-28-link-product-listings' => static function (): void {
                foreach (['work', 'learn', 'store'] as $store) {
                    ContentRepository::sync($store); // make sure the page's sections exist
                    $list = $store . '/products/';
                    self::setSectionLink($store, 'banner', 'link_url', $list);
                    self::setSectionLink($store, 'categories', 'link_url', $list);
                    self::setSectionLink($store, 'products', 'link_url', $list);
                    self::linkCategoryCards($store, $list);
                }
            },
        ];
    }

    /** Split an SQL script into statements (separated by ";" at the end of a line). */
    public static function statements(string $sql): array
    {
        $out = [];
        foreach (preg_split('/;[ \t]*\R/', $sql . "\n") as $stmt) {
            $stmt = trim(preg_replace('/^\s*--.*$/m', '', $stmt));
            if ($stmt !== '') {
                $out[] = rtrim($stmt, ';');
            }
        }
        return $out;
    }

    /** Set a link field of a page section if it is still empty or "#". */
    private static function setSectionLink(string $page, string $key, string $field, string $url): void
    {
        $s = Database::one('SELECT id, data FROM sections WHERE page = ? AND section_key = ?', [$page, $key]);
        if (!$s) return;
        $d = json_decode((string)$s['data'], true) ?: [];
        if (in_array(trim((string)($d[$field] ?? '')), ['', '#'], true)) {
            $d[$field] = $url;
            ContentRepository::saveSectionData((int)$s['id'], $d);
        }
    }

    /** Link category cards (Tables, Seating …) to the filtered product listing. */
    private static function linkCategoryCards(string $page, string $list): void
    {
        // Card title → top-level category of that storefront with the same name (Tables → tables …).
        $s = Database::one("SELECT id FROM sections WHERE page = ? AND section_key = 'categories'", [$page]);
        if (!$s) return;
        $have = array_column(Catalog::terms(Catalog::categoryTax($page)), 'slug');
        foreach (Database::all('SELECT id, data, is_active FROM section_items WHERE section_id = ?', [$s['id']]) as $it) {
            $d = json_decode((string)$it['data'], true) ?: [];
            $slug = Catalog::slugify((string)($d['title'] ?? ''));
            $slug = in_array($slug, $have, true) ? $slug : null;
            if ($slug && in_array(trim((string)($d['url'] ?? '')), ['', '#'], true)) {
                $d['url'] = $list . '?cat[]=' . $slug;
                ContentRepository::saveItem((int)$it['id'], (int)$s['id'], $d, (bool)$it['is_active']);
            }
        }
    }

    /** Give the homepage hero card with this title a link, unless an editor already set a real one. */
    private static function linkHeroCard(string $title, string $url): void
    {
        $hero = Database::one("SELECT id FROM sections WHERE page = 'home' AND section_key = 'hero'");
        if (!$hero) {
            return;
        }
        foreach (Database::all('SELECT id, data, is_active FROM section_items WHERE section_id = ?', [$hero['id']]) as $it) {
            $d = json_decode((string)$it['data'], true) ?: [];
            if (strcasecmp(trim((string)($d['title'] ?? '')), $title) === 0 && in_array(trim((string)($d['url'] ?? '')), ['', '#'], true)) {
                $d['url'] = $url;
                ContentRepository::saveItem((int)$it['id'], (int)$hero['id'], $d, (bool)$it['is_active']);
            }
        }
    }
}
