<?php

$root = dirname(__DIR__);
$server = getenv('MYSQLD_BINARY') ?: 'C:/xampp/mysql/bin/mysqld.exe';

if (!is_file($server)) {
    fwrite(STDERR, 'ECHEC : definir MYSQLD_BINARY vers un serveur MariaDB pour tester les migrations.' . PHP_EOL);
    exit(1);
}

function bootstrapDatabase(string $label, string $sql, ?string $expectedError = null): void
{
    global $root, $server;

    $directory = $root . '/var/mariadb-' . $label . '-' . bin2hex(random_bytes(6));
    mkdir($directory, 0777, true);
    $inputFile = $directory . '/input.sql';
    $outputFile = $directory . '/output.log';
    file_put_contents($inputFile, "SET NAMES utf8mb4;\n" . $sql . "\n");
    $process = proc_open(
        [$server, '--no-defaults', '--bootstrap', '--console', '--datadir=' . $directory, '--basedir=' . dirname(dirname($server)), '--innodb-buffer-pool-size=32M', '--character-set-server=utf8mb4', '--collation-server=utf8mb4_unicode_ci'],
        [0 => ['file', $inputFile, 'r'], 1 => ['file', $outputFile, 'a'], 2 => ['file', $outputFile, 'a']],
        $pipes
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Impossible de lancer MariaDB isole.');
    }

    $exitCode = proc_close($process);
    $output = file_get_contents($outputFile);
    $success = $exitCode === 0 && !str_contains($output, '[ERROR]');

    if (($expectedError === null && !$success) || ($expectedError !== null && ($success || !str_contains($output, $expectedError)))) {
        throw new RuntimeException($label . ' : ' . $output);
    }

    echo 'OK MariaDB ' . $label . PHP_EOL;
}

try {
    bootstrapDatabase('detection-echec', "CREATE DATABASE test_failure;\nUSE test_failure;\nCREATE TABLE assertions (value INT CHECK (value = 1));\nINSERT INTO assertions VALUES (0);", 'ERROR: 4025');
    $schema = file_get_contents($root . '/database/schema.sql');
    $seed = file_get_contents($root . '/database/seed.sql');
    $migration = file_get_contents($root . '/database/migrations/001_vehicle_catalogue.sql');
    $completeMigration = file_get_contents($root . '/database/migrations/002_complete_platform.sql') . chr(10) . file_get_contents($root . '/database/migrations/003_vehicle_details.sql');
    $assertions = "
CREATE TABLE test_assertions (value INT NOT NULL CHECK (value = 1));
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM vehicle_brands) = 6);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM vehicle_models) = 6);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM vehicle_models m INNER JOIN vehicle_brands b ON b.id = m.brand_id WHERE b.name = 'Renault' AND m.name = 'Clio') = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM categories WHERE name = 'Voitures') = 1);
INSERT INTO articles (category_id, vehicle_model_id, name, daily_price)
SELECT c.id, m.id, 'Renault Clio', 150 FROM categories c CROSS JOIN vehicle_models m INNER JOIN vehicle_brands b ON b.id = m.brand_id WHERE c.name = 'Voitures' AND b.name = 'Renault' AND m.name = 'Clio';
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM articles a INNER JOIN vehicle_models m ON m.id = a.vehicle_model_id WHERE a.is_active = 1) = 1);
";
    bootstrapDatabase('installation-neuve', $schema . "\n" . $seed . "\n" . $assertions);
    $legacySchema = file_get_contents($root . '/tests/fixtures/legacy-schema.sql');
    $history = "
