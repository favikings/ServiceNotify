# Notification Engine

Prompt 7 implements the daily Push and SMS notification workflow.

## Delivery policy

- The developer account receives notifications; client phone numbers are never used as a fallback.
- Delivery destinations live in `users.settings` as top-level `sms_phone` and `fcm_tokens` values.
- Prompt 10 enrolls and revokes browser Push destinations through an explicit user-consent control and the authenticated `push-subscription.php` endpoint.
- `sms_phone` must use international E.164 format. `fcm_tokens` is an array and may contain multiple registered devices.
- A reminder is due only when `expiry_date = run date + frequency`. Expired services and missed historical dates are not backfilled.
- `NOTIFICATION_TIMEZONE` controls the run date and defaults to `Africa/Lagos`.
- Message content includes the client, service type, optional provider, expiry date, and remaining days.

## Daily job

`cron/notify.php` is CLI-only. It loads the database and provider configuration, evaluates due reminders, sends each configured channel, prints a summary, and returns a non-zero exit code for a fatal job error. Provider delivery failures are recorded but do not make the whole cron process fail.

Run it once per day from the project root:

```bash
php cron/notify.php
```

MySQL/MariaDB advisory locking prevents two processes from running the same notification date concurrently.

## Providers

FCM uses the current HTTP v1 API. `GOOGLE_APPLICATION_CREDENTIALS` points to a service-account JSON file kept outside the repository. The engine signs a short-lived OAuth 2.0 assertion, caches the access token in memory, and sends messages to the configured Firebase project. FCM `UNREGISTERED` responses and invalid-token `INVALID_ARGUMENT` responses remove the affected token from `users.settings`.

Africa's Talking uses its HTTPS messaging endpoint through PHP cURL. `AFRICASTALKING_ENVIRONMENT=sandbox` selects the sandbox endpoint; `live` selects production. A failed SMS is retried once during the same run. Push is attempted once.

## Attempt log and duplicate protection

Migration `006_create_notifications.sql` creates the notification attempt log. Every attempt records its service, originating reminder, channel, success/failure status, message/provider detail, scheduled date, attempt number, and timestamp.

The unique `(reminder_id, type, scheduled_for, attempt)` key prevents a repeated cron invocation from recreating the same attempt. A Push schedule has at most one attempt. An SMS schedule has at most two attempts, and no second attempt is made after a success. A reservation row is inserted before contacting a provider so overlapping processes cannot both claim the same attempt.

Prompt 9 adds structured file logging for every delivery attempt, explicit SMS retries, and cron start/completion/failure events. See `docs/logging.md` for the file format and authenticated monitoring view.

## Verification

- `tests/notification_test.php` covers exact-date scheduling, Push and SMS dispatch, one SMS retry, per-attempt database and file logs, invalid-token removal, cross-user file-log isolation, repeat-run idempotency, and cascade deletion.
- `tests/notification_provider_test.php` verifies FCM HTTP v1 and Africa's Talking request/response contracts using HTTP mocks. It does not contact either provider or send real notifications.
- Real sandbox/live delivery still requires operator-owned credentials and enrolled destinations.
