# ServiceNotify Database Schema (schema.sql)

```sql
-- Database: service_notify

CREATE DATABASE IF NOT EXISTS service_notify;
USE service_notify;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    settings JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clients table
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    organization VARCHAR(150),
    phone VARCHAR(20),
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_clients_user_id ON clients(user_id);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type ENUM('domain','hosting','maintenance') NOT NULL,
    provider VARCHAR(150),
    expiry_date DATE NOT NULL,
    cost DECIMAL(10,2),
    currency CHAR(3),
    billing_cycle ENUM('monthly','yearly','custom') DEFAULT 'yearly',
    payment_account VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Reminders table
CREATE TABLE reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    frequency INT NOT NULL, -- days before expiry
    channels SET('push','sms') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Notifications log
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    reminder_id INT NOT NULL,
    type ENUM('push','sms') NOT NULL,
    status ENUM('success','failure') DEFAULT 'success',
    message TEXT,
    scheduled_for DATE NOT NULL,
    attempt TINYINT UNSIGNED NOT NULL DEFAULT 1,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_expiry_date ON services(expiry_date);
CREATE INDEX idx_service_reminder ON reminders(service_id);
CREATE UNIQUE INDEX uq_service_reminder_frequency ON reminders(service_id, frequency);
CREATE INDEX idx_notification_service ON notifications(service_id);
CREATE INDEX idx_notification_due ON notifications(scheduled_for, type, status);
CREATE UNIQUE INDEX uq_notification_attempt ON notifications(reminder_id, type, scheduled_for, attempt);
