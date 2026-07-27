<?php
declare(strict_types=1);

if (defined('GRUBER_BOOTSTRAPPED')) {
    return;
}
define('GRUBER_BOOTSTRAPPED', true);

$projectRoot = dirname(__DIR__, 2);
define('PROJECT_ROOT', $projectRoot);

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/app/index.php'));
$appMarker = strpos($scriptName, '/app/');
$portalMarker = strpos($scriptName, '/supplier-portal/');
if ($appMarker !== false) {
    $rootUrl = substr($scriptName, 0, $appMarker);
    $appUrl = $rootUrl . '/app';
} elseif ($portalMarker !== false) {
    $rootUrl = substr($scriptName, 0, $portalMarker);
    $appUrl = $rootUrl . '/app';
} elseif (str_ends_with($scriptName, '/app')) {
    $rootUrl = substr($scriptName, 0, -4);
    $appUrl = $scriptName;
} else {
    $rootUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $appUrl = $rootUrl . '/app';
}
define('ROOT_URL', rtrim($rootUrl, '/'));
define('APP_URL', rtrim($appUrl, '/'));

$configPath = PROJECT_ROOT . '/config.php';
$rawConfig = [];
if (is_file($configPath)) {
    $loadedConfig = require $configPath;
    if (is_array($loadedConfig)) {
        $rawConfig = $loadedConfig;
    }
}
$GLOBALS['gruber_raw_config'] = $rawConfig;

$remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$trustedProxies = array_values(array_filter(array_map('strval', $rawConfig['security']['trusted_proxies'] ?? [])));
$forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
$isDirectHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
$isTrustedForwardedHttps = $remoteAddress !== ''
    && in_array($remoteAddress, $trustedProxies, true)
    && $forwardedProto === 'https';
$isHttps = $isDirectHttps || $isTrustedForwardedHttps;

define('GRUBER_REQUEST_HTTPS', $isHttps);

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_name('gruber_procurement');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => ROOT_URL !== '' ? ROOT_URL . '/' : '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$timezone = (string) ($rawConfig['app']['timezone'] ?? 'America/Phoenix');
if (!in_array($timezone, timezone_identifiers_list(), true)) {
    $timezone = 'America/Phoenix';
}
date_default_timezone_set($timezone);

require_once __DIR__ . '/functions.php';
require_once dirname(__DIR__) . '/demo/defaults.php';
require_once dirname(__DIR__) . '/demo/store.php';
require_once dirname(__DIR__) . '/admin/permissions.php';
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/data/mysql_repository.php';
require_once dirname(__DIR__) . '/data/repository.php';
touch_production_session();
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/components.php';

function app_config(): array
{
    static $config;
    if (is_array($config)) {
        return $config;
    }

    $defaults = [
        'app' => [
            'name' => 'Gruber Procurement Intelligence',
            'environment' => 'production',
            'timezone' => 'America/Phoenix',
            'support_email' => 'support@example.com',
            'demo_enabled' => true,
            'import_storage_path' => PROJECT_ROOT . '/storage/imports',
        ],
        'database' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'name' => '',
            'username' => '',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
        'security' => [
            'session_lifetime_minutes' => 120,
            'session_absolute_lifetime_minutes' => 720,
            'password_reset_lifetime_minutes' => 60,
            'max_login_attempts' => 5,
            'lockout_minutes' => 15,
            'trusted_proxies' => [],
        ],
    ];

    $config = array_replace_recursive($defaults, $GLOBALS['gruber_raw_config'] ?? []);
    return $config;
}
