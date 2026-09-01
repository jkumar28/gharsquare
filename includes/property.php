<?php

declare(strict_types=1);

require_once BASE_PATH . '/includes/functions.php';

function propertyWizardStepDefinitions(): array
{
    return [
        'basic' => [
            'title' => 'Basic Details',
            'fields' => [
                ['key' => 'user_id', 'label' => 'User'],
                ['key' => 'property_type_id', 'label' => 'Property Type'],
                ['key' => 'listing_type_id', 'label' => 'Listing Type'],
                ['key' => 'title', 'label' => 'Title'],
                ['key' => 'posted_by', 'label' => 'Posted By'],
                ['key' => 'available_from', 'label' => 'Available From'],
            ],
        ],
        'location' => [
            'title' => 'Location',
            'fields' => [
                ['key' => 'country_id', 'label' => 'Country'],
                ['key' => 'state_id', 'label' => 'State'],
                ['key' => 'city_id', 'label' => 'City'],
                ['key' => 'locality_id', 'label' => 'Locality'],
            ],
        ],
        'profile' => [
            'title' => 'Property Profile',
            'fields' => [
                ['key' => 'area_detail', 'label' => 'Area Details'],
                ['key' => 'area_unit', 'label' => 'Area Unit'],
                ['key' => 'bedrooms', 'label' => 'Bedrooms'],
                ['key' => 'bathrooms', 'label' => 'Bathrooms'],
                ['key' => 'furnishing', 'label' => 'Furnishing'],
                ['key' => 'property_age', 'label' => 'Property Age'],
                ['key' => 'facing', 'label' => 'Facing'],
            ],
        ],
        'pricing' => [
            'title' => 'Pricing',
            'fields' => [
                ['key' => 'pricing_value', 'label' => 'Price / Rent'],
                ['key' => 'deposit', 'label' => 'Security Deposit'],
                ['key' => 'negotiable', 'label' => 'Negotiable'],
            ],
        ],
        'amenities' => [
            'title' => 'Amenities',
            'fields' => [
                ['key' => 'amenities', 'label' => 'Amenities'],
            ],
        ],
        'media' => [
            'title' => 'Media',
            'fields' => [
                ['key' => 'image_count', 'label' => 'Images'],
            ],
        ],
    ];
}

function propertyStepOrder(): array
{
    return array_keys(propertyWizardStepDefinitions());
}

function propertyWizardUsers(): array
{
    $sql = "SELECT id, name, email, phone, role
            FROM users
            WHERE status != 'deleted'
            ORDER BY name ASC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function areaUnitOptions(): array
{
    return [
        'sq.ft' => 'sq.ft',
        'sq.yards' => 'sq.yards',
        'sq.m' => 'sq.m',
        'acres' => 'acres',
        'marla' => 'marla',
        'cents' => 'cents',
        'bigha' => 'bigha',
        'kottah' => 'kottah',
        'kanal' => 'kanal',
        'grounds' => 'grounds',
        'ares' => 'ares',
        'biswa' => 'biswa',
        'guntha' => 'guntha',
        'aankadam' => 'aankadam',
        'hectares' => 'hectares',
        'rood' => 'rood',
        'chataks' => 'chataks',
        'perch' => 'perch',
    ];
}

function areaUnitOptionsForCategory(string $category): array
{
    $all = areaUnitOptions();

    if ($category === 'land') {
        return $all;
    }

    return array_intersect_key($all, array_flip(['sq.ft', 'sq.yards', 'sq.m']));
}

function propertyAgeOptions(): array
{
    return [
        'new' => 'New Property',
        '0-1' => '0 to 1 Year',
        '1-3' => '1 to 3 Years',
        '3-5' => '3 to 5 Years',
        '5-10' => '5 to 10 Years',
        '10+' => '10+ Years',
    ];
}

function facingOptions(): array
{
    return [
        'east' => 'East',
        'west' => 'West',
        'north' => 'North',
        'south' => 'South',
        'north-east' => 'North East',
        'north-west' => 'North West',
        'south-east' => 'South East',
        'south-west' => 'South West',
    ];
}

function ownershipTypeOptions(): array
{
    return [
        'freehold' => 'Freehold',
        'leasehold' => 'Leasehold',
        'cooperative' => 'Co-operative Society',
        'power-of-attorney' => 'Power of Attorney',
    ];
}

function maintenancePeriodOptions(): array
{
    return [
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
    ];
}

function securityDepositTypeOptions(): array
{
    return [
        'fixed' => 'Fixed Amount',
        'multiple' => 'Multiple of Rent',
        'none' => 'None',
    ];
}

function brokerageTypeOptions(): array
{
    return [
        'fixed' => 'Fixed Amount',
        'percentage' => 'Percentage of Price',
        'none' => 'None',
    ];
}

function propertyPhotoTypeOptions(): array
{
    return [
        'front-view' => 'Front View',
        'living-room' => 'Living Room',
        'bedroom' => 'Bedroom',
        'kitchen' => 'Kitchen',
        'bathroom' => 'Bathroom',
        'balcony' => 'Balcony',
        'floor-plan' => 'Floor Plan',
        'amenities' => 'Amenities',
        'other' => 'Other',
    ];
}

function detectPropertyPhotoTypeFromFilename(string $filename): string
{
    $name = strtolower(pathinfo($filename, PATHINFO_FILENAME));
    $name = preg_replace('/[^a-z0-9]+/', ' ', $name) ?? $name;
    $keywords = [
        'floor-plan' => ['floor plan', 'floorplan', 'layout plan', 'site plan'],
        'bathroom' => ['bathroom', 'bath room', 'washroom', 'wash room', 'toilet'],
        'bedroom' => ['bedroom', 'bed room', 'master bed', 'guest room'],
        'living-room' => ['living room', 'drawing room', 'lounge', 'family room', 'hall'],
        'kitchen' => ['kitchen', 'pantry'],
        'balcony' => ['balcony', 'terrace', 'patio', 'verandah'],
        'front-view' => ['front view', 'front elevation', 'facade', 'exterior', 'entrance', 'building front'],
        'amenities' => ['amenity', 'amenities', 'clubhouse', 'club house', 'gym', 'pool', 'garden', 'parking', 'lobby'],
    ];

    foreach ($keywords as $type => $matches) {
        foreach ($matches as $match) {
            if (str_contains($name, $match)) {
                return $type;
            }
        }
    }

    return 'other';
}

function propertyFurnishingOptions(): array
{
    return [
        'unfurnished' => 'Unfurnished',
        'semi' => 'Semi furnished',
        'fully' => 'Fully furnished',
    ];
}

function propertyFurnishingLabel(?string $value): string
{
    $value = trim((string) $value);
    $options = propertyFurnishingOptions();

    return $options[$value] ?? '';
}

function propertyFurnishingItemOptions(): array
{
    return [
        'light_fan' => 'Lights & Fans',
        'wardrobe' => 'Wardrobe',
        'bed' => 'Bed',
        'sofa' => 'Sofa',
        'dining_table' => 'Dining Table',
        'modular_kitchen' => 'Modular Kitchen',
        'geyser' => 'Geyser',
        'ac' => 'Air Conditioner',
        'curtains' => 'Curtains',
        'tv' => 'TV',
        'fridge' => 'Fridge',
        'washing_machine' => 'Washing Machine',
        'water_purifier' => 'Water Purifier',
        'microwave' => 'Microwave',
        'study_table' => 'Study Table',
    ];
}

function normalizePropertyFurnishingItems(mixed $items, string $furnishing = ''): array
{
    if ($furnishing === 'unfurnished') {
        return [];
    }

    if (is_string($items)) {
        $decoded = json_decode($items, true);
        $items = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($items)) {
        return [];
    }

    $allowed = propertyFurnishingItemOptions();
    $normalized = [];

    foreach ($items as $item) {
        $key = trim((string) $item);
        if (array_key_exists($key, $allowed) && !in_array($key, $normalized, true)) {
            $normalized[] = $key;
        }
    }

    return $normalized;
}

function propertyFurnishingItemLabels(mixed $items): array
{
    $options = propertyFurnishingItemOptions();

    return array_values(array_filter(array_map(
        static fn (string $item): string => $options[$item] ?? '',
        normalizePropertyFurnishingItems($items)
    )));
}

function propertyFurnishingItemsStorageValue(mixed $items, string $furnishing): ?string
{
    $normalized = normalizePropertyFurnishingItems($items, $furnishing);

    if ($normalized === []) {
        return null;
    }

    $encoded = json_encode($normalized, JSON_UNESCAPED_SLASHES);

    return is_string($encoded) ? $encoded : null;
}

function normalizeAreaUnit(?string $unit): string
{
    $unit = trim((string) $unit);
    $units = areaUnitOptions();

    return array_key_exists($unit, $units) ? $unit : 'sq.ft';
}

function propertyAreaUnit(array $profile): string
{
    return normalizeAreaUnit((string) ($profile['area_unit'] ?? 'sq.ft'));
}

function propertyTypeCategoryFromBasic(array $basic): string
{
    $propertyTypeId = (int) ($basic['property_type_id'] ?? 0);
    $propertyType = $propertyTypeId > 0 ? findPropertyType($propertyTypeId) : null;

    return (string) ($propertyType['category'] ?? '');
}

function propertyTypeUsesCustomName(?array $propertyType): bool
{
    $name = strtolower(trim((string) ($propertyType['name'] ?? '')));

    return in_array($name, ['other commercial property', 'other land'], true);
}

function propertyTypeDisplayName(array $basic, ?array $propertyType = null): string
{
    $customName = trim((string) ($basic['custom_property_type'] ?? ''));
    $propertyType ??= !empty($basic['property_type_id'])
        ? findPropertyType((int) $basic['property_type_id'])
        : null;

    return propertyTypeUsesCustomName($propertyType) && $customName !== ''
        ? $customName
        : trim((string) ($propertyType['name'] ?? ''));
}

function isLandPropertyBasic(array $basic): bool
{
    return propertyTypeCategoryFromBasic($basic) === 'land';
}

function listingTypeNameById(int $listingTypeId): string
{
    $listingType = $listingTypeId > 0 ? findListingType($listingTypeId) : null;

    return strtolower(trim((string) ($listingType['name'] ?? '')));
}

function listingRequiresAvailabilityById(int $listingTypeId): bool
{
    $name = listingTypeNameById($listingTypeId);

    return $name !== '' && $name !== 'sell';
}

function listingRequiresAvailabilityBasic(array $basic): bool
{
    return listingRequiresAvailabilityById((int) ($basic['listing_type_id'] ?? 0));
}

function isSellListingBasic(array $basic): bool
{
    return listingTypeNameById((int) ($basic['listing_type_id'] ?? 0)) === 'sell';
}

function isOfficePropertyBasic(array $basic): bool
{
    $propertyType = !empty($basic['property_type_id']) ? findPropertyType((int) $basic['property_type_id']) : null;
    $name = strtolower(trim((string) ($propertyType['name'] ?? '')));

    return $name !== '' && (str_contains($name, 'office') || str_contains($name, 'co-working') || str_contains($name, 'coworking'));
}

function isPgListingBasic(array $basic): bool
{
    $name = listingTypeNameById((int) ($basic['listing_type_id'] ?? 0));

    return $name === 'pg' || str_contains($name, 'paying guest') || str_contains($name, 'co-living');
}

function propertyProfileDetails(array $profile): array
{
    $details = $profile['profile_details'] ?? [];

    if (is_string($details)) {
        $decoded = json_decode($details, true);
        $details = is_array($decoded) ? $decoded : [];
    }

    return is_array($details) ? $details : [];
}

function propertyOfficeFacilityOptions(): array
{
    return [
        'furnishing' => 'Furnishing',
        'central_air_conditioning' => 'Central Air Conditioning',
        'oxygen_duct' => 'Oxygen Duct',
        'ups' => 'UPS',
        'parking' => 'Parking',
    ];
}

function propertyOfficeFireSafetyOptions(): array
{
    return [
        'fire_extinguisher' => 'Fire Extinguisher',
        'fire_sensors' => 'Fire Sensors',
        'sprinklers' => 'Sprinklers',
        'fire_hose' => 'Fire Hose',
    ];
}

function normalizePropertyOfficeProfileDetails(array $input): array
{
    $status = static function (mixed $value): string {
        $value = trim((string) $value);
        return in_array($value, ['available', 'not_available'], true) ? $value : 'not_available';
    };
    $intValue = static function (array $source, string $key): ?int {
        $value = trim((string) ($source[$key] ?? ''));
        return $value !== '' && preg_match('/^\d+$/', $value) ? (int) $value : null;
    };
    $pantryType = trim((string) ($input['office_pantry_type'] ?? ''));
    $fireSafety = array_values(array_filter(array_map('strval', $input['office_fire_safety'] ?? [])));
    $allowedFireSafety = propertyOfficeFireSafetyOptions();

    return [
        'office' => [
            'min_seats' => $intValue($input, 'office_min_seats'),
            'max_seats' => $intValue($input, 'office_max_seats'),
            'cabins' => $intValue($input, 'office_cabins'),
            'meeting_rooms' => $intValue($input, 'office_meeting_rooms'),
            'washrooms' => $status($input['office_washrooms'] ?? ''),
            'private_washrooms' => $intValue($input, 'office_private_washrooms'),
            'shared_washrooms' => $intValue($input, 'office_shared_washrooms'),
            'conference_room' => $status($input['office_conference_room'] ?? ''),
            'reception_area' => $status($input['office_reception_area'] ?? ''),
            'pantry_type' => in_array($pantryType, ['private', 'shared', 'not_available'], true) ? $pantryType : 'not_available',
            'facilities' => [
                'furnishing' => $status($input['office_facility_furnishing'] ?? ''),
                'central_air_conditioning' => $status($input['office_facility_central_air_conditioning'] ?? ''),
                'oxygen_duct' => $status($input['office_facility_oxygen_duct'] ?? ''),
                'ups' => $status($input['office_facility_ups'] ?? ''),
                'parking' => $status($input['office_facility_parking'] ?? ''),
            ],
            'fire_safety' => array_values(array_filter($fireSafety, static fn (string $item): bool => array_key_exists($item, $allowedFireSafety))),
            'staircases' => $intValue($input, 'office_staircases'),
        ],
    ];
}

function propertyProfileDetailsStorageValue(array $details): ?string
{
    $encoded = json_encode($details, JSON_UNESCAPED_SLASHES);

    return is_string($encoded) ? $encoded : null;
}

function propertyOfficeProfileSummary(array $profile): array
{
    $office = propertyProfileDetails($profile)['office'] ?? [];

    return is_array($office) ? $office : [];
}

function propertyOfficeStatusLabel(mixed $value): string
{
    return trim((string) $value) === 'available' ? 'Available' : 'Not Available';
}

function normalizePropertyPgProfileDetails(array $input): array
{
    $intValue = static function (array $source, string $key): ?int {
        $value = trim((string) ($source[$key] ?? ''));
        return $value !== '' && preg_match('/^\d+$/', $value) ? (int) $value : null;
    };
    $roomType = trim((string) ($input['pg_room_type'] ?? ''));
    $availableFor = trim((string) ($input['pg_available_for'] ?? ''));
    $suitableFor = array_values(array_filter(array_map('strval', $input['pg_suitable_for'] ?? [])));
    $allowedSuitable = ['students', 'working_professionals'];

    return [
        'pg' => [
            'room_type' => in_array($roomType, ['sharing', 'private'], true) ? $roomType : '',
            'total_rooms' => $intValue($input, 'pg_total_rooms'),
            'available_rooms' => $intValue($input, 'pg_available_rooms'),
            'attached_bathroom' => isset($input['pg_attached_bathroom']) ? 1 : 0,
            'attached_balcony' => isset($input['pg_attached_balcony']) ? 1 : 0,
            'store_room' => isset($input['pg_store_room']) ? 1 : 0,
            'covered_parking' => $intValue($input, 'pg_covered_parking'),
            'open_parking' => $intValue($input, 'pg_open_parking'),
            'common_area_furnishing' => isset($input['pg_common_area_furnishing']) ? 1 : 0,
            'available_for' => in_array($availableFor, ['girls', 'boys', 'any'], true) ? $availableFor : '',
            'suitable_for' => array_values(array_filter($suitableFor, static fn (string $item): bool => in_array($item, $allowedSuitable, true))),
        ],
    ];
}

function propertyPgProfileSummary(array $profile): array
{
    $pg = propertyProfileDetails($profile)['pg'] ?? [];

    return is_array($pg) ? $pg : [];
}

function propertyPgAvailableForLabel(mixed $value): string
{
    return match (trim((string) $value)) {
        'girls' => 'Girls',
        'boys' => 'Boys',
        'any' => 'Any',
        default => '',
    };
}

function propertyPgSuitableForLabels(mixed $items): array
{
    $items = is_array($items) ? $items : [];
    $labels = [
        'students' => 'Students',
        'working_professionals' => 'Working Professionals',
    ];

    return array_values(array_filter(array_map(static fn (string $item): string => $labels[$item] ?? '', array_map('strval', $items))));
}

function formatNumberIndian(float $number): string
{
    $negative = $number < 0;
    $absolute = abs($number);
    $formatted = number_format($absolute, 2, '.', '');
    [$integerPart, $decimalPart] = array_pad(explode('.', $formatted, 2), 2, '00');

    if (strlen($integerPart) > 3) {
        $lastThree = substr($integerPart, -3);
        $otherNumbers = substr($integerPart, 0, -3);
        $integerPart = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $otherNumbers) . ',' . $lastThree;
    }

    $result = rtrim(rtrim($integerPart . '.' . $decimalPart, '0'), '.');

    return $negative ? '-' . $result : $result;
}

