<?php

declare(strict_types=1);

final class NotificationEngine
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly PushGateway $push,
        private readonly SmsGateway $sms,
        private readonly ?NotificationLogger $logger = null
    ) {
    }

    /** @return array{due_reminders:int,attempted:int,succeeded:int,failed:int,skipped:int} */
    public function run(DateTimeImmutable $runDate): array
    {
        $scheduledFor = $runDate->format('Y-m-d');
        if (!$this->notifications->acquireDailyLock($scheduledFor)) {
            throw new RuntimeException('Another notification job is already running for ' . $scheduledFor . '.');
        }

        $summary = [
            'due_reminders' => 0,
            'attempted' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        try {
            $reminders = $this->notifications->dueReminders($runDate);
            $summary['due_reminders'] = count($reminders);

            foreach ($reminders as $reminder) {
                $message = $this->messageFor($reminder);
                foreach ($reminder['channels'] as $channel) {
                    if ($channel === 'push') {
                        $this->deliverPush($reminder, $scheduledFor, $runDate, $message, $summary);
                    } elseif ($channel === 'sms') {
                        $this->deliverSms($reminder, $scheduledFor, $runDate, $message, $summary);
                    }
                }
            }
        } finally {
            $this->notifications->releaseDailyLock($scheduledFor);
        }

        return $summary;
    }

    /**
     * @param array<string, mixed> $reminder
     * @param array{title:string,body:string} $message
     * @param array{due_reminders:int,attempted:int,succeeded:int,failed:int,skipped:int} $summary
     */
    private function deliverPush(array $reminder, string $scheduledFor, DateTimeImmutable $sentAt, array $message, array &$summary): void
    {
        if ($this->notifications->attemptsFor((int) $reminder['reminder_id'], 'push', $scheduledFor) !== []) {
            $summary['skipped']++;
            return;
        }

        $notificationId = $this->notifications->reserveAttempt(
            (int) $reminder['service_id'],
            (int) $reminder['reminder_id'],
            'push',
            $scheduledFor,
            1,
            $sentAt
        );
        if ($notificationId === null) {
            $summary['skipped']++;
            return;
        }

        $tokens = $reminder['fcm_tokens'];
        if (!is_array($tokens) || $tokens === []) {
            $result = DeliveryResult::failure('No FCM registration token is configured for the user.');
        } else {
            $results = [];
            foreach ($tokens as $token) {
                $result = $this->safePushSend((string) $token, $message, $reminder);
                $results[] = $result;
                if ($result->invalidRecipient) {
                    $this->notifications->removeFcmToken((int) $reminder['user_id'], (string) $token);
                }
            }

            $successful = array_filter($results, static fn (DeliveryResult $result): bool => $result->successful);
            $result = new DeliveryResult(
                $successful !== [],
                implode(' ', array_map(static fn (DeliveryResult $item): string => $item->detail, $results))
            );
        }

        $this->complete($notificationId, $result, $message['body'], $summary, [
            'user_id' => (int) $reminder['user_id'],
            'service_id' => (int) $reminder['service_id'],
            'reminder_id' => (int) $reminder['reminder_id'],
            'channel' => 'push',
            'attempt' => 1,
            'scheduled_for' => $scheduledFor,
        ]);
    }

    /**
     * @param array<string, mixed> $reminder
     * @param array{title:string,body:string} $message
     * @param array{due_reminders:int,attempted:int,succeeded:int,failed:int,skipped:int} $summary
     */
    private function deliverSms(array $reminder, string $scheduledFor, DateTimeImmutable $sentAt, array $message, array &$summary): void
    {
        $attempts = $this->notifications->attemptsFor((int) $reminder['reminder_id'], 'sms', $scheduledFor);
        if (in_array('success', array_column($attempts, 'status'), true) || count($attempts) >= 2) {
            $summary['skipped']++;
            return;
        }

        for ($attempt = count($attempts) + 1; $attempt <= 2; $attempt++) {
            if ($attempt > 1) {
                $this->logger?->log('warning', 'notification_retry', 'Retrying a failed SMS notification.', [
                    'user_id' => (int) $reminder['user_id'],
                    'service_id' => (int) $reminder['service_id'],
                    'reminder_id' => (int) $reminder['reminder_id'],
                    'channel' => 'sms',
                    'attempt' => $attempt,
                    'scheduled_for' => $scheduledFor,
                ], $sentAt);
            }

            $notificationId = $this->notifications->reserveAttempt(
                (int) $reminder['service_id'],
                (int) $reminder['reminder_id'],
                'sms',
                $scheduledFor,
                $attempt,
                $sentAt
            );
            if ($notificationId === null) {
                $summary['skipped']++;
                return;
            }

            $phone = is_string($reminder['sms_phone'] ?? null) ? $reminder['sms_phone'] : '';
            $result = $phone === ''
                ? DeliveryResult::failure('No SMS phone number is configured for the user.')
                : $this->safeSmsSend($phone, $message['body']);
            $this->complete($notificationId, $result, $message['body'], $summary, [
                'user_id' => (int) $reminder['user_id'],
                'service_id' => (int) $reminder['service_id'],
                'reminder_id' => (int) $reminder['reminder_id'],
                'channel' => 'sms',
                'attempt' => $attempt,
                'scheduled_for' => $scheduledFor,
            ]);

            if ($result->successful) {
                return;
            }
        }
    }

    /** @param array{title:string,body:string} $message @param array<string, mixed> $reminder */
    private function safePushSend(string $token, array $message, array $reminder): DeliveryResult
    {
        try {
            return $this->push->send($token, $message['title'], $message['body'], [
                'service_id' => (string) $reminder['service_id'],
                'expiry_date' => (string) $reminder['expiry_date'],
            ]);
        } catch (Throwable $exception) {
            return DeliveryResult::failure('Push gateway error: ' . $exception->getMessage());
        }
    }

    private function safeSmsSend(string $phone, string $message): DeliveryResult
    {
        try {
            return $this->sms->send($phone, $message);
        } catch (Throwable $exception) {
            return DeliveryResult::failure('SMS gateway error: ' . $exception->getMessage());
        }
    }

    /**
     * @param array{due_reminders:int,attempted:int,succeeded:int,failed:int,skipped:int} $summary
     */
    private function complete(
        int $notificationId,
        DeliveryResult $result,
        string $message,
        array &$summary,
        array $context
    ): void
    {
        $this->notifications->finishAttempt($notificationId, $result, $message);
        $summary['attempted']++;
        $summary[$result->successful ? 'succeeded' : 'failed']++;
        $this->logger?->log(
            $result->successful ? 'info' : 'error',
            'notification_attempt',
            $result->successful ? 'Notification delivered successfully.' : 'Notification delivery failed.',
            $context + ['status' => $result->successful ? 'success' : 'failure']
        );
    }

    /** @param array<string, mixed> $reminder @return array{title:string,body:string} */
    private function messageFor(array $reminder): array
    {
        $frequency = (int) $reminder['frequency'];
        $type = ucfirst((string) $reminder['service_type']);
        $provider = is_string($reminder['provider'] ?? null) && trim($reminder['provider']) !== ''
            ? ' with ' . trim($reminder['provider'])
            : '';
        $dayLabel = $frequency === 1 ? 'day' : 'days';

        return [
            'title' => sprintf('%s expires in %d %s', $type, $frequency, $dayLabel),
            'body' => sprintf(
                '%s: %s%s expires on %s (%d %s remaining).',
                (string) $reminder['client_name'],
                strtolower($type),
                $provider,
                (string) $reminder['expiry_date'],
                $frequency,
                $dayLabel
            ),
        ];
    }
}
