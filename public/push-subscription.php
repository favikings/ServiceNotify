<?php

declare(strict_types=1);

/** @var array{pdo: PDO, auth: array<string, int>, root_path: string} $app */
$app = require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isPostRequest()) {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$user = Session::user();
if ($user === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}
if (!Csrf::isValid(RequestInput::serverString('HTTP_X_CSRF_TOKEN'))) {
    http_response_code(419);
    echo json_encode(['error' => 'This request expired. Refresh the page and try again.']);
    exit;
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true, 8, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body.']);
    exit;
}

$action = is_array($payload) && is_string($payload['action'] ?? null) ? $payload['action'] : '';
$destination = is_array($payload) && is_string($payload['destination'] ?? null) ? $payload['destination'] : '';
$subscriptions = new PushSubscriptionService(new UserRepository($app['pdo']));

try {
    if ($action === 'enroll') {
        $subscriptions->enroll($user['id'], $destination);
    } elseif ($action === 'revoke') {
        $subscriptions->revoke($user['id'], $destination);
    } else {
        throw new ValidationException(['action' => 'Choose a valid Push subscription action.']);
    }
} catch (ValidationException $exception) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', array_values($exception->errors()))]);
    exit;
}

echo json_encode(['ok' => true, 'state' => $action === 'enroll' ? 'enabled' : 'disabled']);
