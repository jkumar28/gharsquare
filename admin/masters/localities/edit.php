<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$existingLocality = $id > 0 ? findLocality($id) : null;

if (!$existingLocality) {
    setFlash('danger', 'Locality not found.');
    redirect(ADMIN_URL . '/masters/localities/index.php');
}

$cities = citiesAll();
$pageTitle = 'Edit Locality';
$errors = getFormErrors();
$locality = [
    'id' => $existingLocality['id'],
    'city_id' => (int) old('city_id', (string) $existingLocality['city_id']),
    'name' => old('name', (string) $existingLocality['name']),
    'pincode' => old('pincode', (string) ($existingLocality['pincode'] ?? '')),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/localities/update.php';
$submitLabel = 'Update Locality';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Edit Locality</h3>
            <p class="panel-copy mb-0">Fine-tune neighborhood names and pincodes for cleaner search pages.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/localities/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
