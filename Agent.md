# ServiceNotify Project Memory

> **Mandatory AI instruction:** Read this entire file before planning, implementing, reviewing, or modifying any feature. Re-read the relevant source documents in `docs/` when a detail is unclear. Update this file whenever an approved decision changes the product, architecture, schema, conventions, or scope. Do not silently invent missing requirements.

## 1. Purpose and source of truth

This file is the durable working memory for ServiceNotify. It summarizes the documents currently in `docs/` and records gaps that must not be filled by guesswork.

Source precedence:

1. The user's latest explicit instruction or approved decision.
2. The detailed documents in `docs/`.
3. This memory file, which is a summary and implementation guardrail.
4. Existing code and tests, once implementation begins.

If sources conflict, stop and surface the conflict. Do not silently choose a behavior. When a decision is made, update the detailed document, this file, and affected tests/code together.

## 2. Current project status

- Prompt 1 (Project Setup) is implemented: `config/`, `public/`, `src/`, `views/`, `cron/`, and `logs/` exist; `.env` loading and the PDO connection bootstrap are available.
- `config/config.php` loads `.env` and returns a configured MySQL PDO connection. The first-party `src/Env.php` loader keeps this setup dependency-free; server environment variables override file values.
- `.env.example` documents local variables, while `.env` and runtime log files are ignored. No real credentials are stored in the repository.
- Prompt 2 (User Authentication) is implemented: registration, login, POST-only logout, protected dashboard access, password hashing/verification, CSRF protection, session renewal/expiry, and login throttling are available.
- Authentication, client-management, and service-management UI follow the Evergreen Operations system and are mobile-first. `public/dashboard.php` remains a protected partial dashboard until Prompt 6 implements upcoming-expiry data; its client and service counts are live.
- `database/migrations/001_create_users.sql` through `006_create_notifications.sql` are the executable migrations. `database/migrate.php` records applied filenames in `schema_migrations`, applies each migration once, and safely skips previously applied files.
- Tailwind CSS uses the local npm CLI build (`resources/css/app.css` to `public/assets/css/app.css`); no runtime CSS CDN is used.
- The canonical local URL is `http://localhost/ServiceNotify/`; public URLs never contain `/public`. Root Apache rewrite rules internally serve `public/`, redirect explicit `/public` URLs, disable indexes, and return 404 for private project paths. Production `APP_URL` must likewise omit `/public`.
- The current XAMPP database reports MariaDB 10.4.32, and the authentication migration/live flow passes there. The documented deployment target remains MySQL 8+, which has not yet been integration-tested in this workspace.
- Prompt 3 (Client Management) is implemented: authenticated, ownership-scoped list/create/edit/delete flows, server-side validation, CSRF protection, 404 ownership concealment, responsive views, service counts, and cascade coverage are available.
- Prompt 4 (Service Management) is implemented: ownership-chain-scoped CRUD, strict date/cost/currency/enumeration validation, per-service multi-currency display, CSRF protection, 404 ownership concealment, expiry-state cards, dashboard counts, migrations, and cascade coverage are available.
- Prompt 5 (Reminder Configuration) is implemented inside service create/edit: independent 30/7/1-day timing cards, per-preset Push/SMS channels, transactional full-set reconciliation, ownership scoping, reminder counts, migration, and cascade coverage are available.
- Prompt 6 (Dashboard) is implemented with the default 30-day window, filters, ownership-scoped pagination, summary counts, responsive UI, and acceptance coverage.
- Prompt 7 (Notifications) is implemented with exact-date reminder evaluation, FCM HTTP v1, Africa's Talking Push/SMS delivery adapters, one SMS retry, invalid FCM token cleanup, daily concurrency locking, durable per-attempt idempotency, a CLI cron entry point, and mocked engine/provider coverage. Real delivery requires operator-owned credentials and enrolled destinations.
- Prompt 8 (Reports Export) is implemented with a dedicated Reports page, client/type/date filters, authenticated ownership-scoped CSV and XLSX downloads, approved column ordering, spreadsheet-injection defenses, download headers, PhpSpreadsheet 2.4.8, and file-content acceptance coverage. Report controls are intentionally excluded from the dashboard.
- Prompt 9 (Logging & Monitoring) is implemented with lock-safe JSON-line notification logs, cron lifecycle/error logging, explicit retry and delivery-attempt events, sensitive-context redaction, an authenticated Logs page, ownership-scoped database/file records, delivery filters, and acceptance coverage. Until roles exist, the monitoring page is deliberately self-scoped and never exposes another user's entries.
- Prompt 10 (PWA Compliance) is implemented with a scope-relative manifest, branded standard/maskable icons, shared service-worker registration, an install prompt, connectivity feedback, a branded offline fallback, static asset precaching, and network-first dashboard/client caching in session-keyed private caches. It also provides explicit browser Push consent, current Firebase FID enrollment/revocation, ownership-safe destination persistence, foreground/background notification handling, and notification-click routing. Authentication boundaries clear private caches, and non-GET requests remain network-only.
- Prompt 11 (Security Enhancements) is implemented with centralized `filter_input()` request extraction, complete CSRF coverage for state mutations, production HTTPS enforcement, explicit trusted-proxy handling, secure browser/session headers, and account/IP plus IP-wide login throttling.
- Planned implementation order is recorded in `docs/build-prompts.md` and summarized in section 12.
- The database definition at `docs/schema/schema.sql` is currently a Markdown document containing a fenced SQL block. It is **not directly executable as-is** unless the prose and code fences are removed or an executable migration/schema file is created.

