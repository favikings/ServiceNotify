<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$rootPath = dirname(__DIR__);

require_once $rootPath . '/src/Env.php';
require_once $rootPath . '/src/DeliveryResult.php';
require_once $rootPath . '/src/PushGateway.php';
require_once $rootPath . '/src/SmsGateway.php';
require_once $rootPath . '/src/HttpClient.php';
require_once $rootPath . '/src/FcmClient.php';
require_once $rootPath . '/src/AfricasTalkingClient.php';
require_once $rootPath . '/src/NotificationRepository.php';
require_once $rootPath . '/src/NotificationLogger.php';
require_once $rootPath . '/src/NotificationEngine.php';

$logger = new NotificationLogger($rootPath . '/logs/notifications.log');

try {
    /** @var PDO $pdo */
    $pdo = require $rootPath . '/config/config.php';
    $timezoneName = Env::get('NOTIFICATION_TIMEZONE', 'Africa/Lagos') ?? 'Africa/Lagos';
    $timezone = new DateTimeZone($timezoneName);
    $credentialsPath = Env::get('GOOGLE_APPLICATION_CREDENTIALS', '') ?? '';
    $projectId = Env::get('FIREBASE_PROJECT_ID');
    $http = new CurlHttpClient();
    $repository = new NotificationRepository($pdo);
    $runDate = new DateTimeImmutable('now', $timezone);
    $logger->log('info', 'cron_started', 'Daily notification cron started.', [
        'scheduled_for' => $runDate->format('Y-m-d'),
        'timezone' => $timezoneName,
    ], $runDate);
    $engine = new NotificationEngine(
        $repository,
        new FcmClient($http, $credentialsPath, $projectId),
        new AfricasTalkingClient(
            $http,
            Env::get('AFRICASTALKING_USERNAME', '') ?? '',
            Env::get('AFRICASTALKING_API_KEY', '') ?? '',
            Env::get('AFRICASTALKING_SENDER_ID'),
            strtolower(Env::get('AFRICASTALKING_ENVIRONMENT', 'sandbox') ?? 'sandbox') === 'sandbox'
        ),
        $logger
    );
    $summary = $engine->run($runDate);
    $logger->log('info', 'cron_completed', 'Daily notification cron completed.', [
        'scheduled_for' => $runDate->format('Y-m-d'),
        'due_reminders' => $summary['due_reminders'],
        'attempted' => $summary['attempted'],
        'succeeded' => $summary['succeeded'],
        'failed' => $summary['failed'],
        'skipped' => $summary['skipped'],
    ]);

    fwrite(STDOUT, sprintf(
        "Notification run %s: %d due reminder(s), %d attempt(s), %d succeeded, %d failed, %d skipped.\n",
        $runDate->format(DateTimeInterface::ATOM),
        $summary['due_reminders'],
        $summary['attempted'],
        $summary['succeeded'],
        $summary['failed'],
        $summary['skipped']
    ));
} catch (Throwable $exception) {
    $logger->log('error', 'cron_failed', 'Daily notification cron failed.', [
        'error_type' => $exception::class,
        'error_code' => (string) $exception->getCode(),
    ]);
    fwrite(STDERR, 'Notification run failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
