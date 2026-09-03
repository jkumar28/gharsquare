<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/public_site.php';

if (!isPublicUserLoggedIn()) {
    redirect(publicAuthLoginUrl(publicAuthCurrentUrl()));
}

$user = publicAuthUserPayload();
$view = (string) ($_GET['view'] ?? 'dashboard');
$allowedViews = ['dashboard', 'profile', 'settings', 'properties', 'leads', 'saved', 'enquiries', 'activity'];

if (!in_array($view, $allowedViews, true)) {
    $view = 'dashboard';
}

if ($view === 'settings' && isPostRequest()) {
    $redirectToSettings = static function (): void {
        redirect('account?view=settings');
    };

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Your session expired. Please try again.');
        $redirectToSettings();
    }

    $action = (string) ($_POST['settings_action'] ?? '');
    $userId = (int) ($user['id'] ?? 0);

    try {
        if ($action === 'profile') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $phone = preg_replace('/[\s()-]+/', '', trim((string) ($_POST['phone'] ?? ''))) ?? '';

            if (stringLength($name) < 2 || stringLength($name) > 150) {
                throw new InvalidArgumentException('Enter a valid name between 2 and 150 characters.');
            }
            if ($phone !== '' && !preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
                throw new InvalidArgumentException('Enter a valid phone number with 10 to 15 digits.');
            }

            $duplicate = db()->prepare('SELECT COUNT(*) FROM users WHERE phone = :phone AND id != :id');
            $duplicate->execute([':phone' => $phone !== '' ? $phone : null, ':id' => $userId]);
            if ($phone !== '' && (int) $duplicate->fetchColumn() > 0) {
                throw new InvalidArgumentException('This phone number is already linked to another account.');
            }

            $stmt = db()->prepare('UPDATE users SET name = :name, phone = :phone WHERE id = :id');
            $stmt->execute([':name' => $name, ':phone' => $phone !== '' ? $phone : null, ':id' => $userId]);
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = $phone;
            setFlash('success', 'Profile details updated successfully.');
        } elseif ($action === 'preferences') {
            savePublicUserSettings($userId, $_POST);
            setFlash('success', 'Your preferences have been saved.');
        } elseif ($action === 'password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');
            $stmt = db()->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $hash = (string) $stmt->fetchColumn();

            if ($current === '' || !password_verify($current, $hash)) {
                throw new InvalidArgumentException('Current password is incorrect.');
            }
            if (stringLength($new) < 8) {
                throw new InvalidArgumentException('New password must contain at least 8 characters.');
            }
            if ($new !== $confirm) {
                throw new InvalidArgumentException('New password and confirmation do not match.');
            }
            if (password_verify($new, $hash)) {
                throw new InvalidArgumentException('Choose a password different from your current password.');
            }

            $stmt = db()->prepare('UPDATE users SET password = :password WHERE id = :id');
            $stmt->execute([':password' => password_hash($new, PASSWORD_DEFAULT), ':id' => $userId]);
            session_regenerate_id(true);
            setFlash('success', 'Password changed successfully.');
        } else {
            throw new InvalidArgumentException('Invalid settings request.');
        }
    } catch (Throwable $exception) {
        setFlash('danger', $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Unable to save settings right now. Please try again.');
    }

    $redirectToSettings();
}

$settings = publicUserSettings((int) ($user['id'] ?? 0));

$activity = publicUserRecentActivity(40);
$savedProperties = publicUserSavedProperties(60);
$enquiries = publicUserEnquiries(60);
$propertyDrafts = publicUserPropertyDrafts(80);
$ownerLeads = publicOwnerEnquiries(100);
$ownerLeadSummary = publicOwnerLeadSummary();
$savedCount = publicUserSavedPropertyCount();
$enquiryCount = publicUserEnquiryCount();
$canReceiveLeads = $propertyDrafts !== [] || in_array((string) ($user['role'] ?? ''), ['owner', 'agent', 'builder'], true);
$flash = getFlash();

function accountViewTitle(string $view): string
{
    return [
        'dashboard' => 'Dashboard',
        'profile' => 'My Profile',
        'settings' => 'Settings',
        'properties' => 'My Properties',
        'leads' => 'Property Leads',
        'saved' => 'Saved Properties',
        'enquiries' => 'My Enquiries',
        'activity' => 'My Activity',
    ][$view] ?? 'Dashboard';
}

