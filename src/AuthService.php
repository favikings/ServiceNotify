<?php

declare(strict_types=1);

final class AuthService
{
    private const MINIMUM_PASSWORD_LENGTH = 8;
    private const DUMMY_PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function __construct(private readonly UserRepository $users)
    {
    }

    /**
     * @return array{id: int, name: string, email: string}
     * @throws ValidationException
     * @throws DuplicateEmailException
     */
    public function register(
        string $name,
        string $email,
        string $password,
        string $passwordConfirmation
    ): array {
        $name = trim($name);
        $email = self::normalizeEmail($email);
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Enter your name.';
        } elseif (self::stringLength($name) > 100) {
            $errors['name'] = 'Name must be 100 characters or fewer.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (strlen($email) > 150) {
            $errors['email'] = 'Email must be 150 characters or fewer.';
        }

        if (strlen($password) < self::MINIMUM_PASSWORD_LENGTH) {
            $errors['password'] = sprintf(
                'Password must be at least %d characters.',
                self::MINIMUM_PASSWORD_LENGTH
            );
        }

        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new DuplicateEmailException('An account already uses this email.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if ($passwordHash === false) {
            throw new RuntimeException('Unable to secure the password.');
        }

        return $this->users->create($name, $email, $passwordHash);
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public function attempt(string $email, string $password): ?array
    {
        $email = self::normalizeEmail($email);
        $user = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $this->users->findByEmail($email)
            : null;
        $passwordHash = $user['password'] ?? self::DUMMY_PASSWORD_HASH;

        if (!password_verify($password, $passwordHash) || $user === null) {
            return null;
        }

        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);

            if ($newHash !== false) {
                $this->users->updatePasswordHash($user['id'], $newHash);
            }
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private static function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}

