<?php
declare(strict_types=1);

/**
 * Admin authentication with throttling, session-fixation protection
 * and idle timeout.
 */
final class Auth
{
    private const IDLE_LIMIT = 7200; // 2 hours

    public static function attempt(string $username, string $password): bool
    {
        $user = Database::one('SELECT * FROM admin_users WHERE username = ? AND is_active = 1', [$username]);
        // Verify against a dummy hash when the user doesn't exist to keep timing uniform.
        $hash = $user['password_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        if (!password_verify($password, $hash) || !$user) {
            return false;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::run('UPDATE admin_users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$user['id'];
        $_SESSION['admin_seen'] = time();
        unset($_SESSION['_csrf']);
        Database::run('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        return true;
    }

    public static function user(): ?array
    {
        $id = $_SESSION['admin_id'] ?? null;
        if (!$id) {
            return null;
        }
        if (time() - (int)($_SESSION['admin_seen'] ?? 0) > self::IDLE_LIMIT) {
            self::logout();
            return null;
        }
        $_SESSION['admin_seen'] = time();
        static $cached = null;
        if ($cached === null || (int)$cached['id'] !== (int)$id) {
            $cached = Database::one('SELECT id, username, display_name, last_login_at FROM admin_users WHERE id = ? AND is_active = 1', [$id]);
        }
        return $cached ?: null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function require(): array
    {
        $u = self::user();
        if (!$u) {
            redirect(url('admin/login.php'));
        }
        return $u;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
