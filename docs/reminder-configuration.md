# Reminder Configuration Implementation

Prompt 5 integrates reminder preferences into the existing Add/Edit Service form. Reminder configuration has no separate public route; it is saved with the service through `/service-create.php` and `/service-edit.php?id={id}`.

## Approved reminder model

- MVP timing choices are the exact documented presets: 30, 7, and 1 days before expiry.
- Each enabled preset becomes one `reminders` row, so one service can have up to three reminder rows.
- Every preset independently selects `push`, `sms`, or both channels.
- A service may intentionally have no reminders. Enabling a preset without a channel is rejected.
- The unique `(service_id, frequency)` index prevents duplicate timing rows for one service.
- Editing treats the submitted reminder configuration as the complete desired set: selected rows are inserted/updated through replacement and deselected rows are removed.
- Service data and its complete reminder set save in one database transaction. Invalid reminder input prevents creation or preserves the previous service/reminder state during edits.

Actual Push and SMS delivery does not occur in Prompt 5. Configuration is persisted now; the notification engine and credentials arrive in Prompt 7.

## Ownership and security

Reminder queries join `reminders -> services -> clients` and constrain `clients.user_id` to the authenticated user. Replacement deletes and inserts through the same ownership chain. Service forms remain CSRF-protected, and all reminder statements use prepared PDO queries.

## Storage

Migration `database/migrations/005_create_reminders.sql` creates:

- `id INT AUTO_INCREMENT PRIMARY KEY`
- `service_id INT NOT NULL`, cascading foreign key to `services.id`
- `frequency INT NOT NULL`
- `channels SET('push','sms') NOT NULL`
- `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`
- `idx_service_reminder (service_id)`
- `uq_service_reminder_frequency (service_id, frequency)`

Deleting a service removes all reminder rows through the foreign-key cascade.

## Interface

The Reminder schedule section follows the Evergreen Operations mobile-first system. Each preset appears as an independently enabled timing card with a large day value, descriptive timing language, a native-feeling switch, and 48px Push/SMS targets. Disabled schedules visually mute their channels. The Services list displays the number of active reminder rows without claiming that delivery is already active.

## Verification

`tests/reminder_test.php` covers preset persistence, independent and combined channels, ownership isolation, reminder counts, full-set edit reconciliation, invalid configuration rollback, optional empty schedules, creation atomicity, and service-delete cascade.

The live XAMPP Apache/MariaDB flow also verified create with multiple reminders, Push+SMS storage, edit replacement, invalid-edit atomicity, cascade deletion, clean URLs, and test-data cleanup.
