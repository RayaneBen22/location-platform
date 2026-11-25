<?php

function catalogueMedia(): array
{
    return [
        'audi-a3' => ['brand' => 'Audi', 'model' => 'A3', 'source' => 'Audi A3 bleu sur studio minimaliste.png', 'description' => 'L’Audi A3 accompagne vos déplacements avec une présentation soignée. Consultez les informations du véhicule et choisissez vos dates pour recevoir un tarif précis.'],
        'bmw-serie-3' => ['brand' => 'BMW', 'model' => 'Série 3', 'source' => 'Berline BMW bleue sur fond graphique.png', 'description' => 'Retrouvez la BMW Série 3 dans notre sélection de berlines. Préparez votre trajet, consultez le tarif journalier et envoyez votre demande pour la période souhaitée.'],
        'mercedes-classe-c' => ['brand' => 'Mercedes-Benz', 'model' => 'Classe C', 'source' => 'Berline Mercedes bleue dans un studio moderne.png', 'description' => 'La Mercedes-Benz Classe C fait partie de notre sélection de berlines. Retrouvez ses informations et le montant calculé pour vos dates avant de réserver.'],
        'peugeot-208' => ['brand' => 'Peugeot', 'model' => '208', 'source' => 'Peugeot 208 bleu en studio moderne.png', 'description' => 'Découvrez la Peugeot 208 et préparez vos déplacements au Maroc. Consultez les caractéristiques renseignées, comparez le tarif et choisissez votre période de location.'],
        'renault-clio' => ['brand' => 'Renault', 'model' => 'Clio', 'source' => 'Renault Clio bleu en studio moderne.png', 'description' => 'Choisissez la Renault Clio pour votre prochain trajet. Le calendrier vous permet de vérifier les dates libres et de connaître le montant de votre demande avant confirmation.'],
        'toyota-corolla' => ['brand' => 'Toyota', 'model' => 'Corolla', 'source' => 'Toyota Corolla bleue en studio.png', 'description' => 'Découvrez la Toyota Corolla dans notre catalogue. Consultez les informations disponibles et préparez votre réservation avec un prix calculé selon la durée choisie.'],
    ];
}

function cataloguePhotoOrder(string $alias = ''): string
{
    $column = $alias === 'i' ? 'i.image_path' : 'image_path';

    return "CASE WHEN " . $column . " LIKE 'assets/images/voitures/%' THEN 0 ELSE 1 END, " . ($alias === 'i' ? 'i.id' : 'id');
}

function integrateCatalogueMedia(PDO $connection, bool $clearSeedSpecifications = false): array
{
    return databaseTransaction($connection, function () use ($connection, $clearSeedSpecifications): array {
        $report = [];

        foreach (catalogueMedia() as $slug => $media) {
            $path = 'assets/images/voitures/' . $slug . '.webp';

            if (!is_file(dirname(__DIR__) . '/' . $path)) {
                throw new RuntimeException('Image optimisée manquante : ' . $slug);
            }

            $query = $connection->prepare('SELECT a.id, a.version, a.description FROM articles a INNER JOIN vehicle_models m ON m.id = a.vehicle_model_id INNER JOIN vehicle_brands b ON b.id = m.brand_id WHERE b.name = :brand AND m.name = :model');
            $query->execute(['brand' => $media['brand'], 'model' => $media['model']]);
            $vehicles = $query->fetchAll(PDO::FETCH_ASSOC);

            foreach ($vehicles as $vehicle) {
                $existing = $connection->prepare('SELECT id FROM article_images WHERE article_id = :id AND image_path = :path');
                $existing->execute(['id' => $vehicle['id'], 'path' => $path]);
                $photoId = $existing->fetchColumn();

                if ($photoId) {
                    $update = $connection->prepare('UPDATE article_images SET is_active = 1 WHERE id = :id');
                    $update->execute(['id' => $photoId]);
                } else {
                    $insert = $connection->prepare('INSERT INTO article_images (article_id, image_path) VALUES (:id, :path)');
                    $insert->execute(['id' => $vehicle['id'], 'path' => $path]);
                }

                $seed = in_array(mb_strtolower(trim($vehicle['version'] ?? '')), ['démonstration', 'demo', 'exemple', 'test'], true);

                if ($seed || trim($vehicle['description'] ?? '') === '') {
                    $update = $connection->prepare('UPDATE articles SET description = :description' . ($seed ? ', version = NULL' : '') . ($seed && $clearSeedSpecifications ? ', fuel_type = NULL, transmission = NULL, seats = NULL, manufacture_year = NULL, mileage = NULL' : '') . ' WHERE id = :id');
                    $update->execute(['id' => $vehicle['id'], 'description' => $media['description']]);
                }
            }

            $report[$slug] = count($vehicles);
        }

        return $report;
    });
}
