<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/cities/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingCity = $id > 0 ? findCity($id) : null;

if (!$existingCity) {
    setFlash('danger', 'City not found.');
    redirect(ADMIN_URL . '/masters/cities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/cities/edit.php?id=' . $id);
}

$validation = validateCityInput($_POST, $id);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/cities/edit.php?id=' . $id);
}

try {
    updateCity($id, $validation['data']);
    clearOldInput();
    setFlash('success', 'City updated successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to update the city right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/cities/edit.php?id=' . $id);
}

redirect(ADMIN_URL . '/masters/cities/index.php');
