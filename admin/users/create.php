<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$pageTitle = 'Add User';
$errors = getFormErrors();
$user = [
    'name' => old('name'),
    'email' => old('email'),
    'phone' => old('phone'),
    'role' => old('role', 'owner'),
    'status' => old('status', 'active'),
    'email_verified' => old('email_verified'),
];
clearOldInput();
$formAction = ADMIN_URL . '/users/store.php';
$submitLabel = 'Save User';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">User Management</p>
            <h3>Add User</h3>
            <p class="panel-copy mb-0">Create an owner, agent, builder, or admin account directly from the dashboard.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/users/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
