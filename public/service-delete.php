<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array<string, int>, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';
$user = requireAuthenticatedUser();
$serviceId = requestedPositiveId();
$repository = new ServiceRepository($app['pdo']);
$service = $serviceId !== null ? $repository->findForUser($serviceId, $user['id']) : null;

if ($service === null) {
    http_response_code(404);
    $pageTitle = 'Service not found';
    $activeNav = 'services';
    require $app['root_path'] . '/views/errors/service-not-found.php';
    exit;
}

$error = null;

if (isPostRequest()) {
    if (!Csrf::isValid(RequestInput::postString('_token'))) {
        http_response_code(419);
        $error = 'This confirmation expired. Refresh the page and try again.';
    } else {
        try {
            (new ServiceManager($repository, new ClientRepository($app['pdo'])))->delete($service['id'], $user['id']);
            Session::flash('success', sprintf('%s service for %s was deleted.', serviceTypeLabel($service['type']), $service['client_name']));
            redirect('services.php');
        } catch (ServiceNotFoundException) {
            http_response_code(404);
            $pageTitle = 'Service not found';
            $activeNav = 'services';
            require $app['root_path'] . '/views/errors/service-not-found.php';
            exit;
        }
    }
}

$pageTitle = 'Delete service';
$activeNav = 'services';

require $app['root_path'] . '/views/services/delete.php';
