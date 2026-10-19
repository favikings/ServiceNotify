<?php

declare(strict_types=1);

final class HttpResponse
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers = []
    ) {
    }
}

interface HttpClient
{
    /**
     * @param array<string, string> $headers
     */
    public function post(string $url, array $headers, string $body): HttpResponse;
}

final class CurlHttpClient implements HttpClient
{
    public function __construct(private readonly int $timeoutSeconds = 15)
    {
    }

    public function post(string $url, array $headers, string $body): HttpResponse
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP cURL extension is required for notifications.');
        }

        $handle = curl_init($url);

        if ($handle === false) {
            throw new RuntimeException('Unable to initialize an outbound HTTP request.');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeoutSeconds),
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);

        $responseBody = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($responseBody === false) {
            throw new RuntimeException('Notification provider request failed: ' . ($error !== '' ? $error : 'unknown network error'));
        }

        return new HttpResponse($statusCode, (string) $responseBody);
    }
}
