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

try {
    deletePropertyMediaRecord($draftId, $mediaId);
    $bundle = getPropertyDraftBundle($draftId);
    saveDraftProgress($draftId, $bundle, 'media');

    jsonResponse([
        'success' => true,
        'message' => 'Media removed successfully.',
        'action' => 'remove-media-card',
        'media_id' => $mediaId,
        'grid_html' => propertyMediaGridHtml($bundle['media']),
        'progress' => propertyProgressPayload($draftId),
    ]);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Unable to remove media right now.'], 500);
}
