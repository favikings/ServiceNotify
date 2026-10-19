<?php

declare(strict_types=1);

require dirname(__DIR__) . '/partials/app-top.php';
?>
<section class="empty-state empty-state--error">
    <div class="empty-state__signal" aria-hidden="true"><span></span></div>
    <p class="eyebrow"><span></span> Not found</p>
    <h1>That client is not available.</h1>
    <p>It may have been deleted, the link may be incorrect, or it may belong to a different account.</p>
    <a class="button button--primary" href="<?= escape(appUrl('clients.php')) ?>">Return to clients</a>
</section>
<?php require dirname(__DIR__) . '/partials/app-bottom.php'; ?>

