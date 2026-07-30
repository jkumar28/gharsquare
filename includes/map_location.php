<?php

declare(strict_types=1);

function mapLocationName(array $input, string $key): string
{
    $value = trim((string) ($input[$key] ?? ''));
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return mb_substr($value, 0, 100);
}

function findLocationMasterId(string $table, string $name, ?string $parentColumn = null, ?int $parentId = null): int
{
    $allowed = [
        'countries' => null,
        'states' => 'country_id',
        'cities' => 'state_id',
        'localities' => 'city_id',
    ];

    if (!array_key_exists($table, $allowed) || $allowed[$table] !== $parentColumn || $name === '') {
        return 0;
    }

    $sql = "SELECT id FROM {$table} WHERE LOWER(name) = LOWER(:name)";
    $params = [':name' => $name];
    if ($parentColumn !== null) {
        $sql .= " AND {$parentColumn} = :parent_id";
        $params[':parent_id'] = $parentId;
    }
    $sql .= ' LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function createMapLocationMaster(
    string $table,
    string $name,
    ?string $parentColumn = null,
    ?int $parentId = null,
    string $pincode = ''
): int {
    $existingId = findLocationMasterId($table, $name, $parentColumn, $parentId);
    if ($existingId > 0) {
        return $existingId;
    }

    if ($table === 'states') {
        $stmt = db()->prepare('INSERT INTO states (country_id, name) VALUES (:parent_id, :name)');
    } elseif ($table === 'cities') {
        $stmt = db()->prepare('INSERT INTO cities (state_id, name) VALUES (:parent_id, :name)');
    } elseif ($table === 'localities') {
        $stmt = db()->prepare('INSERT INTO localities (city_id, name, pincode) VALUES (:parent_id, :name, :pincode)');
    } else {
        throw new RuntimeException('Unsupported map location level.');
    }

    $params = [':parent_id' => $parentId, ':name' => $name];
    if ($table === 'localities') {
        $params[':pincode'] = $pincode !== '' ? $pincode : null;
    }
    $stmt->execute($params);

    return (int) db()->lastInsertId();
}

function resolveMapLocationHierarchy(array $input): array
{
    $countryId = validIdOrNull($input, 'country_id', 'findCountry');
    $stateId = validIdOrNull($input, 'state_id', 'findState');
    $cityId = validIdOrNull($input, 'city_id', 'findCity');
    $localityId = validIdOrNull($input, 'locality_id', 'findLocality');

    $state = $stateId ? findState($stateId) : null;
    $city = $cityId ? findCity($cityId) : null;
    $locality = $localityId ? findLocality($localityId) : null;

    $hierarchyValid = $countryId
        && $state
        && (int) $state['country_id'] === $countryId
        && $city
        && (int) $city['state_id'] === $stateId
        && $locality
        && (int) $locality['city_id'] === $cityId;

    if ($hierarchyValid) {
        return $input;
    }

    $stateName = mapLocationName($input, 'map_state_name');
    $cityName = mapLocationName($input, 'map_city_name');
    $localityName = mapLocationName($input, 'map_locality_name');
    $pincode = trim((string) ($input['pincode'] ?? ''));

    if (!$countryId || $stateName === '' || $cityName === '' || $localityName === '') {
        throw new RuntimeException('We could not match the selected map address. Please choose the address again or select the location manually.');
    }
    if ($pincode !== '' && !preg_match('/^[0-9]{4,10}$/', $pincode)) {
        throw new RuntimeException('Please enter a valid pincode.');
    }

    $pdo = db();
    $startedTransaction = !$pdo->inTransaction();
    if ($startedTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $stateId = findLocationMasterId('states', $stateName, 'country_id', $countryId)
            ?: createMapLocationMaster('states', $stateName, 'country_id', $countryId);
        $cityId = findLocationMasterId('cities', $cityName, 'state_id', $stateId)
            ?: createMapLocationMaster('cities', $cityName, 'state_id', $stateId);
        $localityId = findLocationMasterId('localities', $localityName, 'city_id', $cityId)
            ?: createMapLocationMaster('localities', $localityName, 'city_id', $cityId, $pincode);

        if ($startedTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $input['state_id'] = $stateId;
    $input['city_id'] = $cityId;
    $input['locality_id'] = $localityId;

    return $input;
}
