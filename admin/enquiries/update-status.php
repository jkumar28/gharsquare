<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

requireAdminAuth();

if (!isPostRequest() || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Invalid or expired request.');
    redirect(ADMIN_URL . '/enquiries/index.php');
}

$enquiryId = (int) ($_POST['enquiry_id'] ?? 0);
$status = strtolower(trim((string) ($_POST['status'] ?? '')));
$allowedStatuses = ['new', 'contacted', 'closed', 'cancelled'];

if ($enquiryId <= 0 || !in_array($status, $allowedStatuses, true)) {
    setFlash('danger', 'Invalid enquiry status.');
    redirect(ADMIN_URL . '/enquiries/index.php');
}

$stmt = db()->prepare('UPDATE property_enquiries SET status = :status WHERE id = :id');
$stmt->execute([':status' => $status, ':id' => $enquiryId]);
setFlash('success', 'Enquiry status updated.');

redirect(ADMIN_URL . '/enquiries/index.php');
