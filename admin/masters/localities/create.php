<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$cities = citiesAll();
$pageTitle = 'Add Locality';
$errors = getFormErrors();
$locality = [
    'city_id' => (int) old('city_id'),
    'name' => old('name'),
    'pincode' => old('pincode'),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/localities/store.php';
$submitLabel = 'Save Locality';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Add Locality</h3>
            <p class="panel-copy mb-0">Create a neighborhood or area under the correct city.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/localities/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
