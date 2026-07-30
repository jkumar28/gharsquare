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
$returnTo = trim((string) ($_POST['return_to'] ?? ''));
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
    redirect($returnTo !== '' ? $returnTo : ADMIN_URL . '/properties/index.php');
}

try {
    approvePropertyDraft($draftId);
    $updatedDraft = findPropertyDraftListRow($draftId);

    if (isAjaxRequest() && $updatedDraft) {
        jsonResponse([
            'success' => true,
            'message' => 'Property approved successfully.',
            'action' => 'update-property-row',
            'summary' => propertyDraftSummaryPayload(),
            'row' => [
                'status_html' => propertyDraftStatusHtml($updatedDraft),
                'actions_html' => propertyListActionsHtml($updatedDraft),
            ],
        ]);
    }

    setFlash('success', 'Property approved successfully.');
} catch (Throwable $exception) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => $exception->getMessage() ?: 'Unable to approve property right now.'], 422);
    }

    setFlash('danger', 'Unable to approve property right now.');
}

redirect($returnTo !== '' ? $returnTo : ADMIN_URL . '/properties/index.php');
