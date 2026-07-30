<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$existingState = $id > 0 ? findState($id) : null;

if (!$existingState) {
    setFlash('danger', 'State not found.');
    redirect(ADMIN_URL . '/masters/states/index.php');
}

$countries = countryOptions();
$pageTitle = 'Edit State';
$errors = getFormErrors();
$state = [
    'id' => $existingState['id'],
    'country_id' => (int) old('country_id', (string) $existingState['country_id']),
    'name' => old('name', (string) $existingState['name']),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/states/update.php';
$submitLabel = 'Update State';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Edit State</h3>
            <p class="panel-copy mb-0">Keep the state mapped to the right country for location consistency.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/states/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
