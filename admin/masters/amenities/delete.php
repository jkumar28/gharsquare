<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/amenities/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || !findAmenity($id)) {
    setFlash('danger', 'Amenity not found.');
    redirect(ADMIN_URL . '/masters/amenities/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/amenities/index.php');
}

try {
    deleteAmenity($id);
    setFlash('success', 'Amenity deleted successfully.');
} catch (PDOException $exception) {
    setFlash('danger', deleteBlockedMessage('amenity'));
} catch (Throwable $exception) {
    setFlash('danger', 'Unable to delete the amenity right now.');
}

redirect(ADMIN_URL . '/masters/amenities/index.php');
