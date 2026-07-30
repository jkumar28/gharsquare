<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/localities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/localities/create.php');
}

$validation = validateLocalityInput($_POST);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/localities/create.php');
}

try {
    createLocality($validation['data']);
    clearOldInput();
    setFlash('success', 'Locality created successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to save the locality right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/localities/create.php');
}

redirect(ADMIN_URL . '/masters/localities/index.php');
