<?php

declare(strict_types=1);

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function isValid(?string $token): bool
    {
        $storedToken = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($token)
            && is_string($storedToken)
            && hash_equals($storedToken, $token);
    }

    public static function rotate(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::token();
    }
}

