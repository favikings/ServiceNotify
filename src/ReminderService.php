<?php

declare(strict_types=1);

final class ReminderService
{
    public const PRESETS = [30, 7, 1];
    private const CHANNELS = ['push', 'sms'];

    public function __construct(private readonly ReminderRepository $reminders)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return list<array{frequency: int, channels: list<string>}>
     * @throws ValidationException
     */
    public function validate(array $input): array
    {
        $presetInput = isset($input['presets']) && is_array($input['presets']) ? $input['presets'] : [];
        $rows = [];
        $errors = [];

        foreach (self::PRESETS as $frequency) {
            $candidate = $presetInput[(string) $frequency] ?? $presetInput[$frequency] ?? [];
            $candidate = is_array($candidate) ? $candidate : [];
            $enabled = ($candidate['enabled'] ?? null) === '1' || ($candidate['enabled'] ?? null) === 1;

            if (!$enabled) {
                continue;
            }

            $submittedChannels = isset($candidate['channels']) && is_array($candidate['channels'])
                ? array_values(array_unique(array_map('strval', $candidate['channels'])))
                : [];
            $invalidChannels = array_diff($submittedChannels, self::CHANNELS);
            $channels = array_values(array_intersect(self::CHANNELS, $submittedChannels));

            if ($channels === [] || $invalidChannels !== []) {
                $errors['reminder_' . $frequency] = sprintf(
                    'Choose Push, SMS, or both for the %d-day reminder.',
                    $frequency
                );
                continue;
            }

            $rows[] = [
                'frequency' => $frequency,
                'channels' => $channels,
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $rows;
    }

    /**
     * @param list<array{frequency: int, channels: list<string>}> $rows
     * @return array{presets: array<int, array{enabled: bool, channels: array{push: bool, sms: bool}}>}
     */
    public static function formValuesFromRows(array $rows): array
    {
        $values = self::emptyFormValues();

        foreach ($rows as $row) {
            $frequency = (int) $row['frequency'];

            if (!isset($values['presets'][$frequency])) {
                continue;
            }

            $values['presets'][$frequency]['enabled'] = true;
            $values['presets'][$frequency]['channels']['push'] = in_array('push', $row['channels'], true);
            $values['presets'][$frequency]['channels']['sms'] = in_array('sms', $row['channels'], true);
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{presets: array<int, array{enabled: bool, channels: array{push: bool, sms: bool}}>}
     */
    public static function formValuesFromInput(array $input): array
    {
        $values = self::emptyFormValues();
        $presetInput = isset($input['presets']) && is_array($input['presets']) ? $input['presets'] : [];

        foreach (self::PRESETS as $frequency) {
            $candidate = $presetInput[(string) $frequency] ?? $presetInput[$frequency] ?? [];
            $candidate = is_array($candidate) ? $candidate : [];
            $channels = isset($candidate['channels']) && is_array($candidate['channels']) ? $candidate['channels'] : [];
            $values['presets'][$frequency] = [
                'enabled' => ($candidate['enabled'] ?? null) === '1' || ($candidate['enabled'] ?? null) === 1,
                'channels' => [
                    'push' => in_array('push', $channels, true),
                    'sms' => in_array('sms', $channels, true),
                ],
            ];
        }

        return $values;
    }

    /**
     * @param list<array{frequency: int, channels: list<string>}> $rows
     */
    public function replaceValidated(int $serviceId, int $userId, array $rows): void
    {
        $this->reminders->replaceForServiceForUser($serviceId, $userId, $rows);
    }

    /**
     * @return array{presets: array<int, array{enabled: bool, channels: array{push: bool, sms: bool}}>}
     */
    private static function emptyFormValues(): array
    {
        $presets = [];

        foreach (self::PRESETS as $frequency) {
            $presets[$frequency] = [
                'enabled' => false,
                'channels' => ['push' => false, 'sms' => false],
            ];
        }

        return ['presets' => $presets];
    }
}
