<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

logoutAdmin();
setFlash('success', 'You have been logged out.');
redirect(ADMIN_URL . '/auth/login.php');
