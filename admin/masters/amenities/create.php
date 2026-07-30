<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$categories = amenityCategories();
$pageTitle = 'Add Amenity';
$errors = getFormErrors();
$amenity = [
    'name' => old('name'),
    'category' => old('category'),
    'icon' => old('icon'),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/amenities/store.php';
$submitLabel = 'Save Amenity';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Feature Masters</p>
            <h3>Add Amenity</h3>
            <p class="panel-copy mb-0">Create a reusable facility or lifestyle feature for listings.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/amenities/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
