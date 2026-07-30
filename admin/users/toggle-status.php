<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/users/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
$user = $id > 0 ? findUser($id) : null;

if (!$user) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'User not found.'], 404);
    }

    setFlash('danger', 'User not found.');
    redirect(ADMIN_URL . '/users/index.php');
}

if ((int) ($_SESSION['admin']['id'] ?? 0) === $id) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'You cannot change your own admin status.'], 422);
    }

    setFlash('danger', 'You cannot change your own admin status.');
    redirect(ADMIN_URL . '/users/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'Your session token expired. Please refresh and try again.'], 419);
    }

    setFlash('danger', 'Your session token expired. Please try again.');
    redirect(ADMIN_URL . '/users/index.php');
}

$nextStatus = nextUserStatus((string) $user['status']);

try {
    setUserStatus($id, $nextStatus);
    $updatedUser = findUser($id);

    if (isAjaxRequest() && $updatedUser) {
        jsonResponse([
            'success' => true,
            'message' => 'User status updated successfully.',
            'action' => 'update-user-row',
            'summary' => usersSummaryPayload(),
            'row' => [
                'status_html' => userStatusHtml((string) $updatedUser['status']),
                'verified_html' => userVerifiedHtml((int) $updatedUser['email_verified']),
                'actions_html' => userActionsHtml($updatedUser),
            ],
        ]);
    }

    setFlash('success', 'User status updated successfully.');
} catch (Throwable $exception) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'Unable to update user status right now.'], 500);
    }

    setFlash('danger', 'Unable to update user status right now.');
}

redirect(ADMIN_URL . '/users/index.php');
