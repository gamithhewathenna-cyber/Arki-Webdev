<?php
/**
 * Application bootstrap: loads configuration, core classes and helpers,
 * starts a hardened session and sends baseline security headers.
 */
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('TEMPLATE_PATH', ROOT_PATH . '/templates');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

$configFile = ROOT_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Configuration missing: upload config/config.php and enter your database details.');
}

$config = require $configFile;
if (str_contains((string)($config['db']['name'] ?? ''), 'CPANELUSER') || ($config['db']['pass'] ?? '') === 'YOUR_DATABASE_PASSWORD') {
    http_response_code(503);
    header('X-Robots-Tag: noindex, nofollow');
    exit('Almost there: open config/config.php (cPanel → File Manager → Edit) and enter your database name, user and password.');
}
define('APP_DEBUG', (bool)($config['debug'] ?? false));
define('BASE_URL', rtrim((string)($config['base_url'] ?? ''), '/'));

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set($config['timezone'] ?? 'Australia/Melbourne');

require APP_PATH . '/Database.php';
require APP_PATH . '/helpers.php';
require APP_PATH . '/Settings.php';
require APP_PATH . '/ContentRepository.php';
require APP_PATH . '/Auth.php';
require APP_PATH . '/Catalog.php';
require APP_PATH . '/ProductRepository.php';
require APP_PATH . '/HtmlSanitizer.php';
require APP_PATH . '/Shop.php';
require APP_PATH . '/ContactForm.php';
require APP_PATH . '/Construction.php';
require APP_PATH . '/Mailer.php';
require APP_PATH . '/UserAuth.php';
require APP_PATH . '/Cart.php';
require APP_PATH . '/Account.php';

Database::connect($config['db']);

require APP_PATH . '/Migrations.php';
Migrations::run();

start_secure_session((string)($config['session_name'] ?? 'arki_session'));
send_security_headers();

// Website accounts: resolve the signed-in customer/agent now (a "Remember me"
// sign-in may need to set a cookie, which must happen before any output).
UserAuth::user();

// House style: the public website never shows the long dash (—). Every public page
// is passed through this filter, so it also covers text typed in the CMS later.
// The CMS itself (/admin/) is left untouched.
if (!preg_match('#/admin(/|$)#', strtr((string)($_SERVER['SCRIPT_NAME'] ?? ''), chr(92), '/'))) {
    ob_start(static fn(string $html): string => str_replace(['—', '&mdash;', '&#8212;', '&#x2014;'], '-', $html));
}
