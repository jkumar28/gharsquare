<?php

declare(strict_types=1);

require_once BASE_PATH . '/includes/property.php';

const PUBLIC_SITE_NAME = 'GharSquare';

function siteWebsiteUrl(string $path = ''): string
{
    $path = ltrim(trim($path), '/');

    if ($path === '') {
        return APP_URL . '/';
    }

    [$route, $query] = array_pad(explode('?', $path, 2), 2, '');
    $route = ['index' => '', 'listing' => 'properties'][$route] ?? $route;
    $url = APP_URL . ($route !== '' ? '/' . $route : '/');

    return $query !== '' ? $url . '?' . $query : $url;
}

function sitePropertyUrl(array $property): string
{
    return APP_URL . '/property/' . rawurlencode(trim((string) ($property['slug'] ?? '')));
}

function siteListingRouteMap(): array
{
    return [
        'buy' => 'properties-for-sale',
        'rent' => 'properties-for-rent',
        'commercial' => 'commercial-properties',
        'pg' => 'pg-accommodation',
        'plots' => 'plots-for-sale',
    ];
}

function siteListingUrl(array $query = []): string
{
    $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== '');
    $type = strtolower(trim((string) ($query['type'] ?? '')));
    $city = trim((string) ($query['city'] ?? ''));
    $routes = siteListingRouteMap();

    unset($query['type'], $query['city'], $query['city_slug'], $query['seo_route']);

    if (isset($routes[$type])) {
        $url = APP_URL . '/' . $routes[$type];
    } else {
        $url = siteWebsiteUrl('listing');
    }

    if ($city !== '') {
        $url .= '/in-' . rawurlencode(slugify($city));
    }

    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

function siteCityNameFromSlug(string $slug, array $cities): string
{
    $slug = strtolower(trim($slug));

    foreach ($cities as $city) {
        $name = trim((string) ($city['name'] ?? ''));

        if ($name !== '' && slugify($name) === $slug) {
            return $name;
        }
    }

    return '';
}

function siteCurrency(mixed $amount): string
{
    $numeric = (float) $amount;

    return $numeric > 0 ? '₹' . formatNumberIndian($numeric) : '';
}

function sitePropertyNumericPrice(array $property): float
{
    return strtolower(trim((string) ($property['listing_type_name'] ?? ''))) === 'sell'
        ? (float) ($property['expected_price'] ?? 0)
        : (float) ($property['rent'] ?? 0);
}

function sitePropertyPriceLabel(array $property): string
{
    $price = siteCurrency(sitePropertyNumericPrice($property));

    if ($price === '') {
        return '';
    }

    return strtolower(trim((string) ($property['listing_type_name'] ?? ''))) === 'sell'
        ? $price
        : $price . ' / month';
}

function sitePropertyAreaValue(array $property): float
{
    $isLand = strtolower(trim((string) ($property['property_category'] ?? ''))) === 'land';
    $keys = $isLand
        ? ['plot_area', 'super_builtup_area', 'builtup_area', 'carpet_area']
        : ['super_builtup_area', 'builtup_area', 'carpet_area', 'plot_area'];

    foreach ($keys as $key) {
        if ((float) ($property[$key] ?? 0) > 0) {
            return (float) $property[$key];
        }
    }

    return 0;
}

function sitePropertyArea(array $property): string
{
    $unit = normalizeAreaUnit((string) ($property['area_unit'] ?? 'sq.ft'));
    $isLand = strtolower(trim((string) ($property['property_category'] ?? ''))) === 'land';
    $map = $isLand
        ? [
            'plot_area' => 'Plot',
            'super_builtup_area' => 'Super Built-up',
            'builtup_area' => 'Built-up',
            'carpet_area' => 'Carpet',
        ]
        : [
            'super_builtup_area' => 'Super Built-up',
            'builtup_area' => 'Built-up',
            'carpet_area' => 'Carpet',
            'plot_area' => 'Plot',
        ];

    foreach ($map as $key => $label) {
        $value = (float) ($property[$key] ?? 0);

        if ($value > 0) {
            return $label . ': ' . formatNumberIndian($value) . ' ' . $unit;
        }
    }

    return '';
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

    return implode(', ', $parts);
}

