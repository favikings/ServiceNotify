# Progressive Web App

Prompt 10 makes ServiceNotify installable and provides safe offline access to previously visited dashboard and client pages.

## Installability

- `public/manifest.json` uses a scope-relative ID, start URL, routes, and icon paths so root and subdirectory deployments share the same manifest.
- The installed app launches `dashboard.php` in standalone display mode and uses the approved `#0D1410` theme/background color.
- Standard 192px/512px, maskable 512px, and Apple touch icons extend the existing ServiceNotify renewal-pulse mark. `resources/icons/generate-icons.php` regenerates the PNG assets with PHP GD.
- Authenticated and authentication shells both link the manifest, icon metadata, and shared service-worker registration script.
- Browsers supporting `beforeinstallprompt` receive a dismissible, accessible in-app install suggestion. Native browser installation remains available when that event is not supported.
- Production installation and service workers require HTTPS. Browsers also permit local development on `localhost`.

## Browser Push enrollment

- When Firebase web configuration and a public VAPID key are present, the app shell exposes a “Push device” control. Permission is requested only after the user selects **Enable Push**.
- The browser uses Firebase's current FID-based `register()`/`onRegistered()` API with the existing ServiceNotify worker. The returned destination is sent to `push-subscription.php` with the session cookie and CSRF header.
- Enrollment and revocation update only the authenticated user's `settings.fcm_tokens` list, preserve SMS settings, deduplicate destinations, and cap an account at ten browser destinations.
- **Disable on this browser** unregisters the Firebase installation and removes that destination from the server. Invalid destinations returned during delivery continue to be removed by the notification engine.
- Foreground messages are surfaced through the active worker; background Push and notification clicks are handled by `sw.js` and return the user to the dashboard.
- The modular Firebase browser SDK is pinned to `13.0.0` from Google's official CDN. This avoids adding a new bundler to the approved plain-JavaScript toolchain; enrollment requires connectivity, while the core installed app and offline views do not depend on the CDN.

## Cache policy

`public/sw.js` separates public assets from authenticated HTML:

- A versioned static cache precaches the offline page, manifest, compiled CSS/JavaScript, and icons.
- Dashboard and client navigations use network-first delivery. A successful authenticated response is cached only in a private cache keyed by a one-way hash of the current user session.
- Offline dashboard/client requests may use only the currently active private cache. If a matching page was never visited, the worker returns `offline.html`.
- Authentication pages and logout submissions request deletion of all private caches. A server response without the authenticated cache header also clears them.
- Non-GET requests are never handled or queued by the service worker. Client/service mutations, authentication, reports, and exports require a network connection.
- Activation removes old static/state cache versions. Private caches from incompatible strategy versions are removed as well.

This policy deliberately favors account isolation and data integrity over offline editing. Background synchronization and offline mutation conflict resolution are not part of the MVP.

## User feedback

- A compact status banner announces loss of connectivity.
- The offline route explains which saved views may remain available and that changes require reconnection.
- The install suggestion follows the Evergreen Operations visual system and disappears after installation or dismissal for the current browser tab.

## Verification

`tests/pwa_test.php` validates manifest fields, PNG types/dimensions, maskable coverage, route handling, private-cache boundaries, mutation policy, offline fallback, Push/background handlers, registration, private-cache clearing, and install-prompt wiring. `tests/push_subscription_test.php` covers ownership-safe enrollment/revocation, deduplication, settings preservation, destination validation, consent wiring, authentication, and CSRF.
