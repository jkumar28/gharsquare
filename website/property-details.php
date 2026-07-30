<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$property = $slug !== '' ? siteFindPropertyBySlug($slug) : null;

if (!$property) {
    http_response_code(404);
    websiteHeader(
        'Property not found - GharSquare',
        'This property is unavailable or is no longer active.',
        'details-page'
    );
    ?>
    <main class="details-main">
        <section class="details-not-found">
            <i class="bi bi-house-x"></i>
            <h1>Property unavailable</h1>
            <p>This listing may be inactive, booked, sold, occupied or removed.</p>
            <a href="<?= e(siteListingUrl()) ?>">Browse live properties</a>
        </section>
    </main>
    <?php
    websiteFooter();
    exit;
}

$similarProperties = siteSimilarProperties($property, 3);
$images = $property['images'] ?? [];
$videos = $property['videos'] ?? [];
$amenities = $property['amenity_names'] ?? [];
$furnishingItems = propertyFurnishingItemLabels($property['furnishing_items'] ?? []);
$officeDetails = propertyOfficeProfileSummary($property);
$pgDetails = propertyPgProfileSummary($property);
$user = publicUser();
$isSaved = publicSavedPropertyExists((string) $property['id']);
$locationDetails = array_values(array_filter([
    trim((string) ($property['address_line'] ?? '')),
    trim((string) ($property['landmark'] ?? '')),
    trim((string) ($property['map_address'] ?? '')),
    trim((string) ($property['pincode'] ?? '')),
]));
$listingLabel = trim((string) ($property['listing_type_name'] ?? ''));
$propertyType = trim((string) ($property['property_type_name'] ?? ''));
$postedBy = trim((string) ($property['posted_by'] ?? ''));
$publishedAt = trim((string) ($property['published_at'] ?? $property['created_at'] ?? ''));
$metaDescription = trim((string) ($property['description'] ?? ''));
if ($metaDescription === '') {
    $metaDescription = implode(' in ', array_filter([
        trim((string) ($property['title'] ?? 'Active property')),
        trim((string) ($property['location_label'] ?? '')),
    ]));
}
$canonical = sitePropertyUrl($property);

$summarySpecs = [];
if ($propertyType !== '') {
    $summarySpecs[] = ['Property Type', $propertyType];
}
if (($property['area_label'] ?? '') !== '') {
    $summarySpecs[] = ['Area', (string) $property['area_label']];
}
if ((int) ($property['bedrooms'] ?? 0) > 0) {
    $summarySpecs[] = ['Bedrooms', (int) $property['bedrooms'] . ' BHK'];
}
if (($property['available_from'] ?? '') !== '') {
    $summarySpecs[] = ['Available From', date('d M Y', strtotime((string) $property['available_from']))];
}

$overview = [];
$overviewMap = [
    ['super_builtup_area', 'bi-aspect-ratio', 'Super Built-up Area', true],
    ['builtup_area', 'bi-bounding-box', 'Built-up Area', true],
    ['carpet_area', 'bi-crop', 'Carpet Area', true],
    ['plot_area', 'bi-map', 'Plot Area', true],
    ['bathrooms', 'bi-droplet', 'Bathrooms', false],
    ['balconies', 'bi-door-open', 'Balconies', false],
    ['parking_count', 'bi-car-front', 'Parking', false],
    ['furnishing', 'bi-lamp', 'Furnishing', false],
    ['property_age', 'bi-clock-history', 'Property Age', false],
    ['facing', 'bi-compass', 'Facing', false],
    ['ownership_type', 'bi-file-earmark-check', 'Ownership', false],
];
foreach ($overviewMap as [$key, $icon, $label, $isArea]) {
    $value = $property[$key] ?? null;
    if ($isArea && (float) $value > 0) {
        $overview[] = [$icon, $label, formatNumberIndian((float) $value) . ' ' . normalizeAreaUnit((string) ($property['area_unit'] ?? 'sq.ft'))];
    } elseif (!$isArea && trim((string) $value) !== '' && (string) $value !== '0') {
        $overview[] = [
            $icon,
            $label,
            $key === 'furnishing' ? propertyFurnishingLabel((string) $value) : ucwords(str_replace('_', ' ', (string) $value)),
        ];
    }
}
if ((int) ($property['floor_no'] ?? 0) > 0 || (int) ($property['total_floor'] ?? 0) > 0) {
    $floorValue = (int) ($property['floor_no'] ?? 0) > 0 ? (string) (int) $property['floor_no'] : '';
    if ((int) ($property['total_floor'] ?? 0) > 0) {
        $floorValue .= ($floorValue !== '' ? ' of ' : '') . (int) $property['total_floor'];
    }
    $overview[] = ['bi-layers', 'Floor', $floorValue];
}

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

