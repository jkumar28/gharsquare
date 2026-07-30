<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/public_auth.php';

$owner = db()->query(
    "SELECT id, name, email, phone, role, status, email_verified
     FROM users
     WHERE id = (SELECT user_id FROM properties WHERE id = 7)
     LIMIT 1"
)->fetch();

if (!$owner) {
    throw new RuntimeException('A property owner is required for the dashboard smoke test.');
}

$_SESSION['user'] = $owner;
$_GET['view'] = ($argv[1] ?? '') === 'leads' ? 'leads' : 'dashboard';
$_SERVER['PHP_SELF'] = '/website/account.php';

ob_start();
require BASE_PATH . '/website/account.php';
$html = (string) ob_get_clean();

$expected = $_GET['view'] === 'leads' ? 'Property Leads' : 'Property Performance';
if (!str_contains($html, $expected)) {
    throw new RuntimeException('Owner dashboard view did not render: ' . $expected);
}

echo 'Owner ' . $_GET['view'] . ' smoke test passed.' . PHP_EOL;
