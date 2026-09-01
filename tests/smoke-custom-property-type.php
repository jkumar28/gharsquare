<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/property.php';

$userId = (int) db()->query("SELECT id FROM users WHERE status = 'active' ORDER BY id LIMIT 1")->fetchColumn();
$listingTypeId = (int) db()->query('SELECT id FROM listing_types ORDER BY id LIMIT 1')->fetchColumn();
$otherTypeStmt = db()->query("SELECT id, name, category FROM property_types WHERE name IN ('Other Commercial Property', 'Other Land') ORDER BY id LIMIT 1");
$otherType = $otherTypeStmt->fetch();

if ($userId <= 0 || $listingTypeId <= 0 || !$otherType) {
    throw new RuntimeException('Required master data is missing for the custom property type test.');
}

$baseInput = [
    'user_id' => $userId,
    'listing_type_id' => $listingTypeId,
    'property_type_id' => (int) $otherType['id'],
    'title' => 'Custom property type smoke test',
    'posted_by' => 'owner',
];

$missingValidation = validatePropertyBasicInput($baseInput);
if (!in_array('Please enter the closest property type.', $missingValidation['errors'], true)) {
    throw new RuntimeException('Other property type was accepted without a custom name.');
}

$validValidation = validatePropertyBasicInput($baseInput + ['custom_property_type' => 'Banquet Hall']);
if (in_array('Please enter the closest property type.', $validValidation['errors'], true)) {
    throw new RuntimeException('A valid custom property type was rejected.');
}

$pdo = db();
$pdo->beginTransaction();

try {
    $draftStmt = $pdo->prepare('INSERT INTO property_drafts (user_id, current_step) VALUES (:user_id, 1)');
    $draftStmt->execute([':user_id' => $userId]);
    $draftId = (int) $pdo->lastInsertId();

    upsertDraftSection('property_basic', $draftId, [
        'property_type_id' => (int) $otherType['id'],
        'custom_property_type' => 'Banquet Hall',
        'listing_type_id' => $listingTypeId,
        'title' => 'Custom property type smoke test',
        'posted_by' => 'owner',
    ]);

    $basic = draftSectionRow('property_basic', $draftId) ?? [];
    if (
        (string) ($basic['custom_property_type'] ?? '') !== 'Banquet Hall'
        || propertyTypeDisplayName($basic, $otherType) !== 'Banquet Hall'
    ) {
        throw new RuntimeException('Custom property type was not stored or displayed correctly.');
    }

    echo 'Custom property type smoke test passed.' . PHP_EOL;
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
