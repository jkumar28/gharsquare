<?php

declare(strict_types=1);

require_once BASE_PATH . '/includes/property.php';

const SITE_NAME = 'GharSquare';

function siteNavItems(): array
{
    return [
        ['label' => 'Home', 'href' => APP_URL . '/index.php', 'key' => 'home'],
        ['label' => 'Buy', 'href' => APP_URL . '/properties.php?listing_type=sell', 'key' => 'buy'],
        ['label' => 'Rent', 'href' => APP_URL . '/properties.php?listing_type=rent-lease', 'key' => 'rent'],
        ['label' => 'All Properties', 'href' => APP_URL . '/properties.php', 'key' => 'properties'],
        ['label' => 'Admin', 'href' => ADMIN_URL . '/auth/login.php', 'key' => 'admin'],
    ];
}

function siteHeader(string $title, string $activeKey = 'home'): void
{
    $navItems = siteNavItems();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/site.css" rel="stylesheet">
</head>
<body class="site-body">
<div class="site-shell">
    <header class="site-header">
        <div class="site-brand">
            <a href="<?= APP_URL ?>/index.php" class="site-brand-link">
                <span class="site-brand-mark">GS</span>
                <span>
                    <strong><?= e(SITE_NAME) ?></strong>
                    <small>Property Marketplace</small>
                </span>
            </a>
        </div>
        <button class="site-menu-toggle" type="button" data-site-menu-toggle aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <nav class="site-nav" data-site-nav>
            <?php foreach ($navItems as $item): ?>
                <a href="<?= e($item['href']) ?>" class="<?= $activeKey === $item['key'] ? 'active' : '' ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
            <a class="site-post-button" href="<?= ADMIN_URL ?>/properties/index.php">Post Property</a>
        </nav>
    </header>
    <main class="site-main">
    <?php
}

function siteFooter(): void
{
    $year = date('Y');
    ?>
    </main>
    <footer class="site-footer">
        <div>
            <strong><?= e(SITE_NAME) ?></strong>
            <p>Buy, rent, and discover properties with a cleaner search experience built for modern Indian real estate.</p>
        </div>
        <div class="site-footer-links">
            <a href="<?= APP_URL ?>/index.php">Home</a>
            <a href="<?= APP_URL ?>/properties.php">Properties</a>
            <a href="<?= ADMIN_URL ?>/auth/login.php">Admin Login</a>
        </div>
        <p class="site-footer-copy">Copyright <?= e((string) $year) ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
    </footer>
</div>
<script src="<?= APP_URL ?>/assets/js/site.js"></script>
</body>
</html>
    <?php
}

function siteCurrency(mixed $amount): string
{
    $numeric = (float) $amount;

    if ($numeric <= 0) {
        return 'Price on request';
    }

    return '₹' . formatNumberIndian($numeric);
}

function sitePropertyArea(array $property): string
{
    $unit = normalizeAreaUnit((string) ($property['area_unit'] ?? 'sq.ft'));
    $map = [
        'super_builtup_area' => 'Super Built-up',
        'builtup_area' => 'Built-up',
        'carpet_area' => 'Carpet',
        'plot_area' => 'Plot',
    ];

    foreach ($map as $key => $label) {
        $value = trim((string) ($property[$key] ?? ''));

        if ($value !== '') {
            return $label . ': ' . formatNumberIndian((float) $value) . ' ' . $unit;
        }
    }

    return 'Area on request';
}

function sitePropertyLocation(array $property): string
{
    $parts = [];

    foreach (['locality_name', 'city_name', 'state_name'] as $key) {
        $value = trim((string) ($property[$key] ?? ''));

        if ($value !== '' && !in_array($value, $parts, true)) {
            $parts[] = $value;
        }
    }

    return $parts === [] ? 'Location not specified' : implode(', ', $parts);
}

function sitePropertyPriceLabel(array $property): string
{
    $listingType = strtolower(trim((string) ($property['listing_type_name'] ?? '')));

    if ($listingType === 'sell') {
        return siteCurrency($property['expected_price'] ?? null);
    }

    $rent = siteCurrency($property['rent'] ?? null);
    return $rent === 'Price on request' ? $rent : $rent . ' / month';
}

