<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/property.php';

$valid = validatePropertyLocationInput([
    'country_id' => 1,
    'state_id' => 1,
    'city_id' => 1,
    'locality_id' => 1,
    'pincode' => '800001',
]);

if ($valid['errors'] !== []) {
    throw new RuntimeException('A valid location hierarchy was rejected: ' . implode(' ', $valid['errors']));
}

$wrongCity = validatePropertyLocationInput([
    'country_id' => 1,
    'state_id' => 1,
    'city_id' => 4,
    'locality_id' => 3,
]);

if (!in_array('The selected city does not belong to the selected state.', $wrongCity['errors'], true)) {
    throw new RuntimeException('A city from another state was not rejected.');
}

$wrongLocality = validatePropertyLocationInput([
    'country_id' => 1,
    'state_id' => 1,
    'city_id' => 1,
    'locality_id' => 3,
]);

if (!in_array('The selected locality does not belong to the selected city.', $wrongLocality['errors'], true)) {
    throw new RuntimeException('A locality from another city was not rejected.');
}

$pdo = db();
$pdo->beginTransaction();

try {
    $pdo->exec('UPDATE property_location SET city_id = 4, locality_id = 3 WHERE draft_id = 1');
    throw new RuntimeException('The database accepted a mismatched state and city.');
} catch (PDOException $exception) {
    if ((string) $exception->getCode() !== '23000') {
        throw $exception;
    }
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo 'Location hierarchy smoke test passed.' . PHP_EOL;
