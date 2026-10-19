# ServiceNotify Data Flow Diagram (DFD)

```mermaid
flowchart TD
    %% User actions
    U[Developer User] -->|Add/Edit| C[Clients Module]
    U -->|Add/Edit| S[Services Module]
    U -->|Configure| R[Reminders Module]
    U -->|View| D[Dashboard]

    %% Database interactions
    C --> DB[(MySQL Database)]
    S --> DB
    R --> DB
    D --> DB

    %% Cron job process
    CRON[Daily Cron Job] --> DB
    CRON -->|Query upcoming expiries| N[Notification Engine]

    %% Notification engine
    N -->|Push| FCM[Firebase Cloud Messaging]
    N -->|SMS| AT[Africa's Talking API]
    N --> LOG[Notifications Log]

    %% Delivery
    FCM --> U
    AT --> U
