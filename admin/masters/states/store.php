<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/states/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/states/create.php');
}

$validation = validateStateInput($_POST);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/states/create.php');
}

try {
    createState($validation['data']);
    clearOldInput();
    setFlash('success', 'State created successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to save the state right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/states/create.php');
}

redirect(ADMIN_URL . '/masters/states/index.php');
