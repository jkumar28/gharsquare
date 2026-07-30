<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/property.php';
require_once BASE_PATH . '/includes/map_location.php';

header('Content-Type: application/json; charset=utf-8');

function postPropertyResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function currentPublicDraft(int $draftId): array
{
    $user = publicUser();

    if (!$user || $draftId <= 0) {
        throw new RuntimeException('Draft not found.');
    }

    $draft = findPropertyDraft($draftId);

    if (!$draft || (int) ($draft['user_id'] ?? 0) !== (int) $user['id']) {
        throw new RuntimeException('Draft not found.');
    }

    return $draft;
}

function nullableString(array $input, string $key, int $limit = 255): ?string
{
    $value = trim((string) ($input[$key] ?? ''));

    return $value !== '' ? substr($value, 0, $limit) : null;
}

function nullableDate(array $input, string $key): ?string
{
    $value = trim((string) ($input[$key] ?? ''));

    return $value !== '' && strtotime($value) !== false ? $value : null;
}

function nullableNumber(array $input, string $key): ?string
{
    $value = trim((string) ($input[$key] ?? ''));

    return $value !== '' && is_numeric($value) && (float) $value >= 0 ? $value : null;
}

function nullableCoordinate(array $input, string $key): ?string
{
    $value = trim((string) ($input[$key] ?? ''));

    return $value !== '' && is_numeric($value) ? $value : null;
}

function nullableIntValue(array $input, string $key): ?int
{
    $value = trim((string) ($input[$key] ?? ''));

    return $value !== '' && preg_match('/^\d+$/', $value) ? (int) $value : null;
}

function validIdOrNull(array $input, string $key, callable $finder): ?int
{
    $id = (int) ($input[$key] ?? 0);

    return $id > 0 && $finder($id) ? $id : null;
}

function publicDraftPrimaryArea(array $profile, string $category): ?float
{
    $keys = $category === 'land'
        ? ['plot_area']
        : ['builtup_area', 'carpet_area', 'super_builtup_area', 'plot_area'];

    foreach ($keys as $key) {
        $value = trim((string) ($profile[$key] ?? ''));

        if ($value !== '' && is_numeric($value) && (float) $value > 0) {
            return (float) $value;
        }
    }

    return null;
}

function publicIsResidentialLandType(?array $propertyType): bool
{
    if (!$propertyType || (string) ($propertyType['category'] ?? '') !== 'land') {
        return false;
    }

    $name = strtolower(trim((string) ($propertyType['name'] ?? '')));

    return str_contains($name, 'residential');
}

function publicDraftStepValidationErrors(int $draftId, string $step, array $input): array
{
    $bundle = getPropertyDraftBundle($draftId);
    $user = publicUser();

    if ($step === 'basic') {
        $validation = validatePropertyBasicInput(array_merge($input, [
            'user_id' => (int) ($user['id'] ?? 0),
        ]));
        $errors = $validation['errors'];
        $category = trim((string) ($input['property_category'] ?? ''));
        $propertyType = findPropertyType((int) ($input['property_type_id'] ?? 0));
        $listingType = findListingType((int) ($input['listing_type_id'] ?? 0));
        $isSellListing = strtolower(trim((string) ($listingType['name'] ?? ''))) === 'sell';

        if (!in_array($category, ['residential', 'commercial', 'land'], true)) {
            $errors[] = 'Please select a property category.';
        } elseif ($propertyType && (string) $propertyType['category'] !== $category) {
            $errors[] = 'Please select a property subtype from the chosen category.';
        }

        if (!$isSellListing && publicIsResidentialLandType($propertyType)) {
            $errors[] = 'Residential plot is available only for sell listings.';
        }

        if (trim((string) ($input['property_group'] ?? '')) === '') {
            $errors[] = 'Please select a property group.';
        }

        return array_values(array_unique($errors));
    }

    if ($step === 'location') {
        return validatePropertyLocationInput($input)['errors'];
    }

    if ($step === 'profile') {
        return validatePropertyProfileInput($input, $bundle['basic'])['errors'];
    }

    if ($step === 'pricing') {
        return validatePropertyPricingInput($input, $bundle['basic'])['errors'];
    }

    if ($step === 'amenities') {
        $amenityIds = array_values(array_filter(array_map('intval', $input['amenity_ids'] ?? [])));

        return $amenityIds ? [] : ['Please select at least one amenity.'];
    }

    if ($step === 'media') {
        return [];
    }

    if ($step === 'review') {
        return validatePropertyDescriptionInput($input)['errors'];
    }

    return ['Invalid property step.'];
}