function accountActivityLabel(array $item): string
{
    $type = str_replace('_', ' ', (string) ($item['activity_type'] ?? 'interaction'));
    $label = ucwords($type);
    $query = trim((string) ($item['search_query'] ?? ''));
    $title = trim((string) ($item['page_title'] ?? ''));

    if ($query !== '') {
        return $label . ': ' . $query;
    }

    if ($title !== '') {
        return $label . ': ' . $title;
    }

    return $label;
}

function accountPropertyUrl(array $item): string
{
    $url = trim((string) ($item['details_url'] ?? ''));

    if ($url !== '') {
        return $url;
    }

    $query = http_build_query([
        'id' => (string) ($item['property_ref'] ?? ''),
        'type' => (string) (($item['listing_type'] ?? '') ?: 'buy'),
        'city' => (string) (($item['city'] ?? '') ?: 'Ranchi'),
    ]);

    return siteWebsiteUrl('properties') . '?' . $query;
}

function accountPropertyMeta(array $item): string
{
    $parts = array_filter([
        (string) ($item['locality'] ?? ''),
        (string) ($item['city'] ?? ''),
        (string) ($item['category'] ?? ''),
    ]);

    return implode(' | ', $parts);
}

function accountEnquiryStatusLabel(string $status): string
{
    return [
        'new' => 'New',
        'contacted' => 'Contacted',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ][$status] ?? ucfirst($status);
}

function accountDraftStatusLabel(array $draft): string
{
    if (!empty($draft['is_submitted'])) {
        $status = trim((string) ($draft['property_status'] ?? ''));

        return $status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'Pending Review';
    }

    return 'Draft';
}

function accountDraftMeta(array $draft): string
{
    $postedBy = trim((string) ($draft['posted_by'] ?? ''));
    $parts = array_filter([
        (string) ($draft['listing_type_name'] ?? ''),
        (string) ($draft['property_type_name'] ?? ''),
        $postedBy !== '' ? 'Posted by ' . ucfirst($postedBy) : '',
    ]);

    return implode(' | ', $parts);
}

$nav = [
    'dashboard' => ['Dashboard', 'bi-speedometer2'],
    'profile' => ['My Profile', 'bi-person-circle'],
    'settings' => ['Settings', 'bi-gear'],
    'properties' => ['My Properties', 'bi-house-door'],
    'leads' => ['Property Leads', 'bi-person-lines-fill'],
    'saved' => ['Saved Properties', 'bi-heart'],
    'enquiries' => ['My Enquiries', 'bi-send'],
    'activity' => ['My Activity', 'bi-activity'],
];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="app-url" content="<?= e(APP_URL) ?>">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e(accountViewTitle($view)) ?> - GharSquare</title>
    <meta name="description" content="Manage your GharSquare profile, properties, enquiries and activity.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(APP_URL) ?>/website/assets/css/style.css?v=<?= e((string) filemtime(__DIR__ . '/assets/css/style.css')) ?>" rel="stylesheet">
</head>

