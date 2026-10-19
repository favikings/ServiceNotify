<?php

declare(strict_types=1);

final class RequestInput
{
    public static function getString(string $name, string $default = ''): string
    {
        return self::string(INPUT_GET, $name, $default);
    }

    public static function postString(string $name, string $default = ''): string
    {
        return self::string(INPUT_POST, $name, $default);
    }

    public static function serverString(string $name, string $default = ''): string
    {
        return self::string(INPUT_SERVER, $name, $default);
    }

    public static function getPositiveInt(string $name): ?int
    {
        $value = filter_input(
            INPUT_GET,
            $name,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        return is_int($value) ? $value : null;
    }

    /** @return array<string, mixed> */
    public static function postArray(string $name): array
    {
        $value = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);

        return is_array($value) ? $value : [];
    }

    /** @param list<string> $names @return array<string, string> */
    public static function query(array $names): array
    {
        $input = [];
        foreach ($names as $name) {
            $input[$name] = self::getString($name);
        }

        return $input;
    }

    private static function string(int $type, string $name, string $default): string
    {
        $value = filter_input($type, $name, FILTER_UNSAFE_RAW, FILTER_REQUIRE_SCALAR);

        return is_string($value) ? $value : $default;
    }
}