## 3. Product definition

ServiceNotify is an installable Progressive Web App for freelance developers, small agencies, and IT professionals to track client domain, hosting, and maintenance expiry dates and receive reminders before expiry.

Hard product boundaries:

- The application is developer-facing. Clients do not receive accounts or access the application.
- The MVP supports multiple developer user accounts, with each user's client data isolated from other users.
- Notifications in MVP are push notifications through Firebase Cloud Messaging (FCM) and SMS through Africa's Talking.
- Email reminders are Phase 2, not MVP.
- Third-party billing integrations and UI polish are outside the test-plan scope.

Primary MVP outcomes:

- Secure account registration, login, logout, and password handling.
- CRUD management of clients and their services.
- Per-service reminder configuration.
- A dashboard of upcoming expiries.
- Automated daily push/SMS reminders with delivery logs.
- Filterable CSV and Excel exports.
- PWA installation and offline caching.

## 4. Approved technology and deployment constraints

- Backend: plain PHP 8.1+; no PHP framework is specified.
- Database: MySQL 8+ relational database.
- Database access: use PDO for the initial project setup, as explicitly requested in `docs/build-prompts.md`. The TRD also permits MySQLi, but do not mix database access styles without an approved change.
- Frontend: HTML5, Tailwind CSS, and vanilla JavaScript; no frontend framework is specified.
- UI system: follow `docs/ui-design-system.md`. The approved direction is the dark-first **Evergreen Operations** design with mobile as the primary experience and a deliberate desktop adaptation.
- Authentication: PHP sessions.
- Push provider: Firebase Cloud Messaging REST API.
- SMS provider: Africa's Talking API using PHP cURL.
- Spreadsheet export: PhpSpreadsheet. CSV export is also required.
- Configuration: environment variables loaded from `.env`; credentials and secrets must not be committed.
- Hosting: LAMP-compatible server with HTTPS enabled in production.
- Scheduling: a server cron job runs the notification process daily.
- Planned top-level application folders: `config/`, `public/`, `src/`, `views/`, `cron/`, and `logs/`.

Do not introduce a framework, alternate database, alternate notification provider, build system, or major architectural dependency without explicit approval.

### Approved UI foundation

