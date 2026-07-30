<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/config.php';

const MIGRATION_TABLE = 'schema_migrations';

function migrationUsage(): void
{
    echo <<<TEXT
GharSquare database migrations

Usage:
  php database/migrate.php [--status] [--dry-run] [--database=NAME]
  php database/migrate.php --mark-applied=FILENAME [--database=NAME]

Options:
  --status                 Show applied and pending migrations without changing data.
  --dry-run                Show migrations that would be applied.
  --database=NAME          Target a specific database using configured credentials.
  --mark-applied=FILENAME  Record a previously verified migration without executing it.
  --help                    Show this help.

TEXT;
}

function migrationOption(string $name): ?string
{
    foreach (array_slice($_SERVER['argv'] ?? [], 1) as $argument) {
        if ($argument === $name) {
            return '';
        }

        $prefix = $name . '=';
        if (str_starts_with($argument, $prefix)) {
            return substr($argument, strlen($prefix));
        }
    }

    return null;
}

function migrationDatabaseName(): string
{
    $requested = migrationOption('--database');
    $name = $requested !== null && $requested !== '' ? $requested : DB_NAME;

    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        throw new RuntimeException('Database names may contain only letters, numbers, and underscores.');
    }

    return $name;
}

function migrationConnection(string $databaseName): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . $databaseName . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET time_zone = '" . APP_DB_TIMEZONE_OFFSET . "'");

    return $pdo;
}

function ensureMigrationTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `' . MIGRATION_TABLE . '` (
            `filename` varchar(255) NOT NULL,
            `checksum` char(64) NOT NULL,
            `execution_ms` int unsigned NOT NULL DEFAULT 0,
            `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );
}

function migrationFiles(): array
{
    $files = glob(__DIR__ . '/migrations/*.sql') ?: [];
    sort($files, SORT_STRING);

    return $files;
}

function appliedMigrations(PDO $pdo): array
{
    $rows = $pdo->query(
        'SELECT filename, checksum, execution_ms, applied_at
         FROM `' . MIGRATION_TABLE . '`
         ORDER BY filename'
    )->fetchAll();

    $applied = [];
    foreach ($rows as $row) {
        $applied[(string) $row['filename']] = $row;
    }

    return $applied;
}

function migrationChecksum(string $path): string
{
    $checksum = hash_file('sha256', $path);
    if ($checksum === false) {
        throw new RuntimeException('Unable to checksum migration: ' . basename($path));
    }

    return $checksum;
}

function validateAppliedChecksums(array $files, array $applied): void
{
    $available = [];

    foreach ($files as $path) {
        $filename = basename($path);
        $available[$filename] = true;

        if (!isset($applied[$filename])) {
            continue;
        }

        if (!hash_equals((string) $applied[$filename]['checksum'], migrationChecksum($path))) {
            throw new RuntimeException(
                "Checksum mismatch for {$filename}. Never edit an applied migration; create a new one."
            );
        }
    }

    foreach (array_keys($applied) as $filename) {
        if (!isset($available[$filename])) {
            throw new RuntimeException(
                "Applied migration file is missing: {$filename}. Restore the original file."
            );
        }
    }
}

function recordMigration(PDO $pdo, string $filename, string $checksum, int $executionMs): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO `' . MIGRATION_TABLE . '` (filename, checksum, execution_ms, applied_at)
         VALUES (:filename, :checksum, :execution_ms, NOW())'
    );
    $stmt->execute([
        ':filename' => $filename,
        ':checksum' => $checksum,
        ':execution_ms' => $executionMs,
    ]);
}

function releaseMigrationLock(PDO $pdo, string $lockName): void
{
    $stmt = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
    $stmt->execute([':lock_name' => $lockName]);
}

try {
    if (migrationOption('--help') !== null) {
        migrationUsage();
        exit(0);
    }

    $databaseName = migrationDatabaseName();
    $pdo = migrationConnection($databaseName);
    ensureMigrationTable($pdo);

    $lockName = 'gharsquare_migrations_' . $databaseName;
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
    $lockStmt->execute([':lock_name' => $lockName]);

    if ((int) $lockStmt->fetchColumn() !== 1) {
        throw new RuntimeException('Unable to acquire the database migration lock.');
    }

    try {
        $files = migrationFiles();
        $applied = appliedMigrations($pdo);
        validateAppliedChecksums($files, $applied);
        $markApplied = migrationOption('--mark-applied');

        if ($markApplied !== null) {
            $matchingPath = null;
            foreach ($files as $path) {
                if (basename($path) === $markApplied) {
                    $matchingPath = $path;
                    break;
                }
            }

            if ($matchingPath === null) {
                throw new RuntimeException('Migration file not found: ' . $markApplied);
            }
            if (isset($applied[$markApplied])) {
                echo "Already recorded: {$markApplied}" . PHP_EOL;
                exit(0);
            }

            recordMigration($pdo, $markApplied, migrationChecksum($matchingPath), 0);
            echo "Marked as applied: {$markApplied}" . PHP_EOL;
            exit(0);
        }

        $pending = array_values(array_filter(
            $files,
            static fn (string $path): bool => !isset($applied[basename($path)])
        ));

        if (migrationOption('--status') !== null || migrationOption('--dry-run') !== null) {
            echo 'Database: ' . $databaseName . PHP_EOL;
            echo 'Applied: ' . count($applied) . PHP_EOL;
            echo 'Pending: ' . count($pending) . PHP_EOL;

            foreach ($pending as $path) {
                echo '  [pending] ' . basename($path) . PHP_EOL;
            }

            exit(0);
        }

        if ($pending === []) {
            echo 'No pending migrations.' . PHP_EOL;
            exit(0);
        }

        foreach ($pending as $path) {
            $filename = basename($path);
            $sql = file_get_contents($path);
            if ($sql === false || trim($sql) === '') {
                throw new RuntimeException('Migration is empty or unreadable: ' . $filename);
            }

            echo 'Applying ' . $filename . ' ... ';
            $startedAt = hrtime(true);
            $pdo->exec($sql);
            $executionMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            recordMigration($pdo, $filename, migrationChecksum($path), $executionMs);
            echo 'done (' . $executionMs . ' ms)' . PHP_EOL;
        }
    } finally {
        releaseMigrationLock($pdo, $lockName);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
