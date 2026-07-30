<?php

declare(strict_types=1);

require_once BASE_PATH . '/config/database.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function stringLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function isPostRequest(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old'][$key] ?? $default;
}

function setOldInput(array $data): void
{
    $_SESSION['old'] = $data;
}

function clearOldInput(): void
{
    unset($_SESSION['old']);
}

function setFormErrors(array $errors): void
{
    $_SESSION['form_errors'] = $errors;
}

function getFormErrors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);

    return is_array($errors) ? $errors : [];
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function isAjaxRequest(): bool
{
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

    if ($requestedWith === 'xmlhttprequest') {
        return true;
    }

    return str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function authClientIp(): string
{
    return substr(trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown')), 0, 45);
}

function authRateLimitStatus(string $scope, string $identifier): array
{
    $stmt = db()->prepare(
        'SELECT attempt_count, window_started_at, blocked_until
         FROM auth_rate_limits
         WHERE scope = :scope AND identifier_hash = :identifier_hash AND ip_address = :ip_address
         LIMIT 1'
    );
    $stmt->execute([
        ':scope' => $scope,
        ':identifier_hash' => hash('sha256', strtolower(trim($identifier))),
        ':ip_address' => authClientIp(),
    ]);
    $row = $stmt->fetch();
    $blockedUntil = $row['blocked_until'] ?? null;
    $retryAfter = $blockedUntil ? max(0, strtotime((string) $blockedUntil) - time()) : 0;

    return [
        'blocked' => $retryAfter > 0,
        'retry_after' => $retryAfter,
        'attempt_count' => (int) ($row['attempt_count'] ?? 0),
    ];
}

function authRateLimitHit(
    string $scope,
    string $identifier,
    int $maxAttempts,
    int $windowSeconds,
    int $blockSeconds
): array {
    $pdo = db();
    $hash = hash('sha256', strtolower(trim($identifier)));
    $ipAddress = authClientIp();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT id, attempt_count, window_started_at, blocked_until
             FROM auth_rate_limits
             WHERE scope = :scope AND identifier_hash = :identifier_hash AND ip_address = :ip_address
             LIMIT 1 FOR UPDATE'
        );
        $stmt->execute([
            ':scope' => $scope,
            ':identifier_hash' => $hash,
            ':ip_address' => $ipAddress,
        ]);
        $row = $stmt->fetch();
        $now = time();
        $windowExpired = !$row || strtotime((string) $row['window_started_at']) <= $now - $windowSeconds;
        $attemptCount = $windowExpired ? 1 : ((int) $row['attempt_count'] + 1);
        $blocked = $attemptCount >= $maxAttempts;

        if ($row) {
            $update = $pdo->prepare(
                'UPDATE auth_rate_limits
                 SET attempt_count = :attempt_count,
                     window_started_at = :window_started_at,
                     blocked_until = :blocked_until,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute([
                ':attempt_count' => $attemptCount,
                ':window_started_at' => $windowExpired ? date('Y-m-d H:i:s', $now) : $row['window_started_at'],
                ':blocked_until' => $blocked ? date('Y-m-d H:i:s', $now + $blockSeconds) : null,
                ':id' => $row['id'],
            ]);
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO auth_rate_limits
                    (scope, identifier_hash, ip_address, attempt_count, window_started_at, blocked_until, updated_at)
                 VALUES
                    (:scope, :identifier_hash, :ip_address, :attempt_count, NOW(), :blocked_until, NOW())'
            );
            $insert->execute([
                ':scope' => $scope,
                ':identifier_hash' => $hash,
                ':ip_address' => $ipAddress,
                ':attempt_count' => $attemptCount,
                ':blocked_until' => $blocked ? date('Y-m-d H:i:s', $now + $blockSeconds) : null,
            ]);
        }

        $pdo->commit();

        return ['blocked' => $blocked, 'retry_after' => $blocked ? $blockSeconds : 0];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function authRateLimitClear(string $scope, string $identifier): void
{
    $stmt = db()->prepare(
        'DELETE FROM auth_rate_limits
         WHERE scope = :scope AND identifier_hash = :identifier_hash AND ip_address = :ip_address'
    );
    $stmt->execute([
        ':scope' => $scope,
        ':identifier_hash' => hash('sha256', strtolower(trim($identifier))),
        ':ip_address' => authClientIp(),
    ]);
}

function authCleanupExpiredRecords(): void
{
    db()->exec(
        "DELETE FROM user_otps
         WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
            OR (is_used = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY))"
    );
    db()->exec(
        "DELETE FROM auth_rate_limits
         WHERE updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
           AND (blocked_until IS NULL OR blocked_until < NOW())"
    );
}

function tableHasColumn(string $table, string $column): bool
{
    static $cache = [];
    $cacheKey = $table . '.' . $column;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        ':table_name' => $table,
        ':column_name' => $column,
    ]);

    $cache[$cacheKey] = (int) $stmt->fetchColumn() > 0;

    return $cache[$cacheKey];
}

