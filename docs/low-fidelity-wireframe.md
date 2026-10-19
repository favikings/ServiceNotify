# ServiceNotify Wireframes (Low-Fidelity)

## Dashboard
+---------------------------------------------------+
| ServiceNotify                                      |
|---------------------------------------------------|
| [Navigation: Dashboard | Clients | Services | Export] |
|                                                   |
| Upcoming Expiries                                  |
| ------------------------------------------------- |
| Client: ABC Ltd.   | Service: Domain   | Exp: 2026-11-01 |
| Client: XYZ Corp.  | Service: Hosting  | Exp: 2026-11-15 |
| ...                                                   |
|                                                   |
| [Add Client] [Add Service]                         |
+---------------------------------------------------+

---

## Client Management
+---------------------------------------------------+
| Clients                                           |
|---------------------------------------------------|
| [Add Client]                                      |
|                                                   |
| List of Clients                                   |
| ------------------------------------------------- |
| Name: ABC Ltd.   | Contact: abc@example.com        |
| Name: XYZ Corp.  | Contact: xyz@example.com        |
| ...                                                 |
|                                                   |
| [Edit] [Delete]                                   |
+---------------------------------------------------+

---

## Service Management
+---------------------------------------------------+
| Services                                          |
|---------------------------------------------------|
| [Add Service]                                     |
|                                                   |
| Service List                                      |
| ------------------------------------------------- |
| Type: Domain   | Provider: GoDaddy | Exp: 2026-11-01 |
| Type: Hosting  | Provider: AWS     | Exp: 2026-11-15 |
| ...                                                 |
|                                                   |
| [Edit] [Delete]                                   |
+---------------------------------------------------+

---

## Add/Edit Service Form
+---------------------------------------------------+
| Add Service                                       |
|---------------------------------------------------|
| Client: [Dropdown Select]                         |
| Type: [Domain | Hosting | Maintenance]            |
| Provider: [Text Field]                            |
| Expiry Date: [Date Picker]                        |
| Cost: [Numeric Field]                             |
| Currency: [3-letter code, e.g. NGN or USD]        |
| Billing Cycle: [Monthly | Yearly | Custom]        |
| Payment Account: [Text Field]                     |
| Reminder Frequency: [30 | 7 | 1 days]             |
| Channels: [Push] [SMS]                            |
|                                                   |
| [Save] [Cancel]                                   |
+---------------------------------------------------+

---

## Reports Export
+---------------------------------------------------+
| Reports                                           |
|---------------------------------------------------|
| Export Options                                    |
| ------------------------------------------------- |
| [Export CSV] [Export Excel]                       |
|                                                   |
| Filters:                                          |
| - By Client                                       |
| - By Service Type                                 |
| - By Date Range                                   |
+---------------------------------------------------+
