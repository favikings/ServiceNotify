<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/ValidationException.php';
require_once dirname(__DIR__) . '/src/ClientRepository.php';
require_once dirname(__DIR__) . '/src/ServiceNotFoundException.php';
require_once dirname(__DIR__) . '/src/ServiceRepository.php';
require_once dirname(__DIR__) . '/src/ReminderRepository.php';
require_once dirname(__DIR__) . '/src/ReminderService.php';
require_once dirname(__DIR__) . '/src/ServiceManager.php';

$failures = [];

function expectReminder(bool $condition, string $message): void
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
    CREATE TABLE services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        provider VARCHAR(150) DEFAULT NULL,
        expiry_date DATE NOT NULL,
        cost DECIMAL(10,2) DEFAULT NULL,
        currency CHAR(3) DEFAULT NULL,
        billing_cycle VARCHAR(20) DEFAULT "yearly",
        payment_account VARCHAR(150) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    );
    CREATE TABLE reminders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_id INTEGER NOT NULL,
        frequency INTEGER NOT NULL,
        channels VARCHAR(20) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (service_id, frequency),
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    )'
);
$pdo->exec("INSERT INTO users (name) VALUES ('Ada'), ('Ben')");
$pdo->exec("INSERT INTO clients (user_id, name) VALUES (1, 'Atlas'), (2, 'Private')");

$clients = new ClientRepository($pdo);
$services = new ServiceRepository($pdo);
$reminders = new ReminderRepository($pdo);
$reminderService = new ReminderService($reminders);
$manager = new ServiceManager($services, $clients, $reminderService, $pdo);
$serviceInput = [
    'client_id' => 1,
    'type' => 'domain',
    'provider' => 'Cloudflare',
    'expiry_date' => '2027-12-01',
    'cost' => '',
    'currency' => '',
    'billing_cycle' => 'yearly',
    'payment_account' => '',
];
$initialReminders = [
    'presets' => [
        '30' => ['enabled' => '1', 'channels' => ['push']],
        '7' => ['enabled' => '1', 'channels' => ['push', 'sms']],
        '1' => ['channels' => ['sms']],
    ],
];

$serviceId = $manager->create(1, $serviceInput, $initialReminders);
$stored = $reminders->allForServiceForUser($serviceId, 1);
expectReminder(count($stored) === 2, 'Enabled reminder presets should be saved.');
expectReminder($stored[0]['frequency'] === 30 && $stored[0]['channels'] === ['push'], 'The 30-day Push reminder should persist.');
expectReminder($stored[1]['frequency'] === 7 && $stored[1]['channels'] === ['push', 'sms'], 'A reminder should support Push and SMS together.');
expectReminder($reminders->allForServiceForUser($serviceId, 2) === [], 'Another user should not read reminder configuration.');
expectReminder(($services->findForUser($serviceId, 1)['reminder_count'] ?? 0) === 2, 'Service summaries should expose the active reminder count.');

$replacement = [
    'presets' => [
        '1' => ['enabled' => '1', 'channels' => ['sms']],
    ],
];
$manager->update($serviceId, 1, array_replace($serviceInput, ['provider' => 'Updated provider']), $replacement);
$stored = $reminders->allForServiceForUser($serviceId, 1);
expectReminder(count($stored) === 1 && $stored[0]['frequency'] === 1 && $stored[0]['channels'] === ['sms'], 'Editing should replace stale reminders with the submitted set.');

$invalidRejected = false;

try {
    $manager->update(
        $serviceId,
        1,
        array_replace($serviceInput, ['provider' => 'Should not save']),
        ['presets' => ['7' => ['enabled' => '1', 'channels' => []]]]
    );
} catch (ValidationException $exception) {
    $invalidRejected = isset($exception->errors()['reminder_7']);
}

expectReminder($invalidRejected, 'An enabled reminder without a channel should be rejected.');
expectReminder(($services->findForUser($serviceId, 1)['provider'] ?? null) === 'Updated provider', 'Invalid reminder input should roll back the service edit.');
expectReminder(count($reminders->allForServiceForUser($serviceId, 1)) === 1, 'Invalid reminder input should preserve existing reminders.');

$servicesBeforeInvalidCreate = $services->countForUser(1);

try {
    $manager->create(
        1,
        array_replace($serviceInput, ['provider' => 'Invalid create']),
        ['presets' => ['30' => ['enabled' => '1']]]
    );
} catch (ValidationException) {
    // Expected.
}

expectReminder($services->countForUser(1) === $servicesBeforeInvalidCreate, 'Invalid reminders should prevent the service from being created.');

$manager->update($serviceId, 1, $serviceInput, []);
expectReminder($reminders->allForServiceForUser($serviceId, 1) === [], 'Submitting no enabled presets should remove every reminder.');

$manager->update($serviceId, 1, $serviceInput, $initialReminders);
$manager->delete($serviceId, 1);
expectReminder((int) $pdo->query('SELECT COUNT(*) FROM reminders')->fetchColumn() === 0, 'Deleting a service should cascade to its reminders.');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: Reminder configuration meets the Prompt 5 acceptance checks.\n");
