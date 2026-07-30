<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$existingCountry = $id > 0 ? findCountry($id) : null;

if (!$existingCountry) {
    setFlash('danger', 'Country not found.');
    redirect(ADMIN_URL . '/masters/countries/index.php');
}

$pageTitle = 'Edit Country';
$errors = getFormErrors();
$country = [
    'id' => $existingCountry['id'],
    'name' => old('name', (string) $existingCountry['name']),
];
clearOldInput();
$formAction = ADMIN_URL . '/masters/countries/update.php';
$submitLabel = 'Update Country';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">Location Masters</p>
            <h3>Edit Country</h3>
            <p class="panel-copy mb-0">Update the country name without breaking the location hierarchy.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/masters/countries/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
