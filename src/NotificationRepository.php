<?php

declare(strict_types=1);

final class NotificationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array{
     *   reminder_id:int,service_id:int,user_id:int,frequency:int,channels:list<string>,
     *   service_type:string,provider:?string,expiry_date:string,client_name:string,
     *   fcm_tokens:list<string>,sms_phone:?string
     * }>
     */
    public function dueReminders(DateTimeImmutable $runDate): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.id AS reminder_id, r.service_id, r.frequency, r.channels,
                    s.type AS service_type, s.provider, s.expiry_date,
                    c.name AS client_name, u.id AS user_id, u.settings
             FROM reminders r
             INNER JOIN services s ON s.id = r.service_id
             INNER JOIN clients c ON c.id = s.client_id
             INNER JOIN users u ON u.id = c.user_id
             WHERE s.expiry_date BETWEEN :date_from AND :date_to
             ORDER BY s.expiry_date ASC, r.id ASC'
        );
        $statement->execute([
            'date_from' => $runDate->modify('+1 day')->format('Y-m-d'),
            'date_to' => $runDate->modify('+30 days')->format('Y-m-d'),
        ]);

        $due = [];
        foreach ($statement->fetchAll() as $row) {
            $frequency = (int) $row['frequency'];
            $expectedExpiry = $runDate->modify('+' . $frequency . ' days')->format('Y-m-d');
            if ((string) $row['expiry_date'] !== $expectedExpiry) {
                continue;
            }

            $settings = $this->decodeSettings($row['settings'] ?? null);
            $tokens = isset($settings['fcm_tokens']) && is_array($settings['fcm_tokens'])
                ? $settings['fcm_tokens']
                : [];
            $tokens = array_values(array_unique(array_filter(
                array_map(static fn (mixed $token): string => is_string($token) ? trim($token) : '', $tokens),
                static fn (string $token): bool => $token !== ''
            )));
            $smsPhone = is_string($settings['sms_phone'] ?? null) && trim($settings['sms_phone']) !== ''
                ? trim($settings['sms_phone'])
                : null;

            $due[] = [
                'reminder_id' => (int) $row['reminder_id'],
                'service_id' => (int) $row['service_id'],
                'user_id' => (int) $row['user_id'],
                'frequency' => $frequency,
                'channels' => array_values(array_filter(explode(',', (string) $row['channels']))),
                'service_type' => (string) $row['service_type'],
                'provider' => $row['provider'] !== null ? (string) $row['provider'] : null,
                'expiry_date' => (string) $row['expiry_date'],
                'client_name' => (string) $row['client_name'],
                'fcm_tokens' => $tokens,
                'sms_phone' => $smsPhone,
            ];
        }

        return $due;
    }

    /** @return list<array{attempt:int,status:string}> */
    public function attemptsFor(int $reminderId, string $type, string $scheduledFor): array
    {
        $statement = $this->pdo->prepare(
            'SELECT attempt, status
             FROM notifications
             WHERE reminder_id = :reminder_id
               AND type = :type
               AND scheduled_for = :scheduled_for
             ORDER BY attempt ASC'
        );
        $statement->execute([
            'reminder_id' => $reminderId,
            'type' => $type,
            'scheduled_for' => $scheduledFor,
        ]);

        return array_map(
            static fn (array $row): array => [
                'attempt' => (int) $row['attempt'],
                'status' => (string) $row['status'],
            ],
            $statement->fetchAll()
        );
    }

    public function reserveAttempt(
        int $serviceId,
        int $reminderId,
        string $type,
        string $scheduledFor,
        int $attempt,
        DateTimeImmutable $sentAt
    ): ?int {
        $statement = $this->pdo->prepare(
            "INSERT INTO notifications
                (service_id, reminder_id, type, status, message, scheduled_for, attempt, sent_at)
             VALUES
                (:service_id, :reminder_id, :type, 'failure', :message, :scheduled_for, :attempt, :sent_at)"
        );

        try {
            $statement->execute([
                'service_id' => $serviceId,
                'reminder_id' => $reminderId,
                'type' => $type,
                'message' => 'Delivery attempt started but did not complete.',
                'scheduled_for' => $scheduledFor,
                'attempt' => $attempt,
                'sent_at' => $sentAt->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return null;
            }

            throw $exception;
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function finishAttempt(int $notificationId, DeliveryResult $result, string $message): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE notifications
             SET status = :status, message = :message
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $notificationId,
            'status' => $result->successful ? 'success' : 'failure',
            'message' => $message . ' | ' . $result->detail,
        ]);
    }

    public function removeFcmToken(int $userId, string $token): void
    {
        $select = $this->pdo->prepare('SELECT settings FROM users WHERE id = :id');
        $select->execute(['id' => $userId]);
        $settingsValue = $select->fetchColumn();
        if ($settingsValue === false) {
            return;
        }

        $settings = $this->decodeSettings($settingsValue);
        $tokens = isset($settings['fcm_tokens']) && is_array($settings['fcm_tokens'])
            ? $settings['fcm_tokens']
            : [];
        $settings['fcm_tokens'] = array_values(array_filter(
            $tokens,
            static fn (mixed $candidate): bool => !is_string($candidate) || !hash_equals($token, $candidate)
        ));

        $update = $this->pdo->prepare('UPDATE users SET settings = :settings WHERE id = :id');
        $update->execute([
            'id' => $userId,
            'settings' => json_encode($settings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function acquireDailyLock(string $scheduledFor): bool
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return true;
        }

        $statement = $this->pdo->prepare('SELECT GET_LOCK(:lock_name, 0)');
        $statement->execute(['lock_name' => 'servicenotify:notify:' . $scheduledFor]);

        return (int) $statement->fetchColumn() === 1;
    }

    public function releaseDailyLock(string $scheduledFor): void
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }

        $statement = $this->pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $statement->execute(['lock_name' => 'servicenotify:notify:' . $scheduledFor]);
    }

    /** @return array<string, mixed> */
    private function decodeSettings(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
