<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array<string, int>, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';
$user = requireAuthenticatedUser();
$clientRepository = new ClientRepository($app['pdo']);
$clients = $clientRepository->allForUser($user['id']);
$requestedClientId = RequestInput::getPositiveInt('client_id');
$requestedClient = $requestedClientId !== null ? $clientRepository->findForUser($requestedClientId, $user['id']) : null;
$values = [
    'client_id' => $requestedClient !== null ? (string) $requestedClient['id'] : (isset($clients[0]) ? (string) $clients[0]['id'] : ''),
    'type' => 'domain',
    'provider' => '',
    'expiry_date' => '',
    'cost' => '',
    'currency' => '',
    'billing_cycle' => 'yearly',
    'payment_account' => '',
];
$errors = [];
$reminderInput = [];
$reminderValues = ReminderService::formValuesFromInput([]);

if (isPostRequest()) {
    foreach (array_keys($values) as $field) {
        $values[$field] = trim(RequestInput::postString($field));
    }
    $reminderInput = RequestInput::postArray('reminders');
    $reminderValues = ReminderService::formValuesFromInput($reminderInput);

    if (!Csrf::isValid(RequestInput::postString('_token'))) {
        http_response_code(419);
        $errors['form'] = 'This form expired. Refresh the page and try again.';
    } else {
        try {
            $reminderService = new ReminderService(new ReminderRepository($app['pdo']));
            (new ServiceManager(new ServiceRepository($app['pdo']), $clientRepository, $reminderService, $app['pdo']))
                ->create($user['id'], $values, $reminderInput);
            Session::flash('success', sprintf('%s service was added.', serviceTypeLabel($values['type'])));
            redirect('services.php');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        }
    }
}

$pageTitle = 'Add service';
$activeNav = 'services';
$formMode = 'create';
$formAction = appUrl('service-create.php');

require $app['root_path'] . '/views/services/form.php';
