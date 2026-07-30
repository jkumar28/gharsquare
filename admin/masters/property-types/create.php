<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Add Property Type';
$errors = getFormErrors();
$propertyType = [
    'name' => old('name'),
    'category' => old('category'),
];

clearOldInput();

$formAction = ADMIN_URL . '/masters/property-types/store.php';
$submitLabel = 'Save Property Type';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Master Setup</p>
            <h3>Add Property Type</h3>
            <p class="panel-copy mb-0">Define a new listing type that admins and users can select later.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/property-types/index.php">Back to List</a>
    </div>

    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
