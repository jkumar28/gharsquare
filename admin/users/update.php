<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/users/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingUser = $id > 0 ? findUser($id, true) : null;

if (!$existingUser) {
    setFlash('danger', 'User not found.');
    redirect(ADMIN_URL . '/users/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/users/edit.php?id=' . $id);
}

$validation = validateUserInput($_POST, false, $id);

if ((int) ($_SESSION['admin']['id'] ?? 0) === $id && $validation['data']['status'] !== 'active') {
    $validation['errors'][] = 'You cannot block or delete your own admin account.';
}

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/users/edit.php?id=' . $id);
}

try {
    updateUser($id, $validation['data']);
    clearOldInput();
    setFlash('success', 'User updated successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to update the user right now. Please try again.']);
    redirect(ADMIN_URL . '/users/edit.php?id=' . $id);
}

redirect(ADMIN_URL . '/users/index.php');
