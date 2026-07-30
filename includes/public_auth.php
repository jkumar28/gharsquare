<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/includes/functions.php';

const PUBLIC_AUTH_OTP_MINUTES = 10;

function publicUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function publicAuthCurrentUrl(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

    if (preg_match('/(?:^|\\/)(?:login|verify-otp|logout)(?:\\.php)?(?:[?#]|$)/', $uri)) {
        return APP_URL . '/website/';
    }

    return publicAuthCleanWebsiteUrl($uri);
}

function publicAuthCleanWebsiteUrl(string $url): string
{
    $url = preg_replace('~/website/index\.html(?=([?#]|$))~i', '/website/', $url) ?? $url;
    $url = preg_replace('~/website/([^/?#]+)\.(?:php|html)(?=([?#]|$))~i', '/website/$1', $url) ?? $url;

    return $url;
}

function publicAuthLoginUrl(?string $redirectTo = null): string
{
    $target = $redirectTo ?: publicAuthCurrentUrl();

    return APP_URL . '/website/login?redirect=' . rawurlencode(publicAuthCleanWebsiteUrl($target));
}

function publicAuthNormalizeRedirect(string $redirectTo): string
{
    $redirectTo = trim($redirectTo);

    if ($redirectTo === '') {
        return APP_URL . '/website/';
    }

    if (str_starts_with($redirectTo, APP_URL)) {
        return publicAuthCleanWebsiteUrl($redirectTo);
    }

    if (str_starts_with($redirectTo, '/')) {
        return publicAuthCleanWebsiteUrl($redirectTo);
    }

    if (preg_match('/^(?:[a-z][a-z0-9+.-]*:)?\\/\\//i', $redirectTo)) {
        return APP_URL . '/website/';
    }

    return publicAuthCleanWebsiteUrl($redirectTo);
}

function publicAuthRememberRedirect(?string $redirectTo): void
{
    if ($redirectTo === null || trim($redirectTo) === '') {
        return;
    }

    $_SESSION['auth_redirect_to'] = publicAuthNormalizeRedirect($redirectTo);
}

function isPublicUserLoggedIn(): bool
{
    return isset($_SESSION['user']['id']);
}

function loginPublicUser(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'role' => (string) ($user['role'] ?? 'owner'),
        'status' => (string) ($user['status'] ?? 'active'),
        'email_verified' => (int) ($user['email_verified'] ?? 0),
    ];

    $stmt = db()->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
    $stmt->execute([':id' => (int) $user['id']]);
    attachAnonymousActivityToUser((int) $user['id']);
    recordUserActivity('login', [
        'entity_type' => 'user',
        'entity_id' => (string) $user['id'],
        'metadata' => [
            'role' => (string) ($user['role'] ?? ''),
            'email_verified' => (int) ($user['email_verified'] ?? 0),
        ],
    ]);
    session_regenerate_id(true);
}

function logoutPublicUser(): void
{
    if (isset($_SESSION['user']['id'])) {
        recordUserActivity('logout', [
            'entity_type' => 'user',
            'entity_id' => (string) $_SESSION['user']['id'],
        ]);
    }

    unset($_SESSION['user'], $_SESSION['pending_email_verification']);
    session_regenerate_id(true);
}

function publicAuthRoles(): array
{
    return [
        'customer' => 'Customer / Buyer',
        'tenant' => 'Tenant',
        'owner' => 'Owner',
        'agent' => 'Agent',
        'builder' => 'Builder',
    ];
}

