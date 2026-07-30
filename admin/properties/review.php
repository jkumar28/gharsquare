<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

$draftId = (int) ($_GET['draft_id'] ?? 0);

try {
    $bundle = getPropertyDraftBundle($draftId);
} catch (Throwable $exception) {
    setFlash('danger', 'Property draft not found.');
    redirect(ADMIN_URL . '/properties/index.php');
}

$draft = $bundle['draft'];
$basic = $bundle['basic'];
$location = $bundle['location'];
$profile = $bundle['profile'];
$pricing = $bundle['pricing'];
$property = findPropertyByDraftId($draftId);
$listRow = findPropertyDraftListRow($draftId) ?? [
    'id' => $draftId,
    'is_submitted' => (int) ($draft['is_submitted'] ?? 0),
    'property_status' => (string) ($property['status'] ?? ''),
];
$user = findUser((int) ($draft['user_id'] ?? 0));
$propertyType = findPropertyType((int) ($basic['property_type_id'] ?? 0));
$listingType = findListingType((int) ($basic['listing_type_id'] ?? 0));
$country = findCountry((int) ($location['country_id'] ?? 0));
$state = findState((int) ($location['state_id'] ?? 0));
$city = findCity((int) ($location['city_id'] ?? 0));
$locality = findLocality((int) ($location['locality_id'] ?? 0));
$amenities = array_values(array_filter(array_map(static fn (int $amenityId): ?array => findAmenity($amenityId), $bundle['amenity_ids'])));
$statusMeta = propertyDraftStatusMeta($listRow);
$timeline = propertyModerationTimeline($bundle, $property);
$returnTo = propertyReviewUrl($draftId);
$pageTitle = 'Review Property #' . $draftId;
$extraRooms = array_values(array_filter([
    (int) ($profile['servant_room'] ?? 0) === 1 ? 'Servant Room' : null,
    (int) ($profile['pooja_room'] ?? 0) === 1 ? 'Pooja Room' : null,
    (int) ($profile['study_room'] ?? 0) === 1 ? 'Study Room' : null,
]));
$furnishingItems = propertyFurnishingItemLabels($profile['furnishing_items'] ?? []);
$officeDetails = propertyOfficeProfileSummary($profile);
$pgDetails = propertyPgProfileSummary($profile);
$officeAdminDetails = [];
if ($officeDetails !== []) {
    foreach ([
        'min_seats' => 'Minimum seats',
        'max_seats' => 'Maximum seats',
        'cabins' => 'Cabins',
        'meeting_rooms' => 'Meeting rooms',
        'washrooms' => 'Washrooms',
        'private_washrooms' => 'Private washrooms',
        'shared_washrooms' => 'Shared washrooms',
        'conference_room' => 'Conference room',
        'reception_area' => 'Reception area',
        'pantry_type' => 'Pantry type',
        'staircases' => 'Staircases',
    ] as $key => $label) {
        if (isset($officeDetails[$key]) && trim((string) $officeDetails[$key]) !== '') {
            $value = in_array($key, ['washrooms', 'conference_room', 'reception_area'], true)
                ? propertyOfficeStatusLabel($officeDetails[$key])
                : ucwords(str_replace('_', ' ', (string) $officeDetails[$key]));
            $officeAdminDetails[] = $label . ': ' . $value;
        }
    }
    foreach (propertyOfficeFacilityOptions() as $key => $label) {
        if (isset($officeDetails['facilities'][$key])) {
            $officeAdminDetails[] = $label . ': ' . propertyOfficeStatusLabel($officeDetails['facilities'][$key]);
        }
    }
    $fireSafety = array_values(array_filter(array_map(
        static fn (string $key): string => propertyOfficeFireSafetyOptions()[$key] ?? '',
        array_map('strval', $officeDetails['fire_safety'] ?? [])
    )));
    if ($fireSafety !== []) {
        $officeAdminDetails[] = 'Fire safety: ' . implode(', ', $fireSafety);
    }
}
$pgAdminDetails = [];
if ($pgDetails !== []) {
    foreach ([
        'room_type' => 'Room type',
        'total_rooms' => 'Total rooms',
        'available_rooms' => 'Rooms available',
        'covered_parking' => 'Covered parking',
        'open_parking' => 'Open parking',
    ] as $key => $label) {
        if (isset($pgDetails[$key]) && trim((string) $pgDetails[$key]) !== '') {
            $pgAdminDetails[] = $label . ': ' . ucwords(str_replace('_', ' ', (string) $pgDetails[$key]));
        }
    }
    foreach ([
        'attached_bathroom' => 'Attached bathroom',
        'attached_balcony' => 'Attached balcony',
        'store_room' => 'Store room',
        'common_area_furnishing' => 'Common area furnishing',
    ] as $key => $label) {
        if ((int) ($pgDetails[$key] ?? 0) === 1) {
            $pgAdminDetails[] = $label . ': Available';
        }
    }
    $availableFor = propertyPgAvailableForLabel($pgDetails['available_for'] ?? '');
    if ($availableFor !== '') {
        $pgAdminDetails[] = 'Available for: ' . $availableFor;
    }
    $suitableFor = propertyPgSuitableForLabels($pgDetails['suitable_for'] ?? []);
    if ($suitableFor !== []) {
        $pgAdminDetails[] = 'Suitable for: ' . implode(', ', $suitableFor);
    }
}
$categoryKey = strtolower(trim((string) ($propertyType['category'] ?? '')));
$categoryLabel = $categoryKey !== '' ? ucfirst($categoryKey) : 'Category';
$locationParts = array_filter([
    $locality['name'] ?? '',
    $city['name'] ?? '',
    $state['name'] ?? '',
]);
$locationLabel = $locationParts !== [] ? implode(', ', array_map('strval', $locationParts)) : 'Location pending';
$primaryMedia = null;
$mediaCounts = [
    'image' => 0,
    'video' => 0,
    'youtube' => 0,
];
$coverMediaCount = 0;
foreach ($bundle['media'] as $mediaItem) {
    $kind = (string) ($mediaItem['kind'] ?? '');
    if (isset($mediaCounts[$kind])) {
        $mediaCounts[$kind]++;
    }

    if ((int) ($mediaItem['is_primary'] ?? 0) === 1) {
        $coverMediaCount++;
        $primaryMedia = $mediaItem;
    }
}
if ($primaryMedia === null && $bundle['media'] !== []) {
    $primaryMedia = $bundle['media'][0];
}