<body class="account-page">
    <nav class="navbar navbar-expand-lg fixed-top premium-navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="./" aria-label="GharSquare home">
                <span class="logo-icon"><i class="bi bi-house-fill"></i></span>
                Ghar<span>Square</span>
            </a>
            <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="mobile-header-actions" aria-label="Account shortcuts">
                <a href="<?= e(siteWebsiteUrl()) ?>" aria-label="Home"><i class="bi bi-house-door"></i></a>
                <a href="logout" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></a>
            </div>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto gap-lg-4">
                    <li><a class="nav-link" href="./">Home</a></li>
                    <li><a class="nav-link" href="<?= e(siteListingUrl(['type' => 'buy'])) ?>">Buyers</a></li>
                    <li><a class="nav-link" href="<?= e(siteListingUrl(['type' => 'rent'])) ?>">Tenants</a></li>
                    <li><a class="nav-link" href="./#owner-cta">Owners</a></li>
                </ul>
                <a href="logout" class="btn btn-primary"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
    </nav>

    <main class="account-main">
        <section class="account-shell">
            <aside class="account-sidebar">
                <div class="account-user-card">
                    <span class="account-avatar-lg"><?= e((string) ($user['initials'] ?? 'U')) ?></span>
                    <h1><?= e((string) ($user['name'] ?? 'User')) ?></h1>
                    <p><?= e((string) ($user['email'] ?? '')) ?></p>
                    <span><?= e((string) ($user['role_label'] ?? 'Customer')) ?></span>
                </div>

                <nav class="account-nav">
                    <?php foreach ($nav as $key => [$label, $icon]): ?>
                        <?php if ($key === 'leads' && !$canReceiveLeads) { continue; } ?>
                        <a class="<?= $view === $key ? 'active' : '' ?>" href="account<?= $key === 'dashboard' ? '' : '?view=' . e($key) ?>">
                            <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <section class="account-content">
                <div class="account-heading">
                    <div>
                        <span class="auth-kicker">Account</span>
                        <h2><?= e(accountViewTitle($view)) ?></h2>
                    </div>
                    <a href="<?= e(siteListingUrl(['type' => 'buy'])) ?>">Browse Listings</a>
                </div>

                <?php if ($flash): ?>
                    <div class="auth-alert <?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
                <?php endif; ?>

                <?php if ($view === 'dashboard'): ?>
                    <?php if ($canReceiveLeads): ?>
                        <div class="account-stats">
                            <article><strong><?= e((string) count($propertyDrafts)) ?></strong><span>My properties</span></article>
                            <article><strong><?= e((string) $ownerLeadSummary['views']) ?></strong><span>Property views</span></article>
                            <article><strong><?= e((string) $ownerLeadSummary['leads']) ?></strong><span>Received leads</span></article>
                            <article><strong><?= e((string) $ownerLeadSummary['new_leads']) ?></strong><span>New leads</span></article>
                        </div>
                        <div class="account-panel owner-dashboard-panel">
                            <div class="account-panel-heading">
                                <div>
                                    <h3>Property Performance</h3>
                                    <p class="account-muted">Views are deduplicated per visitor session. Your own property views are excluded.</p>
                                </div>
                                <a class="account-action" href="account?view=leads">View Leads</a>
                            </div>
                            <?php if ($propertyDrafts): ?>
                                <div class="owner-property-performance">
                                    <?php foreach ($propertyDrafts as $draft): ?>
                                        <article>
                                            <div>
                                                <span class="account-tag"><?= e(accountDraftStatusLabel($draft)) ?></span>
                                                <h4><?= e((string) (($draft['title'] ?? '') ?: 'Untitled property')) ?></h4>
                                            </div>
                                            <div class="property-performance-metrics">
                                                <span><strong><?= e((string) ($draft['property_view_count'] ?? 0)) ?></strong> Views</span>
                                                <span><strong><?= e((string) ($draft['property_lead_count'] ?? 0)) ?></strong> Enquiries</span>
                                                <span><strong><?= e((string) ($draft['property_new_lead_count'] ?? 0)) ?></strong> New</span>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="account-stats">
                            <article><strong><?= e((string) count($activity)) ?></strong><span>Recent activities</span></article>
                            <article><strong><?= e((string) $savedCount) ?></strong><span>Saved properties</span></article>
                            <article><strong><?= e((string) $enquiryCount) ?></strong><span>Enquiries sent</span></article>
                            <article><strong><?= !empty($user['email_verified']) ? 'Yes' : 'No' ?></strong><span>Email verified</span></article>
                        </div>
                    <?php endif; ?>
                    <div class="account-panel">
                        <h3>Recent Activity</h3>
                        <?php if ($activity): ?>
                            <div class="activity-list">
                                <?php foreach (array_slice($activity, 0, 8) as $item): ?>
                                    <div class="activity-item">
                                        <i class="bi bi-dot"></i>
                                        <div>
                                            <strong><?= e(accountActivityLabel($item)) ?></strong>
                                            <span><?= e((string) ($item['city'] ?: $item['listing_type'] ?: 'Website')) ?> | <?= e((string) $item['created_at']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="account-muted">No activity recorded yet. Browse or search properties to build your recommendations.</p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($view === 'profile'): ?>
                    <div class="account-panel">
                        <h3>Profile Details</h3>
                        <div class="profile-grid">
                            <div><span>Name</span><strong><?= e((string) ($user['name'] ?? '')) ?></strong></div>
                            <div><span>Email</span><strong><?= e((string) ($user['email'] ?? '')) ?></strong></div>
                            <div><span>Phone</span><strong><?= e((string) (($user['phone'] ?? '') ?: 'Not added')) ?></strong></div>
                            <div><span>Account Type</span><strong><?= e((string) ($user['role_label'] ?? 'Customer')) ?></strong></div>
                        </div>
                    </div>
                <?php elseif ($view === 'settings'): ?>
                    <div class="settings-intro">
                        <span class="settings-intro-icon"><i class="bi bi-sliders"></i></span>
                        <div><h3>Account settings</h3><p>Keep your contact details, alerts and account security up to date.</p></div>
                    </div>

                    <form class="account-panel settings-card" method="post" action="account?view=settings">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="settings_action" value="profile">
                        <div class="settings-card-heading"><span><i class="bi bi-person"></i></span><div><h3>Personal details</h3><p>Used for your account and property enquiries.</p></div></div>
                        <div class="settings-form-grid">
                            <label><span>Full name</span><input type="text" name="name" value="<?= e((string) ($user['name'] ?? '')) ?>" maxlength="150" required></label>
                            <label><span>Phone number</span><input type="tel" name="phone" value="<?= e((string) ($user['phone'] ?? '')) ?>" inputmode="tel" placeholder="e.g. 9876543210"></label>
                            <label class="settings-field-wide"><span>Email address</span><div class="settings-readonly"><span><?= e((string) ($user['email'] ?? '')) ?></span><?php if (!empty($user['email_verified'])): ?><em><i class="bi bi-patch-check-fill"></i> Verified</em><?php endif; ?></div><small>Email changes require verification and are handled by support.</small></label>
                        </div>
                        <div class="settings-form-actions"><button type="submit">Save profile</button></div>
                    </form>

                    <form class="account-panel settings-card" method="post" action="account?view=settings">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="settings_action" value="preferences">
                        <div class="settings-card-heading"><span><i class="bi bi-bell"></i></span><div><h3>Alerts & privacy</h3><p>Choose how GharSquare keeps you informed.</p></div></div>
                        <fieldset class="settings-contact-choice"><legend>Preferred contact method</legend><div>
                            <?php foreach (['call' => ['bi-telephone', 'Call'], 'whatsapp' => ['bi-whatsapp', 'WhatsApp'], 'email' => ['bi-envelope', 'Email']] as $key => [$icon, $label]): ?>
                                <label><input type="radio" name="preferred_contact" value="<?= e($key) ?>" <?= ($settings['preferred_contact'] ?? 'call') === $key ? 'checked' : '' ?>><span><i class="bi <?= e($icon) ?>"></i><?= e($label) ?></span></label>
                            <?php endforeach; ?>
                        </div></fieldset>
                        <div class="settings-toggle-list">
                            <?php foreach ([
                                'enquiry_updates' => ['Enquiry updates', 'Replies and status changes for enquiries you send.', 'bi-chat-left-text'],
                                'listing_updates' => ['Property updates', 'Important updates for your posted properties.', 'bi-house-check'],
                                'saved_search_alerts' => ['Saved search alerts', 'Notify me when matching properties become available.', 'bi-search-heart'],
                                'marketing_updates' => ['Offers & tips', 'Occasional product news, market insights and offers.', 'bi-megaphone'],
                                'activity_personalization' => ['Personalised experience', 'Use browsing activity to improve recommendations.', 'bi-stars'],
                            ] as $key => [$title, $description, $icon]): ?>
                                <label class="settings-toggle-row"><i class="bi <?= e($icon) ?>"></i><span><strong><?= e($title) ?></strong><small><?= e($description) ?></small></span><input type="checkbox" name="<?= e($key) ?>" value="1" <?= !empty($settings[$key]) ? 'checked' : '' ?>><em aria-hidden="true"></em></label>
                            <?php endforeach; ?>
                        </div>
                        <div class="settings-form-actions"><button type="submit">Save preferences</button></div>
                    </form>

                    <form class="account-panel settings-card" method="post" action="account?view=settings">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="settings_action" value="password">
                        <div class="settings-card-heading"><span><i class="bi bi-shield-lock"></i></span><div><h3>Password & security</h3><p>Use at least 8 characters and never reuse an old password.</p></div></div>
                        <div class="settings-form-grid settings-password-grid">
                            <label><span>Current password</span><span class="password-input-wrap"><input type="password" name="current_password" autocomplete="current-password" required><button type="button" data-password-toggle aria-label="Show password"><i class="bi bi-eye"></i></button></span></label>
                            <label><span>New password</span><span class="password-input-wrap"><input type="password" name="new_password" minlength="8" autocomplete="new-password" required><button type="button" data-password-toggle aria-label="Show password"><i class="bi bi-eye"></i></button></span></label>
                            <label><span>Confirm new password</span><span class="password-input-wrap"><input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required><button type="button" data-password-toggle aria-label="Show password"><i class="bi bi-eye"></i></button></span></label>
                        </div>
                        <div class="settings-security-note"><i class="bi bi-shield-check"></i><span><strong>Your account is protected</strong><small>Changing your password refreshes your secure login session.</small></span></div>
                        <div class="settings-form-actions"><a href="logout"><i class="bi bi-box-arrow-right"></i> Log out</a><button type="submit">Change password</button></div>
                    </form>
                <?php elseif ($view === 'properties'): ?>
                    <div class="account-panel">
                        <h3>My Properties</h3>
                        <?php if ($propertyDrafts): ?>
                            <div class="account-card-list">
                                <?php foreach ($propertyDrafts as $draft): ?>
                                    <?php
                                    $progress = max(0, min(100, (int) ($draft['completion_percent'] ?? 0)));
                                    $title = trim((string) ($draft['title'] ?? '')) ?: 'Untitled property draft';
                                    $meta = accountDraftMeta($draft) ?: 'Complete the wizard details step by step';
                                    $propertyStatus = strtolower(trim((string) ($draft['property_status'] ?? '')));
                                    $canManageLiveStatus = in_array($propertyStatus, ['active', 'inactive', 'booked', 'sold', 'rented', 'occupied'], true);
                                    ?>
                                    <article class="account-enquiry-card">
                                        <div>
                                            <span class="account-tag"><?= e(accountDraftStatusLabel($draft)) ?></span>
                                            <h4><?= e($title) ?></h4>
                                            <p><?= e($meta) ?></p>
                                            <small><?= e((string) $progress) ?>% complete | Last saved <?= e((string) $draft['updated_at']) ?></small>
                                            <div class="account-progress-mini" aria-hidden="true"><span style="width: <?= e((string) $progress) ?>%"></span></div>
                                            <?php if (!empty($draft['property_id'])): ?>
                                                <div class="property-card-analytics">
                                                    <span><i class="bi bi-eye"></i><strong><?= e((string) ($draft['property_view_count'] ?? 0)) ?></strong> Views</span>
                                                    <span><i class="bi bi-chat-left-text"></i><strong><?= e((string) ($draft['property_lead_count'] ?? 0)) ?></strong> Enquiries</span>
                                                    <?php if ((int) ($draft['property_new_lead_count'] ?? 0) > 0): ?>
                                                        <a href="account?view=leads"><strong><?= e((string) $draft['property_new_lead_count']) ?></strong> New leads</a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (trim((string) ($draft['owner_status_reason'] ?? '')) !== ''): ?>
                                                <small class="account-status-reason">Reason: <?= e((string) $draft['owner_status_reason']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="account-listing-actions">
                                            <a class="account-action" href="post-property?draft_id=<?= e((string) $draft['id']) ?>"><?= !empty($draft['is_submitted']) ? 'Review Details' : 'Continue' ?></a>
                                            <?php if ($progress >= 100): ?>
                                                <a class="account-action account-preview-action" href="property-preview?draft_id=<?= e((string) $draft['id']) ?>"><i class="bi bi-eye"></i> Preview</a>
                                            <?php endif; ?>
                                            <?php if ($canManageLiveStatus && !empty($draft['property_id'])): ?>
                                                <details class="account-manage-listing">
                                                    <summary>Manage Listing</summary>
                                                    <form method="post" action="property-status">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                        <input type="hidden" name="property_id" value="<?= e((string) $draft['property_id']) ?>">
                                                        <label for="property_status_<?= e((string) $draft['property_id']) ?>">Status</label>
                                                        <select id="property_status_<?= e((string) $draft['property_id']) ?>" name="property_status">
                                                            <?php foreach ([
                                                                'active' => 'Live',
                                                                'inactive' => 'Temporarily Inactive',
                                                                'booked' => 'Booked',
                                                                'sold' => 'Sold',
                                                                'rented' => 'Rented',
                                                                'occupied' => 'Occupied',
                                                                'deleted' => 'Delete Listing',
                                                            ] as $value => $label): ?>
                                                                <option value="<?= e($value) ?>"<?= $propertyStatus === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <label for="property_reason_<?= e((string) $draft['property_id']) ?>">Reason for removing from live</label>
                                                        <textarea id="property_reason_<?= e((string) $draft['property_id']) ?>" name="reason" rows="3" maxlength="500" placeholder="Property sold, tenant found, temporarily unavailable..."><?= e((string) ($draft['owner_status_reason'] ?? '')) ?></textarea>
                                                        <button type="submit">Update Status</button>
                                                    </form>
                                                </details>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <a class="account-action mt-3" href="post-property">Post Another Property</a>
                        <?php else: ?>
                            <p class="account-muted">Start a listing and we will save each step as a draft, so you can finish it later.</p>
                            <a class="account-action" href="post-property">Post Property</a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($view === 'leads'): ?>
                    <div class="account-panel">
                        <div class="account-panel-heading">
                            <div>
                                <h3>Property Leads</h3>
                                <p class="account-muted">Only enquiries received for properties listed from your account are shown here.</p>
                            </div>
                            <span class="account-tag"><?= e((string) $ownerLeadSummary['new_leads']) ?> New</span>
                        </div>
                        <?php if ($ownerLeads): ?>
                            <div class="owner-lead-list">
                                <?php foreach ($ownerLeads as $lead): ?>
                                    <article class="owner-lead-card">
                                        <div class="owner-lead-property">
                                            <span class="account-tag"><?= e(accountEnquiryStatusLabel((string) $lead['status'])) ?></span>
                                            <h4>
                                                <?php if (($lead['slug'] ?? '') !== ''): ?>
                                                    <a href="<?= e(APP_URL . '/property/' . rawurlencode((string) $lead['slug'])) ?>"><?= e((string) (($lead['title'] ?? '') ?: 'Property enquiry')) ?></a>
                                                <?php else: ?>
                                                    <?= e((string) (($lead['title'] ?? '') ?: 'Property enquiry')) ?>
                                                <?php endif; ?>
                                            </h4>
                                            <p><?= e(implode(', ', array_filter([(string) ($lead['locality'] ?? ''), (string) ($lead['city'] ?? '')]))) ?></p>
                                            <small>Received <?= e((string) $lead['created_at']) ?></small>
                                        </div>
                                        <div class="owner-lead-contact">
                                            <span>Customer</span>
                                            <strong><?= e((string) (($lead['contact_name'] ?? '') ?: 'Customer')) ?></strong>
                                            <?php if (($lead['contact_phone'] ?? '') !== ''): ?><a href="tel:<?= e((string) $lead['contact_phone']) ?>"><?= e((string) $lead['contact_phone']) ?></a><?php endif; ?>
                                            <?php if (($lead['contact_email'] ?? '') !== ''): ?><a href="mailto:<?= e((string) $lead['contact_email']) ?>"><?= e((string) $lead['contact_email']) ?></a><?php endif; ?>
                                        </div>
                                        <div class="owner-lead-request">
                                            <span><?= e(ucfirst((string) (($lead['enquiry_type'] ?? '') ?: 'Enquiry'))) ?> via <?= e(ucfirst((string) (($lead['preferred_contact'] ?? '') ?: 'Call'))) ?></span>
                                            <?php if (($lead['message'] ?? '') !== ''): ?><p><?= nl2br(e((string) $lead['message'])) ?></p><?php endif; ?>
                                        </div>
                                        <form method="post" action="lead-status" class="owner-lead-status">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="enquiry_id" value="<?= e((string) $lead['id']) ?>">
                                            <label for="lead_status_<?= e((string) $lead['id']) ?>">Follow-up Status</label>
                                            <select id="lead_status_<?= e((string) $lead['id']) ?>" name="status" onchange="this.form.submit()">
                                                <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed', 'cancelled' => 'Cancelled'] as $statusValue => $statusLabel): ?>
                                                    <option value="<?= e($statusValue) ?>" <?= $lead['status'] === $statusValue ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="account-muted">No property leads yet. New enquiries will appear here and will also be sent by email.</p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($view === 'saved'): ?>
                    <div class="account-panel">
                        <h3>Saved Properties</h3>
                        <?php if ($savedProperties): ?>
                            <div class="account-card-list">
                                <?php foreach ($savedProperties as $item): ?>
                                    <article class="account-property-card" data-saved-card="<?= e((string) $item['property_ref']) ?>">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <a class="account-property-image" href="<?= e(accountPropertyUrl($item)) ?>">
                                                <img src="<?= e((string) $item['image_url']) ?>" alt="<?= e((string) (($item['title'] ?? '') ?: 'Saved property')) ?>">
                                            </a>
                                        <?php endif; ?>
                                        <div class="account-property-body">
                                            <span class="account-tag"><?= e((string) (($item['listing_type'] ?? '') ?: 'Property')) ?></span>
                                            <h4><a href="<?= e(accountPropertyUrl($item)) ?>"><?= e((string) (($item['title'] ?? '') ?: 'Saved property')) ?></a></h4>
                                            <p><?= e(accountPropertyMeta($item)) ?></p>
                                            <strong><?= e((string) (($item['price_text'] ?? '') ?: 'Price on request')) ?></strong>
                                            <small>Saved on <?= e((string) $item['created_at']) ?></small>
                                        </div>
                                        <button type="button" class="account-remove-btn" data-remove-saved="<?= e((string) $item['property_ref']) ?>">Remove</button>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="account-muted">No saved properties yet. Save homes from listings or property details to compare them later.</p>
                            <a class="account-action" href="<?= e(siteListingUrl(['type' => 'buy'])) ?>">Browse Listings</a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($view === 'enquiries'): ?>
                    <div class="account-panel">
                        <h3>My Enquiries</h3>
                        <?php if ($enquiries): ?>
                            <div class="account-card-list">
                                <?php foreach ($enquiries as $item): ?>
                                    <article class="account-enquiry-card">
                                        <div>
                                            <span class="account-tag"><?= e(accountEnquiryStatusLabel((string) $item['status'])) ?></span>
                                            <h4><?= e((string) (($item['title'] ?? '') ?: 'Property enquiry')) ?></h4>
                                            <p><?= e(accountPropertyMeta($item)) ?></p>
                                            <small>Source: <?= e((string) (($item['source'] ?? '') ?: 'Website')) ?> | Sent on <?= e((string) $item['created_at']) ?></small>
                                        </div>
                                        <strong><?= e((string) (($item['price_text'] ?? '') ?: 'Price on request')) ?></strong>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="account-muted">No enquiries yet. When you enquire on a property, it will appear here with follow-up status.</p>
                            <a class="account-action" href="<?= e(siteListingUrl(['type' => 'rent'])) ?>">Find Properties</a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($view === 'activity'): ?>
                    <div class="account-panel">
                        <h3>Activity Timeline</h3>
                        <?php if ($activity): ?>
                            <div class="activity-list">
                                <?php foreach ($activity as $item): ?>
                                    <div class="activity-item">
                                        <i class="bi bi-activity"></i>
                                        <div>
                                            <strong><?= e(accountActivityLabel($item)) ?></strong>
                                            <span><?= e((string) ($item['page_url'] ?? '')) ?></span>
                                            <small><?= e((string) $item['created_at']) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="account-muted">No activity recorded yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>

    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
        <a href="<?= e(siteWebsiteUrl()) ?>"><i class="bi bi-house-door"></i><span>Home</span></a>
        <a href="<?= e(siteListingUrl()) ?>"><i class="bi bi-search"></i><span>Search</span></a>
        <a class="mobile-bottom-post" href="post-property"><i class="bi bi-plus-lg"></i><span>Post</span></a>
        <a class="<?= $view === 'saved' ? 'active' : '' ?>" href="account?view=saved"><i class="bi bi-heart"></i><span>Saved</span></a>
        <a class="<?= $view !== 'saved' ? 'active' : '' ?>" href="account"><i class="bi bi-person"></i><span>Account</span></a>
    </nav>

    <script src="<?= e(APP_URL) ?>/website/assets/js/auth-ui.js?v=<?= e((string) filemtime(__DIR__ . '/assets/js/auth-ui.js')) ?>"></script>
    <script src="<?= e(APP_URL) ?>/website/assets/js/account.js?v=<?= e((string) filemtime(__DIR__ . '/assets/js/account.js')) ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
