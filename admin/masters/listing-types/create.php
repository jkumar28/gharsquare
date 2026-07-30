<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Add Listing Type';
$errors = getFormErrors();
$listingType = ['name' => old('name')];
clearOldInput();
$formAction = ADMIN_URL . '/masters/listing-types/store.php';
$submitLabel = 'Save Listing Type';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Listing Masters</p>
            <h3>Add Listing Type</h3>
            <p class="panel-copy mb-0">Create the deal-type options your users will choose from.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/listing-types/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
