# ServiceNotify Coding Prompts

This document contains coding prompts for each feature of ServiceNotify. Use them one by one to implement the app from start to finish.

---

## 1. Project Setup
**Prompt:**  
Create a PHP project structure for *ServiceNotify* with folders: `/config`, `/public`, `/src`, `/views`, `/cron`, `/logs`. Add a `config.php` file for database connection using PDO. Ensure environment variables are loaded from `.env`.

---

## 2. User Authentication
**Prompt:**  
Implement user registration and login in PHP. Use `password_hash()` for storing passwords and `password_verify()` for login. Store users in the `users` table. Add session-based authentication with `$_SESSION`. Redirect logged-in users to the dashboard.

---

## 3. Client Management
**Prompt:**  
Build CRUD operations for clients. Create a `clients.php` page to list all clients for the logged-in user. Add forms for adding, editing, and deleting clients. Validate email and phone formats before saving. Link clients to the authenticated user via `user_id`.

---

## 4. Service Management
**Prompt:**  
Implement CRUD operations for services linked to clients. Create a `services.php` page showing all services per client. Add a form with fields: type, provider, expiry_date, cost, currency, billing_cycle, payment_account. Validate expiry_date format (`YYYY-MM-DD`), numeric cost, and the three-letter currency code required with a cost.

---

## 5. Reminder Configuration
**Prompt:**  
Add a reminder configuration form when creating/editing a service. Allow selecting frequency (30, 7, 1 days before expiry) and channels (push, SMS). Save reminders in the `reminders` table linked to the service.

---

## 6. Dashboard
**Prompt:**  
Build a dashboard page showing upcoming expiries within the next 30 days. Query services by expiry_date. Display client name, service type, provider, and expiry date. Add filters for service type and date range. Implement pagination for large datasets.

---

## 7. Notifications (Push + SMS)
**Prompt:**  
Create a notification engine in PHP. Write a cron job (`cron/notify.php`) that runs daily. Query services nearing expiry based on reminder frequency. Send push notifications via Firebase Cloud Messaging (FCM) and SMS via Africa’s Talking API. Log results in the `notifications` table.

---

## 8. Reports Export
**Prompt:**  
Implement CSV and Excel export using PhpSpreadsheet. Add export buttons on the dashboard. Allow filtering by client, service type, and date range before export. Generate downloadable files with proper headers.

---

## 9. Logging & Monitoring
**Prompt:**  
Add logging for all notifications in `/logs/notifications.log`. Log cron job runs, errors, and retries. Create an admin view (`logs.php`) to display logs from the database and file.

---

## 10. PWA Compliance
**Prompt:**  
Add a `manifest.json` file with app name, icons, and theme color. Implement a service worker (`sw.js`) for offline caching of dashboard and client pages. Ensure the app is installable as a PWA.

---

## 11. Security Enhancements
**Prompt:**  
Implement CSRF protection for all forms using hidden tokens. Sanitize all user inputs with `filter_input()`. Enforce HTTPS in production. Add rate limiting for login attempts to prevent brute force attacks.
