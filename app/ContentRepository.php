<?php
declare(strict_types=1);

/**
 * Reads and writes page sections and their repeatable items.
 *
 * Every page is a list of sections (page + section_key). Each section stores
 * its own fields as JSON and can own any number of ordered items (cards,
 * slides, links …), also stored as JSON. Field definitions live in
 * app/schema/pages.php, so new pages/sections need no database changes.
 */
final class ContentRepository
{
    private static ?array $schema = null;

    public static function schema(): array
    {
        if (self::$schema === null) {
            self::$schema = require APP_PATH . '/schema/pages.php';
        }
        return self::$schema;
    }

    public static function sectionSchema(string $page, string $key): ?array
    {
        return self::schema()[$page]['sections'][$key] ?? null;
    }

    /**
     * Create database rows for sections that exist in the schema but not yet
     * in the database, filled from app/schema/seeds.php. Lets new pages and
     * sections go live without any SQL import. Cheap no-op once complete.
     */
    public static function sync(string $page): void
    {
        $schema = self::schema()[$page] ?? null;
        if (!$schema) {
            return;
        }
        $existing = array_column(Database::all('SELECT section_key FROM sections WHERE page = ?', [$page]), 'section_key');
        $missing = array_diff(array_keys($schema['sections']), $existing);
        if (!$missing) {
            return;
        }
        $seeds = (require APP_PATH . '/schema/seeds.php')[$page] ?? [];

        foreach ($missing as $key) {
            $seed  = $seeds[$key] ?? [];
            $data  = $seed['data'] ?? [];
            $items = $seed['items'] ?? [];
            $active = [];

            if (!empty($seed['copy_from'])) {
                $src = Database::one('SELECT * FROM sections WHERE page = ? AND section_key = ?', [$seed['copy_from'], $key]);
                if ($src) {
                    $data  = json_decode((string)$src['data'], true) ?: [];
                    $items = [];
                    foreach (Database::all('SELECT data, is_active FROM section_items WHERE section_id = ? ORDER BY sort_order, id', [$src['id']]) as $it) {
                        $items[]  = json_decode((string)$it['data'], true) ?: [];
                        $active[] = (int)$it['is_active'];
                    }
                }
            }

            $next = (int)Database::value('SELECT COALESCE(MAX(sort_order),0)+1 FROM sections WHERE page = ?', [$page]);
            // IGNORE: if two requests race, only one creates the section (unique page+section_key).
            $st = Database::run(
                'INSERT IGNORE INTO sections (page, section_key, data, is_active, sort_order, updated_at) VALUES (?, ?, ?, 1, ?, NOW())',
                [$page, $key, self::json($data), $next]
            );
            if ($st->rowCount() === 0) {
                continue;
            }
            $sid = Database::lastId();
            foreach (array_values($items) as $i => $it) {
                Database::run(
                    'INSERT INTO section_items (section_id, data, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
                    [$sid, self::json($it), $active[$i] ?? 1, $i + 1]
                );
            }
        }
    }

    /** All sections of a page, ordered, each with decoded data and items. */
    public static function page(string $page, bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM sections WHERE page = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, id';
        $rows = Database::all($sql, [$page]);
        if (!$rows) {
            return [];
        }
        $ids = array_column($rows, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $itemSql = "SELECT * FROM section_items WHERE section_id IN ($in)" . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, id';
        $items = [];
        foreach (Database::all($itemSql, $ids) as $it) {
            $items[$it['section_id']][] = self::decodeItem($it);
        }
        $out = [];
        foreach ($rows as $r) {
            $s = self::decodeSection($r);
            $s['items'] = $items[$r['id']] ?? [];
            $out[$r['section_key']] = $s;
        }
        return $out;
    }

    public static function section(int $id): ?array
    {
        $r = Database::one('SELECT * FROM sections WHERE id = ?', [$id]);
        return $r ? self::decodeSection($r) : null;
    }

    public static function items(int $sectionId): array
    {
        return array_map(
            [self::class, 'decodeItem'],
            Database::all('SELECT * FROM section_items WHERE section_id = ? ORDER BY sort_order, id', [$sectionId])
        );
    }

    public static function item(int $id): ?array
    {
        $r = Database::one('SELECT * FROM section_items WHERE id = ?', [$id]);
        return $r ? self::decodeItem($r) : null;
    }

    public static function saveSectionData(int $id, array $data): void
    {
        Database::run('UPDATE sections SET data = ?, updated_at = NOW() WHERE id = ?', [self::json($data), $id]);
    }

    public static function saveItem(?int $id, int $sectionId, array $data, bool $active): int
    {
        if ($id) {
            Database::run(
                'UPDATE section_items SET data = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND section_id = ?',
                [self::json($data), $active ? 1 : 0, $id, $sectionId]
            );
            return $id;
        }
        $next = (int)Database::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM section_items WHERE section_id = ?', [$sectionId]);
        Database::run(
            'INSERT INTO section_items (section_id, data, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
            [$sectionId, self::json($data), $active ? 1 : 0, $next]
        );
        return Database::lastId();
    }

    /** Swap an entry with its neighbour (direction -1 = up, 1 = down). */
    public static function move(string $table, int $id, int $direction, string $groupCol, $groupVal): void
    {
        $table = $table === 'sections' ? 'sections' : 'section_items';
        $groupCol = $groupCol === 'page' ? 'page' : 'section_id';

        $rows = Database::all("SELECT id FROM $table WHERE $groupCol = ? ORDER BY sort_order, id", [$groupVal]);
        $ids = array_map('intval', array_column($rows, 'id'));
        $pos = array_search($id, $ids, true);
        if ($pos === false) {
            return;
        }
        $swap = $pos + ($direction < 0 ? -1 : 1);
        if ($swap < 0 || $swap >= count($ids)) {
            return;
        }
        [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        foreach ($ids as $i => $rid) {
            Database::run("UPDATE $table SET sort_order = ? WHERE id = ?", [$i + 1, $rid]);
        }
        $pdo->commit();
    }

    private static function decodeSection(array $r): array
    {
        $r['data'] = json_decode((string)$r['data'], true) ?: [];
        return $r;
    }

    private static function decodeItem(array $r): array
    {
        $r['data'] = json_decode((string)$r['data'], true) ?: [];
        return $r;
    }

    private static function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