function findPublicUserByLogin(string $login): ?array
{
    $stmt = db()->prepare(
        "SELECT id, name, email, phone, password, role, status, email_verified
         FROM users
         WHERE (email = :login OR phone = :login)
           AND status != 'deleted'
         LIMIT 1"
    );
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function findPublicUserByEmail(string $email): ?array
{
    $stmt = db()->prepare(
        "SELECT id, name, email, phone, password, role, status, email_verified
         FROM users
         WHERE email = :email
           AND status != 'deleted'
         LIMIT 1"
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function validatePublicRegisterInput(array $input): array
{
    $roles = publicAuthRoles();
    $name = trim((string) ($input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $phone = trim((string) ($input['phone'] ?? ''));
    $role = trim((string) ($input['role'] ?? 'customer'));
    $password = (string) ($input['password'] ?? '');
    $confirmPassword = (string) ($input['confirm_password'] ?? '');
    $errors = [];

    if ($name === '') {
        $errors[] = 'Please enter your full name.';
    } elseif (stringLength($name) > 150) {
        $errors[] = 'Name must be 150 characters or fewer.';
    }

    if ($email === '') {
        $errors[] = 'Email is required for OTP verification.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (emailExists($email)) {
        $errors[] = 'This email address is already registered.';
    }

    if ($phone !== '') {
        if (!preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) {
            $errors[] = 'Phone number must be 6 to 20 characters and contain only digits, spaces, +, or -.';
        } elseif (phoneExists($phone)) {
            $errors[] = 'This phone number is already registered.';
        }
    }

    if (!array_key_exists($role, $roles)) {
        $errors[] = 'Please select a valid account type.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (stringLength($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    return [
        'data' => [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => $role,
        ],
        'errors' => $errors,
    ];
}

function createPublicUser(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO users (name, email, phone, password, role, status, email_verified, created_at)
         VALUES (:name, :email, :phone, :password, :role, :status, :email_verified, NOW())'
    );

    $stmt->execute([
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
        ':password' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
        ':role' => $data['role'],
        ':status' => 'active',
        ':email_verified' => 0,
    ]);

    return (int) db()->lastInsertId();
}

function publicAuthSetPendingVerification(int $userId, string $purpose): void
{
    $_SESSION['pending_email_verification'] = [
        'user_id' => $userId,
        'purpose' => $purpose,
        'created_at' => time(),
    ];
}

function publicAuthPendingVerification(): ?array
{
    $pending = $_SESSION['pending_email_verification'] ?? null;

    return is_array($pending) ? $pending : null;
}

function publicAuthCreateEmailOtp(int $userId): string
{
    authCleanupExpiredRecords();
    $otp = (string) random_int(100000, 999999);

    $stmt = db()->prepare(
        "UPDATE user_otps
         SET is_used = 1
         WHERE user_id = :user_id
           AND type = 'email'
           AND is_used = 0"
    );
    $stmt->execute([':user_id' => $userId]);

    $stmt = db()->prepare(
        "INSERT INTO user_otps (user_id, otp, type, expires_at, is_used, created_at)
         VALUES (:user_id, :otp, 'email', DATE_ADD(NOW(), INTERVAL " . PUBLIC_AUTH_OTP_MINUTES . " MINUTE), 0, NOW())"
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':otp' => $otp,
    ]);

    return $otp;
}

function publicAuthDeliverEmailOtp(array $user, string $otp, string $purpose): bool
{
    require_once BASE_PATH . '/includes/mailer.php';

    $email = trim((string) ($user['email'] ?? ''));

    if ($email === '') {
        return false;
    }

    $subject = 'Your GharSquare verification OTP';
    $purposeLabel = $purpose === 'login' ? 'login' : 'email verification';
    $message = "Hi " . (string) ($user['name'] ?? 'there') . ",\n\n"
        . "Your GharSquare {$purposeLabel} OTP is {$otp}.\n"
        . "This code is valid for " . PUBLIC_AUTH_OTP_MINUTES . " minutes.\n\n"
        . "If you did not request this, you can safely ignore this email.";
    $html = appMailTemplate(
        'Your verification code',
        '<p>Hello ' . e((string) ($user['name'] ?? 'there')) . ',</p>'
        . '<p>Your GharSquare ' . e($purposeLabel) . ' code is:</p>'
        . '<p style="font-size:28px;font-weight:700;letter-spacing:4px">' . e($otp) . '</p>'
        . '<p>This code expires in ' . PUBLIC_AUTH_OTP_MINUTES . ' minutes. If you did not request it, you can ignore this email.</p>'
    );
    $delivery = appSendMail($email, $subject, $html, $message);
    $sent = (bool) $delivery['sent'];

    publicAuthLogOtp($email, $otp, $purpose, $sent);

    return $sent;
}

function publicAuthLogOtp(string $target, string $otp, string $purpose, bool $mailSent): void
{
    $directory = BASE_PATH . '/storage/mail';

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $line = sprintf(
        "[%s] target=%s purpose=%s otp=%s mail_sent=%s%s",
        date('Y-m-d H:i:s'),
        $target,
        $purpose,
        $otp,
        $mailSent ? 'yes' : 'no',
        PHP_EOL
    );

    file_put_contents($directory . '/auth_otp.log', $line, FILE_APPEND);
}

function publicAuthIssueEmailOtp(array $user, string $purpose): string
{
    $otp = publicAuthCreateEmailOtp((int) $user['id']);
    publicAuthDeliverEmailOtp($user, $otp, $purpose);
    publicAuthSetPendingVerification((int) $user['id'], $purpose);

    return $otp;
}

function publicAuthOtpMessage(string $otp): string
{
    if (IS_LOCAL) {
        return 'OTP generated. Local testing code: ' . $otp . '. It is also saved in storage/mail/auth_otp.log.';
    }

    return 'OTP sent to your email address. Please check your inbox.';
}

function publicAuthVerifyEmailOtp(int $userId, string $otp): bool
{
    $stmt = db()->prepare(
        "SELECT id, otp, attempt_count, max_attempts
         FROM user_otps
         WHERE user_id = :user_id
           AND type = 'email'
           AND is_used = 0
           AND expires_at >= NOW()
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch();

    if (!$row) {
        return false;
    }

    if (!hash_equals((string) $row['otp'], $otp)) {
        $attemptCount = (int) $row['attempt_count'] + 1;
        $stmt = db()->prepare(
            'UPDATE user_otps
             SET attempt_count = :attempt_count,
                 is_used = CASE WHEN :attempt_count >= max_attempts THEN 1 ELSE is_used END
             WHERE id = :id'
        );
        $stmt->execute([':attempt_count' => $attemptCount, ':id' => (int) $row['id']]);
        return false;
    }

    $stmt = db()->prepare('UPDATE user_otps SET is_used = 1, attempt_count = attempt_count + 1 WHERE id = :id');
    $stmt->execute([':id' => (int) $row['id']]);

    $stmt = db()->prepare('UPDATE users SET email_verified = 1 WHERE id = :id');
    $stmt->execute([':id' => $userId]);

    return true;
}

function publicAuthRedirectAfterLogin(): void
{
    $redirectTo = $_SESSION['auth_redirect_to'] ?? APP_URL . '/website/';
    unset($_SESSION['auth_redirect_to']);
    redirect(publicAuthCleanWebsiteUrl((string) $redirectTo));
}

function publicAuthUserPayload(): ?array
{
    $user = publicUser();

    if (!$user) {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) ($user['email'] ?? ''),
        'phone' => (string) ($user['phone'] ?? ''),
        'role' => (string) ($user['role'] ?? 'customer'),
        'role_label' => publicAuthRoles()[(string) ($user['role'] ?? 'customer')] ?? ucfirst((string) ($user['role'] ?? 'customer')),
        'email_verified' => (int) ($user['email_verified'] ?? 0) === 1,
        'initials' => publicAuthInitials((string) $user['name']),
    ];
}

function publicAuthInitials(string $name): string
{
    $parts = preg_split('/\\s+/', trim($name)) ?: [];
    $letters = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= strtoupper(substr($part, 0, 1));
        }

        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters !== '' ? $letters : 'U';
}

function attachAnonymousActivityToUser(int $userId): void
{
    $sessionId = session_id();

    if ($sessionId === '') {
        return;
    }

    $stmt = db()->prepare(
        'UPDATE user_activity_logs
         SET user_id = :user_id
         WHERE user_id IS NULL
           AND session_id = :session_id'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':session_id' => $sessionId,
    ]);
}

function recordUserActivity(string $activityType, array $payload = []): void
{
    if (!tableExists('user_activity_logs')) {
        return;
    }

    $user = publicUser();
    $metadata = $payload['metadata'] ?? null;

    if ($metadata !== null && !is_string($metadata)) {
        $metadata = json_encode($metadata, JSON_UNESCAPED_SLASHES);
    }

    $stmt = db()->prepare(
        'INSERT INTO user_activity_logs
            (user_id, session_id, activity_type, entity_type, entity_id, search_query, listing_type, city, page_url, page_title, metadata, ip_address, user_agent, created_at)
         VALUES
            (:user_id, :session_id, :activity_type, :entity_type, :entity_id, :search_query, :listing_type, :city, :page_url, :page_title, :metadata, :ip_address, :user_agent, NOW())'
    );

    $stmt->execute([
        ':user_id' => isset($payload['user_id']) ? (int) $payload['user_id'] : ($user['id'] ?? null),
        ':session_id' => session_id() ?: null,
        ':activity_type' => substr($activityType, 0, 60),
        ':entity_type' => isset($payload['entity_type']) ? substr((string) $payload['entity_type'], 0, 60) : null,
        ':entity_id' => isset($payload['entity_id']) ? substr((string) $payload['entity_id'], 0, 120) : null,
        ':search_query' => isset($payload['search_query']) ? substr((string) $payload['search_query'], 0, 255) : null,
        ':listing_type' => isset($payload['listing_type']) ? substr((string) $payload['listing_type'], 0, 80) : null,
        ':city' => isset($payload['city']) ? substr((string) $payload['city'], 0, 120) : null,
        ':page_url' => isset($payload['page_url']) ? substr((string) $payload['page_url'], 0, 600) : null,
        ':page_title' => isset($payload['page_title']) ? substr((string) $payload['page_title'], 0, 255) : null,
        ':metadata' => $metadata,
        ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
        ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
    ]);
}

function recordPublicPropertyView(string $propertyRef, array $payload = []): bool
{
    $propertyRef = trim($propertyRef);

    if ($propertyRef === '' || !tableExists('properties') || !tableExists('user_activity_logs')) {
        return false;
    }

    $sql = 'SELECT p.id, p.user_id, p.slug, pb.title, lt.name AS listing_type, ci.name AS city
            FROM properties p
            LEFT JOIN property_basic pb ON pb.draft_id = p.draft_id
            LEFT JOIN listing_types lt ON lt.id = pb.listing_type_id
            LEFT JOIN property_location pl ON pl.draft_id = p.draft_id
            LEFT JOIN cities ci ON ci.id = pl.city_id
            WHERE p.status = "active"';
    $params = [];

    if (ctype_digit($propertyRef)) {
        $sql .= ' AND p.id = :property_id';
        $params[':property_id'] = (int) $propertyRef;
    } else {
        $sql .= ' AND p.slug = :slug';
        $params[':slug'] = $propertyRef;
    }

    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $property = $stmt->fetch();

    if (!$property) {
        return false;
    }

    $viewer = publicUser();
    if ($viewer && (int) ($viewer['id'] ?? 0) === (int) ($property['user_id'] ?? 0)) {
        return false;
    }

    $sessionId = session_id();
    if ($sessionId !== '') {
        $duplicateStmt = db()->prepare(
            'SELECT COUNT(*) FROM user_activity_logs
             WHERE session_id = :session_id
               AND activity_type = "property_view"
               AND entity_type = "property"
               AND entity_id = :entity_id
               AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)'
        );
        $duplicateStmt->execute([
            ':session_id' => $sessionId,
            ':entity_id' => (string) $property['id'],
        ]);

        if ((int) $duplicateStmt->fetchColumn() > 0) {
            return false;
        }
    }

    recordUserActivity('property_view', [
        'entity_type' => 'property',
        'entity_id' => (string) $property['id'],
        'listing_type' => (string) ($property['listing_type'] ?? ''),
        'city' => (string) ($property['city'] ?? ''),
        'page_title' => (string) ($property['title'] ?? ''),
        'page_url' => $payload['page_url'] ?? null,
        'metadata' => ['source' => $payload['source'] ?? 'property_details'],
    ]);

    return true;
}

