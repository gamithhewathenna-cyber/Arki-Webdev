<?php
declare(strict_types=1);

/**
 * Development-stage password protection for the public website.
 * Enabled/disabled and password-changed from Admin → Settings.
 * Logged-in administrators bypass the lock automatically.
 */
final class DevLock
{
    private const SCOPE = 'devlock';

    public static function enabled(): bool
    {
        return Settings::get('dev_lock_enabled', '0') === '1' && (string)Settings::get('dev_lock_password', '') !== '';
    }

    public static function enforce(): void
    {
        if (!self::enabled() || !empty($_SESSION['dev_unlocked']) || Auth::check()) {
            return;
        }

        $error = '';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['dev_password'])) {
            csrf_verify();
            if (!throttle_allowed(self::SCOPE, 8, 15)) {
                $error = 'Too many attempts. Please wait 15 minutes and try again.';
            } elseif (password_verify((string)$_POST['dev_password'], (string)Settings::get('dev_lock_password'))) {
                throttle_clear(self::SCOPE);
                session_regenerate_id(true);
                $_SESSION['dev_unlocked'] = true;
                redirect(strtok($_SERVER['REQUEST_URI'] ?? url(), '?') ?: url());
            } else {
                throttle_hit(self::SCOPE);
                $error = 'Incorrect password.';
            }
        }

        http_response_code(401);
        header('Cache-Control: no-store');
        render('gate', ['error' => $error, 'siteName' => Settings::get('site_name', 'Website')]);
        exit;
    }
}
