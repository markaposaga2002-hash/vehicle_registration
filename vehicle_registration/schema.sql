

CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    owner_name VARCHAR(120) NOT NULL,
    vehicle_type VARCHAR(40) NOT NULL,
    make_model VARCHAR(100) NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    color VARCHAR(50) NOT NULL,
    registration_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('Active', 'Pending', 'Expired') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_owner_name (owner_name),
    INDEX idx_expiry_date (expiry_date)
);
