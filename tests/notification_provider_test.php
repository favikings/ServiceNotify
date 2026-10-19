<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/DeliveryResult.php';
require_once dirname(__DIR__) . '/src/PushGateway.php';
require_once dirname(__DIR__) . '/src/SmsGateway.php';
require_once dirname(__DIR__) . '/src/HttpClient.php';
require_once dirname(__DIR__) . '/src/FcmClient.php';
require_once dirname(__DIR__) . '/src/AfricasTalkingClient.php';

$failures = [];

function expectProvider(bool $condition, string $message): void
{
    global $failures;

    if (!$condition) {
        $failures[] = $message;
    }
}

final class FakeHttpClient implements HttpClient
{
    /** @var list<HttpResponse> */
    public array $responses;
    /** @var list<array{url:string,headers:array<string,string>,body:string}> */
    public array $requests = [];

    /** @param list<HttpResponse> $responses */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function post(string $url, array $headers, string $body): HttpResponse
    {
        $this->requests[] = ['url' => $url, 'headers' => $headers, 'body' => $body];
        $response = array_shift($this->responses);

        if (!$response instanceof HttpResponse) {
            throw new RuntimeException('No fake HTTP response remains.');
        }

        return $response;
    }
}

$fcmHttp = new FakeHttpClient([
    new HttpResponse(200, '{"name":"projects/service-notify-test/messages/123"}'),
    new HttpResponse(404, '{"error":{"status":"UNREGISTERED","message":"Requested entity was not found."}}'),
]);
$fcm = new FcmClient(
    $fcmHttp,
    '',
    'service-notify-test',
    static fn (): string => 'mock-access-token'
);
try {
    $fcmSuccess = $fcm->send('valid-device-token', 'Expiry reminder', 'A domain expires soon.', ['service_id' => '42']);
    $fcmInvalid = $fcm->send('invalid-device-token', 'Expiry reminder', 'A domain expires soon.');

    expectProvider($fcmSuccess->successful, 'FCM should accept a successful HTTP v1 response.');
    expectProvider(!$fcmInvalid->successful && $fcmInvalid->invalidRecipient, 'FCM UNREGISTERED should identify a removable token.');
    expectProvider(count($fcmHttp->requests) === 2, 'FCM should issue one HTTP request for each notification.');
    expectProvider(
        $fcmHttp->requests[0]['url'] === 'https://fcm.googleapis.com/v1/projects/service-notify-test/messages:send'
            && ($fcmHttp->requests[0]['headers']['Authorization'] ?? null) === 'Bearer mock-access-token',
        'FCM should use the HTTP v1 project endpoint and bearer token.'
    );
    $fcmPayload = json_decode($fcmHttp->requests[0]['body'], true);
    expectProvider(
        ($fcmPayload['message']['token'] ?? null) === 'valid-device-token'
            && ($fcmPayload['message']['data']['service_id'] ?? null) === '42',
        'FCM should send the device token, notification content, and string data payload.'
    );

    $smsHttp = new FakeHttpClient([
        new HttpResponse(201, '{"SMSMessageData":{"Recipients":[{"statusCode":101,"status":"Success","messageId":"ATXid"}]}}'),
    ]);
    $sms = new AfricasTalkingClient($smsHttp, 'sandbox', 'secret-api-key', 'ServiceNotify', true);
    $smsResult = $sms->send('+2348012345678', 'A domain expires soon.');

    expectProvider($smsResult->successful, "Africa's Talking status code 101 should be accepted as successful.");
    expectProvider(
        $smsHttp->requests[0]['url'] === 'https://api.sandbox.africastalking.com/version1/messaging'
            && ($smsHttp->requests[0]['headers']['apiKey'] ?? null) === 'secret-api-key',
        "Africa's Talking sandbox request should use its configured endpoint and API key header."
    );
    parse_str($smsHttp->requests[0]['body'], $smsPayload);
    expectProvider(
        ($smsPayload['username'] ?? null) === 'sandbox'
            && ($smsPayload['to'] ?? null) === '+2348012345678'
            && ($smsPayload['from'] ?? null) === 'ServiceNotify',
        "Africa's Talking request should include the configured account, recipient, and sender ID."
    );

    $invalidPhone = $sms->send('08012345678', 'A domain expires soon.');
    expectProvider(!$invalidPhone->successful && count($smsHttp->requests) === 1, 'A non-E.164 phone should fail before any provider request.');
} finally {
    // No external provider is contacted by this mocked integration test.
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }

    exit(1);
}

fwrite(STDOUT, "PASS: FCM HTTP v1 and Africa's Talking request contracts pass mocked integration checks.\n");