function tableExists(string $table): bool
{
    static $cache = [];

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
    );
    $stmt->execute([':table_name' => $table]);

    $cache[$table] = (int) $stmt->fetchColumn() > 0;

    return $cache[$table];
}

function publicUserRecentActivity(int $limit = 20): array
{
    $user = publicUser();

    if (!$user || !tableExists('user_activity_logs')) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT activity_type, entity_type, entity_id, search_query, listing_type, city, page_title, page_url, metadata, created_at
         FROM user_activity_logs
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function publicAuthPropertySnapshot(array $payload): array
{
    $propertyRef = trim((string) ($payload['property_ref'] ?? $payload['property_id'] ?? $payload['entity_id'] ?? ''));
    $metadata = $payload['metadata'] ?? null;

    if ($metadata !== null && !is_string($metadata)) {
        $metadata = json_encode($metadata, JSON_UNESCAPED_SLASHES);
    }

    return [
        'property_ref' => substr($propertyRef, 0, 120),
        'listing_type' => isset($payload['listing_type']) ? substr((string) $payload['listing_type'], 0, 80) : null,
        'title' => isset($payload['title']) ? substr((string) $payload['title'], 0, 180) : null,
        'price_text' => isset($payload['price_text']) ? substr((string) $payload['price_text'], 0, 80) : null,
        'city' => isset($payload['city']) ? substr((string) $payload['city'], 0, 120) : null,
        'locality' => isset($payload['locality']) ? substr((string) $payload['locality'], 0, 120) : null,
        'category' => isset($payload['category']) ? substr((string) $payload['category'], 0, 120) : null,
        'image_url' => isset($payload['image_url']) ? substr((string) $payload['image_url'], 0, 600) : null,
        'details_url' => isset($payload['details_url']) ? substr(publicAuthCleanWebsiteUrl((string) $payload['details_url']), 0, 600) : null,
        'metadata' => $metadata,
    ];
}

function publicUserSavedPropertyRefs(): array
{
    $user = publicUser();

    if (!$user || !tableExists('saved_properties')) {
        return [];
    }

    $stmt = db()->prepare('SELECT property_ref FROM saved_properties WHERE user_id = :user_id');
    $stmt->execute([':user_id' => (int) $user['id']]);

    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function publicUserSavedProperties(int $limit = 50): array
{
    $user = publicUser();

    if (!$user || !tableExists('saved_properties')) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, property_ref, listing_type, title, price_text, city, locality, category, image_url, details_url, metadata, created_at
         FROM saved_properties
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function publicUserSavedPropertyCount(): int
{
    $user = publicUser();

    if (!$user || !tableExists('saved_properties')) {
        return 0;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM saved_properties WHERE user_id = :user_id');
    $stmt->execute([':user_id' => (int) $user['id']]);

    return (int) $stmt->fetchColumn();
}

function publicSavedPropertyExists(string $propertyRef): bool
{
    $user = publicUser();

    if (!$user || !tableExists('saved_properties')) {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM saved_properties
         WHERE user_id = :user_id
           AND property_ref = :property_ref'
    );
    $stmt->execute([
        ':user_id' => (int) $user['id'],
        ':property_ref' => substr($propertyRef, 0, 120),
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function savePublicProperty(array $payload): bool
{
    $user = publicUser();
    $snapshot = publicAuthPropertySnapshot($payload);

    if (!$user || !tableExists('saved_properties') || $snapshot['property_ref'] === '') {
        return false;
    }

    $stmt = db()->prepare(
        'INSERT INTO saved_properties
            (user_id, property_ref, listing_type, title, price_text, city, locality, category, image_url, details_url, metadata, created_at)
         VALUES
            (:user_id, :property_ref, :listing_type, :title, :price_text, :city, :locality, :category, :image_url, :details_url, :metadata, NOW())
         ON DUPLICATE KEY UPDATE
            listing_type = VALUES(listing_type),
            title = VALUES(title),
            price_text = VALUES(price_text),
            city = VALUES(city),
            locality = VALUES(locality),
            category = VALUES(category),
            image_url = VALUES(image_url),
            details_url = VALUES(details_url),
            metadata = VALUES(metadata),
            updated_at = NOW()'
    );

    $stmt->execute([
        ':user_id' => (int) $user['id'],
        ':property_ref' => $snapshot['property_ref'],
        ':listing_type' => $snapshot['listing_type'],
        ':title' => $snapshot['title'],
        ':price_text' => $snapshot['price_text'],
        ':city' => $snapshot['city'],
        ':locality' => $snapshot['locality'],
        ':category' => $snapshot['category'],
        ':image_url' => $snapshot['image_url'],
        ':details_url' => $snapshot['details_url'],
        ':metadata' => $snapshot['metadata'],
    ]);

    recordUserActivity('property_save', [
        'entity_type' => 'property',
        'entity_id' => $snapshot['property_ref'],
        'listing_type' => $snapshot['listing_type'],
        'city' => $snapshot['city'],
        'page_url' => $payload['page_url'] ?? $snapshot['details_url'],
        'page_title' => $snapshot['title'],
        'metadata' => ['source' => $payload['source'] ?? 'save_button'],
    ]);

    return true;
}

function removePublicSavedProperty(string $propertyRef, array $payload = []): bool
{
    $user = publicUser();

    if (!$user || !tableExists('saved_properties') || trim($propertyRef) === '') {
        return false;
    }

    $propertyRef = substr(trim($propertyRef), 0, 120);
    $stmt = db()->prepare(
        'DELETE FROM saved_properties
         WHERE user_id = :user_id
           AND property_ref = :property_ref'
    );
    $stmt->execute([
        ':user_id' => (int) $user['id'],
        ':property_ref' => $propertyRef,
    ]);

    recordUserActivity('property_unsave', [
        'entity_type' => 'property',
        'entity_id' => $propertyRef,
        'listing_type' => $payload['listing_type'] ?? null,
        'city' => $payload['city'] ?? null,
        'page_url' => $payload['page_url'] ?? null,
        'page_title' => $payload['title'] ?? null,
        'metadata' => ['source' => $payload['source'] ?? 'save_button'],
    ]);

    return true;
}

function createPublicPropertyEnquiry(array $payload): int
{
    $user = publicUser();
    $snapshot = publicAuthPropertySnapshot($payload);

    if (!$user || !tableExists('property_enquiries') || $snapshot['property_ref'] === '') {
        return 0;
    }

    $source = isset($payload['source']) ? substr((string) $payload['source'], 0, 80) : null;
    $message = isset($payload['message']) ? substr((string) $payload['message'], 0, 2000) : null;

    $stmt = db()->prepare(
        'INSERT INTO property_enquiries
            (user_id, property_ref, listing_type, title, price_text, city, locality, category, source, message, metadata, created_at)
         VALUES
            (:user_id, :property_ref, :listing_type, :title, :price_text, :city, :locality, :category, :source, :message, :metadata, NOW())'
    );
    $stmt->execute([
        ':user_id' => (int) $user['id'],
        ':property_ref' => $snapshot['property_ref'],
        ':listing_type' => $snapshot['listing_type'],
        ':title' => $snapshot['title'],
        ':price_text' => $snapshot['price_text'],
        ':city' => $snapshot['city'],
        ':locality' => $snapshot['locality'],
        ':category' => $snapshot['category'],
        ':source' => $source,
        ':message' => $message,
        ':metadata' => $snapshot['metadata'],
    ]);

    $enquiryId = (int) db()->lastInsertId();

    recordUserActivity('property_enquiry', [
        'entity_type' => 'property',
        'entity_id' => $snapshot['property_ref'],
        'listing_type' => $snapshot['listing_type'],
        'city' => $snapshot['city'],
        'page_url' => $payload['page_url'] ?? $snapshot['details_url'],
        'page_title' => $snapshot['title'],
        'metadata' => [
            'source' => $source,
            'enquiry_id' => $enquiryId,
        ],
    ]);

    return $enquiryId;
}

function publicUserEnquiries(int $limit = 50): array
{
    $user = publicUser();

    if (!$user || !tableExists('property_enquiries')) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, property_ref, listing_type, title, price_text, city, locality, category, source, message, status, metadata, created_at
         FROM property_enquiries
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function publicUserEnquiryCount(): int
{
    $user = publicUser();

    if (!$user || !tableExists('property_enquiries')) {
        return 0;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM property_enquiries WHERE user_id = :user_id');
    $stmt->execute([':user_id' => (int) $user['id']]);

    return (int) $stmt->fetchColumn();
}

function publicUserPropertyDrafts(int $limit = 50): array
{
    $user = publicUser();

    if (!$user || !tableExists('property_drafts')) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT pd.id, pd.current_step, pd.completion_percent, pd.is_submitted, pd.submitted_at, pd.updated_at,
                pb.title, pb.posted_by, pb.purpose_note,
                lt.name AS listing_type_name,
                pt.name AS property_type_name,
                pt.category AS property_category,
                p.id AS property_id,
                p.status AS property_status,
                p.owner_status_reason,
                p.owner_status_updated_at,
                (SELECT COUNT(DISTINCT NULLIF(views.session_id, ""))
                 FROM user_activity_logs views
                 WHERE views.activity_type = "property_view"
                   AND views.entity_type = "property"
                   AND CAST(views.entity_id AS UNSIGNED) = p.id) AS property_view_count,
                (SELECT COUNT(*)
                 FROM property_enquiries leads
                 WHERE leads.property_id = p.id) AS property_lead_count,
                (SELECT COUNT(*)
                 FROM property_enquiries new_leads
                 WHERE new_leads.property_id = p.id
                   AND new_leads.status = "new") AS property_new_lead_count
         FROM property_drafts pd
         LEFT JOIN property_basic pb ON pb.draft_id = pd.id
         LEFT JOIN listing_types lt ON lt.id = pb.listing_type_id
         LEFT JOIN property_types pt ON pt.id = pb.property_type_id
         LEFT JOIN properties p ON p.draft_id = pd.id
         WHERE pd.user_id = :user_id
         ORDER BY pd.updated_at DESC, pd.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', max(1, min(100, $limit)), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function publicOwnerEnquiries(int $limit = 100): array
{
    $user = publicUser();

    if (!$user || !tableExists('property_enquiries')) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT pe.id, pe.property_id, pe.contact_name, pe.contact_email, pe.contact_phone,
                pe.enquiry_type, pe.preferred_contact, pe.message, pe.status, pe.created_at,
                pe.title, pe.price_text, pe.city, pe.locality, pe.source, p.slug
         FROM property_enquiries pe
         INNER JOIN properties p ON p.id = pe.property_id
         WHERE p.user_id = :user_id
         ORDER BY pe.created_at DESC, pe.id DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', max(1, min(200, $limit)), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function publicOwnerLeadSummary(): array
{
    $user = publicUser();
    $summary = [
        'views' => 0,
        'leads' => 0,
        'new_leads' => 0,
        'contacted_leads' => 0,
    ];

    if (!$user) {
        return $summary;
    }

    $viewStmt = db()->prepare(
        'SELECT COUNT(DISTINCT NULLIF(ual.session_id, ""))
         FROM user_activity_logs ual
         INNER JOIN properties p ON p.id = CAST(ual.entity_id AS UNSIGNED)
         WHERE p.user_id = :user_id
           AND ual.activity_type = "property_view"
           AND ual.entity_type = "property"'
    );
    $viewStmt->execute([':user_id' => (int) $user['id']]);
    $summary['views'] = (int) $viewStmt->fetchColumn();

    $leadStmt = db()->prepare(
        'SELECT COUNT(*) AS total,
                SUM(pe.status = "new") AS new_total,
                SUM(pe.status = "contacted") AS contacted_total
         FROM property_enquiries pe
         INNER JOIN properties p ON p.id = pe.property_id
         WHERE p.user_id = :user_id'
    );
    $leadStmt->execute([':user_id' => (int) $user['id']]);
    $leadRow = $leadStmt->fetch() ?: [];
    $summary['leads'] = (int) ($leadRow['total'] ?? 0);
    $summary['new_leads'] = (int) ($leadRow['new_total'] ?? 0);
    $summary['contacted_leads'] = (int) ($leadRow['contacted_total'] ?? 0);

    return $summary;
}