$pricingDetails = [];
$priceMap = [
    ['price_per_area_unit', 'Price per area unit', 'currency'],
    ['security_deposit_amount', 'Security deposit', 'currency'],
    ['security_deposit_months', 'Security deposit', 'months'],
    ['booking_amount', 'Booking amount', 'currency'],
    ['maintenance', 'Maintenance', 'currency'],
    ['brokerage_value', 'Brokerage', ($property['brokerage_type'] ?? '') === 'percentage' ? 'percent' : 'currency'],
    ['lock_in_months', 'Lock-in period', 'months'],
    ['annual_rent_increase_percent', 'Annual rent increase', 'percent'],
];
foreach ($priceMap as [$key, $label, $format]) {
    $value = (float) ($property[$key] ?? 0);
    if ($value <= 0) {
        continue;
    }
    $formatted = match ($format) {
        'currency' => siteCurrency($value),
        'months' => formatNumberIndian($value) . ' month' . ($value === 1.0 ? '' : 's'),
        'percent' => formatNumberIndian($value) . '%',
        default => (string) $value,
    };
    $pricingDetails[] = [$label, $formatted];
}

websiteHeader(
    trim((string) $property['title']) . ' - GharSquare',
    substr($metaDescription, 0, 155),
    'details-page',
    [
        'selected_city' => (string) ($property['city_name'] ?? ''),
        'canonical' => $canonical,
        'image' => (string) ($property['primary_image'] ?? ''),
    ]
);
?>
<main class="details-main" data-property-id="<?= e((string) $property['id']) ?>" data-csrf-token="<?= e(csrfToken()) ?>">
    <section class="details-hero">
        <div class="container-fluid px-lg-5">
            <div class="details-breadcrumb">
                <a href="<?= e(siteWebsiteUrl()) ?>">Home</a>
                <i class="bi bi-chevron-right"></i>
                <a href="<?= e(siteListingUrl(['type' => $property['public_type'], 'city' => $property['city_name']])) ?>">Listings</a>
                <i class="bi bi-chevron-right"></i>
                <span><?= e((string) $property['title']) ?></span>
            </div>

            <div class="details-top-grid <?= $images === [] ? 'details-without-gallery' : '' ?>">
                <?php if ($images !== []): ?>
                    <div class="details-gallery" data-gallery>
                        <div class="main-photo">
                            <img data-gallery-main src="<?= e((string) $images[0]['file_url']) ?>" alt="<?= e((string) $property['title']) ?>">
                            <span class="photo-count"><?= e((string) count($images)) ?> Photo<?= count($images) === 1 ? '' : 's' ?></span>
                            <?php if (count($images) > 1): ?>
                                <button type="button" class="gallery-nav gallery-prev" data-gallery-prev aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" class="gallery-nav gallery-next" data-gallery-next aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
                            <?php endif; ?>
                        </div>
                        <?php if (count($images) > 1): ?>
                            <div class="thumb-row">
                                <?php foreach ($images as $index => $image): ?>
                                    <button type="button" class="thumb-btn <?= $index === 0 ? 'active' : '' ?>" data-gallery-thumb data-index="<?= e((string) $index) ?>" data-src="<?= e((string) $image['file_url']) ?>">
                                        <img src="<?= e((string) $image['file_url']) ?>" alt="<?= e((string) (($image['title'] ?? '') ?: $property['title'])) ?>">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="details-summary-panel">
                    <?php if ($listingLabel !== '' || $propertyType !== ''): ?>
                        <div class="summary-badges">
                            <?php if ($listingLabel !== ''): ?><span class="summary-badge"><?= e($listingLabel) ?></span><?php endif; ?>
                            <?php if ($propertyType !== ''): ?><span class="summary-badge"><?= e($propertyType) ?></span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <h1><?= e((string) $property['title']) ?></h1>
                    <?php if (($property['location_label'] ?? '') !== ''): ?>
                        <p class="detail-location"><i class="bi bi-geo-alt"></i> <?= e((string) $property['location_label']) ?></p>
                    <?php endif; ?>
                    <?php if (($property['price_label'] ?? '') !== ''): ?>
                        <div class="detail-price"><?= e((string) $property['price_label']) ?></div>
                    <?php endif; ?>

                    <?php if ($summarySpecs !== []): ?>
                        <div class="detail-spec-grid">
                            <?php foreach ($summarySpecs as [$label, $value]): ?>
                                <div class="detail-spec"><span><?= e($label) ?></span><strong><?= e($value) ?></strong></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="detail-action-row">
                        <button type="button" class="primary-action" data-scroll-enquiry><i class="bi bi-send"></i> Send Enquiry</button>
                        <button type="button" class="secondary-action premium-contact-lock" disabled title="Direct contact will be available with a future Premium plan">
                            <i class="bi bi-lock"></i> Premium Contact
                        </button>
                        <button type="button" class="icon-action <?= $isSaved ? 'active' : '' ?>" data-detail-save aria-label="Save property">
                            <i class="bi <?= $isSaved ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="details-content">
        <div class="container-fluid px-lg-5">
            <div class="details-layout">
                <div class="details-left">
                    <?php if ($overview !== []): ?>
                        <section class="detail-panel">
                            <div class="detail-section-heading">
                                <h2>Overview</h2>
                                <?php if ($publishedAt !== ''): ?><span>Live since <?= e(date('d M Y', strtotime($publishedAt))) ?></span><?php endif; ?>
                            </div>
                            <div class="overview-grid">
                                <?php foreach ($overview as [$icon, $label, $value]): ?>
                                    <div class="overview-item"><i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span><strong><?= e($value) ?></strong></div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (trim((string) ($property['description'] ?? '')) !== ''): ?>
                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Description</h2></div>
                            <p class="property-description"><?= nl2br(e((string) $property['description'])) ?></p>
                        </section>
                    <?php endif; ?>

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

                    <?php if ($pricingDetails !== []): ?>
                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Pricing Details</h2></div>
                            <div class="pricing-detail-grid">
                                <?php foreach ($pricingDetails as [$label, $value]): ?>
                                    <div><span><?= e($label) ?></span><strong><?= e($value) ?></strong></div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($amenities !== []): ?>
                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Amenities</h2></div>
                            <div class="amenity-grid">
                                <?php foreach ($amenities as $amenity): ?>
                                    <div class="amenity-item"><i class="bi bi-check2-circle"></i> <?= e((string) $amenity) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($videos !== []): ?>
                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Property Videos</h2></div>
                            <div class="property-video-grid">
                                <?php foreach ($videos as $video): ?>
                                    <?php if (str_contains((string) $video['file_url'], 'youtube.com') || str_contains((string) $video['file_url'], 'youtu.be')): ?>
                                        <a href="<?= e((string) $video['file_url']) ?>" target="_blank" rel="noopener" class="property-video-link"><i class="bi bi-play-circle"></i> Watch property video</a>
                                    <?php else: ?>
                                        <video controls preload="metadata"><source src="<?= e((string) $video['file_url']) ?>" type="<?= e((string) ($video['mime_type'] ?? 'video/mp4')) ?>"></video>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($locationDetails !== [] || ($property['latitude'] ?? '') !== '' || ($property['longitude'] ?? '') !== ''): ?>
                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Location</h2></div>
                            <div class="property-location-details">
                                <i class="bi bi-geo-alt-fill"></i>
                                <div>
                                    <?php if (($property['location_label'] ?? '') !== ''): ?><strong><?= e((string) $property['location_label']) ?></strong><?php endif; ?>
                                    <?php foreach ($locationDetails as $detail): ?><span><?= e($detail) ?></span><?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($similarProperties !== []): ?>
                        <section class="detail-panel">
                            <div class="detail-section-heading"><h2>Similar Properties</h2></div>
                            <div class="similar-grid">
                                <?php foreach ($similarProperties as $similar): ?>
                                    <a class="similar-card" href="<?= e(sitePropertyUrl($similar)) ?>">
                                        <?php if (($similar['primary_image'] ?? '') !== ''): ?><img src="<?= e((string) $similar['primary_image']) ?>" alt="<?= e((string) $similar['title']) ?>"><?php endif; ?>
                                        <div class="similar-card-body">
                                            <?php if (($similar['price_label'] ?? '') !== ''): ?><strong><?= e((string) $similar['price_label']) ?></strong><?php endif; ?>
                                            <h3><?= e((string) $similar['title']) ?></h3>
                                            <?php if (($similar['location_label'] ?? '') !== ''): ?><p><?= e((string) $similar['location_label']) ?></p><?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="detail-panel property-enquiry-section" id="propertyEnquiry">
                        <div class="detail-section-heading">
                            <div>
                                <h2>Enquire About This Property</h2>
                                <p>Your request is recorded securely and shared with the assigned admin or property owner.</p>
                            </div>
                        </div>
                        <form class="property-enquiry-form" data-property-enquiry-form>
                            <div class="enquiry-form-grid">
                                <label>
                                    <span>Full Name</span>
                                    <input name="name" type="text" maxlength="100" value="<?= e((string) ($user['name'] ?? '')) ?>" required>
                                </label>
                                <label>
                                    <span>Email</span>
                                    <input name="email" type="email" maxlength="150" value="<?= e((string) ($user['email'] ?? '')) ?>" required>
                                </label>
                                <label>
                                    <span>Phone</span>
                                    <input name="phone" type="tel" maxlength="20" value="<?= e((string) ($user['phone'] ?? '')) ?>" required>
                                </label>
                                <label>
                                    <span>I want to</span>
                                    <select name="enquiry_type" required>
                                        <option value="callback">Request a callback</option>
                                        <option value="visit">Schedule a visit</option>
                                        <option value="buy">Discuss purchase</option>
                                        <option value="rent">Discuss rent</option>
                                    </select>
                                </label>
                                <label>
                                    <span>Preferred Contact</span>
                                    <select name="preferred_contact" required>
                                        <option value="call">Phone call</option>
                                        <option value="email">Email</option>
                                        <option value="whatsapp">WhatsApp</option>
                                    </select>
                                </label>
                                <label class="enquiry-message">
                                    <span>Message</span>
                                    <textarea name="message" maxlength="2000" rows="5" required>I am interested in this property. Please share the next steps.</textarea>
                                </label>
                            </div>
                            <label class="enquiry-consent">
                                <input type="checkbox" name="consent" value="1" required>
                                <span>I agree to be contacted about this property and understand that my enquiry will be recorded.</span>
                            </label>
                            <button type="submit" class="primary-action enquiry-submit"><i class="bi bi-send"></i> Send Enquiry</button>
                            <p class="enquiry-login-note" data-enquiry-note>
                                <?= $user ? 'Submitting as ' . e((string) $user['name']) . '.' : 'Login is required before the enquiry is sent.' ?>
                            </p>
                        </form>
                    </section>
                </div>

                <aside class="contact-panel">
                    <div class="private-contact-icon"><i class="bi bi-shield-lock"></i></div>
                    <h2>Private contact protection</h2>
                    <p>Owner and agent phone numbers are not published. Send an enquiry and the right person will follow up.</p>
                    <?php if ($postedBy !== ''): ?>
                        <div class="agent-verified"><i class="bi bi-patch-check-fill"></i><span>Listed by <?= e(ucfirst($postedBy)) ?></span></div>
                    <?php endif; ?>
                    <button type="button" data-scroll-enquiry><i class="bi bi-chat-left-text"></i> Send Enquiry</button>
                    <div class="premium-contact-box">
                        <i class="bi bi-stars"></i>
                        <div><strong>Direct contact access</strong><span>Reserved for future Premium plans</span></div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</main>
<?php websiteFooter(['scripts' => ['property-details-live.js']]); ?>
