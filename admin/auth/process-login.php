<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/auth/login.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Security token expired. Please try again.');
    redirect(ADMIN_URL . '/auth/login.php');
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

setOldInput(['login' => $login]);

if ($login === '' || $password === '') {
    setFlash('danger', 'Email/phone and password are required.');
    redirect(ADMIN_URL . '/auth/login.php');
}

$rateLimit = authRateLimitStatus('admin_password_login', $login);
if ($rateLimit['blocked']) {
    setFlash('danger', 'Too many login attempts. Please wait before trying again.');
    redirect(ADMIN_URL . '/auth/login.php');
}

if (!attemptAdminLogin($login, $password)) {
    authRateLimitHit('admin_password_login', $login, 5, 900, 900);
    setFlash('danger', 'Invalid admin credentials.');
    redirect(ADMIN_URL . '/auth/login.php');
}

authRateLimitClear('admin_password_login', $login);
clearOldInput();
setFlash('success', 'Welcome back to the admin dashboard.');
redirect(ADMIN_URL . '/index.php');