- Theme: dark-first for MVP; no light theme is required until a complete light semantic theme is approved.
- Core canvas/surface colors: `#0D1410`, `#151E18`, and `#1C2820`.
- Primary action color: `#72E58C`, with `#07110A` content on primary controls.
- Primary text: `#F4F7F4`; secondary text: `#AAB6AE`; muted text: `#76837B`.
- Expiry status colors: healthy `#72E58C`, upcoming `#67B7FF`, due soon `#F4B860`, urgent `#FF8A65`, and overdue `#FF626D`.
- Typography: Onest for the interface and IBM Plex Mono selectively for dates, costs, identifiers, and timestamps. Self-host optimized font assets when they are added.
- Spacing: 4px base scale (`4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80px`).
- Mobile layout: below 640px, single-column, sticky top bar, bottom navigation, safe-area support, and 48px minimum touch targets.
- Tablet: 640–1023px. Desktop: 1024px and above with a 248–272px sidebar. Wide desktop begins at 1440px.
- Visual signature: service cards use a status-colored expiry rail plus explicit countdown text. Never communicate status by color alone.
- Accessibility target: WCAG 2.2 AA, full keyboard operation, visible focus, reduced-motion support, and resilient layout at 200% zoom.
- PWA manifest theme and background color: `#0D1410`.
- Use semantic design tokens; do not scatter arbitrary colors, spacing, or radii through templates.
- Full component, motion, responsive, and design-QA rules live in `docs/ui-design-system.md` and are mandatory for frontend work.

## 5. Users, ownership, and authorization

- A user is a developer/operator of ServiceNotify.
- A user owns many clients through `clients.user_id`.
- A client owns many services through `services.client_id`.
- A service owns many reminders and notification-log entries.
- Every client/service/reminder/notification query initiated by a signed-in user must be constrained through that user's ownership chain. Never trust a submitted record ID alone.
- Registration emails are unique.
- Store passwords with `password_hash()` and authenticate with `password_verify()`.
- Successful login redirects to the dashboard.
- Logout is CSRF-protected and POST-only. Sessions expire after 30 minutes of inactivity by default; `AUTH_SESSION_IDLE_SECONDS` may override the default.

## 6. MVP functional requirements

### Authentication

- Register, log in, and log out.
- Reject duplicate registration email addresses with a clear error.
- Reject incorrect credentials with a clear error.
- Maintain authenticated state with `$_SESSION`.
- Apply login rate limiting to reduce brute-force attempts.
- Authentication implementation decisions and security details are recorded in `docs/authentication.md`.

### Client management

- List only the authenticated user's clients.
- Add, edit, and delete clients.
- Fields: name, organization, phone, email.
- Validate email and phone formats before persistence.
- Deleting a client cascades to its services and their dependent reminder/notification records.
- Implemented validation: name is required and at most 150 characters; organization is optional and at most 150 characters; email is optional, lowercased, at most 150 characters, and validated with `FILTER_VALIDATE_EMAIL`; phone is optional, at most 20 stored characters, contains 7–15 digits, and may use a leading `+`, spaces, parentheses, dots, or hyphens. Blank optional values persist as `NULL`.
- The client list defaults to alphabetical order by name, then ID. Read/update/delete operations always constrain both client ID and authenticated user ID. Unowned IDs receive the same 404 response as missing IDs.
- Delete uses a dedicated confirmation page and a CSRF-protected POST. Service counts are not shown until Prompt 4 provides real service data.

### Service management

