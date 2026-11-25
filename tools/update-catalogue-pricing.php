<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/bookings.php';
require_once dirname(__DIR__) . '/app/catalogue-pricing.php';
date_default_timezone_set('Africa/Casablanca');
$root = dirname(__DIR__);
$preview = in_array('--preview', $argv, true);

if (!$preview && !in_array('--configured-database', $argv, true)) {
    fwrite(STDERR, "Choisir --preview ou --configured-database. Sauvegarder la base configurée et appliquer la migration 003 avant cette commande.\n");
    exit(1);
}

try {
    if ($preview) {
        $path = $root . '/var/preview.sqlite';

        if (!is_file($path)) {
            throw new RuntimeException('Catalogue local absent.');
        }

        $connection = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $backup = $root . '/var/catalogue-before-pricing-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sqlite';
        $connection->exec('VACUUM INTO ' . $connection->quote($backup));
        $columns = array_column($connection->query('PRAGMA table_info(articles)')->fetchAll(), 'name');

        foreach (['doors' => 'INTEGER CHECK(doors IS NULL OR doors BETWEEN 2 AND 5)', 'power_hp' => 'INTEGER CHECK(power_hp IS NULL OR power_hp BETWEEN 1 AND 2000)', 'air_conditioning' => 'INTEGER CHECK(air_conditioning IS NULL OR air_conditioning IN (0, 1))', 'price_is_indicative' => 'INTEGER NOT NULL DEFAULT 0 CHECK(price_is_indicative IN (0, 1))'] as $column => $definition) {
            if (!in_array($column, $columns, true)) {
                $connection->exec('ALTER TABLE articles ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    } else {
        $connection = database();
    }

    $report = applyIndicativeCataloguePricing($connection);
    $json = json_encode(['date' => date(DATE_ATOM), 'preview' => $preview, 'vehicles' => $report], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    file_put_contents($root . '/var/pricing-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json', $json);
    echo $json . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Mise à jour interrompue : ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
