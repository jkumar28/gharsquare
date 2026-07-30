<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/properties/index.php');
}

$draftId = (int) ($_POST['draft_id'] ?? 0);
$draft = $draftId > 0 ? findPropertyDraftListRow($draftId) : null;

if (!$draft) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'Property draft not found.'], 404);
    }

    setFlash('danger', 'Property draft not found.');
    redirect(ADMIN_URL . '/properties/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'Your session token expired. Please refresh and try again.'], 419);
    }

    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/properties/index.php');
}

if (!canDeletePropertyDraft($draft)) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'Active properties cannot be deleted from this list.'], 422);
    }

    setFlash('danger', 'Active properties cannot be deleted from this list.');
    redirect(ADMIN_URL . '/properties/index.php');
}

try {
    deletePropertyDraft($draftId);

    if (isAjaxRequest()) {
        jsonResponse([
            'success' => true,
            'message' => 'Property draft deleted successfully.',
            'action' => 'remove-row',
            'summary' => propertyDraftSummaryPayload(),
        ]);
    }

    setFlash('success', 'Property draft deleted successfully.');
} catch (Throwable $exception) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'Unable to delete property draft right now.'], 500);
    }

    setFlash('danger', 'Unable to delete property draft right now.');
}

redirect(ADMIN_URL . '/properties/index.php');