- List services belonging to the authenticated user's clients.
- Add, edit, and delete services.
- Fields: client, type, provider, expiry date, cost, currency, billing cycle, and payment account.
- Allowed service types: `domain`, `hosting`, `maintenance`.
- Allowed billing cycles: `monthly`, `yearly`, `custom`; schema default is `yearly`.
- Expiry dates use `YYYY-MM-DD` and must be valid dates.
- Cost, when supplied, must be numeric.
- Deleting a service cascades to reminders and notification logs.
- Implemented validation: the client must belong to the authenticated user; type and billing cycle use only the documented enum values; provider and payment account are optional and at most 150 characters; expiry is a strict real `YYYY-MM-DD` date; cost is optional, non-negative, limited to two decimal places and `99,999,999.99`, and normalized to two decimals. Currency is a separate nullable uppercase three-letter code, required when cost is present and cleared when cost is blank. Blank optional fields persist as `NULL`.
- Services default to nearest-expiry-first ordering, then client name and service ID. Every read/update/delete follows `services.client_id -> clients.user_id`; unowned and missing IDs share a 404 response. A service can be reassigned only to another client owned by the same user.
- Cost and currency are never converted or aggregated across currencies. Display always includes the code: `NGN` uses `₦`, `USD` uses `$`, known major codes may use their familiar symbol, and other codes use `CODE amount`. Legacy priced rows with no currency are preserved and labeled `Currency needed` until edited. `custom` is stored as the documented billing-cycle value without inventing an interval.
- Expiry presentation uses written labels plus status rails: past dates are overdue, today is a distinct neutral state, 1–7 days is urgent, 8–30 days is due soon, and more than 30 days is healthy. The server date drives presentation only and does not settle notification timing semantics.
- Delete uses a dedicated confirmation page and CSRF-protected POST. Prompt 5 now supplies reminder inputs in the same service form.

### Reminders

- Configure reminders while creating or editing a service.
- A service may have multiple reminder rows.
- `frequency` means integer days before the service expiry date.
- Documented examples/presets are 30, 7, and 1 days before expiry.
- Supported channels are `push` and `sms`, including both channels on one reminder.
- Cron evaluates due reminders daily and invokes the notification engine.
- Implemented MVP timing choices are exactly 30, 7, and 1 days before expiry. Each enabled preset is a separate reminder row and independently supports Push, SMS, or both; a service may have zero to three rows.
- Enabling a timing without at least one valid channel is rejected. The unique `(service_id, frequency)` index prevents duplicate timing rows.
- Service create/edit treats the submitted schedule as the complete desired set. Service fields and reminder replacement save in one transaction; invalid configuration prevents partial creation/update and deselected timing rows are removed.
- Reminder reads and writes follow `reminders.service_id -> services.client_id -> clients.user_id`. Configuration does not claim or attempt delivery; actual Push/SMS sending remains Prompt 7.

### Dashboard

- Default view shows expiries within the next 30 days.
- Display client name, service type, provider, and expiry date.
- Provide filters for service type and date/date range.
- Provide pagination for large result sets.
- Navigation/wireframes include Dashboard, Clients, Services, and Export, plus Add Client and Add Service actions.
- Implemented Prompt 6 defaults are an inclusive range from the server's current date through 30 days ahead, 10 results per page, and chronological sorting by expiry date with client name and service ID as stable tie-breakers.
- Dashboard filters accept `domain`, `hosting`, or `maintenance` plus an inclusive custom date range. Invalid or reversed values visibly fall back to the default window; out-of-range page numbers clamp to the last available page.
- Dashboard list and summary queries are ownership-scoped through `services.client_id -> clients.user_id`. Explicit custom ranges may include overdue services even though the default upcoming window does not.

### Notifications and operational logs

- Implement the daily entry point at `cron/notify.php`.
- For each due reminder, send the selected push and/or SMS channel.
- Record each attempt/result in the `notifications` table with service, channel type, status, message, and sent time.
- Also log notification activity, cron runs, errors, and retries to `logs/notifications.log`.
- A failed SMS is retried once and the failure/error is logged.
- An invalid FCM push token is removed from the database.
- The developer is the notification recipient. Top-level `sms_phone` and `fcm_tokens` values in `users.settings` store destinations. Prompt 10 provides explicit browser FID enrollment/revocation; SMS destination management and richer device metadata remain unresolved.
- Reminders fire only on their exact computed date, using `NOTIFICATION_TIMEZONE` (default `Africa/Lagos`); missed and expired reminders are not backfilled.
- Push is attempted once. A failed SMS is retried once. The unique reminder/channel/scheduled-date/attempt key makes repeated daily runs idempotent.
- Provide `logs.php` as an admin/operator view of database and file logs. Role-based access is Phase 2, so the exact meaning of “admin” is unresolved; do not expose cross-user logs.

