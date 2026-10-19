<?php

declare(strict_types=1);

final class ServiceManager
{
    private const TYPES = ['domain', 'hosting', 'maintenance'];
    private const BILLING_CYCLES = ['monthly', 'yearly', 'custom'];

    public function __construct(
        private readonly ServiceRepository $services,
        private readonly ClientRepository $clients,
        private readonly ?ReminderService $reminders = null,
        private readonly ?PDO $pdo = null
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @throws ValidationException
     */
    public function create(int $userId, array $input, array $reminderInput = []): int
    {
        $serviceData = $this->validate($userId, $input);

        if ($this->reminders === null || $this->pdo === null) {
            return $this->services->create($serviceData);
        }

        $reminderRows = $this->reminders->validate($reminderInput);

        return $this->transaction(function () use ($serviceData, $reminderRows, $userId): int {
            $serviceId = $this->services->create($serviceData);
            $this->reminders?->replaceValidated($serviceId, $userId, $reminderRows);

            return $serviceId;
        });
    }

    /**
     * @param array<string, mixed> $input
     * @throws ValidationException
     * @throws ServiceNotFoundException
     */
    public function update(int $serviceId, int $userId, array $input, array $reminderInput = []): void
    {
        if ($this->services->findForUser($serviceId, $userId) === null) {
            throw new ServiceNotFoundException('Service not found.');
        }

        $serviceData = $this->validate($userId, $input);

        if ($this->reminders === null || $this->pdo === null) {
            $this->services->updateForUser($serviceId, $userId, $serviceData);
            return;
        }

        $reminderRows = $this->reminders->validate($reminderInput);
        $this->transaction(function () use ($serviceId, $userId, $serviceData, $reminderRows): void {
            $this->services->updateForUser($serviceId, $userId, $serviceData);
            $this->reminders?->replaceValidated($serviceId, $userId, $reminderRows);
        });
    }

    /**
     * @throws ServiceNotFoundException
     */
    public function delete(int $serviceId, int $userId): void
    {
        if (!$this->services->deleteForUser($serviceId, $userId)) {
            throw new ServiceNotFoundException('Service not found.');
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{client_id: int, type: string, provider: ?string, expiry_date: string, cost: ?string, currency: ?string, billing_cycle: string, payment_account: ?string}
     * @throws ValidationException
     */
    private function validate(int $userId, array $input): array
    {
        $clientId = filter_var($input['client_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $type = strtolower(trim((string) ($input['type'] ?? '')));
        $provider = self::nullableString($input['provider'] ?? null);
        $expiryDate = trim((string) ($input['expiry_date'] ?? ''));
        $cost = self::nullableString($input['cost'] ?? null);
        $currency = self::nullableString($input['currency'] ?? null);
        $currency = $currency !== null ? strtoupper($currency) : null;
        $billingCycle = strtolower(trim((string) ($input['billing_cycle'] ?? '')));
        $paymentAccount = self::nullableString($input['payment_account'] ?? null);
        $errors = [];

        if (!is_int($clientId) || $this->clients->findForUser($clientId, $userId) === null) {
            $errors['client_id'] = 'Select a valid client from your account.';
        }

        if (!in_array($type, self::TYPES, true)) {
            $errors['type'] = 'Select domain, hosting, or maintenance.';
        }

        if ($provider !== null && self::stringLength($provider) > 150) {
            $errors['provider'] = 'Provider must be 150 characters or fewer.';
        }

        if (!self::validDate($expiryDate)) {
            $errors['expiry_date'] = 'Enter a valid expiry date in YYYY-MM-DD format.';
        }

        if ($cost !== null) {
            if (preg_match('/^\d+(?:\.\d{1,2})?$/', $cost) !== 1 || (float) $cost > 99999999.99) {
                $errors['cost'] = 'Enter a non-negative amount with no more than 2 decimal places.';
            } else {
                $cost = number_format((float) $cost, 2, '.', '');
            }
        }

        if ($cost !== null && ($currency === null || preg_match('/^[A-Z]{3}$/', $currency) !== 1)) {
            $errors['currency'] = 'Enter a 3-letter currency code such as NGN or USD.';
        }

        if ($cost === null) {
            $currency = null;
        }

        if (!in_array($billingCycle, self::BILLING_CYCLES, true)) {
            $errors['billing_cycle'] = 'Select monthly, yearly, or custom billing.';
        }

        if ($paymentAccount !== null && self::stringLength($paymentAccount) > 150) {
            $errors['payment_account'] = 'Payment account must be 150 characters or fewer.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'client_id' => $clientId,
            'type' => $type,
            'provider' => $provider,
            'expiry_date' => $expiryDate,
            'cost' => $cost,
            'currency' => $currency,
            'billing_cycle' => $billingCycle,
            'payment_account' => $paymentAccount,
        ];
    }

    private static function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private static function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    private function transaction(callable $operation): mixed
    {
        if ($this->pdo === null) {
            return $operation();
        }

        $this->pdo->beginTransaction();

        try {
            $result = $operation();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
