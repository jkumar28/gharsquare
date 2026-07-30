<?php

declare(strict_types=1);

require_once BASE_PATH . '/includes/public_site.php';
require_once BASE_PATH . '/includes/mailer.php';

function publicEnquiryContact(array $input, array $user): array
{
    $name = trim((string) ($input['name'] ?? $user['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? $user['email'] ?? '')));
    $phone = preg_replace('/[^0-9+]/', '', trim((string) ($input['phone'] ?? $user['phone'] ?? ''))) ?? '';

    return [
        'name' => substr($name, 0, 100),
        'email' => substr($email, 0, 150),
        'phone' => substr($phone, 0, 20),
    ];
}

function publicEnquiryErrors(array $input, array $contact): array
{
    $errors = [];
    if ($contact['name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($contact['email'] === '' || !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($contact['phone'] === '' || strlen(preg_replace('/\D/', '', $contact['phone']) ?? '') < 8) {
        $errors[] = 'A valid phone number is required.';
    }
    if (empty($input['consent'])) {
        $errors[] = 'Please agree to be contacted about this property.';
    }
    if (trim((string) ($input['message'] ?? '')) === '') {
        $errors[] = 'Please enter an enquiry message.';
    }

    return $errors;
}

function createCanonicalPropertyEnquiry(array $input): array
{
    $user = publicUser();
    if (!$user) {
        return ['id' => 0, 'errors' => ['Please login to send an enquiry.'], 'property' => null];
    }

    $reference = trim((string) ($input['property_ref'] ?? ''));
    $property = $reference !== '' ? siteFindPropertyByReference($reference) : null;
    if (!$property) {
        return ['id' => 0, 'errors' => ['This property is unavailable or no longer active.'], 'property' => null];
    }

    $contact = publicEnquiryContact($input, $user);
    $errors = publicEnquiryErrors($input, $contact);
    if ($errors !== []) {
        return ['id' => 0, 'errors' => $errors, 'property' => $property];
    }

    $enquiryType = strtolower(trim((string) ($input['enquiry_type'] ?? 'callback')));
    $preferredContact = strtolower(trim((string) ($input['preferred_contact'] ?? 'call')));
    $enquiryType = in_array($enquiryType, ['callback', 'visit', 'buy', 'rent'], true) ? $enquiryType : 'callback';
    $preferredContact = in_array($preferredContact, ['call', 'email', 'whatsapp'], true) ? $preferredContact : 'call';
    $source = substr(trim((string) ($input['source'] ?? 'property_details')), 0, 80);
    $message = substr(trim((string) ($input['message'] ?? '')), 0, 2000);

    $duplicateStmt = db()->prepare(
        'SELECT id FROM property_enquiries
         WHERE user_id = :user_id AND property_id = :property_id
           AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
         ORDER BY id DESC LIMIT 1'
    );
    $duplicateStmt->execute([
        ':user_id' => (int) $user['id'],
        ':property_id' => (int) $property['id'],
    ]);
    $existingId = (int) $duplicateStmt->fetchColumn();
    if ($existingId > 0) {
        return ['id' => $existingId, 'errors' => [], 'property' => $property, 'duplicate' => true];
    }

    $metadata = json_encode([
        'details_url' => sitePropertyUrl($property),
        'page_url' => substr(publicAuthCleanWebsiteUrl((string) ($input['page_url'] ?? '')), 0, 600),
        'property_type' => $property['property_type_name'] ?? null,
    ], JSON_UNESCAPED_SLASHES);

    $stmt = db()->prepare(
        'INSERT INTO property_enquiries
            (user_id, property_ref, property_id, owner_user_id, contact_name, contact_email, contact_phone,
             listing_type, title, price_text, city, locality, category, source, enquiry_type,
             preferred_contact, consent_at, message, status, metadata, notification_status, created_at)
         VALUES
            (:user_id, :property_ref, :property_id, :owner_user_id, :contact_name, :contact_email, :contact_phone,
             :listing_type, :title, :price_text, :city, :locality, :category, :source, :enquiry_type,
             :preferred_contact, NOW(), :message, "new", :metadata, "pending", NOW())'
    );
    $stmt->execute([
        ':user_id' => (int) $user['id'],
        ':property_ref' => (string) $property['id'],
        ':property_id' => (int) $property['id'],
        ':owner_user_id' => (int) ($property['user_id'] ?? 0) ?: null,
        ':contact_name' => $contact['name'],
        ':contact_email' => $contact['email'],
        ':contact_phone' => $contact['phone'],
        ':listing_type' => substr((string) ($property['listing_type_name'] ?? ''), 0, 80),
        ':title' => substr((string) ($property['title'] ?? ''), 0, 180),
        ':price_text' => substr((string) ($property['price_label'] ?? ''), 0, 80),
        ':city' => substr((string) ($property['city_name'] ?? ''), 0, 120),
        ':locality' => substr((string) ($property['locality_name'] ?? ''), 0, 120),
        ':category' => substr((string) ($property['property_type_name'] ?? ''), 0, 120),
        ':source' => $source,
        ':enquiry_type' => $enquiryType,
        ':preferred_contact' => $preferredContact,
        ':message' => $message,
        ':metadata' => $metadata,
    ]);
    $enquiryId = (int) db()->lastInsertId();

    recordUserActivity('property_enquiry', [
        'entity_type' => 'property',
        'entity_id' => (string) $property['id'],
        'listing_type' => (string) ($property['public_type'] ?? ''),
        'city' => (string) ($property['city_name'] ?? ''),
        'page_url' => $input['page_url'] ?? sitePropertyUrl($property),
        'page_title' => (string) ($property['title'] ?? ''),
        'metadata' => ['source' => $source, 'enquiry_id' => $enquiryId],
    ]);

    return ['id' => $enquiryId, 'errors' => [], 'property' => $property, 'duplicate' => false];
}

function propertyEnquiryOwner(int $userId): ?array
{
    if ($userId <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = :id AND status = "active" LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $owner = $stmt->fetch();

    return $owner ?: null;
}

function notifyPropertyEnquiry(int $enquiryId): array
{
    $stmt = db()->prepare('SELECT * FROM property_enquiries WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $enquiryId]);
    $enquiry = $stmt->fetch();
    if (!$enquiry) {
        return ['status' => 'failed', 'error' => 'Enquiry record not found.'];
    }

    $detailsUrl = siteWebsiteUrl('property-details?slug=');
    $propertyStmt = db()->prepare('SELECT slug FROM properties WHERE id = :id LIMIT 1');
    $propertyStmt->execute([':id' => (int) ($enquiry['property_id'] ?? 0)]);
    $slug = (string) $propertyStmt->fetchColumn();
    if ($slug !== '') {
        $detailsUrl .= rawurlencode($slug);
    }

    $safeMessage = nl2br(e((string) ($enquiry['message'] ?? '')));
    $contactRows = '<p><strong>Name:</strong> ' . e((string) $enquiry['contact_name']) . '<br>'
        . '<strong>Email:</strong> ' . e((string) $enquiry['contact_email']) . '<br>'
        . '<strong>Phone:</strong> ' . e((string) $enquiry['contact_phone']) . '<br>'
        . '<strong>Preference:</strong> ' . e(ucfirst((string) $enquiry['preferred_contact'])) . '</p>';
    $propertyRows = '<p><strong>Property:</strong> ' . e((string) $enquiry['title']) . '<br>'
        . '<strong>Location:</strong> ' . e(trim((string) $enquiry['locality'] . ', ' . (string) $enquiry['city'], ', ')) . '<br>'
        . '<strong>Request:</strong> ' . e(ucfirst((string) $enquiry['enquiry_type'])) . '</p>';

    $admin = appSendMail(
        ADMIN_ENQUIRY_EMAIL,
        'New property enquiry #' . $enquiryId,
        appMailTemplate('New property enquiry', $propertyRows . $contactRows . '<p><strong>Message:</strong><br>' . $safeMessage . '</p>', 'Open property', $detailsUrl)
    );

    $owner = propertyEnquiryOwner((int) ($enquiry['owner_user_id'] ?? 0));
    $ownerResult = ['sent' => false, 'mode' => MAIL_TRANSPORT, 'error' => ''];
    if ($owner && (int) $owner['id'] !== (int) $enquiry['user_id'] && filter_var($owner['email'], FILTER_VALIDATE_EMAIL)) {
        $ownerResult = appSendMail(
            (string) $owner['email'],
            'New enquiry for ' . (string) $enquiry['title'],
            appMailTemplate('A customer enquired about your property', $propertyRows . $contactRows . '<p><strong>Message:</strong><br>' . $safeMessage . '</p>', 'View property', $detailsUrl)
        );
    } elseif ($owner && (int) $owner['id'] !== (int) $enquiry['user_id']) {
        $ownerResult['error'] = 'Owner email unavailable.';
    }

    $customer = appSendMail(
        (string) $enquiry['contact_email'],
        'We received your GharSquare enquiry',
        appMailTemplate(
            'Your enquiry is recorded',
            '<p>Hello ' . e((string) $enquiry['contact_name']) . ',</p><p>We received your request for <strong>' . e((string) $enquiry['title']) . '</strong>. The assigned team or property owner will follow up using your preferred contact method.</p>',
            'View property',
            $detailsUrl
        )
    );

    $sentCount = (int) $admin['sent'] + (int) $ownerResult['sent'] + (int) $customer['sent'];
    $expectedCount = $owner && (int) $owner['id'] !== (int) $enquiry['user_id'] ? 3 : 2;
    $status = $sentCount === $expectedCount ? ($admin['mode'] === 'log' ? 'logged' : 'sent') : ($sentCount > 0 ? 'partial' : 'failed');
    $errors = array_filter([$admin['error'], $ownerResult['error'], $customer['error']]);

    $update = db()->prepare(
        'UPDATE property_enquiries SET notification_status = :status,
            admin_notified_at = :admin_notified_at,
            owner_notified_at = :owner_notified_at,
            customer_notified_at = :customer_notified_at,
            notification_error = :notification_error
         WHERE id = :id'
    );
    $update->execute([
        ':status' => $status,
        ':admin_notified_at' => $admin['sent'] ? date('Y-m-d H:i:s') : null,
        ':owner_notified_at' => $ownerResult['sent'] ? date('Y-m-d H:i:s') : null,
        ':customer_notified_at' => $customer['sent'] ? date('Y-m-d H:i:s') : null,
        ':notification_error' => $errors !== [] ? substr(implode(' | ', $errors), 0, 2000) : null,
        ':id' => $enquiryId,
    ]);

    return ['status' => $status, 'error' => $errors !== [] ? implode(' | ', $errors) : ''];
}
