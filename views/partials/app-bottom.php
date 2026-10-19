<?php

declare(strict_types=1);
?>
    </main>

    <aside class="pwa-install" data-pwa-install hidden aria-labelledby="pwa-install-title">
        <span class="pwa-install__mark" aria-hidden="true"><i></i></span>
        <span><strong id="pwa-install-title">Install your renewal desk</strong><small>Launch ServiceNotify like a native app.</small></span>
        <button class="button button--primary" type="button" data-pwa-install-action>Install</button>
        <button class="icon-button" type="button" data-pwa-install-dismiss aria-label="Dismiss install suggestion">×</button>
    </aside>

    <?php if ($firebasePushConfigured): ?>
        <dialog class="push-dialog" data-push-dialog aria-labelledby="push-dialog-title">
            <form method="dialog"><button class="icon-button push-dialog__close" value="cancel" aria-label="Close Push settings">×</button></form>
            <span class="push-dialog__signal" aria-hidden="true"><i></i></span>
            <p class="eyebrow"><span></span> This browser</p>
            <h2 id="push-dialog-title">Push notifications</h2>
            <p>Allow ServiceNotify to send renewal alerts to this browser, even when the app is not open.</p>
            <div class="push-dialog__state" role="status" data-push-state>Checking browser support…</div>
            <div class="push-dialog__actions">
                <button class="button button--primary" type="button" data-push-enable>Enable Push</button>
                <button class="button button--secondary" type="button" data-push-disable hidden>Disable on this browser</button>
            </div>
            <small>Permission is requested only after you choose Enable. You can revoke this browser at any time.</small>
        </dialog>
    <?php endif; ?>

    <nav class="bottom-nav" aria-label="Mobile primary navigation">
        <a class="bottom-nav__item <?= $activeNav === 'dashboard' ? 'bottom-nav__item--active' : '' ?>" href="<?= escape(appUrl('dashboard.php')) ?>" <?= $activeNav === 'dashboard' ? 'aria-current="page"' : '' ?>><span>⌂</span>Dashboard</a>
        <a class="bottom-nav__item <?= $activeNav === 'clients' ? 'bottom-nav__item--active' : '' ?>" href="<?= escape(appUrl('clients.php')) ?>" <?= $activeNav === 'clients' ? 'aria-current="page"' : '' ?>><span>◎</span>Clients</a>
        <a class="bottom-nav__item <?= $activeNav === 'services' ? 'bottom-nav__item--active' : '' ?>" href="<?= escape(appUrl('services.php')) ?>" <?= $activeNav === 'services' ? 'aria-current="page"' : '' ?>><span>◇</span>Services</a>
        <a class="bottom-nav__item <?= $activeNav === 'reports' ? 'bottom-nav__item--active' : '' ?>" href="<?= escape(appUrl('reports.php')) ?>" <?= $activeNav === 'reports' ? 'aria-current="page"' : '' ?>><span>↗</span>Reports</a>
        <a class="bottom-nav__item <?= $activeNav === 'logs' ? 'bottom-nav__item--active' : '' ?>" href="<?= escape(appUrl('logs.php')) ?>" <?= $activeNav === 'logs' ? 'aria-current="page"' : '' ?>><span>≋</span>Logs</a>
    </nav>
</div>
</body>
</html>