function propertyTypeCategories(): array
{
    return [
        'residential' => 'Residential',
        'commercial' => 'Commercial',
        'land' => 'Land',
    ];
}

function propertyTypesSummary(): array
{
    $summary = [
        'total' => 0,
        'residential' => 0,
        'commercial' => 0,
        'land' => 0,
    ];

    try {
        $rows = db()->query('SELECT category, COUNT(*) AS total FROM property_types GROUP BY category')->fetchAll();

        foreach ($rows as $row) {
            $category = (string) ($row['category'] ?? '');
            $count = (int) ($row['total'] ?? 0);

            if (array_key_exists($category, $summary)) {
                $summary[$category] = $count;
            }

            $summary['total'] += $count;
        }
    } catch (Throwable $exception) {
        return $summary;
    }

    return $summary;
}

function propertyTypesAll(): array
{
    $sql = "SELECT pt.id, pt.name, pt.category, COUNT(pb.id) AS usage_count
            FROM property_types pt
            LEFT JOIN property_basic pb ON pb.property_type_id = pt.id
            GROUP BY pt.id, pt.name, pt.category
            ORDER BY FIELD(pt.category, 'residential', 'commercial', 'land'), pt.name ASC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function findPropertyType(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, name, category FROM property_types WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $propertyType = $stmt->fetch();

    return $propertyType ?: null;
}

function propertyTypeExists(string $name, string $category, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM property_types WHERE name = :name AND category = :category';
    $params = [
        ':name' => $name,
        ':category' => $category,
    ];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validatePropertyTypeInput(array $input, ?int $ignoreId = null): array
{
    $categories = propertyTypeCategories();
    $name = trim((string) ($input['name'] ?? ''));
    $category = trim((string) ($input['category'] ?? ''));
    $errors = [];

    if ($name === '') {
        $errors[] = 'Property type name is required.';
    } elseif (stringLength($name) > 100) {
        $errors[] = 'Property type name must be 100 characters or fewer.';
    }

    if (!array_key_exists($category, $categories)) {
        $errors[] = 'Please select a valid category.';
    }

    if ($name !== '' && $category !== '' && !$errors && propertyTypeExists($name, $category, $ignoreId)) {
        $errors[] = 'This property type already exists in the selected category.';
    }

    return [
        'data' => [
            'name' => $name,
            'category' => $category,
        ],
        'errors' => $errors,
    ];
}

function createPropertyType(array $data): int
{
    $stmt = db()->prepare('INSERT INTO property_types (name, category) VALUES (:name, :category)');
    $stmt->execute([
        ':name' => $data['name'],
        ':category' => $data['category'],
    ]);

    return (int) db()->lastInsertId();
}

function updatePropertyType(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE property_types SET name = :name, category = :category WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':name' => $data['name'],
        ':category' => $data['category'],
    ]);
}

