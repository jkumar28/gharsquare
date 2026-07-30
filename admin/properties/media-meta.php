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
$mediaId = (int) ($_POST['media_id'] ?? 0);
$actionType = trim((string) ($_POST['action_type'] ?? ''));

try {
    if ($actionType === 'set_cover') {
        setPropertyMediaAsCover($draftId, $mediaId);
        $message = 'Cover photo updated successfully.';
    } elseif ($actionType === 'set_photo_type') {
        updatePropertyMediaTitle($draftId, $mediaId, trim((string) ($_POST['title'] ?? '')));
        $message = 'Photo type updated successfully.';
    } else {
        jsonResponse(['success' => false, 'message' => 'Invalid media action.'], 422);
    }

    $bundle = getPropertyDraftBundle($draftId);
    saveDraftProgress($draftId, $bundle, 'media');

    jsonResponse([
        'success' => true,
        'message' => $message,
        'grid_html' => propertyMediaGridHtml($bundle['media']),
        'progress' => propertyProgressPayload($draftId),
    ]);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Unable to update media right now.'], 500);
}