function propertyFormatIndianCurrency(mixed $amount): string
{
    $numeric = (float) $amount;

    if ($numeric <= 0) {
        return '';
    }

    return '₹' . formatNumberIndian($numeric);
}

function propertyDescriptionLocation(array $location): string
{
    $parts = [];
    $locality = isset($location['locality_id']) ? findLocality((int) $location['locality_id']) : null;
    $city = isset($location['city_id']) ? findCity((int) $location['city_id']) : null;
    $state = isset($location['state_id']) ? findState((int) $location['state_id']) : null;

    foreach ([$locality['name'] ?? '', $city['name'] ?? '', $state['name'] ?? ''] as $part) {
        $part = trim((string) $part);

        if ($part !== '' && !in_array($part, $parts, true)) {
            $parts[] = $part;
        }
    }

    return implode(', ', $parts);
}

function propertyDescriptionArea(array $profile): string
{
    $areaUnit = propertyAreaUnit($profile);
    $map = [
        'super_builtup_area' => 'super built-up area',
        'builtup_area' => 'built-up area',
        'carpet_area' => 'carpet area',
        'plot_area' => 'plot area',
    ];

    foreach ($map as $key => $label) {
        $value = trim((string) ($profile[$key] ?? ''));

        if ($value !== '') {
            return formatNumberIndian((float) $value) . ' ' . $areaUnit . ' ' . $label;
        }
    }

    return '';
}

function propertyDescriptionRooms(array $profile, bool $isLand): string
{
    if ($isLand) {
        return '';
    }

    $parts = [];

    if ((string) ($profile['bedrooms'] ?? '') !== '') {
        $parts[] = (string) $profile['bedrooms'] . ' bedroom' . ((int) $profile['bedrooms'] !== 1 ? 's' : '');
    }

    if ((string) ($profile['bathrooms'] ?? '') !== '') {
        $parts[] = (string) $profile['bathrooms'] . ' bathroom' . ((int) $profile['bathrooms'] !== 1 ? 's' : '');
    }

    if ((string) ($profile['balconies'] ?? '') !== '') {
        $count = (int) $profile['balconies'];
        $parts[] = (string) $profile['balconies'] . ' ' . ($count === 1 ? 'balcony' : 'balconies');
    }

    return implode(', ', $parts);
}

function propertyDescriptionAmenities(array $amenityIds): string
{
    $names = [];

    foreach (array_slice($amenityIds, 0, 5) as $amenityId) {
        $amenity = findAmenity((int) $amenityId);
        $name = trim((string) ($amenity['name'] ?? ''));

        if ($name !== '') {
            $names[] = $name;
        }
    }

    return implode(', ', $names);
}

function propertyDescriptionPricing(array $basic, array $pricing): string
{
    if (isSellListingBasic($basic)) {
        $price = propertyFormatIndianCurrency($pricing['expected_price'] ?? null);

        if ($price === '') {
            return '';
        }

        $suffix = (int) ($pricing['negotiable'] ?? 0) === 1 ? ' and the price is negotiable.' : '.';
        return 'The quoted sale price is ' . $price . $suffix;
    }

    $rent = propertyFormatIndianCurrency($pricing['rent'] ?? null);

    if ($rent === '') {
        return '';
    }

    $depositType = trim((string) ($pricing['security_deposit_type'] ?? ''));
    $depositText = '';

    if ($depositType === 'fixed') {
        $depositAmount = propertyFormatIndianCurrency($pricing['security_deposit_amount'] ?? null);
        $depositText = $depositAmount !== '' ? ' with a fixed security deposit of ' . $depositAmount : '';
    } elseif ($depositType === 'multiple') {
        $depositMonths = (int) ($pricing['security_deposit_months'] ?? 0);
        $depositText = $depositMonths > 0
            ? ' with a security deposit of ' . $depositMonths . ' month' . ($depositMonths > 1 ? 's' : '') . ' rent'
            : '';
    } elseif ($depositType === '') {
        $depositMonths = (int) ($pricing['deposit'] ?? 0);
        $depositText = $depositMonths > 0
            ? ' with a security deposit of ' . $depositMonths . ' month' . ($depositMonths > 1 ? 's' : '')
            : '';
    }
    $suffix = (int) ($pricing['negotiable'] ?? 0) === 1 ? ', and the terms are negotiable.' : '.';

    return 'Monthly rent is ' . $rent . $depositText . $suffix;
}

function propertyDescriptionAvailability(array $basic): string
{
    $availableFrom = trim((string) ($basic['available_from'] ?? ''));

    if ($availableFrom === '') {
        return '';
    }

    $timestamp = strtotime($availableFrom);

    if ($timestamp === false) {
        return '';
    }

    return 'Available from ' . date('d M Y', $timestamp) . '.';
}

function propertyDescriptionFeatureLine(array $bundle): string
{
    $profile = $bundle['profile'];
    $features = [];
    $isOffice = isOfficePropertyBasic($bundle['basic']);
    $isPg = isPgListingBasic($bundle['basic']);

    if (trim((string) ($profile['furnishing'] ?? '')) !== '') {
        $features[] = propertyFurnishingLabel((string) $profile['furnishing']);
        $furnishingItems = propertyFurnishingItemLabels($profile['furnishing_items'] ?? []);
        if ($furnishingItems !== []) {
            $features[] = 'includes ' . implode(', ', array_slice($furnishingItems, 0, 5));
        }
    }

    if (trim((string) ($profile['property_age'] ?? '')) !== '') {
        $ageOptions = propertyAgeOptions();
        $features[] = $ageOptions[(string) $profile['property_age']] ?? (string) $profile['property_age'];
    }

    if (trim((string) ($profile['facing'] ?? '')) !== '') {
        $facingOptions = facingOptions();
        $features[] = ($facingOptions[(string) $profile['facing']] ?? ucfirst((string) $profile['facing'])) . ' facing';
    }

    if (trim((string) ($profile['ownership_type'] ?? '')) !== '') {
        $features[] = ucfirst((string) $profile['ownership_type']) . ' ownership';
    }

    if (trim((string) ($profile['parking_count'] ?? '')) !== '') {
        $features[] = (string) $profile['parking_count'] . ' parking slot' . ((int) $profile['parking_count'] !== 1 ? 's' : '');
    }

    if ($isOffice) {
        $office = propertyOfficeProfileSummary($profile);
        if (!empty($office['min_seats'])) {
            $features[] = (string) $office['min_seats'] . (!empty($office['max_seats']) ? '-' . (string) $office['max_seats'] : '+') . ' seats';
        }
        if (!empty($office['cabins'])) {
            $features[] = (string) $office['cabins'] . ' cabin' . ((int) $office['cabins'] !== 1 ? 's' : '');
        }
        if (($office['facilities']['central_air_conditioning'] ?? '') === 'available') {
            $features[] = 'central AC';
        }
    }
    if ($isPg) {
        $pg = propertyPgProfileSummary($profile);
        if (($pg['room_type'] ?? '') !== '') {
            $features[] = ucfirst((string) $pg['room_type']) . ' room';
        }
        if (!empty($pg['available_rooms'])) {
            $features[] = (string) $pg['available_rooms'] . ' room' . ((int) $pg['available_rooms'] !== 1 ? 's' : '') . ' available';
        }
        $availableFor = propertyPgAvailableForLabel($pg['available_for'] ?? '');
        if ($availableFor !== '') {
            $features[] = 'available for ' . $availableFor;
        }
    }

    return implode(', ', $features);
}

