<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/countries/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingCountry = $id > 0 ? findCountry($id) : null;

if (!$existingCountry) {
    setFlash('danger', 'Country not found.');
    redirect(ADMIN_URL . '/masters/countries/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/countries/edit.php?id=' . $id);
}

$validation = validateCountryInput($_POST, $id);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/countries/edit.php?id=' . $id);
}

try {
    updateCountry($id, $validation['data']);
    clearOldInput();
    setFlash('success', 'Country updated successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to update the country right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/countries/edit.php?id=' . $id);
}

redirect(ADMIN_URL . '/masters/countries/index.php');
