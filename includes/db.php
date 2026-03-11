<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Returns a PDO connection configured from .env variables.
 */
function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $env = load_env();
    $driver = $env['DB_DRIVER'] ?? 'mysql';
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? ($driver === 'pgsql' ? '5432' : '3306');
    $dbName = $env['DB_NAME'] ?? 'londry';
    $user = $env['DB_USER'] ?? 'root';
    $password = $env['DB_PASS'] ?? '';

    if (!in_array($driver, ['mysql', 'pgsql'], true)) {
        throw new RuntimeException('Unsupported DB_DRIVER. Use mysql or pgsql.');
    }

    $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $driver, $host, $port, $dbName);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $user, $password, $options);
    return $pdo;
}
