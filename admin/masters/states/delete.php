<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/states/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || !findState($id)) {
    setFlash('danger', 'State not found.');
    redirect(ADMIN_URL . '/masters/states/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/states/index.php');
}

try {
    deleteState($id);
    setFlash('success', 'State deleted successfully.');
} catch (PDOException $exception) {
    setFlash('danger', deleteBlockedMessage('state'));
} catch (Throwable $exception) {
    setFlash('danger', 'Unable to delete the state right now.');
}

redirect(ADMIN_URL . '/masters/states/index.php');
