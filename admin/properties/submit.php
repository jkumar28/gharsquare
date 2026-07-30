<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/properties/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Your session token expired. Please refresh and try again.'], 419);
}

$draftId = (int) ($_POST['draft_id'] ?? 0);

try {
    $descriptionValidation = validatePropertyDescriptionInput($_POST);
    if ($descriptionValidation['errors'] !== []) {
        jsonResponse(['success' => false, 'message' => implode(' ', $descriptionValidation['errors'])], 422);
    }

    upsertDraftSection('property_basic', $draftId, [
        'description' => $descriptionValidation['data']['description'],
    ]);

    submitPropertyDraft($draftId);
    jsonResponse([
        'success' => true,
        'message' => 'Listing submitted for review.',
        'progress' => propertyProgressPayload($draftId),
        'redirect_url' => ADMIN_URL . '/properties/index.php',
    ]);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => $exception->getMessage()], 422);
}
