<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/NotificationLogger.php';
require_once dirname(__DIR__) . '/src/LogRepository.php';

$failures = [];

function expectLogging(bool $condition, string $message): void
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
$pdo->exec(
    'CREATE TABLE clients (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, name TEXT NOT NULL);
     CREATE TABLE services (id INTEGER PRIMARY KEY, client_id INTEGER NOT NULL, type TEXT NOT NULL, provider TEXT);
     CREATE TABLE notifications (
        id INTEGER PRIMARY KEY, service_id INTEGER NOT NULL, type TEXT NOT NULL, status TEXT NOT NULL,
        message TEXT, scheduled_for DATE NOT NULL, attempt INTEGER NOT NULL, sent_at DATETIME NOT NULL
     );'
);
$pdo->exec(
    "INSERT INTO clients VALUES (1, 1, 'Atlas'), (2, 2, 'Private');
     INSERT INTO services VALUES (1, 1, 'domain', 'Cloudflare'), (2, 2, 'hosting', 'Hidden');
     INSERT INTO notifications VALUES
        (1, 1, 'sms', 'failure', 'First attempt failed.', '2030-01-01', 1, '2030-01-01 08:00:00'),
        (2, 1, 'sms', 'success', 'Retry succeeded.', '2030-01-01', 2, '2030-01-01 08:00:01'),
        (3, 2, 'push', 'failure', 'Private failure.', '2030-01-01', 1, '2030-01-01 08:00:02');"
);

$repository = new LogRepository($pdo);
$summary = $repository->summaryForUser(1);
expectLogging($summary === ['total' => 2, 'success' => 1, 'failure' => 1, 'retries' => 1], 'Log summary must be scoped to the authenticated user.');
$records = $repository->notificationsForUser(1);
expectLogging(count($records) === 2 && !in_array('Private', array_column($records, 'client_name'), true), 'The delivery ledger must not expose another user\'s records.');
$successfulSms = $repository->notificationsForUser(1, 'success', 'sms');
expectLogging(count($successfulSms) === 1 && (int) $successfulSms[0]['attempt'] === 2, 'Status and channel filters should combine safely.');

$logPath = tempnam(sys_get_temp_dir(), 'servicenotify-logging-');
if ($logPath === false) {
    throw new RuntimeException('Unable to create the logging test file.');
}
$logger = new NotificationLogger($logPath);
$time = new DateTimeImmutable('2030-01-01T08:00:00+01:00');
$logger->log('info', 'cron_started', 'Daily cron started.', ['scheduled_for' => '2030-01-01'], $time);
$logger->log('error', 'notification_attempt', "Failed\ncleanly", ['user_id' => 1, 'phone' => '+2348000000000'], $time);
$logger->log('error', 'notification_attempt', 'Private failure.', ['user_id' => 2], $time);
$entries = $logger->entriesForUser(1);
$rawLog = file_get_contents($logPath);
expectLogging(count($entries) === 2, 'Users should see generic cron events and only their own notification events.');
expectLogging(is_string($rawLog) && !str_contains($rawLog, '+2348000000000') && str_contains($rawLog, '[redacted]'), 'Sensitive context fields must be redacted from the file log.');
expectLogging(is_string($rawLog) && !str_contains($rawLog, "Failed\ncleanly"), 'File-log messages must remain single-line JSON records.');

$logsController = file_get_contents(dirname(__DIR__) . '/public/logs.php');
$logsView = file_get_contents(dirname(__DIR__) . '/views/logs.php');
$navigation = file_get_contents(dirname(__DIR__) . '/views/partials/app-top.php');
expectLogging(is_string($logsController) && str_contains($logsController, 'requireAuthenticatedUser()'), 'The Logs page must require authentication.');
expectLogging(is_string($logsView) && str_contains($logsView, 'Notification ledger') && str_contains($logsView, 'Cron monitor'), 'The Logs view should present both database and file records.');
expectLogging(is_string($navigation) && str_contains($navigation, "appUrl('logs.php')"), 'Primary navigation should expose the Logs view.');

unlink($logPath);

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }
    exit(1);
}

fwrite(STDOUT, "PASS: Structured file logging and the user-scoped monitoring view meet Prompt 9 acceptance checks.\n");
