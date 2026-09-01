<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$existingAmenity = $id > 0 ? findAmenity($id) : null;

if (!$existingAmenity) {
    setFlash('danger', 'Amenity not found.');
    redirect(ADMIN_URL . '/masters/amenities/index.php');
}

$categories = amenityCategories();
$pageTitle = 'Edit Amenity';
$errors = getFormErrors();
$amenity = [
    'id' => $existingAmenity['id'],
    'name' => old('name', (string) $existingAmenity['name']),
    'category' => old('category', (string) ($existingAmenity['category'] ?? '')),
    'icon' => old('icon', (string) ($existingAmenity['icon'] ?? '')),
    'applicable_categories' => old('applicable_categories', explode(',', (string) ($existingAmenity['applicable_categories'] ?? 'residential,commercial,land'))),
    'sort_order' => old('sort_order', (string) ($existingAmenity['sort_order'] ?? 0)),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/amenities/update.php';
$submitLabel = 'Update Amenity';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Feature Masters</p>
            <h3>Edit Amenity</h3>
            <p class="panel-copy mb-0">Keep amenity naming and categories consistent for better listing quality.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/amenities/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