### Reports

- Export CSV and Excel downloads with correct response headers.
- Filters: client, service type, and date range.
- Exported rows must respect the active filters and authenticated-user ownership.
- The approved column order is Client, Organization, Client Email, Client Phone, Service Type, Provider, Expiry Date, Cost, Currency, Billing Cycle, and Payment Account.
- CSV includes a UTF-8 BOM and neutralizes formula-like user text. XLSX stores user text explicitly, keeps cost numeric, and includes a report-context worksheet.
- Export filtering shares the dashboard filter parser. Invalid input is rejected, and an unowned client ID receives a 404 rather than producing a broader export.

### PWA

- `manifest.json` provides the application identity, standalone display, scope-relative routes, approved theme/background color, standard icons, and a maskable icon.
- `sw.js` precaches the public shell and provides network-first offline caching for authenticated dashboard and client GET navigations.
- Authenticated HTML uses a private cache keyed by a one-way session hash. Login/logout boundaries clear all private caches to reduce cross-account exposure on shared devices.
- Offline mutations are not queued: non-GET actions, authentication, reports, and exports require connectivity. An offline fallback and connection banner communicate that boundary.
- When Firebase web/VAPID configuration is present, users can explicitly enable or revoke Push for the current browser. The current FID destination is stored in the authenticated user's `settings.fcm_tokens`; Push permission is never requested on page load.

## 7. Canonical documented data model

The current documented schema uses database name `service_notify`.

### `users`

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `name VARCHAR(100) NOT NULL`
- `email VARCHAR(150) NOT NULL UNIQUE`
- `password VARCHAR(255) NOT NULL`
- `settings JSON DEFAULT NULL`
- `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`

### `clients`

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `user_id INT NOT NULL`, foreign key to `users.id`, `ON DELETE CASCADE`
- `name VARCHAR(150) NOT NULL`
- `organization VARCHAR(150) NULL`
- `phone VARCHAR(20) NULL`
- `email VARCHAR(150) NULL`
- `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
- Index: `idx_clients_user_id (user_id)`

### `services`

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `client_id INT NOT NULL`, foreign key to `clients.id`, `ON DELETE CASCADE`
- `type ENUM('domain','hosting','maintenance') NOT NULL`
- `provider VARCHAR(150) NULL`
- `expiry_date DATE NOT NULL`
- `cost DECIMAL(10,2) NULL`
- `currency CHAR(3) NULL` (uppercase three-letter code; application requires it when cost is present)
- `billing_cycle ENUM('monthly','yearly','custom') DEFAULT 'yearly'`
- `payment_account VARCHAR(150) NULL`
- `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
- Index: `idx_expiry_date (expiry_date)`

### `reminders`

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `service_id INT NOT NULL`, foreign key to `services.id`, `ON DELETE CASCADE`
- `frequency INT NOT NULL` (days before expiry)
- `channels SET('push','sms') NOT NULL`
- `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
- Index: `idx_service_reminder (service_id)`
- Unique index: `uq_service_reminder_frequency (service_id, frequency)`

### `notifications`

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `service_id INT NOT NULL`, foreign key to `services.id`, `ON DELETE CASCADE`
- `reminder_id INT NOT NULL`, foreign key to `reminders.id`, `ON DELETE CASCADE`
- `type ENUM('push','sms') NOT NULL`
- `status ENUM('success','failure') DEFAULT 'success'`
- `message TEXT NULL`
- `scheduled_for DATE NOT NULL`
- `attempt TINYINT UNSIGNED NOT NULL DEFAULT 1`
- `sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
- Index: `idx_notification_service (service_id)`
- Index: `idx_notification_due (scheduled_for, type, status)`
- Unique index: `uq_notification_attempt (reminder_id, type, scheduled_for, attempt)`