function propertyDescriptionTemplatesFromBundle(array $bundle): array
{
    $basic = $bundle['basic'];
    $profile = $bundle['profile'];
    $location = $bundle['location'];
    $pricing = $bundle['pricing'];
    $propertyType = findPropertyType((int) ($basic['property_type_id'] ?? 0));
    $propertyTypeName = propertyTypeDisplayName($basic, $propertyType);
    $typeLabel = $propertyTypeName !== '' ? $propertyTypeName : 'Property';
    $listingType = findListingType((int) ($basic['listing_type_id'] ?? 0));
    $listingLabel = trim((string) ($listingType['name'] ?? 'Listing'));
    $isLand = isLandPropertyBasic($basic);
    $locationText = propertyDescriptionLocation($location);
    $areaText = propertyDescriptionArea($profile);
    $roomsText = propertyDescriptionRooms($profile, $isLand);
    $amenitiesText = propertyDescriptionAmenities($bundle['amenity_ids']);
    $pricingText = propertyDescriptionPricing($basic, $pricing);
    $availabilityText = propertyDescriptionAvailability($basic);
    $featureLine = propertyDescriptionFeatureLine($bundle);
    $purposeNote = trim((string) ($basic['purpose_note'] ?? ''));

    $headline = trim(implode(' ', array_filter([
        $typeLabel,
        'for',
        $listingLabel !== '' ? $listingLabel : 'Listing',
        $locationText !== '' ? 'in ' . $locationText : '',
    ])));

    $templateOne = trim(implode(' ', array_filter([
        'Presenting a well-planned ' . strtolower($typeLabel) . ($locationText !== '' ? ' in ' . $locationText . '.' : '.'),
        $areaText !== '' ? 'The property offers ' . $areaText . '.' : '',
        $roomsText !== '' ? 'It includes ' . $roomsText . ' for practical day-to-day living.' : '',
        $featureLine !== '' ? 'Additional highlights include ' . strtolower($featureLine) . '.' : '',
        $amenitiesText !== '' ? 'Key amenities around this listing include ' . $amenitiesText . '.' : '',
        $pricingText,
        $availabilityText,
        $purposeNote !== '' ? 'A key advantage of this property is that it is ' . $purposeNote . '.' : '',
    ])));

    $templateTwo = trim(implode(' ', array_filter([
        'This ' . strtolower($typeLabel) . ($locationText !== '' ? ' is strategically located in ' . $locationText . ',' : '') . ' making it a strong choice for buyers or tenants looking for a well-connected address.',
        $areaText !== '' ? 'Spread across ' . $areaText . ',' : '',
        $roomsText !== '' ? 'the home is designed with ' . $roomsText . '.' : 'the layout is planned for functional use and day-to-day comfort.',
        $featureLine !== '' ? 'It also benefits from ' . strtolower($featureLine) . '.' : '',
        $amenitiesText !== '' ? 'Residents can enjoy conveniences such as ' . $amenitiesText . '.' : '',
        $pricingText,
        $availabilityText,
    ])));

    $templateThree = trim(implode(' ', array_filter([
        $headline !== '' ? $headline . ' is now available.' : '',
        $areaText !== '' ? 'Configured with ' . $areaText . ',' : '',
        $roomsText !== '' ? 'it features ' . $roomsText . '.' : 'it offers a clean and versatile layout.',
        $featureLine !== '' ? 'The property stands out with ' . strtolower($featureLine) . '.' : '',
        $purposeNote !== '' ? 'It is especially appealing because it is ' . $purposeNote . '.' : '',
        $amenitiesText !== '' ? 'Nearby and in-project advantages include ' . $amenitiesText . '.' : '',
        $pricingText,
        $availabilityText,
    ])));

    return [
        ['id' => 'professional', 'title' => 'Professional Pitch', 'content' => $templateOne],
        ['id' => 'location', 'title' => 'Location-Focused Pitch', 'content' => $templateTwo],
        ['id' => 'premium', 'title' => 'Premium Marketing Pitch', 'content' => $templateThree],
    ];
}

function propertyDescriptionTemplates(int $draftId): array
{
    return propertyDescriptionTemplatesFromBundle(getPropertyDraftBundle($draftId));
}

function resolvedPricePerAreaUnit(array $pricing): mixed
{
    if (array_key_exists('price_per_area_unit', $pricing) && $pricing['price_per_area_unit'] !== null && $pricing['price_per_area_unit'] !== '') {
        return $pricing['price_per_area_unit'];
    }

    return $pricing['price_per_sqft'] ?? null;
}

