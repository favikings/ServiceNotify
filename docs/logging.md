# Logging and Monitoring

Prompt 9 implements durable notification activity logging and an authenticated monitoring page at `logs.php`.

## File log

- `logs/notifications.log` uses newline-delimited JSON so each append is one independently parseable event.
- Writes use an exclusive file lock. A logging failure falls back to PHP's error log and does not interrupt notification delivery.
- Events include `cron_started`, `cron_completed`, `cron_failed`, `notification_attempt`, and `notification_retry`.
- Cron completion records due, attempted, successful, failed, and skipped counts. Fatal cron entries record the exception class/code without exposing the exception message through the web view.
- Delivery entries include ownership and record IDs, channel, attempt, scheduled date, and status. Phone, token, credential, password, secret, and API-key context fields are always redacted.
- Runtime `.log` files remain ignored by Git, and Apache denies direct requests to the entire `logs/` directory.

## Monitoring page

`logs.php` requires a signed-in user. Role-based administration is deferred, so the current safe interpretation of “admin view” is a self-scoped operator view:

- Database notification queries join through `notifications.service_id -> services.client_id -> clients.user_id` and expose only the authenticated user's records.
- File entries carrying a `user_id` are returned only to that user.
- Entries without a user are visible only when they are generic `cron_*` lifecycle events. Their displayed message contains no recipient or provider credential data.
- The page provides total, success, failure, and retry metrics plus status/channel filtering.
- The database ledger and structured cron monitor are both capped to recent records to keep the page operationally focused.

## Verification

- `tests/notification_test.php` verifies per-attempt file entries, explicit retry logging, and cross-user isolation alongside delivery behavior.
- `tests/logging_test.php` verifies database ownership boundaries, combined filters, structured JSON-line handling, sensitive-context redaction, authenticated controller wiring, and both monitoring surfaces.
