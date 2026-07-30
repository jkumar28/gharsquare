<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

$strict = in_array('--strict', $_SERVER['argv'] ?? [], true);
$failures = [];
$warnings = [];
$passed = [];

function preflightCheck(bool $condition, string $message, bool $warning = false): void
{
    global $failures, $warnings, $passed;

    if ($condition) {
        $passed[] = $message;
        return;
    }

    if ($warning) {
        $warnings[] = $message;
        return;
    }

    $failures[] = $message;
}

preflightCheck(version_compare(PHP_VERSION, '8.2.0', '>='), 'PHP 8.2 or newer');
foreach (['pdo_mysql', 'fileinfo', 'mbstring', 'openssl'] as $extension) {
    preflightCheck(extension_loaded($extension), 'PHP extension: ' . $extension);
}

preflightCheck(is_dir(BASE_PATH . '/vendor/phpmailer'), 'Composer dependencies installed');
preflightCheck(is_writable(BASE_PATH . '/storage'), 'Storage directory writable');
preflightCheck(is_writable(BASE_PATH . '/storage/sessions'), 'Session directory writable');
preflightCheck(is_writable(BASE_PATH . '/uploads/properties'), 'Property uploads directory writable');
preflightCheck(APP_ENV === 'live', 'APP_ENV is live', !$strict);
preflightCheck(str_starts_with(APP_URL, 'https://'), 'APP_URL uses HTTPS', !$strict);
preflightCheck(DB_NAME !== '' && DB_USER !== '', 'Production database credentials configured');
preflightCheck(MAIL_TRANSPORT === 'smtp', 'SMTP mail transport enabled', !$strict);
preflightCheck(
    MAIL_HOST !== '' && MAIL_USERNAME !== '' && MAIL_PASSWORD !== '',
    'SMTP credentials configured'
);
preflightCheck(filter_var(MAIL_FROM_ADDRESS, FILTER_VALIDATE_EMAIL) !== false, 'Valid sender email configured');
preflightCheck(filter_var(ADMIN_ENQUIRY_EMAIL, FILTER_VALIDATE_EMAIL) !== false, 'Valid enquiry inbox configured');
preflightCheck(GOOGLE_MAPS_API_KEY !== '', 'Google Maps API key configured', true);

try {
    db()->query('SELECT 1')->fetchColumn();
    $passed[] = 'Database connection';

    $activeAdmins = (int) db()->query(
        "SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'"
    )->fetchColumn();
    preflightCheck($activeAdmins > 0, 'At least one active admin account');

    $migrationFiles = array_map('basename', glob(BASE_PATH . '/database/migrations/*.sql') ?: []);
    $appliedFiles = [];
    $tableExists = (bool) db()->query("SHOW TABLES LIKE 'schema_migrations'")->fetchColumn();
    if ($tableExists) {
        $appliedFiles = db()->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    }
    $pending = array_diff($migrationFiles, $appliedFiles);
    preflightCheck($pending === [], 'All database migrations applied');
} catch (Throwable $exception) {
    $failures[] = 'Database readiness: ' . $exception->getMessage();
}

foreach ($passed as $message) {
    echo "[PASS] {$message}\n";
}
foreach ($warnings as $message) {
    echo "[WARN] {$message}\n";
}
foreach ($failures as $message) {
    echo "[FAIL] {$message}\n";
}

echo sprintf(
    "\nPreflight result: %d passed, %d warnings, %d failures.\n",
    count($passed),
    count($warnings),
    count($failures)
);

exit($failures === [] ? 0 : 1);
