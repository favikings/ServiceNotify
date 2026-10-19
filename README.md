# ServiceNotify

ServiceNotify is a plain PHP and MySQL PWA for tracking client service expiry dates and sending scheduled reminders.

Priced services store the amount and three-letter currency code separately, allowing mixed currencies such as NGN and USD without exchange-rate conversion.

## Requirements

- PHP 8.1 or newer
- PDO MySQL extension
- PHP cURL and OpenSSL extensions for notification delivery
- MySQL 8 or newer
- Apache with `mod_rewrite` and `.htaccess` overrides enabled
- Composer for installing PhpSpreadsheet
- Node.js/npm for rebuilding Tailwind CSS

## Local setup

1. Copy `.env.example` to `.env`.
2. Update the database values in `.env` for your local MySQL installation.
3. Create the `service_notify` database, then run `php database/migrate.php` to create the current tables.
4. Run `composer install` to install the locked PHP dependencies.
5. Run `npm install` and `npm run build:css` to build the local Tailwind stylesheet.
6. Open `http://localhost/ServiceNotify/`. The root rewrite rules serve files internally from `public/`, so `public` never appears in the browser URL.

## Notification setup

Prompt 7 uses Firebase Cloud Messaging HTTP v1 and Africa's Talking. Keep all provider credentials outside the repository and configure these `.env` values:

- `NOTIFICATION_TIMEZONE` (defaults to `Africa/Lagos`)
- `GOOGLE_APPLICATION_CREDENTIALS` (absolute path to a Firebase service-account JSON file)
- `FIREBASE_PROJECT_ID` (optional when the service-account file contains `project_id`)
- `AFRICASTALKING_USERNAME`, `AFRICASTALKING_API_KEY`, and optional `AFRICASTALKING_SENDER_ID`
- `AFRICASTALKING_ENVIRONMENT` (`sandbox` or `live`)

Developer delivery destinations are stored in `users.settings`:

```json
{
  "sms_phone": "+2348012345678",
  "fcm_tokens": ["device-registration-token"]
}
```

Phone numbers must use international E.164 format. Prompt 10 provides user-consented browser Push enrollment when the Firebase web values below are configured; invalid destinations returned by FCM are removed automatically. Run the daily job from the project root with `php cron/notify.php`. Configure the operating-system scheduler to invoke it once per day.

Browser Push additionally requires `FIREBASE_WEB_API_KEY`, `FIREBASE_MESSAGING_SENDER_ID`, `FIREBASE_WEB_APP_ID`, and `FIREBASE_VAPID_PUBLIC_KEY`. These identify the registered Firebase web app and public Web Push credential; service-account credentials remain server-only.

Do not commit `.env`; it is intentionally ignored because it can contain secrets.

## Database access

Require `config/config.php` wherever a PDO connection is needed:

```php
/** @var PDO $pdo */
$pdo = require __DIR__ . '/config/config.php';
```

The connection uses exception mode, associative fetches by default, native prepared statements, and `utf8mb4`.

## Frontend assets

The Tailwind source is `resources/css/app.css`; the browser receives the compiled `public/assets/css/app.css` file.

```bash
npm run build:css
npm run watch:css
```

Authentication routes are `/register.php`, `/login.php`, `/logout.php`, and the protected `/dashboard.php`. Client management uses `/clients.php`, `/client-create.php`, `/client-edit.php?id={id}`, and `/client-delete.php?id={id}`. Service management uses `/services.php`, `/service-create.php`, `/service-edit.php?id={id}`, and `/service-delete.php?id={id}`. The corresponding PHP files remain physically isolated inside `public/`.

CSV and Excel reports are prepared on the authenticated `/reports.php` page and downloaded through `/export.php`. Reports accepts `client_id`, `type`, `date_from`, and `date_to`; the download endpoint also requires `format=csv` or `format=xlsx`.

Notification monitoring is available to authenticated users at `/logs.php`. Database delivery records and structured `logs/notifications.log` events are ownership-filtered; generic cron lifecycle events contain no recipient credentials.

PWA assets are served from `/manifest.json` and `/sw.js`. On HTTPS or localhost, ServiceNotify can be installed and keeps previously visited dashboard/client views available offline in a session-keyed private cache. Writes, reports, and authentication remain network-only. Regenerate branded icons with `php resources/icons/generate-icons.php`.

## Web root and clean URLs

The repository root `.htaccess` is the public routing boundary when the complete project is uploaded beneath an Apache document root:

- Clean URLs such as `/login.php` are internally served from `public/login.php`.
- Requests containing `/public/` redirect to the equivalent clean URL.
- Private folders such as `config/`, `src/`, `database/`, `docs/`, and `logs/` return 404.
- Directory listing is disabled.

For a server where virtual-host configuration is available, pointing the domain's document root directly to `public/` is also secure and keeps the same clean URLs. Set `APP_URL` to the final origin/base path without `/public`, for example `https://notify.example.com`.

## Production security

Set `APP_ENV=production` and use an `https://` `APP_URL`. The application redirects plain HTTP requests to that canonical URL with HTTP 308, sends HSTS on secure production responses, and marks session cookies Secure. If TLS terminates at a reverse proxy, list only its exact IP address(es) in the comma-separated `TRUSTED_PROXY_IPS`; forwarded protocol and client-IP headers are ignored from untrusted senders.

Login throttling defaults to 5 failures per account/IP pair and 25 failures per IP across all account names in a rolling 15-minute window. Adjust `AUTH_MAX_LOGIN_ATTEMPTS`, `AUTH_MAX_IP_LOGIN_ATTEMPTS`, and `AUTH_LOGIN_WINDOW_SECONDS` when required. The local file-backed limiter is for a single application server; use a shared atomic store before horizontal scaling. See `docs/security.md` for the full security contract.

## Verification

Run the dependency-free setup checks from the project root:

```bash
php tests/setup_test.php
php tests/auth_test.php
php tests/client_test.php
php tests/service_test.php
php tests/reminder_test.php
php tests/dashboard_test.php
php tests/notification_test.php
php tests/notification_provider_test.php
php tests/report_test.php
php tests/logging_test.php
php tests/pwa_test.php
php tests/push_subscription_test.php
php tests/security_test.php
```

The tests validate environment parsing, authentication, ownership-scoped client/service CRUD, transactional reminder configuration, dashboard filtering, notification scheduling, structured logging, retries, idempotency, report exports, request/CSRF/HTTPS protections, and mocked provider request contracts without sending real messages. Feature suites use in-memory SQLite databases and provider mocks to verify behavior safely.
