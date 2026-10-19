<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Env.php';
require_once dirname(__DIR__) . '/src/helpers.php';
require_once dirname(__DIR__) . '/src/ValidationException.php';
require_once dirname(__DIR__) . '/src/ClientRepository.php';
require_once dirname(__DIR__) . '/src/ServiceNotFoundException.php';
require_once dirname(__DIR__) . '/src/ServiceRepository.php';
require_once dirname(__DIR__) . '/src/ServiceManager.php';

$failures = [];

function expectService(bool $condition, string $message): void
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
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(
    'CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL
    );
    CREATE TABLE clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        name VARCHAR(150) NOT NULL,
        organization VARCHAR(150) DEFAULT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        email VARCHAR(150) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    CREATE TABLE services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        client_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        provider VARCHAR(150) DEFAULT NULL,
        expiry_date DATE NOT NULL,
        cost DECIMAL(10,2) DEFAULT NULL,
        currency CHAR(3) DEFAULT NULL,
        billing_cycle VARCHAR(20) DEFAULT "yearly",
        payment_account VARCHAR(150) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    );
    CREATE INDEX idx_expiry_date ON services(expiry_date);
    CREATE TABLE reminders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_id INTEGER NOT NULL,
        frequency INTEGER NOT NULL,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    )'
);
$pdo->exec("INSERT INTO users (name) VALUES ('Ada'), ('Ben')");
$pdo->exec("INSERT INTO clients (user_id, name) VALUES (1, 'Zeta Studio'), (1, 'Alpha Labs'), (2, 'Private Corp')");

$clients = new ClientRepository($pdo);
$repository = new ServiceRepository($pdo);
$manager = new ServiceManager($repository, $clients);

$laterId = $manager->create(1, [
    'client_id' => '1',
    'type' => 'DOMAIN',
    'provider' => '  Cloudflare ',
    'expiry_date' => '2027-12-01',
    'cost' => '19.5',
    'currency' => ' usd ',
    'billing_cycle' => 'YEARLY',
    'payment_account' => ' Business card ',
]);
$later = $repository->findForUser($laterId, 1);

expectService($later !== null, 'A valid service should be created.');
expectService($later !== null && $later['type'] === 'domain', 'Service type should be normalized.');
expectService($later !== null && $later['provider'] === 'Cloudflare', 'Provider should be trimmed.');
expectService($later !== null && $later['cost'] === '19.50', 'Cost should be normalized to two decimal places.');
expectService($later !== null && $later['currency'] === 'USD', 'Currency codes should be trimmed and normalized to uppercase.');
expectService($later !== null && $later['payment_account'] === 'Business card', 'Payment account should be trimmed.');

$earlierId = $manager->create(1, [
    'client_id' => 2,
    'type' => 'hosting',
    'provider' => '',
    'expiry_date' => '2027-01-10',
    'cost' => '',
    'currency' => 'NGN',
    'billing_cycle' => 'monthly',
    'payment_account' => '',
]);
$earlier = $repository->findForUser($earlierId, 1);
expectService(
    $earlier !== null && $earlier['provider'] === null && $earlier['cost'] === null && $earlier['currency'] === null && $earlier['payment_account'] === null,
    'Blank cost should also clear currency while optional service fields become null.'
);

$privateId = $manager->create(2, [
    'client_id' => 3,
    'type' => 'maintenance',
    'expiry_date' => '2027-06-01',
    'billing_cycle' => 'custom',
]);

$list = $repository->allForUser(1);
expectService(count($list) === 2, 'The service list should include only services owned through the current user clients.');
expectService($list[0]['id'] === $earlierId && $list[1]['id'] === $laterId, 'Services should be ordered by nearest expiry.');
expectService($repository->countForUser(1) === 2, 'Service counts should be ownership scoped.');
expectService($repository->findForUser($privateId, 1) === null, 'A user should not read another user service.');

$invalidDateRejected = false;

try {
    $manager->create(1, [
        'client_id' => 1,
        'type' => 'domain',
        'expiry_date' => '2027-02-30',
        'billing_cycle' => 'yearly',
    ]);
} catch (ValidationException $exception) {
    $invalidDateRejected = isset($exception->errors()['expiry_date']);
}

expectService($invalidDateRejected, 'An impossible YYYY-MM-DD date should be rejected.');

$invalidCostRejected = false;

