PRAGMA foreign_keys = ON;
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL COLLATE NOCASE UNIQUE,
    password TEXT NOT NULL,
    phone TEXT,
    role TEXT NOT NULL DEFAULT 'client' CHECK(role IN ('client', 'agent')),
    is_active INTEGER NOT NULL DEFAULT 1,
    session_version INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, description TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE vehicle_brands (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL COLLATE NOCASE UNIQUE);
CREATE TABLE vehicle_models (id INTEGER PRIMARY KEY AUTOINCREMENT, brand_id INTEGER NOT NULL REFERENCES vehicle_brands(id), name TEXT NOT NULL COLLATE NOCASE, UNIQUE(brand_id, name));
CREATE TABLE vehicle_categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL COLLATE NOCASE UNIQUE);
CREATE TABLE articles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL REFERENCES categories(id),
    vehicle_model_id INTEGER REFERENCES vehicle_models(id),
    vehicle_category_id INTEGER REFERENCES vehicle_categories(id),
    name TEXT NOT NULL,
    version TEXT,
    description TEXT,
    characteristics TEXT,
    condition_description TEXT,
    daily_price DECIMAL NOT NULL CHECK(daily_price > 0),
    fuel_type TEXT CHECK(fuel_type IS NULL OR fuel_type IN ('essence', 'diesel', 'hybride', 'electrique')),
    transmission TEXT CHECK(transmission IS NULL OR transmission IN ('manuelle', 'automatique')),
    seats INTEGER CHECK(seats IS NULL OR seats BETWEEN 2 AND 9),
    manufacture_year INTEGER,
    mileage INTEGER,
    doors INTEGER CHECK(doors IS NULL OR doors BETWEEN 2 AND 5),
    power_hp INTEGER CHECK(power_hp IS NULL OR power_hp BETWEEN 1 AND 2000),
    air_conditioning INTEGER CHECK(air_conditioning IS NULL OR air_conditioning IN (0, 1)),
    price_is_indicative INTEGER NOT NULL DEFAULT 0 CHECK(price_is_indicative IN (0, 1)),
    status TEXT NOT NULL DEFAULT 'disponible' CHECK(status IN ('disponible', 'en_location', 'en_maintenance')),
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_vehicle_catalogue ON articles(is_active, vehicle_model_id, daily_price);
CREATE TABLE article_images (id INTEGER PRIMARY KEY AUTOINCREMENT, article_id INTEGER NOT NULL REFERENCES articles(id), image_path TEXT NOT NULL, is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id),
    article_id INTEGER NOT NULL REFERENCES articles(id),
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL CHECK(end_date > start_date),
    daily_price DECIMAL NOT NULL CHECK(daily_price > 0),
    total_price DECIMAL NOT NULL CHECK(total_price > 0),
    reference_code TEXT UNIQUE,
    request_key TEXT UNIQUE,
    vehicle_label TEXT,
    status TEXT NOT NULL DEFAULT 'en_attente' CHECK(status IN ('en_attente', 'confirmee', 'en_cours', 'terminee', 'annulee', 'refusee')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_locations_availability ON locations(article_id, status, start_date, end_date);
CREATE INDEX idx_locations_client ON locations(user_id, created_at);
CREATE TABLE login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, identity_hash TEXT NOT NULL, created_at TEXT NOT NULL);
CREATE INDEX idx_login_attempts_identity ON login_attempts(identity_hash, created_at);
