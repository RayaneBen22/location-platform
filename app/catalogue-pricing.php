<?php

function applyIndicativeCataloguePricing(PDO $connection): array
{
    $proposal = json_decode(file_get_contents(dirname(__DIR__) . '/database/catalogue-pricing.json'), true, 512, JSON_THROW_ON_ERROR);

    return databaseTransaction($connection, function () use ($connection, $proposal): array {
        $report = [];
        $select = $connection->prepare('SELECT a.id, a.daily_price, a.price_is_indicative FROM articles a INNER JOIN vehicle_models m ON m.id = a.vehicle_model_id INNER JOIN vehicle_brands b ON b.id = m.brand_id WHERE b.name = :brand AND m.name = :model' . lockSuffix($connection));
        $update = $connection->prepare('UPDATE articles SET daily_price = :price, price_is_indicative = 1 WHERE id = :id');

        foreach ($proposal['vehicles'] as $vehicle) {
            priceInCents($vehicle['daily_price']);
            $select->execute(['brand' => $vehicle['brand'], 'model' => $vehicle['model']]);
            $matches = $select->fetchAll(PDO::FETCH_ASSOC);

            if (!$matches) {
                throw new DomainException('Modèle absent : ' . $vehicle['brand'] . ' ' . $vehicle['model'] . '. Aucun tarif modifié.');
            }

            foreach ($matches as $match) {
                $update->execute(['price' => $vehicle['daily_price'], 'id' => $match['id']]);
                $report[] = ['id' => (int) $match['id'], 'vehicle' => $vehicle['brand'] . ' ' . $vehicle['model'], 'old_daily_price' => decimalAmount(priceInCents((string) $match['daily_price'])), 'new_daily_price' => $vehicle['daily_price'], 'price_is_indicative' => true];
            }
        }

        return $report;
    });
}