try {
    $manager->create(1, [
        'client_id' => 1,
        'type' => 'domain',
        'expiry_date' => '2027-02-28',
        'cost' => '-1.00',
        'billing_cycle' => 'yearly',
    ]);
} catch (ValidationException $exception) {
    $invalidCostRejected = isset($exception->errors()['cost']);
}

expectService($invalidCostRejected, 'A negative cost should be rejected.');

$missingCurrencyRejected = false;

try {
    $manager->create(1, [
        'client_id' => 1,
        'type' => 'domain',
        'expiry_date' => '2027-02-28',
        'cost' => '25.00',
        'currency' => '',
        'billing_cycle' => 'yearly',
    ]);
} catch (ValidationException $exception) {
    $missingCurrencyRejected = isset($exception->errors()['currency']);
}

expectService($missingCurrencyRejected, 'A priced service should require a 3-letter currency code.');

$foreignClientRejected = false;

try {
    $manager->create(1, [
        'client_id' => 3,
        'type' => 'hosting',
        'expiry_date' => '2027-02-28',
        'billing_cycle' => 'yearly',
    ]);
} catch (ValidationException $exception) {
    $foreignClientRejected = isset($exception->errors()['client_id']);
}

expectService($foreignClientRejected, 'A service cannot be attached to another user client.');

$manager->update($earlierId, 1, [
    'client_id' => 1,
    'type' => 'maintenance',
    'provider' => 'Care Plan',
    'expiry_date' => '2027-03-15',
    'cost' => '1200',
    'currency' => 'ngn',
    'billing_cycle' => 'custom',
    'payment_account' => 'Invoice',
]);
$updated = $repository->findForUser($earlierId, 1);
expectService(
    $updated !== null && $updated['client_id'] === 1 && $updated['type'] === 'maintenance' && $updated['cost'] === '1200.00' && $updated['currency'] === 'NGN',
    'An owner should be able to update currency and reassign a service between their clients.'
);

$foreignUpdateRejected = false;

try {
    $manager->update($privateId, 1, [
        'client_id' => 1,
        'type' => 'domain',
        'expiry_date' => '2027-04-01',
        'billing_cycle' => 'yearly',
    ]);
} catch (ServiceNotFoundException) {
    $foreignUpdateRejected = true;
}

expectService($foreignUpdateRejected, 'A user should not update another user service.');

$foreignDeleteRejected = false;

try {
    $manager->delete($privateId, 1);
} catch (ServiceNotFoundException) {
    $foreignDeleteRejected = true;
}

expectService($foreignDeleteRejected, 'A user should not delete another user service.');
expectService($repository->findForUser($privateId, 2) !== null, 'A failed cross-user deletion should preserve the service.');

$insertReminder = $pdo->prepare('INSERT INTO reminders (service_id, frequency) VALUES (:service_id, 7)');
$insertReminder->execute(['service_id' => $laterId]);
$manager->delete($laterId, 1);
expectService($repository->findForUser($laterId, 1) === null, 'An owner should be able to delete a service.');
expectService((int) $pdo->query('SELECT COUNT(*) FROM reminders')->fetchColumn() === 0, 'Deleting a service should cascade to reminders.');

$today = new DateTimeImmutable('2027-06-10');
expectService(serviceExpiryState('2027-06-09', $today)['key'] === 'overdue', 'Past dates should be marked overdue.');
expectService(serviceExpiryState('2027-06-10', $today)['key'] === 'today', 'Today should remain a distinct expiry state.');
expectService(serviceExpiryState('2027-06-15', $today)['key'] === 'urgent', 'Dates within seven days should be urgent.');
expectService(serviceExpiryState('2027-07-01', $today)['key'] === 'due-soon', 'Dates within thirty days should be due soon.');
expectService(serviceExpiryState('2027-08-01', $today)['key'] === 'healthy', 'Dates beyond thirty days should be healthy.');
expectService(formatMoney('45000.00', 'NGN') === '₦45,000.00 NGN', 'Naira should render with its sign and code.');
expectService(formatMoney('120.00', 'USD') === '$120.00 USD', 'US dollars should render with their sign and code.');
expectService(formatMoney('99.50', 'CAD') === 'CAD 99.50', 'Other currencies should render unambiguously by code.');
expectService(formatMoney('19.50', null) === '19.50 · Currency needed', 'Legacy priced rows should clearly request a currency.');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: Service management meets the Prompt 4 acceptance checks.\n");
