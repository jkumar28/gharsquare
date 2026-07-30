<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
$existingUser = $id > 0 ? findUser($id, true) : null;

if (!$existingUser) {
    setFlash('danger', 'User not found.');
    redirect(ADMIN_URL . '/users/index.php');
}

$pageTitle = 'Edit User';
$errors = getFormErrors();
$user = [
    'id' => $existingUser['id'],
    'name' => old('name', (string) $existingUser['name']),
    'email' => old('email', (string) ($existingUser['email'] ?? '')),
    'phone' => old('phone', (string) ($existingUser['phone'] ?? '')),
    'role' => old('role', (string) $existingUser['role']),
    'status' => old('status', (string) $existingUser['status']),
    'email_verified' => (int) old('email_verified', (string) $existingUser['email_verified']),
];
clearOldInput();
$formAction = ADMIN_URL . '/users/update.php';
$submitLabel = 'Update User';

require BASE_PATH . '/admin/includes/header.php';
?>
<section class="panel-card form-panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow mb-1">User Management</p>
            <h3>Edit User</h3>
            <p class="panel-copy mb-0">Update profile details, role, status, and login access for this account.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>/users/index.php">Back to List</a>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</section>
<?php require BASE_PATH . '/admin/includes/footer.php'; ?>
