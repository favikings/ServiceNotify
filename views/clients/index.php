<?php

declare(strict_types=1);

require dirname(__DIR__) . '/partials/app-top.php';
?>
<header class="dashboard__header page-header">
    <div>
        <p class="eyebrow"><span></span> Relationship ledger</p>
        <h1>Clients</h1>
        <p><?= count($clients) === 0 ? 'Build the client list that every service and reminder will belong to.' : sprintf('%d client%s, kept private to your account.', count($clients), count($clients) === 1 ? '' : 's') ?></p>
    </div>
    <a class="button button--primary header-action" href="<?= escape(appUrl('client-create.php')) ?>">
        <span aria-hidden="true">＋</span> Add client
    </a>
</header>

<?php if ($success !== null): ?>
    <div class="alert alert--notice dashboard__alert" role="status">
        <span aria-hidden="true">✓</span><p><?= escape($success) ?></p>
    </div>
<?php endif; ?>

<?php if ($clients === []): ?>
    <section class="empty-state">
        <div class="empty-state__signal" aria-hidden="true"><span></span></div>
        <p class="eyebrow"><span></span> Nothing to monitor yet</p>
        <h2>Your first client starts the system.</h2>
        <p>Add their contact details now. Domains, hosting, maintenance plans, and reminders will connect here in the next steps.</p>
        <a class="button button--primary" href="<?= escape(appUrl('client-create.php')) ?>">Add your first client</a>
    </section>
<?php else: ?>
    <section class="client-list" aria-label="Your clients">
        <div class="client-list__heading" aria-hidden="true">
            <span>Client</span><span>Contact</span><span>Services</span><span>Actions</span>
        </div>
        <?php foreach ($clients as $client): ?>
            <article class="client-row">
                <div class="client-row__identity">
                    <span class="client-avatar" aria-hidden="true"><?= escape(initial($client['name'])) ?></span>
                    <span>
                        <strong><?= escape($client['name']) ?></strong>
                        <small><?= escape($client['organization'] ?? 'Independent client') ?><span class="client-inline-count"> · <?= $client['service_count'] ?> service<?= $client['service_count'] === 1 ? '' : 's' ?></span></small>
                    </span>
                </div>
                <div class="client-row__contact">
                    <?php if ($client['email'] !== null): ?>
                        <a href="mailto:<?= escape($client['email']) ?>"><?= escape($client['email']) ?></a>
                    <?php else: ?>
                        <span>No email added</span>
                    <?php endif; ?>
                    <?php if ($client['phone'] !== null): ?>
                        <a href="tel:<?= escape(telephoneHref($client['phone'])) ?>"><?= escape($client['phone']) ?></a>
                    <?php else: ?>
                        <small>No phone added</small>
                    <?php endif; ?>
                </div>
                <span class="client-row__date"><?= $client['service_count'] ?> service<?= $client['service_count'] === 1 ? '' : 's' ?></span>
                <div class="client-row__actions">
                    <a class="action-link" href="<?= escape(appUrl('client-edit.php?id=' . $client['id'])) ?>">Edit</a>
                    <a class="action-link action-link--danger" href="<?= escape(appUrl('client-delete.php?id=' . $client['id'])) ?>">Delete</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php require dirname(__DIR__) . '/partials/app-bottom.php'; ?>
