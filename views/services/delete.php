<?php

declare(strict_types=1);

require dirname(__DIR__) . '/partials/app-top.php';
?>
<div class="confirm-wrap">
    <section class="confirm-card" aria-labelledby="delete-service-title">
        <span class="danger-mark" aria-hidden="true">!</span>
        <p class="eyebrow"><span></span> Destructive action</p>
        <h1 id="delete-service-title">Delete this <?= escape(serviceTypeLabel($service['type'])) ?> service?</h1>
        <p>This permanently removes the service for <?= escape($service['client_name']) ?>. Future reminders and notification history connected to it will also be removed through database cascade rules.</p>

        <dl class="confirm-summary">
            <div><dt>Client</dt><dd><?= escape($service['client_name']) ?></dd></div>
            <div><dt>Provider</dt><dd><?= escape($service['provider'] ?? 'Not provided') ?></dd></div>
            <div><dt>Expiry</dt><dd><?= escape(date('M j, Y', strtotime($service['expiry_date']))) ?></dd></div>
        </dl>

        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert"><span aria-hidden="true">!</span><p><?= escape($error) ?></p></div>
        <?php endif; ?>

        <div class="confirm-actions">
            <a class="button button--secondary" href="<?= escape(appUrl('services.php')) ?>">Keep service</a>
            <form action="<?= escape(appUrl('service-delete.php?id=' . $service['id'])) ?>" method="post">
                <input type="hidden" name="_token" value="<?= escape(Csrf::token()) ?>">
                <button class="button button--danger" type="submit">Delete permanently</button>
            </form>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/partials/app-bottom.php'; ?>
