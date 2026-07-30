<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$sessionPath = BASE_PATH . '/storage/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

session_save_path($sessionPath);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $documentRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $basePath = str_replace('\\', '/', dirname(__DIR__));
    $relativePath = '';

    if ($documentRoot !== '' && str_starts_with($basePath, $documentRoot)) {
        $relativePath = trim(substr($basePath, strlen($documentRoot)), '/');
    }

    return $scheme . '://' . $host . ($relativePath !== '' ? '/' . $relativePath : '');
}

define('APP_TIMEZONE', 'Asia/Kolkata');
define('APP_DB_TIMEZONE_OFFSET', '+05:30');
define('APP_HOST', detectAppHost());
define('APP_ENV', isLocalEnvironment(APP_HOST) ? 'local' : 'live');
define('IS_LOCAL', APP_ENV === 'local');
define('APP_NAME', 'GharSquare Admin');
define('APP_URL', detectAppUrl());
define('ADMIN_URL', APP_URL . '/admin');
define('UPLOAD_URL', APP_URL . '/uploads');

$privateMailConfig = [];
$privateMailConfigPath = BASE_PATH . '/config/private/mail.php';

if (is_file($privateMailConfigPath)) {
    $loadedMailConfig = require $privateMailConfigPath;
    $privateMailConfig = is_array($loadedMailConfig) ? $loadedMailConfig : [];
}

$mailConfigValue = static function (string $environmentKey, string $configKey, mixed $default = '') use ($privateMailConfig): mixed {
    $environmentValue = getenv($environmentKey);

    if ($environmentValue !== false && trim((string) $environmentValue) !== '') {
        return $environmentValue;
    }

    return $privateMailConfig[$configKey] ?? $default;
};

$configuredMailTransport = strtolower((string) $mailConfigValue('MAIL_TRANSPORT', 'transport', 'smtp'));
define('MAIL_TRANSPORT', IS_LOCAL ? 'log' : $configuredMailTransport);
define('MAIL_HOST', trim((string) $mailConfigValue('MAIL_HOST', 'host')));
define('MAIL_PORT', (int) $mailConfigValue('MAIL_PORT', 'port', 587));
define('MAIL_USERNAME', trim((string) $mailConfigValue('MAIL_USERNAME', 'username')));
define('MAIL_PASSWORD', (string) $mailConfigValue('MAIL_PASSWORD', 'password'));
define('MAIL_ENCRYPTION', strtolower((string) $mailConfigValue('MAIL_ENCRYPTION', 'encryption', 'tls')));
define('MAIL_FROM_ADDRESS', trim((string) $mailConfigValue('MAIL_FROM_ADDRESS', 'from_address', 'info@gharsquare.com')));
define('MAIL_FROM_NAME', trim((string) $mailConfigValue('MAIL_FROM_NAME', 'from_name', 'GharSquare')));
define('ADMIN_ENQUIRY_EMAIL', trim((string) $mailConfigValue('ADMIN_ENQUIRY_EMAIL', 'admin_enquiry_email', MAIL_FROM_ADDRESS)));

$privateMapsConfig = [];
$privateMapsConfigPath = BASE_PATH . '/config/private/maps.php';

if (is_file($privateMapsConfigPath)) {
    $loadedMapsConfig = require $privateMapsConfigPath;
    $privateMapsConfig = is_array($loadedMapsConfig) ? $loadedMapsConfig : [];
}

$databaseConfig = IS_LOCAL
    ? [
        'host' => '127.0.0.1',
        'name' => 'gharsquare',
        'user' => 'root',
        'pass' => '',
    ]
    : [
        'host' => getenv('LIVE_DB_HOST') ?: 'localhost',
        'name' => getenv('LIVE_DB_NAME') ?: 'u939086737_gharsquare2',
        'user' => getenv('LIVE_DB_USER') ?: 'u939086737_gharsquare2',
        'pass' => getenv('LIVE_DB_PASS') ?: '',
    ];

define('DB_HOST', $databaseConfig['host']);
define('DB_NAME', $databaseConfig['name']);
define('DB_USER', $databaseConfig['user']);
define('DB_PASS', $databaseConfig['pass']);


$googleMapsApiKey = getenv('GOOGLE_MAPS_API_KEY');
define(
    'GOOGLE_MAPS_API_KEY',
    trim((string) (
        $googleMapsApiKey !== false && trim((string) $googleMapsApiKey) !== ''
            ? $googleMapsApiKey
            : ($privateMapsConfig['api_key'] ?? '')
    ))
);

ini_set('date.timezone', APP_TIMEZONE);
putenv('TZ=' . APP_TIMEZONE);
date_default_timezone_set(APP_TIMEZONE);
