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

ALTER TABLE articles
    ADD COLUMN vehicle_model_id INT UNSIGNED DEFAULT NULL AFTER category_id,
    ADD CONSTRAINT fk_articles_vehicle_model
        FOREIGN KEY (vehicle_model_id) REFERENCES vehicle_models(id)
        ON DELETE RESTRICT;

INSERT INTO categories (name, description)
SELECT 'Voitures', 'Automobiles proposées à la location'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Voitures');

INSERT INTO vehicle_brands (name)
VALUES ('BMW'), ('Mercedes-Benz'), ('Audi'), ('Renault'), ('Peugeot'), ('Toyota');

INSERT INTO vehicle_models (brand_id, name)
SELECT id, 'Série 3' FROM vehicle_brands WHERE name = 'BMW'
UNION ALL SELECT id, 'Classe C' FROM vehicle_brands WHERE name = 'Mercedes-Benz'
UNION ALL SELECT id, 'A3' FROM vehicle_brands WHERE name = 'Audi'
UNION ALL SELECT id, 'Clio' FROM vehicle_brands WHERE name = 'Renault'
UNION ALL SELECT id, '208' FROM vehicle_brands WHERE name = 'Peugeot'
UNION ALL SELECT id, 'Corolla' FROM vehicle_brands WHERE name = 'Toyota';
