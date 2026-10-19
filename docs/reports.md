# Reports Export

Prompt 8 implements a dedicated authenticated report workspace at `reports.php` with CSV and Excel downloads served by `export.php`.

## Filters and ownership

- The Reports page owns the client, service type, start date, and end date controls; the dashboard remains focused on operational expiry activity.
- The default report window is today through 30 days ahead, matching the dashboard's default period without coupling the two pages.
- Desktop sidebar and mobile bottom navigation link directly to Reports and expose its active-page state.
- Client IDs are always combined with `clients.user_id = authenticated user`; an unowned client returns 404 from the download endpoint and never yields another user's data.
- Invalid filter shapes or values return 422 rather than silently creating a broader report.
- Rows are ordered by expiry date, client name, and service ID.
- Empty result sets still download a valid file containing the approved headers.

## Columns

Both formats use this approved order:

1. Client
2. Organization
3. Client Email
4. Client Phone
5. Service Type
6. Provider
7. Expiry Date
8. Cost
9. Currency
10. Billing Cycle
11. Payment Account

Cost and currency remain separate. No conversion or cross-currency aggregation is performed.

## File behavior

- CSV responses use `text/csv; charset=UTF-8`, include a UTF-8 BOM for spreadsheet compatibility, and use standard comma-delimited quoting.
- User-controlled CSV text beginning with `=`, `+`, `-`, or `@` is prefixed with an apostrophe to prevent spreadsheet formula injection.
- Excel responses use the Open XML MIME type and are generated with PhpSpreadsheet 2.4.8, the maintained security branch compatible with the project's PHP 8.1 floor.
- Excel user text is explicitly stored as string cells, costs are numeric cells, the header row is frozen and filterable, and a second sheet records the active report context.
- Downloads use private no-store cache headers and filenames containing the selected date range.

## Verification

`tests/report_test.php` covers all combined filters, cross-user isolation, approved column order, CSV headers/BOM/formula neutralization, numeric Excel costs, explicit string cells, and Excel report context. Generated files are temporary test artifacts and are removed after inspection.
