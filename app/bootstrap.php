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

Database::connect($config['db']);

require APP_PATH . '/Migrations.php';
Migrations::run();

start_secure_session((string)($config['session_name'] ?? 'arki_session'));
send_security_headers();
