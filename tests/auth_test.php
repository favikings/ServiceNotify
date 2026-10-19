<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Env.php';
require_once dirname(__DIR__) . '/src/RequestInput.php';
require_once dirname(__DIR__) . '/src/Security.php';
require_once dirname(__DIR__) . '/src/Csrf.php';
require_once dirname(__DIR__) . '/src/Session.php';
require_once dirname(__DIR__) . '/src/ValidationException.php';
require_once dirname(__DIR__) . '/src/DuplicateEmailException.php';
require_once dirname(__DIR__) . '/src/UserRepository.php';
require_once dirname(__DIR__) . '/src/AuthService.php';
require_once dirname(__DIR__) . '/src/LoginRateLimiter.php';

$failures = [];

function expect(bool $condition, string $message): void
{
    global $failures;

    if (!$condition) {
        $failures[] = $message;
    }
}

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec(
    'CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        settings TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )'
);

$repository = new UserRepository($pdo);
$auth = new AuthService($repository);
$plainPassword = 'secure-pass-123';
$user = $auth->register('Ada Okafor', '  ADA@Example.COM ', $plainPassword, $plainPassword);

expect($user['id'] === 1, 'Registration should return the new user ID.');
expect($user['email'] === 'ada@example.com', 'Registration should normalize email addresses.');

$storedUser = $repository->findByEmail('ada@example.com');
expect($storedUser !== null, 'Registration should persist the user.');
expect(
    $storedUser !== null && $storedUser['password'] !== $plainPassword,
    'The stored password must not be plaintext.'
);
expect(
    $storedUser !== null && password_verify($plainPassword, $storedUser['password']),
    'The stored password hash should verify.'
);

$loggedInUser = $auth->attempt('ADA@example.com', $plainPassword);
expect($loggedInUser !== null && $loggedInUser['id'] === 1, 'Valid credentials should authenticate.');
expect($auth->attempt('ada@example.com', 'wrong-password') === null, 'Wrong password should fail.');
expect($auth->attempt('missing@example.com', 'wrong-password') === null, 'Unknown email should fail.');

$duplicateRejected = false;

try {
    $auth->register('Another Ada', 'ada@example.com', $plainPassword, $plainPassword);
} catch (DuplicateEmailException) {
    $duplicateRejected = true;
}

expect($duplicateRejected, 'Duplicate email registration should be rejected.');

$invalidRejected = false;

try {
    $auth->register('', 'not-an-email', 'short', 'different');
} catch (ValidationException $exception) {
    $errors = $exception->errors();
    $invalidRejected = isset(
        $errors['name'],
        $errors['email'],
        $errors['password'],
        $errors['password_confirmation']
    );
}

expect($invalidRejected, 'Registration should reject every invalid field.');

$_SESSION = [];
$csrfToken = Csrf::token();
expect(strlen($csrfToken) === 64, 'CSRF token should contain 32 random bytes as hex.');
expect(Csrf::isValid($csrfToken), 'The issued CSRF token should validate.');
expect(!Csrf::isValid('incorrect-token'), 'An incorrect CSRF token should fail.');

$_SESSION = [
    'auth_user' => ['id' => 1, 'name' => 'Ada Okafor', 'email' => 'ada@example.com'],
    'last_activity_at' => 100,
];
$expired = Session::enforceTimeout(1800, 1900);
expect($expired, 'A session should expire at the configured inactivity threshold.');
expect(!Session::check(), 'An expired session should remove authenticated user state.');
expect(Session::pullFlash('notice') !== null, 'Session expiry should create a user-facing notice.');

$rateLimitFile = tempnam(sys_get_temp_dir(), 'service-notify-rate-limit-');

if ($rateLimitFile === false) {
    $failures[] = 'Unable to create the temporary rate-limit store.';
} else {
    $limiter = new LoginRateLimiter($rateLimitFile, 5, 900);
    $key = $limiter->key('ada@example.com', '127.0.0.1');
    $ipKey = $limiter->ipKey('127.0.0.1');

    expect($key !== $ipKey, 'Account/IP and IP-wide limiter keys should use distinct namespaces.');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $limiter->hit($key, 100 + $attempt);
    }

    expect($limiter->tooManyAttempts($key, 105), 'Five failed attempts should trigger throttling.');
    expect($limiter->retryAfter($key, 105) > 0, 'A throttled key should report retry time.');
    $limiter->clear($key);
    expect(!$limiter->tooManyAttempts($key, 105), 'Successful login should be able to clear throttling.');
    unlink($rateLimitFile);
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: Authentication behavior meets the Prompt 2 acceptance checks.\n");
