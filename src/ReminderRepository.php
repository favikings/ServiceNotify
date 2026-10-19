<?php

declare(strict_types=1);

final class ReminderRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array{id: int, frequency: int, channels: list<string>}>
     */
    public function allForServiceForUser(int $serviceId, int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.id, r.frequency, r.channels
             FROM reminders r
             INNER JOIN services s ON s.id = r.service_id
             INNER JOIN clients c ON c.id = s.client_id
             WHERE r.service_id = :service_id AND c.user_id = :user_id
             ORDER BY r.frequency DESC, r.id ASC'
        );
        $statement->execute([
            'service_id' => $serviceId,
            'user_id' => $userId,
        ]);

        return array_map(
            static function (array $row): array {
                $channels = array_values(array_filter(explode(',', (string) $row['channels'])));

                return [
                    'id' => (int) $row['id'],
                    'frequency' => (int) $row['frequency'],
                    'channels' => $channels,
                ];
            },
            $statement->fetchAll()
        );
    }

    /**
     * @param list<array{frequency: int, channels: list<string>}> $rows
     */
    public function replaceForServiceForUser(int $serviceId, int $userId, array $rows): void
    {
        $delete = $this->pdo->prepare(
            'DELETE FROM reminders
             WHERE service_id = :service_id
               AND EXISTS (
                   SELECT 1
                   FROM services s
                   INNER JOIN clients c ON c.id = s.client_id
                   WHERE s.id = reminders.service_id AND c.user_id = :user_id
               )'
        );
        $delete->execute([
            'service_id' => $serviceId,
            'user_id' => $userId,
        ]);

        $insert = $this->pdo->prepare(
            'INSERT INTO reminders (service_id, frequency, channels)
             SELECT s.id, :frequency, :channels
             FROM services s
             INNER JOIN clients c ON c.id = s.client_id
             WHERE s.id = :service_id AND c.user_id = :user_id'
        );

        foreach ($rows as $row) {
            $insert->execute([
                'service_id' => $serviceId,
                'user_id' => $userId,
                'frequency' => $row['frequency'],
                'channels' => implode(',', $row['channels']),
            ]);

            if ($insert->rowCount() !== 1) {
                throw new ServiceNotFoundException('Service not found.');
            }
        }
    }
}
