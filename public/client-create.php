<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array{session_idle_seconds: int, max_login_attempts: int, login_window_seconds: int}, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';
$user = requireAuthenticatedUser();
$values = [
    'name' => '',
    'organization' => '',
    'phone' => '',
    'email' => '',
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
            (new ClientService(new ClientRepository($app['pdo'])))->create($user['id'], $values);
            Session::flash('success', sprintf('%s was added to your clients.', $values['name']));
            redirect('clients.php');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        }
    }
}

$pageTitle = 'Add client';
$activeNav = 'clients';
$formMode = 'create';
$formAction = appUrl('client-create.php');

require $app['root_path'] . '/views/clients/form.php';
