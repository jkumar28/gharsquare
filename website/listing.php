<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

if (!preg_match('~/properties/?$~', (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH))) {
    header('Location: ' . siteListingUrl($_GET), true, 301);
    exit;
}

$allowedTypes = ['buy', 'rent', 'commercial', 'pg', 'plots'];
$type = strtolower(trim((string) ($_GET['type'] ?? '')));
$type = in_array($type, $allowedTypes, true) ? $type : '';
$filters = [
    'type' => $type,
    'city' => trim((string) ($_GET['city'] ?? '')),
    'q' => trim((string) ($_GET['q'] ?? '')),
    'property_type_id' => (int) ($_GET['property_type_id'] ?? 0),
    'budget' => trim((string) ($_GET['budget'] ?? '')),
    'bhk' => (int) ($_GET['bhk'] ?? 0),
    'min_area' => (float) ($_GET['min_area'] ?? 0),
    'sort' => trim((string) ($_GET['sort'] ?? 'latest')),
    'page' => max(1, (int) ($_GET['page'] ?? 1)),
];
$results = siteSearchProperties($filters, 12);
$filterData = siteSearchFilterData();
$cities = $filterData['cities'];
$selectedCity = $filters['city'] !== '' ? $filters['city'] : websiteSelectedCity($cities);
$savedRefs = array_fill_keys(publicUserSavedPropertyRefs(), true);
$typeLabels = [
    '' => 'All live properties',
    'buy' => 'Properties for sale',
    'rent' => 'Rental properties',
    'commercial' => 'Commercial properties',
    'pg' => 'PG and co-living',
    'plots' => 'Land and plots',
];
$heading = $typeLabels[$type];
if ($filters['city'] !== '') {
    $heading .= ' in ' . $filters['city'];
}

function listingQueryUrl(array $changes = []): string
{
    $query = array_merge($_GET, $changes);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null || $value === 0 || $value === '0') {
            unset($query[$key]);
        }
    }

    return siteListingUrl($query);
}

