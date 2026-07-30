<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/states/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingState = $id > 0 ? findState($id) : null;

if (!$existingState) {
    setFlash('danger', 'State not found.');
    redirect(ADMIN_URL . '/masters/states/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/states/edit.php?id=' . $id);
}

$validation = validateStateInput($_POST, $id);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/states/edit.php?id=' . $id);
}

try {
    updateState($id, $validation['data']);
    clearOldInput();
    setFlash('success', 'State updated successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to update the state right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/states/edit.php?id=' . $id);
}

redirect(ADMIN_URL . '/masters/states/index.php');
