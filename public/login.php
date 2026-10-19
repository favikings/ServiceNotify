<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array{session_idle_seconds: int, max_login_attempts: int, max_ip_login_attempts: int, login_window_seconds: int}, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';

if (Session::check()) {
    redirect('dashboard.php');
}

$email = '';
$errors = [];
$notice = Session::pullFlash('notice');

if (isPostRequest()) {
    $email = AuthService::normalizeEmail(RequestInput::postString('email'));

    if (!Csrf::isValid(RequestInput::postString('_token'))) {
        http_response_code(419);
        $errors['form'] = 'This form expired. Refresh the page and try again.';
    } else {
        $storagePath = $app['root_path'] . '/logs/login_attempts.json';
        $accountLimiter = new LoginRateLimiter(
            $storagePath,
            $app['auth']['max_login_attempts'],
            $app['auth']['login_window_seconds']
        );
        $ipLimiter = new LoginRateLimiter(
            $storagePath,
            $app['auth']['max_ip_login_attempts'],
            $app['auth']['login_window_seconds']
        );
        $ipAddress = clientIpAddress();
        $accountKey = $accountLimiter->key($email, $ipAddress);
        $ipKey = $ipLimiter->ipKey($ipAddress);

        if ($accountLimiter->tooManyAttempts($accountKey) || $ipLimiter->tooManyAttempts($ipKey)) {
            $retryAfter = max(
                $accountLimiter->retryAfter($accountKey),
                $ipLimiter->retryAfter($ipKey)
            );
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $retryMinutes = max(1, (int) ceil($retryAfter / 60));
            $errors['form'] = sprintf(
                'Too many sign-in attempts. Try again in %d minute%s.',
                $retryMinutes,
                $retryMinutes === 1 ? '' : 's'
            );
        } else {
            $auth = new AuthService(new UserRepository($app['pdo']));
            $user = $auth->attempt($email, RequestInput::postString('password'));

            if ($user === null) {
                $accountLimiter->hit($accountKey);
                $ipLimiter->hit($ipKey);
                $errors['form'] = 'The email or password is incorrect.';
            } else {
                $accountLimiter->clear($accountKey);
                Session::login($user);
                redirect('dashboard.php');
            }
        }
    }
}

$pageTitle = 'Welcome back';
$authMode = 'login';

require $app['root_path'] . '/views/auth/login.php';
