<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/DeliveryResult.php';
require_once dirname(__DIR__) . '/src/PushGateway.php';
require_once dirname(__DIR__) . '/src/SmsGateway.php';
require_once dirname(__DIR__) . '/src/NotificationRepository.php';
require_once dirname(__DIR__) . '/src/NotificationLogger.php';
require_once dirname(__DIR__) . '/src/NotificationEngine.php';

$failures = [];

function expectNotification(bool $condition, string $message): void
{
    global $failures;

    if (!$condition) {
        $failures[] = $message;
    }
}

final class FakePushGateway implements PushGateway
{
    /** @var list<string> */
    public array $tokens = [];

    public function send(string $token, string $title, string $body, array $data = []): DeliveryResult
    {
        $this->tokens[] = $token;

        return $token === 'invalid-token'
            ? DeliveryResult::failure('FCM token is unregistered.', true)
            : DeliveryResult::success('FCM mock accepted the notification.');
    }
}

final class FakeSmsGateway implements SmsGateway
{
    public int $calls = 0;

    public function send(string $phone, string $message): DeliveryResult
    {
        $this->calls++;

        return $this->calls === 1
            ? DeliveryResult::failure('Africa\'s Talking mock temporary failure.')
            : DeliveryResult::success('Africa\'s Talking mock accepted the SMS.');
    }
}

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(
    'CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        settings TEXT DEFAULT NULL
    );
    CREATE TABLE clients (
        id INTEGER PRIMARY KEY,
        user_id INTEGER NOT NULL,
        name VARCHAR(150) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    CREATE TABLE services (
        id INTEGER PRIMARY KEY,
        client_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        provider VARCHAR(150) DEFAULT NULL,
        expiry_date DATE NOT NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    );
    CREATE TABLE reminders (
        id INTEGER PRIMARY KEY,
        service_id INTEGER NOT NULL,
        frequency INTEGER NOT NULL,
        channels VARCHAR(20) NOT NULL,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    );
    CREATE TABLE notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_id INTEGER NOT NULL,
        reminder_id INTEGER NOT NULL,
        type VARCHAR(10) NOT NULL,
        status VARCHAR(10) NOT NULL,
        message TEXT,
        scheduled_for DATE NOT NULL,
        attempt INTEGER NOT NULL,
        sent_at DATETIME NOT NULL,
        UNIQUE (reminder_id, type, scheduled_for, attempt),
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
        FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE CASCADE
    );'
);

$validSettings = json_encode([
    'fcm_tokens' => ['valid-token'],
    'sms_phone' => '+2348012345678',
], JSON_THROW_ON_ERROR);
$invalidSettings = json_encode([
    'fcm_tokens' => ['invalid-token'],
], JSON_THROW_ON_ERROR);
$insertUser = $pdo->prepare('INSERT INTO users (id, name, settings) VALUES (:id, :name, :settings)');
$insertUser->execute(['id' => 1, 'name' => 'Ada', 'settings' => $validSettings]);
$insertUser->execute(['id' => 2, 'name' => 'Ben', 'settings' => $invalidSettings]);
$pdo->exec("INSERT INTO clients (id, user_id, name) VALUES (1, 1, 'Atlas Studio'), (2, 2, 'Beacon Ltd')");
$pdo->exec(
    "INSERT INTO services (id, client_id, type, provider, expiry_date) VALUES
        (1, 1, 'domain', 'Cloudflare', '2030-01-08'),
        (2, 2, 'hosting', NULL, '2030-01-02'),
        (3, 1, 'maintenance', NULL, '2030-01-09')"
);
$pdo->exec(
    "INSERT INTO reminders (id, service_id, frequency, channels) VALUES
        (1, 1, 7, 'push,sms'),
        (2, 2, 1, 'push'),
        (3, 3, 7, 'sms')"
);

