<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/property.php';

if (!isPublicUserLoggedIn()) {
    redirect(publicAuthLoginUrl(publicAuthCurrentUrl()));
}

$user = publicUser();
$draftId = (int) ($_GET['draft_id'] ?? 0);

if ($draftId > 0) {
    $draft = findPropertyDraft($draftId);

    if (!$draft || (int) ($draft['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
        setFlash('danger', 'Property draft not found.');
        redirect('account?view=properties');
    }
} else {
    $draftId = createPropertyDraft((int) ($user['id'] ?? 0));
    recordUserActivity('property_draft_start', [
        'entity_type' => 'property_draft',
        'entity_id' => (string) $draftId,
    ]);
    redirect('post-property?draft_id=' . $draftId);
}

$bundle = getPropertyDraftBundle($draftId);
$propertyTypes = propertyTypesAll();
$listingTypes = listingTypesAll();
$listingTypePreferredOrder = ['Sell', 'Rent / Lease', 'PG'];
usort(
    $listingTypes,
    static function (array $left, array $right) use ($listingTypePreferredOrder): int {
        $leftPosition = array_search((string) ($left['name'] ?? ''), $listingTypePreferredOrder, true);
        $rightPosition = array_search((string) ($right['name'] ?? ''), $listingTypePreferredOrder, true);
        $leftPosition = $leftPosition === false ? 999 : $leftPosition;
        $rightPosition = $rightPosition === false ? 999 : $rightPosition;

        if ($leftPosition === $rightPosition) {
            return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        }

        return $leftPosition <=> $rightPosition;
    }
);
$countries = countryOptions();
$indiaCountry = findCountryByName('India');

if (!$indiaCountry) {
    throw new RuntimeException('India must be configured in country master before users can post properties.');
}

$publicCountryId = (int) $indiaCountry['id'];
$states = statesAll();
$cities = citiesAll();
$localities = localitiesAll();
$amenities = amenitiesAll();
$areaUnits = areaUnitOptions();
$ageOptions = propertyAgeOptions();
$facingOptions = facingOptions();
$ownershipOptions = ownershipTypeOptions();
$maintenanceOptions = maintenancePeriodOptions();
$progress = $bundle['progress'];
$basic = $bundle['basic'];
$location = $bundle['location'];
$selectedLocality = !empty($location['locality_id']) ? findLocality((int) $location['locality_id']) : null;
$profile = $bundle['profile'];
$pricing = $bundle['pricing'];
$media = $bundle['media'];
$furnishingOptions = propertyFurnishingOptions();
$furnishingItemOptions = propertyFurnishingItemOptions();
$selectedFurnishingItems = normalizePropertyFurnishingItems($profile['furnishing_items'] ?? [], (string) ($profile['furnishing'] ?? ''));
$fullyFurnishedDefaults = ['light_fan', 'wardrobe', 'bed', 'sofa', 'dining_table', 'modular_kitchen', 'geyser', 'ac', 'curtains', 'tv', 'fridge', 'washing_machine'];
$pgFurnishingDefaults = ['ac', 'bed', 'light_fan', 'geyser', 'curtains'];
$pgFurnishingItems = ['ac', 'bed', 'light_fan', 'geyser', 'curtains', 'wardrobe', 'tv', 'fridge', 'washing_machine'];
$profileDetails = propertyProfileDetails($profile);
$officeProfile = propertyOfficeProfileSummary($profile);
$officeFacilities = is_array($officeProfile['facilities'] ?? null) ? $officeProfile['facilities'] : [];
$officeFireSafety = array_values(array_filter(array_map('strval', $officeProfile['fire_safety'] ?? [])));
$pgProfile = propertyPgProfileSummary($profile);
$pgSuitableFor = array_values(array_filter(array_map('strval', $pgProfile['suitable_for'] ?? [])));
$stepMeta = $progress['step_meta'];
$categoryLabels = propertyTypeCategories();
$selectedPropertyType = !empty($basic['property_type_id']) ? findPropertyType((int) $basic['property_type_id']) : null;
$selectedCategory = (string) ($selectedPropertyType['category'] ?? '');
$flash = getFlash();
$googleMapsEnabled = GOOGLE_MAPS_API_KEY !== '';
$mediaGridHtml = propertyPublicMediaGridHtml($media);
$imageCount = propertyImageCount($media);
$securityDepositType = array_key_exists((string) ($pricing['security_deposit_type'] ?? ''), securityDepositTypeOptions())
    ? (string) $pricing['security_deposit_type']
    : ((string) ($pricing['deposit'] ?? '') !== '' ? 'multiple' : 'none');
$securityDepositMonths = (string) ($pricing['security_deposit_months'] ?? ($securityDepositType === 'multiple' ? ($pricing['deposit'] ?? '') : ''));
$brokerageType = array_key_exists((string) ($pricing['brokerage_type'] ?? ''), brokerageTypeOptions())
    ? (string) $pricing['brokerage_type']
    : 'none';
$selectedPostedBy = trim((string) ($basic['posted_by'] ?? ''));
$accountPostingRole = strtolower(trim((string) ($user['role'] ?? '')));

if ($selectedPostedBy === '' && in_array($accountPostingRole, ['agent', 'builder', 'owner'], true)) {
    $selectedPostedBy = $accountPostingRole;
}

function selectedAttr(mixed $left, mixed $right): string
{
    return (string) $left === (string) $right ? ' selected' : '';
}

function checkedAttr(mixed $value): string
{
    return !empty($value) ? ' checked' : '';
}

function publicPropertyTypeFlowPayload(array $propertyTypes): array
{
    $byName = [];

    foreach ($propertyTypes as $type) {
        $byName[strtolower(trim((string) $type['name']))] = [
            'id' => (int) $type['id'],
            'name' => (string) $type['name'],
            'category' => (string) $type['category'],
        ];
    }

    $resolve = static function (array $names) use ($byName): array {
        $resolved = [];

        foreach ($names as $name) {
            $type = $byName[strtolower($name)] ?? null;

            if ($type) {
                $resolved[] = $type;
            }
        }

        return $resolved;
    };

    $group = static fn (string $key, string $label, string $question, array $names): array => [
        'key' => $key,
        'label' => $label,
        'question' => $question,
        'types' => $resolve($names),
    ];

    return [
        'residential' => [
            $group('apartment', 'Apartment', 'What kind of apartment is it?', ['Flat / Apartment', 'Studio Apartment', 'Serviced Apartment']),
            $group('house-villa', 'House / Villa', 'What kind of house is it?', ['Independent House / Villa', 'Farm House']),
            $group('builder-floor', 'Builder Floor', 'Confirm the residential type', ['Builder Floor']),
        ],
        'pg' => [
            $group('shared-living', 'PG / Co-living', 'What kind of shared accommodation is it?', ['PG / Co-living', 'Hostel']),
            $group('private-room', 'Private Room', 'Confirm the accommodation type', ['Private Room']),
        ],
        'commercial' => [
            $group('office', 'Office', 'What kind of office is it?', ['Office', 'ready to move office space', 'Bare shell office space', 'Co-working office space']),
            $group('retail', 'Retail', 'What type of retail space do you have?', ['Commercial Shop', 'Commercial Showroom']),
            $group('storage', 'Storage', 'What kind of storage is it?', ['Warehouse', 'Cold Storage']),
            $group('industry', 'Industry', 'What kind of industrial property is it?', ['Factory', 'Manufacturing Unit']),
            $group('hospitality', 'Hospitality', 'What kind of hospitality property is it?', ['Hotel / Resort', 'Guest House']),
            $group('other-commercial', 'Other', 'Select the closest property type', ['Other Commercial Property']),
        ],
        'land' => [
            $group('residential-land', 'Residential Plot', 'What kind of plot / land is it?', ['Residential Plot', 'Residential Land']),
            $group('commercial-land', 'Commercial / Institutional', 'What kind of plot / land is it?', ['Commercial Land', 'Institutional Land']),
            $group('farm-land', 'Agricultural / Farm', 'What kind of plot / land is it?', ['Agricultural / Farm Land']),
            $group('industrial-land', 'Industrial Land', 'What kind of plot / land is it?', ['Industrial Land / Plot']),
            $group('other-land', 'Other', 'Select the closest land type', ['Other Land']),
        ],
    ];
}

$propertyTypeFlow = publicPropertyTypeFlowPayload($propertyTypes);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Post Property - GharSquare</title>
    <meta name="description" content="Post a property on GharSquare using a step-by-step draft wizard.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(APP_URL) ?>/website/assets/css/style.css?v=<?= e((string) filemtime(__DIR__ . '/assets/css/style.css')) ?>" rel="stylesheet">
</head>

<body class="post-property-page">
    <nav class="navbar navbar-expand-lg fixed-top premium-navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="./" aria-label="GharSquare home">
                <span class="logo-icon"><i class="bi bi-house-fill"></i></span>
                Ghar<span>Square</span>
            </a>
            <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto gap-lg-4">
                    <li><a class="nav-link" href="./">Home</a></li>
                    <li><a class="nav-link" href="<?= e(APP_URL) ?>/properties?type=buy">Buyers</a></li>
                    <li><a class="nav-link" href="<?= e(APP_URL) ?>/properties?type=rent">Tenants</a></li>
                    <li><a class="nav-link" href="account?view=properties">My Properties</a></li>
                </ul>
                <a href="account?view=properties" class="btn btn-primary"><i class="bi bi-house-check"></i> My Properties</a>
            </div>
        </div>
    </nav>

    <main class="post-property-main">
        <section
            class="post-wizard"
            id="postPropertyWizard"
            data-draft-id="<?= e((string) $draftId) ?>"
            data-current-step="<?= e((string) ($bundle['draft']['current_step'] ?? 1)) ?>"
            data-states='<?= e(json_encode($states, JSON_UNESCAPED_SLASHES)) ?>'
            data-cities='<?= e(json_encode($cities, JSON_UNESCAPED_SLASHES)) ?>'
            data-localities='<?= e(json_encode($localities, JSON_UNESCAPED_SLASHES)) ?>'
        >
            <aside class="post-wizard-side">
                <div class="post-progress-card">
                    <span class="auth-kicker">Draft #<?= e((string) $draftId) ?></span>
                    <strong><span data-overall-progress><?= e((string) number_format((float) $progress['overall_percent'], 0)) ?></span>%</strong>
                    <p data-save-status>Saved as draft</p>
                    <div class="post-progress-bar"><span data-progress-bar style="width:<?= e((string) number_format((float) $progress['overall_percent'], 0)) ?>%"></span></div>
                </div>

                <nav class="post-step-list">
                    <?php foreach (propertyStepOrder() as $index => $stepKey): ?>
                        <?php $meta = $stepMeta[$stepKey] ?? ['title' => ucfirst($stepKey), 'percent' => 0]; ?>
                        <button type="button" class="post-step-link" data-step-target="<?= e($stepKey) ?>">
                            <span><?= e((string) ($index + 1)) ?>. <?= e((string) $meta['title']) ?></span>
                            <strong data-step-percent="<?= e($stepKey) ?>"><?= e((string) $meta['percent']) ?>%</strong>
                        </button>
                    <?php endforeach; ?>
                    <button type="button" class="post-step-link" data-step-target="review">
                        <span>7. Review</span>
                        <strong>Submit</strong>
                    </button>
                </nav>

                <div class="post-missing-box">
                    <h3>Still Needed</h3>
                    <ul data-missing-list>
                        <?php foreach (array_slice($progress['missing'], 0, 8) as $missing): ?>
                            <li><?= e((string) $missing) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <section class="post-wizard-content">
                <div class="post-wizard-head">
                    <div>
                        <span class="auth-kicker">Post Property</span>
                        <h1><?= e((string) (($basic['title'] ?? '') ?: 'Create a property listing')) ?></h1>
                        <p>Each step autosaves as a draft. Submit only when everything is ready for review.</p>
                    </div>
                    <div class="post-wizard-head-actions">
                        <a href="property-preview?draft_id=<?= e((string) $draftId) ?>" data-property-preview<?= (float) $progress['overall_percent'] >= 100 ? '' : ' hidden' ?>><i class="bi bi-eye"></i> Preview</a>
                        <a href="account?view=properties" data-property-exit>Exit</a>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="auth-alert <?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
                <?php endif; ?>

                <section class="post-step-panel" data-step-panel="basic">
                    <form class="post-step-form" data-step-form="basic">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="action" value="save_step">
                        <input type="hidden" name="step" value="basic">
                        <div class="post-panel-title"><span>Step 1</span><h2>Basic Details</h2></div>
                        <div class="post-form-grid">
                            <div class="post-field span-2">
                                <label>Listing Type</label>
                                <div class="post-choice-grid">
                                    <?php foreach ($listingTypes as $type): ?>
                                        <label class="post-choice">
                                            <input type="radio" name="listing_type_id" value="<?= e((string) $type['id']) ?>" data-listing-label="<?= e((string) $type['name']) ?>" required<?= checkedAttr((int) ($basic['listing_type_id'] ?? 0) === (int) $type['id']) ?>>
                                            <span><?= e((string) $type['name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="post-field span-2">
                                <label>Property Category</label>
                                <div class="post-choice-grid">
                                    <?php foreach ($categoryLabels as $value => $label): ?>
                                        <label class="post-choice">
                                            <input type="radio" name="property_category" value="<?= e($value) ?>" required<?= checkedAttr($selectedCategory === $value) ?>>
                                            <span><?= e($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="post-field span-2 post-native-property-type" data-native-property-type>
                                <label for="property_type_id">Property Type</label>
                                <select id="property_type_id" name="property_type_id" data-property-type-select required>
                                    <option value="">Select property type</option>
                                    <?php foreach ($propertyTypes as $type): ?>
                                        <option value="<?= e((string) $type['id']) ?>" data-category="<?= e((string) $type['category']) ?>" data-name="<?= e((string) $type['name']) ?>"<?= selectedAttr($basic['property_type_id'] ?? '', $type['id']) ?>><?= e((string) $type['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="post-type-flow span-2" data-property-type-flow hidden>
                                <div class="post-selection-block">
                                    <label data-property-group-heading>Select Property Type</label>
                                    <div class="post-type-chip-grid" data-property-group-list></div>
                                    <input type="hidden" id="property_group" name="property_group" value="">
                                </div>
                                <div class="post-selection-block" data-property-subtype-section hidden>
                                    <label data-property-subtype-heading>Select exact property type</label>
                                    <div class="post-type-chip-grid" data-property-subtype-list></div>
                                    <div class="post-field post-custom-property-type" data-custom-property-type hidden>
                                        <label for="custom_property_type">Enter the closest property type</label>
                                        <input id="custom_property_type" name="custom_property_type" type="text" maxlength="100" value="<?= e((string) ($basic['custom_property_type'] ?? '')) ?>" placeholder="Example: Banquet hall, petrol pump">
                                        <small>Enter the commonly used name for this property.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="post-field col-12">
                                <label for="posted_by">I am posting as</label>
                                <select id="posted_by" name="posted_by" required>
                                    <option value="">Select</option>
                                    <?php foreach (['owner' => 'Owner', 'agent' => 'Agent', 'builder' => 'Builder'] as $value => $label): ?>
                                        <option value="<?= e($value) ?>"<?= selectedAttr($selectedPostedBy, $value) ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="post-field">
                                <label for="available_from">Available From</label>
                                <input id="available_from" name="available_from" type="date" value="<?= e((string) ($basic['available_from'] ?? '')) ?>">
                            </div>
                            <div class="post-field span-2">
                                <label for="title">Property Title</label>
                                <input id="title" name="title" type="text" maxlength="255" required value="<?= e((string) ($basic['title'] ?? '')) ?>" placeholder="Luxury 3BHK Apartment in Bariatu">
                            </div>
                            <div class="post-field span-2">
                                <label for="purpose_note">Short Highlight</label>
                                <input id="purpose_note" name="purpose_note" type="text" maxlength="150" value="<?= e((string) ($basic['purpose_note'] ?? '')) ?>" placeholder="Park facing, corner plot, near main road">
                            </div>
                        </div>
                        <div class="post-actions"><button type="submit">Save Draft</button><button type="button" data-next-step="location">Next</button></div>
                    </form>
                </section>

                <section class="post-step-panel" data-step-panel="location">
                    <form class="post-step-form" data-step-form="location">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="action" value="save_step">
                        <input type="hidden" name="step" value="location">
                        <div class="post-panel-title"><span>Step 2</span><h2>Location</h2></div>
                        <div class="post-form-grid">
                            <div class="post-field">
                                <label for="country_display">Country</label>
                                <input id="country_display" type="text" value="<?= e((string) $indiaCountry['name']) ?>" readonly aria-readonly="true">
                                <input id="country_id" name="country_id" type="hidden" value="<?= e((string) $publicCountryId) ?>" data-country-select>
                                <input id="map_country_name" name="map_country_name" type="hidden" value="<?= e((string) $indiaCountry['name']) ?>">
                                <input id="map_state_name" name="map_state_name" type="hidden" value="">
                                <input id="map_city_name" name="map_city_name" type="hidden" value="">
                                <input id="map_locality_name" name="map_locality_name" type="hidden" value="">
                                <small>Property listings are currently available in India only.</small>
                            </div>
                            <div class="post-field">
                                <label for="state_id">State</label>
                                <select id="state_id" name="state_id" data-state-select required data-selected="<?= e((string) ($location['state_id'] ?? '')) ?>"></select>
                            </div>
                            <div class="post-field">
                                <label for="city_id">City</label>
                                <select id="city_id" name="city_id" data-city-select required data-selected="<?= e((string) ($location['city_id'] ?? '')) ?>"></select>
                            </div>
                            <div class="post-field">
                                <label for="locality_search">Locality</label>
                                <input id="locality_search" type="text" list="locality_suggestions" value="<?= e((string) ($selectedLocality['name'] ?? '')) ?>" placeholder="Search or enter locality" autocomplete="off" required data-locality-search>
                                <datalist id="locality_suggestions" data-locality-suggestions></datalist>
                                <select id="locality_id" name="locality_id" data-locality-select data-selected="<?= e((string) ($location['locality_id'] ?? '')) ?>" hidden></select>
                                <small>Select a suggestion, or type a new locality and it will be added automatically.</small>
                            </div>
                            <div class="post-field span-2">
                                <label for="address_line">Address</label>
                                <input id="address_line" name="address_line" type="text" value="<?= e((string) ($location['address_line'] ?? '')) ?>" placeholder="Building, street, society">
                            </div>
                            <div class="post-field">
                                <label for="landmark">Landmark</label>
                                <input id="landmark" name="landmark" type="text" value="<?= e((string) ($location['landmark'] ?? '')) ?>">
                            </div>
                            <div class="post-field">
                                <label for="pincode">Pincode</label>
                                <input id="pincode" name="pincode" type="text" value="<?= e((string) ($location['pincode'] ?? '')) ?>">
                            </div>
                            <div class="post-field span-2">
                                <div class="post-map-card">
                                    <div class="post-map-head">
                                        <div>
                                            <label for="map_search">Search Place on Google Map</label>
                                            <p>Search a place, click the map, or drag the pin to set the exact property point.</p>
                                        </div>
                                        <span><?= $googleMapsEnabled ? 'Map ready' : 'API key needed' ?></span>
                                    </div>
                                    <?php if ($googleMapsEnabled): ?>
                                        <div class="post-map-toolbar">
                                            <input id="map_search" type="text" placeholder="Search address, society, landmark, or locality">
                                            <button type="button" id="map_search_button">Find</button>
                                            <button type="button" id="use_map_address">Use Picked Address</button>
                                        </div>
                                        <div class="post-map-canvas" id="location_map" aria-label="Google map location picker"></div>
                                        <div class="post-map-meta">
                                            <strong data-map-address-preview><?= e((string) (($location['map_address'] ?? '') !== '' ? $location['map_address'] : 'Not selected')) ?></strong>
                                            <label class="post-check">
                                                <input type="checkbox" id="is_map_exact" name="is_map_exact" value="1"<?= checkedAttr((int) ($location['is_map_exact'] ?? 1) === 1) ?>>
                                                Pin marks exact property point
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <p class="post-map-empty">Google map picker is disabled until the Maps API key is configured.</p>
                                    <?php endif; ?>
                                    <input id="map_address" name="map_address" type="hidden" value="<?= e((string) ($location['map_address'] ?? '')) ?>">
                                </div>
                            </div>
                            <div class="post-field">
                                <label for="latitude">Latitude</label>
                                <input id="latitude" name="latitude" type="text" value="<?= e((string) ($location['latitude'] ?? '')) ?>" placeholder="Auto from map">
                            </div>
                            <div class="post-field">
                                <label for="longitude">Longitude</label>
                                <input id="longitude" name="longitude" type="text" value="<?= e((string) ($location['longitude'] ?? '')) ?>" placeholder="Auto from map">
                            </div>
                        </div>
                        <div class="post-actions"><button type="button" data-prev-step="basic">Back</button><button type="submit">Save Draft</button><button type="button" data-next-step="profile">Next</button></div>
                    </form>
                </section>

                <section class="post-step-panel" data-step-panel="profile">
                    <form class="post-step-form" data-step-form="profile">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="action" value="save_step">
                        <input type="hidden" name="step" value="profile">
                        <div class="post-panel-title"><span>Step 3</span><h2>Property Details</h2></div>
                        <p class="post-context-note" data-profile-context>Fields adjust automatically from the Basic Details selection.</p>
                        <div class="post-form-grid">
                            <div class="post-field" data-profile-field data-visible-for="residential commercial land">
                                <label for="area_unit">Area Unit</label>
                                <select id="area_unit" name="area_unit" data-area-unit-select>
                                    <?php foreach ($areaUnits as $value => $label): ?>
                                        <option value="<?= e($value) ?>"<?= selectedAttr(($profile['area_unit'] ?? 'sq.ft'), $value) ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial">
                                <label for="super_builtup_area">Super Built-up Area</label>
                                <input id="super_builtup_area" name="super_builtup_area" type="number" min="0" step="0.01" value="<?= e((string) ($profile['super_builtup_area'] ?? '')) ?>">
                            </div>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial">
                                <label for="builtup_area">Built-up Area</label>
                                <input id="builtup_area" name="builtup_area" type="number" min="0" step="0.01" value="<?= e((string) ($profile['builtup_area'] ?? '')) ?>">
                            </div>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial">
                                <label for="carpet_area">Carpet Area</label>
                                <input id="carpet_area" name="carpet_area" type="number" min="0" step="0.01" value="<?= e((string) ($profile['carpet_area'] ?? '')) ?>">
                            </div>
                            <div class="post-field" data-profile-field data-visible-for="land commercial">
                                <label for="plot_area">Plot / Land Area</label>
                                <input id="plot_area" name="plot_area" type="number" min="0" step="0.01" value="<?= e((string) ($profile['plot_area'] ?? '')) ?>">
                            </div>
                            <?php foreach (['bedrooms' => 'Bedrooms', 'bathrooms' => 'Bathrooms', 'balconies' => 'Balconies'] as $key => $label): ?>
                                <div class="post-field" data-profile-field data-visible-for="residential"><label for="<?= e($key) ?>"><?= e($label) ?></label><input id="<?= e($key) ?>" name="<?= e($key) ?>" type="number" min="0" step="1" value="<?= e((string) ($profile[$key] ?? '')) ?>"></div>
                            <?php endforeach; ?>
                            <section class="post-office-profile post-pg-profile span-2" data-profile-field data-visible-for="residential" data-pg-profile hidden>
                                <input type="hidden" name="pg_profile_present" value="1">
                                <div class="post-furnishing-head">
                                    <div>
                                        <label>Tell us about your PG</label>
                                        <p>Room sharing, availability and tenant suitability help people quickly understand the stay.</p>
                                    </div>
                                </div>
                                <div class="post-office-block">
                                    <label>Room Type</label>
                                    <?php $pgRoomType = (string) ($pgProfile['room_type'] ?? ''); ?>
                                    <div class="post-inline-options">
                                        <label><input type="radio" name="pg_room_type" value="sharing"<?= checkedAttr($pgRoomType === 'sharing') ?>> Sharing</label>
                                        <label><input type="radio" name="pg_room_type" value="private"<?= checkedAttr($pgRoomType === 'private') ?>> Private</label>
                                    </div>
                                </div>
                                <div class="post-office-block">
                                    <label>Capacity and Availability <small>optional</small></label>
                                    <div class="post-form-grid post-office-grid">
                                        <div class="post-field"><label for="pg_total_rooms">Total no. of rooms in PG</label><input id="pg_total_rooms" name="pg_total_rooms" type="number" min="0" step="1" value="<?= e((string) ($pgProfile['total_rooms'] ?? '')) ?>"></div>
                                        <div class="post-field"><label for="pg_available_rooms">No. of rooms available in PG</label><input id="pg_available_rooms" name="pg_available_rooms" type="number" min="0" step="1" value="<?= e((string) ($pgProfile['available_rooms'] ?? '')) ?>"></div>
                                    </div>
                                    <div class="post-pricing-checks">
                                        <label class="post-check"><input type="checkbox" name="pg_attached_bathroom" value="1"<?= checkedAttr((int) ($pgProfile['attached_bathroom'] ?? 0) === 1) ?>> Attached Bathroom</label>
                                        <label class="post-check"><input type="checkbox" name="pg_attached_balcony" value="1"<?= checkedAttr((int) ($pgProfile['attached_balcony'] ?? 0) === 1) ?>> Attached Balcony</label>
                                    </div>
                                </div>
                                <div class="post-office-block">
                                    <label>Other rooms <small>optional</small></label>
                                    <div class="post-furnishing-chip-grid">
                                        <label class="post-furnishing-chip"><input type="checkbox" name="pooja_room"<?= checkedAttr($profile['pooja_room'] ?? 0) ?>><span>Pooja Room</span></label>
                                        <label class="post-furnishing-chip"><input type="checkbox" name="study_room"<?= checkedAttr($profile['study_room'] ?? 0) ?>><span>Study Room</span></label>
                                        <label class="post-furnishing-chip"><input type="checkbox" name="servant_room"<?= checkedAttr($profile['servant_room'] ?? 0) ?>><span>Servant Room</span></label>
                                        <label class="post-furnishing-chip"><input type="checkbox" name="pg_store_room" value="1"<?= checkedAttr((int) ($pgProfile['store_room'] ?? 0) === 1) ?>><span>Store Room</span></label>
                                    </div>
                                </div>
                                <div class="post-office-block">
                                    <label>Common Area Furnishing</label>
                                    <label class="post-check"><input type="checkbox" name="pg_common_area_furnishing" value="1"<?= checkedAttr((int) ($pgProfile['common_area_furnishing'] ?? 0) === 1) ?>> Common area has furnishing</label>
                                </div>
                                <div class="post-office-block">
                                    <label>Reserved Parking <small>optional</small></label>
                                    <div class="post-form-grid post-office-grid">
                                        <div class="post-field"><label for="pg_covered_parking">Covered Parking</label><input id="pg_covered_parking" name="pg_covered_parking" type="number" min="0" step="1" value="<?= e((string) ($pgProfile['covered_parking'] ?? '')) ?>"></div>
                                        <div class="post-field"><label for="pg_open_parking">Open Parking</label><input id="pg_open_parking" name="pg_open_parking" type="number" min="0" step="1" value="<?= e((string) ($pgProfile['open_parking'] ?? '')) ?>"></div>
                                    </div>
                                </div>
                                <div class="post-office-block">
                                    <label>Available for</label>
                                    <?php $pgAvailableFor = (string) ($pgProfile['available_for'] ?? ''); ?>
                                    <div class="post-inline-options">
                                        <?php foreach (['girls' => 'Girls', 'boys' => 'Boys', 'any' => 'Any'] as $value => $label): ?>
                                            <label><input type="radio" name="pg_available_for" value="<?= e($value) ?>"<?= checkedAttr($pgAvailableFor === $value) ?>> <?= e($label) ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="post-office-block">
                                    <label>Suitable for</label>
                                    <div class="post-pricing-checks">
                                        <label class="post-check"><input type="checkbox" name="pg_suitable_for[]" value="students"<?= checkedAttr(in_array('students', $pgSuitableFor, true)) ?>> Students</label>
                                        <label class="post-check"><input type="checkbox" name="pg_suitable_for[]" value="working_professionals"<?= checkedAttr(in_array('working_professionals', $pgSuitableFor, true)) ?>> Working Professionals</label>
                                    </div>
                                </div>
                            </section>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial" data-office-hide-field data-pg-hide-field><label for="parking_count">Parking</label><input id="parking_count" name="parking_count" type="number" min="0" step="1" value="<?= e((string) ($profile['parking_count'] ?? '')) ?>"></div>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial"><label for="total_floor">Total Floors</label><input id="total_floor" name="total_floor" type="number" min="0" step="1" value="<?= e((string) ($profile['total_floor'] ?? '')) ?>"></div>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial">
                                <label for="floor_no">Floor No</label>
                                <select id="floor_no" name="floor_no">
                                    <option value="">Select floor</option>
                                    <option value="0"<?= selectedAttr($profile['floor_no'] ?? '', '0') ?>>Ground Floor</option>
                                    <?php for ($floorNumber = 1; $floorNumber <= 100; $floorNumber++): ?>
                                        <option value="<?= $floorNumber ?>"<?= selectedAttr($profile['floor_no'] ?? '', (string) $floorNumber) ?>>Floor <?= $floorNumber ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <section class="post-office-profile span-2" data-profile-field data-visible-for="commercial" data-office-profile hidden>
                                <input type="hidden" name="office_profile_present" value="1">
                                <div class="post-furnishing-head">
                                    <div>
                                        <label>Describe your office setup</label>
                                        <p>Office-specific details help businesses compare seating, meeting, utility and safety readiness.</p>
                                    </div>
                                </div>
                                <div class="post-form-grid post-office-grid">
                                    <div class="post-field"><label for="office_min_seats">Min. no. of Seats</label><input id="office_min_seats" name="office_min_seats" type="number" min="0" step="1" value="<?= e((string) ($officeProfile['min_seats'] ?? '')) ?>"></div>
                                    <div class="post-field"><label for="office_max_seats">Max. no. of Seats <small>optional</small></label><input id="office_max_seats" name="office_max_seats" type="number" min="0" step="1" value="<?= e((string) ($officeProfile['max_seats'] ?? '')) ?>"></div>
                                    <div class="post-field"><label for="office_cabins">No. of Cabins</label><input id="office_cabins" name="office_cabins" type="number" min="0" step="1" value="<?= e((string) ($officeProfile['cabins'] ?? '')) ?>"></div>
                                    <div class="post-field"><label for="office_meeting_rooms">No. of Meeting Rooms</label><input id="office_meeting_rooms" name="office_meeting_rooms" type="number" min="0" step="1" value="<?= e((string) ($officeProfile['meeting_rooms'] ?? '')) ?>"></div>
                                </div>

                                <div class="post-office-block">
                                    <label>Washrooms</label>
                                    <div class="post-inline-options">
                                        <?php $officeWashrooms = (string) ($officeProfile['washrooms'] ?? 'not_available'); ?>
                                        <label><input type="radio" name="office_washrooms" value="available"<?= checkedAttr($officeWashrooms === 'available') ?>> Available</label>
                                        <label><input type="radio" name="office_washrooms" value="not_available"<?= checkedAttr($officeWashrooms !== 'available') ?>> Not Available</label>
                                    </div>
                                    <div class="post-form-grid post-office-grid" data-office-washroom-counts>
                                        <div class="post-field"><label for="office_private_washrooms">No. of Private Washrooms</label><input id="office_private_washrooms" name="office_private_washrooms" type="number" min="0" step="1" value="<?= e((string) ($officeProfile['private_washrooms'] ?? '')) ?>"></div>
                                        <div class="post-field"><label for="office_shared_washrooms">No. of Shared Washrooms</label><input id="office_shared_washrooms" name="office_shared_washrooms" type="number" min="0" step="1" value="<?= e((string) ($officeProfile['shared_washrooms'] ?? '')) ?>"></div>
                                    </div>
                                </div>

                                <?php foreach (['office_conference_room' => ['Conference Room', 'conference_room'], 'office_reception_area' => ['Reception Area', 'reception_area']] as $inputName => [$label, $key]): ?>
                                    <?php $value = (string) ($officeProfile[$key] ?? 'not_available'); ?>
                                    <div class="post-office-block">
                                        <label><?= e($label) ?></label>
                                        <div class="post-inline-options">
                                            <label><input type="radio" name="<?= e($inputName) ?>" value="available"<?= checkedAttr($value === 'available') ?>> Available</label>
                                            <label><input type="radio" name="<?= e($inputName) ?>" value="not_available"<?= checkedAttr($value !== 'available') ?>> Not Available</label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="post-office-block">
                                    <label>Pantry Type</label>
                                    <?php $pantryType = (string) ($officeProfile['pantry_type'] ?? 'not_available'); ?>
                                    <div class="post-inline-options">
                                        <?php foreach (['private' => 'Private', 'shared' => 'Shared', 'not_available' => 'Not Available'] as $value => $label): ?>
                                            <label><input type="radio" name="office_pantry_type" value="<?= e($value) ?>"<?= checkedAttr($pantryType === $value) ?>> <?= e($label) ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="post-office-block">
                                    <label>Please select the facilities available</label>
                                    <div class="post-office-facility-list">
                                        <?php foreach (propertyOfficeFacilityOptions() as $key => $label): ?>
                                            <?php $value = (string) ($officeFacilities[$key] ?? 'not_available'); ?>
                                            <div class="post-office-facility">
                                                <span><?= e($label) ?></span>
                                                <label><input type="radio" name="office_facility_<?= e($key) ?>" value="available"<?= checkedAttr($value === 'available') ?>> Available</label>
                                                <label><input type="radio" name="office_facility_<?= e($key) ?>" value="not_available"<?= checkedAttr($value !== 'available') ?>> Not Available</label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="post-office-block">
                                    <label>Fire safety measures include</label>
                                    <div class="post-furnishing-chip-grid">
                                        <?php foreach (propertyOfficeFireSafetyOptions() as $value => $label): ?>
                                            <label class="post-furnishing-chip">
                                                <input type="checkbox" name="office_fire_safety[]" value="<?= e($value) ?>"<?= in_array($value, $officeFireSafety, true) ? ' checked' : '' ?>>
                                                <span><?= e($label) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="post-office-block">
                                    <label for="office_staircases">No. of Staircases <small>optional</small></label>
                                    <div class="post-field"><input id="office_staircases" name="office_staircases" type="number" min="0" step="1" value="<?= e((string) ($officeProfile['staircases'] ?? '')) ?>"></div>
                                </div>
                            </section>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial" data-office-hide-field>
                                <label for="furnishing">Furnishing</label>
                                <select id="furnishing" name="furnishing" data-furnishing-select>
                                    <option value="">Select</option>
                                    <?php foreach ($furnishingOptions as $value => $label): ?>
                                        <option value="<?= e($value) ?>"<?= selectedAttr($profile['furnishing'] ?? '', $value) ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <section class="post-furnishing-panel span-2" data-profile-field data-visible-for="residential commercial" data-furnishing-panel data-office-hide-field hidden>
                                <input type="hidden" name="furnishing_items_present" value="1">
                                <div class="post-furnishing-head">
                                    <div>
                                        <label>What is included?</label>
                                        <p>Select available furniture and appliances so tenants or buyers know what they get.</p>
                                    </div>
                                    <span data-furnishing-count><?= e((string) count($selectedFurnishingItems)) ?> selected</span>
                                </div>
                                <div class="post-furnishing-chip-grid">
                                    <?php foreach ($furnishingItemOptions as $value => $label): ?>
                                        <label class="post-furnishing-chip" data-furnishing-item-wrap data-pg-furnishing-item="<?= in_array($value, $pgFurnishingItems, true) ? '1' : '0' ?>">
                                            <input type="checkbox" name="furnishing_items[]" value="<?= e($value) ?>" data-furnishing-item<?= in_array($value, $selectedFurnishingItems, true) ? ' checked' : '' ?>>
                                            <span><?= e($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial" data-office-hide-field><label for="property_age">Property Age</label><select id="property_age" name="property_age"><option value="">Select</option><?php foreach ($ageOptions as $value => $label): ?><option value="<?= e($value) ?>"<?= selectedAttr($profile['property_age'] ?? '', $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial land" data-office-hide-field data-pg-hide-field><label for="facing">Facing</label><select id="facing" name="facing"><option value="">Select</option><?php foreach ($facingOptions as $value => $label): ?><option value="<?= e($value) ?>"<?= selectedAttr($profile['facing'] ?? '', $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                            <div class="post-field" data-profile-field data-visible-for="residential commercial land" data-office-hide-field data-pg-hide-field><label for="ownership_type">Ownership</label><select id="ownership_type" name="ownership_type"><option value="">Select</option><?php foreach ($ownershipOptions as $value => $label): ?><option value="<?= e($value) ?>"<?= selectedAttr($profile['ownership_type'] ?? '', $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                            <label class="post-check" data-profile-field data-visible-for="residential" data-pg-hide-field><input type="checkbox" name="servant_room"<?= checkedAttr($profile['servant_room'] ?? 0) ?>> Servant room</label>
                            <label class="post-check" data-profile-field data-visible-for="residential" data-pg-hide-field><input type="checkbox" name="pooja_room"<?= checkedAttr($profile['pooja_room'] ?? 0) ?>> Pooja room</label>
                            <label class="post-check" data-profile-field data-visible-for="residential commercial" data-office-hide-field data-pg-hide-field><input type="checkbox" name="study_room"<?= checkedAttr($profile['study_room'] ?? 0) ?>> Study room</label>
                        </div>
                        <div class="post-actions"><button type="button" data-prev-step="location">Back</button><button type="submit">Save Draft</button><button type="button" data-next-step="pricing">Next</button></div>
                    </form>
                </section>

                <section class="post-step-panel" data-step-panel="pricing">
                    <form class="post-step-form" data-step-form="pricing">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="action" value="save_step">
                        <input type="hidden" name="step" value="pricing">
                        <div class="post-panel-title"><span>Step 4</span><h2>Pricing</h2></div>
                        <p class="post-context-note" data-pricing-context>Pricing fields adjust from listing type and property category.</p>
                        <div class="post-form-grid">
                            <div class="post-field span-2" data-pricing-field data-pricing-mode="sell">
                                <div class="post-price-label-row">
                                    <label for="expected_price">Expected Price</label>
                                    <label class="post-check post-price-negotiable"><input type="checkbox" name="negotiable" value="1"<?= checkedAttr($pricing['negotiable'] ?? 0) ?>> Price negotiable</label>
                                </div>
                                <input id="expected_price" class="js-price-input" name="expected_price" type="number" min="0" step="0.01" value="<?= e((string) ($pricing['expected_price'] ?? '')) ?>">
                            </div>
                            <div class="post-field span-2" data-pricing-field data-pricing-mode="rent">
                                <div class="post-price-label-row">
                                    <label for="rent">Monthly Rent</label>
                                    <label class="post-check post-price-negotiable"><input type="checkbox" name="negotiable" value="1"<?= checkedAttr($pricing['negotiable'] ?? 0) ?>> Price negotiable</label>
                                </div>
                                <input id="rent" class="js-price-input" name="rent" type="number" min="0" step="0.01" value="<?= e((string) ($pricing['rent'] ?? '')) ?>">
                            </div>
                            <div class="post-price-summary span-2" data-price-summary>
                                <strong data-price-words-main>Enter amount to see price in words.</strong>
                                <span data-price-unit>Price per unit will appear after area and amount are entered.</span>
                            </div>
                            <div class="post-field" data-pricing-field data-pricing-mode="sell"><label for="booking_amount">Booking Amount</label><input id="booking_amount" class="js-price-input" name="booking_amount" type="number" min="0" step="0.01" value="<?= e((string) ($pricing['booking_amount'] ?? '')) ?>"></div>
                            <div class="post-field" data-pricing-field data-pricing-mode="both"><label for="maintenance">Maintenance</label><input id="maintenance" class="js-price-input" name="maintenance" type="number" min="0" step="0.01" value="<?= e((string) ($pricing['maintenance'] ?? '')) ?>"></div>
                            <div class="post-field" data-pricing-field data-pricing-mode="both"><label for="maintenance_period">Maintenance Period</label><select id="maintenance_period" name="maintenance_period"><option value="">Select</option><?php foreach ($maintenanceOptions as $value => $label): ?><option value="<?= e($value) ?>"<?= selectedAttr($pricing['maintenance_period'] ?? '', $value) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>

                            <section class="post-pricing-group span-2" data-pricing-field data-pricing-mode="rent">
                                <div class="post-pricing-group-head">
                                    <h3>Included Charges</h3>
                                </div>
                                <div class="post-pricing-checks">
                                    <label class="post-check"><input type="checkbox" name="dg_ups_included" value="1"<?= checkedAttr($pricing['dg_ups_included'] ?? 0) ?>> DG &amp; UPS price included</label>
                                    <label class="post-check"><input type="checkbox" name="electricity_water_excluded" value="1"<?= checkedAttr($pricing['electricity_water_excluded'] ?? 0) ?>> Electricity &amp; water charges excluded</label>
                                </div>
                                <div class="post-field">
                                    <label for="electricity_charges">Electricity Charge Details</label>
                                    <input id="electricity_charges" name="electricity_charges" type="text" maxlength="100" value="<?= e((string) ($pricing['electricity_charges'] ?? '')) ?>" placeholder="As per meter or included in rent">
                                </div>
                            </section>

                            <section class="post-pricing-group span-2" data-pricing-field data-pricing-mode="rent">
                                <div class="post-pricing-group-head">
                                    <h3>Security Deposit <small>Optional</small></h3>
                                </div>
                                <input id="deposit" name="deposit" type="hidden" value="<?= e((string) ($pricing['deposit'] ?? '')) ?>">
                                <div class="post-segmented-control" role="radiogroup" aria-label="Security deposit type">
                                    <?php foreach (securityDepositTypeOptions() as $value => $label): ?>
                                        <label>
                                            <input type="radio" name="security_deposit_type" value="<?= e($value) ?>"<?= $securityDepositType === $value ? ' checked' : '' ?>>
                                            <span><?= e($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="post-pricing-dependent">
                                    <div class="post-field" data-security-deposit-detail="fixed">
                                        <label for="security_deposit_amount">Fixed Deposit Amount</label>
                                        <input id="security_deposit_amount" name="security_deposit_amount" type="number" min="1" step="0.01" value="<?= e((string) ($pricing['security_deposit_amount'] ?? '')) ?>" placeholder="Enter amount">
                                    </div>
                                    <div class="post-field" data-security-deposit-detail="multiple">
                                        <label for="security_deposit_months">Number of Months</label>
                                        <input id="security_deposit_months" name="security_deposit_months" type="number" min="1" max="30" step="1" value="<?= e($securityDepositMonths) ?>" placeholder="Maximum 30 months">
                                    </div>
                                </div>
                            </section>

                            <section class="post-pricing-group span-2" data-pricing-field data-pricing-mode="both" data-brokerage-section>
                                <div class="post-pricing-group-head">
                                    <h3>Brokerage</h3>
                                </div>
                                <div class="post-segmented-control" role="radiogroup" aria-label="Brokerage type">
                                    <?php foreach (brokerageTypeOptions() as $value => $label): ?>
                                        <label>
                                            <input type="radio" name="brokerage_type" value="<?= e($value) ?>"<?= $brokerageType === $value ? ' checked' : '' ?>>
                                            <span><?= e($label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="post-pricing-dependent">
                                    <div class="post-field" data-brokerage-detail="fixed percentage">
                                        <label for="brokerage_value">Brokerage Charge</label>
                                        <input id="brokerage_value" name="brokerage_value" type="number" min="0.01" step="0.01" value="<?= e((string) ($pricing['brokerage_value'] ?? '')) ?>" placeholder="Enter amount or percentage">
                                    </div>
                                    <label class="post-check" data-brokerage-detail="fixed percentage"><input type="checkbox" name="brokerage_negotiable" value="1"<?= checkedAttr($pricing['brokerage_negotiable'] ?? 0) ?>> Brokerage negotiable</label>
                                </div>
                                <input name="brokerage" type="hidden" value="<?= e((string) ($pricing['brokerage'] ?? '')) ?>">
                            </section>

                            <section class="post-pricing-group span-2" data-pricing-field data-pricing-mode="rent" data-pg-pricing-hide>
                                <div class="post-pricing-group-head">
                                    <h3>Lease Terms</h3>
                                </div>
                                <div class="post-pricing-dependent">
                                    <div class="post-field">
                                        <label for="lock_in_months">Lock-in Period</label>
                                        <input id="lock_in_months" name="lock_in_months" type="number" min="1" max="120" step="1" value="<?= e((string) ($pricing['lock_in_months'] ?? '')) ?>" placeholder="Number of months">
                                    </div>
                                    <div class="post-field">
                                        <label for="annual_rent_increase_percent">Yearly Rent Increase</label>
                                        <input id="annual_rent_increase_percent" name="annual_rent_increase_percent" type="number" min="0" max="100" step="0.01" value="<?= e((string) ($pricing['annual_rent_increase_percent'] ?? '')) ?>" placeholder="Percentage">
                                    </div>
                                </div>
                            </section>

                        </div>
                        <div class="post-actions"><button type="button" data-prev-step="profile">Back</button><button type="submit">Save Draft</button><button type="button" data-next-step="amenities">Next</button></div>
                    </form>
                </section>

                <section class="post-step-panel" data-step-panel="amenities">
                    <form class="post-step-form" data-step-form="amenities">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="action" value="save_step">
                        <input type="hidden" name="step" value="amenities">
                        <div class="post-panel-title"><span>Step 5</span><h2>Amenities</h2></div>
                        <div class="post-amenity-grid">
                            <?php foreach ($amenities as $amenity): ?>
                                <label class="post-check"><input type="checkbox" name="amenity_ids[]" value="<?= e((string) $amenity['id']) ?>"<?= checkedAttr(in_array((int) $amenity['id'], $bundle['amenity_ids'], true)) ?>> <?= e((string) $amenity['name']) ?></label>
                            <?php endforeach; ?>
                        </div>
                        <div class="post-actions"><button type="button" data-prev-step="pricing">Back</button><button type="submit">Save Draft</button><button type="button" data-next-step="media">Next</button></div>
                    </form>
                </section>

                <section class="post-step-panel" data-step-panel="media">
                    <div class="post-panel-title"><span>Step 6</span><h2>Media</h2></div>
                    <p class="post-context-note">Upload up to 20 images. Every image is automatically center-cropped to 4:3, resized to 1600 × 1200, and compressed as WebP for consistent, faster listings.</p>
                    <div class="post-media-stats">
                        <div><strong data-property-image-count><?= e((string) $imageCount) ?></strong><span>Images</span></div>
                        <div><strong>20</strong><span>Max images</span></div>
                        <div><strong>20 MB</strong><span>Max video size</span></div>
                        <div><strong>60 sec</strong><span>Max video time</span></div>
                    </div>
                    <div class="post-media-upload-layout">
                        <form class="post-media-upload-form" method="post" action="post-property-media" enctype="multipart/form-data" data-upload-kind="image">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                            <input type="hidden" name="upload_kind" value="image">
                            <label class="post-media-dropzone">
                                <input id="image_files" name="image_files[]" type="file" accept="image/*" multiple>
                                <span><i class="bi bi-images"></i></span>
                                <strong>Upload Images</strong>
                                <small>JPG, PNG, GIF, WEBP up to 10 MB. Automatic 4:3 crop and compression.</small>
                            </label>
                            <div class="post-media-file-queue" data-media-file-queue aria-live="polite"></div>
                            <button class="post-media-queue-start" type="submit" data-media-queue-start hidden>Upload selected images</button>
                        </form>
                        <form class="post-media-upload-form" method="post" action="post-property-media" enctype="multipart/form-data" data-upload-kind="video">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                            <input type="hidden" name="upload_kind" value="video">
                            <label class="post-media-dropzone">
                                <input id="video_files" name="video_files[]" type="file" accept="video/*" multiple>
                                <span><i class="bi bi-camera-reels"></i></span>
                                <strong>Upload Videos</strong>
                                <small>20 MB each and 1 minute maximum.</small>
                            </label>
                            <div class="post-media-file-queue" data-media-file-queue aria-live="polite"></div>
                            <button class="post-media-queue-start" type="submit" data-media-queue-start hidden>Upload selected videos</button>
                        </form>
                    </div>
                    <form class="post-media-upload-form post-youtube-form" method="post" action="post-property-media" data-upload-kind="youtube">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="upload_kind" value="youtube">
                        <div class="post-field">
                            <label for="youtube_url">YouTube Video Link</label>
                            <input id="youtube_url" name="youtube_url" type="url" placeholder="https://www.youtube.com/watch?v=...">
                        </div>
                        <button type="submit">Add YouTube Video</button>
                    </form>
                    <div class="post-public-media-grid" data-property-media-grid>
                        <?= $mediaGridHtml ?>
                    </div>
                    <form class="post-step-form post-media-step-form" data-step-form="media">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="action" value="save_step">
                        <input type="hidden" name="step" value="media">
                        <div class="post-actions"><button type="button" data-prev-step="amenities">Back</button><button type="submit">Save Draft</button><button type="button" data-next-step="review">Review</button></div>
                    </form>
                </section>

                <section class="post-step-panel" data-step-panel="review">
                    <form class="post-step-form" data-step-form="review">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="step" value="review">
                        <div class="post-panel-title"><span>Final Step</span><h2>Review & Submit</h2></div>
                        <div class="post-field">
                            <label for="description">Property Description</label>
                            <textarea id="description" name="description" rows="7" placeholder="Describe the property, locality, fittings, access, and ideal buyer or tenant."><?= e((string) ($basic['description'] ?? '')) ?></textarea>
                        </div>
                        <details class="post-description-templates" open>
                            <summary>
                                <span>Smart Description Templates</span>
                                <button type="button" data-description-refresh>Refresh</button>
                            </summary>
                            <div class="post-description-list" data-description-template-list>
                                <div class="post-description-empty">Templates will appear from your saved property details.</div>
                            </div>
                        </details>
                        <div class="post-review-box">
                            <strong><span data-review-progress><?= e((string) number_format((float) $progress['overall_percent'], 0)) ?></span>% complete</strong>
                            <p>Once submitted, the listing goes to admin review before publishing.</p>
                        </div>
                        <div class="post-actions"><button type="button" data-prev-step="media">Back</button><button type="submit" class="post-submit-btn">Submit for Review</button></div>
                    </form>
                </section>
            </section>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.postPropertyData = <?= json_encode([
            'countries' => array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'name' => (string) $row['name']], $countries),
            'public_country_id' => $publicCountryId,
            'states' => array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'country_id' => (int) ($row['country_id'] ?? 0), 'name' => (string) $row['name']], $states),
            'cities' => array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'state_id' => (int) ($row['state_id'] ?? 0), 'name' => (string) $row['name']], $cities),
            'localities' => array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'city_id' => (int) ($row['city_id'] ?? 0), 'name' => (string) $row['name']], $localities),
            'category_labels' => $categoryLabels,
            'property_type_flow' => $propertyTypeFlow,
            'area_units_by_category' => [
                'residential' => array_keys(areaUnitOptionsForCategory('residential')),
                'commercial' => array_keys(areaUnitOptionsForCategory('commercial')),
                'land' => array_keys(areaUnitOptionsForCategory('land')),
            ],
            'fully_furnished_defaults' => $fullyFurnishedDefaults,
            'pg_furnishing_defaults' => $pgFurnishingDefaults,
            'description_templates_url' => 'post-property-description-templates',
            'media_url' => 'post-property-media',
            'google_maps' => [
                'enabled' => $googleMapsEnabled,
                'api_key' => GOOGLE_MAPS_API_KEY,
            ],
            'selected' => [
                'country_id' => $publicCountryId,
                'state_id' => (int) ($location['state_id'] ?? 0),
                'city_id' => (int) ($location['city_id'] ?? 0),
                'locality_id' => (int) ($location['locality_id'] ?? 0),
            ],
            'location' => [
                'latitude' => (string) ($location['latitude'] ?? ''),
                'longitude' => (string) ($location['longitude'] ?? ''),
                'map_address' => (string) ($location['map_address'] ?? ''),
            ],
        ], JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= e(APP_URL) ?>/website/assets/js/post-property.js?v=<?= e((string) filemtime(__DIR__ . '/assets/js/post-property.js')) ?>"></script>
</body>

</html>
