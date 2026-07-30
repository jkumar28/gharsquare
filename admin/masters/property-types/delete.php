<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/masters/property-types/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$existingType = $id > 0 ? findPropertyType($id) : null;

if (!$existingType) {
    setFlash('danger', 'Property type not found.');
    redirect(ADMIN_URL . '/masters/property-types/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/masters/property-types/index.php');
}

try {
    deletePropertyType($id);
    setFlash('success', 'Property type deleted successfully.');
} catch (PDOException $exception) {
    $sqlState = (string) $exception->getCode();
    $message = str_contains(strtolower($exception->getMessage()), 'foreign key')
        ? 'This property type is already used in property records, so it cannot be deleted.'
        : 'Unable to delete the property type right now.';

    if (str_starts_with($sqlState, '23')) {
        $message = 'This property type is already used in property records, so it cannot be deleted.';
    }

    setFlash('danger', $message);
} catch (Throwable $exception) {
    setFlash('danger', 'Unable to delete the property type right now.');
}

redirect(ADMIN_URL . '/masters/property-types/index.php');
