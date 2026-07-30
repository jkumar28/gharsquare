<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/countries/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || !findCountry($id)) {
    setFlash('danger', 'Country not found.');
    redirect(ADMIN_URL . '/masters/countries/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/countries/index.php');
}

try {
    deleteCountry($id);
    setFlash('success', 'Country deleted successfully.');
} catch (PDOException $exception) {
    setFlash('danger', deleteBlockedMessage('country'));
} catch (Throwable $exception) {
    setFlash('danger', 'Unable to delete the country right now.');
}

redirect(ADMIN_URL . '/masters/countries/index.php');
