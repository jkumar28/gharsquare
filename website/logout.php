<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/public_auth.php';

logoutPublicUser();
setFlash('success', 'You have been logged out.');
redirect('login');
