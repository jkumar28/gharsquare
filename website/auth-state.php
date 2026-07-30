<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/public_site.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$redirect = publicAuthNormalizeRedirect((string) ($_GET['redirect'] ?? ''));
$user = publicAuthUserPayload();
$links = [
    ['label' => 'Dashboard', 'href' => siteWebsiteUrl('account')],
    ['label' => 'My Profile', 'href' => siteWebsiteUrl('account?view=profile')],
    ['label' => 'Settings', 'href' => siteWebsiteUrl('account?view=settings')],
    ['label' => 'My Properties', 'href' => siteWebsiteUrl('account?view=properties')],
];

if ($user && (in_array((string) ($user['role'] ?? ''), ['owner', 'agent', 'builder'], true) || publicUserPropertyDrafts(1) !== [])) {
    $links[] = ['label' => 'Property Leads', 'href' => siteWebsiteUrl('account?view=leads')];
}

$links = array_merge($links, [
    ['label' => 'Saved Properties', 'href' => siteWebsiteUrl('account?view=saved')],
    ['label' => 'My Enquiries', 'href' => siteWebsiteUrl('account?view=enquiries')],
    ['label' => 'My Activity', 'href' => siteWebsiteUrl('account?view=activity')],
    ['label' => 'Browse Listings', 'href' => siteListingUrl(['type' => 'buy'])],
    ['label' => 'Post Property', 'href' => siteWebsiteUrl('post-property')],
]);

jsonResponse([
    'logged_in' => $user !== null,
    'user' => $user,
    'login_url' => publicAuthLoginUrl($redirect),
    'logout_url' => siteWebsiteUrl('logout'),
    'links' => $links,
]);
