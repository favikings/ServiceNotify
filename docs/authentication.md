# ServiceNotify Authentication

This document records the approved implementation decisions for Prompt 2. It supplements the PRD, TRD, test plan, and project memory.

## User flow

1. A visitor is sent to `/login.php`; Apache serves the physical `public/login.php` file internally.
2. A new developer registers with name, email, password, and password confirmation.
3. The server validates the fields, normalizes the email to lowercase, hashes the password with `PASSWORD_DEFAULT`, and inserts the user using a prepared statement.
4. Successful registration signs the new user in and redirects to `/dashboard.php`.
5. Existing users authenticate with email and password and are redirected to the dashboard.
6. Dashboard access requires a valid authenticated session.
7. Logout is a CSRF-protected POST request to `/logout.php` and returns the user to login.

The public URL must never contain `/public`. Root rewrite rules redirect explicit `/public` requests and prevent web access to non-public project directories.

## Validation decisions

- Name is required and limited to 100 characters.
- Email is required, must pass server-side email validation, is normalized to lowercase, and is limited to 150 characters.
- Email remains unique through the database constraint. Duplicate attempts receive a field-level error.
- Passwords require at least 8 characters and must match confirmation during registration.
- Authentication errors use a generic email/password message to avoid disclosing registered addresses.
- Password reset/change remains unresolved and is not part of Prompt 2.

## Session security

- PHP sessions store only user ID, name, and email as the authenticated identity.
- The session ID is regenerated on login and logout.
- Session cookies are HTTP-only and SameSite=Lax. They are marked Secure in production or when PHP detects HTTPS.
- Strict session mode and cookie-only session transport are enabled.
- Authenticated sessions expire after 1,800 seconds (30 minutes) of inactivity by default.
- The inactivity timeout is configurable with `AUTH_SESSION_IDLE_SECONDS`.
- Expired sessions remove authenticated identity and show a notice on the login screen.

## CSRF protection

- Registration, login, logout, client/service mutations, and Push subscription changes use a cryptographically random session token.
- State-changing requests are rejected with HTTP 419 when the token is missing or invalid.
- The token rotates when authentication state changes.

## Login throttling

- Default thresholds: 5 failures per normalized account/IP pair and 25 failures per IP across all account names in a rolling 900-second (15-minute) window.
- Thresholds and window are configurable through `AUTH_MAX_LOGIN_ATTEMPTS`, `AUTH_MAX_IP_LOGIN_ATTEMPTS`, and `AUTH_LOGIN_WINDOW_SECONDS`.
- Limiter keys are namespaced hashes. Raw emails, IP addresses, and passwords are not stored.
- The runtime store is `logs/login_attempts.json`, outside the public directory, with file locking for concurrent requests.
- A blocked request returns HTTP 429 with `Retry-After`. Successful authentication clears the matching account/IP key while preserving IP-wide failure history against password spraying.
- This local-file implementation is appropriate for the single-server MVP. A shared atomic store is required before horizontally scaled deployment.

## Database migration

`database/migrations/001_create_users.sql` is the executable MySQL users-table migration and matches the canonical documented users schema. Run all current idempotent migrations with:

```bash
php database/migrate.php
```

The live local smoke test currently runs on the bundled XAMPP MariaDB 10.4.32 server. Production remains targeted at MySQL 8+, so local success must not be represented as MySQL 8 integration coverage.

## UI and accessibility

- Login, registration, and dashboard shell use the approved Evergreen Operations design system.
- Forms have persistent labels, native autocomplete hints, accessible errors, minimum touch targets, visible focus, and password-visibility controls.
- The protected application shell exposes the implemented dashboard, clients, services, reports, logs, and Push controls.
- Tailwind compiles to a local static stylesheet. The content-security policy permits only the Firebase browser SDK origins required by Prompt 10 in addition to same-origin application assets.

## Acceptance coverage

`tests/auth_test.php` verifies registration, email normalization, password hashing, valid/invalid login, duplicate rejection, validation, CSRF tokens, inactivity expiry, and login throttling. `tests/security_test.php` verifies request extraction, full POST-form CSRF coverage, HTTPS/proxy handling, canonical redirects, and limiter namespaces.
