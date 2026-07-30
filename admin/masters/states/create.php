<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$countries = countryOptions();
$pageTitle = 'Add State';
$errors = getFormErrors();
$state = [
    'country_id' => (int) old('country_id'),
    'name' => old('name'),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/states/store.php';
$submitLabel = 'Save State';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Add State</h3>
            <p class="panel-copy mb-0">Create a state or province under the correct country.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/states/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
