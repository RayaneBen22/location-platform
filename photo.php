<?php

require_once __DIR__ . '/app/bootstrap.php';
$id = positiveIdentifier($_GET['id'] ?? null);
$query = database()->prepare('SELECT i.image_path, i.is_active AS image_active, a.is_active, a.vehicle_model_id FROM article_images i INNER JOIN articles a ON a.id = i.article_id WHERE i.id = :id');
$query->execute(['id' => $id]);
$photo = $query->fetch();
$agent = currentUser();
$isAgent = $agent && $agent['role'] === 'agent';

if (!$photo || (!$isAgent && (!$photo['is_active'] || !$photo['image_active'] || $photo['vehicle_model_id'] === null))) {
    http_response_code(404);
    exit;
}

$cataloguePaths = array_map(fn (string $slug): string => 'assets/images/voitures/' . $slug . '.webp', array_keys(catalogueMedia()));

if (in_array($photo['image_path'], $cataloguePaths, true)) {
    $file = __DIR__ . '/' . $photo['image_path'];
    $matches = [null, 'webp'];
} elseif (preg_match('/\Avehicle-[a-f0-9]{32}\.(jpg|png|webp)\z/', $photo['image_path'], $matches)) {
    $file = __DIR__ . '/var/uploads/' . $photo['image_path'];
} else {
    header('Content-Type: image/svg+xml');
    readfile(__DIR__ . '/assets/car-placeholder.svg');
    exit;
}

if (!is_file($file)) {
    header('Content-Type: image/svg+xml');
    readfile(__DIR__ . '/assets/car-placeholder.svg');
    exit;
}

header('Content-Type: ' . ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$matches[1]]);
header('Cache-Control: private, max-age=300');
header('Content-Length: ' . filesize($file));
readfile($file);
