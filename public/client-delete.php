<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array{session_idle_seconds: int, max_login_attempts: int, login_window_seconds: int}, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';
$user = requireAuthenticatedUser();
$clientId = requestedPositiveId();
$repository = new ClientRepository($app['pdo']);
$client = $clientId !== null ? $repository->findForUser($clientId, $user['id']) : null;

if ($client === null) {
    http_response_code(404);
    $pageTitle = 'Client not found';
    $activeNav = 'clients';
    require $app['root_path'] . '/views/errors/not-found.php';
    exit;
}

$error = null;

if (isPostRequest()) {
    if (!Csrf::isValid(RequestInput::postString('_token'))) {
        http_response_code(419);
        $error = 'This confirmation expired. Refresh the page and try again.';
    } else {
        try {
            (new ClientService($repository))->delete($client['id'], $user['id']);
            Session::flash('success', sprintf('%s was deleted.', $client['name']));
            redirect('clients.php');
        } catch (ClientNotFoundException) {
            http_response_code(404);
            $pageTitle = 'Client not found';
            $activeNav = 'clients';
            require $app['root_path'] . '/views/errors/not-found.php';
            exit;
        }
    }
}

$pageTitle = 'Delete client';
$activeNav = 'clients';

require $app['root_path'] . '/views/clients/delete.php';
