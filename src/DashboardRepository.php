<?php

declare(strict_types=1);

final class DashboardRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array{id: int, client_id: int, client_name: string, type: string, provider: ?string, expiry_date: string}>
     */
    public function expiriesForUser(
        int $userId,
        string $dateFrom,
        string $dateTo,
        ?string $type,
        int $limit,
        int $offset,
        ?int $clientId = null
    ): array {
        $sql = 'SELECT s.id, s.client_id, c.name AS client_name, s.type, s.provider, s.expiry_date
                FROM services s
                INNER JOIN clients c ON c.id = s.client_id
                WHERE c.user_id = :user_id
                  AND s.expiry_date BETWEEN :date_from AND :date_to';

        if ($type !== null) {
            $sql .= ' AND s.type = :type';
        }
        if ($clientId !== null) {
            $sql .= ' AND s.client_id = :client_id';
        }

        $sql .= ' ORDER BY s.expiry_date ASC, c.name ASC, s.id ASC
                  LIMIT :limit OFFSET :offset';

        $statement = $this->pdo->prepare($sql);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':date_from', $dateFrom);
        $statement->bindValue(':date_to', $dateTo);
        if ($type !== null) {
            $statement->bindValue(':type', $type);
        }
        if ($clientId !== null) {
            $statement->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'client_id' => (int) $row['client_id'],
                'client_name' => (string) $row['client_name'],
                'type' => (string) $row['type'],
                'provider' => $row['provider'] !== null ? (string) $row['provider'] : null,
                'expiry_date' => (string) $row['expiry_date'],
            ],
            $statement->fetchAll()
        );
    }

    public function countExpiriesForUser(
        int $userId,
        string $dateFrom,
        string $dateTo,
        ?string $type,
        ?int $clientId = null
    ): int {
        $sql = 'SELECT COUNT(*)
                FROM services s
                INNER JOIN clients c ON c.id = s.client_id
                WHERE c.user_id = :user_id
                  AND s.expiry_date BETWEEN :date_from AND :date_to';

        if ($type !== null) {
            $sql .= ' AND s.type = :type';
        }
        if ($clientId !== null) {
            $sql .= ' AND s.client_id = :client_id';
        }

        $statement = $this->pdo->prepare($sql);
        $parameters = [
            'user_id' => $userId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
        if ($type !== null) {
            $parameters['type'] = $type;
        }
        if ($clientId !== null) {
            $parameters['client_id'] = $clientId;
        }
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }
}
