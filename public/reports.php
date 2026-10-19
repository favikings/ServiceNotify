<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array<string, int>, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';

$user = requireAuthenticatedUser();
$today = new DateTimeImmutable('today');
$filters = DashboardFilters::fromInput(
    RequestInput::query(['client_id', 'type', 'date_from', 'date_to', 'page']),
    $today
);
$clients = (new ClientRepository($app['pdo']))->allForUser($user['id']);
$selectedClientName = 'All clients';
$reportErrors = $filters->errors;

if ($filters->clientId !== null) {
    $selectedClient = null;
    foreach ($clients as $client) {
        if ($client['id'] === $filters->clientId) {
            $selectedClient = $client;
            break;
        }
    }

    if ($selectedClient === null) {
        $reportErrors['client_id'] = 'Choose a client from your own workspace.';
    } else {
        $selectedClientName = $selectedClient['name'];
    }
}

$rows = $reportErrors === []
    ? (new ReportRepository($app['pdo']))->servicesForUser(
        $user['id'],
        $filters->dateFrom,
        $filters->dateTo,
        $filters->clientId,
        $filters->type
    )
    : [];
$exportQuery = http_build_query([
    'client_id' => $filters->clientId ?? '',
    'type' => $filters->type ?? '',
    'date_from' => $filters->dateFrom,
    'date_to' => $filters->dateTo,
]);
$pageTitle = 'Reports';
$activeNav = 'reports';

require $app['root_path'] . '/views/reports.php';
