ALTER TABLE articles
    ADD COLUMN doors TINYINT UNSIGNED DEFAULT NULL,
    ADD COLUMN power_hp SMALLINT UNSIGNED DEFAULT NULL,
    ADD COLUMN air_conditioning BOOLEAN DEFAULT NULL,
    ADD COLUMN price_is_indicative BOOLEAN NOT NULL DEFAULT FALSE,
    ADD CONSTRAINT chk_vehicle_doors CHECK (doors IS NULL OR doors BETWEEN 2 AND 5),
    ADD CONSTRAINT chk_vehicle_power CHECK (power_hp IS NULL OR power_hp BETWEEN 1 AND 2000),
    ADD CONSTRAINT chk_vehicle_air_conditioning CHECK (air_conditioning IS NULL OR air_conditioning IN (0, 1)),
    ADD CONSTRAINT chk_vehicle_indicative_price CHECK (price_is_indicative IN (0, 1));
