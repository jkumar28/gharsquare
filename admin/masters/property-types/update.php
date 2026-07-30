<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/property-types/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingType = $id > 0 ? findPropertyType($id) : null;

if (!$existingType) {
    setFlash('danger', 'Property type not found.');
    redirect(ADMIN_URL . '/masters/property-types/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/property-types/edit.php?id=' . $id);
}

$validation = validatePropertyTypeInput($_POST, $id);
$data = $validation['data'];
$errors = $validation['errors'];

if ($errors !== []) {
    setOldInput($data);
    setFormErrors($errors);
    redirect(ADMIN_URL . '/masters/property-types/edit.php?id=' . $id);
}

try {
    updatePropertyType($id, $data);
    clearOldInput();
    setFlash('success', 'Property type updated successfully.');
} catch (Throwable $exception) {
    setOldInput($data);
    setFormErrors(['Unable to update the property type right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/property-types/edit.php?id=' . $id);
}

redirect(ADMIN_URL . '/masters/property-types/index.php');
