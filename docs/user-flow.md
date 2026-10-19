# ServiceNotify User Flow Diagram

```mermaid
flowchart TD
    START[Start] --> LOGIN[Login/Register]
    LOGIN --> DASH[Dashboard]

    DASH --> ADDCLIENT[Add Client]
    DASH --> VIEWSERVICES[View Services]
    DASH --> EXPORT[Export Reports]

    ADDCLIENT --> CLIENTLIST[Client List]
    CLIENTLIST --> ADDSERVICE[Add Service]

    ADDSERVICE --> SERVICEDETAILS[Enter Service Details]
    SERVICEDETAILS --> REMINDERSET[Set Reminder Frequency & Channels]
    REMINDERSET --> DB[(Database)]

    %% Cron job and notifications
    DB --> CRON[Daily Cron Job]
    CRON --> CHECKEXPIRY[Check Upcoming Expiries]
    CHECKEXPIRY --> NOTIFY[Notification Engine]

    NOTIFY --> PUSH[Push Notification via FCM]
    NOTIFY --> SMS[SMS via Africa's Talking]

    PUSH --> USER[Developer Receives Push Reminder]
    SMS --> USER

    EXPORT --> FILES[CSV/Excel Export]
    FILES --> USER

    USER --> END[End]
