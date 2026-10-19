# Product Requirements Document (PRD) - ServiceNotify

## 1. Overview
**ServiceNotify** is a Progressive Web App (PWA) built with **plain PHP + MySQL** for developers to track their clients’ hosting, domain, and maintenance expiry dates. The app will send automated reminders via **Push Notifications (Firebase Cloud Messaging)** and **SMS (Africa’s Talking)**. Clients do not have access; the app is strictly developer-facing.

---

## 2. Goals
- Enable developers to manage multiple clients and their services.
- Provide automated reminders before expiry dates.
- Support push notifications and SMS (email in v2).
- Offer a simple dashboard with export functionality.
- Multi-user support with secure authentication.

---

## 3. Target Users
- **Primary**: Freelance developers, small agencies, IT professionals.
- **Secondary**: Teams of developers (multi-user accounts).

---

## 4. Core Features
- **User Accounts**: Registration, login, password management.
- **Client Management**: Add/edit clients with contact details.
- **Service Management**: Track multiple services per client (domain, hosting, maintenance).
- **Reminders**: Configurable frequency (e.g., 30 days, 7 days, 1 day before expiry).
- **Notifications**: Push (via FCM) and SMS (via Africa’s Talking).
- **Dashboard**: Upcoming expiries, client/service list.
- **Reports**: Export to CSV/Excel.

---

## 5. Non-Functional Requirements
- **Performance**: Fast load times, optimized queries.
- **Security**: Session-based auth, password hashing, CSRF protection.
- **Scalability**: Support multiple developers and clients.
- **Reliability**: Cron jobs for scheduled reminders.
- **PWA Compliance**: Offline caching, installable app.

---

## 6. Tech Stack
- **Backend**: Plain PHP (PDO/MySQLi).
- **Database**: MySQL.
- **Frontend**: Tailwind CSS + Vanilla JS.
- **Push Notifications**: Firebase Cloud Messaging.
- **SMS**: Africa’s Talking API.
- **Reports**: CSV/Excel export via PhpSpreadsheet.

---

## 7. Database Schema
**Users**
- id, name, email, password, settings

**Clients**
- id, user_id, name, organization, phone, email

**Services**
- id, client_id, type, provider, expiry_date, cost, currency, billing_cycle, payment_account

**Reminders**
- id, service_id, frequency, channels

---

## 8. Notification Flow
1. Cron job runs daily.
2. Query services nearing expiry.
3. Trigger push via FCM.
4. Trigger SMS via Africa’s Talking.
5. Log notifications.

---

## 9. User Experience
- **Dashboard**: List of clients/services, upcoming expiries.
- **Forms**: Add/edit clients and services.
- **Reminder Settings**: Per service or default.
- **Export**: CSV/Excel.

---

## 10. Roadmap
**Phase 1 (MVP)**
- User auth
- Client + service management
- Dashboard
- Push + SMS reminders
- Export reports

**Phase 2 (Enhancements)**
- Email reminders
- Role-based access
- Calendar/timeline view
- Advanced payment tracking

---

## 11. Risks & Mitigation
- **Push complexity**: Use Firebase Cloud Messaging for reliability.
- **SMS costs**: Monitor Africa’s Talking usage.
- **Security risks**: Enforce HTTPS, sanitize inputs.

---

## 12. Success Metrics
- Number of active developer accounts.
- Number of clients/services tracked.
- Reminder delivery success rate.
- User satisfaction with dashboard and exports.
