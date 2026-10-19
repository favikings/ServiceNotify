<?php

declare(strict_types=1);

final class ReportRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array{
     *   client_name:string,organization:?string,client_email:?string,client_phone:?string,
     *   service_type:string,provider:?string,expiry_date:string,cost:?string,currency:?string,
     *   billing_cycle:string,payment_account:?string
     * }>
     */
    public function servicesForUser(
        int $userId,
        string $dateFrom,
        string $dateTo,
        ?int $clientId,
        ?string $type
    ): array {
        $sql = 'SELECT c.name AS client_name, c.organization, c.email AS client_email,
                       c.phone AS client_phone, s.type AS service_type, s.provider,
                       s.expiry_date, s.cost, s.currency, s.billing_cycle, s.payment_account
                FROM services s
                INNER JOIN clients c ON c.id = s.client_id
                WHERE c.user_id = :user_id
                  AND s.expiry_date BETWEEN :date_from AND :date_to';

        if ($clientId !== null) {
            $sql .= ' AND c.id = :client_id';
        }
        if ($type !== null) {
            $sql .= ' AND s.type = :type';
        }

        $sql .= ' ORDER BY s.expiry_date ASC, c.name ASC, s.id ASC';
        $statement = $this->pdo->prepare($sql);
        $parameters = [
            'user_id' => $userId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
        if ($clientId !== null) {
            $parameters['client_id'] = $clientId;
        }
        if ($type !== null) {
            $parameters['type'] = $type;
        }
        $statement->execute($parameters);

        return array_map(
            static fn (array $row): array => [
                'client_name' => (string) $row['client_name'],
                'organization' => $row['organization'] !== null ? (string) $row['organization'] : null,
                'client_email' => $row['client_email'] !== null ? (string) $row['client_email'] : null,
                'client_phone' => $row['client_phone'] !== null ? (string) $row['client_phone'] : null,
                'service_type' => (string) $row['service_type'],
                'provider' => $row['provider'] !== null ? (string) $row['provider'] : null,
                'expiry_date' => (string) $row['expiry_date'],
                'cost' => $row['cost'] !== null ? number_format((float) $row['cost'], 2, '.', '') : null,
                'currency' => $row['currency'] !== null ? (string) $row['currency'] : null,
                'billing_cycle' => (string) $row['billing_cycle'],
                'payment_account' => $row['payment_account'] !== null ? (string) $row['payment_account'] : null,
            ],
            $statement->fetchAll()
        );
    }
}