Do not rename, add, or reinterpret persisted fields without updating the schema document, this memory, and tests. Schema changes needed to satisfy unresolved requirements must be proposed explicitly.

## 8. Security invariants

- Use prepared statements for all database input; never concatenate untrusted values into SQL.
- Enforce server-side validation even when client-side validation exists.
- Escape output according to its context to prevent XSS. “Sanitize input” does not replace validation, prepared statements, or output escaping.
- Protect every state-changing form/request with a CSRF token.
- Authorize every data access by the signed-in user's ownership chain.
- Use secure session practices and inactivity timeout; regenerate the session ID on login.
- Enforce HTTPS in production and use secure cookie settings there.
- Keep database, Firebase, and Africa's Talking secrets in environment variables, never source control or rendered pages.
- Avoid logging secrets, passwords, full credentials, or unnecessary personal data.
- Exports and log views must not leak another user's data.

## 9. Performance, reliability, and scale targets

- Database query response target: under 200 ms for fewer than 1,000 records in the documented test environment.
- The system should support 1,000+ clients and services.
- Use the documented indexes and add justified indexes when query evidence requires them.
- The cron task must run daily without failure and record its runs/errors.
- Notification delivery success rate is a product success metric.
- Repeated cron runs are guarded by a per-date database advisory lock and the unique notification-attempt key.

## 10. Required test coverage and acceptance behaviors

Use unit tests for PHP functions/database queries, integration tests for FCM and Africa's Talking, end-to-end system tests for the client-to-notification flow, and acceptance/regression tests against the requirements.

Minimum documented acceptance cases:

- Registration succeeds with valid details and rejects duplicate email.
- Login succeeds with correct credentials, rejects a wrong password, and times out after inactivity.
- Client add/edit/delete works; invalid email is rejected; delete cascades.
- Service add/edit/delete works; invalid date is rejected; deleting cascades reminders.
- Reminder frequencies and multiple channels persist and trigger on the correct date.
- Daily cron finds expiring services.
- Successful push and SMS attempts are logged.
- Failed SMS is logged and retried once.
- Invalid push token is deleted.
- Upcoming expiries, type filters, and pagination return correct results.
- CSV and Excel exports contain correct, ownership-scoped data and honor filters.
- Password hashing and CSRF protection are verified.

External API tests must use safe test credentials/mocks where appropriate and must not send unintended real messages.

## 11. Known gaps: do not hallucinate these decisions

The documents do not currently define the following. Resolve them when their feature is reached and record the approved decision here and in the relevant source document:

- The precise daily cron execution time and production scheduler configuration. The evaluation timezone is configurable and defaults to `Africa/Lagos`.
- General retry/backoff rules beyond the approved single immediate SMS retry. Push has no automatic retry.
- Password reset/change workflow; the PRD says password management, but no flow/schema is specified.
- Login throttling uses a file-locked local store with namespaced hashed account/IP and IP-wide keys: 5 account/IP failures and 25 IP-wide failures per 15-minute rolling window by default. A shared atomic store remains required before horizontal scaling.
- Semantics of `custom` billing cycle; there is no custom interval field.
- Default reminder settings stored in `users.settings` and their precedence versus service reminders.
- The meaning/access rules of the `logs.php` “admin” view before Phase 2 roles exist.
- Broader PHP coding standards, namespace/autoload strategy for first-party classes, and a long-term test framework. Composer now manages the Prompt 8 PhpSpreadsheet dependency; first-party feature tests remain direct PHP scripts.
- Production deployment details and cron configuration syntax.

When one of these blocks implementation, present a focused choice with a recommended default. Do not encode a hidden assumption as fact.

## 12. Planned implementation sequence

Follow the feature order in `docs/build-prompts.md` unless the user changes it:

