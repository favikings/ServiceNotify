<?php

declare(strict_types=1);

final class NotificationLogger
{
    public function __construct(private readonly string $path)
    {
    }

    /** @param array<string, scalar|null> $context */
    public function log(
        string $level,
        string $event,
        string $message,
        array $context = [],
        ?DateTimeInterface $occurredAt = null
    ): bool {
        $level = in_array($level, ['info', 'warning', 'error'], true) ? $level : 'info';
        $entry = [
            'timestamp' => ($occurredAt ?? new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'level' => $level,
            'event' => $this->clean($event, 80),
            'message' => $this->clean($message, 500),
            'context' => $this->safeContext($context),
        ];
        $directory = dirname($this->path);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            error_log('ServiceNotify could not create its notification log directory.');
            return false;
        }

        try {
            $line = json_encode($entry, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        } catch (JsonException) {
            error_log('ServiceNotify could not encode a notification log entry.');
            return false;
        }

        if (@file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log('ServiceNotify could not append to its notification log.');
            return false;
        }

        return true;
    }

    /**
     * Returns generic cron entries plus entries owned by the requested user.
     *
     * @return list<array{timestamp:string,level:string,event:string,message:string,context:array<string, mixed>}>
     */
    public function entriesForUser(int $userId, int $limit = 80): array
    {
        if (!is_file($this->path) || $limit < 1) {
            return [];
        }

        $lines = @file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $entries = [];
        foreach (array_reverse($lines) as $line) {
            try {
                $entry = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (!is_array($entry) || !isset($entry['timestamp'], $entry['level'], $entry['event'], $entry['message'])) {
                continue;
            }

            $context = is_array($entry['context'] ?? null) ? $entry['context'] : [];
            $entryUserId = isset($context['user_id']) && is_numeric($context['user_id'])
                ? (int) $context['user_id']
                : null;
            if ($entryUserId !== null && $entryUserId !== $userId) {
                continue;
            }
            if ($entryUserId === null && !str_starts_with((string) $entry['event'], 'cron_')) {
                continue;
            }

            $entries[] = [
                'timestamp' => (string) $entry['timestamp'],
                'level' => (string) $entry['level'],
                'event' => (string) $entry['event'],
                'message' => (string) $entry['message'],
                'context' => $context,
            ];
            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    /** @param array<string, scalar|null> $context @return array<string, scalar|null> */
    private function safeContext(array $context): array
    {
        $safe = [];
        foreach ($context as $key => $value) {
            if (preg_match('/token|phone|secret|password|credential|api.?key/i', (string) $key) === 1) {
                $safe[(string) $key] = '[redacted]';
                continue;
            }

            $safe[(string) $key] = is_string($value) ? $this->clean($value, 300) : $value;
        }

        return $safe;
    }

    private function clean(string $value, int $limit): string
    {
        $value = trim((string) preg_replace('/[\r\n\t]+/', ' ', $value));

        return function_exists('mb_substr') ? mb_substr($value, 0, $limit) : substr($value, 0, $limit);
    }
}
