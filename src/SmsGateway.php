<?php

declare(strict_types=1);

interface SmsGateway
{
    public function send(string $phone, string $message): DeliveryResult;
}
