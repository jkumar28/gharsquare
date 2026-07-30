<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/localities/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingLocality = $id > 0 ? findLocality($id) : null;

if (!$existingLocality) {
    setFlash('danger', 'Locality not found.');
    redirect(ADMIN_URL . '/masters/localities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/localities/edit.php?id=' . $id);
}

$validation = validateLocalityInput($_POST, $id);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/localities/edit.php?id=' . $id);
}

try {
    updateLocality($id, $validation['data']);
    clearOldInput();
    setFlash('success', 'Locality updated successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to update the locality right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/localities/edit.php?id=' . $id);
}

redirect(ADMIN_URL . '/masters/localities/index.php');
