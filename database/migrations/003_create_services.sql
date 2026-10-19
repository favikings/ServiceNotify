CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type ENUM('domain', 'hosting', 'maintenance') NOT NULL,
    provider VARCHAR(150) DEFAULT NULL,
    expiry_date DATE NOT NULL,
    cost DECIMAL(10,2) DEFAULT NULL,
    billing_cycle ENUM('monthly', 'yearly', 'custom') DEFAULT 'yearly',
    payment_account VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expiry_date (expiry_date),
    CONSTRAINT fk_services_client
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
