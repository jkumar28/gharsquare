<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/includes/public_site.php';

header('Content-Type: application/xml; charset=UTF-8');

$staticUrls = [
    ['loc' => siteWebsiteUrl(), 'priority' => '1.0', 'frequency' => 'daily'],
    ['loc' => siteListingUrl(), 'priority' => '0.9', 'frequency' => 'hourly'],
];
$filterData = siteSearchFilterData();

foreach (array_keys(siteListingRouteMap()) as $type) {
    if ((int) siteSearchProperties(['type' => $type], 1)['total'] <= 0) {
        continue;
    }

    $staticUrls[] = [
        'loc' => siteListingUrl(['type' => $type]),
        'priority' => '0.9',
        'frequency' => 'daily',
    ];
}

foreach (($filterData['cities'] ?? []) as $city) {
    $cityName = trim((string) ($city['name'] ?? ''));

    if ($cityName === '' || (int) ($city['property_count'] ?? 0) <= 0) {
        continue;
    }

    $staticUrls[] = [
        'loc' => siteListingUrl(['city' => $cityName]),
        'priority' => '0.8',
        'frequency' => 'daily',
    ];

    foreach (array_keys(siteListingRouteMap()) as $type) {
        if ((int) siteSearchProperties(['type' => $type, 'city' => $cityName], 1)['total'] <= 0) {
            continue;
        }

        $staticUrls[] = [
            'loc' => siteListingUrl(['type' => $type, 'city' => $cityName]),
            'priority' => '0.8',
            'frequency' => 'daily',
        ];
    }
}

$statement = db()->query(
    "SELECT slug, COALESCE(published_at, created_at) AS last_modified
     FROM properties
     WHERE status = 'active' AND slug IS NOT NULL AND slug <> ''
     ORDER BY published_at DESC, created_at DESC"
);
$properties = $statement->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>', PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticUrls as $item): ?>
    <url>
        <loc><?= htmlspecialchars($item['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
        <changefreq><?= $item['frequency'] ?></changefreq>
        <priority><?= $item['priority'] ?></priority>
    </url>
<?php endforeach; ?>
<?php foreach ($properties as $property): ?>
    <url>
        <loc><?= htmlspecialchars(sitePropertyUrl($property), ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
        <?php if (!empty($property['last_modified'])): ?><lastmod><?= e(date('c', strtotime((string) $property['last_modified']))) ?></lastmod><?php endif; ?>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>
</urlset>
