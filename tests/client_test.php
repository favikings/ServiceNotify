<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/ValidationException.php';
require_once dirname(__DIR__) . '/src/ClientNotFoundException.php';
require_once dirname(__DIR__) . '/src/ClientRepository.php';
require_once dirname(__DIR__) . '/src/ClientService.php';

$failures = [];

function expectClient(bool $condition, string $message): void
{
    global $failures;

    if (!$condition) {
        $failures[] = $message;
    }
}

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(
    'CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL
    );
    CREATE TABLE clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name VARCHAR(150) NOT NULL,
        organization VARCHAR(150) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        email VARCHAR(150) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    CREATE INDEX idx_clients_user_id ON clients(user_id);
    CREATE TABLE services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER NOT NULL,
        name VARCHAR(150) NOT NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )'
);
$pdo->exec("INSERT INTO users (name) VALUES ('Ada'), ('Ben')");

$repository = new ClientRepository($pdo);
$service = new ClientService($repository);

$zaraId = $service->create(1, [
    'name' => '  Zara & Co  ',
    'organization' => '  Northstar Studio ',
    'phone' => '+234 (801) 234-5678',
    'email' => '  HELLO@EXAMPLE.COM ',
]);
$zara = $repository->findForUser($zaraId, 1);

expectClient($zara !== null, 'A valid client should be created for the authenticated user.');
expectClient($zara !== null && $zara['name'] === 'Zara & Co', 'Client names should be trimmed.');
expectClient($zara !== null && $zara['organization'] === 'Northstar Studio', 'Organizations should be trimmed.');
expectClient($zara !== null && $zara['email'] === 'hello@example.com', 'Client email should be normalized to lowercase.');

$alphaId = $service->create(1, [
    'name' => 'Alpha Client',
    'organization' => ' ',
    'phone' => '',
    'email' => '',
]);
$alpha = $repository->findForUser($alphaId, 1);
expectClient(
    $alpha !== null && $alpha['organization'] === null && $alpha['phone'] === null && $alpha['email'] === null,
    'Blank optional values should be stored as null.'
);

$otherUserId = $service->create(2, ['name' => 'Private Client']);
$list = $repository->allForUser(1);
expectClient(count($list) === 2, 'The client list should include only the current user records.');
expectClient($list[0]['name'] === 'Alpha Client' && $list[1]['name'] === 'Zara & Co', 'Clients should be sorted alphabetically.');
expectClient($repository->countForUser(1) === 2, 'The client count should be scoped to the current user.');

$invalidEmailRejected = false;

try {
    $service->create(1, ['name' => 'Invalid Email', 'email' => 'not-an-email']);
} catch (ValidationException $exception) {
    $invalidEmailRejected = isset($exception->errors()['email']);
}

expectClient($invalidEmailRejected, 'Invalid client email should be rejected.');

$invalidPhoneRejected = false;

try {
    $service->create(1, ['name' => 'Invalid Phone', 'phone' => 'call-me-now']);
} catch (ValidationException $exception) {
    $invalidPhoneRejected = isset($exception->errors()['phone']);
}

expectClient($invalidPhoneRejected, 'Invalid phone numbers should be rejected.');

$service->update($alphaId, 1, [
    'name' => 'Alpha Renewed',
    'organization' => 'ACME',
    'phone' => '0801 234 5678',
    'email' => 'alpha@example.com',
]);
$updated = $repository->findForUser($alphaId, 1);
expectClient($updated !== null && $updated['name'] === 'Alpha Renewed', 'An owner should be able to update their client.');

expectClient($repository->findForUser($otherUserId, 1) === null, 'A user should not be able to read another user client.');

$foreignUpdateRejected = false;

try {
    $service->update($otherUserId, 1, ['name' => 'Stolen']);
} catch (ClientNotFoundException) {
    $foreignUpdateRejected = true;
}

expectClient($foreignUpdateRejected, 'A user should not be able to update another user client.');

$foreignDeleteRejected = false;

try {
    $service->delete($otherUserId, 1);
} catch (ClientNotFoundException) {
    $foreignDeleteRejected = true;
}

expectClient($foreignDeleteRejected, 'A user should not be able to delete another user client.');
expectClient($repository->findForUser($otherUserId, 2) !== null, 'A failed cross-user deletion should preserve the client.');

$insertService = $pdo->prepare('INSERT INTO services (client_id, name) VALUES (:client_id, :name)');
$insertService->execute(['client_id' => $zaraId, 'name' => 'Domain renewal']);
$service->delete($zaraId, 1);

$remainingServices = (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
expectClient($repository->findForUser($zaraId, 1) === null, 'An owner should be able to delete their client.');
expectClient($remainingServices === 0, 'Deleting a client should cascade to dependent services.');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: Client management meets the Prompt 3 acceptance checks.\n");
