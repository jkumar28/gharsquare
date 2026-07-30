<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$existingListingType = $id > 0 ? findListingType($id) : null;

if (!$existingListingType) {
    setFlash('danger', 'Listing type not found.');
    redirect(ADMIN_URL . '/masters/listing-types/index.php');
}

$pageTitle = 'Edit Listing Type';
$errors = getFormErrors();
$listingType = [
    'id' => $existingListingType['id'],
    'name' => old('name', (string) $existingListingType['name']),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/listing-types/update.php';
$submitLabel = 'Update Listing Type';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Listing Masters</p>
            <h3>Edit Listing Type</h3>
            <p class="panel-copy mb-0">Keep your property deal types consistent across forms and filters.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/listing-types/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
