<?php

declare(strict_types=1);

require dirname(__DIR__) . '/partials/app-top.php';
?>
<div class="confirm-wrap">
    <section class="confirm-card" aria-labelledby="delete-client-title">
        <span class="danger-mark" aria-hidden="true">!</span>
        <p class="eyebrow"><span></span> Destructive action</p>
        <h1 id="delete-client-title">Delete <?= escape($client['name']) ?>?</h1>
        <p>This permanently removes the client. When services are connected, their reminders and notification history will also be removed through database cascade rules.</p>

        <dl class="confirm-summary">
            <div><dt>Client</dt><dd><?= escape($client['name']) ?></dd></div>
            <div><dt>Organization</dt><dd><?= escape($client['organization'] ?? 'Not provided') ?></dd></div>
            <div><dt>Contact</dt><dd><?= escape($client['email'] ?? $client['phone'] ?? 'Not provided') ?></dd></div>
        </dl>

        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert"><span aria-hidden="true">!</span><p><?= escape($error) ?></p></div>
        <?php endif; ?>

        <div class="confirm-actions">
            <a class="button button--secondary" href="<?= escape(appUrl('clients.php')) ?>">Keep client</a>
            <form action="<?= escape(appUrl('client-delete.php?id=' . $client['id'])) ?>" method="post">
                <input type="hidden" name="_token" value="<?= escape(Csrf::token()) ?>">
                <button class="button button--danger" type="submit">Delete permanently</button>
            </form>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/partials/app-bottom.php'; ?>

