<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D1410">
    <meta name="application-name" content="ServiceNotify">
    <meta name="service-notify-pwa" content="clear-private">
    <title><?= escape($pageTitle) ?> · ServiceNotify</title>
    <link rel="manifest" href="<?= escape(appUrl('manifest.json')) ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= escape(appUrl('assets/icons/icon-192.png')) ?>">
    <link rel="apple-touch-icon" href="<?= escape(appUrl('assets/icons/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= escape(appUrl('assets/css/app.css')) ?>">
    <script src="<?= escape(appUrl('assets/js/app.js')) ?>" defer></script>
</head>
<body class="auth-body">
<div class="connection-banner" data-connection-banner hidden role="status">Offline · sign-in requires a connection</div>
<main class="auth-shell">
    <aside class="auth-story" aria-label="ServiceNotify overview">
        <a class="brand brand--story" href="<?= escape(appUrl()) ?>" aria-label="ServiceNotify home">
            <span class="brand__mark" aria-hidden="true">
                <span></span>
            </span>
            <span>ServiceNotify</span>
        </a>

        <div class="auth-story__content">
            <p class="eyebrow"><span></span> Your renewal command centre</p>
            <h2>Keep every client service <em>in sight.</em></h2>
            <p>One calm place for expiries, reminders, and the details that keep client work running.</p>

            <div class="signal-card" aria-hidden="true">
                <div class="signal-card__top">
                    <span>Renewal pulse</span>
                    <span class="live-pill"><i></i> Ready</span>
                </div>
                <div class="signal-card__row">
                    <span class="signal-card__rail signal-card__rail--urgent"></span>
                    <span class="signal-card__icon">D</span>
                    <span><strong>Domain renewal</strong><small>7 days before expiry</small></span>
                    <b>7d</b>
                </div>
                <div class="signal-card__row">
                    <span class="signal-card__rail signal-card__rail--soon"></span>
                    <span class="signal-card__icon">H</span>
                    <span><strong>Hosting plan</strong><small>30 days before expiry</small></span>
                    <b>30d</b>
                </div>
                <div class="signal-card__row signal-card__row--muted">
                    <span class="signal-card__rail"></span>
                    <span class="signal-card__icon">M</span>
                    <span><strong>Maintenance</strong><small>Scheduled and tracked</small></span>
                    <b>On</b>
                </div>
            </div>
        </div>

        <p class="auth-story__foot">Built for developers who would rather prevent surprises.</p>
    </aside>

    <section class="auth-panel">
        <div class="auth-panel__inner">
            <a class="brand brand--mobile" href="<?= escape(appUrl()) ?>" aria-label="ServiceNotify home">
                <span class="brand__mark" aria-hidden="true"><span></span></span>
                <span>ServiceNotify</span>
            </a>
<?php
