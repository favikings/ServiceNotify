# Dashboard

Prompt 6 implements the authenticated renewal dashboard at `dashboard.php`.

## Query behavior

- The default window is inclusive from the server's current date through 30 days ahead.
- Users can filter by one owned client, by `domain`, `hosting`, or `maintenance`, and choose an inclusive start/end date range.
- Invalid types, impossible dates, reversed ranges, and invalid page numbers fall back to safe defaults. Visible validation feedback is shown for invalid filter values.
- Results are ordered by expiry date, client name, and service ID for stable pagination.
- The page size is 10. A requested page above the last available page is clamped to the final page.
- Every count and list query joins `services -> clients` and requires `clients.user_id` to match the authenticated user.
- Filter parameters are bound values. Pagination limits and offsets are bound as integers.

## Interface behavior

- The nearest result leads the dashboard with its written countdown and exact date.
- Summary cards show matching expiries, deadlines in the next seven days, and all tracked services.
- The expiry queue shows client, service type, provider, expiry status/countdown, exact expiry date, and a review action.
- Status is communicated with words and dates in addition to the expiry rail color.
- Mobile uses touch-sized stacked controls and cards; desktop converts the queue into aligned structured rows.
- Empty states distinguish a new workspace from a filter window with no matches.
- Reporting controls live on the dedicated authenticated `reports.php` page so exports do not add work or visual activity to the dashboard.

## Verification

`tests/dashboard_test.php` covers the default window, ownership isolation, service type/date filters, inclusive boundaries, chronological ordering, input fallback behavior, and the 10-row page boundary.
