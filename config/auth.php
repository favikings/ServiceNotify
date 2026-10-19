<?php

declare(strict_types=1);

/**
 * @return array{session_idle_seconds: int, max_login_attempts: int, max_ip_login_attempts: int, login_window_seconds: int}
 */
function authConfig(): array
{
    return [
        'session_idle_seconds' => positiveEnvironmentInteger(
            'AUTH_SESSION_IDLE_SECONDS',
            1800
        ),
        'max_login_attempts' => positiveEnvironmentInteger(
            'AUTH_MAX_LOGIN_ATTEMPTS',
            5
        ),
        'max_ip_login_attempts' => positiveEnvironmentInteger(
            'AUTH_MAX_IP_LOGIN_ATTEMPTS',
            25
        ),
        'login_window_seconds' => positiveEnvironmentInteger(
            'AUTH_LOGIN_WINDOW_SECONDS',
            900
        ),
    ];
}

function positiveEnvironmentInteger(string $name, int $default): int
{
    $value = filter_var(
        Env::get($name, (string) $default),
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($value === false) {
        throw new RuntimeException(sprintf('%s must be a positive integer.', $name));
    }

    return $value;
}
