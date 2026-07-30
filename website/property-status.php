<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';

if (!isPublicUserLoggedIn()) {
    redirect(publicAuthLoginUrl('account?view=properties'));
}

if (!isPostRequest() || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Unable to update the listing. Please refresh and try again.');
    redirect('account?view=properties');
}

$user = publicUser();
$propertyId = (int) ($_POST['property_id'] ?? 0);
$targetStatus = strtolower(trim((string) ($_POST['property_status'] ?? '')));
$reason = trim((string) ($_POST['reason'] ?? ''));
$allowedStatuses = [
    'active' => 'Live',
    'inactive' => 'Inactive',
    'booked' => 'Booked',
    'sold' => 'Sold',
    'rented' => 'Rented',
    'occupied' => 'Occupied',
    'deleted' => 'Deleted',
];
$manageableStatuses = ['active', 'inactive', 'booked', 'sold', 'rented', 'occupied'];

try {
    if (!isset($allowedStatuses[$targetStatus])) {
        throw new RuntimeException('Please select a valid listing status.');
    }

    $stmt = db()->prepare(
        'SELECT id, draft_id, user_id, status
         FROM properties
         WHERE id = :id AND user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([
        ':id' => $propertyId,
        ':user_id' => (int) ($user['id'] ?? 0),
    ]);
    $property = $stmt->fetch();

    if (!$property) {
        throw new RuntimeException('Property not found.');
    }

    if (!in_array((string) $property['status'], $manageableStatuses, true)) {
        throw new RuntimeException('This listing cannot be changed in its current review status.');
    }

    if ($targetStatus !== 'active' && stringLength($reason) < 5) {
        throw new RuntimeException('Please enter a short reason for removing the listing from live results.');
    }

    if (stringLength($reason) > 500) {
        throw new RuntimeException('Reason must be 500 characters or less.');
    }

    $sql = 'UPDATE properties
            SET status = :status,
                owner_status_reason = :reason,
                owner_status_updated_at = NOW()';

    if ($targetStatus === 'active') {
        $sql .= ', published_at = COALESCE(published_at, NOW())';
    }

    $sql .= ' WHERE id = :id AND user_id = :user_id';
    $update = db()->prepare($sql);
    $update->execute([
        ':status' => $targetStatus,
        ':reason' => $targetStatus === 'active' ? null : $reason,
        ':id' => $propertyId,
        ':user_id' => (int) ($user['id'] ?? 0),
    ]);

    recordUserActivity('property_status_update', [
        'entity_type' => 'property',
        'entity_id' => (string) $propertyId,
        'metadata' => [
            'status' => $targetStatus,
            'reason' => $targetStatus === 'active' ? null : $reason,
        ],
    ]);

    setFlash('success', 'Property status updated to ' . $allowedStatuses[$targetStatus] . '.');
} catch (Throwable $exception) {
    setFlash('danger', $exception->getMessage());
}

redirect('account?view=properties');
