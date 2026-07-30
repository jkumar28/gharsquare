<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/users/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/users/create.php');
}

$validation = validateUserInput($_POST, true);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/users/create.php');
}

try {
    createUser($validation['data']);
    clearOldInput();
    setFlash('success', 'User created successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to save the user right now. Please try again.']);
    redirect(ADMIN_URL . '/users/create.php');
}

redirect(ADMIN_URL . '/users/index.php');