function savePublicDraftStep(int $draftId, string $step, array $input): void
{
    $user = publicUser();

    if (!$user) {
        throw new RuntimeException('Login required.');
    }

    if ($step === 'basic') {
        $listingTypeId = validIdOrNull($input, 'listing_type_id', 'findListingType');
        $propertyTypeId = validIdOrNull($input, 'property_type_id', 'findPropertyType');
        $listingType = $listingTypeId ? findListingType($listingTypeId) : null;
        $propertyType = $propertyTypeId ? findPropertyType($propertyTypeId) : null;
        $listingName = strtolower(trim((string) ($listingType['name'] ?? '')));
        $isPgListing = $listingName === 'pg'
            || str_contains($listingName, 'paying guest')
            || str_contains($listingName, 'co-living');

        if ($isPgListing && (string) ($propertyType['category'] ?? '') !== 'residential') {
            $propertyTypeId = null;
        }
        if ($listingName !== 'sell' && publicIsResidentialLandType($propertyType)) {
            $propertyTypeId = null;
        }

        $title = nullableString($input, 'title');
        $postedBy = trim((string) ($input['posted_by'] ?? ''));

        db()->prepare('UPDATE property_drafts SET user_id = :user_id, updated_at = NOW() WHERE id = :id')
            ->execute([':user_id' => (int) $user['id'], ':id' => $draftId]);

        upsertDraftSection('property_basic', $draftId, [
            'property_type_id' => $propertyTypeId,
            'listing_type_id' => $listingTypeId,
            'title' => $title,
            'slug' => $title ? slugify($title) . '-' . $draftId : null,
            'posted_by' => in_array($postedBy, ['owner', 'agent', 'builder'], true) ? $postedBy : null,
            'purpose_note' => nullableString($input, 'purpose_note', 150),
            'available_from' => nullableDate($input, 'available_from'),
        ]);
    } elseif ($step === 'location') {
        upsertDraftSection('property_location', $draftId, [
            'country_id' => validIdOrNull($input, 'country_id', 'findCountry'),
            'state_id' => validIdOrNull($input, 'state_id', 'findState'),
            'city_id' => validIdOrNull($input, 'city_id', 'findCity'),
            'locality_id' => validIdOrNull($input, 'locality_id', 'findLocality'),
            'address_line' => nullableString($input, 'address_line'),
            'map_address' => nullableString($input, 'map_address'),
            'landmark' => nullableString($input, 'landmark', 150),
            'pincode' => nullableString($input, 'pincode', 10),
            'latitude' => nullableCoordinate($input, 'latitude'),
            'longitude' => nullableCoordinate($input, 'longitude'),
            'is_map_exact' => isset($input['is_map_exact']) ? 1 : 0,
        ]);
    } elseif ($step === 'profile') {
        $areaUnit = trim((string) ($input['area_unit'] ?? 'sq.ft'));
        $profileBasic = draftSectionRow('property_basic', $draftId) ?? [];
        $profileCategory = propertyTypeCategoryFromBasic($profileBasic);
        $isOfficeProfile = isOfficePropertyBasic($profileBasic);
        $isPgProfile = isPgListingBasic($profileBasic);
        $allowedAreaUnits = areaUnitOptionsForCategory($profileCategory);
        $furnishing = trim((string) ($input['furnishing'] ?? ''));
        $propertyAge = trim((string) ($input['property_age'] ?? ''));
        $facing = trim((string) ($input['facing'] ?? ''));
        $ownershipType = trim((string) ($input['ownership_type'] ?? ''));
        $profileData = [
            'builtup_area' => nullableNumber($input, 'builtup_area'),
            'super_builtup_area' => nullableNumber($input, 'super_builtup_area'),
            'carpet_area' => nullableNumber($input, 'carpet_area'),
            'plot_area' => nullableNumber($input, 'plot_area'),
            'area_unit' => array_key_exists($areaUnit, $allowedAreaUnits) ? $areaUnit : 'sq.ft',
            'bedrooms' => nullableIntValue($input, 'bedrooms'),
            'bathrooms' => nullableIntValue($input, 'bathrooms'),
            'balconies' => nullableIntValue($input, 'balconies'),
            'parking_count' => nullableIntValue($input, 'parking_count'),
            'servant_room' => isset($input['servant_room']) ? 1 : 0,
            'pooja_room' => isset($input['pooja_room']) ? 1 : 0,
            'study_room' => isset($input['study_room']) ? 1 : 0,
            'floor_no' => nullableIntValue($input, 'floor_no'),
            'total_floor' => nullableIntValue($input, 'total_floor'),
            'furnishing' => in_array($furnishing, ['unfurnished', 'semi', 'fully'], true) ? $furnishing : null,
            'property_age' => array_key_exists($propertyAge, propertyAgeOptions()) ? $propertyAge : null,
            'facing' => array_key_exists($facing, facingOptions()) ? $facing : null,
            'ownership_type' => array_key_exists($ownershipType, ownershipTypeOptions()) ? $ownershipType : null,
        ];

        if (tableHasColumn('property_profile', 'furnishing_items')) {
            $profileData['furnishing_items'] = propertyFurnishingItemsStorageValue($input['furnishing_items'] ?? [], $furnishing);
        }
        if (tableHasColumn('property_profile', 'profile_details')) {
            if ($isOfficeProfile) {
                $profileData['profile_details'] = propertyProfileDetailsStorageValue(normalizePropertyOfficeProfileDetails($input));
            } elseif ($isPgProfile) {
                $profileData['profile_details'] = propertyProfileDetailsStorageValue(normalizePropertyPgProfileDetails($input));
            } else {
                $profileData['profile_details'] = null;
            }
        }

        upsertDraftSection('property_profile', $draftId, $profileData);
    } elseif ($step === 'pricing') {
        $maintenancePeriod = trim((string) ($input['maintenance_period'] ?? ''));
        $bundle = getPropertyDraftBundle($draftId);
        $validation = validatePropertyPricingInput($input, $bundle['basic']);
        $validated = $validation['data'];
        $propertyType = !empty($bundle['basic']['property_type_id']) ? findPropertyType((int) $bundle['basic']['property_type_id']) : null;
        $category = (string) ($propertyType['category'] ?? 'residential');
        $amount = isSellListingBasic($bundle['basic'])
            ? ($validated['expected_price'] !== '' && is_numeric($validated['expected_price']) ? $validated['expected_price'] : null)
            : ($validated['rent'] !== '' && is_numeric($validated['rent']) ? $validated['rent'] : null);
        $area = publicDraftPrimaryArea($bundle['profile'], $category);
        $pricePerUnit = $amount !== null && $area !== null && $area > 0 ? round((float) $amount / $area, 2) : null;
        $pricingData = [
            'expected_price' => $validated['expected_price'] !== '' && is_numeric($validated['expected_price']) ? $validated['expected_price'] : null,
            'rent' => $validated['rent'] !== '' && is_numeric($validated['rent']) ? $validated['rent'] : null,
            'deposit' => $validated['deposit'] !== '' ? substr($validated['deposit'], 0, 100) : null,
            'booking_amount' => $validated['booking_amount'] !== '' && is_numeric($validated['booking_amount']) ? $validated['booking_amount'] : null,
            'maintenance' => $validated['maintenance'] !== '' && is_numeric($validated['maintenance']) ? $validated['maintenance'] : null,
            'maintenance_period' => array_key_exists($maintenancePeriod, maintenancePeriodOptions()) ? $maintenancePeriod : null,
            'electricity_charges' => $validated['electricity_charges'] !== '' ? substr($validated['electricity_charges'], 0, 100) : null,
            'brokerage' => $validated['brokerage'] !== '' ? substr($validated['brokerage'], 0, 100) : null,
            'negotiable' => $validated['negotiable'],
        ];

        $structuredColumns = [
            'security_deposit_type' => $validated['security_deposit_type'] !== '' ? $validated['security_deposit_type'] : null,
            'security_deposit_amount' => $validated['security_deposit_amount'] !== '' && is_numeric($validated['security_deposit_amount']) ? $validated['security_deposit_amount'] : null,
            'security_deposit_months' => $validated['security_deposit_months'] !== '' && ctype_digit($validated['security_deposit_months']) ? (int) $validated['security_deposit_months'] : null,
            'brokerage_type' => $validated['brokerage_type'] !== '' ? $validated['brokerage_type'] : null,
            'brokerage_value' => $validated['brokerage_value'] !== '' && is_numeric($validated['brokerage_value']) ? $validated['brokerage_value'] : null,
            'brokerage_negotiable' => $validated['brokerage_negotiable'],
            'lock_in_months' => $validated['lock_in_months'] !== '' && ctype_digit($validated['lock_in_months']) ? (int) $validated['lock_in_months'] : null,
            'annual_rent_increase_percent' => $validated['annual_rent_increase_percent'] !== '' && is_numeric($validated['annual_rent_increase_percent']) ? $validated['annual_rent_increase_percent'] : null,
            'dg_ups_included' => $validated['dg_ups_included'],
            'electricity_water_excluded' => $validated['electricity_water_excluded'],
        ];

        foreach ($structuredColumns as $column => $value) {
            if (tableHasColumn('property_pricing', $column)) {
                $pricingData[$column] = $value;
            }
        }

        if (tableHasColumn('property_pricing', 'price_per_area_unit')) {
            $pricingData['price_per_area_unit'] = $pricePerUnit;
        }

        if (tableHasColumn('property_pricing', 'price_per_sqft')) {
            $pricingData['price_per_sqft'] = ($bundle['profile']['area_unit'] ?? 'sq.ft') === 'sq.ft' ? $pricePerUnit : null;
        }

        upsertDraftSection('property_pricing', $draftId, $pricingData);
    } elseif ($step === 'amenities') {
        $ids = array_map('intval', $input['amenity_ids'] ?? []);
        $validIds = array_map(static fn (array $row): int => (int) $row['id'], amenitiesAll());
        $ids = array_values(array_intersect(array_unique(array_filter($ids)), $validIds));
        savePropertyAmenities($draftId, $ids);
    } elseif ($step === 'media') {
        // Media is persisted by post-property-media.php. This step only refreshes progress.
    } elseif ($step === 'review') {
        upsertDraftSection('property_basic', $draftId, [
            'description' => nullableString($input, 'description', 5000),
        ]);
    } else {
        throw new RuntimeException('Invalid step.');
    }

    $bundle = getPropertyDraftBundle($draftId);
    $progressStep = in_array($step, propertyStepOrder(), true) ? $step : null;
    saveDraftProgress($draftId, $bundle, $progressStep);
}

