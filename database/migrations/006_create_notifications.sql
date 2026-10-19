CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    reminder_id INT NOT NULL,
    type ENUM('push', 'sms') NOT NULL,
    status ENUM('success', 'failure') NOT NULL DEFAULT 'success',
    message TEXT NULL,
    scheduled_for DATE NOT NULL,
    attempt TINYINT UNSIGNED NOT NULL DEFAULT 1,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_service (service_id),
    INDEX idx_notification_due (scheduled_for, type, status),
    UNIQUE INDEX uq_notification_attempt (reminder_id, type, scheduled_for, attempt),
    CONSTRAINT fk_notifications_service
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_reminder
        FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