$displayValue = static function (mixed $value, string $fallback = 'Not provided'): string {
    $text = trim((string) ($value ?? ''));
    return $text !== '' ? $text : $fallback;
};

$formatDateTime = static function (?string $value): string {
    $text = trim((string) $value);
    if ($text === '') {
        return 'Not available';
    }

    $timestamp = strtotime($text);
    return $timestamp === false ? $text : date('d M Y, h:i A', $timestamp);
};

$formatArea = static function (?string $value) use ($profile): string {
    $text = trim((string) $value);
    if ($text === '') {
        return 'Not provided';
    }

    return $text . ' ' . propertyAreaUnit($profile);
};

$formatCurrency = static function (mixed $value): string {
    $formatted = propertyFormatIndianCurrency($value);
    return $formatted !== '' ? $formatted : 'Not provided';
};

$mediaTitle = static function (array $media): string {
    $titleKey = trim((string) ($media['title'] ?? ''));
    $options = propertyPhotoTypeOptions();
    if ($titleKey !== '' && isset($options[$titleKey])) {
        return $options[$titleKey];
    }

    return match ((string) ($media['kind'] ?? '')) {
        'youtube' => 'YouTube Video',
        'video' => 'Uploaded Video',
        default => 'Property Media',
    };
};

$priceHeadline = isSellListingBasic($basic)
    ? $formatCurrency($pricing['expected_price'] ?? null)
    : $formatCurrency($pricing['rent'] ?? null);
