<?php

declare(strict_types=1);

final class LoginRateLimiter
{
    public function __construct(
        private readonly string $storagePath,
        private readonly int $maximumAttempts,
        private readonly int $windowSeconds
    ) {
        if ($maximumAttempts < 1 || $windowSeconds < 1) {
            throw new InvalidArgumentException('Rate-limit values must be positive.');
        }
    }

    public function key(string $email, string $ipAddress): string
    {
        return hash('sha256', 'account-ip|' . AuthService::normalizeEmail($email) . '|' . $ipAddress);
    }

    public function ipKey(string $ipAddress): string
    {
        return hash('sha256', 'ip|' . $ipAddress);
    }

    public function tooManyAttempts(string $key, ?int $now = null): bool
    {
        $now ??= time();
        $record = $this->readRecord($key, $now);

        return $record !== null && count($record) >= $this->maximumAttempts;
    }

    public function hit(string $key, ?int $now = null): void
    {
        $now ??= time();

        $this->mutate(function (array &$records) use ($key, $now): void {
            $this->prune($records, $now);
            $attempts = $this->activeAttempts($records[$key] ?? [], $now);
            $attempts[] = $now;
            $records[$key] = $attempts;
        });
    }

    public function clear(string $key): void
    {
        $this->mutate(static function (array &$records) use ($key): void {
            unset($records[$key]);
        });
    }

    public function retryAfter(string $key, ?int $now = null): int
    {
        $now ??= time();
        $attempts = $this->readRecord($key, $now);

        if ($attempts === null || count($attempts) < $this->maximumAttempts) {
            return 0;
        }

        return max(1, ($attempts[0] + $this->windowSeconds) - $now);
    }

    /**
     * @return list<int>|null
     */
    private function readRecord(string $key, int $now): ?array
    {
        $record = null;

        $this->mutate(function (array &$records) use ($key, $now, &$record): void {
            $this->prune($records, $now);
            $attempts = $this->activeAttempts($records[$key] ?? [], $now);

            if ($attempts === []) {
                unset($records[$key]);
                return;
            }

            $records[$key] = $attempts;
            $record = $attempts;
        });

        return $record;
    }

    /**
     * @param mixed $attempts
     * @return list<int>
     */
    private function activeAttempts(mixed $attempts, int $now): array
    {
        if (!is_array($attempts)) {
            return [];
        }

        $threshold = $now - $this->windowSeconds;

        return array_values(array_filter(
            $attempts,
            static fn (mixed $attempt): bool => is_int($attempt) && $attempt > $threshold
        ));
    }

    /** @param array<string, mixed> $records */
    private function prune(array &$records, int $now): void
    {
        foreach ($records as $recordKey => $attempts) {
            $active = $this->activeAttempts($attempts, $now);
            if ($active === []) {
                unset($records[$recordKey]);
            } else {
                $records[$recordKey] = $active;
            }
        }
    }

    /**
     * @param callable(array<string, list<int>>&): void $callback
     */
    private function mutate(callable $callback): void
    {
        $directory = dirname($this->storagePath);

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the login rate-limit directory.');
        }

        $newStore = !is_file($this->storagePath);
        $handle = fopen($this->storagePath, 'c+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the login rate-limit store.');
        }

        if ($newStore) {
            @chmod($this->storagePath, 0600);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the login rate-limit store.');
            }

            $contents = stream_get_contents($handle);
            $records = $contents !== false && trim($contents) !== ''
                ? json_decode($contents, true)
                : [];

            if (!is_array($records)) {
                $records = [];
            }

            $callback($records);
            rewind($handle);
            ftruncate($handle, 0);

            $encoded = json_encode($records, JSON_THROW_ON_ERROR);

            if (fwrite($handle, $encoded) === false) {
                throw new RuntimeException('Unable to write the login rate-limit store.');
            }

            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
