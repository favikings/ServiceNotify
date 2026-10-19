<?php

declare(strict_types=1);

$isEditing = $formMode === 'edit';
require dirname(__DIR__) . '/partials/app-top.php';
?>
<header class="dashboard__header page-header page-header--compact">
    <div>
        <a class="back-link" href="<?= escape(appUrl('services.php')) ?>"><span aria-hidden="true">←</span> Services</a>
        <p class="eyebrow"><span></span> <?= $isEditing ? 'Update renewal' : 'New renewal' ?></p>
        <h1><?= $isEditing ? 'Edit service' : 'Add a service' ?></h1>
        <p><?= $isEditing ? 'Keep the ownership, billing, and deadline details accurate.' : 'Add the operational details that make an upcoming expiry visible.' ?></p>
    </div>
</header>

<?php if ($clients === []): ?>
    <section class="empty-state">
        <div class="empty-state__signal" aria-hidden="true"><span></span></div>
        <p class="eyebrow"><span></span> Client required</p>
        <h2>Add a client before a service.</h2>
        <p>Services belong to client records so ownership and future reminders stay correctly connected.</p>
        <a class="button button--primary" href="<?= escape(appUrl('client-create.php')) ?>">Add client</a>
    </section>
<?php else: ?>
    <?php if ($errors !== []): ?>
        <div class="alert alert--error form-alert" role="alert">
            <span aria-hidden="true">!</span>
            <p><?= isset($errors['form']) ? escape($errors['form']) : 'Check the highlighted fields and try again.' ?></p>
        </div>
    <?php endif; ?>

    <form class="service-record-form" action="<?= escape($formAction) ?>" method="post" novalidate>
        <input type="hidden" name="_token" value="<?= escape(Csrf::token()) ?>">

        <section class="form-card" aria-labelledby="service-details-title">
            <div class="form-card__intro">
                <span class="section-number" aria-hidden="true">01</span>
                <div><h2 id="service-details-title">Service details</h2><p>Connect the service to one of your clients and identify what is being renewed.</p></div>
            </div>
            <div class="record-form record-form--section">
                <div class="field field--wide">
                    <label for="client_id">Client <span aria-hidden="true">*</span></label>
                    <select id="client_id" name="client_id" required <?= isset($errors['client_id']) ? 'aria-invalid="true" aria-describedby="client-id-error"' : '' ?>>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= (string) $client['id'] === $values['client_id'] ? 'selected' : '' ?>><?= escape($client['name'] . ($client['organization'] !== null ? ' · ' . $client['organization'] : '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['client_id'])): ?><p class="field__error" id="client-id-error"><?= escape($errors['client_id']) ?></p><?php endif; ?>
                </div>

                <fieldset class="choice-field field--wide">
                    <legend>Service type <span aria-hidden="true">*</span></legend>
                    <div class="segmented-control">
                        <?php foreach (['domain' => 'Domain', 'hosting' => 'Hosting', 'maintenance' => 'Maintenance'] as $value => $label): ?>
                            <label><input type="radio" name="type" value="<?= $value ?>" <?= $values['type'] === $value ? 'checked' : '' ?>><span><?= $label ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errors['type'])): ?><p class="field__error"><?= escape($errors['type']) ?></p><?php endif; ?>
                </fieldset>

                <div class="field field--wide">
                    <label for="provider">Provider <span class="optional">Optional</span></label>
                    <input id="provider" name="provider" type="text" value="<?= escape($values['provider']) ?>" placeholder="e.g. Cloudflare or DigitalOcean" maxlength="150" <?= isset($errors['provider']) ? 'aria-invalid="true" aria-describedby="provider-error"' : '' ?>>
                    <?php if (isset($errors['provider'])): ?><p class="field__error" id="provider-error"><?= escape($errors['provider']) ?></p><?php endif; ?>
                </div>

                <div class="field field--wide">
                    <label for="expiry_date">Expiry date <span aria-hidden="true">*</span></label>
                    <input id="expiry_date" name="expiry_date" type="date" value="<?= escape($values['expiry_date']) ?>" required <?= isset($errors['expiry_date']) ? 'aria-invalid="true" aria-describedby="expiry-date-error"' : '' ?>>
                    <?php if (isset($errors['expiry_date'])): ?><p class="field__error" id="expiry-date-error"><?= escape($errors['expiry_date']) ?></p><?php endif; ?>
                </div>
            </div>
        </section>

        <section class="form-card" aria-labelledby="billing-title">
            <div class="form-card__intro">
                <span class="section-number" aria-hidden="true">02</span>
                <div><h2 id="billing-title">Billing context</h2><p>Optional commercial details make each renewal easier to recognise.</p></div>
            </div>
            <div class="record-form record-form--section">
                <div class="field">
                    <label for="cost">Cost <span class="optional">Optional</span></label>
                    <input id="cost" name="cost" type="number" value="<?= escape($values['cost']) ?>" inputmode="decimal" min="0" max="99999999.99" step="0.01" placeholder="0.00" <?= isset($errors['cost']) ? 'aria-invalid="true" aria-describedby="cost-error cost-help"' : 'aria-describedby="cost-help"' ?>>
                    <p class="field__help" id="cost-help">Enter the amount without a currency symbol.</p>
                    <?php if (isset($errors['cost'])): ?><p class="field__error" id="cost-error"><?= escape($errors['cost']) ?></p><?php endif; ?>
                </div>

                <div class="field">
                    <label for="currency">Currency <span class="optional">Required with cost</span></label>
                    <input id="currency" name="currency" type="text" value="<?= escape($values['currency']) ?>" inputmode="text" list="currency-suggestions" placeholder="NGN or USD" minlength="3" maxlength="3" autocapitalize="characters" <?= isset($errors['currency']) ? 'aria-invalid="true" aria-describedby="currency-error currency-help"' : 'aria-describedby="currency-help"' ?>>
                    <datalist id="currency-suggestions">
                        <option value="NGN">Nigerian naira</option>
                        <option value="USD">US dollar</option>
                        <option value="EUR">Euro</option>
                        <option value="GBP">British pound</option>
                        <option value="GHS">Ghanaian cedi</option>
                        <option value="KES">Kenyan shilling</option>
                        <option value="ZAR">South African rand</option>
                        <option value="CAD">Canadian dollar</option>
                        <option value="AUD">Australian dollar</option>
                    </datalist>
                    <p class="field__help" id="currency-help">Use a 3-letter code. Suggestions do not limit other currencies.</p>
                    <?php if (isset($errors['currency'])): ?><p class="field__error" id="currency-error"><?= escape($errors['currency']) ?></p><?php endif; ?>
                </div>

                <div class="field">
                    <label for="billing_cycle">Billing cycle <span aria-hidden="true">*</span></label>
                    <select id="billing_cycle" name="billing_cycle" required <?= isset($errors['billing_cycle']) ? 'aria-invalid="true" aria-describedby="billing-cycle-error"' : '' ?>>
                        <?php foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly', 'custom' => 'Custom'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $values['billing_cycle'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['billing_cycle'])): ?><p class="field__error" id="billing-cycle-error"><?= escape($errors['billing_cycle']) ?></p><?php endif; ?>
                </div>

                <div class="field">
                    <label for="payment_account">Payment account <span class="optional">Optional</span></label>
                    <input id="payment_account" name="payment_account" type="text" value="<?= escape($values['payment_account']) ?>" placeholder="e.g. Business card ending 4242" maxlength="150" <?= isset($errors['payment_account']) ? 'aria-invalid="true" aria-describedby="payment-account-error"' : '' ?>>
                    <?php if (isset($errors['payment_account'])): ?><p class="field__error" id="payment-account-error"><?= escape($errors['payment_account']) ?></p><?php endif; ?>
                </div>
            </div>
        </section>

        <section class="form-card reminder-form-card" aria-labelledby="reminders-title">
            <div class="form-card__intro">
                <span class="section-number" aria-hidden="true">03</span>
                <div><h2 id="reminders-title">Reminder schedule</h2><p>Enable any timing you need, then choose how that reminder should reach you.</p></div>
            </div>
            <div class="reminder-form">
                <div class="reminder-form__note">
                    <span aria-hidden="true">↗</span>
                    <p><strong>Optional for now.</strong> Delivery begins when the notification engine is connected in Prompt 7.</p>
                </div>

                <div class="reminder-options">
                    <?php foreach (ReminderService::PRESETS as $frequency): ?>
                        <?php
                        $preset = $reminderValues['presets'][$frequency];
                        $reminderErrorId = 'reminder-' . $frequency . '-error';
                        ?>
                        <fieldset class="reminder-option <?= $preset['enabled'] ? 'reminder-option--enabled' : '' ?>" data-reminder-card>
                            <legend class="sr-only"><?= $frequency ?> days before expiry</legend>
                            <div class="reminder-option__header">
                                <div>
                                    <strong><?= $frequency ?></strong>
                                    <span><b>days before</b><small><?= $frequency === 30 ? 'Early notice' : ($frequency === 7 ? 'Plan the renewal' : 'Final reminder') ?></small></span>
                                </div>
                                <label class="switch-control">
                                    <span class="sr-only">Enable <?= $frequency ?>-day reminder</span>
                                    <input
                                        type="checkbox"
                                        name="reminders[presets][<?= $frequency ?>][enabled]"
                                        value="1"
                                        data-reminder-enable
                                        <?= $preset['enabled'] ? 'checked' : '' ?>
                                        <?= isset($errors['reminder_' . $frequency]) ? 'aria-describedby="' . $reminderErrorId . '"' : '' ?>
                                    >
                                    <span aria-hidden="true"></span>
                                </label>
                            </div>
                            <div class="channel-choices" aria-label="Channels for <?= $frequency ?>-day reminder">
                                <?php foreach (['push' => 'Push', 'sms' => 'SMS'] as $channel => $label): ?>
                                    <label>
                                        <input
                                            type="checkbox"
                                            name="reminders[presets][<?= $frequency ?>][channels][]"
                                            value="<?= $channel ?>"
                                            data-reminder-channel
                                            <?= $preset['channels'][$channel] ? 'checked' : '' ?>
                                            <?= !$preset['enabled'] ? 'disabled' : '' ?>
                                        >
                                        <span><?= $label ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (isset($errors['reminder_' . $frequency])): ?>
                                <p class="field__error" id="<?= $reminderErrorId ?>"><?= escape($errors['reminder_' . $frequency]) ?></p>
                            <?php endif; ?>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="form-actions service-form-actions">
            <a class="button button--secondary" href="<?= escape(appUrl('services.php')) ?>">Cancel</a>
            <button class="button button--primary" type="submit"><?= $isEditing ? 'Save changes' : 'Add service' ?></button>
        </div>
    </form>
<?php endif; ?>
<?php require dirname(__DIR__) . '/partials/app-bottom.php'; ?>
