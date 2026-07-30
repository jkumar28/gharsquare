<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

$draftId = (int) ($_GET['draft_id'] ?? 0);

if ($draftId <= 0 || !findPropertyDraft($draftId)) {
    jsonResponse(['success' => false, 'message' => 'Property draft not found.'], 404);
}

try {
    jsonResponse([
        'success' => true,
        'templates' => propertyDescriptionTemplates($draftId),
    ]);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Unable to generate description templates right now.'], 500);
}
