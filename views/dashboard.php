<?php

declare(strict_types=1);

$filterQuery = [
    'type' => $filters->type ?? '',
    'date_from' => $filters->dateFrom,
    'date_to' => $filters->dateTo,
];
$pageUrl = static fn (int $page): string => appUrl('dashboard.php?' . http_build_query($filterQuery + ['page' => $page]));
$isFiltered = $filters->type !== null
    || $filters->dateFrom !== $today->format('Y-m-d')
    || $filters->dateTo !== $today->modify('+30 days')->format('Y-m-d');
$nearest = $expiries[0] ?? null;
$nearestState = $nearest !== null ? serviceExpiryState($nearest['expiry_date'], $today) : null;

require __DIR__ . '/partials/app-top.php';
?>
<header class="dashboard__header dashboard-home__header">
    <div>
        <p class="eyebrow"><span></span> Renewal command centre</p>
        <h1>Hello, <?= escape(explode(' ', trim($user['name']))[0]) ?>.</h1>
        <p>Your nearest client deadlines, ordered for action.</p>
    </div>
    <a class="button button--primary header-action" href="<?= escape(appUrl($clientCount > 0 ? 'service-create.php' : 'client-create.php')) ?>">
        <span aria-hidden="true">＋</span> <?= $clientCount > 0 ? 'Add service' : 'Add client' ?>
    </a>
</header>

<?php if ($success !== null): ?>
    <div class="alert alert--notice dashboard__alert" role="status">
        <span aria-hidden="true">✓</span><p><?= escape($success) ?></p>
    </div>
<?php endif; ?>

<?php if ($filters->errors !== []): ?>
    <div class="alert alert--error dashboard__alert" role="alert">
        <span aria-hidden="true">!</span>
        <p><?= escape(implode(' ', array_values($filters->errors))) ?> Safe defaults were applied.</p>
    </div>
<?php endif; ?>

<section class="radar-hero" aria-labelledby="radar-title">
    <div class="radar-hero__copy">
        <span class="radar-hero__label"><?= $isFiltered ? 'Filtered deadline window' : 'Next 30 days' ?></span>
        <?php if ($nearest !== null && $nearestState !== null): ?>
            <p class="radar-hero__client"><?= escape($nearest['client_name']) ?> · <?= escape(serviceTypeLabel($nearest['type'])) ?></p>
            <h2 id="radar-title"><?= escape($nearest['provider'] ?? serviceTypeLabel($nearest['type']) . ' service') ?></h2>
            <div class="radar-hero__deadline radar-hero__deadline--<?= escape($nearestState['key']) ?>">
                <strong><?= escape($nearestState['detail']) ?></strong>
                <time datetime="<?= escape($nearest['expiry_date']) ?>"><?= escape(date('M j, Y', strtotime($nearest['expiry_date']))) ?></time>
            </div>
        <?php else: ?>
            <p class="radar-hero__client">No deadline detected</p>
            <h2 id="radar-title">Your selected window is clear.</h2>
            <p class="radar-hero__empty">Adjust the filters or add a service to place an expiry on the radar.</p>
        <?php endif; ?>
    </div>
    <div class="radar-orbit" aria-hidden="true"><span></span><i></i></div>
</section>

<section class="metric-grid dashboard-metrics" aria-label="Deadline summary">
    <article class="metric-card"><span>In this view</span><strong><?= $totalResults ?></strong><small><?= $totalResults === 1 ? 'matching expiry' : 'matching expiries' ?></small></article>
    <article class="metric-card metric-card--urgent"><span>Next 7 days</span><strong><?= $urgentCount ?></strong><small><?= $urgentCount === 1 ? 'deadline needs attention' : 'deadlines need attention' ?></small></article>
    <a class="metric-card metric-card--link" href="<?= escape(appUrl('services.php')) ?>"><span>All services</span><strong><?= $serviceCount ?></strong><small>Open the full renewal register</small></a>
</section>

