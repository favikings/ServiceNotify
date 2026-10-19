<?php

declare(strict_types=1);

$serviceTypeLabel = $filters->type !== null ? serviceTypeLabel($filters->type) : 'All service types';
$dateRangeLabel = date('M j, Y', strtotime($filters->dateFrom))
    . ' — ' . date('M j, Y', strtotime($filters->dateTo));

require __DIR__ . '/partials/app-top.php';
?>
<header class="dashboard__header page-header reports-header">
    <div>
        <p class="eyebrow"><span></span> Portable records</p>
        <h1>Reports</h1>
        <p>Define one precise slice of your renewal register, then carry it into finance, planning, or client review.</p>
    </div>
    <span class="reports-header__stamp" aria-hidden="true">CSV / XLSX</span>
</header>

<?php if ($reportErrors !== []): ?>
    <div class="alert alert--error dashboard__alert" role="alert">
        <span aria-hidden="true">!</span>
        <p><?= escape(implode(' ', array_values($reportErrors))) ?></p>
    </div>
<?php endif; ?>

<section class="reports-studio" aria-labelledby="report-builder-title">
    <div class="reports-studio__intro">
        <div>
            <p class="eyebrow"><span></span> 01 · Define scope</p>
            <h2 id="report-builder-title">Build a clean export.</h2>
            <p>Every selection is applied at the database boundary and restricted to your account.</p>
        </div>
        <div class="reports-studio__count" aria-label="Matching report rows">
            <strong><?= count($rows) ?></strong>
            <span>matching row<?= count($rows) === 1 ? '' : 's' ?></span>
        </div>
    </div>

    <form class="reports-filter" action="<?= escape(appUrl('reports.php')) ?>" method="get">
        <div class="field">
            <label for="client_id">Client</label>
            <select id="client_id" name="client_id">
                <option value="">All clients</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>" <?= $filters->clientId === $client['id'] ? 'selected' : '' ?>><?= escape($client['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="type">Service type</label>
            <select id="type" name="type">
                <option value="">All service types</option>
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
        <div class="reports-filter__actions">
            <button class="button button--primary" type="submit">Preview scope</button>
            <a class="button button--secondary" href="<?= escape(appUrl('reports.php')) ?>">Reset</a>
        </div>
    </form>
</section>

<section class="export-console" aria-labelledby="export-format-title">
    <div class="export-console__scope">
        <div>
            <p class="eyebrow"><span></span> 02 · Choose format</p>
            <h2 id="export-format-title">Export this register.</h2>
        </div>
        <dl>
            <div><dt>Client</dt><dd><?= escape($selectedClientName) ?></dd></div>
            <div><dt>Services</dt><dd><?= escape($serviceTypeLabel) ?></dd></div>
            <div><dt>Window</dt><dd><?= escape($dateRangeLabel) ?></dd></div>
        </dl>
    </div>

    <div class="export-format-grid">
        <?php if ($reportErrors === []): ?>
            <a class="export-format export-format--csv" href="<?= escape(appUrl('export.php?' . $exportQuery . '&format=csv')) ?>">
                <span class="export-format__code">.CSV</span>
                <span><strong>Comma-separated data</strong><small>Lightweight, universal, ready for imports.</small></span>
                <b aria-hidden="true">↓</b>
            </a>
            <a class="export-format export-format--xlsx" href="<?= escape(appUrl('export.php?' . $exportQuery . '&format=xlsx')) ?>">
                <span class="export-format__code">.XLSX</span>
                <span><strong>Formatted workbook</strong><small>Styled columns plus a report-context sheet.</small></span>
                <b aria-hidden="true">↓</b>
            </a>
        <?php else: ?>
            <div class="reports-blocked">
                <span aria-hidden="true">!</span>
                <p>Correct the scope above before generating a download.</p>
            </div>
        <?php endif; ?>
    </div>

    <p class="export-console__footnote"><span aria-hidden="true">●</span> Files include <?= count(ReportExporter::HEADERS) ?> columns and only data owned by <?= escape($user['name']) ?>.</p>
</section>
<?php require __DIR__ . '/partials/app-bottom.php'; ?>
