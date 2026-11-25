USE location_platform;

INSERT INTO categories (name, description)
VALUES ('Voitures', 'Automobiles propos├®es ├á la location');

INSERT INTO vehicle_brands (name)
VALUES ('BMW'), ('Mercedes-Benz'), ('Audi'), ('Renault'), ('Peugeot'), ('Toyota');

INSERT INTO vehicle_models (brand_id, name)
SELECT id, 'S├®rie 3' FROM vehicle_brands WHERE name = 'BMW'
UNION ALL SELECT id, 'Classe C' FROM vehicle_brands WHERE name = 'Mercedes-Benz'
UNION ALL SELECT id, 'A3' FROM vehicle_brands WHERE name = 'Audi'
UNION ALL SELECT id, 'Clio' FROM vehicle_brands WHERE name = 'Renault'
UNION ALL SELECT id, '208' FROM vehicle_brands WHERE name = 'Peugeot'
UNION ALL SELECT id, 'Corolla' FROM vehicle_brands WHERE name = 'Toyota';
