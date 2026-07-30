<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/cities/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || !findCity($id)) {
    setFlash('danger', 'City not found.');
    redirect(ADMIN_URL . '/masters/cities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/cities/index.php');
}

try {
    deleteCity($id);
    setFlash('success', 'City deleted successfully.');
} catch (PDOException $exception) {
    setFlash('danger', deleteBlockedMessage('city'));
} catch (Throwable $exception) {
    setFlash('danger', 'Unable to delete the city right now.');
}

redirect(ADMIN_URL . '/masters/cities/index.php');