$push = new FakePushGateway();
$sms = new FakeSmsGateway();
$repository = new NotificationRepository($pdo);
$notificationLogPath = tempnam(sys_get_temp_dir(), 'servicenotify-notify-log-');
if ($notificationLogPath === false) {
    throw new RuntimeException('Unable to create the notification file-log fixture.');
}
$fileLogger = new NotificationLogger($notificationLogPath);
$engine = new NotificationEngine($repository, $push, $sms, $fileLogger);
$runDate = new DateTimeImmutable('2030-01-01 08:00:00', new DateTimeZone('Africa/Lagos'));
$summary = $engine->run($runDate);

expectNotification($summary['due_reminders'] === 2, 'Only reminders whose expiry exactly matches their frequency should be due.');
expectNotification($summary['attempted'] === 4, 'Push attempts and both SMS attempts should be recorded.');
expectNotification($summary['succeeded'] === 2 && $summary['failed'] === 2, 'Mock delivery outcomes should be summarized accurately.');
expectNotification($sms->calls === 2, 'A failed SMS should be retried exactly once.');
$attemptedTokens = $push->tokens;
sort($attemptedTokens);
expectNotification($attemptedTokens === ['invalid-token', 'valid-token'], 'Each due push token should be attempted once.');

$rows = $pdo->query('SELECT type, status, attempt, scheduled_for, message FROM notifications ORDER BY id')->fetchAll();
expectNotification(count($rows) === 4, 'Every provider attempt should have a notification log row.');
$smsRows = array_values(array_filter($rows, static fn (array $row): bool => $row['type'] === 'sms'));
expectNotification(
    count($smsRows) === 2 && $smsRows[0]['status'] === 'failure' && (int) $smsRows[0]['attempt'] === 1,
    'The initial SMS failure should be logged as attempt one.'
);
expectNotification(
    count($smsRows) === 2 && $smsRows[1]['status'] === 'success' && (int) $smsRows[1]['attempt'] === 2,
    'The successful SMS retry should be logged as attempt two.'
);
expectNotification(
    array_unique(array_column($rows, 'scheduled_for')) === ['2030-01-01'],
    'Logs should retain the reminder due date used for idempotency.'
);

$updatedSettings = json_decode((string) $pdo->query('SELECT settings FROM users WHERE id = 2')->fetchColumn(), true);
expectNotification(($updatedSettings['fcm_tokens'] ?? null) === [], 'An invalid FCM token should be removed from user settings.');

$secondSummary = $engine->run($runDate);
expectNotification($secondSummary['attempted'] === 0, 'A repeated daily run should not create duplicate delivery attempts.');
expectNotification($secondSummary['skipped'] === 3, 'Previously completed channel schedules should be skipped.');
expectNotification((int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn() === 4, 'Idempotent reruns should leave notification logs unchanged.');

$adaFileEntries = $fileLogger->entriesForUser(1);
$adaAttemptEvents = array_filter($adaFileEntries, static fn (array $entry): bool => $entry['event'] === 'notification_attempt');
$adaRetryEvents = array_filter($adaFileEntries, static fn (array $entry): bool => $entry['event'] === 'notification_retry');
expectNotification(count($adaAttemptEvents) === 3, 'The file log should contain every notification attempt owned by the user.');
expectNotification(count($adaRetryEvents) === 1, 'The file log should explicitly record the SMS retry.');
expectNotification(
    count(array_filter($adaFileEntries, static fn (array $entry): bool => ($entry['context']['user_id'] ?? null) === 2)) === 0,
    'Reading file logs for one user must exclude another user\'s entries.'
);

$pdo->exec('DELETE FROM services WHERE id = 1');
expectNotification((int) $pdo->query('SELECT COUNT(*) FROM notifications WHERE service_id = 1')->fetchColumn() === 0, 'Deleting a service should cascade its notification logs.');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

unlink($notificationLogPath);

fwrite(STDOUT, "PASS: Notification scheduling, delivery logging, retry, token cleanup, and idempotency meet Prompt 7 acceptance checks.\n");
