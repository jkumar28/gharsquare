<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$states = statesAll();
$pageTitle = 'Add City';
$errors = getFormErrors();
$city = [
    'state_id' => (int) old('state_id'),
    'name' => old('name'),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/cities/store.php';
$submitLabel = 'Save City';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Add City</h3>
            <p class="panel-copy mb-0">Create a city under the correct state for precise property targeting.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/cities/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
