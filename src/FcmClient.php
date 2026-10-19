<?php

declare(strict_types=1);

final class FcmClient implements PushGateway
{
    private const MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private ?string $accessToken = null;
    private int $accessTokenExpiresAt = 0;
    /** @var array<string, mixed>|null */
    private ?array $credentials = null;

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $credentialsPath,
        private readonly ?string $projectId = null,
        private readonly ?Closure $accessTokenProvider = null
    ) {
    }

    public function send(string $token, string $title, string $body, array $data = []): DeliveryResult
    {
        if (trim($token) === '') {
            return DeliveryResult::failure('FCM registration token is empty.', true);
        }

        try {
            $fcmMessage = [
                'token' => $token,
                'notification' => ['title' => $title, 'body' => $body],
            ];
            if ($data !== []) {
                $fcmMessage['data'] = $data;
            }
            $payload = json_encode(
                ['message' => $fcmMessage],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
            );
            $response = $this->http->post(
                sprintf(
                    'https://fcm.googleapis.com/v1/projects/%s/messages:send',
                    rawurlencode($this->resolvedProjectId())
                ),
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json; charset=UTF-8',
                ],
                $payload
            );
        } catch (Throwable $exception) {
            return DeliveryResult::failure('FCM request failed: ' . $exception->getMessage());
        }

        $decoded = json_decode($response->body, true);
        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            $messageId = is_array($decoded) && is_string($decoded['name'] ?? null)
                ? $decoded['name']
                : 'accepted';

            return DeliveryResult::success('FCM accepted the notification (' . $messageId . ').');
        }

        $status = is_array($decoded) && is_array($decoded['error'] ?? null)
            ? (string) ($decoded['error']['status'] ?? '')
            : '';
        $invalid = $status === 'UNREGISTERED'
            || ($response->statusCode === 404 && str_contains($response->body, 'UNREGISTERED'))
            || ($status === 'INVALID_ARGUMENT' && $response->statusCode === 400);
        $detail = is_array($decoded) && is_array($decoded['error'] ?? null)
            ? (string) ($decoded['error']['message'] ?? $status)
            : 'HTTP ' . $response->statusCode;

        return DeliveryResult::failure('FCM rejected the notification: ' . $detail, $invalid);
    }

    private function getAccessToken(): string
    {
        if ($this->accessTokenProvider !== null) {
            $token = trim((string) ($this->accessTokenProvider)());
            if ($token === '') {
                throw new RuntimeException('The configured FCM access-token provider returned an empty token.');
            }

            return $token;
        }

        if ($this->accessToken !== null && $this->accessTokenExpiresAt > time() + 60) {
            return $this->accessToken;
        }

        $credentials = $this->loadCredentials();
        $issuedAt = time();
        $tokenUri = (string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token');
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        if (is_string($credentials['private_key_id'] ?? null)) {
            $header['kid'] = $credentials['private_key_id'];
        }
        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => self::MESSAGING_SCOPE,
            'aud' => $tokenUri,
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ];
        $unsigned = $this->base64Url(json_encode($header, JSON_THROW_ON_ERROR))
            . '.' . $this->base64Url(json_encode($claims, JSON_THROW_ON_ERROR));
        $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);
        if ($privateKey === false) {
            throw new RuntimeException('The Firebase service-account private key is invalid.');
        }
        $signature = '';
        $signed = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$signed) {
            throw new RuntimeException('Unable to sign the Firebase service-account assertion.');
        }

        $assertion = $unsigned . '.' . $this->base64Url($signature);
        $response = $this->http->post(
            $tokenUri,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ], '', '&', PHP_QUERY_RFC3986)
        );
        $decoded = json_decode($response->body, true);
        if ($response->statusCode < 200 || $response->statusCode >= 300 || !is_array($decoded) || !is_string($decoded['access_token'] ?? null)) {
            throw new RuntimeException('Unable to obtain an FCM access token (HTTP ' . $response->statusCode . ').');
        }

        $this->accessToken = $decoded['access_token'];
        $this->accessTokenExpiresAt = $issuedAt + max(60, (int) ($decoded['expires_in'] ?? 3600));

        return $this->accessToken;
    }

    /** @return array<string, mixed> */
    private function loadCredentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }
        if ($this->credentialsPath === '' || !is_readable($this->credentialsPath)) {
            throw new RuntimeException('Firebase service-account credentials are missing or unreadable.');
        }

        $contents = file_get_contents($this->credentialsPath);
        $decoded = $contents === false ? null : json_decode($contents, true);
        if (!is_array($decoded) || !is_string($decoded['client_email'] ?? null) || !is_string($decoded['private_key'] ?? null)) {
            throw new RuntimeException('Firebase service-account credentials are invalid.');
        }

        return $this->credentials = $decoded;
    }

    private function resolvedProjectId(): string
    {
        $projectId = trim((string) ($this->projectId ?? $this->loadCredentials()['project_id'] ?? ''));
        if ($projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID is missing and no project_id exists in the service account.');
        }

        return $projectId;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