function sitePublicType(array $property): string
{
    $listing = strtolower(trim((string) ($property['listing_type_name'] ?? '')));
    $category = strtolower(trim((string) ($property['property_category'] ?? '')));

    if ($listing === 'pg') {
        return 'pg';
    }
    if ($category === 'land') {
        return 'plots';
    }
    if ($category === 'commercial') {
        return 'commercial';
    }

    return $listing === 'rent / lease' ? 'rent' : 'buy';
}

function siteNormalizeMediaUrl(string $url): string
{
    return propertyNormalizeMediaUrl($url);
}

function sitePropertyMedia(int $draftId, ?string $type = null): array
{
    if ($draftId <= 0) {
        return [];
    }

    $sql = 'SELECT id, source_type, file_url, title, thumbnail_url, mime_type, type, is_primary, sort_order
            FROM property_media WHERE draft_id = :draft_id';
    $params = [':draft_id' => $draftId];

    if ($type !== null) {
        $sql .= ' AND type = :type';
        $params[':type'] = $type;
    }

    $sql .= ' ORDER BY is_primary DESC, sort_order ASC, id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $item['file_url'] = siteNormalizeMediaUrl((string) ($item['file_url'] ?? ''));
        $item['thumbnail_url'] = siteNormalizeMediaUrl((string) ($item['thumbnail_url'] ?? ''));
    }
    unset($item);

    return $items;
}

function sitePropertyPrimaryImage(int $draftId): string
{
    foreach (sitePropertyMedia($draftId, 'image') as $item) {
        if (($item['file_url'] ?? '') !== '') {
            return (string) $item['file_url'];
        }
    }

    return '';
}

