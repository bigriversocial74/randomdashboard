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
if ($appMarker !== false) {
    $rootUrl = substr($scriptName, 0, $appMarker);
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

    $loaded = $GLOBALS['gruber_raw_config'] ?? [];
    $config = is_array($loaded) ? array_replace_recursive($defaults, $loaded) : $defaults;
    return $config;
}

function config_present(): bool
{
    return is_file(PROJECT_ROOT . '/config.php');
}

function config_validation_errors(): array
{
    if (!config_present()) return ['config.php is absent.'];

    $config = app_config();
    $errors = [];
    $environment = strtolower(trim((string) ($config['app']['environment'] ?? '')));
    if (!in_array($environment, ['production', 'staging', 'development', 'testing'], true)) {
        $errors[] = 'The application environment is invalid.';
    }

    $timezone = (string) ($config['app']['timezone'] ?? '');
    if (!in_array($timezone, timezone_identifiers_list(), true)) {
        $errors[] = 'The configured timezone is invalid.';
    }
    $supportEmail = trim((string) ($config['app']['support_email'] ?? ''));
    if (!filter_var($supportEmail, FILTER_VALIDATE_EMAIL) || str_ends_with(strtolower($supportEmail), '@example.com')) {
        $errors[] = 'The support email is not configured.';
    }

    $db = $config['database'] ?? [];
    foreach (['host', 'name', 'username'] as $key) {
        $value = trim((string) ($db[$key] ?? ''));
        if ($value === '' || str_starts_with($value, 'YOUR_')) {
            $errors[] = "The database {$key} is not configured.";
        }
    }
    $port = (int) ($db['port'] ?? 0);
    if ($port < 1 || $port > 65535) {
        $errors[] = 'The database port is invalid.';
    }
    if (!in_array((string) ($db['charset'] ?? ''), ['utf8mb4', 'utf8'], true)) {
        $errors[] = 'The database charset must be utf8mb4 or utf8.';
    }

    $security = $config['security'] ?? [];
    if ((int) ($security['session_lifetime_minutes'] ?? 0) < 5) {
        $errors[] = 'The session lifetime must be at least five minutes.';
    }
    if ((int) ($security['session_absolute_lifetime_minutes'] ?? 0) < (int) ($security['session_lifetime_minutes'] ?? 0)) {
        $errors[] = 'The absolute session lifetime must not be shorter than the idle session lifetime.';
    }
    if ((int) ($security['max_login_attempts'] ?? 0) < 1) {
        $errors[] = 'The login attempt limit must be at least one.';
    }

    return array_values(array_unique($errors));
}

function config_valid(): bool
{
    return config_validation_errors() === [];
}

function production_database_connection(): ?PDO
{
    static $attempted = false;
    static $pdo = null;
    if ($attempted) return $pdo;
    $attempted = true;
    if (!config_valid()) {
        $_SESSION['gruber_db_error'] = implode(' ', config_validation_errors());
        return null;
    }

    $db = app_config()['database'];
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) $db['host'],
            (int) $db['port'],
            (string) $db['name'],
            (string) ($db['charset'] ?? 'utf8mb4')
        );
        $pdo = new PDO($dsn, (string) $db['username'], (string) $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $exception) {
        $_SESSION['gruber_db_error'] = $exception->getMessage();
        $pdo = null;
    }
    return $pdo;
}

function production_database_available(): bool
{
    return production_database_connection() instanceof PDO;
}

function database_connection(): ?PDO
{
    if (demo_mode_active()) return null;
    return production_database_connection();
}

function database_available(): bool
{
    return database_connection() instanceof PDO;
}

function environment_snapshot(): array
{
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'session', 'fileinfo', 'simplexml', 'zip'];
    $extensions = [];
    foreach ($requiredExtensions as $extension) {
        $extensions[$extension] = extension_loaded($extension);
    }

    $storagePath = PROJECT_ROOT . '/storage/demo';
    $importStoragePath = PROJECT_ROOT . '/storage/imports';
    $productionAvailable = production_database_available();
    return [
        'mode' => demo_mode_active() ? 'Demo' : 'Production',
        'database' => $productionAvailable ? (demo_mode_active() ? 'Connected / bypassed' : 'Connected') : 'Unavailable',
        'config' => config_present() ? (config_valid() ? 'Present / valid' : 'Present / invalid') : 'Absent',
        'config_errors' => config_validation_errors(),
        'schema_version' => production_schema_version(),
        'php_version' => PHP_VERSION,
        'extensions' => $extensions,
        'storage_writable' => is_dir($storagePath) && is_writable($storagePath),
        'import_storage_writable' => is_dir($importStoragePath) && is_writable($importStoragePath),
        'email_queue' => 'Not configured',
        'maintenance' => 'Inactive',
        'setup' => is_file(PROJECT_ROOT . '/storage/manual-setup.lock') ? 'Manual setup locked' : 'Manual setup not locked',
        'last_health_check' => date('Y-m-d H:i:s'),
        'database_error' => $_SESSION['gruber_db_error'] ?? null,
    ];
}
