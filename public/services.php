<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array<string, int>, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';
$user = requireAuthenticatedUser();
$services = (new ServiceRepository($app['pdo']))->allForUser($user['id']);
$clientCount = (new ClientRepository($app['pdo']))->countForUser($user['id']);
$success = Session::pullFlash('success');
$pageTitle = 'Services';
$activeNav = 'services';

require $app['root_path'] . '/views/services/index.php';
