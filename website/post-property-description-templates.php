<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/property.php';

header('Content-Type: application/json; charset=utf-8');

function postPropertyDescriptionResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (!isPublicUserLoggedIn()) {
    postPropertyDescriptionResponse([
        'success' => false,
        'login_required' => true,
        'login_url' => publicAuthLoginUrl(publicAuthCurrentUrl()),
    ], 401);
}

$draftId = (int) ($_GET['draft_id'] ?? 0);
$draft = $draftId > 0 ? findPropertyDraft($draftId) : null;
$user = publicUser();

if (!$draft || !$user || (int) ($draft['user_id'] ?? 0) !== (int) $user['id']) {
    postPropertyDescriptionResponse(['success' => false, 'message' => 'Draft not found.'], 404);
}

try {
    postPropertyDescriptionResponse([
        'success' => true,
        'templates' => propertyDescriptionTemplates($draftId),
    ]);
} catch (Throwable $exception) {
    postPropertyDescriptionResponse(['success' => false, 'message' => 'Unable to generate description templates right now.'], 500);
}
