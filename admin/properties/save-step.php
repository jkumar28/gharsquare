<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/properties/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Your session token expired. Please refresh and try again.'], 419);
}

$draftId = (int) ($_POST['draft_id'] ?? 0);
$step = trim((string) ($_POST['step'] ?? ''));
$draft = $draftId > 0 ? findPropertyDraft($draftId) : null;

if (!$draft) {
    jsonResponse(['success' => false, 'message' => 'Property draft not found.'], 404);
}

try {
    if ($step === 'basic') {
        $validation = validatePropertyBasicInput($_POST);

        if ($validation['errors'] !== []) {
            jsonResponse(['success' => false, 'message' => implode(' ', $validation['errors'])], 422);
        }

        $stmt = db()->prepare('UPDATE property_drafts SET user_id = :user_id, current_step = 1, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':user_id' => $validation['data']['user_id'],
            ':id' => $draftId,
        ]);

        upsertDraftSection('property_basic', $draftId, [
            'property_type_id' => $validation['data']['property_type_id'],
            'listing_type_id' => $validation['data']['listing_type_id'],
            'title' => $validation['data']['title'],
            'slug' => slugify($validation['data']['title']) . '-' . $draftId,
            'description' => $validation['data']['description'],
            'posted_by' => $validation['data']['posted_by'],
            'purpose_note' => $validation['data']['purpose_note'] !== '' ? $validation['data']['purpose_note'] : null,
            'available_from' => $validation['data']['available_from'] !== '' ? $validation['data']['available_from'] : null,
        ]);
    } elseif ($step === 'location') {
        $validation = validatePropertyLocationInput($_POST);
        if ($validation['errors'] !== []) {
            jsonResponse(['success' => false, 'message' => implode(' ', $validation['errors'])], 422);
        }

        upsertDraftSection('property_location', $draftId, [
            'country_id' => $validation['data']['country_id'],
            'state_id' => $validation['data']['state_id'],
            'city_id' => $validation['data']['city_id'],
            'locality_id' => $validation['data']['locality_id'],
            'address_line' => $validation['data']['address_line'] ?? '',
            'map_address' => $validation['data']['map_address'] !== '' ? $validation['data']['map_address'] : null,
            'landmark' => $validation['data']['landmark'] !== '' ? $validation['data']['landmark'] : null,
            'pincode' => $validation['data']['pincode'],
            'latitude' => $validation['data']['latitude'] !== '' ? $validation['data']['latitude'] : null,
            'longitude' => $validation['data']['longitude'] !== '' ? $validation['data']['longitude'] : null,
            'is_map_exact' => $validation['data']['is_map_exact'],
        ]);
    } elseif ($step === 'profile') {
        $basic = draftSectionRow('property_basic', $draftId) ?? [];
        $validation = validatePropertyProfileInput($_POST, $basic);
        if ($validation['errors'] !== []) {
            jsonResponse(['success' => false, 'message' => implode(' ', $validation['errors'])], 422);
        }

        $profileData = [
            'builtup_area' => $validation['data']['builtup_area'] !== '' ? $validation['data']['builtup_area'] : null,
            'super_builtup_area' => $validation['data']['super_builtup_area'] !== '' ? $validation['data']['super_builtup_area'] : null,
            'carpet_area' => $validation['data']['carpet_area'] !== '' ? $validation['data']['carpet_area'] : null,
            'plot_area' => $validation['data']['plot_area'] !== '' ? $validation['data']['plot_area'] : null,
            'area_unit' => $validation['data']['area_unit'],
            'bedrooms' => $validation['data']['bedrooms'] !== '' ? $validation['data']['bedrooms'] : null,
            'bathrooms' => $validation['data']['bathrooms'] !== '' ? $validation['data']['bathrooms'] : null,
            'balconies' => $validation['data']['balconies'] !== '' ? $validation['data']['balconies'] : null,
            'parking_count' => $validation['data']['parking_count'] !== '' ? $validation['data']['parking_count'] : null,
            'servant_room' => $validation['data']['servant_room'],
            'pooja_room' => $validation['data']['pooja_room'],
            'study_room' => $validation['data']['study_room'],
            'floor_no' => $validation['data']['floor_no'] !== '' ? $validation['data']['floor_no'] : null,
            'total_floor' => $validation['data']['total_floor'] !== '' ? $validation['data']['total_floor'] : null,
            'furnishing' => $validation['data']['furnishing'],
            'property_age' => $validation['data']['property_age'],
            'facing' => $validation['data']['facing'],
            'ownership_type' => $validation['data']['ownership_type'],
        ];

        if (
            tableHasColumn('property_profile', 'furnishing_items')
            && (isset($_POST['furnishing_items_present']) || array_key_exists('furnishing_items', $_POST))
        ) {
            $profileData['furnishing_items'] = propertyFurnishingItemsStorageValue(
                $_POST['furnishing_items'] ?? [],
                $validation['data']['furnishing']
            );
        }
        if (
            tableHasColumn('property_profile', 'profile_details')
            && (
                isset($_POST['office_profile_present'])
                || isset($_POST['pg_profile_present'])
                || array_key_exists('office_min_seats', $_POST)
                || array_key_exists('pg_room_type', $_POST)
            )
        ) {
            $profileData['profile_details'] = (isOfficePropertyBasic($basic) || isPgListingBasic($basic))
                ? propertyProfileDetailsStorageValue($validation['data']['profile_details'])
                : null;
        }

        upsertDraftSection('property_profile', $draftId, $profileData);
    } elseif ($step === 'pricing') {
        $basic = draftSectionRow('property_basic', $draftId) ?? [];
        $validation = validatePropertyPricingInput($_POST, $basic);
        if ($validation['errors'] !== []) {
            jsonResponse(['success' => false, 'message' => implode(' ', $validation['errors'])], 422);
        }

        $pricingData = [
            'expected_price' => $validation['data']['expected_price'] !== '' ? $validation['data']['expected_price'] : null,
            'rent' => $validation['data']['rent'] !== '' ? $validation['data']['rent'] : null,
            'deposit' => $validation['data']['deposit'] !== '' ? $validation['data']['deposit'] : null,
            'booking_amount' => $validation['data']['booking_amount'] !== '' ? $validation['data']['booking_amount'] : null,
            'maintenance' => $validation['data']['maintenance'] !== '' ? $validation['data']['maintenance'] : null,
            'electricity_charges' => $validation['data']['electricity_charges'] !== '' ? $validation['data']['electricity_charges'] : null,
            'brokerage' => $validation['data']['brokerage'] !== '' ? $validation['data']['brokerage'] : null,
            'negotiable' => $validation['data']['negotiable'],
        ];

        if (tableHasColumn('property_pricing', 'maintenance_period')) {
            $pricingData['maintenance_period'] = $validation['data']['maintenance_period'] !== '' ? $validation['data']['maintenance_period'] : null;
        }

        if (tableHasColumn('property_pricing', 'price_per_area_unit')) {
            $pricingData['price_per_area_unit'] = null;
        }

        if (tableHasColumn('property_pricing', 'price_per_sqft')) {
            $pricingData['price_per_sqft'] = null;
        }

        upsertDraftSection('property_pricing', $draftId, $pricingData);
    } elseif ($step === 'amenities') {
        $validation = validateAmenitiesInput($_POST);
        savePropertyAmenities($draftId, $validation['data']['amenity_ids']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Invalid property step.'], 422);
    }

    $bundle = getPropertyDraftBundle($draftId);
    saveDraftProgress($draftId, $bundle, $step);
    $payload = propertyProgressPayload($draftId);

    jsonResponse([
        'success' => true,
        'message' => ucfirst($step) . ' saved successfully.',
        'progress' => $payload,
    ]);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Unable to save this step right now.'], 500);
}
