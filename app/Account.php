<?php
declare(strict_types=1);

/**
 * Website account pages (login, register, password reset, my account) and the
 * shopping cart page. Forms use Post/Redirect/Get; results travel in the session.
 */
final class Account
{
    private const FLASH = 'account_form';

    /* ---------------- shared ---------------- */

    private static function page(string $template, string $title, array $vars): void
    {
        render('layout/page', [
            'pageTitle'   => $title . ' – ' . Settings::get('site_name', 'ARKI Environments'),
            'bodyClass'   => 'page-account',
            'global'      => ContentRepository::page('global'),
            'sections'    => [],
            'content'     => $template,
            'contentVars' => $vars + ['form' => self::takeForm()],
        ]);
    }

    private static function back(string $path, array $errors = [], array $old = [], string $notice = ''): void
    {
        unset($old['password'], $old['password_confirm'], $old['current_password']); // never echo passwords
        $_SESSION[self::FLASH] = ['errors' => $errors, 'old' => $old, 'notice' => $notice];
        redirect(url($path));
    }

    private static function takeForm(): array
    {
        $f = $_SESSION[self::FLASH] ?? [];
        unset($_SESSION[self::FLASH]);
        return $f + ['errors' => [], 'old' => [], 'notice' => ''];
    }

    private static function post(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return false;
        csrf_verify();
        return true;
    }

    /** Only allow returning to a page of this website. */
    private static function safeNext(?string $next): string
    {
        $next = (string)$next;
        return (str_starts_with($next, '/') && !str_starts_with($next, '//') && !preg_match('#[\r\n\\\\]#', $next)) ? $next : url('account/');
    }

    /* ---------------- login ---------------- */

    public static function login(): void
    {
        Construction::enforce();
        if (UserAuth::check()) redirect(url('account/'));
        if (self::post()) {
            $email = (string)($_POST['email'] ?? '');
            $errors = [];
            if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
            if ((string)($_POST['password'] ?? '') === '') $errors['password'] = 'Please enter your password.';
            if (!$errors) {
                $r = UserAuth::attempt($email, (string)$_POST['password'], !empty($_POST['remember']));
                if ($r['ok']) redirect(self::safeNext($_POST['next'] ?? null));
                $errors['form'] = $r['error'];
                if ($r['field']) $errors[$r['field']] = ' ';
            }
            self::back('account/login/' . (!empty($_POST['next']) ? '?next=' . rawurlencode((string)$_POST['next']) : ''), $errors, ['email' => $email, 'remember' => !empty($_POST['remember'])]);
        }
        self::page('account/login', 'Log In', ['next' => (string)($_GET['next'] ?? '')]);
    }

    public static function logout(): void
    {
        if (self::post()) UserAuth::logout();
        redirect(url());
    }

    /* ---------------- register ---------------- */

    public static function register(): void
    {
        Construction::enforce();
        if (UserAuth::check()) redirect(url('account/'));
        $type = ($_GET['type'] ?? $_POST['type'] ?? '') === 'agent' ? 'agent' : 'customer';
        if (self::post()) {
            [$id, $errors] = UserAuth::register($type, $_POST);
            if ($errors) {
                self::back('account/register/?type=' . $type, $errors + ['form' => 'Please check the highlighted fields.'], $_POST);
            }
            if ($type === 'customer') {
                UserAuth::login((int)$id, false);
                $_SESSION[self::FLASH] = ['errors' => [], 'old' => [], 'notice' => 'Welcome! Your account is ready.'];
                redirect(url('account/'));
            }
            self::back('account/register/?type=agent&done=1');
        }
        self::page('account/register', $type === 'agent' ? 'Agent Registration' : 'Create An Account', ['type' => $type, 'done' => !empty($_GET['done'])]);
    }

    /* ---------------- forgot / reset password ---------------- */

