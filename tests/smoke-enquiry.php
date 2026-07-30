<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/public_auth.php';
require BASE_PATH . '/includes/enquiries.php';

$user = db()->query(
    "SELECT id, name, email, phone, role, status, email_verified
     FROM users
     WHERE status = 'active'
       AND email IS NOT NULL
       AND id != (SELECT user_id FROM properties WHERE id = 7)
     LIMIT 1"
)->fetch();

if (!$user) {
    throw new RuntimeException('No active user with an email is available for the enquiry smoke test.');
}

$_SESSION['user'] = $user;
$mailOutboxStart = (int) db()->query('SELECT COALESCE(MAX(id), 0) FROM mail_outbox')->fetchColumn();
$activityStart = (int) db()->query('SELECT COALESCE(MAX(id), 0) FROM user_activity_logs')->fetchColumn();
$firstViewRecorded = recordPublicPropertyView('7', ['source' => 'smoke_test']);
$duplicateViewRecorded = recordPublicPropertyView('7', ['source' => 'smoke_test']);

if (!$firstViewRecorded || $duplicateViewRecorded) {
    throw new RuntimeException('Property view deduplication did not behave correctly.');
}

$result = createCanonicalPropertyEnquiry([
    'property_ref' => '7',
    'name' => $user['name'],
    'email' => $user['email'],
    'phone' => $user['phone'] ?: '9999999999',
    'enquiry_type' => 'visit',
    'preferred_contact' => 'email',
    'message' => 'Automated implementation verification enquiry.',
    'consent' => true,
    'source' => 'implementation_test',
]);
$enquiryId = (int) ($result['id'] ?? 0);

if ($enquiryId <= 0) {
    throw new RuntimeException(implode(' ', $result['errors'] ?? ['Unable to create enquiry.']));
}

try {
    $notification = notifyPropertyEnquiry($enquiryId);
    $stmt = db()->prepare(
        'SELECT property_id, owner_user_id, contact_email, consent_at, notification_status
         FROM property_enquiries WHERE id = :id'
    );
    $stmt->execute([':id' => $enquiryId]);
    $row = $stmt->fetch();

    if (!$row || (int) $row['property_id'] !== 7 || $row['consent_at'] === null) {
        throw new RuntimeException('Canonical enquiry fields were not saved correctly.');
    }
    if (!in_array($notification['status'], ['logged', 'sent', 'partial'], true)) {
        throw new RuntimeException('Enquiry notification was not accepted by the configured mail transport.');
    }

    $ownerStmt = db()->prepare(
        'SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.email_verified
         FROM properties p
         INNER JOIN users u ON u.id = p.user_id
         WHERE p.id = 7'
    );
    $ownerStmt->execute();
    $owner = $ownerStmt->fetch();
    $ownerMailStmt = db()->prepare(
        'SELECT COUNT(*) FROM mail_outbox
         WHERE id > :starting_id AND recipient = :recipient'
    );
    $ownerMailStmt->execute([
        ':starting_id' => $mailOutboxStart,
        ':recipient' => (string) $owner['email'],
    ]);

    if ((int) $ownerMailStmt->fetchColumn() < 1) {
        throw new RuntimeException('The property lister did not receive an enquiry email.');
    }

    $_SESSION['user'] = $owner;
    if (recordPublicPropertyView('7', ['source' => 'owner_smoke_test'])) {
        throw new RuntimeException('A property owner view was incorrectly counted.');
    }

    $receivedLeads = publicOwnerEnquiries(20);
    $ownerDrafts = publicUserPropertyDrafts(20);
    $matchingLead = array_filter($receivedLeads, static fn (array $lead): bool => (int) $lead['id'] === $enquiryId);
    $matchingDraft = array_filter($ownerDrafts, static fn (array $draft): bool => (int) ($draft['property_id'] ?? 0) === 7);

    if ($matchingLead === [] || $matchingDraft === []) {
        throw new RuntimeException('The enquiry was not available in the property owner dashboard.');
    }

    echo 'Enquiry smoke test passed with notification status: ' . $notification['status'] . PHP_EOL;
} finally {
    db()->prepare('DELETE FROM property_enquiries WHERE id = :id')->execute([':id' => $enquiryId]);
    db()->prepare('DELETE FROM mail_outbox WHERE id > :starting_id')->execute([':starting_id' => $mailOutboxStart]);
    db()->prepare('DELETE FROM user_activity_logs WHERE id > :starting_id')->execute([':starting_id' => $activityStart]);
}
