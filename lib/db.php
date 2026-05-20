<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $path = __DIR__ . '/../data/app.sqlite';
    @mkdir(dirname($path), 0755, true);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA foreign_keys=ON');
    $pdo->exec('PRAGMA busy_timeout=5000'); // wait up to 5s if another process holds a lock

    $schema = file_get_contents(__DIR__ . '/../schema.sql');
    $pdo->exec($schema);

    return $pdo;
}
