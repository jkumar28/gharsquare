<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/includes/public_auth.php';

$_SERVER['REMOTE_ADDR'] = '127.0.0.250';
$identifier = 'security-smoke-' . bin2hex(random_bytes(6)) . '@example.test';

try {
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        authRateLimitHit('security_smoke', $identifier, 5, 900, 900);
    }

    if (!authRateLimitStatus('security_smoke', $identifier)['blocked']) {
        throw new RuntimeException('Authentication rate limiting did not block repeated attempts.');
    }

    authRateLimitClear('security_smoke', $identifier);

    if (authRateLimitStatus('security_smoke', $identifier)['blocked']) {
        throw new RuntimeException('Authentication rate limiting did not clear after success.');
    }

    $userEmail = 'security-smoke-' . bin2hex(random_bytes(6)) . '@example.test';
    $userStmt = db()->prepare(
        "INSERT INTO users (name, email, password, role, status, email_verified, created_at)
         VALUES ('Security Smoke', :email, :password, 'customer', 'active', 0, NOW())"
    );
    $userStmt->execute([
        ':email' => $userEmail,
        ':password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
    ]);
    $userId = (int) db()->lastInsertId();
    $otp = publicAuthCreateEmailOtp($userId);
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        publicAuthVerifyEmailOtp($userId, $otp === '000000' ? '111111' : '000000');
    }

    $stmt = db()->prepare(
        "SELECT is_used, attempt_count, max_attempts
         FROM user_otps
         WHERE user_id = :user_id AND type = 'email'
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch();

    if (!$row || (int) $row['is_used'] !== 1 || (int) $row['attempt_count'] < (int) $row['max_attempts']) {
        throw new RuntimeException('OTP attempt limiting did not invalidate the code.');
    }

    if (publicAuthVerifyEmailOtp($userId, $otp)) {
        throw new RuntimeException('An OTP remained usable after reaching its attempt limit.');
    }

    echo 'Authentication security smoke test passed.' . PHP_EOL;
} finally {
    authRateLimitClear('security_smoke', $identifier);

    if (isset($userId) && $userId > 0) {
        db()->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $userId]);
    }
}
