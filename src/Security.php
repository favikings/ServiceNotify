<?php

declare(strict_types=1);

final class Security
{
    public static function enforceHttps(): void
    {
        if (PHP_SAPI === 'cli' || Env::get('APP_ENV', 'local') !== 'production') {
            return;
        }

        $appUrl = Env::require('APP_URL');
        if (strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new RuntimeException('APP_URL must use https:// when APP_ENV=production.');
        }

        if (self::isHttps()) {
            return;
        }

        header(
            'Location: ' . self::secureUrl($appUrl, RequestInput::serverString('REQUEST_URI', '/')),
            true,
            308
        );
        exit;
    }

    /** @param array<string, mixed>|null $server */
    public static function isHttps(?array $server = null): bool
    {
        $https = strtolower(self::serverValue('HTTPS', $server));
        if ($https !== '' && $https !== 'off' && $https !== '0') {
            return true;
        }

        $remoteAddress = self::serverValue('REMOTE_ADDR', $server);
        if (!self::isTrustedProxy($remoteAddress)) {
            return false;
        }

        $forwardedProtocol = strtolower(trim(explode(',', self::serverValue('HTTP_X_FORWARDED_PROTO', $server))[0] ?? ''));

        return $forwardedProtocol === 'https';
    }

    /** @param array<string, mixed>|null $server */
    public static function clientIpAddress(?array $server = null): string
    {
        $remoteAddress = self::serverValue('REMOTE_ADDR', $server, 'unknown');
        if (!self::isTrustedProxy($remoteAddress)) {
            return filter_var($remoteAddress, FILTER_VALIDATE_IP) !== false ? $remoteAddress : 'unknown';
        }

        $chain = explode(',', self::serverValue('HTTP_X_FORWARDED_FOR', $server));
        $chain[] = $remoteAddress;
        foreach (array_reverse($chain) as $candidate) {
            $candidate = trim($candidate);
            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false && !self::isTrustedProxy($candidate)) {
                return $candidate;
            }
        }

        return filter_var($remoteAddress, FILTER_VALIDATE_IP) !== false ? $remoteAddress : 'unknown';
    }

    public static function secureUrl(string $appUrl, string $requestUri): string
    {
        $requestUri = str_replace(["\r", "\n"], '', $requestUri);
        if (preg_match('#^https?://#i', $requestUri) === 1) {
            $path = (string) parse_url($requestUri, PHP_URL_PATH);
            $query = parse_url($requestUri, PHP_URL_QUERY);
            $requestUri = $path . (is_string($query) && $query !== '' ? '?' . $query : '');
        }

        $basePath = rtrim((string) parse_url($appUrl, PHP_URL_PATH), '/');
        if ($basePath !== '' && ($requestUri === $basePath || str_starts_with($requestUri, $basePath . '/'))) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        return rtrim($appUrl, '/') . '/' . ltrim($requestUri, '/');
    }

    public static function sendHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('X-Frame-Options: DENY');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cache-Control: private, no-store, max-age=0');
        if (Env::get('APP_ENV', 'local') === 'production' && self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; "
            . "img-src 'self' data:; style-src 'self'; "
            . "script-src 'self' https://www.gstatic.com; "
            . "connect-src 'self' https://*.googleapis.com; font-src 'self'; "
            . "manifest-src 'self'; worker-src 'self'"
        );
    }

    private static function isTrustedProxy(string $address): bool
    {
        if ($address === '' || filter_var($address, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $configured = Env::get('TRUSTED_PROXY_IPS', '') ?? '';
        $proxies = array_filter(array_map('trim', explode(',', $configured)));

        return in_array($address, $proxies, true);
    }

    /** @param array<string, mixed>|null $server */
    private static function serverValue(string $name, ?array $server, string $default = ''): string
    {
        if ($server === null) {
            return RequestInput::serverString($name, $default);
        }

        $value = $server[$name] ?? null;

        return is_string($value) ? $value : $default;
    }
}
