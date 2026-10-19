# ServiceNotify Security Hardening

This document records the implemented Prompt 11 controls and their production operating requirements.

## Request input and output safety

- Web controllers extract query, form, and server values through `RequestInput`, which uses `filter_input()` with scalar or array requirements.
- Domain services remain responsible for allowlists, formats, lengths, ownership, and business validation. Request filtering is an input boundary, not a replacement for validation.
- Database repositories use prepared statements. Rendered values use context-appropriate HTML escaping through `escape()`.
- JSON request bodies are decoded with `JSON_THROW_ON_ERROR`, depth-limited, type-checked, and passed to domain validation because PHP does not expose JSON fields through `filter_input()`.

## CSRF protection

- Every state-changing HTML form includes the session's cryptographically random hidden `_token` value.
- Controllers reject missing or invalid tokens before mutation and return HTTP 419.
- The Push subscription JSON endpoint carries the same token in `X-CSRF-Token`.
- Tokens rotate whenever authentication state changes. Read-only GET filters do not require tokens.

## HTTPS and proxy trust

When `APP_ENV=production`, bootstrap requires `APP_URL` to use `https://`. An HTTP request receives a permanent-method-preserving `308` redirect to the configured canonical origin. The request's `Host` header is never used to construct that redirect.

Set `TRUSTED_PROXY_IPS` to a comma-separated list of the exact reverse-proxy IP addresses only when TLS terminates upstream. `X-Forwarded-Proto` and `X-Forwarded-For` are ignored from all other senders. Configure the proxy to replace, rather than append to, untrusted incoming forwarding headers.

Production HTTPS responses send HSTS. All web responses also receive a restrictive content-security policy, clickjacking denial, MIME-sniffing prevention, a same-origin referrer policy, browser capability restrictions, opener isolation, and private no-store caching.

## Sessions

- Cookies are HTTP-only, SameSite=Lax, path-scoped to the application, and Secure in production or whenever HTTPS is detected.
- PHP strict session mode and cookie-only sessions are enabled.
- Session IDs regenerate on login and logout, and authenticated sessions expire after the configured inactivity window.

## Login rate limiting

Each failed login records two hashed keys without storing the raw email or IP address:

- normalized account plus client IP: 5 attempts by default;
- client IP across all account names: 25 attempts by default.

Both use the default 900-second rolling window. Configure the values with `AUTH_MAX_LOGIN_ATTEMPTS`, `AUTH_MAX_IP_LOGIN_ATTEMPTS`, and `AUTH_LOGIN_WINDOW_SECONDS`. A blocked request returns HTTP 429 and `Retry-After`. Successful authentication clears its account/IP key; the IP-wide history remains for password-spray protection.

The file-locked `logs/login_attempts.json` store is private to the host and prunes expired entries. It is suitable for the current single-server deployment. Multi-server deployment requires a shared atomic limiter such as Redis or a database-backed equivalent before scaling out.

## Verification

Run `php tests/security_test.php`. It checks CSRF form coverage, centralized request extraction, trusted-proxy behavior, canonical HTTPS redirects, spoof-resistant client IP selection, and both limiter namespaces.