websiteHeader(
    $heading . ' - GharSquare',
    'Browse active property listings with real city, property type, price, bedroom and area filters.',
    'listing-page',
    ['cities' => $cities, 'selected_city' => $selectedCity]
);
?>
<main class="listing-main" data-csrf-token="<?= e(csrfToken()) ?>">
    <section class="listing-hero">
        <div class="container-fluid px-lg-5">
            <div class="listing-hero-grid">
                <div>
                    <a href="<?= e(siteWebsiteUrl()) ?>" class="back-link"><i class="bi bi-arrow-left"></i> Home</a>
                    <h1><?= e($heading) ?></h1>
                    <p><?= e((string) $results['total']) ?> active listing<?= $results['total'] === 1 ? '' : 's' ?> matched to your search.</p>
                </div>

                <form class="listing-search-panel" method="get" action="<?= e(siteWebsiteUrl('listing')) ?>">
                    <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
                    <?php if ($filters['city'] !== ''): ?><input type="hidden" name="city" value="<?= e($filters['city']) ?>"><?php endif; ?>
                    <div class="listing-search-row">
                        <div class="listing-input-wrap">
                            <i class="bi bi-search"></i>
                            <input name="q" type="search" value="<?= e($filters['q']) ?>" placeholder="Locality, landmark or property type">
                        </div>
                        <button class="listing-search-btn" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="listing-content">
        <div class="container-fluid px-lg-5">
            <nav class="live-type-tabs" aria-label="Listing type">
                <?php foreach ($typeLabels as $key => $label): ?>
                    <a class="<?= $type === $key ? 'active' : '' ?>" href="<?= e(listingQueryUrl(['type' => $key, 'page' => 1])) ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <button class="filter-toggle listing-filter-mobile" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileFilters">
                <i class="bi bi-sliders"></i> Filters
            </button>

            <div class="listing-layout listing-layout-live">
                <aside class="filter-panel desktop-filters" aria-label="Property filters">
                    <div class="filter-heading">
                        <h2>Filters</h2>
                        <a class="clear-filter-btn" href="<?= e(siteListingUrl($type !== '' ? ['type' => $type] : [])) ?>">Clear</a>
                    </div>
                    <?php $filterFormIndex = 'desktop'; require __DIR__ . '/partials/listing-filters.php'; ?>
                </aside>

                <div class="results-area">
                    <div class="results-toolbar">
                        <div>
                            <h2><?= e($typeLabels[$type]) ?></h2>
                            <p><?= e((string) $results['total']) ?> listing<?= $results['total'] === 1 ? '' : 's' ?> found</p>
                        </div>
                        <form method="get" action="<?= e(siteWebsiteUrl('listing')) ?>">
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if ($key !== 'sort' && $key !== 'page' && is_scalar($value)): ?>
                                    <input type="hidden" name="<?= e((string) $key) ?>" value="<?= e((string) $value) ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <select name="sort" class="sort-select" aria-label="Sort listings" onchange="this.form.submit()">
                                <option value="latest" <?= $filters['sort'] === 'latest' ? 'selected' : '' ?>>Latest first</option>
                                <option value="price_low" <?= $filters['sort'] === 'price_low' ? 'selected' : '' ?>>Price low to high</option>
                                <option value="price_high" <?= $filters['sort'] === 'price_high' ? 'selected' : '' ?>>Price high to low</option>
                                <option value="area_high" <?= $filters['sort'] === 'area_high' ? 'selected' : '' ?>>Area high to low</option>
                            </select>
                        </form>
                    </div>

                    <?php if ($results['items'] !== []): ?>
                        <div class="results-grid live-results-grid list-view">
                            <?php foreach ($results['items'] as $property): ?>
                                <?php
                                $propertyId = (string) $property['id'];
                                $isSaved = isset($savedRefs[$propertyId]);
                                $facts = array_values(array_filter([
                                    (int) ($property['bedrooms'] ?? 0) > 0 ? (int) $property['bedrooms'] . ' BHK' : '',
                                    (string) ($property['area_label'] ?? ''),
                                    (string) ($property['property_type_name'] ?? ''),
                                ]));
                                ?>
                                <article class="result-card" data-property-id="<?= e($propertyId) ?>">
                                    <a class="result-image" href="<?= e(sitePropertyUrl($property)) ?>">
                                        <?php if (($property['primary_image'] ?? '') !== ''): ?>
                                            <img src="<?= e((string) $property['primary_image']) ?>" alt="<?= e((string) $property['title']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <span class="property-image-empty"><i class="bi bi-building"></i></span>
                                        <?php endif; ?>
                                        <span class="result-badge"><?= e((string) $property['listing_type_name']) ?></span>
                                    </a>
                                    <div class="result-body">
                                        <div class="result-top">
                                            <?php if (($property['price_label'] ?? '') !== ''): ?>
                                                <div class="result-price"><?= e((string) $property['price_label']) ?></div>
                                            <?php endif; ?>
                                            <button type="button" class="save-btn <?= $isSaved ? 'active' : '' ?>" data-save-property aria-label="<?= $isSaved ? 'Remove saved property' : 'Save property' ?>">
                                                <i class="bi <?= $isSaved ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                            </button>
                                        </div>
                                        <?php if (($property['title'] ?? '') !== ''): ?>
                                            <h3><a href="<?= e(sitePropertyUrl($property)) ?>"><?= e((string) $property['title']) ?></a></h3>
                                        <?php endif; ?>
                                        <?php if (($property['location_label'] ?? '') !== ''): ?>
                                            <p class="result-location"><i class="bi bi-geo-alt"></i> <?= e((string) $property['location_label']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($facts !== []): ?>
                                            <div class="result-specs">
                                                <?php foreach ($facts as $fact): ?><span><?= e($fact) ?></span><?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (($property['amenity_names'] ?? []) !== []): ?>
                                            <div class="listing-amenities">
                                                <?= e(implode(' | ', $property['amenity_names'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="result-actions">
                                            <a class="details-btn" href="<?= e(sitePropertyUrl($property)) ?>">View Details</a>
                                            <button type="button" class="enquire-btn" data-quick-enquiry>Enquire</button>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($results['pages'] > 1): ?>
                            <nav class="live-pagination" aria-label="Property result pages">
                                <?php for ($page = 1; $page <= $results['pages']; $page++): ?>
                                    <a class="<?= $page === $results['page'] ? 'active' : '' ?>" href="<?= e(listingQueryUrl(['page' => $page])) ?>"><?= e((string) $page) ?></a>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-results live-empty-results">
                            <i class="bi bi-search"></i>
                            <h3>No matching live listings</h3>
                            <p>Try another city, category or filter. No dummy properties are shown.</p>
                            <a href="<?= e(siteListingUrl()) ?>">Reset Filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileFilters" aria-labelledby="mobileFiltersLabel">
    <div class="offcanvas-header">
        <h2 id="mobileFiltersLabel">Filters</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="filter-panel">
            <?php $filterFormIndex = 'mobile'; require __DIR__ . '/partials/listing-filters.php'; ?>
        </div>
    </div>
</div>
<?php websiteFooter(['scripts' => ['listing-live.js']]); ?>
