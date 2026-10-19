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

$values = [
    'name' => $client['name'],
    'organization' => $client['organization'] ?? '',
    'phone' => $client['phone'] ?? '',
    'email' => $client['email'] ?? '',
];
$errors = [];

if (isPostRequest()) {
    foreach (array_keys($values) as $field) {
        $values[$field] = trim(RequestInput::postString($field));
    }

    if (!Csrf::isValid(RequestInput::postString('_token'))) {
        http_response_code(419);
        $errors['form'] = 'This form expired. Refresh the page and try again.';
    } else {
        try {
            (new ClientService($repository))->update($client['id'], $user['id'], $values);
            Session::flash('success', sprintf('%s was updated.', $values['name']));
            redirect('clients.php');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        } catch (ClientNotFoundException) {
            http_response_code(404);
            $pageTitle = 'Client not found';
            $activeNav = 'clients';
            require $app['root_path'] . '/views/errors/not-found.php';
            exit;
        }
    }
}

$pageTitle = 'Edit client';
$activeNav = 'clients';
$formMode = 'edit';
$formAction = appUrl('client-edit.php?id=' . $client['id']);

require $app['root_path'] . '/views/clients/form.php';
