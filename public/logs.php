<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array<string, int>, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';

$user = requireAuthenticatedUser();
$statusInput = RequestInput::getString('status');
$typeInput = RequestInput::getString('type');
$status = in_array($statusInput, ['success', 'failure'], true) ? $statusInput : null;
$type = in_array($typeInput, ['push', 'sms'], true) ? $typeInput : null;
$filterErrors = [];
if ($statusInput !== '' && $status === null) {
    $filterErrors[] = 'Choose a valid delivery status.';
}
if ($typeInput !== '' && $type === null) {
    $filterErrors[] = 'Choose a valid notification channel.';
}

$repository = new LogRepository($app['pdo']);
$summary = $repository->summaryForUser($user['id']);
$notificationLogs = $repository->notificationsForUser($user['id'], $status, $type);
$fileLogs = (new NotificationLogger($app['root_path'] . '/logs/notifications.log'))->entriesForUser($user['id']);
$pageTitle = 'Activity logs';
$activeNav = 'logs';

require $app['root_path'] . '/views/logs.php';
