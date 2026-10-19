<?php

declare(strict_types=1);

require dirname(__DIR__) . '/partials/app-top.php';
?>
<header class="dashboard__header page-header">
    <div>
        <p class="eyebrow"><span></span> Renewal radar</p>
        <h1>Services</h1>
        <p><?= count($services) === 0 ? 'Track every domain, hosting plan, and maintenance agreement from one calm queue.' : sprintf('%d service%s ordered by nearest expiry.', count($services), count($services) === 1 ? '' : 's') ?></p>
    </div>
    <?php if ($clientCount > 0): ?>
        <a class="button button--primary header-action" href="<?= escape(appUrl('service-create.php')) ?>">
            <span aria-hidden="true">＋</span> Add service
        </a>
    <?php endif; ?>
</header>

<?php if ($success !== null): ?>
    <div class="alert alert--notice dashboard__alert" role="status">
        <span aria-hidden="true">✓</span><p><?= escape($success) ?></p>
    </div>
<?php endif; ?>

<?php if ($clientCount === 0): ?>
    <section class="empty-state">
        <div class="empty-state__signal" aria-hidden="true"><span></span></div>
        <p class="eyebrow"><span></span> A client comes first</p>
        <h2>Every service needs an owner.</h2>
        <p>Add a client before recording their domains, hosting plans, or maintenance agreements.</p>
        <a class="button button--primary" href="<?= escape(appUrl('client-create.php')) ?>">Add your first client</a>
    </section>
<?php elseif ($services === []): ?>
    <section class="empty-state">
        <div class="empty-state__signal" aria-hidden="true"><span></span></div>
        <p class="eyebrow"><span></span> Radar is clear</p>
        <h2>Add the first renewal to watch.</h2>
        <p>Record an expiry date now. Reminder scheduling will connect to this service in the next milestone.</p>
        <a class="button button--primary" href="<?= escape(appUrl('service-create.php')) ?>">Add your first service</a>
    </section>
<?php else: ?>
    <section class="service-grid" aria-label="Your services">
        <?php foreach ($services as $service): ?>
            <?php $expiry = serviceExpiryState($service['expiry_date']); ?>
            <article class="service-card service-card--<?= escape($expiry['key']) ?>">
                <span class="service-card__rail" aria-hidden="true"></span>
                <div class="service-card__topline">
                    <span class="service-type"><?= escape(serviceTypeLabel($service['type'])) ?></span>
                    <span class="status-badge status-badge--<?= escape($expiry['key']) ?>"><i aria-hidden="true"></i><?= escape($expiry['label']) ?></span>
                </div>
                <div class="service-card__identity">
                    <p><?= escape($service['client_name']) ?></p>
                    <h2><?= escape($service['provider'] ?? serviceTypeLabel($service['type']) . ' service') ?></h2>
                </div>
                <div class="service-card__deadline">
                    <strong><?= escape($expiry['detail']) ?></strong>
                    <time datetime="<?= escape($service['expiry_date']) ?>"><?= escape(date('M j, Y', strtotime($service['expiry_date']))) ?></time>
                </div>
                <dl class="service-card__meta">
                    <div><dt>Billing</dt><dd><?= escape(billingCycleLabel($service['billing_cycle'])) ?></dd></div>
                    <div><dt>Cost</dt><dd class="money-value"><?= escape(formatMoney($service['cost'], $service['currency'])) ?></dd></div>
                    <div><dt>Reminders</dt><dd><?= $service['reminder_count'] === 0 ? 'Not configured' : $service['reminder_count'] . ' configured' ?></dd></div>
                    <div><dt>Account</dt><dd><?= escape($service['payment_account'] ?? 'Not set') ?></dd></div>
                </dl>
                <div class="service-card__actions">
                    <a class="action-link" href="<?= escape(appUrl('service-edit.php?id=' . $service['id'])) ?>">Edit</a>
                    <a class="action-link action-link--danger" href="<?= escape(appUrl('service-delete.php?id=' . $service['id'])) ?>">Delete</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php require dirname(__DIR__) . '/partials/app-bottom.php'; ?>
