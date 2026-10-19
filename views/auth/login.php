<?php

declare(strict_types=1);

require dirname(__DIR__) . '/partials/auth-top.php';
?>
<header class="form-heading">
    <p class="eyebrow"><span></span> Secure workspace</p>
    <h1>Welcome back</h1>
    <p>Sign in to review the services that need your attention.</p>
</header>

<?php if ($notice !== null): ?>
    <div class="alert alert--notice" role="status">
        <span aria-hidden="true">✓</span>
        <p><?= escape($notice) ?></p>
    </div>
<?php endif; ?>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert--error" role="alert">
        <span aria-hidden="true">!</span>
        <p><?= escape($errors['form']) ?></p>
    </div>
<?php endif; ?>

<form class="auth-form" action="<?= escape(appUrl('login.php')) ?>" method="post" novalidate>
    <input type="hidden" name="_token" value="<?= escape(Csrf::token()) ?>">

    <div class="field">
        <label for="email">Email address</label>
        <input
            id="email"
            name="email"
            type="email"
            value="<?= escape($email) ?>"
            autocomplete="email"
            inputmode="email"
            placeholder="you@example.com"
            required
        >
    </div>

    <div class="field">
        <div class="field__label-row">
            <label for="password">Password</label>
            <span class="field__hint">At least 8 characters</span>
        </div>
        <div class="password-field">
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            >
            <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
            </button>
        </div>
    </div>

    <button class="button button--primary" type="submit">
        <span>Sign in</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
    </button>
</form>

<p class="auth-switch">New to ServiceNotify? <a href="<?= escape(appUrl('register.php')) ?>">Create an account</a></p>
<p class="auth-meta"><span aria-hidden="true">●</span> Your session is protected and expires after inactivity.</p>
<?php require dirname(__DIR__) . '/partials/auth-bottom.php'; ?>

