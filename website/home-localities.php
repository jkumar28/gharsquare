<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$cityName = trim((string) ($_GET['city'] ?? ''));
$cityName = substr($cityName, 0, 120);
$limit = max(1, min(12, (int) ($_GET['limit'] ?? 5)));

if ($cityName === '') {
    jsonResponse([
        'success' => false,
        'message' => 'City is required.',
        'city_found' => false,
        'localities' => [],
    ], 422);
}

try {
    $cityStmt = db()->prepare(
        'SELECT ci.id, ci.name, s.name AS state_name
         FROM cities ci
         LEFT JOIN states s ON s.id = ci.state_id
         WHERE ci.name = :city
         LIMIT 1'
    );
    $cityStmt->execute([':city' => $cityName]);
    $city = $cityStmt->fetch();

    if (!$city) {
        jsonResponse([
            'success' => true,
            'city_found' => false,
            'city' => $cityName,
            'localities' => [],
        ]);
    }

    $localityStmt = db()->prepare(
        "SELECT l.id, l.name, l.pincode,
                COUNT(DISTINCT CASE WHEN p.status = 'active' THEN p.id END) AS property_count
         FROM localities l
         LEFT JOIN property_location pl ON pl.locality_id = l.id
         LEFT JOIN properties p ON p.draft_id = pl.draft_id
         WHERE l.city_id = :city_id
         GROUP BY l.id, l.name, l.pincode
         ORDER BY property_count DESC, l.name ASC
         LIMIT :limit"
    );
    $localityStmt->bindValue(':city_id', (int) $city['id'], PDO::PARAM_INT);
    $localityStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $localityStmt->execute();

    jsonResponse([
        'success' => true,
        'city_found' => true,
        'city' => (string) $city['name'],
        'state' => (string) ($city['state_name'] ?? ''),
        'localities' => array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'pincode' => (string) ($row['pincode'] ?? ''),
            'property_count' => (int) $row['property_count'],
        ], $localityStmt->fetchAll()),
    ]);
} catch (Throwable $exception) {
    jsonResponse([
        'success' => false,
        'message' => 'Unable to load localities right now.',
        'city_found' => false,
        'localities' => [],
    ], 500);
}
