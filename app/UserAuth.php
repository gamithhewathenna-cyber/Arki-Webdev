<?php
declare(strict_types=1);

/**
 * Website accounts (customers and agents) – separate from CMS administrators.
 *
 *  - Customers are active straight after registering.
 *  - Agents stay 'pending' until approved in the CMS (Accounts → Agents).
 *  - Passwords: password_hash(); sessions are regenerated on login.
 *  - "Remember me": selector + hashed validator in user_tokens (30 days),
 *    rotated on every use and removed on logout.
 *  - Login attempts are throttled per IP (5 per 15 minutes).
 */
final class UserAuth
{
    public const TYPES    = ['customer' => 'Customer', 'agent' => 'Agent'];
    public const STATUSES = ['active' => 'Active', 'pending' => 'Pending approval', 'rejected' => 'Rejected', 'inactive' => 'Deactivated'];
    public const STATES   = ['ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA', 'Outside Australia'];

    private const SESSION  = 'user_id';
    private const REMEMBER = 'arki_remember';
    private const REMEMBER_DAYS = 30;

    private static ?array $user = null;
    private static bool $loaded = false;

    /* ---------------- current visitor ---------------- */

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        try {
            $id = (int)($_SESSION[self::SESSION] ?? 0);
            if (!$id) {
                $id = self::fromRememberCookie();
            }
            if ($id) {
                $u = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
                if ($u && $u['status'] === 'active') {
                    self::$user = $u;
                } else {
                    unset($_SESSION[self::SESSION]); // deactivated / deleted since login
                }
            }
        } catch (Throwable $e) {
            self::$user = null; // tables not created yet
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** An approved agent is signed in (sees agent prices). */
    public static function isAgent(): bool
    {
        $u = self::user();
        return $u !== null && $u['account_type'] === 'agent' && $u['status'] === 'active';
    }

    public static function require(): array
    {
        $u = self::user();
        if (!$u) {
            redirect(url('account/login/') . '?next=' . rawurlencode((string)($_SERVER['REQUEST_URI'] ?? '')));
        }
        return $u;
    }

    /* ---------------- register / login / logout ---------------- */

    /**
     * Validate and create an account. Returns [user id or null, errors].
     * Agents are created as 'pending'.
     */
    public static function register(string $type, array $in): array
    {
        $type = isset(self::TYPES[$type]) ? $type : 'customer';
        $t = static fn(string $k, int $max) => mb_substr(trim((string)($in[$k] ?? '')), 0, $max);
        $d = [
            'first_name' => $t('first_name', 100), 'last_name' => $t('last_name', 100),
            'email' => mb_strtolower($t('email', 190)), 'phone' => $t('phone', 40),
            'company' => $t('company', 150), 'abn' => preg_replace('/\s+/', '', $t('abn', 20)),
            'job_title' => $t('job_title', 100), 'website' => $t('website', 255),
            'state' => $t('state', 40), 'about' => mb_substr(trim(str_replace("\r\n", "\n", (string)($in['about'] ?? ''))), 0, 3000),
        ];
        $password = (string)($in['password'] ?? '');
        $errors = [];
        if ($d['first_name'] === '') $errors['first_name'] = 'Please enter your first name.';
        if ($d['last_name'] === '')  $errors['last_name'] = 'Please enter your last name.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (Database::value('SELECT id FROM users WHERE email = ?', [$d['email']])) {
            $errors['email'] = 'An account with this email already exists. Please log in or reset your password.';
        }
        if ($d['phone'] !== '' && !preg_match('/^[0-9 +()\-.]{6,40}$/', $d['phone'])) $errors['phone'] = 'Please enter a valid phone number.';
        if ($e = self::passwordProblem($password)) $errors['password'] = $e;
        elseif (!hash_equals($password, (string)($in['password_confirm'] ?? ''))) $errors['password_confirm'] = 'The passwords do not match.';
        if (empty($in['terms'])) $errors['terms'] = 'Please accept the terms and privacy policy.';

        if ($type === 'agent') {
            if ($d['company'] === '') $errors['company'] = 'Please enter your company name.';
            if ($d['phone'] === '' && !isset($errors['phone'])) $errors['phone'] = 'Please enter a phone number.';
            if ($d['abn'] !== '' && !preg_match('/^\d{11}$/', $d['abn'])) $errors['abn'] = 'An ABN has 11 digits.';
            if ($d['website'] !== '' && !preg_match('#^https?://#i', $d['website'])) $d['website'] = 'https://' . $d['website'];
            if ($d['website'] !== '' && !filter_var($d['website'], FILTER_VALIDATE_URL)) $errors['website'] = 'Please enter a valid website address.';
            if ($d['state'] !== '' && !in_array($d['state'], self::STATES, true)) $d['state'] = '';
        } else {
            $d['company'] = $d['abn'] = $d['job_title'] = $d['website'] = $d['state'] = $d['about'] = '';
        }
        if ($errors) {
            return [null, $errors];
        }

        $status = $type === 'agent' ? 'pending' : 'active';
        Database::run(
            'INSERT INTO users (account_type, status, first_name, last_name, email, phone, password_hash, company, abn, job_title, website, state, about, newsletter, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$type, $status, $d['first_name'], $d['last_name'], $d['email'], $d['phone'] ?: null, password_hash($password, PASSWORD_DEFAULT),
             $d['company'] ?: null, $d['abn'] ?: null, $d['job_title'] ?: null, $d['website'] ?: null, $d['state'] ?: null, $d['about'] ?: null,
             !empty($in['newsletter']) ? 1 : 0]
        );
        $id = Database::lastId();
        $name = $d['first_name'] . ' ' . $d['last_name'];
        if ($type === 'agent') {
            Mailer::queue('agent_received', $d['email'], $name, 'We have received your agent registration', ['first_name' => $d['first_name']]);
        } else {
            Mailer::queue('welcome', $d['email'], $name, 'Welcome to ' . Settings::get('site_name', 'our website'), ['first_name' => $d['first_name']]);
        }
        return [$id, []];
    }

    /** Password rule: at least 8 characters with a letter and a number. */
    public static function passwordProblem(string $p): ?string
    {
        if (strlen($p) < 8 || !preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) {
            return 'Use at least 8 characters, including a letter and a number.';
        }
        if (strlen($p) > 200) {
            return 'That password is too long.';
        }
        return null;
    }

    /**
     * Try to sign in. Returns ['ok' => bool, 'error' => string, 'field' => string].
     * Same message for unknown email and wrong password (no account probing).
     */
    public static function attempt(string $email, string $password, bool $remember): array
    {
        if (!throttle_allowed('user_login', 5, 15)) {
            return ['ok' => false, 'error' => 'Too many attempts. Please wait 15 minutes and try again, or reset your password.', 'field' => ''];
        }
        $email = mb_strtolower(trim($email));
        $u = $email !== '' ? Database::one('SELECT * FROM users WHERE email = ?', [$email]) : null;
        $hash = $u['password_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // even timing
        if (!$u || !password_verify($password, $hash)) {
            throttle_hit('user_login');
            return ['ok' => false, 'error' => 'The email address or password is incorrect.', 'field' => 'password'];
        }
        if ($u['status'] !== 'active') {
            $msg = match ($u['status']) {
                'pending'  => 'Thanks for registering as an agent – your account is waiting for approval. We’ll be in touch soon.',
                'rejected' => 'Your agent registration was not approved. Please contact us if you have any questions.',
                default    => 'This account has been deactivated. Please contact us for help.',
            };
            return ['ok' => false, 'error' => $msg, 'field' => ''];
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
        }
        throttle_clear('user_login');
        self::login((int)$u['id'], $remember);
        return ['ok' => true, 'error' => '', 'field' => ''];
    }

    /** Start a signed-in session (and keep the guest cart). */
    public static function login(int $id, bool $remember): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION] = $id;
        unset($_SESSION['_csrf']);
        Database::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
        if ($remember) {
            self::issueRememberCookie($id);
        }
        self::$loaded = false;
        Cart::attachToUser($id);
    }

    public static function logout(): void
    {
        $cookie = (string)($_COOKIE[self::REMEMBER] ?? '');
        if (str_contains($cookie, ':')) {
            Database::run("DELETE FROM user_tokens WHERE type = 'remember' AND selector = ?", [explode(':', $cookie, 2)[0]]);
        }
        self::clearCookie();
        unset($_SESSION[self::SESSION]);
        session_regenerate_id(true);
        self::$user = null;
        self::$loaded = true;
    }

    /* ---------------- remember me ---------------- */

    private static function issueRememberCookie(int $id): void
    {
        [$selector, $validator] = self::newToken($id, 'remember', self::REMEMBER_DAYS * 86400);
        self::setCookie($selector . ':' . $validator, time() + self::REMEMBER_DAYS * 86400);
    }

    private static function fromRememberCookie(): int
    {
        $cookie = (string)($_COOKIE[self::REMEMBER] ?? '');
        if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $m)) {
            return 0;
        }
        $row = self::findToken($m[1], $m[2], 'remember');
        if (!$row) {
            self::clearCookie();
            return 0;
        }
        // Rotate: one-time use, new cookie.
        Database::run('DELETE FROM user_tokens WHERE id = ?', [$row['id']]);
        session_regenerate_id(true);
        $_SESSION[self::SESSION] = (int)$row['user_id'];
        self::issueRememberCookie((int)$row['user_id']);
        return (int)$row['user_id'];
    }

    private static function setCookie(string $value, int $expires): void
    {
        setcookie(self::REMEMBER, $value, [
            'expires' => $expires, 'path' => (BASE_URL ?: '') . '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax',
        ]);
    }

    private static function clearCookie(): void
    {
        if (isset($_COOKIE[self::REMEMBER])) {
            self::setCookie('', time() - 3600);
        }
    }

    /* ---------------- tokens (remember me, password reset, future email verification) ---------------- */

    /** @return array{0: string, 1: string} [selector, validator] – only the hash of the validator is stored. */
    public static function newToken(int $userId, string $type, int $ttlSeconds): array
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        Database::run('DELETE FROM user_tokens WHERE expires_at < NOW()');
        Database::run(
            'INSERT INTO user_tokens (user_id, type, selector, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())',
            [$userId, $type, $selector, hash('sha256', $validator), $ttlSeconds]
        );
        return [$selector, $validator];
    }

    public static function findToken(string $selector, string $validator, string $type): ?array
    {
        $row = Database::one('SELECT * FROM user_tokens WHERE selector = ? AND type = ? AND used_at IS NULL AND expires_at > NOW()', [$selector, $type]);
        return ($row && hash_equals($row['token_hash'], hash('sha256', $validator))) ? $row : null;
    }

    /**
     * Forgot password: create a 1-hour reset link and queue the email (sent once
     * email is configured). Always behaves the same, whether or not the email exists.
     */
    public static function requestPasswordReset(string $email): void
    {
        $u = Database::one("SELECT * FROM users WHERE email = ? AND status = 'active'", [mb_strtolower(trim($email))]);
        if (!$u) {
            return;
        }
        Database::run("DELETE FROM user_tokens WHERE user_id = ? AND type = 'reset'", [$u['id']]);
        [$selector, $validator] = self::newToken((int)$u['id'], 'reset', 3600);
        Mailer::queue('password_reset', $u['email'], $u['first_name'] . ' ' . $u['last_name'], 'Reset your password', [
            'first_name' => $u['first_name'],
            'reset_url'  => Mailer::absoluteUrl('account/reset-password/?t=' . $selector . '.' . $validator),
            'expires'    => '1 hour',
        ]);
    }

    /** Set a new password from a reset link. Returns an error message or null on success. */
    public static function resetPassword(string $token, string $password, string $confirm): ?string
    {
        $row = self::tokenFromLink($token);
        if (!$row) return 'This reset link is invalid or has expired. Please request a new one.';
        if ($e = self::passwordProblem($password)) return $e;
        if (!hash_equals($password, $confirm)) return 'The passwords do not match.';
        Database::run('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
        Database::run("DELETE FROM user_tokens WHERE user_id = ? AND type IN ('reset', 'remember')", [$row['user_id']]); // sign out everywhere
        return null;
    }

    public static function tokenFromLink(string $token): ?array
    {
        return preg_match('/^([a-f0-9]{24})\.([a-f0-9]{64})$/', $token, $m) ? self::findToken($m[1], $m[2], 'reset') : null;
    }

    /* ---------------- CMS (agent approval) ---------------- */

    public static function setStatus(int $id, string $status, int $adminId): void
    {
        $u = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$u || !isset(self::STATUSES[$status]) || $u['status'] === $status) return;
        $approve = $u['account_type'] === 'agent' && $status === 'active' && $u['approved_at'] === null;
        Database::run(
            'UPDATE users SET status = ?, updated_at = NOW()' . ($approve ? ', approved_at = NOW(), approved_by = ?' : '') . ' WHERE id = ?',
            $approve ? [$status, $adminId, $id] : [$status, $id]
        );
        if ($status !== 'active') {
            Database::run("DELETE FROM user_tokens WHERE user_id = ? AND type = 'remember'", [$id]); // sign them out
        }
        $name = $u['first_name'] . ' ' . $u['last_name'];
        if ($approve) {
            Mailer::queue('agent_approved', $u['email'], $name, 'Your agent account is approved', ['first_name' => $u['first_name'], 'login_url' => Mailer::absoluteUrl('account/login/')]);
        } elseif ($u['account_type'] === 'agent' && $status === 'rejected') {
            Mailer::queue('agent_rejected', $u['email'], $name, 'Your agent registration', ['first_name' => $u['first_name']]);
        }
    }
}
