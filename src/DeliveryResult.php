<?php

declare(strict_types=1);

final class DeliveryResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $detail,
        public readonly bool $invalidRecipient = false
    ) {
    }

    public static function success(string $detail): self
    {
        return new self(true, $detail);
    }

    public static function failure(string $detail, bool $invalidRecipient = false): self
    {
        return new self(false, $detail, $invalidRecipient);
    }
}
