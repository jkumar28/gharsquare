<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/cities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/cities/create.php');
}

$validation = validateCityInput($_POST);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/cities/create.php');
}

try {
    createCity($validation['data']);
    clearOldInput();
    setFlash('success', 'City created successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to save the city right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/cities/create.php');
}

redirect(ADMIN_URL . '/masters/cities/index.php');
