<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/public_auth.php';
require_once BASE_PATH . '/includes/public_site.php';

function websiteAssetUrl(string $path): string
{
    $file = BASE_PATH . '/website/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '';
    $url = APP_URL . '/website/' . ltrim($path, '/');

    return $url . ($version !== '' ? '?v=' . rawurlencode($version) : '');
}

function websiteSelectedCity(array $cities): string
{
    $requested = trim((string) ($_GET['city'] ?? ''));

    foreach ($cities as $city) {
        if (strcasecmp((string) ($city['name'] ?? ''), $requested) === 0) {
            return (string) $city['name'];
        }
    }

    return (string) ($cities[0]['name'] ?? '');
}

function websiteCanonicalUrl(string $url): string
{
    $url = strtok(trim($url), '?') ?: '/';

    if (!str_starts_with($url, '/')) {
        return $url;
    }

    $appPath = rtrim((string) parse_url(APP_URL, PHP_URL_PATH), '/');
    $relativePath = $appPath !== '' && str_starts_with($url, $appPath)
        ? substr($url, strlen($appPath))
        : $url;

    return APP_URL . ($relativePath !== '' ? $relativePath : '/');
}

function websiteHeader(string $title, string $description, string $bodyClass = '', array $options = []): void
{
    $cities = $options['cities'] ?? siteHomepageCities(30);
    $selectedCity = (string) ($options['selected_city'] ?? websiteSelectedCity($cities));
    $canonical = websiteCanonicalUrl((string) ($options['canonical'] ?? publicAuthCurrentUrl()));
    $image = trim((string) ($options['image'] ?? ''));
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="app-url" content="<?= e(APP_URL) ?>">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="robots" content="<?= e((string) ($options['robots'] ?? 'index,follow,max-image-preview:large')) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:type" content="<?= e((string) ($options['og_type'] ?? 'website')) ?>">
    <meta property="og:site_name" content="<?= e(PUBLIC_SITE_NAME) ?>">
    <meta name="twitter:card" content="<?= $image !== '' ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <?php if ($image !== ''): ?>
        <meta property="og:image" content="<?= e($image) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php if (!empty($options['swiper'])): ?>
        <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?= e(websiteAssetUrl('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
<nav class="navbar navbar-expand-lg fixed-top premium-navbar">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand" href="<?= e(siteWebsiteUrl()) ?>" aria-label="GharSquare home">
            <span class="logo-icon"><i class="bi bi-house-fill"></i></span>
            Ghar<span>Square</span>
        </a>

        <?php if ($cities !== []): ?>
            <div class="header-location dropdown">
                <button class="location-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span id="headerCity"><?= e($selectedCity) ?></span>
                </button>
                <div class="dropdown-menu location-dropdown">
                    <input type="search" class="form-control" data-city-search placeholder="Search city..." aria-label="Search city">
                    <?php foreach ($cities as $city): ?>
                        <a class="dropdown-item city-option" href="<?= e(siteListingUrl(['city' => $city['name']])) ?>" data-city="<?= e((string) $city['name']) ?>">
                            <?= e((string) $city['name']) ?>
                            <small><?= e((string) $city['property_count']) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto gap-lg-4">
                <li><a class="nav-link" href="<?= e(siteListingUrl(['type' => 'buy', 'city' => $selectedCity])) ?>">Buyers</a></li>
                <li><a class="nav-link" href="<?= e(siteListingUrl(['type' => 'rent', 'city' => $selectedCity])) ?>">Tenants</a></li>
                <li><a class="nav-link" href="<?= e(siteListingUrl(['type' => 'commercial', 'city' => $selectedCity])) ?>">Commercial</a></li>
                <li><a class="nav-link" href="<?= e(siteListingUrl(['type' => 'pg', 'city' => $selectedCity])) ?>">PG</a></li>
                <li><a class="nav-link" href="<?= e(siteListingUrl(['type' => 'plots', 'city' => $selectedCity])) ?>">Land</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <a href="<?= e(siteWebsiteUrl('post-property')) ?>" class="post-btn">Post Property <small>Free</small></a>
                <a href="<?= e(siteWebsiteUrl('login')) ?>" class="btn btn-primary js-login-btn">
                    <i class="bi bi-person-circle"></i> Login
                </a>
            </div>
        </div>
    </div>
</nav>
    <?php
}

function websiteFooter(array $options = []): void
{
    ?>
<footer id="footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <h3>Ghar<span>Square</span></h3>
                <p>Live property discovery for buyers, tenants, owners and local real estate professionals.</p>
            </div>
            <div class="col-6 col-md-3">
                <h5>Explore</h5>
                <a href="<?= e(siteListingUrl(['type' => 'buy'])) ?>">Buy Property</a>
                <a href="<?= e(siteListingUrl(['type' => 'rent'])) ?>">Rent Property</a>
                <a href="<?= e(siteListingUrl(['type' => 'plots'])) ?>">Land and Plots</a>
            </div>
            <div class="col-6 col-md-4">
                <h5>Owners</h5>
                <a href="<?= e(siteWebsiteUrl('post-property')) ?>">Post Property</a>
                <a href="<?= e(siteWebsiteUrl('account?view=properties')) ?>">Manage Listings</a>
                <p><i class="bi bi-envelope"></i> <?= e(CONTACT_EMAIL) ?></p>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($options['swiper'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(websiteAssetUrl('assets/js/auth-ui.js')) ?>"></script>
<script src="<?= e(websiteAssetUrl('assets/js/public-site.js')) ?>"></script>
<?php foreach (($options['scripts'] ?? []) as $script): ?>
    <script src="<?= e(websiteAssetUrl('assets/js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}
