<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$existingCity = $id > 0 ? findCity($id) : null;

if (!$existingCity) {
    setFlash('danger', 'City not found.');
    redirect(ADMIN_URL . '/masters/cities/index.php');
}

$states = statesAll();
$pageTitle = 'Edit City';
$errors = getFormErrors();
$city = [
    'id' => $existingCity['id'],
    'state_id' => (int) old('state_id', (string) $existingCity['state_id']),
    'name' => old('name', (string) $existingCity['name']),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/cities/update.php';
$submitLabel = 'Update City';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Edit City</h3>
            <p class="panel-copy mb-0">Update city details while keeping the location hierarchy intact.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/cities/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
