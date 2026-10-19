<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array{session_idle_seconds: int, max_login_attempts: int, login_window_seconds: int}, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';

if (!isPostRequest()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

if (!Csrf::isValid(RequestInput::postString('_token'))) {
    http_response_code(419);
    exit('This request expired. Return to the application and try again.');
}

Session::logout();
Session::flash('notice', 'You have signed out safely.');
redirect('login.php');
