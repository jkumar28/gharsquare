<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/property-types/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/property-types/create.php');
}

$validation = validatePropertyTypeInput($_POST);
$data = $validation['data'];
$errors = $validation['errors'];

if ($errors !== []) {
    setOldInput($data);
    setFormErrors($errors);
    redirect(ADMIN_URL . '/masters/property-types/create.php');
}

try {
    createPropertyType($data);
    clearOldInput();
    setFlash('success', 'Property type created successfully.');
} catch (Throwable $exception) {
    setOldInput($data);
    setFormErrors(['Unable to save the property type right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/property-types/create.php');
}

redirect(ADMIN_URL . '/masters/property-types/index.php');
