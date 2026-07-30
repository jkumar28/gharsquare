<?php

declare(strict_types=1);

$currentPath = $_SERVER['PHP_SELF'] ?? '';

function navIsActive(string $needle, string $currentPath): string
{
    return str_contains($currentPath, $needle) ? 'active' : '';
}

function navGroupIsActive(array $needles, string $currentPath): bool
{
    foreach ($needles as $needle) {
        if (str_contains($currentPath, $needle)) {
            return true;
        }
    }

    return false;
}

$mastersOpen = navGroupIsActive([
    '/admin/masters/countries/',
    '/admin/masters/states/',
    '/admin/masters/cities/',
    '/admin/masters/localities/',
    '/admin/masters/property-types/',
    '/admin/masters/listing-types/',
    '/admin/masters/amenities/',
], $currentPath);
?>
<header class="admin-navbar">
    <div class="admin-navbar-inner">
        <a class="brand-block" href="<?= ADMIN_URL ?>/index.php" aria-label="GharSquare admin dashboard">
            <span class="brand-mark">GS</span>
            <span class="brand-copy">
                <strong>GharSquare</strong>
                <small>Admin Panel</small>
            </span>
        </a>

        <button
            class="admin-nav-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="adminPrimaryNav"
            data-admin-nav-toggle
        >
            <span class="visually-hidden">Toggle admin navigation</span>
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="admin-nav-panel" id="adminPrimaryNav" data-admin-nav-panel>
            <nav class="admin-primary-nav" aria-label="Admin navigation">
                <a class="<?= navIsActive('/admin/index.php', $currentPath) ?>" href="<?= ADMIN_URL ?>/index.php">
                    <i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Dashboard</span>
                </a>
                <a class="<?= navIsActive('/admin/users/', $currentPath) ?>" href="<?= ADMIN_URL ?>/users/index.php">
                    <i class="bi bi-people" aria-hidden="true"></i><span>Users</span>
                </a>
                <a class="<?= navIsActive('/admin/properties/', $currentPath) ?>" href="<?= ADMIN_URL ?>/properties/index.php">
                    <i class="bi bi-buildings" aria-hidden="true"></i><span>Properties</span>
                </a>
                <a class="<?= navIsActive('/admin/enquiries/', $currentPath) ?>" href="<?= ADMIN_URL ?>/enquiries/index.php">
                    <i class="bi bi-chat-left-text" aria-hidden="true"></i><span>Enquiries</span>
                </a>

                <details class="admin-nav-group">
                    <summary class="admin-nav-group-toggle<?= $mastersOpen ? ' active' : '' ?>">
                        <i class="bi bi-sliders" aria-hidden="true"></i>
                        <span>Masters</span>
                        <i class="bi bi-chevron-down admin-nav-chevron" aria-hidden="true"></i>
                    </summary>
                    <div class="admin-nav-dropdown">
                        <a class="<?= navIsActive('/admin/masters/countries/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/countries/index.php">Countries</a>
                        <a class="<?= navIsActive('/admin/masters/states/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/states/index.php">States</a>
                        <a class="<?= navIsActive('/admin/masters/cities/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/cities/index.php">Cities</a>
                        <a class="<?= navIsActive('/admin/masters/localities/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/localities/index.php">Localities</a>
                        <a class="<?= navIsActive('/admin/masters/property-types/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/property-types/index.php">Property Types</a>
                        <a class="<?= navIsActive('/admin/masters/listing-types/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/listing-types/index.php">Listing Types</a>
                        <a class="<?= navIsActive('/admin/masters/amenities/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/amenities/index.php">Amenities</a>
                    </div>
                </details>
            </nav>

            <div class="admin-nav-account">
                <div class="admin-nav-user">
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    <span>
                        <small>Signed in as</small>
                        <strong><?= e($currentAdmin['name'] ?? 'Admin') ?></strong>
                    </span>
                </div>
                <a class="admin-nav-logout" href="<?= ADMIN_URL ?>/logout.php" aria-label="Logout">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>
