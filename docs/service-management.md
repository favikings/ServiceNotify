# Service Management Implementation

Prompt 4 implements authenticated service CRUD through clean application URLs:

- `/services.php` lists services belonging to the signed-in user's clients.
- `/service-create.php` creates a service, with optional `?client_id={id}` preselection.
- `/service-edit.php?id={id}` edits an owned service.
- `/service-delete.php?id={id}` confirms deletion and deletes via POST.

Every service read, update, and delete traverses the ownership chain through `clients.user_id`. Client selections are also validated against the authenticated user, so a submitted foreign `client_id` cannot reassign or create a service outside the account. Missing and unowned service IDs return the same 404 response. State changes require CSRF tokens and all persistence uses prepared PDO statements.

## Data and validation

Migration `database/migrations/003_create_services.sql` creates the base services table, expiry-date index, and cascading foreign key to `clients.id`. Migration `004_add_service_currency.sql` adds per-service currency without rewriting existing amounts. The migration runner records applied filenames in `schema_migrations`, so altering migrations execute only once.

- `client_id`: required and must identify one of the authenticated user's clients.
- `type`: required; exactly `domain`, `hosting`, or `maintenance`.
- `provider`: optional, trimmed, maximum 150 characters; blank becomes `NULL`.
- `expiry_date`: required, strict real calendar date in `YYYY-MM-DD` format. Past dates remain valid because existing overdue services must be recordable.
- `cost`: optional, non-negative decimal, maximum two decimal places and `99,999,999.99`; normalized to two decimal places.
- `currency`: nullable three-letter uppercase code stored separately from cost. It is required whenever cost is present and cleared when cost is blank. The form suggests common codes such as `NGN`, `USD`, `EUR`, and `GBP` without restricting other three-letter codes.
- `billing_cycle`: required; exactly `monthly`, `yearly`, or `custom`. Custom interval semantics remain intentionally undefined because the schema contains no interval field.
- `payment_account`: optional, trimmed, maximum 150 characters; blank becomes `NULL`.

Services default to nearest-expiry-first ordering, followed by client name and service ID for deterministic results. Editing may move a service between clients owned by the same user.

Money remains stored as an amount and code; ServiceNotify does not convert exchange rates or combine currencies. Presentation always keeps the code visible: Naira displays as `₦45,000.00 NGN`, US dollars as `$120.00 USD`, known major currencies receive their familiar symbol plus code, and all other currencies display as `CODE 120.00`. Legacy priced rows with no currency display `Currency needed` until edited; their amount is never guessed or changed.

## Expiry presentation

The Services interface uses the approved expiry rail plus explicit written status and exact date:

- Past date: `Overdue by N days`.
- Current server date: distinct `Expires today` state. This does not decide Prompt 7 notification semantics.
- 1–7 days: `Urgent` and `N days left`.
- 8–30 days: `Due soon` and `N days left`.
- More than 30 days: `Healthy` and `N days left`.

The runtime's server date is used only for this presentation until the product notification timezone is approved. Prompt 5 now integrates the documented reminder fields into the service form.

## Interface behavior

The mobile-first view uses touch-friendly renewal cards; tablet and desktop use a controlled two-column grid. The form is divided into service details, billing context, and reminder schedule; it keeps persistent labels and provides accessible field/form errors. If the account has no clients, the service form routes the user to create one. Client rows show real service counts, service cards show reminder counts, navigation enables Services, and dashboard client/service metrics are live.

## Verification

`tests/service_test.php` uses SQLite in memory to cover normalization, nullable fields, invalid/impossible dates, invalid costs and currencies, multi-currency formatting, enum and client ownership validation, expiry ordering, user isolation, update and owned-client reassignment, deletion, reminder cascade behavior, counts, and deterministic expiry states.

The live XAMPP Apache/MariaDB flow was verified through `http://localhost/ServiceNotify/`: create client, reject invalid service date, create/list/edit service, cross-user 404 concealment, delete, and cleanup all passed without exposing `/public`.
