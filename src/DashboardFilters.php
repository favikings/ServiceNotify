<?php

declare(strict_types=1);

final class DashboardFilters
{
    public const PAGE_SIZE = 10;

    /** @var list<string> */
    private const SERVICE_TYPES = ['domain', 'hosting', 'maintenance'];

    /**
     * @param array<string, string> $errors
     */
    private function __construct(
        public readonly ?int $clientId,
        public readonly ?string $type,
        public readonly string $dateFrom,
        public readonly string $dateTo,
        public readonly int $page,
        public readonly array $errors
    ) {
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function fromInput(array $input, DateTimeImmutable $today): self
    {
        $defaultFrom = $today->format('Y-m-d');
        $defaultTo = $today->modify('+30 days')->format('Y-m-d');
        $errors = [];

        $clientInput = $input['client_id'] ?? '';
        if ($clientInput === '' || $clientInput === null) {
            $clientId = null;
        } elseif (!is_string($clientInput) && !is_int($clientInput)) {
            $clientId = null;
            $errors['client_id'] = 'Choose a valid client.';
        } else {
            $validatedClientId = filter_var($clientInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $clientId = is_int($validatedClientId) ? $validatedClientId : null;
            if ($clientId === null) {
                $errors['client_id'] = 'Choose a valid client.';
            }
        }

        $typeInput = $input['type'] ?? '';
        $rawType = is_string($typeInput) ? strtolower(trim($typeInput)) : '__invalid__';
        $type = $rawType === '' ? null : $rawType;
        if ($type !== null && !in_array($type, self::SERVICE_TYPES, true)) {
            $errors['type'] = 'Choose a valid service type.';
            $type = null;
        }

        $fromInput = $input['date_from'] ?? $defaultFrom;
        $toInput = $input['date_to'] ?? $defaultTo;
        $rawFrom = is_string($fromInput) ? trim($fromInput) : '';
        $rawTo = is_string($toInput) ? trim($toInput) : '';
        $dateFrom = self::isDate($rawFrom) ? $rawFrom : $defaultFrom;
        $dateTo = self::isDate($rawTo) ? $rawTo : $defaultTo;

        if ($dateFrom !== $rawFrom) {
            $errors['date_from'] = 'Enter a valid start date.';
        }
        if ($dateTo !== $rawTo) {
            $errors['date_to'] = 'Enter a valid end date.';
        }
        if ($dateFrom > $dateTo) {
            $errors['date_range'] = 'The start date must be on or before the end date.';
            $dateFrom = $defaultFrom;
            $dateTo = $defaultTo;
        }

        $rawPage = $input['page'] ?? 1;
        $page = filter_var($rawPage, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return new self($clientId, $type, $dateFrom, $dateTo, is_int($page) ? $page : 1, $errors);
    }

    private static function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
