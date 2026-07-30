<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/includes/public_site.php';

$route = trim((string) ($_GET['route'] ?? 'index'), '/');
$route = preg_replace('/\.(?:php|html)$/i', '', $route) ?? $route;
$query = $_GET;
unset($query['route']);

if ($route === '' || $route === 'index') {
    $target = siteWebsiteUrl();
} elseif ($route === 'listing') {
    $target = siteListingUrl($query);
    $query = [];
} elseif ($route === 'property-details' && !empty($query['slug'])) {
    $target = APP_URL . '/property/' . rawurlencode((string) $query['slug']);
    $query = [];
} else {
    $target = siteWebsiteUrl($route);
}

if ($query !== []) {
    $target .= (str_contains($target, '?') ? '&' : '?') . http_build_query($query);
}

header('Location: ' . $target, true, 301);
exit;
