# ServiceNotify ER Diagram

```mermaid
erDiagram
    USERS ||--o{ CLIENTS : "has"
    CLIENTS ||--o{ SERVICES : "owns"
    SERVICES ||--o{ REMINDERS : "has"
    SERVICES ||--o{ NOTIFICATIONS : "generates"
    REMINDERS ||--o{ NOTIFICATIONS : "records attempts"

    USERS {
        int id PK
        varchar name
        varchar email
        varchar password
        json settings
        timestamp created_at
    }

    CLIENTS {
        int id PK
        int user_id FK
        varchar name
        varchar organization
        varchar phone
        varchar email
        timestamp created_at
    }

    SERVICES {
        int id PK
        int client_id FK
        enum type
        varchar provider
        date expiry_date
        decimal cost
        char currency
        enum billing_cycle
        varchar payment_account
        timestamp created_at
    }

    REMINDERS {
        int id PK
        int service_id FK
        int frequency
        set channels
        timestamp created_at
    }

    NOTIFICATIONS {
        int id PK
        int service_id FK
        int reminder_id FK
        enum type
        enum status
        text message
        date scheduled_for
        tinyint attempt
        timestamp sent_at
    }
