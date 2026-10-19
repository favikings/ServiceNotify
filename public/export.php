<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array<string, int>, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';

$user = requireAuthenticatedUser();
$format = strtolower(trim(RequestInput::getString('format')));
if (!in_array($format, ['csv', 'xlsx'], true)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Choose csv or xlsx as the export format.';
    exit;
}

$today = new DateTimeImmutable('today');
$filters = DashboardFilters::fromInput(
    RequestInput::query(['client_id', 'type', 'date_from', 'date_to', 'page']),
    $today
);
if ($filters->errors !== []) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=UTF-8');
    echo implode(' ', array_values($filters->errors));
    exit;
}

$clients = (new ClientRepository($app['pdo']))->allForUser($user['id']);
$selectedClient = null;
if ($filters->clientId !== null) {
    foreach ($clients as $client) {
        if ($client['id'] === $filters->clientId) {
            $selectedClient = $client;
            break;
        }
    }

    if ($selectedClient === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Client not found.';
        exit;
    }
}

$rows = (new ReportRepository($app['pdo']))->servicesForUser(
    $user['id'],
    $filters->dateFrom,
    $filters->dateTo,
    $filters->clientId,
    $filters->type
);
$exporter = new ReportExporter();
$autoloadPath = $app['root_path'] . '/vendor/autoload.php';
if ($format === 'xlsx') {
    if (!is_file($autoloadPath)) {
        throw new RuntimeException('Composer dependencies are missing. Run composer install.');
    }
    require_once $autoloadPath;
}
$filename = sprintf(
    'servicenotify-services-%s-to-%s.%s',
    $filters->dateFrom,
    $filters->dateTo,
    $format
);

session_write_close();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('Content-Disposition: attachment; filename="' . $filename . '"');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    $stream = fopen('php://output', 'wb');
    if ($stream === false) {
        throw new RuntimeException('Unable to open the CSV output stream.');
    }
    $exporter->writeCsv($rows, $stream);
    fclose($stream);
    exit;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$exporter->writeXlsx($rows, 'php://output', [
    'client' => $selectedClient['name'] ?? 'All clients',
    'service_type' => $filters->type !== null ? serviceTypeLabel($filters->type) : 'All service types',
    'date_range' => $filters->dateFrom . ' to ' . $filters->dateTo,
    'generated_at' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
]);
exit;
