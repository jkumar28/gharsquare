<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/listing-types/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingListingType = $id > 0 ? findListingType($id) : null;

if (!$existingListingType) {
    setFlash('danger', 'Listing type not found.');
    redirect(ADMIN_URL . '/masters/listing-types/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/listing-types/edit.php?id=' . $id);
}

$validation = validateListingTypeInput($_POST, $id);

if ($validation['errors'] !== []) {
    setOldInput($validation['data']);
    setFormErrors($validation['errors']);
    redirect(ADMIN_URL . '/masters/listing-types/edit.php?id=' . $id);
}

try {
    updateListingType($id, $validation['data']);
    clearOldInput();
    setFlash('success', 'Listing type updated successfully.');
} catch (Throwable $exception) {
    setOldInput($validation['data']);
    setFormErrors(['Unable to update the listing type right now. Please try again.']);
    redirect(ADMIN_URL . '/masters/listing-types/edit.php?id=' . $id);
}

redirect(ADMIN_URL . '/masters/listing-types/index.php');
