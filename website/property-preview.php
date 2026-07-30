<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/property.php';

if (!isPublicUserLoggedIn()) {
    redirect(publicAuthLoginUrl(publicAuthCurrentUrl()));
}

$user = publicAuthUserPayload();
$draftId = (int) ($_GET['draft_id'] ?? 0);
$draft = $draftId > 0 ? findPropertyDraft($draftId) : null;

if (!$draft || (int) ($draft['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
    setFlash('danger', 'Property preview not found.');
    redirect('account?view=properties');
}

$bundle = getPropertyDraftBundle($draftId);

if ((float) ($bundle['progress']['overall_percent'] ?? 0) < 100) {
    setFlash('warning', 'Complete all required property details before opening the preview.');
    redirect('post-property?draft_id=' . $draftId);
}

$basic = $bundle['basic'];
$profile = $bundle['profile'];
$pricing = $bundle['pricing'];
$location = $bundle['location'];
$propertyType = findPropertyType((int) ($basic['property_type_id'] ?? 0));
$listingType = findListingType((int) ($basic['listing_type_id'] ?? 0));
$propertyTypeName = (string) ($propertyType['name'] ?? 'Property');
$listingTypeName = (string) ($listingType['name'] ?? 'Listing');
$isSell = isSellListingBasic($basic);
$priceValue = $isSell ? ($pricing['expected_price'] ?? null) : ($pricing['rent'] ?? null);
$priceText = propertyFormatIndianCurrency($priceValue);
$priceText = $priceText !== '' ? $priceText . ($isSell ? '' : ' / month') : 'Price on request';
$areaUnit = propertyAreaUnit($profile);
$primaryArea = $profile['builtup_area'] ?? $profile['carpet_area'] ?? $profile['super_builtup_area'] ?? $profile['plot_area'] ?? '';
$locality = !empty($location['locality_id']) ? findLocality((int) $location['locality_id']) : null;
$city = !empty($location['city_id']) ? findCity((int) $location['city_id']) : null;
$state = !empty($location['state_id']) ? findState((int) $location['state_id']) : null;
$locationText = implode(', ', array_filter([
    (string) ($locality['name'] ?? ''),
    (string) ($city['name'] ?? ''),
    (string) ($state['name'] ?? ''),
]));
$addressText = implode(', ', array_filter([
    (string) ($location['address_line'] ?? ''),
    (string) ($location['landmark'] ?? ''),
    $locationText,
    (string) ($location['pincode'] ?? ''),
]));
$images = array_values(array_filter(
    $bundle['media'],
    static fn (array $media): bool => (string) ($media['kind'] ?? '') === 'image'
));
$photoTypeOptions = propertyPhotoTypeOptions();
$amenityNames = [];
$furnishingItems = propertyFurnishingItemLabels($profile['furnishing_items'] ?? []);
$officeDetails = propertyOfficeProfileSummary($profile);
$pgDetails = propertyPgProfileSummary($profile);

foreach ($bundle['amenity_ids'] as $amenityId) {
    $amenity = findAmenity((int) $amenityId);
    if ($amenity && trim((string) ($amenity['name'] ?? '')) !== '') {
        $amenityNames[] = (string) $amenity['name'];
    }
}

$floorText = trim((string) ($profile['floor_no'] ?? '')) !== ''
    ? (string) $profile['floor_no'] . (trim((string) ($profile['total_floor'] ?? '')) !== '' ? ' of ' . $profile['total_floor'] : '')
    : 'Not applicable';
$availableFrom = trim((string) ($basic['available_from'] ?? ''));
$availabilityText = $availableFrom !== '' ? date('d M Y', strtotime($availableFrom)) : 'Ready';
$postedBy = ucfirst((string) (($basic['posted_by'] ?? '') ?: ($user['role'] ?? 'Owner')));
$initials = strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1));
$overview = [
    ['bi-aspect-ratio', 'Super Built-up Area', ($profile['super_builtup_area'] ?? '') !== '' ? $profile['super_builtup_area'] . ' ' . $areaUnit : 'Not provided'],
    ['bi-bounding-box', 'Built-up Area', ($profile['builtup_area'] ?? '') !== '' ? $profile['builtup_area'] . ' ' . $areaUnit : 'Not provided'],
    ['bi-bounding-box-circles', 'Carpet Area', ($profile['carpet_area'] ?? '') !== '' ? $profile['carpet_area'] . ' ' . $areaUnit : 'Not provided'],
    ['bi-layers', 'Floor', $floorText],
    ['bi-door-open', 'Bedrooms', ($profile['bedrooms'] ?? '') !== '' ? (string) $profile['bedrooms'] : 'Not applicable'],
    ['bi-droplet', 'Bathrooms', ($profile['bathrooms'] ?? '') !== '' ? (string) $profile['bathrooms'] : 'Not applicable'],
    ['bi-compass', 'Facing', ($profile['facing'] ?? '') !== '' ? ucwords(str_replace('-', ' ', (string) $profile['facing'])) : 'Not provided'],
    ['bi-lamp', 'Furnishing', ($profile['furnishing'] ?? '') !== '' ? propertyFurnishingLabel((string) $profile['furnishing']) : 'Not applicable'],
];
$officeSetupDetails = [];
if ($officeDetails !== []) {
    foreach ([
        'min_seats' => 'Minimum seats',
        'max_seats' => 'Maximum seats',
        'cabins' => 'Cabins',
        'meeting_rooms' => 'Meeting rooms',
        'private_washrooms' => 'Private washrooms',
        'shared_washrooms' => 'Shared washrooms',
        'staircases' => 'Staircases',
    ] as $key => $label) {
        if (isset($officeDetails[$key]) && trim((string) $officeDetails[$key]) !== '') {
            $officeSetupDetails[] = [$label, (string) $officeDetails[$key]];
        }
    }
    foreach ([
        'washrooms' => 'Washrooms',
        'conference_room' => 'Conference room',
        'reception_area' => 'Reception area',
    ] as $key => $label) {
        if (isset($officeDetails[$key])) {
            $officeSetupDetails[] = [$label, propertyOfficeStatusLabel($officeDetails[$key])];
        }
    }
    if (isset($officeDetails['pantry_type'])) {
        $officeSetupDetails[] = ['Pantry type', ucwords(str_replace('_', ' ', (string) $officeDetails['pantry_type']))];
    }
}
$officeFacilityDetails = [];
foreach (propertyOfficeFacilityOptions() as $key => $label) {
    $value = $officeDetails['facilities'][$key] ?? null;
    if ($value !== null) {
        $officeFacilityDetails[] = [$label, propertyOfficeStatusLabel($value)];
    }
}
$officeFireSafety = array_values(array_filter(array_map(
    static fn (string $key): string => propertyOfficeFireSafetyOptions()[$key] ?? '',
    array_map('strval', $officeDetails['fire_safety'] ?? [])
)));
$pgSetupDetails = [];
if ($pgDetails !== []) {
    foreach ([
        'room_type' => 'Room type',
        'total_rooms' => 'Total rooms',
        'available_rooms' => 'Rooms available',
    ] as $key => $label) {
        if (isset($pgDetails[$key]) && trim((string) $pgDetails[$key]) !== '') {
            $pgSetupDetails[] = [$label, ucwords(str_replace('_', ' ', (string) $pgDetails[$key]))];
        }
    }
    foreach ([
        'attached_bathroom' => 'Attached bathroom',
        'attached_balcony' => 'Attached balcony',
        'store_room' => 'Store room',
        'common_area_furnishing' => 'Common area furnishing',
    ] as $key => $label) {
        if ((int) ($pgDetails[$key] ?? 0) === 1) {
            $pgSetupDetails[] = [$label, 'Available'];
        }
    }
    foreach ([
        'covered_parking' => 'Covered parking',
        'open_parking' => 'Open parking',
    ] as $key => $label) {
        if (isset($pgDetails[$key]) && trim((string) $pgDetails[$key]) !== '') {
            $pgSetupDetails[] = [$label, (string) $pgDetails[$key]];
        }
    }
    $availableFor = propertyPgAvailableForLabel($pgDetails['available_for'] ?? '');
    if ($availableFor !== '') {
        $pgSetupDetails[] = ['Available for', $availableFor];
    }
    $suitableFor = propertyPgSuitableForLabels($pgDetails['suitable_for'] ?? []);
    if ($suitableFor !== []) {
        $pgSetupDetails[] = ['Suitable for', implode(', ', $suitableFor)];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Preview: <?= e((string) ($basic['title'] ?? 'Property')) ?> - GharSquare</title>
    <meta name="robots" content="noindex,nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css?v=<?= e((string) filemtime(__DIR__ . '/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="details-page property-preview-page">
    <nav class="navbar navbar-expand-lg fixed-top premium-navbar">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand" href="./" aria-label="GharSquare home">
                <span class="logo-icon"><i class="bi bi-house-fill"></i></span>
                Ghar<span>Square</span>
            </a>
            <div class="ms-auto d-flex gap-2">
                <a class="preview-nav-action" href="post-property?draft_id=<?= e((string) $draftId) ?>"><i class="bi bi-pencil"></i> Edit Listing</a>
                <a class="preview-nav-action secondary" href="account?view=properties">My Properties</a>
            </div>
        </div>
    </nav>

    <main class="details-main">
        <section class="property-preview-banner">
            <i class="bi bi-eye"></i>
            <div><strong>Owner Preview</strong><span>This is how your completed property profile will appear to visitors.</span></div>
        </section>

        <section class="details-hero">
            <div class="container-fluid px-lg-5">
                <div class="details-breadcrumb">
                    <a href="account?view=properties">My Properties</a>
                    <i class="bi bi-chevron-right"></i>
                    <a href="post-property?draft_id=<?= e((string) $draftId) ?>">Edit Listing</a>
                    <i class="bi bi-chevron-right"></i>
                    <span><?= e((string) ($basic['title'] ?? 'Property')) ?></span>
                </div>

                <div class="details-top-grid">
                    <div class="details-gallery" data-preview-gallery>
                        <div class="main-photo">
                            <img data-preview-main-image src="<?= e((string) ($images[0]['file_url'] ?? '')) ?>" alt="<?= e((string) ($basic['title'] ?? 'Property image')) ?>">
                            <span class="photo-count"><?= e((string) count($images)) ?> Photos</span>
                            <?php if (count($images) > 1): ?>
                                <button type="button" class="gallery-nav gallery-prev" data-preview-gallery-prev aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" class="gallery-nav gallery-next" data-preview-gallery-next aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
                            <?php endif; ?>
                        </div>
                        <div class="thumb-row">
                            <?php foreach ($images as $index => $image): ?>
                                <?php $photoLabel = $photoTypeOptions[(string) ($image['title'] ?? '')] ?? 'Property photo'; ?>
                                <button type="button" class="thumb-btn<?= $index === 0 ? ' active' : '' ?>" data-preview-thumb data-index="<?= e((string) $index) ?>" data-src="<?= e((string) $image['file_url']) ?>" aria-label="View <?= e($photoLabel) ?>">
                                    <img src="<?= e((string) $image['file_url']) ?>" alt="<?= e($photoLabel) ?>">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="details-summary-panel">
                        <div class="summary-badges">
                            <span class="summary-badge">Preview</span>
                            <span class="summary-badge"><?= e($listingTypeName) ?></span>
                            <span class="summary-badge"><?= e($availabilityText) ?></span>
                        </div>
                        <h1><?= e((string) ($basic['title'] ?? 'Property')) ?></h1>
                        <p class="detail-location"><i class="bi bi-geo-alt"></i> <?= e($locationText ?: 'Location available in listing') ?></p>
                        <div class="detail-price"><?= e($priceText) ?></div>
                        <div class="detail-spec-grid">
                            <div class="detail-spec"><span>Property Type</span><strong><?= e($propertyTypeName) ?></strong></div>
                            <div class="detail-spec"><span>Area</span><strong><?= e((string) $primaryArea) ?> <?= e($areaUnit) ?></strong></div>
                            <div class="detail-spec"><span>Bedrooms</span><strong><?= e((string) (($profile['bedrooms'] ?? '') !== '' ? $profile['bedrooms'] : 'Not applicable')) ?></strong></div>
                            <div class="detail-spec"><span>Available</span><strong><?= e($availabilityText) ?></strong></div>
                        </div>
                        <div class="preview-summary-actions">
                            <a href="post-property?draft_id=<?= e((string) $draftId) ?>"><i class="bi bi-pencil"></i> Edit Property</a>
                            <a href="account?view=properties"><i class="bi bi-grid"></i> My Properties</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="details-content">
            <div class="container-fluid px-lg-5">
                <div class="details-layout">
                    <div class="details-left">
                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Overview</h2><span><?= e($listingTypeName) ?></span></div>
                            <div class="overview-grid">
                                <?php foreach ($overview as [$icon, $label, $value]): ?>
                                    <div class="overview-item"><i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span><strong><?= e((string) $value) ?></strong></div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Description</h2></div>
                            <p class="property-description"><?= nl2br(e((string) ($basic['description'] ?? ''))) ?></p>
                        </section>

                        <?php if ($officeSetupDetails !== [] || $officeFacilityDetails !== [] || $officeFireSafety !== []): ?>
                            <section class="detail-panel">
                                <div class="detail-section-heading"><h2>Office Setup</h2></div>
                                <?php if ($officeSetupDetails !== []): ?>
                                    <div class="pricing-detail-grid">
                                        <?php foreach ($officeSetupDetails as [$label, $value]): ?>
                                            <div><span><?= e($label) ?></span><strong><?= e($value) ?></strong></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($officeFacilityDetails !== []): ?>
                                    <div class="amenity-grid mt-3">
                                        <?php foreach ($officeFacilityDetails as [$label, $value]): ?>
                                            <div class="amenity-item"><i class="bi bi-check2-circle"></i> <?= e($label) ?>: <?= e($value) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($officeFireSafety !== []): ?>
                                    <div class="amenity-grid mt-3">
                                        <?php foreach ($officeFireSafety as $item): ?>
                                            <div class="amenity-item"><i class="bi bi-shield-check"></i> <?= e($item) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>

                        <?php if ($pgSetupDetails !== []): ?>
                            <section class="detail-panel">
                                <div class="detail-section-heading"><h2>PG Details</h2></div>
                                <div class="pricing-detail-grid">
                                    <?php foreach ($pgSetupDetails as [$label, $value]): ?>
                                        <div><span><?= e($label) ?></span><strong><?= e($value) ?></strong></div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ($furnishingItems !== []): ?>
                            <section class="detail-panel">
                                <div class="detail-section-heading"><h2>Furnishing Includes</h2></div>
                                <div class="amenity-grid">
                                    <?php foreach ($furnishingItems as $item): ?>
                                        <div class="amenity-item"><i class="bi bi-check2-circle"></i> <?= e($item) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Amenities</h2></div>
                            <div class="amenity-grid">
                                <?php foreach ($amenityNames as $amenityName): ?>
                                    <div class="amenity-item"><i class="bi bi-check2-circle"></i> <?= e($amenityName) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Location</h2><span><?= e($locationText) ?></span></div>
                            <div class="detail-location-grid">
                                <div class="detail-map"><div class="detail-map-pin"><i class="bi bi-geo-alt-fill"></i></div></div>
                                <div class="preview-location-copy">
                                    <strong><?= e($locationText) ?></strong>
                                    <p><?= e($addressText) ?></p>
                                    <?php if (trim((string) ($location['map_address'] ?? '')) !== ''): ?><small><?= e((string) $location['map_address']) ?></small><?php endif; ?>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="contact-panel preview-owner-panel">
                        <div class="agent-mini">
                            <span class="preview-owner-avatar"><?= e($initials) ?></span>
                            <div><h3><?= e((string) ($user['name'] ?? 'Property Owner')) ?></h3><p><?= e($postedBy) ?></p></div>
                        </div>
                        <div class="agent-verified"><i class="bi bi-patch-check-fill"></i><span>Verified GharSquare account</span></div>
                        <div class="preview-owner-details">
                            <span>Email</span><strong><?= e((string) ($user['email'] ?? 'Not added')) ?></strong>
                            <span>Phone</span><strong><?= e((string) (($user['phone'] ?? '') ?: 'Not added')) ?></strong>
                        </div>
                        <a class="preview-edit-button" href="post-property?draft_id=<?= e((string) $draftId) ?>"><i class="bi bi-pencil"></i> Continue Editing</a>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/js/property-preview.js?v=<?= e((string) filemtime(__DIR__ . '/assets/js/property-preview.js')) ?>"></script>
</body>
</html>