if (!isPublicUserLoggedIn()) {
    postPropertyResponse([
        'success' => false,
        'login_required' => true,
        'login_url' => publicAuthLoginUrl(publicAuthCurrentUrl()),
    ], 401);
}

if (!isPostRequest()) {
    postPropertyResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    postPropertyResponse(['success' => false, 'message' => 'Security token expired. Please refresh.'], 419);
}

$draftId = (int) ($_POST['draft_id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? 'save_step'));

try {
    currentPublicDraft($draftId);

    if ($action === 'save_step') {
        $step = trim((string) ($_POST['step'] ?? ''));
        $stepInput = $_POST;

        if ($step === 'location') {
            $indiaCountry = findCountryByName('India');
            if (!$indiaCountry) {
                throw new RuntimeException('India is not configured in country master.');
            }
            $stepInput['country_id'] = (int) $indiaCountry['id'];
            $stepInput = resolveMapLocationHierarchy($stepInput);
        }

        if (isset($_POST['validate_step'])) {
            $errors = publicDraftStepValidationErrors($draftId, $step, $stepInput);

            if ($errors) {
                postPropertyResponse([
                    'success' => false,
                    'message' => (string) $errors[0],
                    'errors' => $errors,
                ], 422);
            }
        }

        savePublicDraftStep($draftId, $step, $stepInput);
        recordUserActivity('property_draft_save', [
            'entity_type' => 'property_draft',
            'entity_id' => (string) $draftId,
            'metadata' => ['step' => $step],
        ]);

        $response = [
            'success' => true,
            'message' => ucfirst($step) . ' saved as draft.',
            'progress' => propertyProgressPayload($draftId),
        ];

        if ($step === 'location') {
            $state = findState((int) ($stepInput['state_id'] ?? 0));
            $city = findCity((int) ($stepInput['city_id'] ?? 0));
            $locality = findLocality((int) ($stepInput['locality_id'] ?? 0));
            $response['location'] = [
                'country_id' => (int) ($stepInput['country_id'] ?? 0),
                'state_id' => (int) ($state['id'] ?? 0),
                'state_name' => (string) ($state['name'] ?? ''),
                'city_id' => (int) ($city['id'] ?? 0),
                'city_name' => (string) ($city['name'] ?? ''),
                'locality_id' => (int) ($locality['id'] ?? 0),
                'locality_name' => (string) ($locality['name'] ?? ''),
                'pincode' => (string) ($locality['pincode'] ?? ($stepInput['pincode'] ?? '')),
            ];
        }

        postPropertyResponse($response);
    }

    if ($action === 'submit') {
        if (trim((string) ($_POST['description'] ?? '')) === '') {
            postPropertyResponse(['success' => false, 'message' => 'Please add a short property description before submitting.'], 422);
        }

        savePublicDraftStep($draftId, 'review', $_POST);
        submitPropertyDraft($draftId);
        recordUserActivity('property_submit', [
            'entity_type' => 'property_draft',
            'entity_id' => (string) $draftId,
            'metadata' => ['source' => 'public_post_property'],
        ]);

        postPropertyResponse([
            'success' => true,
            'message' => 'Property submitted for review.',
            'progress' => propertyProgressPayload($draftId),
            'redirect_url' => 'account?view=properties',
        ]);
    }

    postPropertyResponse(['success' => false, 'message' => 'Invalid action.'], 422);
} catch (Throwable $exception) {
    if ($exception instanceof PDOException) {
        error_log('Property draft database error: ' . $exception->getMessage());
        postPropertyResponse([
            'success' => false,
            'message' => 'We could not save this location. Please select the map address again and retry.',
        ], 422);
    }

    postPropertyResponse(['success' => false, 'message' => $exception->getMessage()], 422);
}