function sitePropertyAmenityNames(int $draftId, int $limit = 100): array
{
    if ($draftId <= 0) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT am.name
         FROM property_amenities pa
         INNER JOIN amenities_master am ON am.id = pa.amenity_id
         WHERE pa.draft_id = :draft_id AND am.name IS NOT NULL AND TRIM(am.name) != ""
         ORDER BY am.name ASC LIMIT :limit'
    );
    $stmt->bindValue(':draft_id', $draftId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
    $stmt->execute();

    return array_values(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
}

function siteBasePropertyQuery(): string
{
    $furnishingItemsSelect = tableHasColumn('property_profile', 'furnishing_items')
        ? 'pp.furnishing_items,'
        : 'NULL AS furnishing_items,';
    $profileDetailsSelect = tableHasColumn('property_profile', 'profile_details')
        ? 'pp.profile_details,'
        : 'NULL AS profile_details,';

    return "SELECT p.id, p.slug, p.status, p.draft_id, p.user_id, p.published_at, p.created_at,
                   pb.title, pb.description, pb.posted_by, pb.purpose_note, pb.available_from,
                   pt.id AS property_type_id, pt.name AS property_type_name, pt.category AS property_category,
                   lt.id AS listing_type_id, lt.name AS listing_type_name,
                   pl.address_line, pl.landmark, pl.pincode, pl.map_address, pl.latitude, pl.longitude,
                   l.name AS locality_name, ci.name AS city_name, s.name AS state_name,
                   pp.area_unit, pp.builtup_area, pp.super_builtup_area, pp.carpet_area, pp.plot_area,
                   pp.bedrooms, pp.bathrooms, pp.balconies, pp.furnishing, {$furnishingItemsSelect} {$profileDetailsSelect} pp.property_age,
                   pp.facing, pp.ownership_type, pp.parking_count, pp.floor_no, pp.total_floor,
                   pp.servant_room, pp.pooja_room, pp.study_room,
                   pr.expected_price, pr.price_per_area_unit, pr.rent, pr.deposit,
                   pr.security_deposit_type, pr.security_deposit_amount, pr.security_deposit_months,
                   pr.booking_amount, pr.maintenance, pr.maintenance_period,
                   pr.electricity_charges, pr.brokerage, pr.brokerage_type, pr.brokerage_value,
                   pr.brokerage_negotiable, pr.lock_in_months, pr.annual_rent_increase_percent,
                   pr.dg_ups_included, pr.electricity_water_excluded, pr.negotiable
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

function siteHydratePropertyRow(array $row, bool $full = false): array
{
    $draftId = (int) ($row['draft_id'] ?? 0);
    $row['primary_image'] = $draftId > 0 ? sitePropertyPrimaryImage($draftId) : '';
    $row['price_value'] = sitePropertyNumericPrice($row);
    $row['price_label'] = sitePropertyPriceLabel($row);
    $row['location_label'] = sitePropertyLocation($row);
    $row['area_value'] = sitePropertyAreaValue($row);
    $row['area_label'] = sitePropertyArea($row);
    $row['public_type'] = sitePublicType($row);
    $row['amenity_names'] = $draftId > 0 ? sitePropertyAmenityNames($draftId, $full ? 100 : 4) : [];

    if ($full) {
        $row['media'] = sitePropertyMedia($draftId);
        $row['images'] = array_values(array_filter(
            $row['media'],
            static fn (array $item): bool => ($item['type'] ?? '') === 'image' && ($item['file_url'] ?? '') !== ''
        ));
        $row['videos'] = array_values(array_filter(
            $row['media'],
            static fn (array $item): bool => ($item['type'] ?? '') === 'video' && ($item['file_url'] ?? '') !== ''
        ));
    }

    return $row;
}

function sitePropertyConditions(array $filters, array &$params): string
{
    $sql = '';
    $query = trim((string) ($filters['q'] ?? ''));

    if ($query !== '') {
        $sql .= ' AND (pb.title LIKE :q OR pb.description LIKE :q OR pl.address_line LIKE :q
                  OR pl.landmark LIKE :q OR l.name LIKE :q OR ci.name LIKE :q OR pt.name LIKE :q)';
        $params[':q'] = '%' . $query . '%';
    }

    $city = trim((string) ($filters['city'] ?? ''));
    if ($city !== '') {
        $sql .= ' AND ci.name = :city';
        $params[':city'] = $city;
    }

    if ((int) ($filters['property_type_id'] ?? 0) > 0) {
        $sql .= ' AND pb.property_type_id = :property_type_id';
        $params[':property_type_id'] = (int) $filters['property_type_id'];
    }

    $type = strtolower(trim((string) ($filters['type'] ?? '')));
    if ($type === 'buy') {
        $sql .= " AND LOWER(lt.name) = 'sell'";
    } elseif ($type === 'rent') {
        $sql .= " AND LOWER(lt.name) = 'rent / lease'";
    } elseif ($type === 'commercial') {
        $sql .= " AND LOWER(pt.category) = 'commercial'";
    } elseif ($type === 'pg') {
        $sql .= " AND LOWER(lt.name) = 'pg'";
    } elseif ($type === 'plots') {
        $sql .= " AND LOWER(pt.category) = 'land'";
    }

    if ((int) ($filters['bhk'] ?? 0) > 0) {
        $sql .= (int) $filters['bhk'] >= 4 ? ' AND pp.bedrooms >= :bedrooms' : ' AND pp.bedrooms = :bedrooms';
        $params[':bedrooms'] = (int) $filters['bhk'];
    }

    if ((float) ($filters['min_area'] ?? 0) > 0) {
        $sql .= " AND CASE WHEN LOWER(pt.category) = 'land'
                    THEN COALESCE(NULLIF(pp.plot_area, 0), NULLIF(pp.super_builtup_area, 0), NULLIF(pp.builtup_area, 0), NULLIF(pp.carpet_area, 0), 0)
                    ELSE COALESCE(NULLIF(pp.super_builtup_area, 0), NULLIF(pp.builtup_area, 0), NULLIF(pp.carpet_area, 0), NULLIF(pp.plot_area, 0), 0)
                  END >= :min_area";
        $params[':min_area'] = (float) $filters['min_area'];
    }

    $budget = strtolower(trim((string) ($filters['budget'] ?? '')));
    if (in_array($budget, ['low', 'mid', 'premium'], true)) {
        $rentType = $type === 'rent' || $type === 'pg';
        $priceExpression = $rentType ? 'COALESCE(pr.rent, 0)' : 'COALESCE(pr.expected_price, 0)';
        $lowBoundary = $rentType ? 15000 : 3000000;
        $highBoundary = $rentType ? 40000 : 10000000;

        if ($budget === 'low') {
            $sql .= " AND {$priceExpression} > 0 AND {$priceExpression} <= :budget_low";
            $params[':budget_low'] = $lowBoundary;
        } elseif ($budget === 'mid') {
            $sql .= " AND {$priceExpression} > :budget_low AND {$priceExpression} <= :budget_high";
            $params[':budget_low'] = $lowBoundary;
            $params[':budget_high'] = $highBoundary;
        } else {
            $sql .= " AND {$priceExpression} > :budget_high";
            $params[':budget_high'] = $highBoundary;
        }
    }

    return $sql;
}

function siteSearchProperties(array $filters = [], int $perPage = 12): array
{
    $perPage = max(1, min(48, $perPage));
    $page = max(1, (int) ($filters['page'] ?? 1));
    $params = [];
    $conditions = sitePropertyConditions($filters, $params);
    $countSql = 'SELECT COUNT(*) FROM (' . siteBasePropertyQuery() . $conditions . ') site_results';

    try {
        $countStmt = db()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        $sort = strtolower(trim((string) ($filters['sort'] ?? 'latest')));
        $orderBy = match ($sort) {
            'price_low' => ' ORDER BY COALESCE(NULLIF(pr.expected_price, 0), pr.rent, 0) ASC, p.id DESC',
            'price_high' => ' ORDER BY COALESCE(NULLIF(pr.expected_price, 0), pr.rent, 0) DESC, p.id DESC',
            'area_high' => " ORDER BY CASE WHEN LOWER(pt.category) = 'land'
                              THEN COALESCE(NULLIF(pp.plot_area, 0), NULLIF(pp.super_builtup_area, 0), NULLIF(pp.builtup_area, 0), pp.carpet_area, 0)
                              ELSE COALESCE(NULLIF(pp.super_builtup_area, 0), NULLIF(pp.builtup_area, 0), NULLIF(pp.carpet_area, 0), pp.plot_area, 0)
                            END DESC, p.id DESC",
            default => ' ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC',
        };
        $stmt = db()->prepare(siteBasePropertyQuery() . $conditions . $orderBy . ' LIMIT :limit OFFSET :offset');

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map('siteHydratePropertyRow', $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    } catch (Throwable $exception) {
        return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage];
    }
}

function siteFeaturedProperties(int $limit = 8): array
{
    return siteSearchProperties([], max(1, min(24, $limit)))['items'];
}

function siteFindPropertyBySlug(string $slug): ?array
{
    try {
        $stmt = db()->prepare(siteBasePropertyQuery() . ' AND p.slug = :slug LIMIT 1');
        $stmt->execute([':slug' => trim($slug)]);
        $row = $stmt->fetch();

        return $row ? siteHydratePropertyRow($row, true) : null;
    } catch (Throwable $exception) {
        return null;
    }
}

function siteFindPropertyByReference(string $reference): ?array
{
    try {
        $sql = siteBasePropertyQuery() . (ctype_digit($reference)
            ? ' AND p.id = :property_id LIMIT 1'
            : ' AND p.slug = :slug LIMIT 1');
        $stmt = db()->prepare($sql);
        $stmt->execute(ctype_digit($reference)
            ? [':property_id' => (int) $reference]
            : [':slug' => $reference]);
        $row = $stmt->fetch();

        return $row ? siteHydratePropertyRow($row, true) : null;
    } catch (Throwable $exception) {
        return null;
    }
}

function siteSimilarProperties(array $property, int $limit = 3): array
{
    $results = siteSearchProperties(['type' => (string) ($property['public_type'] ?? '')], min(12, $limit + 4));

    return array_slice(array_values(array_filter(
        $results['items'],
        static fn (array $item): bool => (int) ($item['id'] ?? 0) !== (int) ($property['id'] ?? 0)
    )), 0, $limit);
}

function siteHomepageStats(): array
{
    try {
        return [
            'active_properties' => (int) db()->query("SELECT COUNT(*) FROM properties WHERE status = 'active'")->fetchColumn(),
            'cities' => (int) db()->query(
                "SELECT COUNT(DISTINCT pl.city_id) FROM properties p
                 INNER JOIN property_location pl ON pl.draft_id = p.draft_id
                 WHERE p.status = 'active' AND pl.city_id IS NOT NULL"
            )->fetchColumn(),
            'localities' => (int) db()->query(
                "SELECT COUNT(DISTINCT pl.locality_id) FROM properties p
                 INNER JOIN property_location pl ON pl.draft_id = p.draft_id
                 WHERE p.status = 'active' AND pl.locality_id IS NOT NULL"
            )->fetchColumn(),
        ];
    } catch (Throwable $exception) {
        return ['active_properties' => 0, 'cities' => 0, 'localities' => 0];
    }
}

function siteHomepageCities(int $limit = 20): array
{
    try {
        $stmt = db()->prepare(
            "SELECT ci.id, ci.name, s.name AS state_name, COUNT(p.id) AS property_count
             FROM cities ci
             INNER JOIN property_location pl ON pl.city_id = ci.id
             INNER JOIN properties p ON p.draft_id = pl.draft_id AND p.status = 'active'
             LEFT JOIN states s ON s.id = ci.state_id
             GROUP BY ci.id, ci.name, s.name
             ORDER BY property_count DESC, ci.name ASC LIMIT :limit"
        );
        $stmt->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function sitePopularLocalities(string $city, int $limit = 6): array
{
    if (trim($city) === '') {
        return [];
    }

    try {
        $stmt = db()->prepare(
            "SELECT l.name, COUNT(p.id) AS property_count
             FROM localities l
             INNER JOIN property_location pl ON pl.locality_id = l.id
             INNER JOIN cities ci ON ci.id = pl.city_id
             INNER JOIN properties p ON p.draft_id = pl.draft_id AND p.status = 'active'
             WHERE ci.name = :city
             GROUP BY l.id, l.name
             ORDER BY property_count DESC, l.name ASC LIMIT :limit"
        );
        $stmt->bindValue(':city', trim($city), PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(20, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function siteSearchFilterData(): array
{
    return ['property_types' => propertyTypesAll(), 'cities' => siteHomepageCities(100)];
}

function sitePropertyCard(array $property, bool $compact = false): string
{
    $title = trim((string) ($property['title'] ?? ''));
    $image = trim((string) ($property['primary_image'] ?? ''));
    $url = sitePropertyUrl($property);
    $facts = [];

    if ((int) ($property['bedrooms'] ?? 0) > 0) {
        $facts[] = (int) $property['bedrooms'] . ' BHK';
    }
    if (($property['area_label'] ?? '') !== '') {
        $facts[] = (string) $property['area_label'];
    }

    ob_start();
    ?>
    <article class="<?= $compact ? 'property-card property-card-compact' : 'property-card' ?>" data-property-id="<?= e((string) $property['id']) ?>">
        <a class="property-img" href="<?= e($url) ?>">
            <?php if ($image !== ''): ?>
                <img src="<?= e($image) ?>" alt="<?= e($title) ?>" loading="lazy">
            <?php else: ?>
                <span class="property-image-empty"><i class="bi bi-building"></i></span>
            <?php endif; ?>
            <?php if (($property['listing_type_name'] ?? '') !== ''): ?>
                <span class="badge-premium"><?= e((string) $property['listing_type_name']) ?></span>
            <?php endif; ?>
        </a>
        <div class="property-body">
            <?php if (($property['price_label'] ?? '') !== ''): ?>
                <div class="price"><?= e((string) $property['price_label']) ?></div>
            <?php endif; ?>
            <?php if ($title !== ''): ?>
                <h3><a href="<?= e($url) ?>"><?= e($title) ?></a></h3>
            <?php endif; ?>
            <?php if (($property['location_label'] ?? '') !== ''): ?>
                <div class="location"><i class="bi bi-geo-alt"></i> <?= e((string) $property['location_label']) ?></div>
            <?php endif; ?>
            <?php if ($facts !== []): ?>
                <div class="property-info">
                    <?php foreach ($facts as $fact): ?>
                        <span><?= e($fact) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
    <?php

    return (string) ob_get_clean();
}
