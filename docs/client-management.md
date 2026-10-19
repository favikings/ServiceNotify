# Client Management Implementation

Prompt 3 implements authenticated client CRUD at clean application URLs:

- `/clients.php` lists the signed-in user's clients alphabetically.
- `/client-create.php` creates a client.
- `/client-edit.php?id={id}` edits an owned client.
- `/client-delete.php?id={id}` shows a confirmation and deletes via POST.

All controllers require an authenticated session. Repository reads, updates, and deletes constrain both the record `id` and authenticated `user_id`; an absent or unowned record returns the same 404 response to avoid exposing whether another user's record exists. State-changing requests require a valid CSRF token and use prepared PDO statements.

## Data and validation

Migration `database/migrations/002_create_clients.sql` creates the documented `clients` table, the `idx_clients_user_id` index, and the cascading foreign key to `users.id`.

- `name`: required, trimmed, maximum 150 characters.
- `organization`: optional, trimmed, maximum 150 characters; blank becomes `NULL`.
- `email`: optional, trimmed and lowercased, maximum 150 characters, validated with `FILTER_VALIDATE_EMAIL`; blank becomes `NULL`.
- `phone`: optional, trimmed, maximum 20 stored characters, 7–15 digits. A leading `+`, spaces, parentheses, dots, and hyphens are accepted; blank becomes `NULL`.

Client deletion relies on database foreign-key cascades. The Prompt 4 services foreign key now ensures deleting a client removes its services; future reminder and notification foreign keys will extend that cascade through the documented ownership chain.

## Interface behavior

The client experience follows the Evergreen Operations design system. It provides a mobile-first card/list view, persistent labels, accessible validation states, minimum touch targets, an explicit destructive-action confirmation, mobile bottom navigation, and a denser desktop table within the shared sidebar shell. Client rows now display real service counts.

## Verification

`tests/client_test.php` uses an in-memory SQLite database to cover creation, normalization, invalid email and phone rejection, alphabetical ordering, nullable optional values, update, ownership isolation, deletion, and dependent-service cascade behavior.

The live XAMPP flow was also verified through `http://localhost/ServiceNotify/`: registration, invalid input, create, list, edit, cross-user 404 concealment, delete, and cleanup all passed without exposing `/public` in the URL.
