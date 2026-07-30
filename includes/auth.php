<?php

declare(strict_types=1);

require_once BASE_PATH . '/includes/functions.php';

function admin(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin']['id']);
}

function requireAdminAuth(): void
{
    if (!isAdminLoggedIn()) {
        setFlash('danger', 'Please login to continue.');
        redirect(ADMIN_URL . '/auth/login.php');
    }
}

function attemptAdminLogin(string $login, string $password): bool
{
    $sql = "SELECT id, name, email, phone, password, role, status
            FROM users
            WHERE (email = :login OR phone = :login)
              AND role = 'admin'
            LIMIT 1";

    $stmt = db()->prepare($sql);
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    if ($user['status'] !== 'active') {
        return false;
    }

    if (!password_verify($password, $user['password'])) {
        return false;
    }

    unset($user['password']);
    session_regenerate_id(true);
    $_SESSION['admin'] = $user;

    return true;
}

function logoutAdmin(): void
{
    unset($_SESSION['admin']);
    session_regenerate_id(true);
}
