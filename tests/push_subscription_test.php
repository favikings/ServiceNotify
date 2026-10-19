<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/ValidationException.php';
require_once dirname(__DIR__) . '/src/UserRepository.php';
require_once dirname(__DIR__) . '/src/PushSubscriptionService.php';

$failures = [];

function expectPushSubscription(bool $condition, string $message): void
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
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, settings TEXT DEFAULT NULL)');
$pdo->exec("INSERT INTO users (id, settings) VALUES
    (1, '{\"sms_phone\":\"+2348012345678\",\"fcm_tokens\":[\"existing-destination\"]}'),
    (2, '{\"fcm_tokens\":[\"private-destination\"]}')");

$users = new UserRepository($pdo);
$subscriptions = new PushSubscriptionService($users);
$subscriptions->enroll(1, 'new-browser-destination');
$subscriptions->enroll(1, 'new-browser-destination');
$settings = $users->settingsForUser(1);
expectPushSubscription(($settings['sms_phone'] ?? null) === '+2348012345678', 'Push enrollment must preserve unrelated notification settings.');
expectPushSubscription(($settings['fcm_tokens'] ?? null) === ['existing-destination', 'new-browser-destination'], 'Enrollment should append a unique browser destination once.');

$subscriptions->revoke(1, 'new-browser-destination');
$settings = $users->settingsForUser(1);
expectPushSubscription(($settings['fcm_tokens'] ?? null) === ['existing-destination'], 'Revocation should remove only the requested browser destination.');
expectPushSubscription(($users->settingsForUser(2)['fcm_tokens'] ?? null) === ['private-destination'], 'Subscription changes must not affect another user.');

try {
    $subscriptions->enroll(1, "bad\ntoken");
    expectPushSubscription(false, 'Control characters in Push destinations should be rejected.');
} catch (ValidationException) {
    expectPushSubscription(true, 'Invalid Push destination rejected.');
}

$endpoint = file_get_contents(dirname(__DIR__) . '/public/push-subscription.php');
$pushScript = file_get_contents(dirname(__DIR__) . '/public/assets/js/push.js');
expectPushSubscription(is_string($endpoint) && str_contains($endpoint, 'HTTP_X_CSRF_TOKEN') && str_contains($endpoint, 'Session::user()'), 'The subscription endpoint must require CSRF and authentication.');
expectPushSubscription(is_string($pushScript) && str_contains($pushScript, 'Notification.requestPermission()'), 'Browser permission must be requested from an explicit user action.');
expectPushSubscription(is_string($pushScript) && str_contains($pushScript, 'registerMessaging') && str_contains($pushScript, 'unregisterMessaging'), 'The browser should support FCM enrollment and revocation.');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }
    exit(1);
}

fwrite(STDOUT, "PASS: Browser Push enrollment and revocation meet Prompt 10 ownership and consent checks.\n");
