<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

$draftId = (int) ($_GET['draft_id'] ?? 0);
$bundle = $draftId > 0 ? getPropertyDraftBundle($draftId) : null;

if (!$bundle) {
    setFlash('danger', 'Property draft not found.');
    redirect(ADMIN_URL . '/properties/index.php');
}

$pageTitle = 'Property Wizard';
$pageScripts = [APP_URL . '/assets/js/property-wizard.js'];
$users = propertyWizardUsers();
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
$states = statesAll();
$cities = citiesAll();
$localities = localitiesAll();
$amenities = amenitiesAll();
$areaUnits = areaUnitOptions();
$propertyAgeOptions = propertyAgeOptions();
$facingOptions = facingOptions();
$ownershipTypeOptions = ownershipTypeOptions();
$maintenancePeriodOptions = maintenancePeriodOptions();
$photoTypeOptions = propertyPhotoTypeOptions();
$stepMeta = $bundle['progress']['step_meta'];
$selectedAreaUnit = propertyAreaUnit($bundle['profile']);
$selectedPropertyType = findPropertyType((int) ($bundle['basic']['property_type_id'] ?? 0));
$selectedCategory = (string) ($selectedPropertyType['category'] ?? 'residential');
$categoryLabels = propertyTypeCategories();
$isSellListing = isSellListingBasic($bundle['basic']);
$selectedListingName = 'Not selected';
foreach ($listingTypes as $type) {
    if ((int) ($bundle['basic']['listing_type_id'] ?? 0) === (int) $type['id']) {
        $selectedListingName = (string) $type['name'];
        break;
    }
}
$selectedPropertyTypeName = (string) ($selectedPropertyType['name'] ?? 'Not selected');
$selectedCategoryLabel = (string) ($categoryLabels[$selectedCategory] ?? 'Residential');

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="wizard-shell" id="property-wizard" data-draft-id="<?= e((string) $draftId) ?>">
    <aside class="wizard-sidebar panel-card">
        <div class="wizard-overview">
            <p class="eyebrow mb-1">Listing Completion</p>
            <h2><span data-property-overall><?= e((string) number_format((float) $bundle['progress']['overall_percent'], 0)) ?></span>%</h2>
            <p class="panel-copy mb-0">Each required field updates the listing percentage instantly after save.</p>
        </div>

        <nav class="wizard-steps">
            <?php foreach ($stepMeta as $stepKey => $step): ?>
                <button class="wizard-step-link" type="button" data-step-target="<?= e((string) $stepKey) ?>">
                    <span><?= e((string) $step['title']) ?></span>
                    <strong data-step-percent="<?= e((string) $stepKey) ?>"><?= e((string) $step['percent']) ?>%</strong>
                </button>
            <?php endforeach; ?>
        </nav>

        <div class="wizard-missing">
            <h4>Missing Parts</h4>
            <ul data-property-missing-list>
                <?php foreach ($bundle['progress']['missing'] as $missingItem): ?>
                    <li><?= e((string) $missingItem) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <div class="wizard-content">
        <section class="panel-card wizard-head-card">
            <div class="panel-head">
                <div>
                    <p class="eyebrow mb-1">Draft #<?= e((string) $draftId) ?></p>
                    <h3><?= e((string) ($bundle['basic']['title'] ?? 'Untitled Property Draft')) ?></h3>
                    <p class="panel-copy mb-0">This is the admin-side staged listing flow. Save step-by-step, upload media, then submit the draft.</p>
                </div>
                <div class="page-tools">
                    <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/properties/index.php">Back to Properties</a>
                </div>
            </div>
        </section>

        <section class="panel-card wizard-panel" data-step-panel="basic">
            <div class="panel-head">
                <div>
                    <p class="eyebrow mb-1">Step 1</p>
                    <h3>Basic Details</h3>
                </div>
                <span class="wizard-step-badge" data-step-badge="basic"><?= e((string) $stepMeta['basic']['percent']) ?>%</span>
            </div>

            <form class="admin-form property-step-form" method="post" action="<?= ADMIN_URL ?>/properties/save-step.php" data-custom-handler="property-step">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                <input type="hidden" name="step" value="basic">
                <input type="hidden" id="listing_type_id" name="listing_type_id" value="<?= e((string) ($bundle['basic']['listing_type_id'] ?? '')) ?>">
                <input type="hidden" id="property_type_id" name="property_type_id" value="<?= e((string) ($bundle['basic']['property_type_id'] ?? '')) ?>">
                <input type="hidden" id="property_category" value="<?= e($selectedCategory) ?>">

                <div class="form-grid">
                    <div class="form-field form-field-span-2">
                        <div class="selection-block">
                            <p class="selection-question required-label">I'm looking to</p>
                            <div class="selection-chip-row">
                                <?php foreach ($listingTypes as $type): ?>
                                    <?php $isActive = (int) ($bundle['basic']['listing_type_id'] ?? 0) === (int) $type['id']; ?>
                                    <button
                                        class="choice-chip choice-chip-large<?= $isActive ? ' is-active' : '' ?>"
                                        type="button"
                                        data-listing-choice
                                        data-listing-id="<?= e((string) $type['id']) ?>"
                                        aria-pressed="<?= $isActive ? 'true' : 'false' ?>"
                                    >
                                        <?= e((string) $type['name']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="selection-current">
                                <span class="selection-current-label">Selected</span>
                                <strong data-selected-listing><?= e($selectedListingName) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-field form-field-span-2">
                        <div class="selection-block">
                            <p class="selection-question">What kind of property do you have?</p>
                            <div class="selection-radio-row">
                                <?php foreach ($categoryLabels as $value => $label): ?>
                                    <?php $isActive = $selectedCategory === $value; ?>
                                    <button
                                        class="choice-radio<?= $isActive ? ' is-active' : '' ?>"
                                        type="button"
                                        data-category-choice
                                        data-category="<?= e($value) ?>"
                                        aria-pressed="<?= $isActive ? 'true' : 'false' ?>"
                                    >
                                        <span class="choice-radio-dot"></span>
                                        <span><?= e($label) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="selection-current">
                                <span class="selection-current-label">Selected</span>
                                <strong data-selected-category><?= e($selectedCategoryLabel) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-field form-field-span-2">
                        <div class="selection-block selection-type-block">
                            <div class="selection-block-head">
                                <p class="selection-question required-label mb-0" data-property-type-heading>Choose a property type</p>
                                <span class="selection-caption" data-property-type-caption>Pick the closest match for this listing.</span>
                            </div>
                            <div class="selection-chip-row selection-chip-grid" data-property-type-grid>
                                <?php foreach ($propertyTypes as $type): ?>
                                    <?php $isActive = (int) ($bundle['basic']['property_type_id'] ?? 0) === (int) $type['id']; ?>
                                    <button
                                        class="choice-chip<?= $isActive ? ' is-active' : '' ?>"
                                        type="button"
                                        data-property-type-choice
                                        data-property-type-id="<?= e((string) $type['id']) ?>"
                                        data-property-type-category="<?= e((string) $type['category']) ?>"
                                        data-property-type-custom="<?= propertyTypeUsesCustomName($type) ? '1' : '0' ?>"
                                        aria-pressed="<?= $isActive ? 'true' : 'false' ?>"
                                    >
                                        <?= e((string) $type['name']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="selection-current">
                                <span class="selection-current-label">Selected</span>
                                <strong data-selected-property-type><?= e($selectedPropertyTypeName) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-field form-field-span-2" data-admin-custom-property-type<?= propertyTypeUsesCustomName($selectedPropertyType) ? '' : ' hidden' ?>>
                        <label class="required-label" for="custom_property_type">Other Property Type</label>
                        <input class="form-control" id="custom_property_type" name="custom_property_type" type="text" maxlength="100" value="<?= e((string) ($bundle['basic']['custom_property_type'] ?? '')) ?>" placeholder="Enter the closest property type"<?= propertyTypeUsesCustomName($selectedPropertyType) ? ' required' : ' disabled' ?>>
                        <span class="field-hint">Required when an Other property type is selected.</span>
                    </div>

                    <div class="form-field">
                        <label class="required-label" for="user_id">Assign User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">Select user</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= e((string) $user['id']) ?>" <?= (int) ($bundle['draft']['user_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $user['name']) ?><?= $user['role'] ? ' (' . e((string) $user['role']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="required-label" for="posted_by">Posted By</label>
                        <select class="form-select" id="posted_by" name="posted_by" required>
                            <option value="">Select source</option>
                            <?php foreach (['owner' => 'Owner', 'agent' => 'Agent', 'builder' => 'Builder'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= (($bundle['basic']['posted_by'] ?? '') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field form-field-span-2">
                        <label class="required-label" for="title">Title</label>
                        <input class="form-control" id="title" name="title" type="text" maxlength="255" value="<?= e((string) ($bundle['basic']['title'] ?? '')) ?>" required>
                    </div>
                    <div class="form-field">
                        <label class="" for="purpose_note">Listing Note</label>
                        <input class="form-control" id="purpose_note" name="purpose_note" type="text" maxlength="150" value="<?= e((string) ($bundle['basic']['purpose_note'] ?? '')) ?>" placeholder="Near metro, corner plot, park facing">
                        <span class="field-hint">Short USP or context for the listing.</span>
                    </div>
                    <div class="form-field">
                        <label class="<?= listingRequiresAvailabilityBasic($bundle['basic']) ? 'required-label ' : '' ?>" data-basic-required-label="available_from" for="available_from">Available From</label>
                        <input class="form-control" id="available_from" name="available_from" type="date" value="<?= e((string) ($bundle['basic']['available_from'] ?? '')) ?>">
                        <span class="field-hint">Useful for rent, lease, and PG listings.</span>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-dark" type="submit">Save Basic Details</button>
                    <button class="btn btn-outline-secondary wizard-next" type="button" data-next-step="location">Next Step</button>
                </div>
            </form>
        </section>

        <section class="panel-card wizard-panel" data-step-panel="location">
            <div class="panel-head">
                <div><p class="eyebrow mb-1">Step 2</p><h3>Location</h3></div>
                <span class="wizard-step-badge" data-step-badge="location"><?= e((string) $stepMeta['location']['percent']) ?>%</span>
            </div>
            <form class="admin-form property-step-form" method="post" action="<?= ADMIN_URL ?>/properties/save-step.php" data-custom-handler="property-step">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                <input type="hidden" name="step" value="location">

                <div class="form-grid">
                    <div class="form-field">
                        <label class="required-label" for="country_id">Country</label>
                        <select class="form-select js-country" id="country_id" name="country_id" required>
                            <option value="">Select country</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?= e((string) $country['id']) ?>" <?= (int) ($bundle['location']['country_id'] ?? 0) === (int) $country['id'] ? 'selected' : '' ?>><?= e((string) $country['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="required-label" for="state_id">State</label>
                        <select class="form-select js-state" id="state_id" name="state_id" required></select>
                    </div>
                    <div class="form-field">
                        <label class="required-label" for="city_id">City</label>
                        <select class="form-select js-city" id="city_id" name="city_id" required></select>
                    </div>
                    <div class="form-field">
                        <label class="required-label" for="locality_id">Locality</label>
                        <select class="form-select js-locality" id="locality_id" name="locality_id" required></select>
                    </div>
                    <div class="form-field form-field-span-2">
                        <label for="address_line">Address</label>
                        <input class="form-control" id="address_line" name="address_line" type="text" value="<?= e((string) ($bundle['location']['address_line'] ?? '')) ?>">
                    </div>
                    <div class="form-field form-field-span-2">
                        <div class="map-picker-card">
                            <div class="map-picker-head">
                                <div>
                                    <label class="map-picker-title" for="map_search">Pick Location on Google Map</label>
                                    <p class="map-picker-copy">Search an address, click on the map, or drag the pin. Latitude, longitude, and picked address will update automatically.</p>
                                </div>
                                <?php if (GOOGLE_MAPS_API_KEY === ''): ?>
                                    <span class="map-picker-badge map-picker-badge-warning">API key needed</span>
                                <?php else: ?>
                                    <span class="map-picker-badge">Google Maps ready</span>
                                <?php endif; ?>
                            </div>

                            <?php if (GOOGLE_MAPS_API_KEY === ''): ?>
                                <div class="map-picker-empty">
                                    <strong>Google Map is not enabled yet.</strong>
                                    <p class="mb-0">Set `GOOGLE_MAPS_API_KEY` in the server environment or add it to `config/private/maps.php` to enable the map picker.</p>
                                </div>
                            <?php else: ?>
                                <div class="map-picker-toolbar">
                                    <input class="form-control" id="map_search" type="text" placeholder="Search address or landmark on map">
                                    <button class="btn btn-outline-primary" id="map_search_button" type="button">Find on Map</button>
                                    <button class="btn btn-outline-secondary" id="use_map_address" type="button">Use Picked Address</button>
                                </div>
                                <div class="map-picker-canvas" id="location_map" aria-label="Google map location picker"></div>
                                <div class="map-picker-meta">
                                    <div class="selection-current">
                                        <span class="selection-current-label">Picked Address</span>
                                        <strong data-map-address-preview><?= e((string) (($bundle['location']['map_address'] ?? '') !== '' ? $bundle['location']['map_address'] : 'Not selected')) ?></strong>
                                    </div>
                                    <label class="map-picker-check">
                                        <input type="checkbox" id="is_map_exact" name="is_map_exact" value="1" <?= (int) ($bundle['location']['is_map_exact'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        <span>Pin marks the exact property location</span>
                                    </label>
                                </div>
                            <?php endif; ?>

                            <input type="hidden" id="map_address" name="map_address" value="<?= e((string) ($bundle['location']['map_address'] ?? '')) ?>">
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="" for="landmark">Landmark</label>
                        <input class="form-control" id="landmark" name="landmark" type="text" value="<?= e((string) ($bundle['location']['landmark'] ?? '')) ?>">
                    </div>
                    <div class="form-field">
                        <label for="pincode">Pincode</label>
                        <input class="form-control" id="pincode" name="pincode" type="text" maxlength="10" value="<?= e((string) ($bundle['location']['pincode'] ?? '')) ?>">
                    </div>
                    <div class="form-field">
                        <label class="" for="latitude">Latitude</label>
                        <input class="form-control" id="latitude" name="latitude" type="text" value="<?= e((string) ($bundle['location']['latitude'] ?? '')) ?>">
                    </div>
                    <div class="form-field">
                        <label class="" for="longitude">Longitude</label>
                        <input class="form-control" id="longitude" name="longitude" type="text" value="<?= e((string) ($bundle['location']['longitude'] ?? '')) ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-dark" type="submit">Save Location</button>
                    <button class="btn btn-outline-secondary wizard-next" type="button" data-next-step="profile">Next Step</button>
                </div>
            </form>
        </section>

        <section class="panel-card wizard-panel" data-step-panel="profile">
            <div class="panel-head">
                <div><p class="eyebrow mb-1">Step 3</p><h3>Property Profile</h3></div>
                <span class="wizard-step-badge" data-step-badge="profile"><?= e((string) $stepMeta['profile']['percent']) ?>%</span>
            </div>
            <form class="admin-form property-step-form" method="post" action="<?= ADMIN_URL ?>/properties/save-step.php" data-custom-handler="property-step">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                <input type="hidden" name="step" value="profile">

                <div class="form-grid">
                    <div class="form-field">
                        <label class="required-label" for="area_unit">Area Unit</label>
                        <select class="form-select js-area-unit" id="area_unit" name="area_unit" required>
                            <?php foreach ($areaUnits as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $selectedAreaUnit === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field"><label class="" data-area-label="builtup">Built-up Area (<?= e($selectedAreaUnit) ?>)</label><input class="form-control" name="builtup_area" type="number" min="0" step="0.01" value="<?= e((string) ($bundle['profile']['builtup_area'] ?? '')) ?>"><span class="field-hint required-note">* Enter at least one area value</span></div>
                    <div class="form-field"><label class="" data-area-label="super_builtup">Super Built-up Area (<?= e($selectedAreaUnit) ?>)</label><input class="form-control" name="super_builtup_area" type="number" min="0" step="0.01" value="<?= e((string) ($bundle['profile']['super_builtup_area'] ?? '')) ?>"></div>
                    <div class="form-field"><label class="" data-area-label="carpet">Carpet Area (<?= e($selectedAreaUnit) ?>)</label><input class="form-control" name="carpet_area" type="number" min="0" step="0.01" value="<?= e((string) ($bundle['profile']['carpet_area'] ?? '')) ?>"></div>
                    <div class="form-field"><label class="" data-area-label="plot">Plot Area (<?= e($selectedAreaUnit) ?>)</label><input class="form-control" name="plot_area" type="number" min="0" step="0.01" value="<?= e((string) ($bundle['profile']['plot_area'] ?? '')) ?>"></div>
                    <div class="form-field"><label class="<?= $selectedCategory !== 'land' ? 'required-label ' : '' ?>" data-land-optional-label="bedrooms">Bedrooms</label><input class="form-control" name="bedrooms" type="number" min="0" step="1" value="<?= e((string) ($bundle['profile']['bedrooms'] ?? '')) ?>"></div>
                    <div class="form-field"><label class="<?= $selectedCategory !== 'land' ? 'required-label ' : '' ?>" data-land-optional-label="bathrooms">Bathrooms</label><input class="form-control" name="bathrooms" type="number" min="0" step="1" value="<?= e((string) ($bundle['profile']['bathrooms'] ?? '')) ?>"></div>
                    <div class="form-field"><label>Balconies</label><input class="form-control" name="balconies" type="number" min="0" step="1" value="<?= e((string) ($bundle['profile']['balconies'] ?? '')) ?>"></div>
                    <div class="form-field"><label class="">Parking Count</label><input class="form-control" name="parking_count" type="number" min="0" value="<?= e((string) ($bundle['profile']['parking_count'] ?? '')) ?>"></div>
                    <div class="form-field">
                        <label class="">Floor No</label>
                        <select class="form-select" name="floor_no">
                            <option value="">Select floor</option>
                            <option value="0" <?= selectedAttr($bundle['profile']['floor_no'] ?? '', '0') ?>>Ground Floor</option>
                            <?php for ($floorNumber = 1; $floorNumber <= 100; $floorNumber++): ?>
                                <option value="<?= $floorNumber ?>" <?= selectedAttr($bundle['profile']['floor_no'] ?? '', (string) $floorNumber) ?>>Floor <?= $floorNumber ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-field"><label class="">Total Floors</label><input class="form-control" name="total_floor" type="number" min="0" step="1" value="<?= e((string) ($bundle['profile']['total_floor'] ?? '')) ?>"></div>
                    <div class="form-field">
                        <label class="<?= $selectedCategory !== 'land' ? 'required-label ' : '' ?>" data-land-optional-label="furnishing">Furnishing</label>
                        <select class="form-select" name="furnishing">
                            <option value="">Select furnishing</option>
                            <?php foreach (['unfurnished' => 'Unfurnished', 'semi' => 'Semi Furnished', 'fully' => 'Fully Furnished'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= (($bundle['profile']['furnishing'] ?? '') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="<?= $selectedCategory !== 'land' ? 'required-label ' : '' ?>" data-land-optional-label="property_age" for="property_age">Property Age</label>
                        <select class="form-select" id="property_age" name="property_age">
                            <option value="">Select property age</option>
                            <?php foreach ($propertyAgeOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= (($bundle['profile']['property_age'] ?? '') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="required-label" for="facing">Facing</label>
                        <select class="form-select" id="facing" name="facing">
                            <option value="">Select facing</option>
                            <?php foreach ($facingOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= (($bundle['profile']['facing'] ?? '') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="ownership_type">Ownership Type <span class="field-hint">(Optional)</span></label>
                        <select class="form-select" id="ownership_type" name="ownership_type">
                            <option value="">Select ownership type (optional)</option>
                            <?php foreach ($ownershipTypeOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= (($bundle['profile']['ownership_type'] ?? '') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field form-field-span-2">
                        <label class="">Extra Rooms / Spaces</label>
                        <div class="feature-check-grid">
                            <label class="feature-check">
                                <input type="checkbox" name="servant_room" value="1" <?= (int) ($bundle['profile']['servant_room'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <span>Servant Room</span>
                            </label>
                            <label class="feature-check">
                                <input type="checkbox" name="pooja_room" value="1" <?= (int) ($bundle['profile']['pooja_room'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <span>Pooja Room</span>
                            </label>
                            <label class="feature-check">
                                <input type="checkbox" name="study_room" value="1" <?= (int) ($bundle['profile']['study_room'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <span>Study Room</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-dark" type="submit">Save Profile</button>
                    <button class="btn btn-outline-secondary wizard-next" type="button" data-next-step="pricing">Next Step</button>
                </div>
            </form>
        </section>

        <section class="panel-card wizard-panel" data-step-panel="pricing">
            <div class="panel-head">
                <div><p class="eyebrow mb-1">Step 4</p><h3>Pricing</h3></div>
                <span class="wizard-step-badge" data-step-badge="pricing"><?= e((string) $stepMeta['pricing']['percent']) ?>%</span>
            </div>
            <form class="admin-form property-step-form" method="post" action="<?= ADMIN_URL ?>/properties/save-step.php" data-custom-handler="property-step">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                <input type="hidden" name="step" value="pricing">

                <div class="form-grid">
                    <div class="form-field" data-pricing-mode-panel="sell"<?= $isSellListing ? '' : ' hidden' ?>>
                        <label class="<?= $isSellListing ? 'required-label ' : '' ?>" data-pricing-required-label="expected_price">Expected Price</label>
                        <input class="form-control js-price-input" data-price-words-target="expected_price" name="expected_price" type="number" min="0" step="0.01" value="<?= e((string) ($bundle['pricing']['expected_price'] ?? '')) ?>">
                        <span class="field-hint price-words" data-price-words="expected_price">Enter amount to see formatted price.</span>
                    </div>
                    <div class="form-field" data-pricing-mode-panel="rent"<?= $isSellListing ? ' hidden' : '' ?>>
                        <label class="<?= !$isSellListing && (int) ($bundle['basic']['listing_type_id'] ?? 0) > 0 ? 'required-label ' : '' ?>" data-pricing-required-label="rent">Monthly Rent</label>
                        <input class="form-control js-price-input" data-price-words-target="rent" name="rent" type="number" min="0" step="0.01" value="<?= e((string) ($bundle['pricing']['rent'] ?? '')) ?>">
                        <span class="field-hint price-words" data-price-words="rent">Enter amount to see formatted rent.</span>
                    </div>
                    <div data-pricing-mode-panel="rent"<?= $isSellListing ? ' hidden' : '' ?>>
                        <label class="<?= !$isSellListing && (int) ($bundle['basic']['listing_type_id'] ?? 0) > 0 ? 'required-label ' : '' ?>" data-pricing-required-label="deposit">Security Deposit</label>
                        <select class="form-select" name="deposit">
                            <option value="">Select months</option>
                            <?php for ($month = 1; $month <= 6; $month++): ?>
                                <option value="<?= $month ?>" <?= ((string) ($bundle['pricing']['deposit'] ?? '') === (string) $month) ? 'selected' : '' ?>><?= e((string) $month) ?> Month<?= $month > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-field" data-pricing-mode-panel="sell"<?= $isSellListing ? '' : ' hidden' ?>>
                        <label>Booking Amount</label>
                        <input class="form-control js-price-input" data-price-words-target="booking_amount" name="booking_amount" type="number" min="0" step="0.01" value="<?= e((string) ($bundle['pricing']['booking_amount'] ?? '')) ?>">
                        <span class="field-hint price-words" data-price-words="booking_amount">Optional upfront booking amount.</span>
                    </div>
                    <div class="form-field">
                        <label for="maintenance">Maintenance Charges</label>
                        <input class="form-control js-price-input" data-price-words-target="maintenance" name="maintenance" id="maintenance" type="number" min="0" step="0.01" value="<?= e((string) ($bundle['pricing']['maintenance'] ?? '')) ?>">
                        <span class="field-hint price-words" data-price-words="maintenance">Optional maintenance charge.</span>
                    </div>
                    <div class="form-field">
                        <label for="maintenance_period">Maintenance Period</label>
                        <select class="form-select" id="maintenance_period" name="maintenance_period">
                            <option value="">Select period</option>
                            <?php foreach ($maintenancePeriodOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= (($bundle['pricing']['maintenance_period'] ?? '') === $value) ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-hint">Choose only when maintenance amount is applicable.</span>
                    </div>
                    <div class="form-field"><label>Electricity Charges</label><input class="form-control" name="electricity_charges" type="text" maxlength="100" value="<?= e((string) ($bundle['pricing']['electricity_charges'] ?? '')) ?>" placeholder="Excluded / As per meter / Included"></div>
                    <div class="form-field"><label>Brokerage</label><input class="form-control" name="brokerage" type="text" maxlength="100" value="<?= e((string) ($bundle['pricing']['brokerage'] ?? '')) ?>" placeholder="1 month / 2% / No brokerage"></div>
                    <div class="form-check align-self-end">
                        <input class="form-check-input" type="checkbox" id="negotiable" name="negotiable" value="1" <?= (int) ($bundle['pricing']['negotiable'] ?? 0) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="negotiable">Negotiable</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn-dark" type="submit">Save Pricing</button>
                    <button class="btn btn-outline-secondary wizard-next" type="button" data-next-step="amenities">Next Step</button>
                </div>
            </form>
        </section>

        <section class="panel-card wizard-panel" data-step-panel="amenities">
            <div class="panel-head">
                <div><p class="eyebrow mb-1">Step 5</p><h3>Amenities</h3></div>
                <span class="wizard-step-badge" data-step-badge="amenities"><?= e((string) $stepMeta['amenities']['percent']) ?>%</span>
            </div>
            <form class="admin-form property-step-form" method="post" action="<?= ADMIN_URL ?>/properties/save-step.php" data-custom-handler="property-step">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                <input type="hidden" name="step" value="amenities">

                <div class="amenity-grid">
                    <?php foreach ($amenities as $amenity): ?>
                        <label class="amenity-option">
                            <input type="checkbox" name="amenity_ids[]" value="<?= e((string) $amenity['id']) ?>" <?= in_array((int) $amenity['id'], $bundle['amenity_ids'], true) ? 'checked' : '' ?>>
                            <span><?= e((string) $amenity['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button class="btn btn-dark" type="submit">Save Amenities</button>
                    <button class="btn btn-outline-secondary wizard-next" type="button" data-next-step="media">Next Step</button>
                </div>
            </form>
        </section>

        <section class="panel-card wizard-panel" data-step-panel="media">
            <div class="panel-head">
                <div>
                    <p class="eyebrow mb-1">Step 6</p>
                    <h3>Media</h3>
                      <p class="panel-copy mb-0">Minimum 1 image, maximum 20 images. Images auto-upload on select or drag-drop. Videos allow up to 20 MB and 30 seconds.</p>
                </div>
                <span class="wizard-step-badge" data-step-badge="media"><?= e((string) $stepMeta['media']['percent']) ?>%</span>
            </div>

              <div class="wizard-media-stats">
                  <div class="media-stat"><strong data-property-image-count><?= e((string) $bundle['progress']['image_count']) ?></strong><span>Images Uploaded</span></div>
                  <div class="media-stat"><strong>20</strong><span>Maximum Images</span></div>
                  <div class="media-stat"><strong>20 MB</strong><span>Max Video Size</span></div>
                  <div class="media-stat"><strong>30 sec</strong><span>Max Video Length</span></div>
              </div>

              <div class="media-upload-layout">
                  <form class="admin-form property-media-form media-upload-card" method="post" action="<?= ADMIN_URL ?>/properties/media-upload.php" enctype="multipart/form-data" data-custom-handler="property-media-upload" data-upload-kind="image">
                      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                      <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                      <input type="hidden" name="upload_kind" value="image">
                      <div class="media-dropzone" data-dropzone="image">
                          <input class="media-input" id="image_files" name="image_files[]" type="file" accept="image/*" multiple>
                          <label class="media-dropzone-body" for="image_files">
                              <span class="media-dropzone-icon"><i class="bi bi-images"></i></span>
                              <strong>Upload Property Images</strong>
                              <span>Drag images here or click to browse</span>
                              <small>JPG, PNG, GIF, WEBP up to 10 MB. Automatically cropped to 4:3, resized to 1600 × 1200, and compressed as WebP.</small>
                          </label>
                      </div>
                  </form>

                  <form class="admin-form property-media-form media-upload-card" method="post" action="<?= ADMIN_URL ?>/properties/media-upload.php" enctype="multipart/form-data" data-custom-handler="property-media-upload" data-upload-kind="video">
                      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                      <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                      <input type="hidden" name="upload_kind" value="video">
                      <div class="media-dropzone" data-dropzone="video">
                          <input class="media-input" id="video_files" name="video_files[]" type="file" accept="video/*" multiple>
                          <label class="media-dropzone-body" for="video_files">
                              <span class="media-dropzone-icon"><i class="bi bi-camera-reels"></i></span>
                              <strong>Upload Short Videos</strong>
                              <span>Drag videos here or click to browse</span>
                              <small>Max 20 MB each and max 30 seconds. Reels or shorts style only.</small>
                          </label>
                      </div>
                  </form>
              </div>

              <form class="admin-form property-media-form media-youtube-form" method="post" action="<?= ADMIN_URL ?>/properties/media-upload.php" data-custom-handler="property-media-upload" data-upload-kind="youtube">
                  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                  <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                  <input type="hidden" name="upload_kind" value="youtube">
                  <div class="form-grid">
                      <div class="form-field form-field-span-2">
                          <label class="" for="youtube_url">YouTube Link</label>
                          <input class="form-control" id="youtube_url" name="youtube_url" type="url" placeholder="https://www.youtube.com/watch?v=...">
                      </div>
                  </div>

                  <div class="form-actions">
                      <button class="btn btn-dark" type="submit">Add YouTube Video</button>
                  </div>
              </form>

            <div class="media-grid" data-property-media-grid>
                <?php foreach ($bundle['media'] as $media): ?>
                    <?= propertyMediaCardHtml($media) ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel-card wizard-panel">
            <div class="panel-head">
                <div><p class="eyebrow mb-1">Final Step</p><h3>Submit Listing</h3></div>
            </div>
            <form class="admin-form property-submit-form" method="post" action="<?= ADMIN_URL ?>/properties/submit.php" data-custom-handler="property-submit">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                <div class="form-grid">
                    <div class="form-field form-field-span-2">
                        <label class="required-label" for="description">Property Description</label>
                        <textarea class="form-control" id="description" name="description" rows="6" required><?= e((string) ($bundle['basic']['description'] ?? '')) ?></textarea>
                        <span class="field-hint">Add the final polished property description before submitting the listing for review.</span>
                        <details class="description-generator">
                            <summary class="description-generator-summary">
                                <div>
                                    <strong>Smart Description Templates</strong>
                                    <span>Click to view and choose a professional description.</span>
                                </div>
                                <span class="description-generator-chevron" aria-hidden="true"></span>
                            </summary>
                            <div class="description-generator-body">
                                <div class="description-generator-head">
                                    <div>
                                        <strong>Professional drafts based on your saved property details.</strong>
                                        <span>Pick a template at the end, review it, then submit the listing.</span>
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-description-refresh>Refresh Templates</button>
                                </div>
                                <div class="description-template-list" data-description-template-list>
                                    <div class="description-template-empty">Templates will appear here based on the property details you save in the wizard.</div>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-dark" type="submit">Submit for Review</button>
                </div>
            </form>
        </section>
    </div>
</section>

<script>
window.propertyWizardData = <?= json_encode([
    'countries' => array_map(static fn ($row) => ['id' => (int) $row['id'], 'name' => (string) $row['name']], $countries),
    'states' => array_map(static fn ($row) => ['id' => (int) $row['id'], 'country_id' => (int) ($row['country_id'] ?? 0), 'name' => (string) $row['name']], $states),
    'cities' => array_map(static fn ($row) => ['id' => (int) $row['id'], 'state_id' => (int) ($row['state_id'] ?? 0), 'name' => (string) $row['name']], $cities),
    'localities' => array_map(static fn ($row) => ['id' => (int) $row['id'], 'city_id' => (int) ($row['city_id'] ?? 0), 'name' => (string) $row['name']], $localities),
    'area_units' => array_values($areaUnits),
    'selected_area_unit' => $selectedAreaUnit,
    'category_labels' => $categoryLabels,
    'property_age_options' => $propertyAgeOptions,
    'facing_options' => $facingOptions,
    'photo_type_options' => $photoTypeOptions,
    'description_templates_url' => ADMIN_URL . '/properties/description-templates.php',
    'google_maps' => [
        'enabled' => GOOGLE_MAPS_API_KEY !== '',
        'api_key' => GOOGLE_MAPS_API_KEY,
    ],
    'selected' => [
        'country_id' => (int) ($bundle['location']['country_id'] ?? 0),
        'state_id' => (int) ($bundle['location']['state_id'] ?? 0),
        'city_id' => (int) ($bundle['location']['city_id'] ?? 0),
        'locality_id' => (int) ($bundle['location']['locality_id'] ?? 0),
    ],
    'location' => [
        'latitude' => (string) ($bundle['location']['latitude'] ?? ''),
        'longitude' => (string) ($bundle['location']['longitude'] ?? ''),
        'map_address' => (string) ($bundle['location']['map_address'] ?? ''),
        'address_line' => (string) ($bundle['location']['address_line'] ?? ''),
    ],
], JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
