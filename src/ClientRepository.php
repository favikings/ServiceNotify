<?php

declare(strict_types=1);

final class ClientRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array{id: int, name: string, organization: ?string, phone: ?string, email: ?string, created_at: string, service_count: int}>
     */
    public function allForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, organization, phone, email, created_at,
                    (SELECT COUNT(*) FROM services s WHERE s.client_id = clients.id) AS service_count
             FROM clients
             WHERE user_id = :user_id
             ORDER BY name ASC, id ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map(
            [self::class, 'normalizeRecord'],
            $statement->fetchAll()
        );
    }

    /**
     * @return array{id: int, name: string, organization: ?string, phone: ?string, email: ?string, created_at: string, service_count: int}|null
     */
    public function findForUser(int $clientId, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, organization, phone, email, created_at
             FROM clients
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $clientId,
            'user_id' => $userId,
        ]);
        $client = $statement->fetch();

        return $client === false ? null : self::normalizeRecord($client);
    }

    public function countForUser(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM clients WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param array{name: string, organization: ?string, phone: ?string, email: ?string} $data
     */
    public function createForUser(int $userId, array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO clients (user_id, name, organization, phone, email)
             VALUES (:user_id, :name, :organization, :phone, :email)'
        );
        $statement->execute([
            'user_id' => $userId,
            'name' => $data['name'],
            'organization' => $data['organization'],
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{name: string, organization: ?string, phone: ?string, email: ?string} $data
     */
    public function updateForUser(int $clientId, int $userId, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE clients
             SET name = :name,
                 organization = :organization,
                 phone = :phone,
                 email = :email
             WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute([
            'id' => $clientId,
            'user_id' => $userId,
            'name' => $data['name'],
            'organization' => $data['organization'],
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);
    }

    public function deleteForUser(int $clientId, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM clients WHERE id = :id AND user_id = :user_id'
        );
        $statement->execute([
            'id' => $clientId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() === 1;
    }

    /**
     * @param array<string, mixed> $record
     * @return array{id: int, name: string, organization: ?string, phone: ?string, email: ?string, created_at: string, service_count: int}
     */
    private static function normalizeRecord(array $record): array
    {
        return [
            'id' => (int) $record['id'],
            'name' => (string) $record['name'],
            'organization' => $record['organization'] !== null ? (string) $record['organization'] : null,
            'phone' => $record['phone'] !== null ? (string) $record['phone'] : null,
            'email' => $record['email'] !== null ? (string) $record['email'] : null,
            'created_at' => (string) $record['created_at'],
            'service_count' => isset($record['service_count']) ? (int) $record['service_count'] : 0,
        ];
    }
}
