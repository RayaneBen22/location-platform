CREATE TABLE vehicle_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO vehicle_categories (name) VALUES ('Citadine'), ('Berline'), ('SUV'), ('Compacte'), ('Familiale'), ('Cabriolet');

ALTER TABLE articles
    ADD COLUMN vehicle_category_id INT UNSIGNED DEFAULT NULL,
    ADD COLUMN version VARCHAR(60) DEFAULT NULL,
    ADD COLUMN fuel_type VARCHAR(20) DEFAULT NULL,
    ADD COLUMN transmission VARCHAR(20) DEFAULT NULL,
    ADD COLUMN seats TINYINT UNSIGNED DEFAULT NULL,
    ADD COLUMN manufacture_year SMALLINT UNSIGNED DEFAULT NULL,
    ADD COLUMN mileage INT UNSIGNED DEFAULT NULL,
    ADD CONSTRAINT fk_articles_vehicle_category FOREIGN KEY (vehicle_category_id) REFERENCES vehicle_categories(id) ON DELETE RESTRICT,
    ADD CONSTRAINT chk_vehicle_seats CHECK (seats IS NULL OR seats BETWEEN 2 AND 9),
    ADD CONSTRAINT chk_vehicle_fuel CHECK (fuel_type IS NULL OR fuel_type IN ('essence', 'diesel', 'hybride', 'electrique')),
    ADD CONSTRAINT chk_vehicle_transmission CHECK (transmission IS NULL OR transmission IN ('manuelle', 'automatique')),
    ADD INDEX idx_vehicle_catalogue (is_active, vehicle_model_id, daily_price);

ALTER TABLE users
    ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE,
    ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE article_images ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE;

ALTER TABLE locations
    ADD COLUMN reference_code VARCHAR(32) DEFAULT NULL UNIQUE,
    ADD COLUMN request_key CHAR(64) DEFAULT NULL UNIQUE,
    ADD COLUMN vehicle_label VARCHAR(200) DEFAULT NULL,
    ADD INDEX idx_locations_availability (article_id, status, start_date, end_date),
    ADD INDEX idx_locations_client (user_id, created_at);

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identity_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_login_attempts_identity (identity_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
