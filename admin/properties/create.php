<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/property.php';

requireAdminAuth();

$draftId = createPropertyDraft();

setFlash('success', 'Property draft created. Start filling the steps.');
redirect(ADMIN_URL . '/properties/wizard.php?draft_id=' . $draftId);
