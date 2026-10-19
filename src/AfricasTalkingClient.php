<?php

declare(strict_types=1);

final class AfricasTalkingClient implements SmsGateway
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $username,
        private readonly string $apiKey,
        private readonly ?string $senderId = null,
        private readonly bool $sandbox = false
    ) {
    }

    public function send(string $phone, string $message): DeliveryResult
    {
        if (trim($this->username) === '' || trim($this->apiKey) === '') {
            return DeliveryResult::failure("Africa's Talking credentials are not configured.");
        }
        if (preg_match('/^\+[1-9]\d{6,14}$/', $phone) !== 1) {
            return DeliveryResult::failure('SMS recipient must use international E.164 format.');
        }

        $parameters = [
            'username' => $this->username,
            'to' => $phone,
            'message' => $message,
        ];
        if ($this->senderId !== null && trim($this->senderId) !== '') {
            $parameters['from'] = trim($this->senderId);
        }

        try {
            $response = $this->http->post(
                $this->sandbox
                    ? 'https://api.sandbox.africastalking.com/version1/messaging'
                    : 'https://api.africastalking.com/version1/messaging',
                [
                    'apiKey' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                http_build_query($parameters, '', '&', PHP_QUERY_RFC3986)
            );
        } catch (Throwable $exception) {
            return DeliveryResult::failure("Africa's Talking request failed: " . $exception->getMessage());
        }

        $decoded = json_decode($response->body, true);
        $recipient = is_array($decoded)
            ? ($decoded['SMSMessageData']['Recipients'][0] ?? null)
            : null;
        $statusCode = is_array($recipient) ? (int) ($recipient['statusCode'] ?? 0) : 0;
        $status = is_array($recipient) ? (string) ($recipient['status'] ?? '') : '';
        $messageId = is_array($recipient) ? (string) ($recipient['messageId'] ?? '') : '';

        if ($response->statusCode >= 200 && $response->statusCode < 300 && in_array($statusCode, [100, 101, 102], true)) {
            return DeliveryResult::success(
                "Africa's Talking accepted the SMS"
                . ($messageId !== '' ? ' (' . $messageId . ')' : '')
                . ($status !== '' ? ': ' . $status : '.')
            );
        }

        $detail = $status !== '' ? $status . ' (code ' . $statusCode . ')' : 'HTTP ' . $response->statusCode;

        return DeliveryResult::failure("Africa's Talking rejected the SMS: " . $detail);
    }
}
