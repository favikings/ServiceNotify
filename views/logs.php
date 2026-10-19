<?php

declare(strict_types=1);

require __DIR__ . '/partials/app-top.php';
?>
<header class="dashboard__header page-header logs-header">
    <div>
        <p class="eyebrow"><span></span> Delivery observatory</p>
        <h1>Activity logs</h1>
        <p>Trace notification outcomes, retries, and the health of the daily delivery run.</p>
    </div>
    <div class="logs-live-state"><i aria-hidden="true"></i><span>Audit trail active</span></div>
</header>

<?php if ($filterErrors !== []): ?>
    <div class="alert alert--error dashboard__alert" role="alert">
        <span aria-hidden="true">!</span><p><?= escape(implode(' ', $filterErrors)) ?> Showing the unfiltered activity stream.</p>
    </div>
<?php endif; ?>

<section class="log-metrics" aria-label="Notification activity summary">
    <article><span>Total attempts</span><strong><?= $summary['total'] ?></strong><small>Recorded deliveries</small></article>
    <article class="log-metric--success"><span>Successful</span><strong><?= $summary['success'] ?></strong><small>Provider accepted</small></article>
    <article class="log-metric--failure"><span>Failed</span><strong><?= $summary['failure'] ?></strong><small>Needs review</small></article>
    <article class="log-metric--retry"><span>Retries</span><strong><?= $summary['retries'] ?></strong><small>Second attempts</small></article>
</section>

<section class="delivery-ledger" aria-labelledby="delivery-ledger-title">
    <div class="delivery-ledger__heading">
        <div>
            <p class="eyebrow"><span></span> Database record</p>
            <h2 id="delivery-ledger-title">Notification ledger</h2>
            <p>Only delivery attempts belonging to your clients appear here.</p>
        </div>
        <span class="result-count"><?= count($notificationLogs) ?> shown</span>
    </div>

    <form class="log-filters" action="<?= escape(appUrl('logs.php')) ?>" method="get">
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All outcomes</option>
                <option value="success" <?= $status === 'success' ? 'selected' : '' ?>>Successful</option>
                <option value="failure" <?= $status === 'failure' ? 'selected' : '' ?>>Failed</option>
            </select>
        </div>
        <div class="field">
            <label for="type">Channel</label>
            <select id="type" name="type">
                <option value="">All channels</option>
                <option value="push" <?= $type === 'push' ? 'selected' : '' ?>>Push</option>
                <option value="sms" <?= $type === 'sms' ? 'selected' : '' ?>>SMS</option>
            </select>
        </div>
        <div class="log-filters__actions">
            <button class="button button--primary" type="submit">Filter activity</button>
            <a class="button button--secondary" href="<?= escape(appUrl('logs.php')) ?>">Reset</a>
        </div>
    </form>

    <?php if ($notificationLogs === []): ?>
        <div class="log-empty"><span aria-hidden="true">◎</span><div><h3>No delivery attempts found.</h3><p>New Push and SMS activity will appear after the notification cron runs.</p></div></div>
    <?php else: ?>
        <div class="log-stream">
            <?php foreach ($notificationLogs as $log): ?>
                <article class="delivery-log delivery-log--<?= escape($log['status']) ?>">
                    <span class="delivery-log__signal" aria-hidden="true"></span>
                    <div class="delivery-log__identity">
                        <span><?= escape(strtoupper($log['type'])) ?> · attempt <?= (int) $log['attempt'] ?></span>
                        <h3><?= escape($log['client_name']) ?></h3>
                        <small><?= escape(serviceTypeLabel($log['service_type'])) ?><?= $log['provider'] !== null ? ' · ' . escape($log['provider']) : '' ?></small>
                    </div>
                    <div class="delivery-log__message"><span class="delivery-status delivery-status--<?= escape($log['status']) ?>"><?= escape(ucfirst($log['status'])) ?></span><p><?= escape($log['message']) ?></p></div>
                    <div class="delivery-log__time"><time datetime="<?= escape($log['sent_at']) ?>"><?= escape(date('M j, Y', strtotime($log['sent_at']))) ?></time><small><?= escape(date('H:i', strtotime($log['sent_at']))) ?> · due <?= escape($log['scheduled_for']) ?></small></div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="system-log" aria-labelledby="system-log-title">
    <div class="system-log__heading">
        <div><p class="eyebrow"><span></span> File record</p><h2 id="system-log-title">Cron monitor</h2></div>
        <code>logs/notifications.log</code>
    </div>
    <?php if ($fileLogs === []): ?>
        <div class="system-log__empty">No cron activity has been recorded yet.</div>
    <?php else: ?>
        <ol class="system-log__entries">
            <?php foreach ($fileLogs as $entry): ?>
                <li class="system-entry system-entry--<?= escape($entry['level']) ?>">
                    <time datetime="<?= escape($entry['timestamp']) ?>"><?= escape(date('M j · H:i:s', strtotime($entry['timestamp']))) ?></time>
                    <span><?= escape(str_replace('_', ' ', strtoupper($entry['event']))) ?></span>
                    <p><?= escape($entry['message']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
    <p class="system-log__note">System events are generic. User-linked entries are filtered to your account.</p>
</section>
<?php require __DIR__ . '/partials/app-bottom.php'; ?>
