<?php

declare(strict_types=1);

final class Session
{
    private const USER_KEY = 'auth_user';
    private const LAST_ACTIVITY_KEY = 'last_activity_at';
    private const FLASH_KEY = 'flash';

    public static function start(int $idleTimeoutSeconds): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $secure = Env::get('APP_ENV', 'local') === 'production' || Security::isHttps();
            $cookiePath = parse_url(Env::require('APP_URL'), PHP_URL_PATH) ?: '/';

            session_name('servicenotify_session');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => rtrim($cookiePath, '/') ?: '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        self::enforceTimeout($idleTimeoutSeconds);
    }

    public static function enforceTimeout(int $idleTimeoutSeconds, ?int $now = null): bool
    {
        if (!self::check()) {
            return false;
        }

        $now ??= time();
        $lastActivity = (int) ($_SESSION[self::LAST_ACTIVITY_KEY] ?? $now);

        if (($now - $lastActivity) >= $idleTimeoutSeconds) {
            self::forgetUser();
            self::flash('notice', 'Your session expired. Sign in to continue.');

            return true;
        }

        $_SESSION[self::LAST_ACTIVITY_KEY] = $now;

        return false;
    }

    /**
     * @param array{id: int, name: string, email: string} $user
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION[self::USER_KEY] = $user;
        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
        Csrf::rotate();
    }

    public static function logout(): void
    {
        self::forgetUser();
        session_regenerate_id(true);
        Csrf::rotate();
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::USER_KEY]['id']);
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public static function user(): ?array
    {
        return self::check() ? $_SESSION[self::USER_KEY] : null;
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION[self::FLASH_KEY][$key] = $message;
    }

    public static function pullFlash(string $key): ?string
    {
        $message = $_SESSION[self::FLASH_KEY][$key] ?? null;
        unset($_SESSION[self::FLASH_KEY][$key]);

        return is_string($message) ? $message : null;
    }

    private static function forgetUser(): void
    {
        unset($_SESSION[self::USER_KEY], $_SESSION[self::LAST_ACTIVITY_KEY]);
    }
}
