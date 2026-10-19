<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array{session_idle_seconds: int, max_login_attempts: int, login_window_seconds: int}, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';

$user = requireAuthenticatedUser();
$today = new DateTimeImmutable('today');
$dashboardInput = RequestInput::query(['type', 'date_from', 'date_to', 'page']);
$filters = DashboardFilters::fromInput($dashboardInput, $today);
$dashboardRepository = new DashboardRepository($app['pdo']);
$totalResults = $dashboardRepository->countExpiriesForUser(
    $user['id'],
    $filters->dateFrom,
    $filters->dateTo,
    $filters->type
);
$totalPages = max(1, (int) ceil($totalResults / DashboardFilters::PAGE_SIZE));
$currentPage = min($filters->page, $totalPages);
$expiries = $dashboardRepository->expiriesForUser(
    $user['id'],
    $filters->dateFrom,
    $filters->dateTo,
    $filters->type,
    DashboardFilters::PAGE_SIZE,
    ($currentPage - 1) * DashboardFilters::PAGE_SIZE
);

$urgentFrom = max($filters->dateFrom, $today->format('Y-m-d'));
$urgentTo = min($filters->dateTo, $today->modify('+7 days')->format('Y-m-d'));
$urgentCount = $urgentFrom <= $urgentTo
    ? $dashboardRepository->countExpiriesForUser($user['id'], $urgentFrom, $urgentTo, $filters->type)
    : 0;
$clientRepository = new ClientRepository($app['pdo']);
$clientCount = $clientRepository->countForUser($user['id']);
$serviceCount = (new ServiceRepository($app['pdo']))->countForUser($user['id']);
$success = Session::pullFlash('success');
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

require $app['root_path'] . '/views/dashboard.php';
