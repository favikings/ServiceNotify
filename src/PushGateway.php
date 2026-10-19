<?php

declare(strict_types=1);

interface PushGateway
{
    /** @param array<string, string> $data */
    public function send(string $token, string $title, string $body, array $data = []): DeliveryResult;
}
