<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/amenities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/amenities/create.php');
}

$validation = validateAmenityInput($_POST);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/amenities/create.php');
}

try {
    createAmenity($validation['data']);
    clearOldInput();
    setFlash('success', 'Amenity created successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to save the amenity right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/amenities/create.php');
}

redirect(ADMIN_URL . '/masters/amenities/index.php');
