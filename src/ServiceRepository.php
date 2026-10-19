<?php

declare(strict_types=1);

final class ServiceRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT s.id, s.client_id, c.name AS client_name, c.organization AS client_organization,
                    s.type, s.provider, s.expiry_date, s.cost, s.currency, s.billing_cycle,
                    s.payment_account, s.created_at,
                    (SELECT COUNT(*) FROM reminders r WHERE r.service_id = s.id) AS reminder_count
             FROM services s
             INNER JOIN clients c ON c.id = s.client_id
             WHERE c.user_id = :user_id
             ORDER BY s.expiry_date ASC, c.name ASC, s.id ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map([self::class, 'normalizeRecord'], $statement->fetchAll());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $serviceId, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT s.id, s.client_id, c.name AS client_name, c.organization AS client_organization,
                    s.type, s.provider, s.expiry_date, s.cost, s.currency, s.billing_cycle,
                    s.payment_account, s.created_at,
                    (SELECT COUNT(*) FROM reminders r WHERE r.service_id = s.id) AS reminder_count
             FROM services s
             INNER JOIN clients c ON c.id = s.client_id
             WHERE s.id = :id AND c.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $serviceId,
            'user_id' => $userId,
        ]);
        $service = $statement->fetch();

        return $service === false ? null : self::normalizeRecord($service);
    }

    public function countForUser(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM services s
             INNER JOIN clients c ON c.id = s.client_id
             WHERE c.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param array{client_id: int, type: string, provider: ?string, expiry_date: string, cost: ?string, currency: ?string, billing_cycle: string, payment_account: ?string} $data
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO services (client_id, type, provider, expiry_date, cost, currency, billing_cycle, payment_account)
             VALUES (:client_id, :type, :provider, :expiry_date, :cost, :currency, :billing_cycle, :payment_account)'
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array{client_id: int, type: string, provider: ?string, expiry_date: string, cost: ?string, currency: ?string, billing_cycle: string, payment_account: ?string} $data
     */
    public function updateForUser(int $serviceId, int $userId, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE services
             SET client_id = :client_id,
                 type = :type,
                 provider = :provider,
                 expiry_date = :expiry_date,
                 cost = :cost,
                 currency = :currency,
                 billing_cycle = :billing_cycle,
                 payment_account = :payment_account
             WHERE id = :id
               AND client_id IN (SELECT id FROM clients WHERE user_id = :user_id)'
        );
        $statement->execute($data + [
            'id' => $serviceId,
            'user_id' => $userId,
        ]);
    }

    public function deleteForUser(int $serviceId, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM services
             WHERE id = :id
               AND client_id IN (SELECT id FROM clients WHERE user_id = :user_id)'
        );
        $statement->execute([
            'id' => $serviceId,
            'user_id' => $userId,
        ]);

        return $statement->rowCount() === 1;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private static function normalizeRecord(array $record): array
    {
        return [
            'id' => (int) $record['id'],
            'client_id' => (int) $record['client_id'],
            'client_name' => (string) $record['client_name'],
            'client_organization' => $record['client_organization'] !== null ? (string) $record['client_organization'] : null,
            'type' => (string) $record['type'],
            'provider' => $record['provider'] !== null ? (string) $record['provider'] : null,
            'expiry_date' => (string) $record['expiry_date'],
            'cost' => $record['cost'] !== null ? number_format((float) $record['cost'], 2, '.', '') : null,
            'currency' => $record['currency'] !== null ? (string) $record['currency'] : null,
            'billing_cycle' => (string) $record['billing_cycle'],
            'payment_account' => $record['payment_account'] !== null ? (string) $record['payment_account'] : null,
            'created_at' => (string) $record['created_at'],
            'reminder_count' => isset($record['reminder_count']) ? (int) $record['reminder_count'] : 0,
        ];
    }
}
