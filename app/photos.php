<?php

require_once __DIR__ . '/vehicle-media.php';

function vehiclePhotos(PDO $connection, int $vehicleId, bool $all = false): array
{
    $query = $connection->prepare('SELECT id, image_path, is_active FROM article_images WHERE article_id = :id' . ($all ? '' : ' AND is_active = 1') . ' ORDER BY ' . cataloguePhotoOrder());
    $query->execute(['id' => $vehicleId]);

    return $query->fetchAll();
}

function validatedPhoto(array $file): array
{
    if (!isset($file['error'], $file['tmp_name'], $file['size']) || !is_int($file['error']) || $file['error'] !== UPLOAD_ERR_OK || !is_string($file['tmp_name']) || !is_int($file['size']) || $file['size'] < 1 || $file['size'] > 5 * 1024 * 1024 || !is_file($file['tmp_name'])) {
        throw new DomainException('Choisissez une photo JPEG, PNG ou WebP de 5 Mo maximum.');
    }

    $type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$type] ?? null;
    $dimensions = @getimagesize($file['tmp_name']);

    if (!$extension || !$dimensions || $dimensions[0] > 12000 || $dimensions[1] > 12000 || $dimensions[0] * $dimensions[1] > 40000000) {
        throw new DomainException('Le fichier doit être une image valide, de dimensions raisonnables.');
    }

    return ['extension' => $extension, 'temporary_path' => $file['tmp_name']];
}

function uploadVehiclePhoto(PDO $connection, int $vehicleId, array $file): void
{
    $photo = validatedPhoto($file);

    if (!is_uploaded_file($photo['temporary_path'])) {
        throw new DomainException('Téléversement invalide.');
    }

    databaseTransaction($connection, function () use ($connection, $vehicleId, $photo): void {
        $query = $connection->prepare('SELECT id FROM articles WHERE id = :id AND vehicle_model_id IS NOT NULL' . lockSuffix($connection));
        $query->execute(['id' => $vehicleId]);

        if (!$query->fetch() || count(vehiclePhotos($connection, $vehicleId)) >= 8) {
            throw new DomainException('Voiture introuvable ou limite de 8 photos actives atteinte.');
        }

        $directory = dirname(__DIR__) . '/var/uploads';

        if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
            throw new RuntimeException('Stockage indisponible.');
        }

        $name = 'vehicle-' . bin2hex(random_bytes(16)) . '.' . $photo['extension'];

        if (!move_uploaded_file($photo['temporary_path'], $directory . '/' . $name)) {
            throw new RuntimeException('Téléversement indisponible.');
        }

        $query = $connection->prepare('INSERT INTO article_images (article_id, image_path) VALUES (:id, :path)');
        $query->execute(['id' => $vehicleId, 'path' => $name]);
    });
}
