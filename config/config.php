<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Env.php';

Env::load(dirname(__DIR__) . '/.env');

$host = Env::require('DB_HOST');
$database = Env::require('DB_DATABASE');
$username = Env::require('DB_USERNAME');
$password = Env::get('DB_PASSWORD', '') ?? '';
$port = filter_var(
    Env::get('DB_PORT', '3306'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 65535]]
);

if ($port === false) {
    throw new RuntimeException('DB_PORT must be an integer between 1 and 65535.');
}

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $host,
    $port,
    $database
);

try {
    return new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    throw new RuntimeException(
        'Unable to connect to the database. Verify the database settings in .env.',
        0,
        $exception
    );
}

