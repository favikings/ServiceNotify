<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Env.php';
require_once dirname(__DIR__) . '/src/RequestInput.php';
require_once dirname(__DIR__) . '/src/Security.php';
require_once dirname(__DIR__) . '/src/ValidationException.php';
require_once dirname(__DIR__) . '/src/DuplicateEmailException.php';
require_once dirname(__DIR__) . '/src/UserRepository.php';
require_once dirname(__DIR__) . '/src/AuthService.php';
require_once dirname(__DIR__) . '/src/LoginRateLimiter.php';

$failures = [];

function expectSecurity(bool $condition, string $message): void
{
    global $failures;

    if (!$condition) {
        $failures[] = $message;
    }
}

$rootPath = dirname(__DIR__);
$postForms = 0;
$csrfFields = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath . '/views')) as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $source = file_get_contents($file->getPathname());
    if ($source === false) {
        $failures[] = 'Unable to inspect ' . $file->getPathname() . '.';
        continue;
    }

    $postForms += preg_match_all('/\bmethod="post"/i', $source);
    $csrfFields += preg_match_all('/<input\b[^>]*type="hidden"[^>]*name="_token"[^>]*>/i', $source);
}

expectSecurity($postForms === 8, 'All eight server-mutating HTML forms should be discovered.');
expectSecurity($csrfFields === $postForms, 'Every server-mutating HTML form should include a hidden CSRF token.');

$publicSources = '';
foreach (glob($rootPath . '/public/*.php') ?: [] as $path) {
    $source = file_get_contents($path);
    $publicSources .= $source === false ? '' : $source;
}
expectSecurity(
    preg_match('/\$_(?:GET|POST|REQUEST|SERVER)\b/', $publicSources) !== 1,
    'Public controllers should extract request data through RequestInput, not request superglobals.'
);

$requestInputSource = file_get_contents($rootPath . '/src/RequestInput.php');
expectSecurity(
    is_string($requestInputSource) && str_contains($requestInputSource, 'filter_input('),
    'The centralized request boundary should use filter_input().'
);

putenv('TRUSTED_PROXY_IPS=10.0.0.1,10.0.0.2');
expectSecurity(Security::isHttps(['HTTPS' => 'on']), 'A direct HTTPS request should be recognized.');
expectSecurity(
    !Security::isHttps(['REMOTE_ADDR' => '198.51.100.8', 'HTTP_X_FORWARDED_PROTO' => 'https']),
    'Forwarded HTTPS must be ignored from an untrusted sender.'
);
expectSecurity(
    Security::isHttps(['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_PROTO' => 'https']),
    'Forwarded HTTPS should be honored from a configured proxy.'
);
expectSecurity(
    Security::clientIpAddress([
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_X_FORWARDED_FOR' => '192.0.2.12, 198.51.100.9, 10.0.0.2',
    ]) === '198.51.100.9',
    'Client IP parsing should walk the trusted proxy chain from right to left.'
);
expectSecurity(
    Security::clientIpAddress([
        'REMOTE_ADDR' => '198.51.100.8',
        'HTTP_X_FORWARDED_FOR' => '192.0.2.12',
    ]) === '198.51.100.8',
    'An untrusted sender must not be able to spoof the client IP with X-Forwarded-For.'
);

$secureUrl = Security::secureUrl(
    'https://notify.example.com/ServiceNotify',
    "https://attacker.example/path?next=1\r\nX-Injected: yes"
);
expectSecurity(
    $secureUrl === 'https://notify.example.com/ServiceNotify/path?next=1X-Injected: yes',
    'HTTPS redirects should retain the canonical configured origin and strip header newlines.'
);

$rateLimitFile = tempnam(sys_get_temp_dir(), 'service-notify-security-');
if ($rateLimitFile === false) {
    $failures[] = 'Unable to create a rate-limit test store.';
} else {
    $limiter = new LoginRateLimiter($rateLimitFile, 2, 900);
    $firstAccount = $limiter->key('first@example.com', '192.0.2.20');
    $secondAccount = $limiter->key('second@example.com', '192.0.2.20');
    $ipKey = $limiter->ipKey('192.0.2.20');

    expectSecurity($firstAccount !== $secondAccount, 'Account/IP limiter keys should distinguish accounts.');
    expectSecurity($ipKey !== $firstAccount, 'IP-wide limiter keys should use a separate namespace.');
    $limiter->hit($ipKey, 100);
    $limiter->hit($ipKey, 101);
    expectSecurity($limiter->tooManyAttempts($ipKey, 102), 'The IP-wide limiter should reach its threshold.');
    unlink($rateLimitFile);
}

putenv('TRUSTED_PROXY_IPS');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: Prompt 11 request, CSRF, HTTPS, proxy, and throttling controls are enforced.\n");
