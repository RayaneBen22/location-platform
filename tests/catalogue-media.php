<?php

require_once __DIR__ . '/support.php';
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/vehicles.php';
require_once dirname(__DIR__) . '/app/vehicle-media.php';
$root = dirname(__DIR__);
$runtime = $root . '/var/media-test-' . bin2hex(random_bytes(6));
mkdir($runtime, 0700, true);
$testDsn = 'sqlite:' . $runtime . '/catalogue.sqlite';
$connection = new PDO($testDsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$passed = 0;

try {
    $connection->exec(file_get_contents(__DIR__ . '/fixtures/platform.sqlite.sql'));
    $connection->exec(preg_replace('/^USE location_platform;\s*/', '', file_get_contents($root . '/database/seed.sql')));
    $insert = $connection->prepare('INSERT INTO articles (category_id, vehicle_model_id, name, version, description, daily_price) SELECT 1, m.id, :name, :version, :description, :price FROM vehicle_models m JOIN vehicle_brands b ON b.id = m.brand_id WHERE b.name = :brand AND m.name = :model');

    foreach (catalogueMedia() as $slug => $media) {
        $insert->execute(['name' => $media['brand'] . ' ' . $media['model'], 'version' => 'Démonstration', 'description' => 'Voiture fictive', 'price' => '350.75', 'brand' => $media['brand'], 'model' => $media['model']]);
    }

    $before = $connection->query('SELECT id, daily_price, status, is_active FROM articles ORDER BY id')->fetchAll();
    $report = integrateCatalogueMedia($connection);
    check(count(array_filter($report, fn ($count) => $count === 1)) === 6, 'Six associations exactes par marque et modele');
    integrateCatalogueMedia($connection);
    check((int) $connection->query('SELECT COUNT(*) FROM article_images')->fetchColumn() === 6, 'Reexecution sans duplication des photos');
    check($before === $connection->query('SELECT id, daily_price, status, is_active FROM articles ORDER BY id')->fetchAll(), 'Prix et disponibilite inchanges');
    $rows = $connection->query('SELECT a.id, b.name AS brand, m.name AS model, i.id AS photo_id, i.image_path FROM articles a JOIN vehicle_models m ON m.id = a.vehicle_model_id JOIN vehicle_brands b ON b.id = m.brand_id JOIN article_images i ON i.article_id = a.id')->fetchAll();

    foreach ($rows as $row) {
        $slug = array_search($row['image_path'], array_combine(array_keys(catalogueMedia()), array_map(fn ($slug) => 'assets/images/voitures/' . $slug . '.webp', array_keys(catalogueMedia()))), true);
        $media = catalogueMedia()[$slug];
        check($row['brand'] === $media['brand'] && $row['model'] === $media['model'], $slug . ' associe au bon modele');
        $original = getimagesize($root . '/assets/images/voitures/' . $media['source']);
        $optimized = getimagesize($root . '/' . $row['image_path']);
        check($original[0] === $optimized[0] && $original[1] === $optimized[1], $slug . ' proportions et cadrage conserves');
        $photo = requestPage('photo.php', 'GET', [], '', 'id=' . $row['photo_id']);
        check($photo['status'] === 200 && str_contains($photo['headers'], 'image/webp') && strlen($photo['body']) === filesize($root . '/' . $row['image_path']), $slug . ' charge via la route photo');
        $page = requestPage('voiture.php', 'GET', [], '', 'id=' . $row['id']);
        check($page['status'] === 200 && str_contains($page['body'], 'photo.php?id=' . $row['photo_id']) && !preg_match('/démonstration|voiture fictive/iu', $page['body']), $slug . ' fiche complete sans mention temporaire');
    }

    $catalogue = requestPage('catalogue.php');
    check(substr_count($catalogue['body'], '>Voir le véhicule</a>') === 6 && substr_count($catalogue['body'], '>Réserver ') === 6, 'Six cartes avec deux appels a action');

    foreach (['index.php', 'catalogue.php'] as $path) {
        $page = requestPage($path);
        check($page['status'] === 200 && !preg_match('/démonstration|voiture fictive|données fictives/iu', $page['body']), $path . ' sans mention temporaire');
    }

    $idsByPhoto = array_column($rows, 'id', 'image_path');
    $bmwId = $idsByPhoto['assets/images/voitures/bmw-serie-3.webp'];
    $visibility = $connection->prepare('UPDATE articles SET is_active = :visible WHERE id = :id');

    foreach ([1 => '/voiture.php?id=' . $bmwId, 0 => '/catalogue.php'] as $visible => $destination) {
        $visibility->execute(['visible' => $visible, 'id' => $bmwId]);
        $home = requestPage('index.php');
        $document = new DOMDocument();
        $document->loadHTML($home['body'], LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        $link = (new DOMXPath($document))->query('//div[@class="hero-art"]//a[@href]')->item(0);
        check($home['status'] === 200 && $link?->getAttribute('href') === $destination,
            $visible ? 'La selection BMW ouvre sa fiche publique' : 'La selection ouvre le catalogue si la BMW est masquee');
    }

    echo $passed . " verifications des medias reussies.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
