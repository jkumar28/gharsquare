<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/public_site.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$redirect = publicAuthNormalizeRedirect((string) ($_GET['redirect'] ?? ''));
$user = publicAuthUserPayload();
$links = [
    ['label' => 'Dashboard', 'href' => 'account'],
    ['label' => 'My Profile', 'href' => 'account?view=profile'],
    ['label' => 'Settings', 'href' => 'account?view=settings'],
    ['label' => 'My Properties', 'href' => 'account?view=properties'],
];

if ($user && (in_array((string) ($user['role'] ?? ''), ['owner', 'agent', 'builder'], true) || publicUserPropertyDrafts(1) !== [])) {
    $links[] = ['label' => 'Property Leads', 'href' => 'account?view=leads'];
}

$links = array_merge($links, [
    ['label' => 'Saved Properties', 'href' => 'account?view=saved'],
    ['label' => 'My Enquiries', 'href' => 'account?view=enquiries'],
    ['label' => 'My Activity', 'href' => 'account?view=activity'],
    ['label' => 'Browse Listings', 'href' => siteListingUrl(['type' => 'buy'])],
    ['label' => 'Post Property', 'href' => 'post-property'],
]);

jsonResponse([
    'logged_in' => $user !== null,
    'user' => $user,
    'login_url' => publicAuthLoginUrl($redirect),
    'logout_url' => 'logout',
    'links' => $links,
]);
