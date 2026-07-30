<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';

if (!isPublicUserLoggedIn()) {
    redirect(publicAuthLoginUrl('account?view=leads'));
}

if (!isPostRequest() || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Invalid or expired request.');
    redirect('account?view=leads');
}

$user = publicUser();
$enquiryId = (int) ($_POST['enquiry_id'] ?? 0);
$status = strtolower(trim((string) ($_POST['status'] ?? '')));
$allowedStatuses = ['new', 'contacted', 'closed', 'cancelled'];

if ($enquiryId <= 0 || !in_array($status, $allowedStatuses, true)) {
    setFlash('danger', 'Invalid lead status.');
    redirect('account?view=leads');
}

$ownerStmt = db()->prepare(
    'SELECT pe.id, pe.property_id
     FROM property_enquiries pe
     INNER JOIN properties p ON p.id = pe.property_id
     WHERE pe.id = :enquiry_id AND p.user_id = :user_id
     LIMIT 1'
);
$ownerStmt->execute([
    ':enquiry_id' => $enquiryId,
    ':user_id' => (int) $user['id'],
]);
$lead = $ownerStmt->fetch();

if (!$lead) {
    setFlash('danger', 'You cannot update this property lead.');
    redirect('account?view=leads');
}

$updateStmt = db()->prepare('UPDATE property_enquiries SET status = :status WHERE id = :id');
$updateStmt->execute([':status' => $status, ':id' => $enquiryId]);

recordUserActivity('property_lead_status_update', [
    'entity_type' => 'property',
    'entity_id' => (string) $lead['property_id'],
    'metadata' => ['enquiry_id' => $enquiryId, 'status' => $status],
]);

setFlash('success', 'Lead status updated.');
redirect('account?view=leads');
