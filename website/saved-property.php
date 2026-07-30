<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/public_site.php';

header('Content-Type: application/json; charset=utf-8');

function savedPropertyInput(): array
{
    $raw = file_get_contents('php://input');
    $input = $raw !== false && trim($raw) !== '' ? json_decode($raw, true) : null;

    return is_array($input) ? $input : $_POST;
}

function savedPropertyResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    savedPropertyResponse([
        'success' => true,
        'logged_in' => isPublicUserLoggedIn(),
        'saved_ids' => publicUserSavedPropertyRefs(),
        'properties' => publicUserSavedProperties(100),
    ]);
}

if (!isPostRequest()) {
    savedPropertyResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$payload = savedPropertyInput();
$pageUrl = (string) ($payload['page_url'] ?? publicAuthCurrentUrl());
$csrf = (string) ($payload['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!verifyCsrfToken($csrf)) {
    savedPropertyResponse(['success' => false, 'message' => 'Your session token expired. Refresh the page and try again.'], 403);
}

if (!isPublicUserLoggedIn()) {
    savedPropertyResponse([
        'success' => false,
        'logged_in' => false,
        'login_required' => true,
        'login_url' => publicAuthLoginUrl($pageUrl),
        'message' => 'Please login to save properties.',
    ], 401);
}

$propertyRef = trim((string) ($payload['property_ref'] ?? $payload['property_id'] ?? $payload['entity_id'] ?? ''));

if ($propertyRef === '') {
    savedPropertyResponse(['success' => false, 'message' => 'Property reference is required.'], 422);
}

$action = strtolower(trim((string) ($payload['action'] ?? 'toggle')));
$saved = publicSavedPropertyExists($propertyRef);

if ($action === 'toggle') {
    $action = $saved ? 'unsave' : 'save';
}

if ($action === 'save') {
    $property = siteFindPropertyByReference($propertyRef);
    if (!$property) {
        savedPropertyResponse(['success' => false, 'message' => 'This property is unavailable or no longer active.'], 422);
    }
    $payload = array_merge($payload, [
        'property_ref' => (string) $property['id'],
        'listing_type' => (string) $property['listing_type_name'],
        'title' => (string) $property['title'],
        'price_text' => (string) $property['price_label'],
        'city' => (string) $property['city_name'],
        'locality' => (string) $property['locality_name'],
        'category' => (string) $property['property_type_name'],
        'image_url' => (string) $property['primary_image'],
        'details_url' => sitePropertyUrl($property),
        'metadata' => ['property_id' => (int) $property['id']],
    ]);

    if (!savePublicProperty($payload)) {
        savedPropertyResponse(['success' => false, 'message' => 'Unable to save this property.'], 500);
    }

    $saved = true;
} elseif ($action === 'unsave' || $action === 'remove') {
    if (!removePublicSavedProperty($propertyRef, $payload)) {
        savedPropertyResponse(['success' => false, 'message' => 'Unable to remove this property.'], 500);
    }

    $saved = false;
} else {
    savedPropertyResponse(['success' => false, 'message' => 'Unsupported save action.'], 422);
}

savedPropertyResponse([
    'success' => true,
    'logged_in' => true,
    'saved' => $saved,
    'saved_ids' => publicUserSavedPropertyRefs(),
    'saved_count' => publicUserSavedPropertyCount(),
]);