function sitePropertyPrimaryImage(int $draftId): string
{
    $mediaItems = propertyDraftMedia($draftId);

    foreach ($mediaItems as $item) {
        if (($item['kind'] ?? '') === 'image' && (int) ($item['is_primary'] ?? 0) === 1) {
            return (string) $item['file_url'];
        }
    }

    foreach ($mediaItems as $item) {
        if (($item['kind'] ?? '') === 'image') {
            return (string) $item['file_url'];
        }
    }

    return APP_URL . '/assets/images/site-property-placeholder.svg';
}

function sitePropertyAmenityNames(int $draftId, int $limit = 4): array
{
    $ids = propertyDraftAmenityIds($draftId);
    $names = [];

    foreach ($ids as $id) {
        $amenity = findAmenity((int) $id);
        $name = trim((string) ($amenity['name'] ?? ''));

        if ($name !== '') {
            $names[] = $name;
        }

        if (count($names) >= $limit) {
            break;
        }
    }

    return $names;
}

function siteBasePropertyQuery(): string
{
    $furnishingItemsSelect = tableHasColumn('property_profile', 'furnishing_items')
        ? 'pp.furnishing_items,'
        : 'NULL AS furnishing_items,';
    $profileDetailsSelect = tableHasColumn('property_profile', 'profile_details')
        ? 'pp.profile_details,'
        : 'NULL AS profile_details,';

    return "SELECT p.id, p.slug, p.status, p.draft_id, p.user_id,
                   pb.title, pb.description, pb.posted_by, pb.purpose_note,
                   pt.name AS property_type_name, pt.category AS property_category,
                   lt.name AS listing_type_name,
                   pl.address_line, pl.landmark, pl.pincode, pl.map_address, pl.latitude, pl.longitude,
                   l.name AS locality_name, ci.name AS city_name, s.name AS state_name,
                   pp.area_unit, pp.builtup_area, pp.super_builtup_area, pp.carpet_area, pp.plot_area,
                   pp.bedrooms, pp.bathrooms, pp.balconies, pp.furnishing, {$furnishingItemsSelect} {$profileDetailsSelect} pp.property_age, pp.facing, pp.ownership_type, pp.parking_count,
                   pr.expected_price, pr.rent, pr.deposit, pr.maintenance, pr.negotiable
            FROM properties p
            LEFT JOIN property_basic pb ON pb.draft_id = p.draft_id
            LEFT JOIN property_types pt ON pt.id = pb.property_type_id
            LEFT JOIN listing_types lt ON lt.id = pb.listing_type_id
            LEFT JOIN property_location pl ON pl.draft_id = p.draft_id
            LEFT JOIN localities l ON l.id = pl.locality_id
            LEFT JOIN cities ci ON ci.id = pl.city_id
            LEFT JOIN states s ON s.id = pl.state_id
            LEFT JOIN property_profile pp ON pp.draft_id = p.draft_id
            LEFT JOIN property_pricing pr ON pr.draft_id = p.draft_id
            WHERE p.status = 'active'";
}

function siteHydratePropertyRow(array $row): array
{
    $draftId = (int) ($row['draft_id'] ?? 0);
    $row['primary_image'] = $draftId > 0 ? sitePropertyPrimaryImage($draftId) : '';
    $row['price_label'] = sitePropertyPriceLabel($row);
    $row['location_label'] = sitePropertyLocation($row);
    $row['area_label'] = sitePropertyArea($row);
    $row['amenity_names'] = $draftId > 0 ? sitePropertyAmenityNames($draftId) : [];

    return $row;
}

function siteFeaturedProperties(int $limit = 6): array
{
    $sql = siteBasePropertyQuery() . ' ORDER BY p.id DESC LIMIT :limit';

    try {
        $stmt = db()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('siteHydratePropertyRow', $stmt->fetchAll());
    } catch (Throwable $exception) {
        return [];
    }
}

function siteSearchProperties(array $filters = [], int $limit = 12): array
{
    $sql = siteBasePropertyQuery();
    $params = [];

    if (($filters['q'] ?? '') !== '') {
        $sql .= " AND (pb.title LIKE :q OR pb.description LIKE :q OR l.name LIKE :q OR ci.name LIKE :q)";
        $params[':q'] = '%' . trim((string) $filters['q']) . '%';
    }

    if ((int) ($filters['city_id'] ?? 0) > 0) {
        $sql .= ' AND pl.city_id = :city_id';
        $params[':city_id'] = (int) $filters['city_id'];
    }

    if ((int) ($filters['property_type_id'] ?? 0) > 0) {
        $sql .= ' AND pb.property_type_id = :property_type_id';
        $params[':property_type_id'] = (int) $filters['property_type_id'];
    }

    if ((int) ($filters['listing_type_id'] ?? 0) > 0) {
        $sql .= ' AND pb.listing_type_id = :listing_type_id';
        $params[':listing_type_id'] = (int) $filters['listing_type_id'];
    }

    if (($filters['listing_type'] ?? '') !== '') {
        $sql .= ' AND LOWER(REPLACE(lt.name, " / ", "-")) = :listing_type_name';
        $params[':listing_type_name'] = strtolower(trim((string) $filters['listing_type']));
    }

    $sql .= ' ORDER BY p.id DESC LIMIT :limit';

    try {
        $stmt = db()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('siteHydratePropertyRow', $stmt->fetchAll());
    } catch (Throwable $exception) {
        return [];
    }
}

