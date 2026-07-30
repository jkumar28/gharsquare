<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

function loadPrivateConfig(string $name): array
{
    $path = BASE_PATH . '/config/private/' . $name . '.php';

    if (!is_file($path)) {
        return [];
    }

    $loaded = require $path;

    return is_array($loaded) ? $loaded : [];
}

function environmentValue(array|string $keys): ?string
{
    foreach ((array) $keys as $key) {
        $value = getenv($key);

        if ($value !== false && trim((string) $value) !== '') {
            return trim((string) $value);
        }
    }

    return null;
}

function configuredValue(
    array|string $environmentKeys,
    array $privateConfig,
    string $configKey,
    mixed $default = ''
): mixed {
    $environmentValue = environmentValue($environmentKeys);

    return $environmentValue ?? ($privateConfig[$configKey] ?? $default);
}

function configuredBool(
    array|string $environmentKeys,
    array $privateConfig,
    string $configKey,
    bool $default
): bool {
    $value = configuredValue($environmentKeys, $privateConfig, $configKey, $default);

    if (is_bool($value)) {
        return $value;
    }

    return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
}

function detectAppHost(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

    return strtolower((string) preg_replace('/:\d+$/', '', (string) $host));
}

function isLocalEnvironment(string $host): bool
{
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }

    return str_ends_with($host, '.local') || str_ends_with($host, '.test');
}

function detectAppUrl(): string
{
    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $scheme = $forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $documentRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $basePath = str_replace('\\', '/', BASE_PATH);
    $relativePath = '';

    if ($documentRoot !== '' && str_starts_with($basePath, $documentRoot)) {
        $relativePath = trim(substr($basePath, strlen($documentRoot)), '/');
    }

    return $scheme . '://' . $host . ($relativePath !== '' ? '/' . $relativePath : '');
}

$privateAppConfig = loadPrivateConfig('app');
$detectedHost = detectAppHost();
$configuredEnvironment = strtolower((string) configuredValue('APP_ENV', $privateAppConfig, 'environment', ''));
$appEnvironment = in_array($configuredEnvironment, ['local', 'live'], true)
    ? $configuredEnvironment
    : (isLocalEnvironment($detectedHost) ? 'local' : 'live');
$configuredAppUrl = trim((string) configuredValue('APP_URL', $privateAppConfig, 'url', ''));
$appTimezone = trim((string) configuredValue('APP_TIMEZONE', $privateAppConfig, 'timezone', 'Asia/Kolkata'));
$databaseTimezoneOffset = trim((string) configuredValue(
    'APP_DB_TIMEZONE_OFFSET',
    $privateAppConfig,
    'database_timezone_offset',
    '+05:30'
));

define('APP_HOST', $detectedHost);
define('APP_ENV', $appEnvironment);
define('IS_LOCAL', APP_ENV === 'local');
define('APP_NAME', trim((string) configuredValue('APP_NAME', $privateAppConfig, 'name', 'GharSquare Admin')));
define('APP_URL', rtrim($configuredAppUrl !== '' ? $configuredAppUrl : detectAppUrl(), '/'));
define('APP_TIMEZONE', $appTimezone !== '' ? $appTimezone : 'Asia/Kolkata');
define('APP_DB_TIMEZONE_OFFSET', $databaseTimezoneOffset !== '' ? $databaseTimezoneOffset : '+05:30');
define('ADMIN_URL', APP_URL . '/admin');
define('UPLOAD_URL', APP_URL . '/uploads');
define('CONTACT_EMAIL', trim((string) configuredValue(
    'CONTACT_EMAIL',
    $privateAppConfig,
    'contact_email',
    'info@gharsquare.com'
)));

ini_set('date.timezone', APP_TIMEZONE);
putenv('TZ=' . APP_TIMEZONE);
date_default_timezone_set(APP_TIMEZONE);

$sessionPath = BASE_PATH . '/storage/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
}

$requestIsHttps = str_starts_with(APP_URL, 'https://');
$secureSessionCookie = configuredBool(
    'SESSION_COOKIE_SECURE',
    $privateAppConfig,
    'session_cookie_secure',
    $requestIsHttps
);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_save_path($sessionPath);
session_name('gharsquare_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureSessionCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$privateMailConfig = loadPrivateConfig('mail');
$configuredMailTransport = strtolower((string) configuredValue(
    'MAIL_TRANSPORT',
    $privateMailConfig,
    'transport',
    'smtp'
));

define('MAIL_TRANSPORT', IS_LOCAL ? 'log' : $configuredMailTransport);
define('MAIL_HOST', trim((string) configuredValue('MAIL_HOST', $privateMailConfig, 'host')));
define('MAIL_PORT', (int) configuredValue('MAIL_PORT', $privateMailConfig, 'port', 587));
define('MAIL_USERNAME', trim((string) configuredValue('MAIL_USERNAME', $privateMailConfig, 'username')));
define('MAIL_PASSWORD', (string) configuredValue('MAIL_PASSWORD', $privateMailConfig, 'password'));
define('MAIL_ENCRYPTION', strtolower((string) configuredValue(
    'MAIL_ENCRYPTION',
    $privateMailConfig,
    'encryption',
    'tls'
)));
define('MAIL_FROM_ADDRESS', trim((string) configuredValue(
    'MAIL_FROM_ADDRESS',
    $privateMailConfig,
    'from_address',
    CONTACT_EMAIL
)));
define('MAIL_FROM_NAME', trim((string) configuredValue(
    'MAIL_FROM_NAME',
    $privateMailConfig,
    'from_name',
    'GharSquare'
)));
define('ADMIN_ENQUIRY_EMAIL', trim((string) configuredValue(
    'ADMIN_ENQUIRY_EMAIL',
    $privateMailConfig,
    'admin_enquiry_email',
    MAIL_FROM_ADDRESS
)));

$privateDatabaseConfig = loadPrivateConfig('database');
$databasePrefix = IS_LOCAL ? 'LOCAL_DB_' : 'LIVE_DB_';
$databaseDefaults = IS_LOCAL
    ? ['host' => '127.0.0.1', 'name' => 'gharsquare', 'user' => 'root', 'pass' => '']
    : ['host' => 'localhost', 'name' => '', 'user' => '', 'pass' => ''];

$databaseConfig = [
    'host' => configuredValue(['DB_HOST', $databasePrefix . 'HOST'], $privateDatabaseConfig, 'host', $databaseDefaults['host']),
    'name' => configuredValue(['DB_NAME', $databasePrefix . 'NAME'], $privateDatabaseConfig, 'name', $databaseDefaults['name']),
    'user' => configuredValue(['DB_USER', $databasePrefix . 'USER'], $privateDatabaseConfig, 'user', $databaseDefaults['user']),
    'pass' => configuredValue(['DB_PASS', $databasePrefix . 'PASS'], $privateDatabaseConfig, 'pass', $databaseDefaults['pass']),
];

define('DB_HOST', trim((string) $databaseConfig['host']));
define('DB_NAME', trim((string) $databaseConfig['name']));
define('DB_USER', trim((string) $databaseConfig['user']));
define('DB_PASS', (string) $databaseConfig['pass']);

$privateMapsConfig = loadPrivateConfig('maps');
define('GOOGLE_MAPS_API_KEY', trim((string) configuredValue(
    'GOOGLE_MAPS_API_KEY',
    $privateMapsConfig,
    'api_key'
)));
