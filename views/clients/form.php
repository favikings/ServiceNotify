<?php

declare(strict_types=1);

$isEditing = $formMode === 'edit';
require dirname(__DIR__) . '/partials/app-top.php';
?>
<header class="dashboard__header page-header page-header--compact">
    <div>
        <a class="back-link" href="<?= escape(appUrl('clients.php')) ?>"><span aria-hidden="true">←</span> Clients</a>
        <p class="eyebrow"><span></span> <?= $isEditing ? 'Update relationship' : 'New relationship' ?></p>
        <h1><?= $isEditing ? 'Edit client' : 'Add a client' ?></h1>
        <p><?= $isEditing ? 'Keep contact details accurate for future reminders and service records.' : 'Create the owner record that services and reminders will connect to.' ?></p>
    </div>
</header>

<?php if ($errors !== []): ?>
    <div class="alert alert--error form-alert" role="alert">
        <span aria-hidden="true">!</span>
        <p><?= isset($errors['form']) ? escape($errors['form']) : 'Check the highlighted fields and try again.' ?></p>
    </div>
<?php endif; ?>

<section class="form-card" aria-labelledby="client-details-title">
    <div class="form-card__intro">
        <span class="section-number" aria-hidden="true">01</span>
        <div>
            <h2 id="client-details-title">Client details</h2>
            <p>Only the name is required. Add contact information you use for service renewals.</p>
        </div>
    </div>

    <form class="record-form" action="<?= escape($formAction) ?>" method="post" novalidate>
        <input type="hidden" name="_token" value="<?= escape(Csrf::token()) ?>">

        <div class="field field--wide">
            <label for="name">Client name <span aria-hidden="true">*</span></label>
            <input
                id="name"
                name="name"
                type="text"
                value="<?= escape($values['name']) ?>"
                autocomplete="name"
                placeholder="e.g. James Adams"
                maxlength="150"
                required
                <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>
            >
            <?php if (isset($errors['name'])): ?><p class="field__error" id="name-error"><?= escape($errors['name']) ?></p><?php endif; ?>
        </div>

        <div class="field field--wide">
            <label for="organization">Organization <span class="optional">Optional</span></label>
            <input
                id="organization"
                name="organization"
                type="text"
                value="<?= escape($values['organization']) ?>"
                autocomplete="organization"
                placeholder="e.g. Northstar Studio"
                maxlength="150"
                <?= isset($errors['organization']) ? 'aria-invalid="true" aria-describedby="organization-error"' : '' ?>
            >
            <?php if (isset($errors['organization'])): ?><p class="field__error" id="organization-error"><?= escape($errors['organization']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="email">Email address <span class="optional">Optional</span></label>
            <input
                id="email"
                name="email"
                type="email"
                value="<?= escape($values['email']) ?>"
                autocomplete="email"
                inputmode="email"
                placeholder="client@example.com"
                maxlength="150"
                <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>
            >
            <?php if (isset($errors['email'])): ?><p class="field__error" id="email-error"><?= escape($errors['email']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="phone">Phone number <span class="optional">Optional</span></label>
            <input
                id="phone"
                name="phone"
                type="tel"
                value="<?= escape($values['phone']) ?>"
                autocomplete="tel"
                inputmode="tel"
                placeholder="+234 801 234 5678"
                maxlength="20"
                <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="phone-error phone-help"' : 'aria-describedby="phone-help"' ?>
            >
            <p class="field__help" id="phone-help">Use 7–15 digits; spaces, brackets, dots, and hyphens are accepted.</p>
            <?php if (isset($errors['phone'])): ?><p class="field__error" id="phone-error"><?= escape($errors['phone']) ?></p><?php endif; ?>
        </div>

        <div class="form-actions field--wide">
            <a class="button button--secondary" href="<?= escape(appUrl('clients.php')) ?>">Cancel</a>
            <button class="button button--primary" type="submit"><?= $isEditing ? 'Save changes' : 'Add client' ?></button>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/partials/app-bottom.php'; ?>