$priceLabel = isSellListingBasic($basic) ? 'Expected Price' : 'Monthly Rent';
$propertySlug = $displayValue($property['slug'] ?? '');
$publishedAt = $formatDateTime((string) ($property['published_at'] ?? ''));
$isNegotiable = (int) ($pricing['negotiable'] ?? 0) === 1;
$securityDepositType = (string) ($pricing['security_deposit_type'] ?? '');
$securityDepositLabel = match ($securityDepositType) {
    'fixed' => $formatCurrency($pricing['security_deposit_amount'] ?? null),
    'multiple' => trim((string) ($pricing['security_deposit_months'] ?? '')) !== ''
        ? (string) $pricing['security_deposit_months'] . ' month(s) of rent'
        : 'Not applicable',
    'none' => 'None',
    default => $displayValue($pricing['deposit'] ?? '', 'Not applicable'),
};
$brokerageType = (string) ($pricing['brokerage_type'] ?? '');
$brokerageLabel = match ($brokerageType) {
    'fixed' => $formatCurrency($pricing['brokerage_value'] ?? null),
    'percentage' => trim((string) ($pricing['brokerage_value'] ?? '')) !== ''
        ? rtrim(rtrim((string) $pricing['brokerage_value'], '0'), '.') . '% of price'
        : 'Not applicable',
    'none' => 'None',
    default => $displayValue($pricing['brokerage'] ?? ''),
};
$pricingHighlights = array_values(array_filter([
    [
        'label' => $priceLabel,
        'value' => $priceHeadline,
        'tone' => 'primary',
    ],
    [
        'label' => 'Security Deposit',
        'value' => $securityDepositLabel,
        'tone' => 'warning',
    ],
    [
        'label' => 'Booking Amount',
        'value' => $formatCurrency($pricing['booking_amount'] ?? null),
        'tone' => 'success',
    ],
    [
        'label' => 'Deal Status',
        'value' => $isNegotiable ? 'Negotiable' : 'Fixed Price',
        'tone' => $isNegotiable ? 'info' : 'slate',
    ],
], static fn (array $item): bool => trim($item['value']) !== ''));

