<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/listing-types/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || !findListingType($id)) {
    setFlash('danger', 'Listing type not found.');
    redirect(ADMIN_URL . '/masters/listing-types/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/listing-types/index.php');
}

try {
    deleteListingType($id);
    setFlash('success', 'Listing type deleted successfully.');
} catch (PDOException $exception) {
    setFlash('danger', deleteBlockedMessage('listing type'));
} catch (Throwable $exception) {
    setFlash('danger', 'Unable to delete the listing type right now.');
}

redirect(ADMIN_URL . '/masters/listing-types/index.php');
