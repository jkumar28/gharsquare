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

if (!canRejectPropertyDraft($draft)) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'This property cannot be rejected from its current state.'], 422);
    }

    setFlash('danger', 'This property cannot be rejected from its current state.');
    redirect($returnTo !== '' ? $returnTo : ADMIN_URL . '/properties/index.php');
}

$validation = validatePropertyRejectionInput($_POST);
if ($validation['errors'] !== []) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => implode(' ', $validation['errors'])], 422);
    }

    setFlash('danger', implode(' ', $validation['errors']));
    redirect($returnTo !== '' ? $returnTo : propertyReviewUrl($draftId, '#moderation'));
}

try {
    rejectPropertyDraft(
        $draftId,
        $validation['data']['rejected_reason'],
        $validation['data']['admin_note']
    );

    if (isAjaxRequest()) {
        $updatedDraft = findPropertyDraftListRow($draftId);
        jsonResponse([
            'success' => true,
            'message' => 'Property rejected with reason.',
            'action' => 'update-property-row',
            'summary' => propertyDraftSummaryPayload(),
            'row' => $updatedDraft ? [
                'status_html' => propertyDraftStatusHtml($updatedDraft),
                'actions_html' => propertyListActionsHtml($updatedDraft),
            ] : null,
        ]);
    }

    setFlash('success', 'Property rejected with reason.');
} catch (Throwable $exception) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => $exception->getMessage() ?: 'Unable to reject property right now.'], 422);
    }

    setFlash('danger', 'Unable to reject property right now.');
}

redirect($returnTo !== '' ? $returnTo : propertyReviewUrl($draftId, '#moderation'));
