<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);

require_once $rootPath . '/src/Env.php';
Env::load($rootPath . '/.env');
require_once $rootPath . '/src/RequestInput.php';
require_once $rootPath . '/src/Security.php';
Security::enforceHttps();
require_once $rootPath . '/src/helpers.php';
require_once $rootPath . '/src/Csrf.php';
require_once $rootPath . '/src/Session.php';
require_once $rootPath . '/src/ValidationException.php';
require_once $rootPath . '/src/DuplicateEmailException.php';
require_once $rootPath . '/src/UserRepository.php';
require_once $rootPath . '/src/PushSubscriptionService.php';
require_once $rootPath . '/src/AuthService.php';
require_once $rootPath . '/src/LoginRateLimiter.php';
require_once $rootPath . '/src/ClientNotFoundException.php';
require_once $rootPath . '/src/ClientRepository.php';
require_once $rootPath . '/src/ClientService.php';
require_once $rootPath . '/src/ServiceNotFoundException.php';
require_once $rootPath . '/src/ServiceRepository.php';
require_once $rootPath . '/src/ReminderRepository.php';
require_once $rootPath . '/src/ReminderService.php';
require_once $rootPath . '/src/ServiceManager.php';
require_once $rootPath . '/src/DashboardFilters.php';
require_once $rootPath . '/src/DashboardRepository.php';
require_once $rootPath . '/src/ReportRepository.php';
require_once $rootPath . '/src/ReportExporter.php';
require_once $rootPath . '/src/DeliveryResult.php';
require_once $rootPath . '/src/PushGateway.php';
require_once $rootPath . '/src/SmsGateway.php';
require_once $rootPath . '/src/HttpClient.php';
require_once $rootPath . '/src/FcmClient.php';
require_once $rootPath . '/src/AfricasTalkingClient.php';
require_once $rootPath . '/src/NotificationRepository.php';
require_once $rootPath . '/src/NotificationLogger.php';
require_once $rootPath . '/src/NotificationEngine.php';
require_once $rootPath . '/src/LogRepository.php';

/** @var PDO $pdo */
$pdo = require $rootPath . '/config/config.php';
require_once $rootPath . '/config/auth.php';

$authConfig = authConfig();
Session::start($authConfig['session_idle_seconds']);
Security::sendHeaders();

return [
    'pdo' => $pdo,
    'auth' => $authConfig,
    'root_path' => $rootPath,
];
