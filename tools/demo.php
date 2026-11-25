<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/vehicles.php';
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/catalogue.php';
require_once dirname(__DIR__) . '/app/bookings.php';
require_once dirname(__DIR__) . '/app/vehicle-media.php';
require_once dirname(__DIR__) . '/app/catalogue-pricing.php';
date_default_timezone_set('Africa/Casablanca');
$root = dirname(__DIR__);
$path = $root . '/var/preview.sqlite';

if (file_exists($path)) {
    fwrite(STDERR, "L’aperçu existe déjà. Aucun fichier n’a été remplacé.\n");
    exit(1);
}

if (!is_dir($root . '/var')) {
    mkdir($root . '/var', 0700, true);
}

$connection = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$connection->exec(file_get_contents($root . '/tests/fixtures/platform.sqlite.sql'));
$seed = preg_replace('/^USE location_platform;\s*/', '', file_get_contents($root . '/database/seed.sql'));
$connection->exec($seed);
$models = vehicleModels($connection);
$pricing = json_decode(file_get_contents($root . '/database/catalogue-pricing.json'), true, 512, JSON_THROW_ON_ERROR)['vehicles'];

foreach ($models as $index => $model) {
    $categoryName = ['Clio' => 'Citadine', '208' => 'Citadine', 'A3' => 'Compacte'][$model['model_name']] ?? 'Berline';
    $category = $connection->prepare('SELECT id FROM vehicle_categories WHERE name = :name');
    $category->execute(['name' => $categoryName]);
    $proposals = array_values(array_filter($pricing, fn (array $row): bool => $row['brand'] === $model['brand_name'] && $row['model'] === $model['model_name']));
    saveVehicleDetails($connection, null, [
        'vehicle_model_id' => (string) $model['id'], 'vehicle_category_id' => (string) $category->fetchColumn(),
        'daily_price' => $proposals[0]['daily_price'],
        'status' => 'disponible',
    ]);
}

integrateCatalogueMedia($connection, true);
applyIndicativeCataloguePricing($connection);
echo "Catalogue local créé sous var/preview.sqlite avec les six visuels de présentation. Aucun stock réel ni compte utilisateur ajouté.\n";
