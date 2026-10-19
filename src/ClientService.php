<?php

declare(strict_types=1);

final class ClientService
{
    public function __construct(private readonly ClientRepository $clients)
    {
    }

    /**
     * @param array{name?: mixed, organization?: mixed, phone?: mixed, email?: mixed} $input
     * @throws ValidationException
     */
    public function create(int $userId, array $input): int
    {
        return $this->clients->createForUser($userId, $this->validate($input));
    }

    /**
     * @param array{name?: mixed, organization?: mixed, phone?: mixed, email?: mixed} $input
     * @throws ValidationException
     * @throws ClientNotFoundException
     */
    public function update(int $clientId, int $userId, array $input): void
    {
        if ($this->clients->findForUser($clientId, $userId) === null) {
            throw new ClientNotFoundException('Client not found.');
        }

        $this->clients->updateForUser($clientId, $userId, $this->validate($input));
    }

    /**
     * @throws ClientNotFoundException
     */
    public function delete(int $clientId, int $userId): void
    {
        if (!$this->clients->deleteForUser($clientId, $userId)) {
            throw new ClientNotFoundException('Client not found.');
        }
    }

    /**
     * @param array{name?: mixed, organization?: mixed, phone?: mixed, email?: mixed} $input
     * @return array{name: string, organization: ?string, phone: ?string, email: ?string}
     * @throws ValidationException
     */
    private function validate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $organization = self::nullableString($input['organization'] ?? null);
        $phone = self::nullableString($input['phone'] ?? null);
        $email = self::nullableString($input['email'] ?? null);
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Enter the client name.';
        } elseif (self::stringLength($name) > 150) {
            $errors['name'] = 'Client name must be 150 characters or fewer.';
        }

        if ($organization !== null && self::stringLength($organization) > 150) {
            $errors['organization'] = 'Organization must be 150 characters or fewer.';
        }

        if ($phone !== null && !self::validPhone($phone)) {
            $errors['phone'] = 'Enter a valid phone number with 7–15 digits.';
        }

        if ($email !== null) {
            $email = strtolower($email);

            if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Enter a valid email address.';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'name' => $name,
            'organization' => $organization,
            'phone' => $phone,
            'email' => $email,
        ];
    }

    private static function validPhone(string $phone): bool
    {
        if (strlen($phone) > 20 || preg_match('/^\+?[0-9() .-]+$/', $phone) !== 1) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        $digitCount = $digits === null ? 0 : strlen($digits);

        return $digitCount >= 7 && $digitCount <= 15;
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
}