function propertyDraftsAll(): array
{
    $sql = "SELECT pd.id, pd.user_id, pd.current_step, pd.completion_percent, pd.is_submitted, pd.updated_at,
                   u.name AS user_name,
                   pb.title,
                   pb.posted_by,
                   COALESCE(NULLIF(pb.custom_property_type, ''), pt.name) AS property_type_name,
                   lt.name AS listing_type_name,
                   p.id AS property_id,
                   p.status AS property_status
            FROM property_drafts pd
            LEFT JOIN users u ON u.id = pd.user_id
            LEFT JOIN property_basic pb ON pb.draft_id = pd.id
            LEFT JOIN property_types pt ON pt.id = pb.property_type_id
            LEFT JOIN listing_types lt ON lt.id = pb.listing_type_id
            LEFT JOIN properties p ON p.draft_id = pd.id
            ORDER BY pd.updated_at DESC, pd.id DESC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function propertyDraftSummary(): array
{
    $summary = [
        'drafts' => 0,
        'submitted' => 0,
        'published' => 0,
        'rejected' => 0,
        'avg_completion' => '0',
    ];

    try {
        $summary['drafts'] = (int) db()->query('SELECT COUNT(*) FROM property_drafts')->fetchColumn();
        $summary['submitted'] = (int) db()->query('SELECT COUNT(*) FROM property_drafts WHERE is_submitted = 1')->fetchColumn();
        $summary['published'] = (int) db()->query("SELECT COUNT(*) FROM properties WHERE status = 'active'")->fetchColumn();
        $summary['rejected'] = (int) db()->query("SELECT COUNT(*) FROM properties WHERE status = 'rejected'")->fetchColumn();
        $average = db()->query('SELECT AVG(completion_percent) FROM property_drafts')->fetchColumn();
        $summary['avg_completion'] = number_format((float) $average, 0);
    } catch (Throwable $exception) {
        return $summary;
    }

    return $summary;
}

function propertyDraftSummaryPayload(): array
{
    $summary = propertyDraftSummary();

    return [
        'drafts' => (string) $summary['drafts'],
        'submitted' => (string) $summary['submitted'],
        'published' => (string) $summary['published'],
        'rejected' => (string) $summary['rejected'],
        'avg_completion' => (string) $summary['avg_completion'],
    ];
}

function findPropertyDraftListRow(int $draftId): ?array
{
    $stmt = db()->prepare(
        "SELECT pd.id, pd.user_id, pd.current_step, pd.completion_percent, pd.is_submitted, pd.updated_at,
                u.name AS user_name,
                pb.title,
                pb.posted_by,
                COALESCE(NULLIF(pb.custom_property_type, ''), pt.name) AS property_type_name,
                lt.name AS listing_type_name,
                p.id AS property_id,
                p.status AS property_status
         FROM property_drafts pd
         LEFT JOIN users u ON u.id = pd.user_id
         LEFT JOIN property_basic pb ON pb.draft_id = pd.id
         LEFT JOIN property_types pt ON pt.id = pb.property_type_id
         LEFT JOIN listing_types lt ON lt.id = pb.listing_type_id
         LEFT JOIN properties p ON p.draft_id = pd.id
         WHERE pd.id = :id
         LIMIT 1"
    );
    $stmt->execute([':id' => $draftId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function propertyDraftStatusHtml(array $draft): string
{
    $meta = propertyDraftStatusMeta($draft);
    return '<span class="' . e($meta['class']) . '">' . e($meta['label']) . '</span>';
}

function propertyDraftStatusMeta(array $draft): array
{
    if ((int) ($draft['is_submitted'] ?? 0) !== 1) {
        return [
            'label' => 'Draft',
            'class' => 'status-pill draft',
        ];
    }

    $status = strtolower(trim((string) ($draft['property_status'] ?? 'pending')));

    return match ($status) {
        'active' => ['label' => 'Published', 'class' => 'status-pill submitted'],
        'rejected' => ['label' => 'Rejected', 'class' => 'status-pill blocked'],
        'inactive' => ['label' => 'Inactive', 'class' => 'status-pill deleted'],
        'booked' => ['label' => 'Booked', 'class' => 'status-pill review'],
        'sold' => ['label' => 'Sold', 'class' => 'status-pill submitted'],
        'rented' => ['label' => 'Rented', 'class' => 'status-pill submitted'],
        'occupied' => ['label' => 'Occupied', 'class' => 'status-pill review'],
        'deleted' => ['label' => 'Deleted', 'class' => 'status-pill deleted'],
        default => ['label' => 'Pending Review', 'class' => 'status-pill review'],
    };
}

function canApprovePropertyDraft(array $draft): bool
{
    if ((int) ($draft['is_submitted'] ?? 0) !== 1) {
        return false;
    }

    return (string) ($draft['property_status'] ?? '') !== 'active';
}

function canDeletePropertyDraft(array $draft): bool
{
    return (string) ($draft['property_status'] ?? '') !== 'active';
}

function canRejectPropertyDraft(array $draft): bool
{
    if ((int) ($draft['is_submitted'] ?? 0) !== 1) {
        return false;
    }

    return (string) ($draft['property_status'] ?? '') !== 'active';
}

function propertyReviewUrl(int $draftId, string $fragment = ''): string
{
    return ADMIN_URL . '/properties/review.php?draft_id=' . $draftId . $fragment;
}

function propertyListActionsHtml(array $draft): string
{
    $id = (int) ($draft['id'] ?? 0);
    $html = '<div class="table-actions">';
    $html .= '<a class="btn btn-sm btn-outline-primary icon-action-btn" href="' . propertyReviewUrl($id) . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Review property" aria-label="Review property"><i class="bi bi-eye" aria-hidden="true"></i></a>';
    $html .= '<a class="btn btn-sm btn-outline-dark icon-action-btn" href="' . ADMIN_URL . '/properties/wizard.php?draft_id=' . $id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Open property wizard" aria-label="Open property wizard"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>';

    if (canApprovePropertyDraft($draft)) {
        $html .= '<form method="post" action="' . ADMIN_URL . '/properties/approve.php" data-confirm="Approve this property listing?" data-confirm-button="Approve" data-loading-text="Approving listing..." data-async="true" data-row-id="' . $id . '">';
        $html .= '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
        $html .= '<input type="hidden" name="draft_id" value="' . $id . '">';
        $html .= '<button class="btn btn-sm btn-outline-success icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Approve property" aria-label="Approve property"><i class="bi bi-check2-circle" aria-hidden="true"></i></button>';
        $html .= '</form>';
    }

    if (canRejectPropertyDraft($draft)) {
        $html .= '<a class="btn btn-sm btn-outline-danger icon-action-btn" href="' . propertyReviewUrl($id, '#moderation') . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Open reject form" aria-label="Open reject form"><i class="bi bi-x-octagon" aria-hidden="true"></i></a>';
    }

    if (canDeletePropertyDraft($draft)) {
        $html .= '<form method="post" action="' . ADMIN_URL . '/properties/delete.php" data-confirm="Delete this draft property?" data-confirm-button="Delete" data-loading-text="Deleting draft..." data-async="true" data-row-id="' . $id . '">';
        $html .= '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
        $html .= '<input type="hidden" name="draft_id" value="' . $id . '">';
        $html .= '<button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete property" aria-label="Delete property"><i class="bi bi-trash3" aria-hidden="true"></i></button>';
        $html .= '</form>';
    }

    $html .= '</div>';

    return $html;
}

function createPropertyDraft(int $userId = 0): int
{
    $stmt = db()->prepare('INSERT INTO property_drafts (user_id, current_step, completion_percent, is_submitted, created_at, updated_at) VALUES (:user_id, 1, 0, 0, NOW(), NOW())');
    $stmt->execute([
        ':user_id' => $userId > 0 ? $userId : null,
    ]);

    return (int) db()->lastInsertId();
}

function findPropertyDraft(int $draftId): ?array
{
    $stmt = db()->prepare('SELECT * FROM property_drafts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $draftId]);
    $draft = $stmt->fetch();

    return $draft ?: null;
}

function findPropertyByDraftId(int $draftId): ?array
{
    $columns = ['id', 'draft_id', 'user_id', 'slug', 'status', 'created_at'];

    foreach (['published_at', 'rejected_reason', 'updated_at'] as $optionalColumn) {
        if (tableHasColumn('properties', $optionalColumn)) {
            $columns[] = $optionalColumn;
        }
    }

    $stmt = db()->prepare('SELECT ' . implode(', ', $columns) . ' FROM properties WHERE draft_id = :draft_id LIMIT 1');
    $stmt->execute([':draft_id' => $draftId]);
    $property = $stmt->fetch();

    return $property ?: null;
}

function draftSectionRow(string $table, int $draftId): ?array
{
    $allowedTables = ['property_basic', 'property_location', 'property_profile', 'property_pricing'];

    if (!in_array($table, $allowedTables, true)) {
        return null;
    }

    $stmt = db()->prepare("SELECT * FROM {$table} WHERE draft_id = :draft_id LIMIT 1");
    $stmt->execute([':draft_id' => $draftId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function propertyDraftMedia(int $draftId): array
{
    $orderBy = tableHasColumn('property_media', 'sort_order')
        ? 'ORDER BY is_primary DESC, sort_order ASC, id ASC'
        : 'ORDER BY is_primary DESC, id ASC';
    $columns = ['id', 'draft_id', 'file_url', 'type', 'is_primary'];

    if (tableHasColumn('property_media', 'title')) {
        $columns[] = 'title';
    }

    if (tableHasColumn('property_media', 'mime_type')) {
        $columns[] = 'mime_type';
    }

    $sql = 'SELECT ' . implode(', ', $columns) . '
            FROM property_media
            WHERE draft_id = :draft_id
            ' . $orderBy;

    $stmt = db()->prepare($sql);
    $stmt->execute([':draft_id' => $draftId]);
    $rows = $stmt->fetchAll();

    return array_map('hydratePropertyMediaItem', $rows);
}

function propertyNormalizeMediaUrl(string $url): string
{
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    $path = parse_url($url, PHP_URL_PATH);
    $position = is_string($path) ? strpos($path, '/uploads/') : false;

    return $position !== false ? APP_URL . substr($path, $position) : $url;
}

function propertyDraftAmenityIds(int $draftId): array
{
    $stmt = db()->prepare('SELECT amenity_id FROM property_amenities WHERE draft_id = :draft_id');
    $stmt->execute([':draft_id' => $draftId]);

    return array_map(static fn ($row) => (int) $row['amenity_id'], $stmt->fetchAll());
}

function hydratePropertyMediaItem(array $row): array
{
    $fileUrl = propertyNormalizeMediaUrl((string) ($row['file_url'] ?? ''));
    $type = (string) ($row['type'] ?? '');
    $youtubeId = extractYoutubeId($fileUrl);
    $kind = $youtubeId ? 'youtube' : $type;

    return [
        'id' => (int) ($row['id'] ?? 0),
        'draft_id' => (int) ($row['draft_id'] ?? 0),
        'file_url' => $fileUrl,
        'type' => $type,
        'kind' => $kind,
        'is_primary' => (int) ($row['is_primary'] ?? 0),
        'title' => (string) ($row['title'] ?? ''),
        'mime_type' => (string) ($row['mime_type'] ?? ''),
        'youtube_id' => $youtubeId,
    ];
}

function getPropertyDraftBundle(int $draftId): array
{
    $draft = findPropertyDraft($draftId);

    if (!$draft) {
        throw new RuntimeException('Draft not found.');
    }

    $bundle = [
        'draft' => $draft,
        'basic' => draftSectionRow('property_basic', $draftId) ?? [],
        'location' => draftSectionRow('property_location', $draftId) ?? [],
        'profile' => draftSectionRow('property_profile', $draftId) ?? [],
        'pricing' => draftSectionRow('property_pricing', $draftId) ?? [],
        'amenity_ids' => propertyDraftAmenityIds($draftId),
        'media' => propertyDraftMedia($draftId),
    ];

    $bundle['progress'] = propertyDraftProgress($bundle);

    return $bundle;
}

function valueFilled(mixed $value): bool
{
    if (is_array($value)) {
        return $value !== [];
    }

    if (is_bool($value)) {
        return true;
    }

    if ($value === null) {
        return false;
    }

    return trim((string) $value) !== '';
}

function propertyDraftProgress(array $bundle): array
{
    $steps = propertyWizardStepDefinitions();
    $propertyCategory = propertyTypeCategoryFromBasic($bundle['basic']);
    $residentialProperty = $propertyCategory === 'residential';
    $buildingProperty = in_array($propertyCategory, ['residential', 'commercial'], true);
    $officeProperty = isOfficePropertyBasic($bundle['basic']);
    $pgListing = isPgListingBasic($bundle['basic']);
    $furnishing = (string) ($bundle['profile']['furnishing'] ?? '');
    $requiresFurnishingItems = in_array($furnishing, ['semi', 'fully'], true) && tableHasColumn('property_profile', 'furnishing_items');

    if ($requiresFurnishingItems) {
        $steps['profile']['fields'][] = ['key' => 'furnishing_items', 'label' => 'Furnishing Includes'];
    }
    if ($officeProperty && tableHasColumn('property_profile', 'profile_details')) {
        $steps['profile']['fields'][] = ['key' => 'office_min_seats', 'label' => 'Minimum Seats'];
        $steps['profile']['fields'][] = ['key' => 'office_cabins', 'label' => 'Cabins'];
        $steps['profile']['fields'][] = ['key' => 'office_meeting_rooms', 'label' => 'Meeting Rooms'];
    }
    if ($pgListing && tableHasColumn('property_profile', 'profile_details')) {
        $steps['profile']['fields'][] = ['key' => 'pg_room_type', 'label' => 'PG Room Type'];
        $steps['profile']['fields'][] = ['key' => 'pg_available_for', 'label' => 'Available For'];
    }

    $officeDetails = propertyOfficeProfileSummary($bundle['profile']);
    $pgDetails = propertyPgProfileSummary($bundle['profile']);

    $checkValues = [
        'basic' => [
            'user_id' => $bundle['draft']['user_id'] ?? null,
            'property_type_id' => $bundle['basic']['property_type_id'] ?? null,
            'listing_type_id' => $bundle['basic']['listing_type_id'] ?? null,
            'title' => $bundle['basic']['title'] ?? null,
            'posted_by' => $bundle['basic']['posted_by'] ?? null,
            'available_from' => listingRequiresAvailabilityBasic($bundle['basic']) ? ($bundle['basic']['available_from'] ?? null) : '__optional__',
        ],
        'location' => [
            'country_id' => $bundle['location']['country_id'] ?? null,
            'state_id' => $bundle['location']['state_id'] ?? null,
            'city_id' => $bundle['location']['city_id'] ?? null,
            'locality_id' => $bundle['location']['locality_id'] ?? null,
        ],
        'profile' => [
            'area_detail' => $propertyCategory === 'land'
                ? ($bundle['profile']['plot_area'] ?? null)
                : (($bundle['profile']['super_builtup_area'] ?? null) ?: (($bundle['profile']['builtup_area'] ?? null) ?: (($bundle['profile']['carpet_area'] ?? null) ?: ($bundle['profile']['plot_area'] ?? null)))),
            'area_unit' => array_key_exists(propertyAreaUnit($bundle['profile']), areaUnitOptionsForCategory($propertyCategory))
                ? propertyAreaUnit($bundle['profile'])
                : null,
            'bedrooms' => $residentialProperty ? ($bundle['profile']['bedrooms'] ?? null) : '__optional__',
            'bathrooms' => $residentialProperty ? ($bundle['profile']['bathrooms'] ?? null) : '__optional__',
            'furnishing' => $residentialProperty ? ($bundle['profile']['furnishing'] ?? null) : '__optional__',
            'furnishing_items' => $requiresFurnishingItems ? normalizePropertyFurnishingItems($bundle['profile']['furnishing_items'] ?? [], $furnishing) : '__optional__',
            'office_min_seats' => $officeProperty ? ($officeDetails['min_seats'] ?? null) : '__optional__',
            'office_cabins' => $officeProperty ? ($officeDetails['cabins'] ?? null) : '__optional__',
            'office_meeting_rooms' => $officeProperty ? ($officeDetails['meeting_rooms'] ?? null) : '__optional__',
            'pg_room_type' => $pgListing ? ($pgDetails['room_type'] ?? null) : '__optional__',
            'pg_available_for' => $pgListing ? ($pgDetails['available_for'] ?? null) : '__optional__',
            'property_age' => ($buildingProperty && !$officeProperty) ? ($bundle['profile']['property_age'] ?? null) : '__optional__',
            'facing' => !$officeProperty ? ($bundle['profile']['facing'] ?? null) : '__optional__',
            'ownership_type' => '__optional__',
        ],
        'pricing' => [
            'pricing_value' => resolvedPricingValue($bundle['basic'], $bundle['pricing']),
            'deposit' => '__optional__',
            'negotiable' => array_key_exists('negotiable', $bundle['pricing']) ? (string) $bundle['pricing']['negotiable'] : null,
        ],
        'amenities' => [
            'amenities' => $bundle['amenity_ids'],
        ],
        'media' => [
            'image_count' => propertyImageCount($bundle['media']),
        ],
    ];

    $totalFields = 0;
    $filledFields = 0;
    $stepMeta = [];
    $missing = [];

    foreach ($steps as $key => $step) {
        $fields = $step['fields'];
        $stepTotal = count($fields);
        $stepFilled = 0;
        $stepMissing = [];

        foreach ($fields as $field) {
            $fieldValue = $checkValues[$key][$field['key']] ?? null;
            $isFilled = false;

            if ($field['key'] === 'image_count') {
                $isFilled = (int) $fieldValue >= 1;
            } elseif ($field['key'] === 'video_presence') {
                $isFilled = valueFilled($fieldValue);
            } else {
                $isFilled = valueFilled($fieldValue);
            }

            if ($isFilled) {
                $stepFilled++;
                $filledFields++;
            } else {
                $stepMissing[] = $field['label'];
                $missing[] = $step['title'] . ': ' . $field['label'];
            }

            $totalFields++;
        }

        $stepMeta[$key] = [
            'title' => $step['title'],
            'filled' => $stepFilled,
            'total' => $stepTotal,
            'percent' => $stepTotal > 0 ? (int) round(($stepFilled / $stepTotal) * 100) : 0,
            'missing' => $stepMissing,
        ];
    }

    $overallPercent = $totalFields > 0 ? round(($filledFields / $totalFields) * 100, 2) : 0.0;

    return [
        'overall_percent' => $overallPercent,
        'filled_fields' => $filledFields,
        'total_fields' => $totalFields,
        'step_meta' => $stepMeta,
        'missing' => $missing,
        'image_count' => propertyImageCount($bundle['media']),
    ];
}

function resolvedPricingValue(array $basic, array $pricing): mixed
{
    if (isSellListingBasic($basic)) {
        return $pricing['expected_price'] ?? null;
    }

    return $pricing['rent'] ?? null;
}

function propertyImageCount(array $media): int
{
    return count(array_filter($media, static fn ($item) => ($item['kind'] ?? '') === 'image'));
}

function propertyVideoPresence(array $media): bool
{
    foreach ($media as $item) {
        if (in_array($item['kind'] ?? '', ['video', 'youtube'], true)) {
            return true;
        }
    }

    return false;
}

function propertyMediaGridHtml(array $mediaItems): string
{
    return implode('', array_map('propertyMediaCardHtml', $mediaItems));
}

function propertyVideoMaxBytes(): int
{
    return 20 * 1024 * 1024;
}

function propertyImageMaxBytes(): int
{
    return 10 * 1024 * 1024;
}

function propertyImageMaxPixels(): int
{
    return 40_000_000;
}

function propertyAllowedImageMimes(): array
{
    return [
        'image/jpeg' => IMAGETYPE_JPEG,
        'image/png' => IMAGETYPE_PNG,
        'image/gif' => IMAGETYPE_GIF,
        'image/webp' => IMAGETYPE_WEBP,
    ];
}

function propertyAllowedVideoMimes(): array
{
    return [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];
}

function propertyUploadErrorMessage(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file is too large.',
        UPLOAD_ERR_PARTIAL => 'The file upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'The server could not accept the uploaded file.',
        default => 'The file upload failed.',
    };
}

function inspectPropertyMediaUpload(array $file, string $expectedKind): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_OK);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(propertyUploadErrorMessage($error));
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_file($temporaryPath)) {
        throw new RuntimeException('The uploaded file is missing.');
    }
    if (PHP_SAPI !== 'cli' && !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The file was not received through a valid upload.');
    }

    $size = (int) ($file['size'] ?? filesize($temporaryPath) ?: 0);
    if ($size <= 0) {
        throw new RuntimeException('Empty files cannot be uploaded.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = strtolower((string) $finfo->file($temporaryPath));

    if ($expectedKind === 'image') {
        $allowedImages = propertyAllowedImageMimes();
        if (!isset($allowedImages[$mime])) {
            throw new RuntimeException('Only JPEG, PNG, GIF, or WebP images are allowed.');
        }
        if ($size > propertyImageMaxBytes()) {
            throw new RuntimeException('Image size must be 10 MB or less.');
        }

        $imageInfo = getimagesize($temporaryPath);
        if ($imageInfo === false || (int) ($imageInfo[2] ?? 0) !== $allowedImages[$mime]) {
            throw new RuntimeException('The image signature does not match its format.');
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        if ($width <= 0 || $height <= 0 || $width > 10000 || $height > 10000 || $width * $height > propertyImageMaxPixels()) {
            throw new RuntimeException('Image dimensions are too large.');
        }

        return ['kind' => 'image', 'mime' => $mime, 'size' => $size, 'width' => $width, 'height' => $height];
    }

    if ($expectedKind === 'video') {
        $allowedVideos = propertyAllowedVideoMimes();
        if (!isset($allowedVideos[$mime])) {
            throw new RuntimeException('Only MP4, WebM, or MOV videos are allowed.');
        }
        if ($size > propertyVideoMaxBytes()) {
            throw new RuntimeException('Video size must be 20 MB or less.');
        }

        return ['kind' => 'video', 'mime' => $mime, 'size' => $size, 'extension' => $allowedVideos[$mime]];
    }

    throw new RuntimeException('Unsupported upload type.');
}

function updatePropertyMediaTitle(int $draftId, int $mediaId, string $title): void
{
    if (!tableHasColumn('property_media', 'title')) {
        return;
    }

    $allowed = propertyPhotoTypeOptions();
    $normalizedTitle = array_key_exists($title, $allowed) ? $title : 'other';
    $stmt = db()->prepare('UPDATE property_media SET title = :title WHERE id = :id AND draft_id = :draft_id');
    $stmt->execute([
        ':title' => $normalizedTitle,
        ':id' => $mediaId,
        ':draft_id' => $draftId,
    ]);
}

function setPropertyMediaAsCover(int $draftId, int $mediaId): void
{
    $stmt = db()->prepare("SELECT id, type FROM property_media WHERE id = :id AND draft_id = :draft_id LIMIT 1");
    $stmt->execute([
        ':id' => $mediaId,
        ':draft_id' => $draftId,
    ]);
    $media = $stmt->fetch();

    if (!$media || (string) ($media['type'] ?? '') !== 'image') {
        throw new RuntimeException('Only image can be set as cover photo.');
    }

    $resetStmt = db()->prepare("UPDATE property_media SET is_primary = 0 WHERE draft_id = :draft_id AND type = 'image'");
    $resetStmt->execute([':draft_id' => $draftId]);

    $updateStmt = db()->prepare("UPDATE property_media SET is_primary = 1 WHERE id = :id AND draft_id = :draft_id");
    $updateStmt->execute([
        ':id' => $mediaId,
        ':draft_id' => $draftId,
    ]);
}

function saveDraftProgress(int $draftId, array $bundle, ?string $stepKey = null): void
{
    $progress = propertyDraftProgress($bundle);
    $currentStep = 1;

    if ($stepKey !== null) {
        $order = propertyStepOrder();
        $position = array_search($stepKey, $order, true);
        $currentStep = $position === false ? 1 : $position + 1;
    } else {
        $currentStep = max(1, (int) ($bundle['draft']['current_step'] ?? 1));
    }

    $fields = [
        'completion_percent = :completion_percent',
        'current_step = :current_step',
    ];
    $params = [
        ':completion_percent' => $progress['overall_percent'],
        ':current_step' => $currentStep,
        ':id' => $draftId,
    ];

    if (tableHasColumn('property_drafts', 'last_completed_step')) {
        $fields[] = 'last_completed_step = :last_completed_step';
        $params[':last_completed_step'] = $currentStep;
    }

    $stmt = db()->prepare('UPDATE property_drafts SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id');
    $stmt->execute($params);
}

function upsertDraftSection(string $table, int $draftId, array $data): void
{
    $existing = draftSectionRow($table, $draftId);

    if ($existing) {
        $setParts = [];
        $params = [':draft_id' => $draftId];

        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . ' WHERE draft_id = :draft_id';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return;
    }

    $columns = array_merge(['draft_id'], array_keys($data));
    $placeholders = array_map(static fn ($column) => ':' . $column, $columns);
    $params = [':draft_id' => $draftId];

    foreach ($data as $column => $value) {
        $params[":{$column}"] = $value;
    }

    $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
}

function validatePropertyBasicInput(array $input): array
{
    $listingTypeId = (int) ($input['listing_type_id'] ?? 0);
    $data = [
        'user_id' => (int) ($input['user_id'] ?? 0),
        'property_type_id' => (int) ($input['property_type_id'] ?? 0),
        'custom_property_type' => trim((string) ($input['custom_property_type'] ?? '')),
        'listing_type_id' => $listingTypeId,
        'title' => trim((string) ($input['title'] ?? '')),
        'description' => trim((string) ($input['description'] ?? '')),
        'posted_by' => trim((string) ($input['posted_by'] ?? '')),
        'purpose_note' => trim((string) ($input['purpose_note'] ?? '')),
        'available_from' => trim((string) ($input['available_from'] ?? '')),
    ];
    $errors = [];

    if ($data['user_id'] <= 0 || !findUser($data['user_id'])) {
        $errors[] = 'Please select a valid user.';
    }
    if ($data['property_type_id'] <= 0 || !findPropertyType($data['property_type_id'])) {
        $errors[] = 'Please select a valid property type.';
    }
    $selectedPropertyType = $data['property_type_id'] > 0 ? findPropertyType($data['property_type_id']) : null;
    if (propertyTypeUsesCustomName($selectedPropertyType)) {
        if ($data['custom_property_type'] === '') {
            $errors[] = 'Please enter the closest property type.';
        } elseif (stringLength($data['custom_property_type']) > 100) {
            $errors[] = 'Other property type must be 100 characters or fewer.';
        }
    } else {
        $data['custom_property_type'] = '';
    }
    if ($data['listing_type_id'] <= 0 || !findListingType($data['listing_type_id'])) {
        $errors[] = 'Please select a valid listing type.';
    }
    if ($data['title'] === '') {
        $errors[] = 'Property title is required.';
    }
    if (!in_array($data['posted_by'], ['owner', 'agent', 'builder'], true)) {
        $errors[] = 'Please select who posted the property.';
    }
    if ($data['purpose_note'] !== '' && stringLength($data['purpose_note']) > 150) {
        $errors[] = 'Listing note must be 150 characters or fewer.';
    }
    if (listingRequiresAvailabilityById($listingTypeId)) {
        if ($data['available_from'] === '') {
            $errors[] = 'Available from date is required for rent or PG listings.';
        } elseif (strtotime($data['available_from']) === false) {
            $errors[] = 'Please enter a valid available from date.';
        }
    } elseif ($data['available_from'] !== '' && strtotime($data['available_from']) === false) {
        $errors[] = 'Please enter a valid available from date.';
    }

    return ['data' => $data, 'errors' => $errors];
}

function validatePropertyDescriptionInput(array $input): array
{
    $description = trim((string) ($input['description'] ?? ''));
    $errors = [];

    if ($description === '') {
        $errors[] = 'Property description is required before final submit.';
    }

    return [
        'data' => ['description' => $description],
        'errors' => $errors,
    ];
}

function validatePropertyRejectionInput(array $input): array
{
    $data = [
        'rejected_reason' => trim((string) ($input['rejected_reason'] ?? '')),
        'admin_note' => trim((string) ($input['admin_note'] ?? '')),
    ];
    $errors = [];

    if ($data['rejected_reason'] === '') {
        $errors[] = 'Rejection reason is required.';
    }
    if ($data['rejected_reason'] !== '' && stringLength($data['rejected_reason']) > 255) {
        $errors[] = 'Rejection reason must be 255 characters or fewer.';
    }
    if ($data['admin_note'] !== '' && stringLength($data['admin_note']) > 255) {
        $errors[] = 'Internal note must be 255 characters or fewer.';
    }

    return [
        'data' => $data,
        'errors' => $errors,
    ];
}

function validatePropertyLocationInput(array $input): array
{
    $data = [
        'country_id' => (int) ($input['country_id'] ?? 0),
        'state_id' => (int) ($input['state_id'] ?? 0),
        'city_id' => (int) ($input['city_id'] ?? 0),
        'locality_id' => (int) ($input['locality_id'] ?? 0),
        'address_line' => trim((string) ($input['address_line'] ?? '')),
        'map_address' => trim((string) ($input['map_address'] ?? '')),
        'landmark' => trim((string) ($input['landmark'] ?? '')),
        'pincode' => trim((string) ($input['pincode'] ?? '')),
        'latitude' => trim((string) ($input['latitude'] ?? '')),
        'longitude' => trim((string) ($input['longitude'] ?? '')),
        'is_map_exact' => isset($input['is_map_exact']) ? 1 : 0,
    ];
    $errors = [];

    $country = $data['country_id'] > 0 ? findCountry($data['country_id']) : null;
    $state = $data['state_id'] > 0 ? findState($data['state_id']) : null;
    $city = $data['city_id'] > 0 ? findCity($data['city_id']) : null;
    $locality = $data['locality_id'] > 0 ? findLocality($data['locality_id']) : null;

    if (!$country) {
        $errors[] = 'Please select a valid country.';
    }
    if (!$state) {
        $errors[] = 'Please select a valid state.';
    } elseif ($country && (int) $state['country_id'] !== $data['country_id']) {
        $errors[] = 'The selected state does not belong to the selected country.';
    }
    if (!$city) {
        $errors[] = 'Please select a valid city.';
    } elseif ($state && (int) $city['state_id'] !== $data['state_id']) {
        $errors[] = 'The selected city does not belong to the selected state.';
    }
    if (!$locality) {
        $errors[] = 'Please select a valid locality.';
    } elseif ($city && (int) $locality['city_id'] !== $data['city_id']) {
        $errors[] = 'The selected locality does not belong to the selected city.';
    }
    if ($data['pincode'] !== '' && !preg_match('/^[0-9]{4,10}$/', $data['pincode'])) {
        $errors[] = 'Please enter a valid pincode.';
    }
    if (($data['latitude'] !== '' && !is_numeric($data['latitude'])) || ($data['longitude'] !== '' && !is_numeric($data['longitude']))) {
        $errors[] = 'Latitude and longitude must be valid numeric values.';
    }
    if (($data['latitude'] === '') !== ($data['longitude'] === '')) {
        $errors[] = 'Please provide both latitude and longitude together.';
    }

    return ['data' => $data, 'errors' => $errors];
}

function validatePropertyProfileInput(array $input, array $basic = []): array
{
    $rawAreaUnit = trim((string) ($input['area_unit'] ?? ''));
    $category = propertyTypeCategoryFromBasic($basic);
    $landProperty = $category === 'land';
    $residentialProperty = $category === 'residential';
    $commercialProperty = $category === 'commercial';
    $officeProperty = isOfficePropertyBasic($basic);
    $pgListing = isPgListingBasic($basic);
    $data = [
        'builtup_area' => trim((string) ($input['builtup_area'] ?? '')),
        'super_builtup_area' => trim((string) ($input['super_builtup_area'] ?? '')),
        'carpet_area' => trim((string) ($input['carpet_area'] ?? '')),
        'plot_area' => trim((string) ($input['plot_area'] ?? '')),
        'area_unit' => $rawAreaUnit !== '' ? $rawAreaUnit : 'sq.ft',
        'bedrooms' => trim((string) ($input['bedrooms'] ?? '')),
        'bathrooms' => trim((string) ($input['bathrooms'] ?? '')),
        'balconies' => trim((string) ($input['balconies'] ?? '')),
        'parking_count' => trim((string) ($input['parking_count'] ?? '')),
        'servant_room' => isset($input['servant_room']) ? 1 : 0,
        'pooja_room' => isset($input['pooja_room']) ? 1 : 0,
        'study_room' => isset($input['study_room']) ? 1 : 0,
        'floor_no' => trim((string) ($input['floor_no'] ?? '')),
        'total_floor' => trim((string) ($input['total_floor'] ?? '')),
        'furnishing' => trim((string) ($input['furnishing'] ?? '')),
        'furnishing_items' => normalizePropertyFurnishingItems($input['furnishing_items'] ?? [], trim((string) ($input['furnishing'] ?? ''))),
        'profile_details' => $officeProperty
            ? normalizePropertyOfficeProfileDetails($input)
            : ($pgListing ? normalizePropertyPgProfileDetails($input) : []),
        'property_age' => trim((string) ($input['property_age'] ?? '')),
        'facing' => trim((string) ($input['facing'] ?? '')),
        'ownership_type' => trim((string) ($input['ownership_type'] ?? '')),
    ];
    $errors = [];

    if ($landProperty && $data['plot_area'] === '') {
        $errors[] = 'Plot area is required for land listings.';
    } elseif (!$landProperty && $data['super_builtup_area'] === '' && $data['builtup_area'] === '' && $data['carpet_area'] === '' && $data['plot_area'] === '') {
        $errors[] = 'Please provide at least one area value.';
    }
    if (!array_key_exists($data['area_unit'], areaUnitOptionsForCategory($category))) {
        $errors[] = $landProperty
            ? 'Please select a valid land area unit.'
            : 'Built properties can use only sq.ft, sq.yards, or sq.m.';
    }
    foreach (['builtup_area', 'super_builtup_area', 'carpet_area', 'plot_area'] as $field) {
        if ($data[$field] !== '' && (!is_numeric($data[$field]) || (float) $data[$field] < 0)) {
            $errors[] = 'Area values cannot be negative.';
            break;
        }
    }
    if (
        $data['super_builtup_area'] !== ''
        && $data['builtup_area'] !== ''
        && is_numeric($data['super_builtup_area'])
        && is_numeric($data['builtup_area'])
        && (float) $data['builtup_area'] > (float) $data['super_builtup_area']
    ) {
        $errors[] = 'Built-up area cannot exceed super built-up area.';
    }
    if (
        $data['builtup_area'] !== ''
        && $data['carpet_area'] !== ''
        && is_numeric($data['builtup_area'])
        && is_numeric($data['carpet_area'])
        && (float) $data['carpet_area'] > (float) $data['builtup_area']
    ) {
        $errors[] = 'Carpet area cannot exceed built-up area.';
    }
    if ($residentialProperty && $data['bedrooms'] === '') {
        $errors[] = 'Bedrooms are required.';
    }
    if ($data['bedrooms'] !== '' && (!ctype_digit($data['bedrooms']) || (int) $data['bedrooms'] < 0)) {
        $errors[] = 'Bedrooms cannot be negative.';
    }
    if ($residentialProperty && $data['bathrooms'] === '') {
        $errors[] = 'Bathrooms are required.';
    }
    if ($data['bathrooms'] !== '' && (!ctype_digit($data['bathrooms']) || (int) $data['bathrooms'] < 0)) {
        $errors[] = 'Bathrooms cannot be negative.';
    }
    if ($data['balconies'] !== '' && (!ctype_digit($data['balconies']) || (int) $data['balconies'] < 0)) {
        $errors[] = 'Balconies cannot be negative.';
    }
    foreach (['parking_count', 'floor_no', 'total_floor'] as $field) {
        if ($data[$field] !== '' && (!preg_match('/^\d+$/', $data[$field]) || (int) $data[$field] < 0)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' cannot be negative.';
        }
    }
    if (
        $data['floor_no'] !== ''
        && $data['total_floor'] !== ''
        && ctype_digit($data['floor_no'])
        && ctype_digit($data['total_floor'])
        && (int) $data['floor_no'] > (int) $data['total_floor']
    ) {
        $errors[] = 'Floor number cannot exceed total floors.';
    }
    if ($residentialProperty && !in_array($data['furnishing'], ['unfurnished', 'semi', 'fully'], true)) {
        $errors[] = 'Please select furnishing type.';
    }
    if (
        isset($input['furnishing_items_present'])
        && in_array($data['furnishing'], ['semi', 'fully'], true)
        && $data['furnishing_items'] === []
    ) {
        $errors[] = 'Please select what is included in furnishing.';
    }
    if (
        $pgListing
        && isset($input['furnishing_items_present'])
        && $data['furnishing'] === 'fully'
        && count($data['furnishing_items']) < 3
    ) {
        $errors[] = 'Please select at least three furnishing items for furnished PG.';
    }
    if ($officeProperty) {
        $office = $data['profile_details']['office'] ?? [];
        if (empty($office['min_seats'])) {
            $errors[] = 'Minimum seats are required for office listings.';
        }
        if (!empty($office['max_seats']) && !empty($office['min_seats']) && (int) $office['max_seats'] < (int) $office['min_seats']) {
            $errors[] = 'Maximum seats cannot be less than minimum seats.';
        }
        if ($office['cabins'] === null) {
            $errors[] = 'Number of cabins is required for office listings.';
        }
        if ($office['meeting_rooms'] === null) {
            $errors[] = 'Number of meeting rooms is required for office listings.';
        }
        if (($office['washrooms'] ?? '') === 'available' && empty($office['private_washrooms']) && empty($office['shared_washrooms'])) {
            $errors[] = 'Add private or shared washroom count for office listings.';
        }
    }
    if ($pgListing) {
        $pg = $data['profile_details']['pg'] ?? [];
        if (($pg['room_type'] ?? '') === '') {
            $errors[] = 'Please select PG room type.';
        }
        if (($pg['available_for'] ?? '') === '') {
            $errors[] = 'Please select who the PG is available for.';
        }
        if (!empty($pg['total_rooms']) && !empty($pg['available_rooms']) && (int) $pg['available_rooms'] > (int) $pg['total_rooms']) {
            $errors[] = 'Available rooms cannot exceed total rooms.';
        }
    }
    if (($residentialProperty || ($commercialProperty && !$officeProperty)) && !array_key_exists($data['property_age'], propertyAgeOptions())) {
        $errors[] = 'Please select property age.';
    }
    if (!$officeProperty && !$pgListing && !array_key_exists($data['facing'], facingOptions())) {
        $errors[] = 'Please select facing.';
    }
    if ($data['ownership_type'] !== '' && !array_key_exists($data['ownership_type'], ownershipTypeOptions())) {
        $errors[] = 'Please select a valid ownership type.';
    }

    return ['data' => $data, 'errors' => $errors];
}

function validatePropertyPricingInput(array $input, array $basic): array
{
    $isSell = isSellListingBasic($basic);
    $isPgListing = isPgListingBasic($basic);
    $isOwnerPosting = strtolower(trim((string) ($basic['posted_by'] ?? ''))) === 'owner';
    $hasStructuredDeposit = array_key_exists('security_deposit_type', $input);
    $hasStructuredBrokerage = array_key_exists('brokerage_type', $input);
    $data = [
        'expected_price' => trim((string) ($input['expected_price'] ?? '')),
        'rent' => trim((string) ($input['rent'] ?? '')),
        'deposit' => trim((string) ($input['deposit'] ?? '')),
        'security_deposit_type' => trim((string) ($input['security_deposit_type'] ?? '')),
        'security_deposit_amount' => trim((string) ($input['security_deposit_amount'] ?? '')),
        'security_deposit_months' => trim((string) ($input['security_deposit_months'] ?? '')),
        'booking_amount' => trim((string) ($input['booking_amount'] ?? '')),
        'maintenance' => trim((string) ($input['maintenance'] ?? '')),
        'maintenance_period' => trim((string) ($input['maintenance_period'] ?? '')),
        'electricity_charges' => trim((string) ($input['electricity_charges'] ?? '')),
        'brokerage' => trim((string) ($input['brokerage'] ?? '')),
        'brokerage_type' => trim((string) ($input['brokerage_type'] ?? '')),
        'brokerage_value' => trim((string) ($input['brokerage_value'] ?? '')),
        'brokerage_negotiable' => isset($input['brokerage_negotiable']) ? 1 : 0,
        'lock_in_months' => trim((string) ($input['lock_in_months'] ?? '')),
        'annual_rent_increase_percent' => trim((string) ($input['annual_rent_increase_percent'] ?? '')),
        'dg_ups_included' => isset($input['dg_ups_included']) ? 1 : 0,
        'electricity_water_excluded' => isset($input['electricity_water_excluded']) ? 1 : 0,
        'negotiable' => isset($input['negotiable']) ? 1 : 0,
    ];
    $errors = [];

    if ($isSell) {
        if ($data['expected_price'] === '') {
            $errors[] = 'Expected price is required for sale listings.';
        }
        if ($data['expected_price'] !== '' && (!is_numeric($data['expected_price']) || (float) $data['expected_price'] < 0)) {
            $errors[] = 'Expected price cannot be negative.';
        }

        $data['rent'] = '';
        $data['deposit'] = '';
        $data['security_deposit_type'] = '';
        $data['security_deposit_amount'] = '';
        $data['security_deposit_months'] = '';
        $data['lock_in_months'] = '';
        $data['annual_rent_increase_percent'] = '';
        $data['dg_ups_included'] = 0;
        $data['electricity_water_excluded'] = 0;
    } else {
        if ($data['rent'] === '') {
            $errors[] = 'Rent is required for rent or PG listings.';
        }
        if ($data['rent'] !== '' && (!is_numeric($data['rent']) || (float) $data['rent'] < 0)) {
            $errors[] = 'Rent cannot be negative.';
        }

        if ($hasStructuredDeposit) {
            if (!array_key_exists($data['security_deposit_type'], securityDepositTypeOptions())) {
                $errors[] = 'Please select a valid security deposit type.';
            } elseif ($data['security_deposit_type'] === 'fixed') {
                if ($data['security_deposit_amount'] === '' || !is_numeric($data['security_deposit_amount']) || (float) $data['security_deposit_amount'] <= 0) {
                    $errors[] = 'Enter a valid fixed security deposit amount.';
                }
                $data['security_deposit_months'] = '';
                $data['deposit'] = $data['security_deposit_amount'];
            } elseif ($data['security_deposit_type'] === 'multiple') {
                if (!preg_match('/^\d+$/', $data['security_deposit_months']) || (int) $data['security_deposit_months'] < 1 || (int) $data['security_deposit_months'] > 30) {
                    $errors[] = 'Security deposit months must be between 1 and 30.';
                }
                $data['security_deposit_amount'] = '';
                $data['deposit'] = $data['security_deposit_months'];
            } else {
                $data['security_deposit_amount'] = '';
                $data['security_deposit_months'] = '';
                $data['deposit'] = '';
            }
        } elseif ($data['deposit'] === '' || !in_array($data['deposit'], ['1', '2', '3', '4', '5', '6'], true)) {
            $errors[] = 'Please select a security deposit from 1 to 6 months.';
        }

        $data['expected_price'] = '';

        if ($isPgListing) {
            $data['lock_in_months'] = '';
            $data['annual_rent_increase_percent'] = '';
        }

        if ($data['lock_in_months'] !== '' && (!preg_match('/^\d+$/', $data['lock_in_months']) || (int) $data['lock_in_months'] < 1 || (int) $data['lock_in_months'] > 120)) {
            $errors[] = 'Lock-in period must be between 1 and 120 months.';
        }

        if ($data['annual_rent_increase_percent'] !== '' && (!is_numeric($data['annual_rent_increase_percent']) || (float) $data['annual_rent_increase_percent'] < 0 || (float) $data['annual_rent_increase_percent'] > 100)) {
            $errors[] = 'Yearly rent increase must be between 0 and 100 percent.';
        }
    }

    foreach (['booking_amount', 'maintenance'] as $field) {
        if ($data[$field] !== '' && (!is_numeric($data[$field]) || (float) $data[$field] < 0)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' cannot be negative.';
        }
    }

    if ($data['maintenance'] !== '' && !array_key_exists($data['maintenance_period'], maintenancePeriodOptions())) {
        $errors[] = 'Please select whether maintenance is monthly or yearly.';
    }

    if ($data['maintenance'] === '') {
        $data['maintenance_period'] = '';
    }

    if ($isOwnerPosting) {
        $data['brokerage_type'] = 'none';
        $data['brokerage_value'] = '';
        $data['brokerage_negotiable'] = 0;
        $data['brokerage'] = 'No brokerage';
    } elseif ($hasStructuredBrokerage) {
        if (!array_key_exists($data['brokerage_type'], brokerageTypeOptions())) {
            $errors[] = 'Please select a valid brokerage type.';
        } elseif ($data['brokerage_type'] === 'none') {
            $data['brokerage_value'] = '';
            $data['brokerage_negotiable'] = 0;
            $data['brokerage'] = 'No brokerage';
        } else {
            if ($data['brokerage_value'] === '' || !is_numeric($data['brokerage_value']) || (float) $data['brokerage_value'] <= 0) {
                $errors[] = 'Enter a valid brokerage value.';
            } elseif ($data['brokerage_type'] === 'percentage' && (float) $data['brokerage_value'] > 100) {
                $errors[] = 'Brokerage percentage cannot exceed 100.';
            }

            $data['brokerage'] = $data['brokerage_type'] === 'percentage'
                ? $data['brokerage_value'] . '% of price'
                : 'Fixed amount: ' . $data['brokerage_value'];
        }
    }

    return ['data' => $data, 'errors' => $errors];
}

function savePropertyAmenities(int $draftId, array $amenityIds): void
{
    $deleteStmt = db()->prepare('DELETE FROM property_amenities WHERE draft_id = :draft_id');
    $deleteStmt->execute([':draft_id' => $draftId]);

    if ($amenityIds === []) {
        return;
    }

    $insertStmt = db()->prepare('INSERT INTO property_amenities (draft_id, amenity_id) VALUES (:draft_id, :amenity_id)');

    foreach ($amenityIds as $amenityId) {
        $insertStmt->execute([
            ':draft_id' => $draftId,
            ':amenity_id' => $amenityId,
        ]);
    }
}

function validateAmenitiesInput(array $input): array
{
    $amenityIds = array_map('intval', $input['amenity_ids'] ?? []);
    $amenityIds = array_values(array_unique(array_filter($amenityIds)));

    return [
        'data' => ['amenity_ids' => $amenityIds],
        'errors' => [],
    ];
}

function propertyUploadBasePath(): string
{
    return BASE_PATH . '/uploads/properties';
}

function propertyUploadBaseUrl(): string
{
    return UPLOAD_URL . '/properties';
}

function ensurePropertyDraftFolders(int $draftId): array
{
    $basePath = propertyUploadBasePath() . '/' . $draftId;
    $imagesPath = $basePath . '/images';
    $videosPath = $basePath . '/videos';

    foreach ([$basePath, $imagesPath, $videosPath] as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    return ['base' => $basePath, 'images' => $imagesPath, 'videos' => $videosPath];
}

function optimizeImageToWebp(array $file, int $draftId): string
{
    $folders = ensurePropertyDraftFolders($draftId);
    $info = getimagesize($file['tmp_name']);

    if ($info === false) {
        throw new RuntimeException('Invalid image file.');
    }

    $imageType = $info[2] ?? null;
    $source = match ($imageType) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($file['tmp_name']),
        IMAGETYPE_PNG => imagecreatefrompng($file['tmp_name']),
        IMAGETYPE_GIF => imagecreatefromgif($file['tmp_name']),
        IMAGETYPE_WEBP => imagecreatefromwebp($file['tmp_name']),
        default => null,
    };

    if (!$source) {
        throw new RuntimeException('Unsupported image format.');
    }

    if ($imageType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3 => imagerotate($source, 180, 0),
            6 => imagerotate($source, -90, 0),
            8 => imagerotate($source, 90, 0),
            default => false,
        };
        if ($rotated !== false) {
            imagedestroy($source);
            $source = $rotated;
        }
    }

    $width = max(1, imagesx($source));
    $height = max(1, imagesy($source));
    $targetWidth = 1600;
    $targetHeight = 1200;
    $sourceRatio = $width / $height;
    $targetRatio = $targetWidth / $targetHeight;
    $cropWidth = $width;
    $cropHeight = $height;
    $cropX = 0;
    $cropY = 0;

    if ($sourceRatio > $targetRatio) {
        $cropWidth = (int) round($height * $targetRatio);
        $cropX = (int) floor(($width - $cropWidth) / 2);
    } elseif ($sourceRatio < $targetRatio) {
        $cropHeight = (int) round($width / $targetRatio);
        $cropY = (int) floor(($height - $cropHeight) / 2);
    }

    $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
    $background = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $background);
    imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

    $filename = uniqid('img_', true) . '.webp';
    $targetPath = $folders['images'] . '/' . $filename;

    if (!imagewebp($canvas, $targetPath, 78)) {
        imagedestroy($source);
        imagedestroy($canvas);
        throw new RuntimeException('Unable to optimize image.');
    }

    imagedestroy($source);
    imagedestroy($canvas);

    return propertyUploadBaseUrl() . '/' . $draftId . '/images/' . $filename;
}

function propertyPublicMediaCardHtml(array $media): string
{
    $kind = (string) ($media['kind'] ?? '');
    $mediaId = (int) ($media['id'] ?? 0);
    $fileUrl = (string) ($media['file_url'] ?? '');
    $title = (string) ($media['title'] ?? '');
    $preview = '';
    $coverBadge = $kind === 'image' && (int) ($media['is_primary'] ?? 0) === 1
        ? '<span class="post-media-badge">Cover</span>'
        : '';

    if ($kind === 'image') {
        $preview = '<img src="' . e($fileUrl) . '" alt="Property image">';
    } elseif ($kind === 'youtube') {
        $preview = '<iframe src="https://www.youtube.com/embed/' . e((string) ($media['youtube_id'] ?? '')) . '" title="YouTube property video" allowfullscreen loading="lazy"></iframe>';
    } else {
        $preview = '<video src="' . e($fileUrl) . '" controls preload="metadata"></video>';
    }

    $typeControl = '';
    $coverControl = '';

    if ($kind === 'image') {
        $options = '<option value="">Photo type</option>';
        foreach (propertyPhotoTypeOptions() as $value => $label) {
            $options .= '<option value="' . e($value) . '"' . ($title === $value ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        $typeControl = '<select class="post-media-type-select" data-public-media-title="' . $mediaId . '" aria-label="Photo type">' . $options . '</select>';
        if ((int) ($media['is_primary'] ?? 0) !== 1) {
            $coverControl = '<button class="post-media-icon-btn" type="button" data-public-media-cover="' . $mediaId . '" title="Make cover photo" aria-label="Make cover photo"><i class="bi bi-star"></i></button>';
        }
    }

    return '<article class="post-media-card" data-media-id="' . $mediaId . '">' .
        '<div class="post-media-thumb">' . $preview . $coverBadge . '</div>' .
        '<div class="post-media-meta">' .
        '<div class="post-media-meta-main"><strong>' . e($kind === 'youtube' ? 'YouTube Video' : ucfirst($kind)) . '</strong>' . $typeControl . '</div>' .
        '<div class="post-media-card-actions">' . $coverControl .
        '<button class="post-media-icon-btn danger" type="button" data-public-media-delete="' . $mediaId . '" title="Remove media" aria-label="Remove media"><i class="bi bi-trash3"></i></button>' .
        '</div>' .
        '</div>' .
        '</article>';
}

function propertyPublicMediaGridHtml(array $mediaItems): string
{
    if (!$mediaItems) {
        return '<p class="post-media-empty">No media uploaded yet.</p>';
    }

    return implode('', array_map('propertyPublicMediaCardHtml', $mediaItems));
}

function storeVideoUpload(array $file, int $draftId, string $extension): string
{
    $folders = ensurePropertyDraftFolders($draftId);
    $allowedExtensions = array_values(propertyAllowedVideoMimes());
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Unsupported video format.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $folders['videos'] . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Unable to store video file.');
    }

    return propertyUploadBaseUrl() . '/' . $draftId . '/videos/' . $filename;
}

function extractYoutubeId(string $url): ?string
{
    $patterns = [
        '/(?:youtube\.com\/watch\?v=|youtube\.com\/embed\/|youtu\.be\/)([A-Za-z0-9_-]{11})/',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches) === 1) {
            return $matches[1];
        }
    }

    return null;
}

function normalizeYoutubeUrl(string $url): ?string
{
    $id = extractYoutubeId($url);

    return $id ? 'https://www.youtube.com/watch?v=' . $id : null;
}

function propertyNextMediaSortOrder(int $draftId): int
{
    if (!tableHasColumn('property_media', 'sort_order')) {
        return 0;
    }

    $stmt = db()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM property_media WHERE draft_id = :draft_id');
    $stmt->execute([':draft_id' => $draftId]);

    return (int) $stmt->fetchColumn();
}

function addPropertyMediaRecord(int $draftId, string $fileUrl, string $type, int $isPrimary = 0, array $meta = []): int
{
    if ($type === 'image' && $isPrimary === 1) {
        $stmt = db()->prepare("UPDATE property_media SET is_primary = 0 WHERE draft_id = :draft_id AND type = 'image'");
        $stmt->execute([':draft_id' => $draftId]);
    }

    $columns = ['draft_id', 'file_url', 'type', 'is_primary'];
    $params = [
        ':draft_id' => $draftId,
        ':file_url' => $fileUrl,
        ':type' => $type,
        ':is_primary' => $isPrimary,
    ];

    $optionalColumns = [
        'source_type',
        'title',
        'thumbnail_url',
        'mime_type',
        'file_size',
        'video_provider',
        'sort_order',
    ];

    foreach ($optionalColumns as $column) {
        if (array_key_exists($column, $meta) && tableHasColumn('property_media', $column)) {
            $columns[] = $column;
            $params[':' . $column] = $meta[$column];
        }
    }

    $placeholders = array_map(static fn ($column) => ':' . $column, $columns);
    $stmt = db()->prepare(
        'INSERT INTO property_media (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
    );
    $stmt->execute($params);

    return (int) db()->lastInsertId();
}

function deletePropertyMediaRecord(int $draftId, int $mediaId): void
{
    $stmt = db()->prepare('SELECT id, file_url, type, is_primary FROM property_media WHERE id = :id AND draft_id = :draft_id LIMIT 1');
    $stmt->execute([
        ':id' => $mediaId,
        ':draft_id' => $draftId,
    ]);
    $media = $stmt->fetch();

    if (!$media) {
        throw new RuntimeException('Media item not found.');
    }

    $fileUrl = (string) $media['file_url'];

    if (!extractYoutubeId($fileUrl) && str_starts_with($fileUrl, propertyUploadBaseUrl())) {
        $relativePath = substr($fileUrl, strlen(propertyUploadBaseUrl()));
        $absolutePath = propertyUploadBasePath() . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    $deleteStmt = db()->prepare('DELETE FROM property_media WHERE id = :id AND draft_id = :draft_id');
    $deleteStmt->execute([
        ':id' => $mediaId,
        ':draft_id' => $draftId,
    ]);

    if ((string) $media['type'] === 'image' && (int) $media['is_primary'] === 1) {
        $primaryStmt = db()->prepare("SELECT id FROM property_media WHERE draft_id = :draft_id AND type = 'image' ORDER BY id ASC LIMIT 1");
        $primaryStmt->execute([':draft_id' => $draftId]);
        $nextPrimaryId = $primaryStmt->fetchColumn();

        if ($nextPrimaryId) {
            $updateStmt = db()->prepare('UPDATE property_media SET is_primary = 1 WHERE id = :id AND draft_id = :draft_id');
            $updateStmt->execute([
                ':id' => $nextPrimaryId,
                ':draft_id' => $draftId,
            ]);
        }
    }
}

function propertyMediaCardHtml(array $media): string
{
    $preview = '';
    $kind = $media['kind'] ?? '';
    $title = (string) ($media['title'] ?? '');
    $photoTypeOptions = propertyPhotoTypeOptions();
    $coverBadge = $kind === 'image' && (int) ($media['is_primary'] ?? 0) === 1
        ? '<span class="media-badge">Cover Photo</span>'
        : '';

    if ($kind === 'image') {
        $preview = '<img src="' . e($media['file_url']) . '" alt="Property image">';
    } elseif ($kind === 'youtube') {
        $preview = '<iframe src="https://www.youtube.com/embed/' . e((string) $media['youtube_id']) . '" title="YouTube preview" allowfullscreen loading="lazy"></iframe>';
    } else {
        $preview = '<video src="' . e($media['file_url']) . '" controls preload="metadata"></video>';
    }

    return '<article class="media-card" data-media-id="' . (int) $media['id'] . '">' .
        '<div class="media-preview">' . $preview . $coverBadge . '</div>' .
        '<div class="media-meta">' .
        '<div class="media-meta-main">' .
        '<span class="media-kind">' . e(ucfirst((string) $kind)) . '</span>' .
        ($kind === 'image' && tableHasColumn('property_media', 'title')
            ? '<form method="post" action="' . ADMIN_URL . '/properties/media-meta.php" data-custom-handler="property-media-meta" class="media-inline-form">' .
                '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">' .
                '<input type="hidden" name="draft_id" value="' . (int) $media['draft_id'] . '">' .
                '<input type="hidden" name="media_id" value="' . (int) $media['id'] . '">' .
                '<input type="hidden" name="action_type" value="set_photo_type">' .
                '<select class="form-select form-select-sm media-type-select" name="title" data-media-auto-submit>' .
                '<option value="">Photo Type</option>' .
                implode('', array_map(
                    static fn ($value, $label) => '<option value="' . e((string) $value) . '"' . ($title === $value ? ' selected' : '') . '>' . e((string) $label) . '</option>',
                    array_keys($photoTypeOptions),
                    array_values($photoTypeOptions)
                )) .
                '</select>' .
            '</form>'
            : '') .
        '</div>' .
        '<div class="media-actions">' .
        ($kind === 'image' && (int) ($media['is_primary'] ?? 0) !== 1
            ? '<form method="post" action="' . ADMIN_URL . '/properties/media-meta.php" data-custom-handler="property-media-meta" class="media-inline-form">' .
                '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">' .
                '<input type="hidden" name="draft_id" value="' . (int) $media['draft_id'] . '">' .
                '<input type="hidden" name="media_id" value="' . (int) $media['id'] . '">' .
                '<input type="hidden" name="action_type" value="set_cover">' .
                '<button class="btn btn-sm btn-outline-primary icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Make cover photo" aria-label="Make cover photo"><i class="bi bi-star" aria-hidden="true"></i></button>' .
            '</form>'
            : '') .
        '<form method="post" action="' . ADMIN_URL . '/properties/media-delete.php" data-custom-handler="property-media-delete" class="media-inline-form">' .
        '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">' .
        '<input type="hidden" name="draft_id" value="' . (int) $media['draft_id'] . '">' .
        '<input type="hidden" name="media_id" value="' . (int) $media['id'] . '">' .
        '<button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Remove media" aria-label="Remove media"><i class="bi bi-x-lg" aria-hidden="true"></i></button>' .
        '</form>' .
        '</div>' .
        '</div>' .
        '</article>';
}

function slugify(string $text): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text) ?? ''));
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'property';
}

function submitPropertyDraft(int $draftId): void
{
    $bundle = getPropertyDraftBundle($draftId);
    $progress = $bundle['progress'];

    foreach (propertyStepOrder() as $stepKey) {
        if ($stepKey === 'media') {
            continue;
        }

        if ((int) ($progress['step_meta'][$stepKey]['percent'] ?? 0) < 100) {
            throw new RuntimeException('Complete all required property details before submitting.');
        }
    }

    $basic = $bundle['basic'];
    $draft = $bundle['draft'];
    $slug = slugify((string) ($basic['title'] ?? 'property')) . '-' . $draftId;
    $existingStmt = db()->prepare('SELECT id FROM properties WHERE draft_id = :draft_id LIMIT 1');
    $existingStmt->execute([':draft_id' => $draftId]);
    $propertyId = $existingStmt->fetchColumn();

    if ($propertyId) {
        $fields = ['user_id = :user_id', 'slug = :slug', "status = 'pending'"];

        if (tableHasColumn('properties', 'published_at')) {
            $fields[] = 'published_at = NULL';
        }
        if (tableHasColumn('properties', 'rejected_reason')) {
            $fields[] = 'rejected_reason = NULL';
        }

        $update = db()->prepare('UPDATE properties SET ' . implode(', ', $fields) . ' WHERE draft_id = :draft_id');
        $update->execute([
            ':user_id' => $draft['user_id'],
            ':slug' => $slug,
            ':draft_id' => $draftId,
        ]);
    } else {
        $insert = db()->prepare("INSERT INTO properties (user_id, draft_id, slug, status, created_at) VALUES (:user_id, :draft_id, :slug, 'pending', NOW())");
        $insert->execute([
            ':user_id' => $draft['user_id'],
            ':draft_id' => $draftId,
            ':slug' => $slug,
        ]);
    }

    $draftFields = ['is_submitted = 1', 'completion_percent = :completion_percent'];
    $params = [
        ':completion_percent' => $progress['overall_percent'],
        ':id' => $draftId,
    ];

    if (tableHasColumn('property_drafts', 'submitted_at')) {
        $draftFields[] = 'submitted_at = NOW()';
    }
    if (tableHasColumn('property_drafts', 'admin_note')) {
        $draftFields[] = 'admin_note = NULL';
    }

    $stmt = db()->prepare('UPDATE property_drafts SET ' . implode(', ', $draftFields) . ', updated_at = NOW() WHERE id = :id');
    $stmt->execute($params);
}

function approvePropertyDraft(int $draftId): void
{
    $bundle = getPropertyDraftBundle($draftId);
    $progress = $bundle['progress'];

    if ($progress['overall_percent'] < 100 || $progress['image_count'] < 1) {
        throw new RuntimeException('Complete all required steps and upload at least one image before approval.');
    }

    $basic = $bundle['basic'];
    $draft = $bundle['draft'];
    $slug = slugify((string) ($basic['title'] ?? 'property')) . '-' . $draftId;
    $existingStmt = db()->prepare('SELECT id FROM properties WHERE draft_id = :draft_id LIMIT 1');
    $existingStmt->execute([':draft_id' => $draftId]);
    $propertyId = $existingStmt->fetchColumn();

    if ($propertyId) {
        $fields = ['user_id = :user_id', 'slug = :slug', "status = 'active'"];

        if (tableHasColumn('properties', 'published_at')) {
            $fields[] = 'published_at = NOW()';
        }
        if (tableHasColumn('properties', 'rejected_reason')) {
            $fields[] = 'rejected_reason = NULL';
        }

        $update = db()->prepare('UPDATE properties SET ' . implode(', ', $fields) . ' WHERE draft_id = :draft_id');
        $update->execute([
            ':user_id' => $draft['user_id'],
            ':slug' => $slug,
            ':draft_id' => $draftId,
        ]);
    } else {
        if (tableHasColumn('properties', 'published_at')) {
            $insert = db()->prepare("INSERT INTO properties (user_id, draft_id, slug, status, published_at, created_at) VALUES (:user_id, :draft_id, :slug, 'active', NOW(), NOW())");
            $insert->execute([
                ':user_id' => $draft['user_id'],
                ':draft_id' => $draftId,
                ':slug' => $slug,
            ]);
        } else {
            $insert = db()->prepare("INSERT INTO properties (user_id, draft_id, slug, status, created_at) VALUES (:user_id, :draft_id, :slug, 'active', NOW())");
            $insert->execute([
                ':user_id' => $draft['user_id'],
                ':draft_id' => $draftId,
                ':slug' => $slug,
            ]);
        }
    }

    $draftFields = ['is_submitted = 1', 'completion_percent = :completion_percent'];
    $params = [
        ':completion_percent' => $progress['overall_percent'],
        ':id' => $draftId,
    ];

    if (tableHasColumn('property_drafts', 'submitted_at')) {
        $draftFields[] = 'submitted_at = COALESCE(submitted_at, NOW())';
    }
    if (tableHasColumn('property_drafts', 'admin_note')) {
        $draftFields[] = 'admin_note = NULL';
    }

    $stmt = db()->prepare('UPDATE property_drafts SET ' . implode(', ', $draftFields) . ', updated_at = NOW() WHERE id = :id');
    $stmt->execute($params);
}

function rejectPropertyDraft(int $draftId, string $reason, string $adminNote = ''): void
{
    $bundle = getPropertyDraftBundle($draftId);
    $draft = $bundle['draft'];

    if ((int) ($draft['is_submitted'] ?? 0) !== 1) {
        throw new RuntimeException('Only submitted listings can be rejected.');
    }

    $basic = $bundle['basic'];
    $slug = slugify((string) ($basic['title'] ?? 'property')) . '-' . $draftId;
    $property = findPropertyByDraftId($draftId);

    if ($property) {
        $fields = ['user_id = :user_id', 'slug = :slug', "status = 'rejected'"];

        if (tableHasColumn('properties', 'published_at')) {
            $fields[] = 'published_at = NULL';
        }
        if (tableHasColumn('properties', 'rejected_reason')) {
            $fields[] = 'rejected_reason = :rejected_reason';
        }

        $params = [
            ':user_id' => $draft['user_id'],
            ':slug' => $slug,
            ':draft_id' => $draftId,
        ];

        if (tableHasColumn('properties', 'rejected_reason')) {
            $params[':rejected_reason'] = $reason;
        }

        $update = db()->prepare('UPDATE properties SET ' . implode(', ', $fields) . ' WHERE draft_id = :draft_id');
        $update->execute($params);
    } else {
        $columns = ['user_id', 'draft_id', 'slug', 'status', 'created_at'];
        $values = [':user_id', ':draft_id', ':slug', "'rejected'", 'NOW()'];
        $params = [
            ':user_id' => $draft['user_id'],
            ':draft_id' => $draftId,
            ':slug' => $slug,
        ];

        if (tableHasColumn('properties', 'published_at')) {
            $columns[] = 'published_at';
            $values[] = 'NULL';
        }
        if (tableHasColumn('properties', 'rejected_reason')) {
            $columns[] = 'rejected_reason';
            $values[] = ':rejected_reason';
            $params[':rejected_reason'] = $reason;
        }

        $insert = db()->prepare('INSERT INTO properties (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')');
        $insert->execute($params);
    }

    $draftFields = ['is_submitted = 1'];
    $params = [':id' => $draftId];

    if (tableHasColumn('property_drafts', 'submitted_at')) {
        $draftFields[] = 'submitted_at = COALESCE(submitted_at, NOW())';
    }
    if (tableHasColumn('property_drafts', 'admin_note')) {
        $draftFields[] = 'admin_note = :admin_note';
        $params[':admin_note'] = $adminNote !== '' ? $adminNote : $reason;
    }

    $stmt = db()->prepare('UPDATE property_drafts SET ' . implode(', ', $draftFields) . ', updated_at = NOW() WHERE id = :id');
    $stmt->execute($params);
}

function propertyModerationTimeline(array $bundle, ?array $property = null): array
{
    $draft = $bundle['draft'];
    $events = [];

    if (!empty($draft['created_at'])) {
        $events[] = [
            'title' => 'Draft Created',
            'timestamp' => (string) $draft['created_at'],
            'description' => 'The property draft was started in the admin wizard.',
        ];
    }

    if (!empty($draft['submitted_at'])) {
        $events[] = [
            'title' => 'Submitted For Review',
            'timestamp' => (string) $draft['submitted_at'],
            'description' => 'The listing was sent to the moderation queue.',
        ];
    }

    if ($property && (string) ($property['status'] ?? '') === 'active' && !empty($property['published_at'])) {
        $events[] = [
            'title' => 'Approved & Published',
            'timestamp' => (string) $property['published_at'],
            'description' => 'The listing is live and visible as a published property.',
        ];
    }

    if ($property && (string) ($property['status'] ?? '') === 'rejected') {
        $events[] = [
            'title' => 'Rejected',
            'timestamp' => (string) (($property['updated_at'] ?? '') !== '' ? $property['updated_at'] : ($draft['updated_at'] ?? '')),
            'description' => trim((string) ($property['rejected_reason'] ?? '')) !== ''
                ? 'Reason: ' . (string) $property['rejected_reason']
                : 'The listing was rejected during review.',
        ];
    }

    if (!empty($draft['updated_at'])) {
        $events[] = [
            'title' => 'Last Updated',
            'timestamp' => (string) $draft['updated_at'],
            'description' => 'Most recent draft change saved in the wizard.',
        ];
    }

    return array_values(array_filter($events, static fn (array $event): bool => trim((string) ($event['timestamp'] ?? '')) !== ''));
}

function deletePropertyDraft(int $draftId): void
{
    $mediaItems = propertyDraftMedia($draftId);

    foreach ($mediaItems as $media) {
        $mediaId = (int) ($media['id'] ?? 0);

        if ($mediaId > 0) {
            deletePropertyMediaRecord($draftId, $mediaId);
        }
    }

    $draftFolder = propertyUploadBasePath() . DIRECTORY_SEPARATOR . $draftId;

    if (is_dir($draftFolder)) {
        deleteDirectoryRecursively($draftFolder);
    }

    $stmt = db()->prepare('DELETE FROM property_drafts WHERE id = :id');
    $stmt->execute([':id' => $draftId]);
}

function deleteDirectoryRecursively(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $item;

        if (is_dir($itemPath)) {
            deleteDirectoryRecursively($itemPath);
        } elseif (is_file($itemPath)) {
            unlink($itemPath);
        }
    }

    rmdir($path);
}

function propertyProgressPayload(int $draftId): array
{
    $bundle = getPropertyDraftBundle($draftId);
    $progress = $bundle['progress'];

    return [
        'draft_id' => $draftId,
        'overall_percent' => (string) number_format((float) $progress['overall_percent'], 0),
        'step_meta' => $progress['step_meta'],
        'missing' => $progress['missing'],
        'image_count' => $progress['image_count'],
        'is_submitted' => (int) ($bundle['draft']['is_submitted'] ?? 0),
    ];
}
