# ServiceNotify Test Plan

## 1. Introduction
This Test Plan defines the strategy, scope, and test cases for validating the functionality of ServiceNotify. The goal is to ensure that all modules (User Management, Client Management, Service Management, Reminders, Notifications, Dashboard, Reports) work as expected and meet the requirements defined in the TRD.

---

## 2. Scope
- **In Scope**: Functional testing, non-functional testing (performance, security), integration testing (FCM, Africa’s Talking), regression testing.
- **Out of Scope**: UI design polish, third-party billing integrations.

---

## 3. Test Strategy
- **Unit Testing**: Validate individual PHP functions and database queries.
- **Integration Testing**: Validate API calls to FCM and Africa’s Talking.
- **System Testing**: Validate end-to-end workflows (add client → add service → reminder → notification).
- **Acceptance Testing**: Validate against user stories and success criteria.

---

## 4. Test Cases

### 4.1 User Management
- **TC-UM1**: Register with valid details → Success.
- **TC-UM2**: Register with duplicate email → Error message.
- **TC-UM3**: Login with correct credentials → Redirect to dashboard.
- **TC-UM4**: Login with wrong password → Error message.
- **TC-UM5**: Session timeout after inactivity → User logged out.

### 4.2 Client Management
- **TC-CM1**: Add client with valid details → Client saved.
- **TC-CM2**: Add client with invalid email → Error message.
- **TC-CM3**: Edit client details → Changes saved.
- **TC-CM4**: Delete client → Client removed, related services cascade delete.

### 4.3 Service Management
- **TC-SM1**: Add service with valid expiry date → Service saved.
- **TC-SM2**: Add service with invalid date format → Error message.
- **TC-SM3**: Edit service → Changes saved.
- **TC-SM4**: Delete service → Service removed, reminders cascade delete.
- **TC-SM5**: Add priced services in NGN, USD, and another valid code → Amount and currency saved and displayed unambiguously.
- **TC-SM6**: Add cost without a valid three-letter currency code → Error message.

### 4.4 Reminders
- **TC-RM1**: Set reminder frequency (30 days) → Reminder saved.
- **TC-RM2**: Set multiple channels (push + SMS) → Reminder saved.
- **TC-RM3**: Reminder triggers correctly based on expiry date.
- **TC-RM4**: Configure independent 30/7/1-day rows with different channels → Complete reminder set saved.
- **TC-RM5**: Enable a timing without Push or SMS → Error and prior service/reminder state preserved.
- **TC-RM6**: Deselect an existing timing → Stale reminder row removed.

### 4.5 Notifications
- **TC-NF1**: Cron job runs daily → Expiring services queried.
- **TC-NF2**: Push notification sent via FCM → Success logged.
- **TC-NF3**: SMS sent via Africa’s Talking → Success logged.
- **TC-NF4**: Failed SMS → Error logged, retry once.
- **TC-NF5**: Invalid push token → Token removed from DB.

### 4.6 Dashboard
- **TC-DB1**: Upcoming expiries displayed correctly.
- **TC-DB2**: Filter by service type → Correct results.
- **TC-DB3**: Pagination works for large datasets.

### 4.7 Reports
- **TC-RP1**: Export CSV → File generated with correct data.
- **TC-RP2**: Export Excel → File generated with correct data.
- **TC-RP3**: Apply filters before export → Filtered data exported.

### 4.8 Progressive Web App
- **TC-PWA1**: Install metadata loads with standard and maskable icons → Browser recognizes an installable standalone app.
- **TC-PWA2**: Visit dashboard/clients online, then lose connectivity → Previously visited user-scoped views remain available.
- **TC-PWA3**: Attempt an offline mutation → Change is not queued and the user is told to reconnect.
- **TC-PWA4**: Sign out or reach an authentication page → Private page caches are cleared.
- **TC-PWA5**: Explicitly enable/disable Push → Current Firebase destination is added/removed only for the authenticated user.

### 4.9 Security hardening
- **TC-SEC1**: Submit any state-changing form without a valid session token → HTTP 419 and no mutation.
- **TC-SEC2**: Send forwarded HTTPS/client-IP headers from an untrusted address → Headers ignored.
- **TC-SEC3**: Run production over plain HTTP → Redirect to the configured canonical HTTPS origin with HTTP 308.
- **TC-SEC4**: Exceed account/IP or IP-wide login threshold → HTTP 429 with `Retry-After`.
- **TC-SEC5**: Inspect public controllers → Request values cross the centralized `filter_input()` boundary before validation.

---

## 5. Non-Functional Testing
- **Performance**: Query response time < 200ms for <1000 records.
- **Security**: Passwords hashed; all mutations CSRF-protected; HTTPS enforced in production; request input filtered and validated; login attempts rate-limited.
- **Reliability**: Cron job runs daily without failure.
- **Scalability**: System supports 1000+ clients and services.

---

## 6. Test Environment
- **Backend**: PHP 8.1+, MySQL 8+
- **Frontend**: Tailwind CSS, Vanilla JS
- **APIs**: Firebase Cloud Messaging, Africa’s Talking
- **Server**: LAMP stack with HTTPS
