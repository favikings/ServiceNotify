<?php

declare(strict_types=1);

function escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function appUrl(string $path = ''): string
{
    $baseUrl = rtrim(Env::require('APP_URL'), '/');

    return $path === '' ? $baseUrl : $baseUrl . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . appUrl($path), true, 302);
    exit;
}

function isPostRequest(): bool
{
    return RequestInput::serverString('REQUEST_METHOD', 'GET') === 'POST';
}

function clientIpAddress(): string
{
    return Security::clientIpAddress();
}

/**
 * @return array{id: int, name: string, email: string}
 */
function requireAuthenticatedUser(): array
{
    $user = Session::user();

    if ($user === null) {
        redirect('login.php');
    }

    return $user;
}

function requestedPositiveId(): ?int
{
    return RequestInput::getPositiveInt('id');
}

function initial(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '?';
    }

    $character = function_exists('mb_substr') ? mb_substr($value, 0, 1) : substr($value, 0, 1);

    return function_exists('mb_strtoupper') ? mb_strtoupper($character) : strtoupper($character);
}

function telephoneHref(string $phone): string
{
    return preg_replace('/[^0-9+]/', '', $phone) ?? '';
}

/**
 * @return array{key: string, label: string, detail: string, days: int}
 */
function serviceExpiryState(string $expiryDate, ?DateTimeImmutable $today = null): array
{
    $expiry = DateTimeImmutable::createFromFormat('!Y-m-d', $expiryDate);
    $today ??= new DateTimeImmutable('today');

    if ($expiry === false) {
        return ['key' => 'upcoming', 'label' => 'Date unavailable', 'detail' => 'Check expiry date', 'days' => 0];
    }

    $days = (int) $today->diff($expiry)->format('%r%a');

    if ($days < 0) {
        $overdueDays = abs($days);

        return [
            'key' => 'overdue',
            'label' => 'Overdue',
            'detail' => sprintf('Overdue by %d day%s', $overdueDays, $overdueDays === 1 ? '' : 's'),
            'days' => $days,
        ];
    }

    if ($days === 0) {
        return ['key' => 'today', 'label' => 'Today', 'detail' => 'Expires today', 'days' => 0];
    }

    if ($days <= 7) {
        return ['key' => 'urgent', 'label' => 'Urgent', 'detail' => sprintf('%d day%s left', $days, $days === 1 ? '' : 's'), 'days' => $days];
    }

    if ($days <= 30) {
        return ['key' => 'due-soon', 'label' => 'Due soon', 'detail' => sprintf('%d days left', $days), 'days' => $days];
    }

    return ['key' => 'healthy', 'label' => 'Healthy', 'detail' => sprintf('%d days left', $days), 'days' => $days];
}

function serviceTypeLabel(string $type): string
{
    return ucfirst($type);
}

function billingCycleLabel(string $billingCycle): string
{
    return ucfirst($billingCycle);
}

function formatMoney(?string $amount, ?string $currency): string
{
    if ($amount === null) {
        return 'Not set';
    }

    $formatted = number_format((float) $amount, 2, '.', ',');

    if ($currency === null) {
        return $formatted . ' · Currency needed';
    }

    $currency = strtoupper($currency);
    $symbols = [
        'NGN' => '₦',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
    ];

    if (isset($symbols[$currency])) {
        return $symbols[$currency] . $formatted . ' ' . $currency;
    }

    return $currency . ' ' . $formatted;
}