INSERT INTO categories (id, name) VALUES (1, 'Informatique'), (2, 'Vehicules');
INSERT INTO users (id, name, email, password) VALUES (1, 'Test migration', 'migration@example.invalid', 'test-only-hash');
INSERT INTO articles (id, category_id, name, daily_price) VALUES (1, 1, 'Ordinateur historique', 20), (2, 2, 'Ancienne voiture a verifier', 100);
INSERT INTO article_images (article_id, image_path) VALUES (1, 'historique.jpg');
INSERT INTO locations (user_id, article_id, start_date, end_date, daily_price, total_price) VALUES (1, 1, '2026-01-01', '2026-01-03', 20, 40);
";
    $preservation = "
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM articles WHERE vehicle_model_id IS NULL) = 2);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM articles WHERE id = 1 AND name = 'Ordinateur historique' AND category_id = 1 AND daily_price = 20 AND is_active = 1) = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM articles WHERE id = 2 AND name = 'Ancienne voiture a verifier' AND vehicle_model_id IS NULL) = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM locations WHERE article_id = 1 AND total_price = 40) = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM article_images WHERE article_id = 1 AND image_path = 'historique.jpg') = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM users WHERE id = 1 AND email = 'migration@example.invalid') = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM categories WHERE id = 1 AND name = 'Informatique') = 1);
";
    $completeAssertions = "
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM vehicle_categories) = 6);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM users WHERE id = 1 AND is_active = 1) = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM article_images WHERE article_id = 1 AND is_active = 1) = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM locations WHERE reference_code IS NULL AND request_key IS NULL) = 1);
INSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM articles WHERE vehicle_model_id IS NULL AND fuel_type IS NULL AND vehicle_category_id IS NULL) = 2);
";
    bootstrapDatabase('migration-historique', $legacySchema . "\n" . $history . "\n" . $migration . "\n" . $completeMigration . "\n" . $assertions . "\n" . $preservation . "\n" . $completeAssertions);
    bootstrapDatabase('migration-depuis-v1', file_get_contents($root . '/tests/fixtures/day1-schema.sql') . "\n" . file_get_contents($root . '/tests/fixtures/day1-seed.sql') . "\n" . $completeMigration . "\n" . $assertions . "\nINSERT INTO test_assertions VALUES ((SELECT COUNT(*) FROM vehicle_categories) = 6);\n");
    bootstrapDatabase('contrainte-modele', $schema . "\n" . $seed . "\nINSERT INTO articles (category_id, vehicle_model_id, name, daily_price) VALUES (1, 999, 'Modele forge', 100);", 'ERROR: 1452');
    bootstrapDatabase('doublon-modele', $schema . "\n" . $seed . "\nINSERT INTO vehicle_models (brand_id, name) SELECT brand_id, name FROM vehicle_models LIMIT 1;", 'ERROR: 1062');
    bootstrapDatabase('contrainte-places', $schema . "\n" . $seed . "\nINSERT INTO articles (category_id, name, daily_price, seats) VALUES (1, 'Places invalides', 100, 30);", 'ERROR: 4025');
    bootstrapDatabase('contrainte-carburant', $schema . "\n" . $seed . "\nINSERT INTO articles (category_id, name, daily_price, fuel_type) VALUES (1, 'Carburant invalide', 100, 'kerosene');", 'ERROR: 4025');
    bootstrapDatabase('contrainte-portes', $schema . "\n" . $seed . "\nINSERT INTO articles (category_id, name, daily_price, doors) VALUES (1, 'Portes invalides', 100, 6);", 'ERROR: 4025');
    bootstrapDatabase('contrainte-climatisation', $schema . "\n" . $seed . "\nINSERT INTO articles (category_id, name, daily_price, air_conditioning) VALUES (1, 'Climatisation invalide', 100, 2);", 'ERROR: 4025');
    bootstrapDatabase('contrainte-puissance', $schema . "\n" . $seed . "\nINSERT INTO articles (category_id, name, daily_price, power_hp) VALUES (1, 'Puissance invalide', 100, 0);", 'ERROR: 4025');
    bootstrapDatabase('contrainte-validation-prix', $schema . "\n" . $seed . "\nINSERT INTO articles (category_id, name, daily_price, price_is_indicative) VALUES (1, 'Validation invalide', 100, 2);", 'ERROR: 4025');
    echo '12 scenarios MariaDB reussis sans connexion au serveur existant.' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'ECHEC : ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
