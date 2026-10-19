<?php

declare(strict_types=1);

final class LogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{total:int,success:int,failure:int,retries:int} */
    public function summaryForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN n.status = 'success' THEN 1 ELSE 0 END) AS succeeded,
                    SUM(CASE WHEN n.status = 'failure' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN n.attempt > 1 THEN 1 ELSE 0 END) AS retries
             FROM notifications n
             INNER JOIN services s ON s.id = n.service_id
             INNER JOIN clients c ON c.id = s.client_id
             WHERE c.user_id = :user_id"
        );
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'success' => (int) ($row['succeeded'] ?? 0),
            'failure' => (int) ($row['failed'] ?? 0),
            'retries' => (int) ($row['retries'] ?? 0),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function notificationsForUser(
        int $userId,
        ?string $status = null,
        ?string $type = null,
        int $limit = 100
    ): array {
        $conditions = ['c.user_id = :user_id'];
        $parameters = ['user_id' => $userId];
        if ($status !== null) {
            $conditions[] = 'n.status = :status';
            $parameters['status'] = $status;
        }
        if ($type !== null) {
            $conditions[] = 'n.type = :type';
            $parameters['type'] = $type;
        }

        $statement = $this->pdo->prepare(
            'SELECT n.id, n.type, n.status, n.message, n.scheduled_for, n.attempt, n.sent_at,
                    s.type AS service_type, s.provider, c.name AS client_name
             FROM notifications n
             INNER JOIN services s ON s.id = n.service_id
             INNER JOIN clients c ON c.id = s.client_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY n.sent_at DESC, n.id DESC
             LIMIT ' . max(1, min($limit, 200))
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }
}
