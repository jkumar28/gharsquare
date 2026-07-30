<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/property.php';

function validIdOrNull(array $input, string $key, callable $finder): ?int
{
    $id = (int) ($input[$key] ?? 0);

    return $id > 0 && $finder($id) ? $id : null;
}

require BASE_PATH . '/includes/map_location.php';

$india = findCountryByName('India');
if (!$india) {
    throw new RuntimeException('India must exist for the map location smoke test.');
}

$suffix = bin2hex(random_bytes(4));
$pdo = db();
$pdo->beginTransaction();

try {
    $resolved = resolveMapLocationHierarchy([
        'country_id' => (int) $india['id'],
        'state_id' => 0,
        'city_id' => 0,
        'locality_id' => 0,
        'map_state_name' => 'Test State ' . $suffix,
        'map_city_name' => 'Test City ' . $suffix,
        'map_locality_name' => 'Test Locality ' . $suffix,
        'pincode' => '800001',
    ]);

    $state = findState((int) $resolved['state_id']);
    $city = findCity((int) $resolved['city_id']);
    $locality = findLocality((int) $resolved['locality_id']);

    if (
        !$state
        || !$city
        || !$locality
        || (int) $state['country_id'] !== (int) $india['id']
        || (int) $city['state_id'] !== (int) $state['id']
        || (int) $locality['city_id'] !== (int) $city['id']
        || (string) $locality['pincode'] !== '800001'
    ) {
        throw new RuntimeException('Map location hierarchy was not created correctly.');
    }

    $manualResolved = resolveMapLocationHierarchy([
        'country_id' => (int) $india['id'],
        'state_id' => (int) $state['id'],
        'city_id' => (int) $city['id'],
        'locality_id' => 0,
        'map_state_name' => '',
        'map_city_name' => '',
        'map_locality_name' => 'Manual Locality ' . $suffix,
        'pincode' => '800002',
    ]);
    $manualLocality = findLocality((int) $manualResolved['locality_id']);

    if (
        !$manualLocality
        || (int) $manualLocality['city_id'] !== (int) $city['id']
        || (string) $manualLocality['name'] !== 'Manual Locality ' . $suffix
    ) {
        throw new RuntimeException('Manually entered locality was not created under the selected city.');
    }

    echo 'Map location auto-creation smoke test passed.' . PHP_EOL;
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
