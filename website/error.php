<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$code = (int) ($_GET['code'] ?? 404);
$errors = [
    403 => [
        'title' => 'Access denied',
        'heading' => 'This area is private',
        'message' => 'The requested resource is protected and cannot be accessed from the public website.',
        'icon' => 'bi-shield-lock',
    ],
    404 => [
        'title' => 'Page not found',
        'heading' => 'We could not find that page',
        'message' => 'The link may be outdated, or the property or page may have moved.',
        'icon' => 'bi-house-x',
    ],
];

if (!isset($errors[$code])) {
    $code = 404;
}

$error = $errors[$code];
http_response_code($code);

websiteHeader(
    $error['title'] . ' - GharSquare',
    $error['message'],
    'details-page',
    [
        'canonical' => siteWebsiteUrl(),
        'robots' => 'noindex,follow',
    ]
);
?>
<main class="details-main">
    <section class="details-not-found">
        <i class="bi <?= e($error['icon']) ?>"></i>
        <p class="eyebrow">Error <?= e((string) $code) ?></p>
        <h1><?= e($error['heading']) ?></h1>
        <p><?= e($error['message']) ?></p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= e(siteWebsiteUrl()) ?>">Return home</a>
            <?php if ($code === 404): ?>
                <a href="<?= e(siteListingUrl()) ?>">Browse properties</a>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php websiteFooter(); ?>