1. Project structure, environment loading, and PDO connection.
2. User authentication.
3. Client CRUD.
4. Service CRUD.
5. Reminder configuration.
6. Dashboard, filtering, and pagination.
7. Push/SMS notification engine and daily cron.
8. CSV/Excel reports.
9. File/database logging and log view.
10. PWA manifest, service worker, and installability.
11. Security hardening (security invariants apply from the start, not only at step 11).

## 13. Phase 2 / explicitly deferred work

- Email reminders.
- Role-based access.
- Calendar/timeline view.
- Advanced payment tracking.
- Third-party billing integrations.

Do not implement these as part of MVP unless the user explicitly reprioritizes them.

## 14. AI working protocol for every feature

Before work:

1. Read this entire file.
2. Read the relevant documents under `docs/`; read all of them again if the feature crosses boundaries.
3. Inspect the current code, schema, tests, and uncommitted changes. Treat code as implementation evidence, not permission to contradict approved requirements.
4. State the feature's scope, relevant requirements, acceptance cases, and any unresolved decisions.
5. Ask only for decisions that materially affect behavior and cannot safely be deferred.

During work:

- Implement only the requested phase/feature and necessary supporting changes.
- Preserve multi-user isolation in every query and action.
- Build security controls into the feature, rather than postponing them.
- Add or update tests alongside the implementation.
- Keep environment-specific values and secrets out of the repository.
- Do not claim an external integration works unless it was tested; distinguish mock/sandbox verification from real delivery.

After work:

1. Run the relevant automated checks and report exactly what passed, failed, or could not be run.
2. Verify the documented acceptance cases for the feature.
3. Update documentation and this memory when an approved decision, schema, convention, or scope changes.
4. Record remaining limitations without disguising them as completed work.

## 15. Document map

- `docs/prd.md`: product goals, users, MVP, roadmap, and success metrics.
- `docs/trd.md`: architecture, technical/functional/non-functional requirements, integrations, and deployment assumptions.
- `docs/test-plan.md`: test strategy, acceptance cases, performance/security/reliability targets, and environment.
- `docs/schema/schema.sql`: canonical documented tables, constraints, relationships, and indexes (currently Markdown-wrapped).
- `docs/er.md`: entity relationships and field-level ER diagram.
- `docs/dfd.md`: developer, modules, database, cron, notification providers, and logging data flow.
- `docs/user-flow.md`: login-to-dashboard, client/service setup, reminders, delivery, and export flow.
- `docs/low-fidelity-wireframe.md`: required screens, fields, navigation, actions, and export filters.
- `docs/ui-design-system.md`: approved colors, typography, spacing, responsive shell, components, motion, accessibility, and design QA rules.
- `docs/authentication.md`: implemented authentication flow, validation, sessions, CSRF, throttling, migration, UI, and acceptance coverage.
- `docs/client-management.md`: implemented client routes, ownership rules, validation, migration, UI behavior, and verification coverage.
- `docs/service-management.md`: implemented service routes, ownership chain, validation, expiry states, migration, UI behavior, and verification coverage.
- `docs/reminder-configuration.md`: implemented timing/channel rules, transactional reconciliation, ownership, storage, UI behavior, and verification coverage.
- `docs/dashboard.md`: implemented dashboard range/type filters, ownership-scoped query behavior, pagination defaults, responsive UI, and verification coverage.
- `docs/notifications.md`: implemented recipient settings, exact-date timing, provider configuration, retries, token cleanup, per-attempt logs, idempotency, cron operation, and verification coverage.
- `docs/reports.md`: implemented filters, ownership rules, approved columns, CSV/XLSX behavior, download security, dependency choice, and verification coverage.
- `docs/logging.md`: structured file events, redaction rules, user-scoped monitoring access, database/file presentation, and verification coverage.
- `docs/pwa.md`: installability metadata, icon generation, private/static cache strategies, offline boundaries, user feedback, and verification coverage.
- `docs/security.md`: request extraction, CSRF, HTTPS/proxy trust, browser/session headers, login throttling, and production operating requirements.
- `docs/build-prompts.md`: intended build sequence and feature-specific implementation prompts.
