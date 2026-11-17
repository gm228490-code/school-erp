<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('APP_VERSION', '1.0.0');

$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    header('Location: install/index.php');
    exit;
}
$CONFIG = require $configFile;
$GLOBALS['CONFIG'] = $CONFIG;

date_default_timezone_set($CONFIG['timezone'] ?? 'Asia/Karachi');
error_reporting(E_ALL);
ini_set('display_errors', !empty($CONFIG['debug']) ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/error.log');

require APP_PATH . '/core/DB.php';
require APP_PATH . '/core/helpers.php';
require APP_PATH . '/core/Auth.php';
require APP_PATH . '/core/Crud.php';
require APP_PATH . '/core/form.php';
require APP_PATH . '/core/services.php';

// Controllers can reference each other (e.g. PortalController::studentIds())
spl_autoload_register(function (string $class): void {
    if (preg_match('/^[A-Z][A-Za-z]+Controller$/', $class) && is_file($f = APP_PATH . '/controllers/' . $class . '.php')) {
        require_once $f;
    }
});

// ---- Secure session ----
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '7200');
session_name('SCHOOLERP');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
if (PHP_SAPI !== 'cli') {
    session_start();
}

// ---- Security headers ----
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; frame-ancestors 'self'; form-action 'self'");
}

DB::connect($CONFIG['db']);
