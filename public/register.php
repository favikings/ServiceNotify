<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array{session_idle_seconds: int, max_login_attempts: int, login_window_seconds: int}, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';

if (Session::check()) {
    redirect('dashboard.php');
}

$values = [
    'name' => '',
    'email' => '',
];
$errors = [];

if (isPostRequest()) {
    $values['name'] = trim(RequestInput::postString('name'));
    $values['email'] = AuthService::normalizeEmail(RequestInput::postString('email'));

    if (!Csrf::isValid(RequestInput::postString('_token'))) {
        http_response_code(419);
        $errors['form'] = 'This form expired. Refresh the page and try again.';
    } else {
        $auth = new AuthService(new UserRepository($app['pdo']));

        try {
            $user = $auth->register(
                $values['name'],
                $values['email'],
                RequestInput::postString('password'),
                RequestInput::postString('password_confirmation')
            );
            Session::login($user);
            Session::flash('success', 'Welcome to ServiceNotify. Your account is ready.');
            redirect('dashboard.php');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        } catch (DuplicateEmailException) {
            $errors['email'] = 'An account already uses this email. Try signing in instead.';
        }
    }
}

$pageTitle = 'Create your account';
$authMode = 'register';

require $app['root_path'] . '/views/auth/register.php';
