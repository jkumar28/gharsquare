<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';

if (!isPostRequest()) {
    jsonResponse(['success' => false, 'message' => 'POST required.'], 405);
}

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    $payload = $_POST;
}

$activityType = preg_replace('/[^a-z0-9_\\-]/i', '', (string) ($payload['activity_type'] ?? 'interaction'));

if ($activityType === '') {
    $activityType = 'interaction';
}

$metadata = $payload['metadata'] ?? [];

if (!is_array($metadata)) {
    $metadata = ['value' => (string) $metadata];
}

$recorded = false;

if ($activityType === 'property_view' && ($payload['entity_type'] ?? '') === 'property') {
    $recorded = recordPublicPropertyView((string) ($payload['entity_id'] ?? ''), [
        'page_url' => $payload['page_url'] ?? null,
        'source' => $metadata['source'] ?? 'property_details',
    ]);
} else {
    recordUserActivity($activityType, [
        'entity_type' => $payload['entity_type'] ?? null,
        'entity_id' => $payload['entity_id'] ?? null,
        'search_query' => $payload['search_query'] ?? null,
        'listing_type' => $payload['listing_type'] ?? null,
        'city' => $payload['city'] ?? null,
        'page_url' => $payload['page_url'] ?? null,
        'page_title' => $payload['page_title'] ?? null,
        'metadata' => $metadata,
    ]);
    $recorded = true;
}

jsonResponse([
    'success' => true,
    'logged_in' => isPublicUserLoggedIn(),
    'recorded' => $recorded,
]);
