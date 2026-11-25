CREATE DATABASE IF NOT EXISTS location_platform
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE location_platform;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('client', 'agent') NOT NULL DEFAULT 'client',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    session_version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
CREATE TABLE vehicle_brands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehicle_models (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_id INT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    CONSTRAINT uq_vehicle_models_brand_name UNIQUE (brand_id, name),
    CONSTRAINT fk_vehicle_models_brand FOREIGN KEY (brand_id)
        REFERENCES vehicle_brands(id) ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehicle_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    vehicle_model_id INT UNSIGNED DEFAULT NULL,
    vehicle_category_id INT UNSIGNED DEFAULT NULL,
    version VARCHAR(60) DEFAULT NULL,
    fuel_type VARCHAR(20) DEFAULT NULL,
    transmission VARCHAR(20) DEFAULT NULL,
    seats TINYINT UNSIGNED DEFAULT NULL,
    manufacture_year SMALLINT UNSIGNED DEFAULT NULL,
    mileage INT UNSIGNED DEFAULT NULL,
    doors TINYINT UNSIGNED DEFAULT NULL,
    power_hp SMALLINT UNSIGNED DEFAULT NULL,
    air_conditioning BOOLEAN DEFAULT NULL,
    price_is_indicative BOOLEAN NOT NULL DEFAULT FALSE,
    name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    characteristics TEXT DEFAULT NULL,
    daily_price DECIMAL(10, 2) NOT NULL,
    condition_description VARCHAR(255) DEFAULT NULL,
    status ENUM('disponible', 'en_location', 'en_maintenance')
        NOT NULL DEFAULT 'disponible',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_articles_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_articles_vehicle_model
        FOREIGN KEY (vehicle_model_id) REFERENCES vehicle_models(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_articles_daily_price
        CHECK (daily_price > 0),
    CONSTRAINT fk_articles_vehicle_category FOREIGN KEY (vehicle_category_id) REFERENCES vehicle_categories(id) ON DELETE RESTRICT,
    CONSTRAINT chk_vehicle_seats CHECK (seats IS NULL OR seats BETWEEN 2 AND 9),
    CONSTRAINT chk_vehicle_fuel CHECK (fuel_type IS NULL OR fuel_type IN ('essence', 'diesel', 'hybride', 'electrique')),
    CONSTRAINT chk_vehicle_transmission CHECK (transmission IS NULL OR transmission IN ('manuelle', 'automatique')),
    CONSTRAINT chk_vehicle_doors CHECK (doors IS NULL OR doors BETWEEN 2 AND 5),
    CONSTRAINT chk_vehicle_power CHECK (power_hp IS NULL OR power_hp BETWEEN 1 AND 2000),
    CONSTRAINT chk_vehicle_air_conditioning CHECK (air_conditioning IS NULL OR air_conditioning IN (0, 1)),
    CONSTRAINT chk_vehicle_indicative_price CHECK (price_is_indicative IN (0, 1)),
    INDEX idx_vehicle_catalogue (is_active, vehicle_model_id, daily_price)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  CREATE TABLE article_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_article_images_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  CREATE TABLE locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    article_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    daily_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(12, 2) NOT NULL,
    reference_code VARCHAR(32) DEFAULT NULL UNIQUE,
    request_key CHAR(64) DEFAULT NULL UNIQUE,
    vehicle_label VARCHAR(200) DEFAULT NULL,
    status ENUM(
        'en_attente',
        'confirmee',
        'en_cours',
        'terminee',
        'annulee',
        'refusee'
    ) NOT NULL DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_locations_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_locations_article
        FOREIGN KEY (article_id) REFERENCES articles(id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_locations_dates
        CHECK (end_date > start_date),

    CONSTRAINT chk_locations_prices
        CHECK (daily_price > 0 AND total_price > 0),
    INDEX idx_locations_availability (article_id, status, start_date, end_date),
    INDEX idx_locations_client (user_id, created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identity_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_login_attempts_identity (identity_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
