<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/property.php';

$commercialTypeId = (int) db()->query(
    "SELECT id FROM property_types WHERE category = 'commercial' ORDER BY id LIMIT 1"
)->fetchColumn();

if ($commercialTypeId <= 0) {
    throw new RuntimeException('Commercial property type master data is missing.');
}

$validation = validatePropertyProfileInput([
    'area_unit' => 'sq.ft',
    'builtup_area' => '100',
    'property_age' => 'new',
    'facing' => 'east',
    'study_room' => '1',
], [
    'property_type_id' => $commercialTypeId,
]);

if ((int) ($validation['data']['study_room'] ?? 1) !== 0) {
    throw new RuntimeException('Study room was retained for a commercial property.');
}

echo 'Commercial profile normalization smoke test passed.' . PHP_EOL;
