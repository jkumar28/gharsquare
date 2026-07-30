<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/localities/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || !findLocality($id)) {
    setFlash('danger', 'Locality not found.');
    redirect(ADMIN_URL . '/masters/localities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/localities/index.php');
}

try {
    deleteLocality($id);
    setFlash('success', 'Locality deleted successfully.');
} catch (PDOException $exception) {
    setFlash('danger', deleteBlockedMessage('locality'));
} catch (Throwable $exception) {
    setFlash('danger', 'Unable to delete the locality right now.');
}

redirect(ADMIN_URL . '/masters/localities/index.php');