    public static function forgot(): void
    {
        Construction::enforce();
        if (self::post()) {
            $email = trim((string)($_POST['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                self::back('account/forgot-password/', ['email' => 'Please enter a valid email address.', 'form' => 'Please check the highlighted field.'], ['email' => $email]);
            }
            if (throttle_allowed('pw_reset', 5, 30)) {
                throttle_hit('pw_reset');
                UserAuth::requestPasswordReset($email);
            }
            self::back('account/forgot-password/?sent=1');
        }
        self::page('account/forgot', 'Forgot Password', ['sent' => !empty($_GET['sent'])]);
    }

    public static function reset(): void
    {
        Construction::enforce();
        $token = (string)($_GET['t'] ?? $_POST['t'] ?? '');
        if (self::post()) {
            $err = UserAuth::resetPassword($token, (string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''));
            if ($err) self::back('account/reset-password/?t=' . rawurlencode($token), ['form' => $err, 'password' => ' ']);
            $_SESSION[self::FLASH] = ['errors' => [], 'old' => [], 'notice' => 'Your password has been changed. Please log in.'];
            redirect(url('account/login/'));
        }
        self::page('account/reset', 'Reset Password', ['token' => $token, 'valid' => UserAuth::tokenFromLink($token) !== null]);
    }

    /* ---------------- my account ---------------- */

    public static function dashboard(): void
    {
        Construction::enforce();
        $u = UserAuth::require();
        if (self::post()) {
            $do = (string)($_POST['do'] ?? '');
            if ($do === 'profile') {
                $first = mb_substr(trim((string)($_POST['first_name'] ?? '')), 0, 100);
                $last = mb_substr(trim((string)($_POST['last_name'] ?? '')), 0, 100);
                $phone = mb_substr(trim((string)($_POST['phone'] ?? '')), 0, 40);
                $errors = [];
                if ($first === '') $errors['first_name'] = 'Please enter your first name.';
                if ($last === '') $errors['last_name'] = 'Please enter your last name.';
                if ($phone !== '' && !preg_match('/^[0-9 +()\-.]{6,40}$/', $phone)) $errors['phone'] = 'Please enter a valid phone number.';
                if ($errors) self::back('account/#details', $errors + ['form' => 'Please check the highlighted fields.'], $_POST);
                Database::run('UPDATE users SET first_name = ?, last_name = ?, phone = ?, newsletter = ?, updated_at = NOW() WHERE id = ?',
                    [$first, $last, $phone ?: null, !empty($_POST['newsletter']) ? 1 : 0, $u['id']]);
                self::back('account/#details', [], [], 'Your details have been saved.');
            }
            if ($do === 'password') {
                $errors = [];
                if (!password_verify((string)($_POST['current_password'] ?? ''), $u['password_hash'])) $errors['current_password'] = 'Your current password is incorrect.';
                elseif ($e = UserAuth::passwordProblem((string)($_POST['password'] ?? ''))) $errors['password'] = $e;
                elseif (!hash_equals((string)$_POST['password'], (string)($_POST['password_confirm'] ?? ''))) $errors['password_confirm'] = 'The passwords do not match.';
                if ($errors) self::back('account/#password', $errors + ['form_pw' => 'Your password was not changed.']);
                Database::run('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $u['id']]);
                Database::run("DELETE FROM user_tokens WHERE user_id = ? AND type = 'remember'", [$u['id']]);
                session_regenerate_id(true);
                self::back('account/#password', [], [], 'Your password has been changed.');
            }
            redirect(url('account/'));
        }
        self::page('account/dashboard', 'My Account', ['u' => $u]);
    }

    /* ---------------- cart ---------------- */

    public static function cart(): void
    {
        Construction::enforce();
        if (self::post()) {
            $do = (string)($_POST['do'] ?? '');
            if ($do === 'add') {
                $err = Cart::add((int)($_POST['product_id'] ?? 0), [
                    'size' => $_POST['opt_size'] ?? null, 'colour' => $_POST['opt_colour'] ?? null, 'material' => $_POST['opt_material'] ?? null,
                ], (int)($_POST['qty'] ?? 1));
                self::back('cart/', $err ? ['form' => $err] : [], [], $err ? '' : 'Added to your cart.');
            }
            if ($do === 'update') {
                foreach ((array)($_POST['qty'] ?? []) as $itemId => $qty) {
                    Cart::update((int)$itemId, (int)$qty);
                }
                if (isset($_POST['step']) && preg_match('/^(\d+):(-?1)$/', (string)$_POST['step'], $m)) {
                    $line = array_values(array_filter(Cart::lines()[0], static fn($l) => $l['id'] === (int)$m[1]))[0] ?? null;
                    if ($line) Cart::update($line['id'], $line['qty'] + (int)$m[2]);
                }
                self::back('cart/', [], [], 'Your cart has been updated.');
            }
            if ($do === 'remove') {
                Cart::remove((int)($_POST['item'] ?? 0));
                self::back('cart/', [], [], 'The item has been removed.');
            }
            redirect(url('cart/'));
        }
        [$lines, $subtotal, $currency, $unavailable] = Cart::lines();
        self::page('shop/cart', 'Shopping Cart', [
            'lines' => $lines, 'subtotal' => $subtotal, 'currency' => $currency, 'unavailable' => $unavailable,
            'isAgent' => UserAuth::isAgent(), 'user' => UserAuth::user(),
        ]);
    }
}
