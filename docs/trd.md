# Technical Requirements Document (TRD) - ServiceNotify

## 1. Introduction
ServiceNotify is a Progressive Web App (PWA) designed for developers to track client hosting, domain, and maintenance expiry dates. It provides automated reminders via push notifications (Firebase Cloud Messaging) and SMS (Africa’s Talking). This document outlines the technical requirements necessary to build and maintain the system.

---

## 2. System Architecture
- **Frontend**: PWA built with HTML5, Tailwind CSS, and Vanilla JavaScript.
- **Backend**: Plain PHP using PDO/MySQLi for database interactions.
- **Database**: MySQL relational database.
- **Notifications**:
  - Push: Firebase Cloud Messaging (FCM).
  - SMS: Africa’s Talking API.
- **Reports**: CSV/Excel export using PhpSpreadsheet.
- **Hosting**: Any LAMP stack server with HTTPS enabled.

---

## 3. Functional Requirements
### User Management
- Register, login, logout.
- Password hashing with `password_hash()`.
- Session-based authentication.

### Client Management
- Add/edit/delete clients.
- Store client details: name, organization, phone, email.

### Service Management
- Add/edit/delete services linked to clients.
- Service attributes: type (domain/hosting/maintenance), provider, expiry_date, cost, three-letter currency code, billing_cycle, payment_account.

### Reminders
- Configurable reminder frequency (e.g., 30, 7, 1 days before expiry).
- Channels: push notifications, SMS.

### Notifications
- Daily cron job checks upcoming expiries.
- Push notifications sent via FCM.
- SMS sent via Africa’s Talking.
- Log notifications in database.

### Dashboard
- Display list of clients and services.
- Show upcoming expiries.
- Filter by service type or date.

### Reports
- Export client/service data to CSV.
- Export to Excel using PhpSpreadsheet.

---

## 4. Non-Functional Requirements
- **Performance**: Queries optimized with indexes.
- **Security**: CSRF protection, input validation, HTTPS enforced.
- **Scalability**: Support multiple developer accounts.
- **Reliability**: Cron jobs scheduled daily.
- **PWA Compliance**: Manifest.json, service worker for offline caching.

---

## 5. Database Schema
**Users**
- id (PK), name, email, password, settings

**Clients**
- id (PK), user_id (FK), name, organization, phone, email

**Services**
- id (PK), client_id (FK), type, provider, expiry_date, cost, currency, billing_cycle, payment_account

**Reminders**
- id (PK), service_id (FK), frequency, channels

**Notifications**
- id (PK), service_id (FK), type (push/sms), status, timestamp

---

## 6. API Integrations
- **Firebase Cloud Messaging (FCM)**: REST API for push notifications.
- **Africa’s Talking SMS API**: PHP cURL integration for SMS delivery.

---

## 7. Cron Jobs
- Daily script to:
  - Query services nearing expiry.
  - Trigger push and SMS notifications.
  - Log results.

---

## 8. User Interface Requirements
- Responsive dashboard built with Tailwind CSS.
- Forms for adding/editing clients and services.
- Export buttons for CSV/Excel.
- Notification settings per service.

---

## 9. Risks & Mitigation
- **Push complexity**: Use FCM for reliability.
- **SMS costs**: Monitor Africa’s Talking usage.
- **Security risks**: Enforce HTTPS, sanitize inputs, validate forms.

---

## 10. Success Criteria
- Accurate tracking of expiry dates.
- Reliable delivery of push and SMS reminders.
- User satisfaction with dashboard and exports.
- Scalability to support multiple developer accounts.
