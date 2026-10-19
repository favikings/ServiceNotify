<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$rootPath = dirname(__DIR__);

/** @var PDO $pdo */
$pdo = require $rootPath . '/config/config.php';
$migrationFiles = glob(__DIR__ . '/migrations/*.sql');

if ($migrationFiles === false) {
    fwrite(STDERR, "Unable to read database migrations.\n");
    exit(1);
}

sort($migrationFiles, SORT_STRING);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);
$wasApplied = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration = :migration');
$recordMigration = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');

foreach ($migrationFiles as $migrationFile) {
    $migrationName = basename($migrationFile);
    $wasApplied->execute(['migration' => $migrationName]);

    if ($wasApplied->fetchColumn() !== false) {
        fwrite(STDOUT, sprintf("Skipped %s (already applied)\n", $migrationName));
        continue;
    }

    $sql = file_get_contents($migrationFile);

    if ($sql === false) {
        fwrite(STDERR, sprintf("Unable to read %s.\n", basename($migrationFile)));
        exit(1);
    }

    $pdo->exec($sql);
    $recordMigration->execute(['migration' => $migrationName]);
    fwrite(STDOUT, sprintf("Applied %s\n", $migrationName));
}

fwrite(STDOUT, "Database migrations complete.\n");
