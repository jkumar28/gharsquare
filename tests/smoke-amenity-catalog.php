<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/property.php';

if (!tableHasColumn('amenities_master', 'applicable_categories')) {
    throw new RuntimeException('Amenity category applicability column is missing.');
}
if (!tableHasColumn('property_profile', 'flooring_type')) {
    throw new RuntimeException('Flooring type column is missing.');
}

$amenities = amenitiesAll();
$byName = [];
foreach ($amenities as $amenity) {
    $byName[(string) $amenity['name']] = $amenity;
}

foreach (['Water Storage', 'CCTV Surveillance', 'Private Garden', 'Close to Hospital'] as $required) {
    if (!isset($byName[$required])) {
        throw new RuntimeException("Required amenity was not seeded: {$required}");
    }
}

if (str_contains((string) $byName['Private Garden']['applicable_categories'], 'commercial')) {
    throw new RuntimeException('Residential-only amenity is available for commercial listings.');
}

$validation = validateAmenityInput([
    'name' => 'Smoke Test Amenity',
    'category' => 'property_features',
    'applicable_categories' => ['commercial'],
    'sort_order' => '25',
]);
if ($validation['errors'] !== [] || $validation['data']['applicable_categories'] !== 'commercial') {
    throw new RuntimeException('Amenity category applicability validation failed.');
}

echo 'Amenity catalog smoke test passed.' . PHP_EOL;