function siteFindPropertyBySlug(string $slug): ?array
{
    $sql = siteBasePropertyQuery() . ' AND p.slug = :slug LIMIT 1';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? siteHydratePropertyRow($row) : null;
    } catch (Throwable $exception) {
        return null;
    }
}

function siteHomepageStats(): array
{
    try {
        return [
            'active_properties' => (int) db()->query("SELECT COUNT(*) FROM properties WHERE status = 'active'")->fetchColumn(),
            'cities' => (int) db()->query('SELECT COUNT(*) FROM cities')->fetchColumn(),
            'localities' => (int) db()->query('SELECT COUNT(*) FROM localities')->fetchColumn(),
            'users' => (int) db()->query("SELECT COUNT(*) FROM users WHERE status != 'deleted'")->fetchColumn(),
        ];
    } catch (Throwable $exception) {
        return [
            'active_properties' => 0,
            'cities' => 0,
            'localities' => 0,
            'users' => 0,
        ];
    }
}

function siteHomepageCities(int $limit = 8): array
{
    try {
        $stmt = db()->prepare("SELECT ci.id, ci.name, s.name AS state_name, COUNT(p.id) AS property_count
            FROM cities ci
            LEFT JOIN states s ON s.id = ci.state_id
            LEFT JOIN property_location pl ON pl.city_id = ci.id
            LEFT JOIN properties p ON p.draft_id = pl.draft_id AND p.status = 'active'
            GROUP BY ci.id, ci.name, s.name
            ORDER BY property_count DESC, ci.name ASC
            LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function siteSearchFilterData(): array
{
    return [
        'listing_types' => listingTypesAll(),
        'property_types' => propertyTypesAll(),
        'cities' => citiesAll(),
    ];
}

function sitePropertyCard(array $property): string
{
    $amenities = $property['amenity_names'] ?? [];
    $factItems = array_filter([
        trim((string) ($property['bedrooms'] ?? '')) !== '' ? e((string) $property['bedrooms']) . ' Beds' : null,
        trim((string) ($property['bathrooms'] ?? '')) !== '' ? e((string) $property['bathrooms']) . ' Baths' : null,
        $property['area_label'] ?? null,
    ]);

    return '<article class="property-card">' .
        '<a class="property-card-media" href="' . APP_URL . '/property-details.php?slug=' . urlencode((string) ($property['slug'] ?? '')) . '">' .
            '<img src="' . e((string) ($property['primary_image'] ?? '')) . '" alt="' . e((string) ($property['title'] ?? 'Property image')) . '">' .
            '<span class="property-chip">' . e((string) ($property['listing_type_name'] ?? 'Property')) . '</span>' .
        '</a>' .
        '<div class="property-card-body">' .
            '<div class="property-card-topline">' .
                '<span class="property-price">' . e((string) ($property['price_label'] ?? 'Price on request')) . '</span>' .
                '<span class="property-type">' . e((string) ($property['property_type_name'] ?? 'Property')) . '</span>' .
            '</div>' .
            '<h3><a href="' . APP_URL . '/property-details.php?slug=' . urlencode((string) ($property['slug'] ?? '')) . '">' . e((string) ($property['title'] ?? 'Untitled property')) . '</a></h3>' .
            '<p class="property-location"><i class="bi bi-geo-alt"></i> ' . e((string) ($property['location_label'] ?? 'Location not specified')) . '</p>' .
            '<div class="property-facts">' . implode('', array_map(static fn ($item) => '<span>' . $item . '</span>', $factItems)) . '</div>' .
            ($amenities !== [] ? '<div class="property-amenities">' . implode('', array_map(static fn ($item) => '<span>' . e((string) $item) . '</span>', $amenities)) . '</div>' : '') .
        '</div>' .
    '</article>';
}
