<?php

declare(strict_types=1);

require dirname(__DIR__) . '/partials/auth-top.php';
?>
<header class="form-heading">
    <p class="eyebrow"><span></span> Start organised</p>
    <h1>Create your workspace</h1>
    <p>Set up your private command centre for client renewals.</p>
</header>

<?php if (isset($errors['form'])): ?>
    <div class="alert alert--error" role="alert">
        <span aria-hidden="true">!</span>
        <p><?= escape($errors['form']) ?></p>
    </div>
<?php endif; ?>

<?php if ($errors !== [] && !isset($errors['form'])): ?>
    <div class="alert alert--error" role="alert">
        <span aria-hidden="true">!</span>
        <p>Check the highlighted fields and try again.</p>
    </div>
<?php endif; ?>

<form class="auth-form" action="<?= escape(appUrl('register.php')) ?>" method="post" novalidate>
    <input type="hidden" name="_token" value="<?= escape(Csrf::token()) ?>">

    <div class="field">
        <label for="name">Your name</label>
        <input
            id="name"
            name="name"
            type="text"
            value="<?= escape($values['name']) ?>"
            autocomplete="name"
            placeholder="Ada Okafor"
            maxlength="100"
            required
            <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>
        >
        <?php if (isset($errors['name'])): ?>
            <p class="field__error" id="name-error"><?= escape($errors['name']) ?></p>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="email">Email address</label>
        <input
            id="email"
            name="email"
            type="email"
            value="<?= escape($values['email']) ?>"
            autocomplete="email"
            inputmode="email"
            placeholder="you@example.com"
            maxlength="150"
            required
            <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>
        >
        <?php if (isset($errors['email'])): ?>
            <p class="field__error" id="email-error"><?= escape($errors['email']) ?></p>
        <?php endif; ?>
    </div>

    <div class="field">
        <div class="field__label-row">
            <label for="password">Password</label>
            <span class="field__hint">8+ characters</span>
        </div>
        <div class="password-field">
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                minlength="8"
                required
                <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
            >
            <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
            </button>
        </div>
        <?php if (isset($errors['password'])): ?>
            <p class="field__error" id="password-error"><?= escape($errors['password']) ?></p>
        <?php endif; ?>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm password</label>
        <div class="password-field">
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
                <?= isset($errors['password_confirmation']) ? 'aria-invalid="true" aria-describedby="password-confirmation-error"' : '' ?>
            >
            <button type="button" class="password-toggle" data-password-toggle="password_confirmation" aria-label="Show password confirmation" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
            </button>
        </div>
        <?php if (isset($errors['password_confirmation'])): ?>
            <p class="field__error" id="password-confirmation-error"><?= escape($errors['password_confirmation']) ?></p>
        <?php endif; ?>
    </div>

    <button class="button button--primary" type="submit">
        <span>Create account</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
    </button>
</form>

<p class="auth-switch">Already have an account? <a href="<?= escape(appUrl('login.php')) ?>">Sign in</a></p>
<p class="auth-meta"><span aria-hidden="true">●</span> Your data stays private to your developer account.</p>
<?php require dirname(__DIR__) . '/partials/auth-bottom.php'; ?>