function deletePropertyType(int $id): void
{
    $stmt = db()->prepare('DELETE FROM property_types WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function countTableRows(string $table): int
{
    $allowedTables = [
        'countries',
        'states',
        'cities',
        'localities',
        'listing_types',
        'amenities_master',
        'property_types',
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    try {
        return (int) db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    } catch (Throwable $exception) {
        return 0;
    }
}

function countriesAll(): array
{
    $sql = "SELECT c.id, c.name, COUNT(s.id) AS state_count
            FROM countries c
            LEFT JOIN states s ON s.country_id = c.id
            GROUP BY c.id, c.name
            ORDER BY c.name ASC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function countryOptions(): array
{
    try {
        return db()->query('SELECT id, name FROM countries ORDER BY name ASC')->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function findCountry(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, name FROM countries WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

function countryExists(string $name, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM countries WHERE name = :name';
    $params = [':name' => $name];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validateCountryInput(array $input, ?int $ignoreId = null): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $errors = [];

    if ($name === '') {
        $errors[] = 'Country name is required.';
    } elseif (stringLength($name) > 100) {
        $errors[] = 'Country name must be 100 characters or fewer.';
    } elseif (countryExists($name, $ignoreId)) {
        $errors[] = 'This country already exists.';
    }

    return [
        'data' => ['name' => $name],
        'errors' => $errors,
    ];
}

function createCountry(array $data): int
{
    $stmt = db()->prepare('INSERT INTO countries (name) VALUES (:name)');
    $stmt->execute([':name' => $data['name']]);

    return (int) db()->lastInsertId();
}

function updateCountry(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE countries SET name = :name WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':name' => $data['name'],
    ]);
}

function deleteCountry(int $id): void
{
    $stmt = db()->prepare('DELETE FROM countries WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function statesAll(): array
{
    $sql = "SELECT s.id, s.name, s.country_id, c.name AS country_name, COUNT(ci.id) AS city_count
            FROM states s
            LEFT JOIN countries c ON c.id = s.country_id
            LEFT JOIN cities ci ON ci.state_id = s.id
            GROUP BY s.id, s.name, s.country_id, c.name
            ORDER BY c.name ASC, s.name ASC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function stateOptions(?int $countryId = null): array
{
    $sql = 'SELECT id, country_id, name FROM states';
    $params = [];

    if ($countryId !== null) {
        $sql .= ' WHERE country_id = :country_id';
        $params[':country_id'] = $countryId;
    }

    $sql .= ' ORDER BY name ASC';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function findState(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, country_id, name FROM states WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

function stateExists(string $name, int $countryId, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM states WHERE name = :name AND country_id = :country_id';
    $params = [
        ':name' => $name,
        ':country_id' => $countryId,
    ];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validateStateInput(array $input, ?int $ignoreId = null): array
{
    $countryId = (int) ($input['country_id'] ?? 0);
    $name = trim((string) ($input['name'] ?? ''));
    $errors = [];

    if ($countryId <= 0 || !findCountry($countryId)) {
        $errors[] = 'Please select a valid country.';
    }

    if ($name === '') {
        $errors[] = 'State name is required.';
    } elseif (stringLength($name) > 100) {
        $errors[] = 'State name must be 100 characters or fewer.';
    }

    if ($countryId > 0 && $name !== '' && !$errors && stateExists($name, $countryId, $ignoreId)) {
        $errors[] = 'This state already exists under the selected country.';
    }

    return [
        'data' => [
            'country_id' => $countryId,
            'name' => $name,
        ],
        'errors' => $errors,
    ];
}

function createState(array $data): int
{
    $stmt = db()->prepare('INSERT INTO states (country_id, name) VALUES (:country_id, :name)');
    $stmt->execute([
        ':country_id' => $data['country_id'],
        ':name' => $data['name'],
    ]);

    return (int) db()->lastInsertId();
}

function updateState(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE states SET country_id = :country_id, name = :name WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':country_id' => $data['country_id'],
        ':name' => $data['name'],
    ]);
}

function deleteState(int $id): void
{
    $stmt = db()->prepare('DELETE FROM states WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function citiesAll(): array
{
    $sql = "SELECT ci.id, ci.name, ci.state_id, s.name AS state_name, c.name AS country_name, COUNT(l.id) AS locality_count
            FROM cities ci
            LEFT JOIN states s ON s.id = ci.state_id
            LEFT JOIN countries c ON c.id = s.country_id
            LEFT JOIN localities l ON l.city_id = ci.id
            GROUP BY ci.id, ci.name, ci.state_id, s.name, c.name
            ORDER BY c.name ASC, s.name ASC, ci.name ASC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function cityOptions(?int $stateId = null): array
{
    $sql = 'SELECT id, state_id, name FROM cities';
    $params = [];

    if ($stateId !== null) {
        $sql .= ' WHERE state_id = :state_id';
        $params[':state_id'] = $stateId;
    }

    $sql .= ' ORDER BY name ASC';

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function findCity(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, state_id, name FROM cities WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

function cityExists(string $name, int $stateId, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM cities WHERE name = :name AND state_id = :state_id';
    $params = [
        ':name' => $name,
        ':state_id' => $stateId,
    ];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validateCityInput(array $input, ?int $ignoreId = null): array
{
    $stateId = (int) ($input['state_id'] ?? 0);
    $name = trim((string) ($input['name'] ?? ''));
    $errors = [];

    if ($stateId <= 0 || !findState($stateId)) {
        $errors[] = 'Please select a valid state.';
    }

    if ($name === '') {
        $errors[] = 'City name is required.';
    } elseif (stringLength($name) > 100) {
        $errors[] = 'City name must be 100 characters or fewer.';
    }

    if ($stateId > 0 && $name !== '' && !$errors && cityExists($name, $stateId, $ignoreId)) {
        $errors[] = 'This city already exists under the selected state.';
    }

    return [
        'data' => [
            'state_id' => $stateId,
            'name' => $name,
        ],
        'errors' => $errors,
    ];
}

function createCity(array $data): int
{
    $stmt = db()->prepare('INSERT INTO cities (state_id, name) VALUES (:state_id, :name)');
    $stmt->execute([
        ':state_id' => $data['state_id'],
        ':name' => $data['name'],
    ]);

    return (int) db()->lastInsertId();
}

function updateCity(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE cities SET state_id = :state_id, name = :name WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':state_id' => $data['state_id'],
        ':name' => $data['name'],
    ]);
}

function deleteCity(int $id): void
{
    $stmt = db()->prepare('DELETE FROM cities WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function localitiesAll(): array
{
    $sql = "SELECT l.id, l.name, l.pincode, l.city_id,
                   ci.name AS city_name, s.name AS state_name, c.name AS country_name,
                   COUNT(pl.id) AS property_count
            FROM localities l
            LEFT JOIN cities ci ON ci.id = l.city_id
            LEFT JOIN states s ON s.id = ci.state_id
            LEFT JOIN countries c ON c.id = s.country_id
            LEFT JOIN property_location pl ON pl.locality_id = l.id
            GROUP BY l.id, l.name, l.pincode, l.city_id, ci.name, s.name, c.name
            ORDER BY c.name ASC, s.name ASC, ci.name ASC, l.name ASC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function findLocality(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, city_id, name, pincode FROM localities WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

function localityExists(string $name, int $cityId, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM localities WHERE name = :name AND city_id = :city_id';
    $params = [
        ':name' => $name,
        ':city_id' => $cityId,
    ];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validateLocalityInput(array $input, ?int $ignoreId = null): array
{
    $cityId = (int) ($input['city_id'] ?? 0);
    $name = trim((string) ($input['name'] ?? ''));
    $pincode = trim((string) ($input['pincode'] ?? ''));
    $errors = [];

    if ($cityId <= 0 || !findCity($cityId)) {
        $errors[] = 'Please select a valid city.';
    }

    if ($name === '') {
        $errors[] = 'Locality name is required.';
    } elseif (stringLength($name) > 150) {
        $errors[] = 'Locality name must be 150 characters or fewer.';
    }

    if ($pincode !== '' && !preg_match('/^[0-9]{4,10}$/', $pincode)) {
        $errors[] = 'Pincode must be 4 to 10 digits.';
    }

    if ($cityId > 0 && $name !== '' && !$errors && localityExists($name, $cityId, $ignoreId)) {
        $errors[] = 'This locality already exists under the selected city.';
    }

    return [
        'data' => [
            'city_id' => $cityId,
            'name' => $name,
            'pincode' => $pincode,
        ],
        'errors' => $errors,
    ];
}

function createLocality(array $data): int
{
    $stmt = db()->prepare('INSERT INTO localities (city_id, name, pincode) VALUES (:city_id, :name, :pincode)');
    $stmt->execute([
        ':city_id' => $data['city_id'],
        ':name' => $data['name'],
        ':pincode' => $data['pincode'] !== '' ? $data['pincode'] : null,
    ]);

    return (int) db()->lastInsertId();
}

function updateLocality(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE localities SET city_id = :city_id, name = :name, pincode = :pincode WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':city_id' => $data['city_id'],
        ':name' => $data['name'],
        ':pincode' => $data['pincode'] !== '' ? $data['pincode'] : null,
    ]);
}

function deleteLocality(int $id): void
{
    $stmt = db()->prepare('DELETE FROM localities WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function listingTypesAll(): array
{
    $sql = "SELECT lt.id, lt.name, COUNT(pb.id) AS usage_count
            FROM listing_types lt
            LEFT JOIN property_basic pb ON pb.listing_type_id = lt.id
            GROUP BY lt.id, lt.name
            ORDER BY lt.name ASC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function findListingType(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, name FROM listing_types WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

function listingTypeExists(string $name, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM listing_types WHERE name = :name';
    $params = [':name' => $name];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validateListingTypeInput(array $input, ?int $ignoreId = null): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $errors = [];

    if ($name === '') {
        $errors[] = 'Listing type name is required.';
    } elseif (stringLength($name) > 50) {
        $errors[] = 'Listing type name must be 50 characters or fewer.';
    } elseif (listingTypeExists($name, $ignoreId)) {
        $errors[] = 'This listing type already exists.';
    }

    return [
        'data' => ['name' => $name],
        'errors' => $errors,
    ];
}

function createListingType(array $data): int
{
    $stmt = db()->prepare('INSERT INTO listing_types (name) VALUES (:name)');
    $stmt->execute([':name' => $data['name']]);

    return (int) db()->lastInsertId();
}

function updateListingType(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE listing_types SET name = :name WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':name' => $data['name'],
    ]);
}

function deleteListingType(int $id): void
{
    $stmt = db()->prepare('DELETE FROM listing_types WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function amenitiesAll(): array
{
    $sql = "SELECT am.id, am.name, am.category, am.icon, COUNT(pa.draft_id) AS usage_count
            FROM amenities_master am
            LEFT JOIN property_amenities pa ON pa.amenity_id = am.id
            GROUP BY am.id, am.name, am.category, am.icon
            ORDER BY am.category ASC, am.name ASC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function amenityCategories(): array
{
    try {
        $rows = db()->query("SELECT DISTINCT category FROM amenities_master WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll();
        $categories = [];

        foreach ($rows as $row) {
            $value = trim((string) ($row['category'] ?? ''));

            if ($value !== '') {
                $categories[$value] = ucwords(str_replace(['_', '-'], ' ', $value));
            }
        }

        return $categories;
    } catch (Throwable $exception) {
        return [];
    }
}

function findAmenity(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, name, category, icon FROM amenities_master WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

function amenityExists(string $name, string $category, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM amenities_master WHERE name = :name AND COALESCE(category, \'\') = :category';
    $params = [
        ':name' => $name,
        ':category' => $category,
    ];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validateAmenityInput(array $input, ?int $ignoreId = null): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $category = trim((string) ($input['category'] ?? ''));
    $icon = trim((string) ($input['icon'] ?? ''));
    $errors = [];

    if ($name === '') {
        $errors[] = 'Amenity name is required.';
    } elseif (stringLength($name) > 100) {
        $errors[] = 'Amenity name must be 100 characters or fewer.';
    }

    if ($category !== '' && stringLength($category) > 100) {
        $errors[] = 'Amenity category must be 100 characters or fewer.';
    }

    if ($icon !== '' && stringLength($icon) > 100) {
        $errors[] = 'Icon value must be 100 characters or fewer.';
    }

    if ($name !== '' && !$errors && amenityExists($name, $category, $ignoreId)) {
        $errors[] = 'This amenity already exists in the selected category.';
    }

    return [
        'data' => [
            'name' => $name,
            'category' => $category,
            'icon' => $icon,
        ],
        'errors' => $errors,
    ];
}

function createAmenity(array $data): int
{
    $stmt = db()->prepare('INSERT INTO amenities_master (name, category, icon) VALUES (:name, :category, :icon)');
    $stmt->execute([
        ':name' => $data['name'],
        ':category' => $data['category'] !== '' ? $data['category'] : null,
        ':icon' => $data['icon'] !== '' ? $data['icon'] : null,
    ]);

    return (int) db()->lastInsertId();
}

function updateAmenity(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE amenities_master SET name = :name, category = :category, icon = :icon WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':name' => $data['name'],
        ':category' => $data['category'] !== '' ? $data['category'] : null,
        ':icon' => $data['icon'] !== '' ? $data['icon'] : null,
    ]);
}

function deleteAmenity(int $id): void
{
    $stmt = db()->prepare('DELETE FROM amenities_master WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function deleteBlockedMessage(string $moduleLabel): string
{
    return "This {$moduleLabel} is already linked to other records, so it cannot be deleted.";
}

function userRoles(): array
{
    return [
        'customer' => 'Customer / Buyer',
        'tenant' => 'Tenant',
        'owner' => 'Owner',
        'agent' => 'Agent',
        'builder' => 'Builder',
        'admin' => 'Admin',
    ];
}

function userStatuses(): array
{
    return [
        'active' => 'Active',
        'blocked' => 'Blocked',
        'deleted' => 'Deleted',
    ];
}

function userRoleBadgeClass(string $role): string
{
    return match ($role) {
        'admin' => 'role-pill admin',
        'agent' => 'role-pill agent',
        'builder' => 'role-pill builder',
        'tenant' => 'role-pill tenant',
        'customer' => 'role-pill customer',
        default => 'role-pill owner',
    };
}

function userStatusBadgeClass(string $status): string
{
    return match ($status) {
        'active' => 'status-pill submitted',
        'blocked' => 'status-pill blocked',
        'deleted' => 'status-pill deleted',
        default => 'status-pill draft',
    };
}

function usersSummary(): array
{
    $summary = [
        'total' => 0,
        'active' => 0,
        'blocked' => 0,
        'admins' => 0,
    ];

    try {
        $summary['total'] = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $summary['active'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
        $summary['blocked'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetchColumn();
        $summary['admins'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    } catch (Throwable $exception) {
        return $summary;
    }

    return $summary;
}

function usersSummaryPayload(): array
{
    $summary = usersSummary();

    return [
        'total' => (string) $summary['total'],
        'active' => (string) $summary['active'],
        'blocked' => (string) $summary['blocked'],
        'admins' => (string) $summary['admins'],
    ];
}

function usersAll(): array
{
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.email_verified, u.last_login, u.created_at,
                   (SELECT COUNT(*) FROM property_drafts pd WHERE pd.user_id = u.id) AS draft_count,
                   (SELECT COUNT(*) FROM properties p WHERE p.user_id = u.id) AS property_count,
                   (SELECT COUNT(*) FROM property_leads pl WHERE pl.user_id = u.id) AS lead_count
            FROM users u
            ORDER BY u.created_at DESC, u.id DESC";

    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}

function findUser(int $id, bool $includePassword = false): ?array
{
    $columns = $includePassword
        ? 'id, name, email, phone, password, role, status, email_verified, last_login, created_at'
        : 'id, name, email, phone, role, status, email_verified, last_login, created_at';

    $stmt = db()->prepare("SELECT {$columns} FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function emailExists(string $email, ?int $ignoreId = null): bool
{
    if ($email === '') {
        return false;
    }

    $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
    $params = [':email' => $email];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function phoneExists(string $phone, ?int $ignoreId = null): bool
{
    if ($phone === '') {
        return false;
    }

    $sql = 'SELECT COUNT(*) FROM users WHERE phone = :phone';
    $params = [':phone' => $phone];

    if ($ignoreId !== null) {
        $sql .= ' AND id != :ignore_id';
        $params[':ignore_id'] = $ignoreId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function validateUserInput(array $input, bool $isCreate = true, ?int $ignoreId = null): array
{
    $roles = userRoles();
    $statuses = userStatuses();
    $name = trim((string) ($input['name'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $phone = trim((string) ($input['phone'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $role = trim((string) ($input['role'] ?? 'owner'));
    $status = trim((string) ($input['status'] ?? 'active'));
    $emailVerified = isset($input['email_verified']) ? 1 : 0;
    $errors = [];

    if ($name === '') {
        $errors[] = 'User name is required.';
    } elseif (stringLength($name) > 150) {
        $errors[] = 'User name must be 150 characters or fewer.';
    }

    if ($email === '' && $phone === '') {
        $errors[] = 'Please provide at least an email or phone number.';
    }

    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (emailExists($email, $ignoreId)) {
            $errors[] = 'This email address is already in use.';
        }
    }

    if ($phone !== '') {
        if (!preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) {
            $errors[] = 'Phone number must be 6 to 20 characters and contain only digits, spaces, +, or -.';
        } elseif (phoneExists($phone, $ignoreId)) {
            $errors[] = 'This phone number is already in use.';
        }
    }

    if (!array_key_exists($role, $roles)) {
        $errors[] = 'Please select a valid user role.';
    }

    if (!array_key_exists($status, $statuses)) {
        $errors[] = 'Please select a valid user status.';
    }

    if ($isCreate) {
        if ($password === '') {
            $errors[] = 'Password is required for a new user.';
        } elseif (stringLength($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
    } elseif ($password !== '' && stringLength($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    return [
        'data' => [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => $role,
            'status' => $status,
            'email_verified' => $emailVerified,
        ],
        'errors' => $errors,
    ];
}

function createUser(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO users (name, email, phone, password, role, status, email_verified, created_at)
         VALUES (:name, :email, :phone, :password, :role, :status, :email_verified, NOW())'
    );

    $stmt->execute([
        ':name' => $data['name'],
        ':email' => $data['email'] !== '' ? $data['email'] : null,
        ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
        ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ':role' => $data['role'],
        ':status' => $data['status'],
        ':email_verified' => $data['email_verified'],
    ]);

    return (int) db()->lastInsertId();
}

function updateUser(int $id, array $data): void
{
    $params = [
        ':id' => $id,
        ':name' => $data['name'],
        ':email' => $data['email'] !== '' ? $data['email'] : null,
        ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
        ':role' => $data['role'],
        ':status' => $data['status'],
        ':email_verified' => $data['email_verified'],
    ];

    $sql = 'UPDATE users
            SET name = :name,
                email = :email,
                phone = :phone,
                role = :role,
                status = :status,
                email_verified = :email_verified';

    if ($data['password'] !== '') {
        $sql .= ', password = :password';
        $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
}

function setUserStatus(int $id, string $status): void
{
    $stmt = db()->prepare('UPDATE users SET status = :status WHERE id = :id');
    $stmt->execute([
        ':id' => $id,
        ':status' => $status,
    ]);
}

function nextUserStatus(string $currentStatus): string
{
    return $currentStatus === 'active' ? 'blocked' : 'active';
}

function userVerifiedHtml(int $emailVerified): string
{
    $class = $emailVerified === 1 ? 'table-verified yes' : 'table-verified no';
    $label = $emailVerified === 1 ? 'Verified' : 'Pending';

    return '<span class="' . $class . '">' . e($label) . '</span>';
}

function userStatusHtml(string $status): string
{
    $statuses = userStatuses();
    $label = $statuses[$status] ?? ucfirst($status);

    return '<span class="' . userStatusBadgeClass($status) . '">' . e($label) . '</span>';
}

function userActionsHtml(array $user): string
{
    $id = (int) ($user['id'] ?? 0);
    $status = (string) ($user['status'] ?? 'active');
    $nextStatus = nextUserStatus($status);
    $statusLabel = $nextStatus === 'active' ? 'Activate' : 'Block';
    $statusQuestion = $nextStatus === 'active'
        ? 'Activate this user account?'
        : 'Block this user account?';
    $selfAdminId = (int) ($_SESSION['admin']['id'] ?? 0);

    $html = '<div class="table-actions">';
    $html .= '<a class="btn btn-sm btn-outline-dark icon-action-btn" href="' . ADMIN_URL . '/users/edit.php?id=' . $id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit user" aria-label="Edit user"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>';

    if ($id !== $selfAdminId && $status !== 'deleted') {
        $html .= '<form method="post" action="' . ADMIN_URL . '/users/toggle-status.php" data-confirm="' . e($statusQuestion) . '" data-confirm-button="' . e($statusLabel) . '" data-loading-text="Updating user status..." data-async="true" data-row-id="' . $id . '">';
        $html .= '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
        $html .= '<input type="hidden" name="id" value="' . $id . '">';
        $html .= '<button class="btn btn-sm btn-outline-primary icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($statusLabel . ' user') . '" aria-label="' . e($statusLabel . ' user') . '"><i class="bi ' . e($nextStatus === 'active' ? 'bi-check-circle' : 'bi-slash-circle') . '" aria-hidden="true"></i></button>';
        $html .= '</form>';

        $html .= '<form method="post" action="' . ADMIN_URL . '/users/delete.php" data-confirm="Mark this user as deleted?" data-confirm-button="Delete" data-loading-text="Deleting user..." data-async="true" data-row-id="' . $id . '">';
        $html .= '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
        $html .= '<input type="hidden" name="id" value="' . $id . '">';
        $html .= '<button class="btn btn-sm btn-outline-danger icon-action-btn" type="submit" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete user" aria-label="Delete user"><i class="bi bi-trash3" aria-hidden="true"></i></button>';
        $html .= '</form>';
    }

    $html .= '</div>';

    return $html;
}

function adminStatCounts(): array
{
    $queries = [
        'total_users' => 'SELECT COUNT(*) FROM users',
        'active_properties' => "SELECT COUNT(*) FROM properties WHERE status = 'active'",
        'pending_properties' => "SELECT COUNT(*) FROM properties WHERE status = 'pending'",
        'total_leads' => 'SELECT COUNT(*) FROM property_leads',
    ];
    $stats = [];

    foreach ($queries as $key => $sql) {
        try {
            $stats[$key] = (int) db()->query($sql)->fetchColumn();
        } catch (Throwable $exception) {
            $stats[$key] = 0;
        }
    }

    return $stats;
}

function recentDrafts(int $limit = 5): array
{
    $sql = "SELECT pd.id, pd.current_step, pd.completion_percent, pd.is_submitted, pd.updated_at,
                   u.name AS user_name,
                   pb.title
            FROM property_drafts pd
            LEFT JOIN users u ON u.id = pd.user_id
            LEFT JOIN property_basic pb ON pb.draft_id = pd.id
            ORDER BY pd.updated_at DESC
            LIMIT :limit";

    try {
        $stmt = db()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (Throwable $exception) {
        return [];
    }
}
