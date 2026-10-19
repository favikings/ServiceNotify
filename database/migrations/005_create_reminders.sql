CREATE TABLE IF NOT EXISTS reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    frequency INT NOT NULL,
    channels SET('push', 'sms') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_service_reminder (service_id),
    UNIQUE INDEX uq_service_reminder_frequency (service_id, frequency),
    CONSTRAINT fk_reminders_service
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
