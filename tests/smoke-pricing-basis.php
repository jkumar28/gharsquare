<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/property.php';

$sellListingId = (int) db()->query("SELECT id FROM listing_types WHERE LOWER(name) = 'sell' LIMIT 1")->fetchColumn();
if ($sellListingId <= 0) {
    throw new RuntimeException('Sell listing type is missing.');
}

$validation = validatePropertyPricingInput([
    'expected_price' => '500000',
    'price_area_basis' => 'carpet_area',
    'electricity_water_excluded' => '1',
], [
    'listing_type_id' => $sellListingId,
    'posted_by' => 'owner',
]);

if ($validation['errors'] !== []) {
    throw new RuntimeException('Valid pricing basis was rejected: ' . implode(' ', $validation['errors']));
}
if ((string) $validation['data']['price_area_basis'] !== 'carpet_area') {
    throw new RuntimeException('Selected price area basis was not retained.');
}
if ((int) $validation['data']['electricity_water_excluded'] !== 1) {
    throw new RuntimeException('Electricity and water exclusion was cleared for a sale listing.');
}

echo 'Pricing basis smoke test passed.' . PHP_EOL;
