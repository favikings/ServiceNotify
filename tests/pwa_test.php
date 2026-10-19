<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

function expectPwa(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

$manifestRaw = file_get_contents($root . '/public/manifest.json');
$manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
expectPwa(is_array($manifest), 'The web app manifest should contain valid JSON.');
expectPwa(($manifest['name'] ?? null) === 'ServiceNotify' && ($manifest['short_name'] ?? null) === 'ServiceNotify', 'The manifest should identify the application.');
expectPwa(($manifest['start_url'] ?? null) === './dashboard.php' && ($manifest['scope'] ?? null) === './', 'The manifest should launch the clean dashboard URL within its deployment scope.');
expectPwa(($manifest['display'] ?? null) === 'standalone', 'The manifest should request a standalone installed experience.');
expectPwa(($manifest['theme_color'] ?? null) === '#0D1410' && ($manifest['background_color'] ?? null) === '#0D1410', 'Manifest colors should match the approved Evergreen canvas.');

$icons = is_array($manifest['icons'] ?? null) ? $manifest['icons'] : [];
$iconPurposes = array_column($icons, 'purpose');
expectPwa(count($icons) >= 3 && in_array('maskable', $iconPurposes, true), 'The manifest should include standard and maskable icons.');
foreach ([
    'icon-192.png' => [192, 192],
    'icon-512.png' => [512, 512],
    'icon-maskable-512.png' => [512, 512],
    'apple-touch-icon.png' => [180, 180],
] as $filename => $expectedSize) {
    $path = $root . '/public/assets/icons/' . $filename;
    $size = is_file($path) ? getimagesize($path) : false;
    expectPwa(is_array($size) && [$size[0], $size[1]] === $expectedSize, $filename . ' should exist at its declared dimensions.');
    expectPwa(is_array($size) && $size['mime'] === 'image/png', $filename . ' should be a PNG image.');
}

$serviceWorker = file_get_contents($root . '/public/sw.js');
$staticAssetBlock = '';
if (is_string($serviceWorker) && preg_match('/const STATIC_ASSETS = \[(.*?)\];/s', $serviceWorker, $matches) === 1) {
    $staticAssetBlock = $matches[1];
}
expectPwa($staticAssetBlock !== '' && !str_contains($staticAssetBlock, 'dashboard.php') && !str_contains($staticAssetBlock, 'clients.php'), 'Authenticated HTML should not be blindly precached during service-worker installation.');
expectPwa(is_string($serviceWorker) && str_contains($serviceWorker, "endsWith('/dashboard.php')") && str_contains($serviceWorker, "endsWith('/clients.php')"), 'Offline routing should explicitly cover dashboard and client pages.');
expectPwa(is_string($serviceWorker) && str_contains($serviceWorker, 'X-ServiceNotify-PWA-Key') && str_contains($serviceWorker, 'PRIVATE_PREFIX'), 'Authenticated pages should use session-keyed private caches.');
expectPwa(is_string($serviceWorker) && str_contains($serviceWorker, 'CLEAR_PRIVATE_CACHES'), 'The service worker should support clearing private data at the authentication boundary.');
expectPwa(is_string($serviceWorker) && str_contains($serviceWorker, "request.method !== 'GET'"), 'Offline caching should leave mutations network-only.');
expectPwa(is_string($serviceWorker) && str_contains($serviceWorker, "addEventListener('push'") && str_contains($serviceWorker, "addEventListener('notificationclick'"), 'The application worker should receive background Push and open the app from notifications.');
expectPwa(is_file($root . '/public/offline.html'), 'A branded offline fallback should be available.');

$appTop = file_get_contents($root . '/views/partials/app-top.php');
$authTop = file_get_contents($root . '/views/partials/auth-top.php');
$appJs = file_get_contents($root . '/public/assets/js/app.js');
expectPwa(is_string($appTop) && str_contains($appTop, 'X-ServiceNotify-PWA-Key') && str_contains($appTop, "appUrl('manifest.json')"), 'Authenticated pages should expose private-cache identity and manifest metadata.');
expectPwa(is_string($authTop) && str_contains($authTop, 'clear-private') && str_contains($authTop, "appUrl('manifest.json')"), 'Authentication pages should clear private PWA state and remain installable.');
expectPwa(is_string($appJs) && str_contains($appJs, 'navigator.serviceWorker.register') && str_contains($appJs, 'beforeinstallprompt'), 'The shared script should register the worker and support an accessible install action.');
expectPwa(is_file($root . '/public/assets/js/push.js') && is_file($root . '/public/push-subscription.php'), 'Prompt 10 should include browser Push enrollment assets and its authenticated endpoint.');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, sprintf("FAIL: %s\n", $failure));
    }
    exit(1);
}

fwrite(STDOUT, "PASS: Manifest, icons, private offline caching, and install wiring meet Prompt 10 acceptance checks.\n");
