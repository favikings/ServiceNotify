<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array<string, int>, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';
$user = requireAuthenticatedUser();
$serviceId = requestedPositiveId();
$serviceRepository = new ServiceRepository($app['pdo']);
$service = $serviceId !== null ? $serviceRepository->findForUser($serviceId, $user['id']) : null;

if ($service === null) {
    http_response_code(404);
    $pageTitle = 'Service not found';
    $activeNav = 'services';
    require $app['root_path'] . '/views/errors/service-not-found.php';
    exit;
}

$clientRepository = new ClientRepository($app['pdo']);
$clients = $clientRepository->allForUser($user['id']);
$values = [
    'client_id' => (string) $service['client_id'],
    'type' => $service['type'],
    'provider' => $service['provider'] ?? '',
    'expiry_date' => $service['expiry_date'],
    'cost' => $service['cost'] ?? '',
    'currency' => $service['currency'] ?? '',
    'billing_cycle' => $service['billing_cycle'],
    'payment_account' => $service['payment_account'] ?? '',
];
$errors = [];
$reminderRepository = new ReminderRepository($app['pdo']);
$reminderInput = [];
$reminderValues = ReminderService::formValuesFromRows(
    $reminderRepository->allForServiceForUser($service['id'], $user['id'])
);

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
            $reminderService = new ReminderService($reminderRepository);
            (new ServiceManager($serviceRepository, $clientRepository, $reminderService, $app['pdo']))
                ->update($service['id'], $user['id'], $values, $reminderInput);
            Session::flash('success', sprintf('%s service was updated.', serviceTypeLabel($values['type'])));
            redirect('services.php');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        } catch (ServiceNotFoundException) {
            http_response_code(404);
            $pageTitle = 'Service not found';
            $activeNav = 'services';
            require $app['root_path'] . '/views/errors/service-not-found.php';
            exit;
        }
    }
}

$pageTitle = 'Edit service';
$activeNav = 'services';
$formMode = 'edit';
$formAction = appUrl('service-edit.php?id=' . $service['id']);

require $app['root_path'] . '/views/services/form.php';