$renderMediaPreview = static function (?array $media, string $extraClass = '') use ($mediaTitle): string {
    if (!$media) {
        return '<div class="review-media-empty">No media uploaded yet.</div>';
    }

    $kind = (string) ($media['kind'] ?? '');
    $classes = trim('media-preview review-feature-media ' . $extraClass);

    if ($kind === 'image') {
        return '<div class="' . e($classes) . '"><img src="' . e((string) $media['file_url']) . '" alt="' . e($mediaTitle($media)) . '"></div>';
    }

    if ($kind === 'video') {
        return '<div class="' . e($classes) . '"><video controls preload="metadata" src="' . e((string) $media['file_url']) . '"></video></div>';
    }

    if ($kind === 'youtube' && trim((string) ($media['youtube_id'] ?? '')) !== '') {
        return '<div class="' . e($classes) . '"><iframe src="https://www.youtube.com/embed/' . e((string) ($media['youtube_id'] ?? '')) . '" title="YouTube video preview" allowfullscreen loading="lazy"></iframe></div>';
    }

    return '<div class="review-media-empty">Preview unavailable.</div>';
};

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="review-shell">
    <section class="panel-card review-hero-card">
        <div class="review-hero-layout">
            <div class="review-hero-copy">
                <div class="review-hero-topline">
                    <p class="eyebrow mb-1">Moderation Workspace</p>
                    <div class="page-tools">
                        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/properties/index.php">Back to Properties</a>
                        <a class="btn btn-dark" href="<?= ADMIN_URL ?>/properties/wizard.php?draft_id=<?= e((string) $draftId) ?>">Open Wizard</a>
                    </div>
                </div>
                <h2 class="review-title"><?= e((string) ($basic['title'] ?? 'Untitled Property Draft')) ?></h2>
                <p class="review-subtitle"><?= e($locationLabel) ?><?php if (($basic['purpose_note'] ?? '') !== ''): ?> / <?= e((string) $basic['purpose_note']) ?><?php endif; ?></p>
                <div class="review-hero-meta">
                    <span class="<?= e($statusMeta['class']) ?>"><?= e($statusMeta['label']) ?></span>
                    <span class="category-pill <?= e($categoryKey !== '' ? $categoryKey : 'residential') ?>"><?= e($categoryLabel) ?></span>
                    <span class="review-meta-chip"><?= e($displayValue($listingType['name'] ?? '', 'Listing')) ?></span>
                    <span class="review-meta-chip">Draft #<?= e((string) $draftId) ?></span>
                </div>

                <div class="review-stat-grid">
                    <article class="review-stat-card">
                        <span><?= e($priceLabel) ?></span>
                        <strong><?= e($priceHeadline) ?></strong>
                    </article>
                    <article class="review-stat-card">
                        <span>Completion</span>
                        <strong><?= e((string) number_format((float) $bundle['progress']['overall_percent'], 0)) ?>%</strong>
                    </article>
                    <article class="review-stat-card">
                        <span>Media</span>
                        <strong><?= e((string) count($bundle['media'])) ?></strong>
                    </article>
                    <article class="review-stat-card">
                        <span>Amenities</span>
                        <strong><?= e((string) count($amenities)) ?></strong>
                    </article>
                </div>
            </div>

            <div class="review-hero-visual">
                <?= $renderMediaPreview($primaryMedia) ?>
                <div class="review-hero-caption">
                    <div>
                        <span class="review-media-label">Featured Preview</span>
                        <strong><?= e($primaryMedia ? $mediaTitle($primaryMedia) : 'No cover media yet') ?></strong>
                    </div>
                    <span class="review-media-kind"><?= e($primaryMedia ? ucfirst((string) ($primaryMedia['kind'] ?? 'media')) : 'Pending') ?></span>
                </div>
            </div>
        </div>
    </section>

    <section class="review-grid">
        <div class="review-main">
            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Overview</p>
                        <h3>Listing Snapshot</h3>
                    </div>
                </div>
                <div class="review-summary-band">
                    <article class="review-summary-card">
                        <span>Owner / Agent</span>
                        <strong><?= e($displayValue($user['name'] ?? '', 'Unassigned')) ?></strong>
                        <p><?= e($displayValue($user['email'] ?? '')) ?><?php if (($user['phone'] ?? '') !== ''): ?> / <?= e((string) $user['phone']) ?><?php endif; ?></p>
                    </article>
                    <article class="review-summary-card">
                        <span>Property Type</span>
                        <strong><?= e($displayValue($propertyType['name'] ?? '')) ?></strong>
                        <p><?= e($categoryLabel) ?><?php if (($listingType['name'] ?? '') !== ''): ?> / <?= e((string) $listingType['name']) ?><?php endif; ?></p>
                    </article>
                    <article class="review-summary-card">
                        <span>Workflow</span>
                        <strong><?= e($displayValue($basic['posted_by'] ?? '', 'Not set')) ?></strong>
                        <p>Available from <?= e($displayValue($basic['available_from'] ?? '', 'Not specified')) ?></p>
                    </article>
                    <article class="review-summary-card">
                        <span>Listing Slug</span>
                        <strong><?= e($propertySlug) ?></strong>
                        <p>Published at <?= e($publishedAt) ?></p>
                    </article>
                </div>
            </section>

            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Narrative</p>
                        <h3>Description & Sales Story</h3>
                    </div>
                </div>
                <div class="review-story-card">
                    <div class="review-story-head">
                        <span class="review-story-label">Property Description</span>
                        <span class="review-story-note"><?= e($displayValue($basic['purpose_note'] ?? '', 'No short note added')) ?></span>
                    </div>
                    <p class="review-description"><?= nl2br(e($displayValue($basic['description'] ?? ''))) ?></p>
                </div>
            </section>

            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Location</p>
                        <h3>Address & Map</h3>
                    </div>
                    <?php if (trim((string) ($location['latitude'] ?? '')) !== '' && trim((string) ($location['longitude'] ?? '')) !== ''): ?>
                        <a class="btn btn-outline-primary btn-sm" href="https://www.google.com/maps?q=<?= urlencode((string) ($location['latitude'] ?? '')) ?>,<?= urlencode((string) ($location['longitude'] ?? '')) ?>" target="_blank" rel="noopener">Open Map</a>
                    <?php endif; ?>
                </div>
                <div class="review-detail-grid">
                    <div class="review-span-2 review-address-card">
                        <span>Full Display Address</span>
                        <strong><?= e($locationLabel) ?></strong>
                        <p><?= e($displayValue($location['address_line'] ?? '')) ?></p>
                    </div>
                    <div><span>Country</span><strong><?= e($displayValue($country['name'] ?? '')) ?></strong></div>
                    <div><span>State</span><strong><?= e($displayValue($state['name'] ?? '')) ?></strong></div>
                    <div><span>City</span><strong><?= e($displayValue($city['name'] ?? '')) ?></strong></div>
                    <div><span>Locality</span><strong><?= e($displayValue($locality['name'] ?? '')) ?></strong></div>
                    <div><span>Pincode</span><strong><?= e($displayValue($location['pincode'] ?? '')) ?></strong></div>
                    <div><span>Landmark</span><strong><?= e($displayValue($location['landmark'] ?? '')) ?></strong></div>
                    <div class="review-span-2"><span>Picked Map Address</span><strong><?= e($displayValue($location['map_address'] ?? '')) ?></strong></div>
                    <div><span>Latitude</span><strong><?= e($displayValue($location['latitude'] ?? '')) ?></strong></div>
                    <div><span>Longitude</span><strong><?= e($displayValue($location['longitude'] ?? '')) ?></strong></div>
                </div>
            </section>

            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Profile</p>
                        <h3>Property Configuration</h3>
                    </div>
                </div>
                <div class="review-detail-grid">
                    <div><span>Built-up Area</span><strong><?= e($formatArea($profile['builtup_area'] ?? null)) ?></strong></div>
                    <div><span>Super Built-up</span><strong><?= e($formatArea($profile['super_builtup_area'] ?? null)) ?></strong></div>
                    <div><span>Carpet Area</span><strong><?= e($formatArea($profile['carpet_area'] ?? null)) ?></strong></div>
                    <div><span>Plot Area</span><strong><?= e($formatArea($profile['plot_area'] ?? null)) ?></strong></div>
                    <div><span>Bedrooms</span><strong><?= e($displayValue($profile['bedrooms'] ?? '')) ?></strong></div>
                    <div><span>Bathrooms</span><strong><?= e($displayValue($profile['bathrooms'] ?? '')) ?></strong></div>
                    <div><span>Balconies</span><strong><?= e($displayValue($profile['balconies'] ?? '')) ?></strong></div>
                    <div><span>Parking Count</span><strong><?= e($displayValue($profile['parking_count'] ?? '')) ?></strong></div>
                    <div><span>Floor No</span><strong><?= e($displayValue($profile['floor_no'] ?? '')) ?></strong></div>
                    <div><span>Total Floors</span><strong><?= e($displayValue($profile['total_floor'] ?? '')) ?></strong></div>
                    <div><span>Furnishing</span><strong><?= e(propertyFurnishingLabel((string) ($profile['furnishing'] ?? '')) ?: 'Not provided') ?></strong></div>
                    <div><span>Property Age</span><strong><?= e($displayValue($profile['property_age'] ?? '')) ?></strong></div>
                    <div><span>Facing</span><strong><?= e($displayValue($profile['facing'] ?? '')) ?></strong></div>
                    <div><span>Ownership Type</span><strong><?= e($displayValue($profile['ownership_type'] ?? '')) ?></strong></div>
                    <div class="review-span-2"><span>Furnishing Includes</span><strong><?= e($furnishingItems !== [] ? implode(', ', $furnishingItems) : 'Not provided') ?></strong></div>
                    <div class="review-span-2"><span>Office Setup</span><strong><?= e($officeAdminDetails !== [] ? implode(', ', $officeAdminDetails) : 'Not provided') ?></strong></div>
                    <div class="review-span-2"><span>PG Details</span><strong><?= e($pgAdminDetails !== [] ? implode(', ', $pgAdminDetails) : 'Not provided') ?></strong></div>
                    <div class="review-span-2"><span>Extra Rooms</span><strong><?= e($extraRooms !== [] ? implode(', ', $extraRooms) : 'Not provided') ?></strong></div>
                </div>
            </section>

            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Pricing</p>
                        <h3>Commercial Details</h3>
                    </div>
                </div>
                <div class="review-price-band">
                    <?php foreach ($pricingHighlights as $highlight): ?>
                        <article class="review-price-card review-price-card-<?= e($highlight['tone']) ?>">
                            <span><?= e($highlight['label']) ?></span>
                            <strong><?= e($highlight['value']) ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="review-detail-grid">
                    <div><span>Expected Price</span><strong><?= e($formatCurrency($pricing['expected_price'] ?? null)) ?></strong></div>
                    <div><span>Monthly Rent</span><strong><?= e($formatCurrency($pricing['rent'] ?? null)) ?></strong></div>
                    <div><span>Security Deposit</span><strong><?= e($securityDepositLabel) ?></strong></div>
                    <div><span>Booking Amount</span><strong><?= e($formatCurrency($pricing['booking_amount'] ?? null)) ?></strong></div>
                    <div><span>Maintenance</span><strong><?= e($formatCurrency($pricing['maintenance'] ?? null)) ?></strong></div>
                    <div><span>Maintenance Period</span><strong><?= e($displayValue($pricing['maintenance_period'] ?? '', 'Not applicable')) ?></strong></div>
                    <div><span>Electricity Charges</span><strong><?= e($displayValue($pricing['electricity_charges'] ?? '')) ?></strong></div>
                    <div><span>Brokerage</span><strong><?= e($brokerageLabel) ?></strong></div>
                    <div><span>Brokerage Negotiable</span><strong><?= (int) ($pricing['brokerage_negotiable'] ?? 0) === 1 ? 'Yes' : 'No' ?></strong></div>
                    <div><span>Lock-in Period</span><strong><?= e(trim((string) ($pricing['lock_in_months'] ?? '')) !== '' ? (string) $pricing['lock_in_months'] . ' month(s)' : 'Not applicable') ?></strong></div>
                    <div><span>Yearly Rent Increase</span><strong><?= e(trim((string) ($pricing['annual_rent_increase_percent'] ?? '')) !== '' ? rtrim(rtrim((string) $pricing['annual_rent_increase_percent'], '0'), '.') . '%' : 'Not applicable') ?></strong></div>
                    <div><span>DG &amp; UPS Included</span><strong><?= (int) ($pricing['dg_ups_included'] ?? 0) === 1 ? 'Yes' : 'No' ?></strong></div>
                    <div><span>Electricity &amp; Water Excluded</span><strong><?= (int) ($pricing['electricity_water_excluded'] ?? 0) === 1 ? 'Yes' : 'No' ?></strong></div>
                    <div><span>Negotiable</span><strong><?= $isNegotiable ? 'Yes' : 'No' ?></strong></div>
                </div>
            </section>

            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Amenities</p>
                        <h3>Included Features</h3>
                    </div>
                </div>
                <?php if ($amenities !== []): ?>
                    <div class="review-tag-list review-tag-list-large">
                        <?php foreach ($amenities as $amenity): ?>
                            <span class="review-tag"><?= e((string) ($amenity['name'] ?? 'Amenity')) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="panel-copy mb-0">No amenities selected for this listing.</p>
                <?php endif; ?>
            </section>

            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Media</p>
                        <h3>Photos & Videos</h3>
                    </div>
                    <div class="review-media-summary">
                        <span class="review-counter-pill"><i class="bi bi-image"></i><?= e((string) $mediaCounts['image']) ?> Images</span>
                        <span class="review-counter-pill"><i class="bi bi-camera-reels"></i><?= e((string) $mediaCounts['video']) ?> Videos</span>
                        <span class="review-counter-pill"><i class="bi bi-youtube"></i><?= e((string) $mediaCounts['youtube']) ?> YouTube</span>
                        <span class="review-counter-pill"><i class="bi bi-star-fill"></i><?= e((string) $coverMediaCount) ?> Cover</span>
                    </div>
                </div>
                <?php if ($bundle['media'] !== []): ?>
                    <div class="review-gallery-grid">
                        <?php foreach ($bundle['media'] as $index => $media): ?>
                            <article class="review-gallery-card">
                                <div class="review-gallery-frame">
                                    <?= $renderMediaPreview($media, 'review-gallery-preview') ?>
                                    <button
                                        class="review-gallery-open"
                                        type="button"
                                        data-review-gallery-trigger
                                        data-index="<?= e((string) $index) ?>"
                                        data-kind="<?= e((string) ($media['kind'] ?? '')) ?>"
                                        data-title="<?= e($mediaTitle($media)) ?>"
                                        data-file-url="<?= e((string) ($media['file_url'] ?? '')) ?>"
                                        data-youtube-id="<?= e((string) ($media['youtube_id'] ?? '')) ?>"
                                        data-primary="<?= (int) ($media['is_primary'] ?? 0) === 1 ? '1' : '0' ?>"
                                        aria-label="Open full preview for <?= e($mediaTitle($media)) ?>"
                                    >
                                        <i class="bi bi-arrows-fullscreen"></i>
                                    </button>
                                </div>
                                <div class="review-gallery-body">
                                    <div class="review-gallery-top">
                                        <strong><?= e($mediaTitle($media)) ?></strong>
                                        <?php if ((int) ($media['is_primary'] ?? 0) === 1): ?>
                                            <span class="media-badge">Cover Photo</span>
                                        <?php endif; ?>
                                    </div>
                                    <p><?= e(ucfirst((string) ($media['kind'] ?? 'media'))) ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="panel-copy mb-0">No media uploaded yet.</p>
                <?php endif; ?>
            </section>
        </div>

        <aside class="review-sidebar">
            <section class="panel-card review-section" id="moderation">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Moderation</p>
                        <h3>Approve or Reject</h3>
                    </div>
                </div>
                <div class="review-moderation-banner">
                    <span class="<?= e($statusMeta['class']) ?>"><?= e($statusMeta['label']) ?></span>
                    <p>Use this panel to publish the listing immediately or send it back with a clear correction reason.</p>
                </div>
                <div class="review-detail-stack">
                    <div>
                        <span>Current Status</span>
                        <strong class="d-block mt-1"><?= e($statusMeta['label']) ?></strong>
                    </div>
                    <div>
                        <span>Rejected Reason</span>
                        <strong class="d-block mt-1"><?= e($displayValue($property['rejected_reason'] ?? '')) ?></strong>
                    </div>
                    <div>
                        <span>Internal Note</span>
                        <strong class="d-block mt-1"><?= e($displayValue($draft['admin_note'] ?? '')) ?></strong>
                    </div>
                </div>

                <?php if (canApprovePropertyDraft($listRow)): ?>
                    <form class="admin-form mt-4" method="post" action="<?= ADMIN_URL ?>/properties/approve.php" data-confirm="Approve this property listing?" data-confirm-button="Approve" data-loading-text="Approving property...">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                        <button class="btn btn-success w-100" type="submit">Approve & Publish</button>
                    </form>
                <?php endif; ?>

                <?php if (canRejectPropertyDraft($listRow)): ?>
                    <form class="admin-form mt-4" method="post" action="<?= ADMIN_URL ?>/properties/reject.php" data-confirm="Reject this property listing?" data-confirm-button="Reject" data-loading-text="Rejecting property...">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="draft_id" value="<?= e((string) $draftId) ?>">
                        <input type="hidden" name="return_to" value="<?= e($returnTo . '#moderation') ?>">
                        <div class="form-grid">
                            <div class="form-field form-field-span-2">
                                <label class="required-label" for="rejected_reason">Rejection Reason</label>
                                <textarea class="form-control" id="rejected_reason" name="rejected_reason" rows="4" maxlength="255" required placeholder="Tell the team why this listing is being rejected."></textarea>
                            </div>
                            <div class="form-field form-field-span-2">
                                <label for="admin_note">Internal Admin Note</label>
                                <input class="form-control" id="admin_note" name="admin_note" type="text" maxlength="255" placeholder="Optional internal note for follow-up">
                            </div>
                        </div>
                        <button class="btn btn-outline-danger w-100" type="submit">Reject With Reason</button>
                    </form>
                <?php endif; ?>

                <?php if (!canApprovePropertyDraft($listRow) && !canRejectPropertyDraft($listRow)): ?>
                    <p class="panel-copy mb-0 mt-3">This listing is already published. Open the wizard if you want to make changes before a future moderation action.</p>
                <?php endif; ?>
            </section>

            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Timeline</p>
                        <h3>Status History</h3>
                    </div>
                </div>
                <div class="timeline-list">
                    <?php foreach ($timeline as $event): ?>
                        <article class="review-timeline-card">
                            <div class="review-timeline-dot"></div>
                            <div>
                                <strong><?= e((string) $event['title']) ?></strong>
                                <p class="table-subtext mb-1"><?= e($formatDateTime((string) $event['timestamp'])) ?></p>
                                <p class="mb-0"><?= e((string) $event['description']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel-card review-section">
                <div class="panel-head">
                    <div>
                        <p class="eyebrow mb-1">Record</p>
                        <h3>Audit Summary</h3>
                    </div>
                </div>
                <div class="review-detail-stack">
                    <div><span>Draft Created</span><strong class="d-block mt-1"><?= e($formatDateTime((string) ($draft['created_at'] ?? ''))) ?></strong></div>
                    <div><span>Last Updated</span><strong class="d-block mt-1"><?= e($formatDateTime((string) ($draft['updated_at'] ?? ''))) ?></strong></div>
                    <div><span>Submitted At</span><strong class="d-block mt-1"><?= e($formatDateTime((string) ($draft['submitted_at'] ?? ''))) ?></strong></div>
                    <div><span>Property Slug</span><strong class="d-block mt-1"><?= e($propertySlug) ?></strong></div>
                </div>
            </section>
        </aside>
    </section>
</section>
<div class="modal fade review-media-modal" id="reviewGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="review-modal-kicker mb-1">Media Preview</p>
                    <h4 class="modal-title" id="reviewGalleryModalLabel">Property Media</h4>
                </div>
                <div class="review-modal-tools">
                    <span class="review-modal-kind" id="reviewGalleryModalKind">Media</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <button class="review-modal-nav review-modal-prev" id="reviewGalleryPrev" type="button" aria-label="Previous media">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div class="review-modal-stage" id="reviewGalleryStage"></div>
                <button class="review-modal-nav review-modal-next" id="reviewGalleryNext" type="button" aria-label="Next media">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined') {
        return;
    }

    var modalElement = document.getElementById('reviewGalleryModal');
    var stage = document.getElementById('reviewGalleryStage');
    var title = document.getElementById('reviewGalleryModalLabel');
    var kind = document.getElementById('reviewGalleryModalKind');
    var prevButton = document.getElementById('reviewGalleryPrev');
    var nextButton = document.getElementById('reviewGalleryNext');
    var triggers = Array.prototype.slice.call(document.querySelectorAll('[data-review-gallery-trigger]'));

    if (!modalElement || !stage || !title || !kind || triggers.length === 0) {
        return;
    }

    var modal = new bootstrap.Modal(modalElement);
    var items = triggers.map(function (trigger) {
        return {
            title: trigger.dataset.title || 'Property Media',
            kind: trigger.dataset.kind || 'media',
            fileUrl: trigger.dataset.fileUrl || '',
            youtubeId: trigger.dataset.youtubeId || '',
            isPrimary: trigger.dataset.primary === '1'
        };
    });
    var activeIndex = 0;

    function renderMedia(index) {
        var item = items[index];
        if (!item) {
            return;
        }

        activeIndex = index;
        title.textContent = item.title;
        kind.textContent = item.isPrimary ? 'Cover ' + item.kind : item.kind.charAt(0).toUpperCase() + item.kind.slice(1);

        if (item.kind === 'image' && item.fileUrl !== '') {
            stage.innerHTML = '<img src="' + item.fileUrl + '" alt="' + item.title.replace(/"/g, '&quot;') + '">';
        } else if (item.kind === 'video' && item.fileUrl !== '') {
            stage.innerHTML = '<video controls autoplay preload="metadata" src="' + item.fileUrl + '"></video>';
        } else if (item.kind === 'youtube' && item.youtubeId !== '') {
            stage.innerHTML = '<iframe src="https://www.youtube.com/embed/' + item.youtubeId + '?autoplay=1" title="' + item.title.replace(/"/g, '&quot;') + '" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
        } else {
            stage.innerHTML = '<div class="review-media-empty">Preview unavailable.</div>';
        }

        prevButton.disabled = items.length <= 1;
        nextButton.disabled = items.length <= 1;
    }

    triggers.forEach(function (trigger, index) {
        trigger.addEventListener('click', function () {
            renderMedia(index);
            modal.show();
        });
    });

    prevButton.addEventListener('click', function () {
        renderMedia((activeIndex - 1 + items.length) % items.length);
    });

    nextButton.addEventListener('click', function () {
        renderMedia((activeIndex + 1) % items.length);
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        stage.innerHTML = '';
    });
});
</script>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
