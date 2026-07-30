<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/includes/auth.php';

if (!isPostRequest()) {
    redirect(ADMIN_URL . '/auth/login.php');
}

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

setOldInput(['login' => $login]);

if ($login === '' || $password === '') {
    setFlash('danger', 'Email/phone and password are required.');
    redirect(ADMIN_URL . '/auth/login.php');
}

if (!attemptAdminLogin($login, $password)) {
    setFlash('danger', 'Invalid admin credentials.');
    redirect(ADMIN_URL . '/auth/login.php');
}

clearOldInput();
setFlash('success', 'Welcome back to the admin dashboard.');
redirect(ADMIN_URL . '/index.php');
