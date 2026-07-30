<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';
require_once BASE_PATH . '/includes/enquiries.php';

header('Content-Type: application/json; charset=utf-8');

function propertyEnquiryInput(): array
{
    $raw = file_get_contents('php://input');
    $input = $raw !== false && trim($raw) !== '' ? json_decode($raw, true) : null;

    return is_array($input) ? $input : $_POST;
}

function propertyEnquiryResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (!isPostRequest()) {
    propertyEnquiryResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$payload = propertyEnquiryInput();
$pageUrl = (string) ($payload['page_url'] ?? publicAuthCurrentUrl());
$csrf = (string) ($payload['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!verifyCsrfToken($csrf)) {
    propertyEnquiryResponse(['success' => false, 'message' => 'Your session token expired. Refresh the page and try again.'], 403);
}

if (!isPublicUserLoggedIn()) {
    propertyEnquiryResponse([
        'success' => false,
        'logged_in' => false,
        'login_required' => true,
        'login_url' => publicAuthLoginUrl($pageUrl),
        'message' => 'Please login to send an enquiry.',
    ], 401);
}

$result = createCanonicalPropertyEnquiry($payload);
$enquiryId = (int) ($result['id'] ?? 0);

if ($enquiryId <= 0) {
    propertyEnquiryResponse([
        'success' => false,
        'message' => implode(' ', $result['errors'] ?? ['Unable to create enquiry.']),
        'errors' => $result['errors'] ?? [],
    ], 422);
}

$notification = !empty($result['duplicate'])
    ? ['status' => 'already_sent', 'error' => '']
    : notifyPropertyEnquiry($enquiryId);

propertyEnquiryResponse([
    'success' => true,
    'logged_in' => true,
    'enquiry_id' => $enquiryId,
    'status' => 'new',
    'notification_status' => $notification['status'],
    'message' => !empty($result['duplicate'])
        ? 'Your recent enquiry for this property is already recorded.'
        : 'Your enquiry has been recorded. The assigned team or property owner will contact you shortly.',
]);
