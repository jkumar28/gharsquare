<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/amenities/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingAmenity = $id > 0 ? findAmenity($id) : null;

if (!$existingAmenity) {
    setFlash('danger', 'Amenity not found.');
    redirect(ADMIN_URL . '/masters/amenities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/amenities/edit.php?id=' . $id);
}

$validation = validateAmenityInput($_POST, $id);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/amenities/edit.php?id=' . $id);
}

try {
    updateAmenity($id, $validation['data']);
    clearOldInput();
    setFlash('success', 'Amenity updated successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to update the amenity right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/amenities/edit.php?id=' . $id);
}

redirect(ADMIN_URL . '/masters/amenities/index.php');
