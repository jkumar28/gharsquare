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
<aside class="admin-sidebar">
    <a class="brand-block" href="<?= ADMIN_URL ?>/index.php">
        <span class="brand-mark">GS</span>
        <span>
            <strong>GharSquare</strong>
            <small>Admin Panel</small>
        </span>
    </a>

    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Core</div>
        <a class="<?= navIsActive('/admin/index.php', $currentPath) ?>" href="<?= ADMIN_URL ?>/index.php">Dashboard</a>
        <a class="<?= navIsActive('/admin/users/', $currentPath) ?>" href="<?= ADMIN_URL ?>/users/index.php">Users</a>
        <a class="<?= navIsActive('/admin/properties/', $currentPath) ?>" href="<?= ADMIN_URL ?>/properties/index.php">Properties</a>
        <a class="<?= navIsActive('/admin/enquiries/', $currentPath) ?>" href="<?= ADMIN_URL ?>/enquiries/index.php">Enquiries</a>

        <details class="sidebar-group<?= $mastersOpen ? ' is-open' : '' ?>" <?= $mastersOpen ? 'open' : '' ?>>
            <summary class="sidebar-group-toggle<?= $mastersOpen ? ' active' : '' ?>">
                <span>Masters</span>
                <span class="sidebar-group-icon" aria-hidden="true"></span>
            </summary>
            <div class="sidebar-subnav">
                <a class="<?= navIsActive('/admin/masters/countries/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/countries/index.php">Countries</a>
                <a class="<?= navIsActive('/admin/masters/states/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/states/index.php">States</a>
                <a class="<?= navIsActive('/admin/masters/cities/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/cities/index.php">Cities</a>
                <a class="<?= navIsActive('/admin/masters/localities/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/localities/index.php">Localities</a>
                <a class="<?= navIsActive('/admin/masters/property-types/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/property-types/index.php">Property Types</a>
                <a class="<?= navIsActive('/admin/masters/listing-types/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/listing-types/index.php">Listing Types</a>
                <a class="<?= navIsActive('/admin/masters/amenities/', $currentPath) ?>" href="<?= ADMIN_URL ?>/masters/amenities/index.php">Amenities</a>
            </div>
        </details>

        <div class="sidebar-section-title">System</div>
        <a class="<?= navIsActive('/admin/settings/', $currentPath) ?>" href="javascript:void(0)">Settings</a>
    </nav>
</aside>
