<?php

declare(strict_types=1);

/**
 * Minimal, dependency-free .env loader for the project bootstrap.
 *
 * Existing server/process environment variables take precedence over values
 * declared in the file, which keeps production-level overrides possible.
 */
final class Env
{
    /**
     * @throws RuntimeException when the file or a declaration is invalid.
     */
    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                sprintf('Environment file not found or unreadable: %s', $path)
            );
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            throw new RuntimeException(
                sprintf('Unable to read environment file: %s', $path)
            );
        }

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = ltrim(substr($line, 7));
            }

            if (!str_contains($line, '=')) {
                throw self::invalidDeclaration($path, $lineNumber + 1);
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));

            if (preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name) !== 1) {
                throw self::invalidDeclaration($path, $lineNumber + 1);
            }

            $value = self::normalizeValue($value, $path, $lineNumber + 1);

            if (getenv($name) !== false || array_key_exists($name, $_ENV)) {
                continue;
            }

            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    public static function get(string $name, ?string $default = null): ?string
    {
        $value = getenv($name);

        if ($value !== false) {
            return $value;
        }

        return $_ENV[$name] ?? $_SERVER[$name] ?? $default;
    }

    /**
     * @throws RuntimeException when the variable is missing or empty.
     */
    public static function require(string $name): string
    {
        $value = self::get($name);

        if ($value === null || trim($value) === '') {
            throw new RuntimeException(
                sprintf('Required environment variable %s is missing or empty.', $name)
            );
        }

        return $value;
    }

    /**
     * @throws RuntimeException when quotes are not balanced.
     */
    private static function normalizeValue(string $value, string $path, int $lineNumber): string
    {
        if ($value === '') {
            return '';
        }

        $firstCharacter = $value[0];

        if ($firstCharacter !== '"' && $firstCharacter !== "'") {
            return $value;
        }

        if (strlen($value) < 2 || !str_ends_with($value, $firstCharacter)) {
            throw self::invalidDeclaration($path, $lineNumber);
        }

        $value = substr($value, 1, -1);

        return $firstCharacter === '"' ? stripcslashes($value) : $value;
    }

    private static function invalidDeclaration(string $path, int $lineNumber): RuntimeException
    {
        return new RuntimeException(
            sprintf('Invalid environment declaration in %s on line %d.', $path, $lineNumber)
        );
    }
}

