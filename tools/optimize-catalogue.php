<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/vehicle-media.php';

if (!function_exists('imagewebp')) {
    fwrite(STDERR, "Activer GD pour cette commande avec php -d extension=gd tools/optimize-catalogue.php\n");
    exit(1);
}

$directory = dirname(__DIR__) . '/assets/images/voitures/';

foreach (catalogueMedia() as $slug => $media) {
    $source = $directory . $media['source'];
    $image = imagecreatefrompng($source);

    if (!$image || !imagewebp($image, $directory . $slug . '.webp', 88)) {
        throw new RuntimeException('Conversion impossible : ' . $slug);
    }

    imagedestroy($image);
    echo $slug . ' : ' . filesize($source) . ' -> ' . filesize($directory . $slug . '.webp') . " octets\n";
}
