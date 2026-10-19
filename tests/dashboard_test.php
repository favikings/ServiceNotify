<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/DashboardFilters.php';
require_once dirname(__DIR__) . '/src/DashboardRepository.php';

$failures = [];

function expectDashboard(bool $condition, string $message): void
{
    global $failures;

    if (!$condition) {
        $failures[] = $message;
    }
}

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec(
    'CREATE TABLE users (id INTEGER PRIMARY KEY, name VARCHAR(100) NOT NULL);
     CREATE TABLE clients (
        id INTEGER PRIMARY KEY,
        user_id INTEGER NOT NULL,
        name VARCHAR(150) NOT NULL
     );
     CREATE TABLE services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        provider VARCHAR(150) DEFAULT NULL,
        expiry_date DATE NOT NULL
     );
     CREATE INDEX idx_expiry_date ON services(expiry_date);'
);
$pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Ada'), (2, 'Ben')");
$pdo->exec("INSERT INTO clients (id, user_id, name) VALUES (1, 1, 'Zeta Studio'), (2, 1, 'Alpha Labs'), (3, 2, 'Private Corp')");

$insert = $pdo->prepare(
    'INSERT INTO services (client_id, type, provider, expiry_date)
     VALUES (:client_id, :type, :provider, :expiry_date)'
);
for ($index = 0; $index < 12; $index++) {
    $insert->execute([
        'client_id' => $index % 3 === 0 ? 2 : 1,
        'type' => $index % 2 === 0 ? 'domain' : 'hosting',
        'provider' => 'Provider ' . ($index + 1),
        'expiry_date' => (new DateTimeImmutable('2030-01-01'))->modify('+' . ($index * 2) . ' days')->format('Y-m-d'),
    ]);
}
$insert->execute([
    'client_id' => 1,
    'type' => 'maintenance',
    'provider' => 'Far Future',
    'expiry_date' => '2030-03-01',
]);
$insert->execute([
    'client_id' => 3,
    'type' => 'domain',
    'provider' => 'Must Stay Private',
    'expiry_date' => '2030-01-03',
]);

$today = new DateTimeImmutable('2030-01-01');
$filters = DashboardFilters::fromInput([], $today);
$repository = new DashboardRepository($pdo);

expectDashboard($filters->dateFrom === '2030-01-01', 'The default dashboard range should start today.');
expectDashboard($filters->dateTo === '2030-01-31', 'The default dashboard range should include the next 30 days.');
expectDashboard($filters->clientId === null && $filters->type === null && $filters->page === 1, 'Default filters should include every client and type on page one.');

$total = $repository->countExpiriesForUser(1, $filters->dateFrom, $filters->dateTo, null);
$firstPage = $repository->expiriesForUser(1, $filters->dateFrom, $filters->dateTo, null, DashboardFilters::PAGE_SIZE, 0);
$secondPage = $repository->expiriesForUser(1, $filters->dateFrom, $filters->dateTo, null, DashboardFilters::PAGE_SIZE, DashboardFilters::PAGE_SIZE);

expectDashboard($total === 12, 'The upcoming count should include only the signed-in user records in the date window.');
expectDashboard(count($firstPage) === 10 && count($secondPage) === 2, 'Dashboard results should paginate at ten rows per page.');
expectDashboard($firstPage[0]['expiry_date'] === '2030-01-01' && $secondPage[1]['expiry_date'] === '2030-01-23', 'Pages should preserve chronological expiry ordering.');
expectDashboard(!in_array('Must Stay Private', array_column($firstPage, 'provider'), true), 'Another user service must never appear in dashboard results.');

$domainFilters = DashboardFilters::fromInput(['type' => 'domain'], $today);
$domainTotal = $repository->countExpiriesForUser(1, $domainFilters->dateFrom, $domainFilters->dateTo, $domainFilters->type);
expectDashboard($domainTotal === 6, 'The service type filter should return only matching owned services.');

$clientFilters = DashboardFilters::fromInput(['client_id' => '2'], $today);
$clientTotal = $repository->countExpiriesForUser(1, $clientFilters->dateFrom, $clientFilters->dateTo, null, $clientFilters->clientId);
expectDashboard($clientFilters->clientId === 2 && $clientTotal === 4, 'The client filter should return only services for that owned client.');
expectDashboard(
    $repository->countExpiriesForUser(1, $filters->dateFrom, $filters->dateTo, null, 3) === 0,
    'A client owned by another user must not leak through dashboard filtering.'
);

$rangeFilters = DashboardFilters::fromInput([
    'date_from' => '2030-01-05',
    'date_to' => '2030-01-09',
], $today);
$rangeTotal = $repository->countExpiriesForUser(1, $rangeFilters->dateFrom, $rangeFilters->dateTo, null);
expectDashboard($rangeTotal === 3, 'The custom date range should be inclusive at both boundaries.');

$invalidFilters = DashboardFilters::fromInput([
    'type' => 'email',
    'date_from' => '2030-02-30',
    'date_to' => 'not-a-date',
    'page' => '-4',
], $today);
expectDashboard(
    $invalidFilters->type === null
        && $invalidFilters->dateFrom === '2030-01-01'
        && $invalidFilters->dateTo === '2030-01-31'
        && $invalidFilters->page === 1
        && count($invalidFilters->errors) === 3,
    'Invalid dashboard inputs should fall back to safe defaults with validation errors.'
);

$reversedFilters = DashboardFilters::fromInput([
    'date_from' => '2030-02-01',
    'date_to' => '2030-01-01',
], $today);
expectDashboard(
    isset($reversedFilters->errors['date_range'])
        && $reversedFilters->dateFrom === '2030-01-01'
        && $reversedFilters->dateTo === '2030-01-31',
    'A reversed date range should be rejected and reset safely.'
);

$arrayFilters = DashboardFilters::fromInput([
    'type' => ['domain'],
    'date_from' => ['2030-01-01'],
    'date_to' => ['2030-01-31'],
    'client_id' => ['1'],
], $today);
expectDashboard(
    count($arrayFilters->errors) === 4,
    'Array-shaped query parameters should be rejected without reaching the database.'
);

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: Dashboard filtering, ownership, ordering, and pagination meet Prompt 6 acceptance checks.\n");
