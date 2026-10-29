<?php

declare(strict_types=1);

function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $config = require dirname(__DIR__) . '/config/config.php';
    $database = $config['database'];
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $database['host'],
        $database['name'],
        $database['charset']
    );

    try {
        $connection = new PDO($dsn, $database['username'], $database['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        throw $exception;
    }

    return $connection;
}