<section class="expiry-workspace" aria-labelledby="expiry-heading">
    <div class="expiry-workspace__heading">
        <div>
            <p class="eyebrow"><span></span> Expiry queue</p>
            <h2 id="expiry-heading">Upcoming services</h2>
            <p><?= escape(date('M j, Y', strtotime($filters->dateFrom))) ?> — <?= escape(date('M j, Y', strtotime($filters->dateTo))) ?></p>
        </div>
        <span class="result-count"><?= $totalResults ?> result<?= $totalResults === 1 ? '' : 's' ?></span>
    </div>

    <form class="dashboard-filters" action="<?= escape(appUrl('dashboard.php')) ?>" method="get">
        <div class="field">
            <label for="type">Service type</label>
            <select id="type" name="type">
                <option value="">All services</option>
                <?php foreach (['domain', 'hosting', 'maintenance'] as $type): ?>
                    <option value="<?= escape($type) ?>" <?= $filters->type === $type ? 'selected' : '' ?>><?= escape(serviceTypeLabel($type)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="date_from">From</label>
            <input id="date_from" name="date_from" type="date" value="<?= escape($filters->dateFrom) ?>">
        </div>
        <div class="field">
            <label for="date_to">To</label>
            <input id="date_to" name="date_to" type="date" value="<?= escape($filters->dateTo) ?>">
        </div>
        <div class="dashboard-filters__actions">
            <button class="button button--primary" type="submit">Apply filters</button>
            <a class="button button--secondary" href="<?= escape(appUrl('dashboard.php')) ?>">Reset</a>
        </div>
    </form>

    <?php if ($expiries === []): ?>
        <div class="dashboard-empty">
            <span aria-hidden="true">○</span>
            <div>
                <h3><?= $serviceCount === 0 ? 'No services are being tracked yet.' : 'No expiries match this window.' ?></h3>
                <p><?= $serviceCount === 0 ? 'Add a client service and its expiry will appear here.' : 'Try a wider date range or reset the service type.' ?></p>
            </div>
            <a class="button button--secondary" href="<?= escape(appUrl($serviceCount === 0 ? ($clientCount > 0 ? 'service-create.php' : 'client-create.php') : 'dashboard.php')) ?>"><?= $serviceCount === 0 ? ($clientCount > 0 ? 'Add service' : 'Add client') : 'Clear filters' ?></a>
        </div>
    <?php else: ?>
        <div class="expiry-list">
            <div class="expiry-list__labels" aria-hidden="true"><span>Client / service</span><span>Provider</span><span>Expiry</span><span>Action</span></div>
            <?php foreach ($expiries as $service): ?>
                <?php $expiry = serviceExpiryState($service['expiry_date'], $today); ?>
                <article class="expiry-row expiry-row--<?= escape($expiry['key']) ?>">
                    <span class="expiry-row__rail" aria-hidden="true"></span>
                    <div class="expiry-row__identity">
                        <span class="service-type"><?= escape(serviceTypeLabel($service['type'])) ?></span>
                        <h3><?= escape($service['client_name']) ?></h3>
                    </div>
                    <div class="expiry-row__provider"><small>Provider</small><span><?= escape($service['provider'] ?? 'Not specified') ?></span></div>
                    <div class="expiry-row__date">
                        <span class="status-badge status-badge--<?= escape($expiry['key']) ?>"><i aria-hidden="true"></i><?= escape($expiry['label']) ?></span>
                        <strong><?= escape($expiry['detail']) ?></strong>
                        <time datetime="<?= escape($service['expiry_date']) ?>"><?= escape(date('M j, Y', strtotime($service['expiry_date']))) ?></time>
                    </div>
                    <a class="action-link" href="<?= escape(appUrl('service-edit.php?id=' . $service['id'])) ?>">Review</a>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Dashboard results pages">
                <?php if ($currentPage > 1): ?><a class="button button--secondary" rel="prev" href="<?= escape($pageUrl($currentPage - 1)) ?>">← Previous</a><?php else: ?><span></span><?php endif; ?>
                <span>Page <strong><?= $currentPage ?></strong> of <?= $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?><a class="button button--secondary" rel="next" href="<?= escape($pageUrl($currentPage + 1)) ?>">Next →</a><?php else: ?><span></span><?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/app-bottom.php'; ?>
