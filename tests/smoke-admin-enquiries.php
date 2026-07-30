<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/auth.php';

$admin = db()->query(
    "SELECT id, name, email, phone, role, status
     FROM users
     WHERE role = 'admin' AND status = 'active'
     LIMIT 1"
)->fetch();

if (!$admin) {
    throw new RuntimeException('No active admin is available for the enquiry inbox smoke test.');
}

$_SESSION['admin'] = $admin;
$_SERVER['PHP_SELF'] = '/admin/enquiries/index.php';

ob_start();
require BASE_PATH . '/admin/enquiries/index.php';
$html = (string) ob_get_clean();

if (!str_contains($html, 'Property Enquiries') || !str_contains($html, 'Lead Inbox')) {
    throw new RuntimeException('The admin enquiry inbox did not render correctly.');
}

echo 'Admin enquiry inbox smoke test passed.' . PHP_EOL;
