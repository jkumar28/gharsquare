<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$existingType = $id > 0 ? findPropertyType($id) : null;

if (!$existingType) {
    setFlash('danger', 'Property type not found.');
    redirect(ADMIN_URL . '/masters/property-types/index.php');
}

$pageTitle = 'Edit Property Type';
$errors = getFormErrors();
$propertyType = [
    'id' => $existingType['id'],
    'name' => old('name', (string) $existingType['name']),
    'category' => old('category', (string) $existingType['category']),
];

clearOldInput();

$formAction = ADMIN_URL . '/masters/property-types/update.php';
$submitLabel = 'Update Property Type';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Master Setup</p>
            <h3>Edit Property Type</h3>
            <p class="panel-copy mb-0">Keep naming and categories clean so listing filters remain reliable.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/property-types/index.php">Back to List</a>
    </div>

    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
