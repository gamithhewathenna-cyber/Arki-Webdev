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
            // Link the homepage "Work" hero card to the new Work page,
            // unless an editor has already given it a real link.
            '2026-11-27-link-work-card' => static function (): void {
                $hero = Database::one("SELECT id FROM sections WHERE page = 'home' AND section_key = 'hero'");
                if (!$hero) {
                    return;
                }
                foreach (Database::all('SELECT id, data, is_active FROM section_items WHERE section_id = ?', [$hero['id']]) as $it) {
                    $d = json_decode((string)$it['data'], true) ?: [];
                    if (strcasecmp(trim((string)($d['title'] ?? '')), 'Work') === 0 && in_array(trim((string)($d['url'] ?? '')), ['', '#'], true)) {
                        $d['url'] = 'work/';
                        ContentRepository::saveItem((int)$it['id'], (int)$hero['id'], $d, (bool)$it['is_active']);
                    }
                }
            },
        ];
    }
}
