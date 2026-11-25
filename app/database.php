<?php

function database(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = getenv('APP_DATABASE_DSN');

    if ($dsn !== false && $dsn !== '') {
        if (!str_starts_with($dsn, 'mysql:') && !(in_array(getenv('APP_ENV'), ['testing', 'demo'], true) && str_starts_with($dsn, 'sqlite:'))) {
            throw new RuntimeException('Configuration de base invalide.');
        }

        $connection = new PDO($dsn, getenv('APP_DATABASE_USER') ?: null, getenv('APP_DATABASE_PASSWORD') ?: null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } else {
        if (!is_file(dirname(__DIR__) . '/config/database.php')) {
            throw new RuntimeException('Configurez la connexion locale avant de démarrer le site.');
        }

        require dirname(__DIR__) . '/config/database.php';
        $connection = $pdo;
    }

    if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $connection->exec('PRAGMA foreign_keys = ON');
        $connection->exec('PRAGMA busy_timeout = 10000');
    }

    return $connection;
}

function databaseTransaction(PDO $connection, callable $operation): mixed
{
    $sqlite = $connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $sqlite ? $connection->exec('BEGIN IMMEDIATE') : $connection->beginTransaction();

    try {
        $result = $operation();
        $sqlite ? $connection->exec('COMMIT') : $connection->commit();

        return $result;
    } catch (Throwable $error) {
        $sqlite ? $connection->exec('ROLLBACK') : $connection->rollBack();
        throw $error;
    }
}

function lockSuffix(PDO $connection): string
{
    return $connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
}
