<?php

declare(strict_types=1);

final class PushSubscriptionService
{
    private const MAX_DESTINATIONS = 10;

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function enroll(int $userId, string $destination): void
    {
        $destination = $this->validatedDestination($destination);
        $settings = $this->users->settingsForUser($userId);
        $destinations = $this->destinations($settings);
        if (!in_array($destination, $destinations, true)) {
            if (count($destinations) >= self::MAX_DESTINATIONS) {
                throw new ValidationException(['push' => 'Remove an existing Push device before adding another.']);
            }
            $destinations[] = $destination;
        }
        $settings['fcm_tokens'] = $destinations;
        $this->users->updateSettings($userId, $settings);
    }

    public function revoke(int $userId, string $destination): void
    {
        $destination = $this->validatedDestination($destination);
        $settings = $this->users->settingsForUser($userId);
        $settings['fcm_tokens'] = array_values(array_filter(
            $this->destinations($settings),
            static fn (string $candidate): bool => !hash_equals($destination, $candidate)
        ));
        $this->users->updateSettings($userId, $settings);
    }

    private function validatedDestination(string $destination): string
    {
        $destination = trim($destination);
        $length = strlen($destination);
        if ($length < 10 || $length > 4096 || preg_match('/[\x00-\x1F\x7F]/', $destination) === 1) {
            throw new ValidationException(['push' => 'The Push destination is invalid.']);
        }

        return $destination;
    }

    /** @param array<string, mixed> $settings @return list<string> */
    private function destinations(array $settings): array
    {
        if (!isset($settings['fcm_tokens']) || !is_array($settings['fcm_tokens'])) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $value): string => is_string($value) ? trim($value) : '', $settings['fcm_tokens']),
            static fn (string $value): bool => $value !== ''
        )));
    }
}
