<?php

function vehicleModels(PDO $database): array
{
    return $database->query(
        'SELECT m.id, m.brand_id, m.name AS model_name, b.name AS brand_name
         FROM vehicle_models m
         INNER JOIN vehicle_brands b ON b.id = m.brand_id
         ORDER BY b.name, m.name'
    )->fetchAll(PDO::FETCH_ASSOC);
}

function vehicleModel(PDO $database, int $modelId): array|false
{
    $statement = $database->prepare(
        'SELECT m.id, m.brand_id, m.name AS model_name, b.name AS brand_name
         FROM vehicle_models m
         INNER JOIN vehicle_brands b ON b.id = m.brand_id
         WHERE m.id = :id'
    );
    $statement->execute(['id' => $modelId]);

    return $statement->fetch(PDO::FETCH_ASSOC);
}

function publicVehicles(PDO $database): array
{
    return $database->query(
        'SELECT a.id, a.description, a.daily_price, a.status,
                m.name AS model_name, b.name AS brand_name
         FROM articles a
         INNER JOIN vehicle_models m ON m.id = a.vehicle_model_id
         INNER JOIN vehicle_brands b ON b.id = m.brand_id
         WHERE a.is_active = 1
         ORDER BY b.name, m.name, a.id'
    )->fetchAll(PDO::FETCH_ASSOC);
}

function publicVehicleBrands(PDO $database): array
{
    return $database->query(
        'SELECT b.id, b.name, COUNT(a.id) AS vehicle_count
         FROM vehicle_brands b
         INNER JOIN vehicle_models m ON m.brand_id = b.id
         INNER JOIN articles a ON a.vehicle_model_id = m.id
         WHERE a.is_active = 1
         GROUP BY b.id, b.name
         ORDER BY b.name'
    )->fetchAll(PDO::FETCH_ASSOC);
}

function agentVehicles(PDO $database): array
{
    return $database->query(
        'SELECT a.id, a.name, a.daily_price, a.status, a.is_active,
                a.vehicle_model_id, m.name AS model_name, b.name AS brand_name
         FROM articles a
         LEFT JOIN vehicle_models m ON m.id = a.vehicle_model_id
         LEFT JOIN vehicle_brands b ON b.id = m.brand_id
         ORDER BY b.name, m.name, a.id'
    )->fetchAll(PDO::FETCH_ASSOC);
}

function saveVehicle(PDO $database, ?int $articleId, int $modelId, string $dailyPrice): bool
{
    if (!preg_match('/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/', $dailyPrice) || (float) $dailyPrice <= 0) {
        return false;
    }

    $model = vehicleModel($database, $modelId);

    if (!$model) {
        return false;
    }

    $parameters = [
        'name' => $model['brand_name'] . ' ' . $model['model_name'],
        'vehicle_model_id' => $modelId,
        'daily_price' => $dailyPrice,
    ];

    if ($articleId !== null) {
        $exists = $database->prepare('SELECT id FROM articles WHERE id = :id');
        $exists->execute(['id' => $articleId]);

        if (!$exists->fetchColumn()) {
            return false;
        }

        $parameters['id'] = $articleId;
        $statement = $database->prepare(
            'UPDATE articles
             SET name = :name, vehicle_model_id = :vehicle_model_id, daily_price = :daily_price
             WHERE id = :id'
        );
    } else {
        $category = $database->prepare('SELECT id FROM categories WHERE name = :name');
        $category->execute(['name' => 'Voitures']);
        $categoryId = $category->fetchColumn();

        if ($categoryId === false) {
            return false;
        }

        $parameters['category_id'] = (int) $categoryId;
        $statement = $database->prepare(
            'INSERT INTO articles (category_id, vehicle_model_id, name, daily_price)
             VALUES (:category_id, :vehicle_model_id, :name, :daily_price)'
        );
    }

    return $statement->execute($parameters);
}

function setVehicleActivation(PDO $database, int $articleId, int $isActive): bool
{
    if (!in_array($isActive, [0, 1], true)) {
        return false;
    }

    $statement = $database->prepare(
        'SELECT a.vehicle_model_id
         FROM articles a
         LEFT JOIN vehicle_models m ON m.id = a.vehicle_model_id
         WHERE a.id = :id AND (:is_active = 0 OR m.id IS NOT NULL)'
    );
    $statement->execute(['id' => $articleId, 'is_active' => $isActive]);

    if ($statement->fetch(PDO::FETCH_ASSOC) === false) {
        return false;
    }

    $statement = $database->prepare('UPDATE articles SET is_active = :is_active WHERE id = :id');

    return $statement->execute(['id' => $articleId, 'is_active' => $isActive]);
}

function submittedString(array $values, string $key): string
{
    $value = $values[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

function positiveIdentifier(mixed $value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $identifier = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $identifier === false ? null : $identifier;
}
