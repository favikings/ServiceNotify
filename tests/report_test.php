<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/src/ReportRepository.php';
require_once dirname(__DIR__) . '/src/ReportExporter.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

$failures = [];

function expectReport(bool $condition, string $message): void
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
        name VARCHAR(150) NOT NULL,
        organization VARCHAR(150),
        phone VARCHAR(20),
        email VARCHAR(150)
     );
     CREATE TABLE services (
        id INTEGER PRIMARY KEY,
        client_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        provider VARCHAR(150),
        expiry_date DATE NOT NULL,
        cost DECIMAL(10,2),
        currency CHAR(3),
        billing_cycle VARCHAR(20) NOT NULL,
        payment_account VARCHAR(150)
     );'
);
$pdo->exec("INSERT INTO users (id, name) VALUES (1, 'Ada'), (2, 'Ben')");
$pdo->exec(
    "INSERT INTO clients (id, user_id, name, organization, phone, email) VALUES
        (1, 1, '=Formula Client', 'Atlas Studio', '+2348011111111', 'atlas@example.test'),
        (2, 1, 'Beacon Ltd', NULL, NULL, NULL),
        (3, 2, 'Private Corp', 'Hidden', '+2348022222222', 'private@example.test')"
);
$pdo->exec(
    "INSERT INTO services (id, client_id, type, provider, expiry_date, cost, currency, billing_cycle, payment_account) VALUES
        (1, 1, 'domain', 'Cloudflare', '2030-01-05', 12500.50, 'NGN', 'yearly', 'Operations card'),
        (2, 2, 'hosting', 'Render', '2030-01-09', NULL, NULL, 'monthly', NULL),
        (3, 1, 'hosting', 'Fly', '2030-02-15', 40, 'USD', 'monthly', 'Virtual card'),
        (4, 3, 'domain', 'Secret provider', '2030-01-04', 99, 'USD', 'yearly', 'Hidden account')"
);

$repository = new ReportRepository($pdo);
$rows = $repository->servicesForUser(1, '2030-01-01', '2030-01-31', null, null);
expectReport(count($rows) === 2, 'The report query should include only the signed-in user and requested date range.');
expectReport($rows[0]['client_name'] === '=Formula Client' && $rows[1]['client_name'] === 'Beacon Ltd', 'Report rows should use expiry-first stable ordering.');
expectReport(!in_array('Private Corp', array_column($rows, 'client_name'), true), 'Another user\'s client data must never be exported.');
expectReport($rows[0]['cost'] === '12500.50' && $rows[0]['currency'] === 'NGN', 'Cost and currency should remain separate and unambiguous.');

$clientRows = $repository->servicesForUser(1, '2030-01-01', '2030-12-31', 1, 'hosting');
expectReport(count($clientRows) === 1 && $clientRows[0]['provider'] === 'Fly', 'Client and service-type filters should combine.');
expectReport(
    $repository->servicesForUser(1, '2030-01-01', '2030-12-31', 3, null) === [],
    'Filtering by another user\'s client ID should return no export rows.'
);

$dashboardView = file_get_contents(dirname(__DIR__) . '/views/dashboard.php');
$reportsView = file_get_contents(dirname(__DIR__) . '/views/reports.php');
$desktopNav = file_get_contents(dirname(__DIR__) . '/views/partials/app-top.php');
$mobileNav = file_get_contents(dirname(__DIR__) . '/views/partials/app-bottom.php');
expectReport(
    is_string($dashboardView) && !str_contains($dashboardView, 'export.php') && !str_contains($dashboardView, 'client_id'),
    'The dashboard should not contain report downloads or report-only client filtering.'
);
expectReport(
    is_string($reportsView)
        && str_contains($reportsView, "appUrl('reports.php')")
        && str_contains($reportsView, "&format=csv")
        && str_contains($reportsView, "&format=xlsx"),
    'The dedicated Reports page should own report filters and both download formats.'
);
expectReport(
    is_string($desktopNav) && is_string($mobileNav)
        && str_contains($desktopNav, "appUrl('reports.php')")
        && str_contains($mobileNav, "appUrl('reports.php')"),
    'Desktop and mobile navigation should link to the dedicated Reports page.'
);

$exporter = new ReportExporter();
$csvStream = fopen('php://temp', 'w+b');
if ($csvStream === false) {
    throw new RuntimeException('Unable to create CSV test stream.');
}
$exporter->writeCsv($rows, $csvStream);
rewind($csvStream);
$csv = stream_get_contents($csvStream);
fclose($csvStream);
expectReport(is_string($csv) && str_starts_with($csv, "\xEF\xBB\xBFClient,Organization,\"Client Email\""), 'CSV should contain a UTF-8 BOM and approved headers.');
expectReport(is_string($csv) && str_contains($csv, "'=Formula Client"), 'CSV should neutralize spreadsheet-formula prefixes in user text.');
expectReport(is_string($csv) && !str_contains($csv, 'Private Corp'), 'CSV output should contain only repository-scoped rows.');

$xlsxPath = tempnam(sys_get_temp_dir(), 'servicenotify-report-');
if ($xlsxPath === false) {
    throw new RuntimeException('Unable to create XLSX test file.');
}
$xlsxFile = $xlsxPath . '.xlsx';
rename($xlsxPath, $xlsxFile);

try {
    $exporter->writeXlsx($rows, $xlsxFile, [
        'client' => 'All clients',
        'service_type' => 'All service types',
        'date_range' => '2030-01-01 to 2030-01-31',
        'generated_at' => '2030-01-01T08:00:00+01:00',
    ]);
    $spreadsheet = IOFactory::load($xlsxFile);
    $sheet = $spreadsheet->getSheetByName('Services');
    $details = $spreadsheet->getSheetByName('Report details');
    expectReport($sheet !== null && $sheet->getCell('A1')->getValue() === 'Client', 'Excel should use the approved first column.');
    expectReport($sheet !== null && $sheet->getCell('K1')->getValue() === 'Payment Account', 'Excel should use the approved final column.');
    expectReport(
        $sheet !== null && $sheet->getCell('A2')->getValue() === '=Formula Client' && $sheet->getCell('A2')->getDataType() === DataType::TYPE_STRING,
        'Excel should store user text explicitly rather than evaluating formulas.'
    );
    expectReport($sheet !== null && (float) $sheet->getCell('H2')->getValue() === 12500.5, 'Excel should preserve costs as numeric cells.');
    expectReport($details !== null && $details->getCell('B4')->getValue() === '2030-01-01 to 2030-01-31', 'Excel should communicate the active report date range.');
    $spreadsheet->disconnectWorksheets();
} finally {
    if (is_file($xlsxFile)) {
        unlink($xlsxFile);
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: The dedicated Reports page and ownership-scoped CSV/Excel exports honor every Prompt 8 contract.\n");
