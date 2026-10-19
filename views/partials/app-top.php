<?php

declare(strict_types=1);

$activeNav ??= '';
$pwaCacheKey = substr(hash('sha256', session_id() . '|' . $user['id']), 0, 24);
$firebaseWebConfig = [
    'api_key' => Env::get('FIREBASE_WEB_API_KEY', '') ?? '',
    'project_id' => Env::get('FIREBASE_PROJECT_ID', '') ?? '',
    'sender_id' => Env::get('FIREBASE_MESSAGING_SENDER_ID', '') ?? '',
    'app_id' => Env::get('FIREBASE_WEB_APP_ID', '') ?? '',
    'vapid_key' => Env::get('FIREBASE_VAPID_PUBLIC_KEY', '') ?? '',
];
$firebasePushConfigured = !in_array('', $firebaseWebConfig, true);
header('X-ServiceNotify-PWA-Key: ' . $pwaCacheKey);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D1410">
    <meta name="application-name" content="ServiceNotify">
    <meta name="service-notify-pwa" content="authenticated">
    <meta name="csrf-token" content="<?= escape(Csrf::token()) ?>">
    <?php if ($firebasePushConfigured): ?>
        <meta name="firebase-api-key" content="<?= escape($firebaseWebConfig['api_key']) ?>">
        <meta name="firebase-project-id" content="<?= escape($firebaseWebConfig['project_id']) ?>">
        <meta name="firebase-sender-id" content="<?= escape($firebaseWebConfig['sender_id']) ?>">
        <meta name="firebase-app-id" content="<?= escape($firebaseWebConfig['app_id']) ?>">
        <meta name="firebase-vapid-key" content="<?= escape($firebaseWebConfig['vapid_key']) ?>">
    <?php endif; ?>
    <title><?= escape($pageTitle) ?> · ServiceNotify</title>
    <link rel="manifest" href="<?= escape(appUrl('manifest.json')) ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= escape(appUrl('assets/icons/icon-192.png')) ?>">
    <link rel="apple-touch-icon" href="<?= escape(appUrl('assets/icons/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= escape(appUrl('assets/css/app.css')) ?>">
    <script src="<?= escape(appUrl('assets/js/app.js')) ?>" defer></script>
    <?php if ($firebasePushConfigured): ?><script src="<?= escape(appUrl('assets/js/push.js')) ?>" type="module"></script><?php endif; ?>
</head>
<body class="app-body">
<div class="connection-banner" data-connection-banner hidden role="status">Offline mode · saved views only</div>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= escape(appUrl('dashboard.php')) ?>">
            <span class="brand__mark" aria-hidden="true"><span></span></span>
            <span>ServiceNotify</span>
        </a>
        <nav class="sidebar__nav" aria-label="Primary navigation">
            <a class="nav-item <?= $activeNav === 'dashboard' ? 'nav-item--active' : '' ?>" href="<?= escape(appUrl('dashboard.php')) ?>" <?= $activeNav === 'dashboard' ? 'aria-current="page"' : '' ?>><span class="nav-item__icon">⌂</span>Dashboard</a>
            <a class="nav-item <?= $activeNav === 'clients' ? 'nav-item--active' : '' ?>" href="<?= escape(appUrl('clients.php')) ?>" <?= $activeNav === 'clients' ? 'aria-current="page"' : '' ?>><span class="nav-item__icon">◎</span>Clients</a>
            <a class="nav-item <?= $activeNav === 'services' ? 'nav-item--active' : '' ?>" href="<?= escape(appUrl('services.php')) ?>" <?= $activeNav === 'services' ? 'aria-current="page"' : '' ?>><span class="nav-item__icon">◇</span>Services</a>
            <a class="nav-item <?= $activeNav === 'reports' ? 'nav-item--active' : '' ?>" href="<?= escape(appUrl('reports.php')) ?>" <?= $activeNav === 'reports' ? 'aria-current="page"' : '' ?>><span class="nav-item__icon">↗</span>Reports</a>
            <a class="nav-item <?= $activeNav === 'logs' ? 'nav-item--active' : '' ?>" href="<?= escape(appUrl('logs.php')) ?>" <?= $activeNav === 'logs' ? 'aria-current="page"' : '' ?>><span class="nav-item__icon">≋</span>Logs</a>
        </nav>
        <?php if ($firebasePushConfigured): ?>
            <button class="push-nav" type="button" data-push-open><span aria-hidden="true">◉</span><span>Push device<small data-push-nav-state>Check status</small></span></button>
        <?php endif; ?>
        <div class="sidebar__user">
            <span class="avatar" aria-hidden="true"><?= escape(initial($user['name'])) ?></span>
            <span><strong><?= escape($user['name']) ?></strong><small><?= escape($user['email']) ?></small></span>
            <form action="<?= escape(appUrl('logout.php')) ?>" method="post">
                <input type="hidden" name="_token" value="<?= escape(Csrf::token()) ?>">
                <button class="icon-button" type="submit" aria-label="Sign out">↗</button>
            </form>
        </div>
    </aside>

    <header class="mobile-topbar">
        <a class="brand" href="<?= escape(appUrl('dashboard.php')) ?>">
            <span class="brand__mark" aria-hidden="true"><span></span></span>
            <span>ServiceNotify</span>
        </a>
        <div class="mobile-topbar__actions">
            <?php if ($firebasePushConfigured): ?><button class="icon-button" type="button" data-push-open aria-label="Manage Push notifications">◉</button><?php endif; ?>
            <form action="<?= escape(appUrl('logout.php')) ?>" method="post">
                <input type="hidden" name="_token" value="<?= escape(Csrf::token()) ?>">
                <button class="icon-button" type="submit" aria-label="Sign out">↗</button>
            </form>
        </div>
    </header>

    <main class="dashboard">
