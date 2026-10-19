<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array{session_idle_seconds: int, max_login_attempts: int, login_window_seconds: int}, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';
$user = requireAuthenticatedUser();
$clients = (new ClientRepository($app['pdo']))->allForUser($user['id']);
$success = Session::pullFlash('success');
$pageTitle = 'Clients';
$activeNav = 'clients';

require $app['root_path'] . '/views/clients/index.php';

