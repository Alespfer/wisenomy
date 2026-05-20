<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Returns the PDO singleton. Picks PostgreSQL when DATABASE_URL is set,
// otherwise falls back to local SQLite (development).
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $url = env('DATABASE_URL');
    if ($url !== null && $url !== '') {
        $pdo = db_connect_postgres($url);
    } else {
        $pdo = db_connect_sqlite();
    }
    return $pdo;
}

function db_driver(): string {
    return db()->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function db_connect_sqlite(): PDO {
    $path = __DIR__ . '/../data/app.sqlite';
    @mkdir(dirname($path), 0755, true);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA foreign_keys=ON');
    $pdo->exec('PRAGMA busy_timeout=5000');

    $pdo->exec(file_get_contents(__DIR__ . '/../schema.sql'));
    db_migrate_sqlite($pdo);
    return $pdo;
}

// Lightweight migrations for SQLite installs that predate newer columns.
function db_migrate_sqlite(PDO $pdo): void {
    $cols  = $pdo->query("PRAGMA table_info('users')")->fetchAll();
    $names = array_column($cols, 'name');
    if (!in_array('email_verified_at', $names, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN email_verified_at TEXT NULL');
    }
}

function db_connect_postgres(string $url): PDO {
    // Parse "postgres://user:pass@host:port/dbname?sslmode=require"
    $p = parse_url($url);
    if ($p === false || empty($p['host']) || empty($p['path'])) {
        throw new RuntimeException('Invalid DATABASE_URL');
    }
    $host = $p['host'];
    $port = $p['port'] ?? 5432;
    $db   = ltrim($p['path'], '/');
    $user = $p['user'] ?? '';
    $pass = $p['pass'] ?? '';
    parse_str($p['query'] ?? '', $q);
    $sslmode = $q['sslmode'] ?? 'require';

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$sslmode";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec(file_get_contents(__DIR__ . '/../schema.postgres.sql'));
    return $pdo;
}

// --- Cross-driver SQL helpers ---

// Returns a SQL expression for "current timestamp".
function sql_now(): string {
    return db_driver() === 'pgsql' ? 'CURRENT_TIMESTAMP' : "datetime('now')";
}

// Returns a SQL expression for "current timestamp minus N units".
// Unit must be one of: minutes, hours, days.
function sql_now_minus(int $n, string $unit): string {
    $n = (int)$n;
    if (db_driver() === 'pgsql') {
        return "CURRENT_TIMESTAMP - INTERVAL '$n $unit'";
    }
    return "datetime('now', '-$n $unit')";
}

// Returns the last inserted ID for the given table.
// SQLite uses the implicit rowid; Postgres needs the sequence name.
function last_insert_id(string $table): int {
    if (db_driver() === 'pgsql') {
        return (int)db()->lastInsertId($table . '_id_seq');
    }
    return (int)db()->lastInsertId();
}
