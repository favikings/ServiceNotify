<?php

declare(strict_types=1);

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{id: int, name: string, email: string, password: string}|null
     */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user === false) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'password' => (string) $user['password'],
        ];
    }

    /**
     * @return array{id: int, name: string, email: string}
     */
    public function create(string $name, string $email, string $passwordHash): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
        );

        try {
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DuplicateEmailException('An account already uses this email.', 0, $exception);
            }

            throw $exception;
        }

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'name' => $name,
            'email' => $email,
        ];
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password = :password WHERE id = :id'
        );
        $statement->execute([
            'id' => $userId,
            'password' => $passwordHash,
        ]);
    }

    /** @return array<string, mixed> */
    public function settingsForUser(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT settings FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $value = $statement->fetchColumn();
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $settings = json_decode($value, true);

        return is_array($settings) ? $settings : [];
    }

    /** @param array<string, mixed> $settings */
    public function updateSettings(int $userId, array $settings): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET settings = :settings WHERE id = :id');
        $statement->execute([
            'id' => $userId,
            'settings' => json_encode($settings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
