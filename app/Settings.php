<?php
declare(strict_types=1);

/**
 * Site-wide key/value settings (site name, development lock, etc.).
 */
final class Settings
{
    private static ?array $cache = null;

    private static function load(): void
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::all('SELECT setting_key, setting_value FROM settings') as $r) {
                self::$cache[$r['setting_key']] = $r['setting_value'];
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::run(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );
        self::load();
        self::$cache[$key] = $value;
    }
}
