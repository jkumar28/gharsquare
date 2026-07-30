<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/public_auth.php';

$draft = db()->query(
    'SELECT d.id, u.id AS user_id, u.name, u.email, u.phone, u.role, u.status, u.email_verified
     FROM property_drafts d
     INNER JOIN users u ON u.id = d.user_id
     ORDER BY d.id
     LIMIT 1'
)->fetch();

if (!$draft) {
    throw new RuntimeException('A property draft is required for the fixed-country smoke test.');
}

$_SESSION['user'] = [
    'id' => (int) $draft['user_id'],
    'name' => (string) $draft['name'],
    'email' => (string) $draft['email'],
    'phone' => (string) ($draft['phone'] ?? ''),
    'role' => (string) $draft['role'],
    'status' => (string) $draft['status'],
    'email_verified' => (int) $draft['email_verified'],
];
$_GET['draft_id'] = (int) $draft['id'];
$_SERVER['REQUEST_URI'] = '/gharsquare/website/post-property?draft_id=' . (int) $draft['id'];

ob_start();
require BASE_PATH . '/website/post-property.php';
$html = (string) ob_get_clean();

if (!preg_match('/id="country_display"[^>]+value="India"[^>]+readonly/', $html)) {
    throw new RuntimeException('India is not displayed as the fixed public country.');
}

if (!preg_match('/name="country_id"[^>]+type="hidden"[^>]+value="1"/', $html)) {
    throw new RuntimeException('The fixed India country ID is not submitted.');
}

if (preg_match('/<select[^>]+name="country_id"/', $html)) {
    throw new RuntimeException('The public country field is still selectable.');
}

echo 'Fixed public country smoke test passed.' . PHP_EOL;
