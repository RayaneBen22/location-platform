<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/vehicle-media.php';
$preview = in_array('--preview', $argv, true);

if (!$preview && !in_array('--configured-database', $argv, true)) {
    fwrite(STDERR, "Choisir --preview ou --configured-database après sauvegarde de la base configurée.\n");
    exit(1);
}

if ($preview) {
    $path = dirname(__DIR__) . '/var/preview.sqlite';

    if (!is_file($path)) {
        throw new RuntimeException('Catalogue local introuvable. Aucun véhicule créé.');
    }

    $connection = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $backup = dirname(__DIR__) . '/var/catalogue-before-photos-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sqlite';
    $connection->exec('VACUUM INTO ' . $connection->quote($backup));
} else {
    $connection = database();
}

$report = integrateCatalogueMedia($connection, $preview);

foreach ($report as $slug => $count) {
    echo $slug . ' : ' . $count . " véhicule(s) associé(s)\n";
}
