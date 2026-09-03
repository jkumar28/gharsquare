<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$cities = siteHomepageCities(30);
$selectedCity = websiteSelectedCity($cities);
$featured = siteFeaturedProperties(8);
$stats = siteHomepageStats();
$localities = sitePopularLocalities($selectedCity, 6);
$heroImages = array_values(array_filter(array_map(
    static fn (array $property): string => (string) ($property['primary_image'] ?? ''),
    $featured
)));

websiteHeader(
    'GharSquare - Live Properties, PG and Land',
    'Browse live homes, rental properties, PG accommodation, commercial spaces and land on GharSquare.',
    '',
    ['cities' => $cities, 'selected_city' => $selectedCity, 'swiper' => true]
);
?>
<main>
    <section class="hero-section" id="home">
        <div class="hero-shape"></div>
        <div class="container">
            <div class="row align-items-center min-vh-60">
                <div class="col-lg-8 hero-content">
                    <h1>Find live properties<?= $selectedCity !== '' ? ' in ' . e($selectedCity) : '' ?></h1>
                    <p>Homes, rentals, commercial spaces, PG rooms and land from active listings.</p>

                    <form class="search-panel" action="<?= e(siteWebsiteUrl('listing')) ?>" method="get">
                        <input type="hidden" name="type" value="buy" data-home-type>
                        <?php if ($selectedCity !== ''): ?>
                            <input type="hidden" name="city" value="<?= e($selectedCity) ?>">
                        <?php endif; ?>
                        <div class="search-tabs" role="tablist" aria-label="Property type">
                            <button class="tab-btn active" type="button" data-type="buy">Buy</button>
                            <button class="tab-btn" type="button" data-type="rent">Rent</button>
                            <button class="tab-btn" type="button" data-type="commercial">Commercial</button>
                            <button class="tab-btn" type="button" data-type="pg">PG/Co-living</button>
                            <button class="tab-btn" type="button" data-type="plots">Plots</button>
                        </div>
                        <div class="search-bar">
                            <i class="bi bi-search"></i>
                            <input name="q" type="search" placeholder="Search locality, landmark or property type">
                            <button type="submit">Search</button>
                        </div>
                    </form>

                    <?php if ($localities !== []): ?>
                        <div class="locality-row">
                            <span class="locality-label">Popular Localities</span>
                            <div class="locality-chip-list">
                                <?php foreach ($localities as $locality): ?>
                                    <a class="locality-chip" href="<?= e(siteListingUrl([
                                        'city' => $selectedCity,
                                        'q' => $locality['name'],
                                    ])) ?>">
                                        <?= e((string) $locality['name']) ?>
                                        <span><?= e((string) $locality['property_count']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="owner-box">
                        Own a property? <a href="<?= e(siteWebsiteUrl('post-property')) ?>">List it for free</a>
                    </div>
                </div>

                <?php if ($heroImages !== []): ?>
                    <div class="col-lg-4 d-none d-lg-block">
                        <div class="hero-slider-box">
                            <div class="swiper heroSwiper">
                                <div class="swiper-wrapper">
                                    <?php foreach (array_slice($heroImages, 0, 5) as $image): ?>
                                        <div class="swiper-slide hero-slide">
                                            <img src="<?= e($image) ?>" alt="Live property">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="mobile-home-categories" aria-label="Explore property categories">
        <div class="container">
            <div class="mobile-section-title"><h2>What are you looking for?</h2></div>
            <div class="mobile-category-strip">
                <?php
                $mobileCategories = [
                    ['type' => 'buy', 'icon' => 'bi-buildings', 'label' => 'Buy'],
                    ['type' => 'rent', 'icon' => 'bi-key', 'label' => 'Rent'],
                    ['type' => 'commercial', 'icon' => 'bi-shop', 'label' => 'Commercial'],
                    ['type' => 'pg', 'icon' => 'bi-people', 'label' => 'PG'],
                    ['type' => 'plots', 'icon' => 'bi-map', 'label' => 'Plots'],
                ];
                ?>
                <?php foreach ($mobileCategories as $category): ?>
                    <a href="<?= e(siteListingUrl(['type' => $category['type'], 'city' => $selectedCity])) ?>">
                        <span><i class="bi <?= e($category['icon']) ?>"></i></span>
                        <strong><?= e($category['label']) ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="pt-3 pb-5 content-section home-latest-section" id="properties">
        <div class="container">
            <div class="section-heading">
                <div>
                    <h2>Latest live properties</h2>
                    <p><?= e((string) $stats['active_properties']) ?> active listings across every property category</p>
                </div>
                <a class="view-btn" href="<?= e(siteListingUrl()) ?>">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <?php if ($featured !== []): ?>
                <div class="swiper propertySwiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($featured as $property): ?>
                            <div class="swiper-slide"><?= sitePropertyCard($property) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            <?php else: ?>
                <div class="live-empty-state">
                    <i class="bi bi-house-check"></i>
                    <h3>No live properties yet</h3>
                    <p>Approved listings will appear here automatically.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="py-5 category-section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <h2>Explore active listings</h2>
                    <p>Choose the property journey that matches your need.</p>
                </div>
            </div>
            <div class="row g-3">
                <?php
                $categories = [
                    ['type' => 'buy', 'icon' => 'bi-building', 'title' => 'Properties for Sale', 'copy' => 'Homes and investment properties'],
                    ['type' => 'rent', 'icon' => 'bi-key', 'title' => 'Rental Homes', 'copy' => 'Available rental properties'],
                    ['type' => 'plots', 'icon' => 'bi-map', 'title' => 'Land and Plots', 'copy' => 'Residential and commercial land'],
                    ['type' => 'commercial', 'icon' => 'bi-shop', 'title' => 'Commercial', 'copy' => 'Office, retail and storage'],
                ];
                ?>
                <?php foreach ($categories as $category): ?>
                    <div class="col-6 col-lg-3">
                        <a class="category-card" href="<?= e(siteListingUrl([
                            'type' => $category['type'],
                            'city' => $selectedCity,
                        ])) ?>">
                            <i class="bi <?= e($category['icon']) ?>"></i>
                            <h3><?= e($category['title']) ?></h3>
                            <p><?= e($category['copy']) ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 premium-features" id="services">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-5">
                    <h2>Property discovery with less noise</h2>
                    <p>Only active listings appear publicly. Search by real locations, save useful options and send an enquiry without exposing an owner’s private contact details.</p>
                    <a class="btn purple-btn" href="<?= e(siteListingUrl()) ?>">Start Exploring</a>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-6"><div class="feature-card"><i class="bi bi-patch-check-fill"></i><h3>Live Listings</h3><p>Admin-approved active properties.</p></div></div>
                        <div class="col-6"><div class="feature-card"><i class="bi bi-sliders"></i><h3>Useful Filters</h3><p>Search by city, type, price and area.</p></div></div>
                        <div class="col-6"><div class="feature-card"><i class="bi bi-shield-lock"></i><h3>Contact Privacy</h3><p>Owner details remain protected.</p></div></div>
                        <div class="col-6"><div class="feature-card"><i class="bi bi-send-check"></i><h3>Tracked Enquiries</h3><p>Every request reaches the lead inbox.</p></div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="quick-stats">
        <div class="container">
            <div class="row g-3 justify-content-center">
                <div class="col-6 col-md-3"><div class="stat-card"><h3><?= e((string) $stats['active_properties']) ?></h3><p>Live Properties</p></div></div>
                <div class="col-6 col-md-3"><div class="stat-card"><h3><?= e((string) $stats['cities']) ?></h3><p>Active Cities</p></div></div>
                <div class="col-6 col-md-3"><div class="stat-card"><h3><?= e((string) $stats['localities']) ?></h3><p>Active Localities</p></div></div>
            </div>
        </div>
    </section>
</main>
<?php websiteFooter(['swiper' => true, 'scripts' => ['home-live.js']]); ?>
