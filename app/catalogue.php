<?php

require_once __DIR__ . '/vehicle-details.php';

function fuelTypes(): array
{
    return ['essence' => 'Essence', 'diesel' => 'Diesel', 'hybride' => 'Hybride', 'electrique' => 'Électrique'];
}

function transmissions(): array
{
    return ['manuelle' => 'Manuelle', 'automatique' => 'Automatique'];
}

function vehicleCategories(PDO $connection): array
{
    return $connection->query('SELECT id, name FROM vehicle_categories ORDER BY name')->fetchAll();
}

function vehicleSelect(): string
{
    return 'SELECT a.*, m.name AS model_name, b.name AS brand_name, b.id AS brand_id, c.name AS category_name,
        (SELECT i.id FROM article_images i WHERE i.article_id = a.id AND i.is_active = 1 ORDER BY CASE WHEN i.image_path LIKE \'assets/images/voitures/%\' THEN 0 ELSE 1 END, i.id LIMIT 1) AS photo_id
        FROM articles a LEFT JOIN vehicle_models m ON m.id = a.vehicle_model_id
        LEFT JOIN vehicle_brands b ON b.id = m.brand_id
        LEFT JOIN vehicle_categories c ON c.id = a.vehicle_category_id';
}

function vehicleDetails(PDO $connection, int $id, bool $includeHidden = false): array|false
{
    $query = $connection->prepare(vehicleSelect() . ' WHERE a.id = :id' . ($includeHidden ? '' : ' AND a.is_active = 1 AND m.id IS NOT NULL'));
    $query->execute(['id' => $id]);

    return $query->fetch();
}

function publicVehicleIdForModel(PDO $connection, string $brand, string $model): ?int
{
    $query = $connection->prepare(
        "SELECT a.id
         FROM articles a
         JOIN vehicle_models m ON m.id = a.vehicle_model_id
         JOIN vehicle_brands b ON b.id = m.brand_id
         WHERE a.is_active = 1 AND b.name = :brand AND m.name = :model
         ORDER BY CASE WHEN a.status = 'disponible' THEN 0 ELSE 1 END, a.id
         LIMIT 1"
    );
    $query->execute(['brand' => $brand, 'model' => $model]);
    $id = $query->fetchColumn();

    return $id === false ? null : (int) $id;
}

function cataloguePage(PDO $connection, array $input, int $perPage = 9): array
{
    $conditions = ['a.is_active = 1', 'm.id IS NOT NULL'];
    $parameters = [];
    $filters = [];

    foreach (['brand' => 'b.id', 'model' => 'm.id', 'category' => 'a.vehicle_category_id', 'seats' => 'a.seats'] as $name => $column) {
        $value = submittedString($input, $name);
        $filters[$name] = $value;

        if ($value !== '') {
            $id = positiveIdentifier($value);

            if ($id === null || ($name === 'seats' && ($id < 2 || $id > 9))) {
                throw new DomainException('Un filtre contient une valeur invalide.');
            }

            $conditions[] = $column . ' = :' . $name;
            $parameters[$name] = $id;
        }
    }

    foreach (['fuel' => ['a.fuel_type', fuelTypes()], 'transmission' => ['a.transmission', transmissions()]] as $name => [$column, $allowed]) {
        $value = submittedString($input, $name);
        $filters[$name] = $value;

        if ($value !== '') {
            if (!isset($allowed[$value])) {
                throw new DomainException('Choisissez un carburant ou une transmission proposé.');
            }

            $conditions[] = $column . ' = :' . $name;
            $parameters[$name] = $value;
        }
    }

    foreach (['min_price' => '>=', 'max_price' => '<='] as $name => $operator) {
        $value = submittedString($input, $name);
        $filters[$name] = $value;

        if ($value !== '') {
            $cents = $value === '0' ? 0 : priceInCents($value);
            $conditions[] = 'a.daily_price ' . $operator . ' :' . $name;
            $parameters[$name] = decimalAmount($cents);
        }
    }

    if (isset($parameters['min_price'], $parameters['max_price']) && (float) $parameters['min_price'] > (float) $parameters['max_price']) {
        throw new DomainException('Le budget maximum doit être supérieur au minimum.');
    }

    $filters['q'] = submittedString($input, 'q');

    if (mb_strlen($filters['q']) > 100) {
        throw new DomainException('La recherche ne peut pas dépasser 100 caractères.');
    }

    if ($filters['q'] !== '') {
        $conditions[] = '(b.name LIKE :brand_query OR m.name LIKE :model_query OR a.version LIKE :version_query)';

        foreach (['brand_query', 'model_query', 'version_query'] as $placeholder) {
            $parameters[$placeholder] = '%' . $filters['q'] . '%';
        }
    }

    $filters['start'] = submittedString($input, 'start');
    $filters['end'] = submittedString($input, 'end');

    if ($filters['start'] !== '' || $filters['end'] !== '') {
        dateRange($filters['start'], $filters['end']);
        $conditions[] = "a.status = 'disponible' AND NOT EXISTS (SELECT 1 FROM locations l WHERE l.article_id = a.id AND l.status IN ('en_attente', 'confirmee', 'en_cours') AND l.start_date < :available_end AND l.end_date > :available_start)";
        $parameters['available_start'] = $filters['start'];
        $parameters['available_end'] = $filters['end'];
    }

    $filters['sort'] = submittedString($input, 'sort');
    $sort = ['price_asc' => 'a.daily_price ASC, a.id DESC', 'price_desc' => 'a.daily_price DESC, a.id DESC', 'recent' => 'a.id DESC'][$filters['sort']] ?? 'b.name, m.name, a.id DESC';
    $where = ' WHERE ' . implode(' AND ', $conditions);
    $count = $connection->prepare('SELECT COUNT(*) FROM articles a LEFT JOIN vehicle_models m ON m.id = a.vehicle_model_id LEFT JOIN vehicle_brands b ON b.id = m.brand_id' . $where);
    $count->execute($parameters);
    $total = (int) $count->fetchColumn();
    $page = min(positiveIdentifier($input['page'] ?? 1) ?? 1, max(1, (int) ceil($total / $perPage)));
    $query = $connection->prepare(vehicleSelect() . $where . ' ORDER BY ' . $sort . ' LIMIT :page_limit OFFSET :page_offset');

    foreach ($parameters as $key => $value) {
        $query->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    $query->bindValue(':page_limit', $perPage, PDO::PARAM_INT);
    $query->bindValue(':page_offset', ($page - 1) * $perPage, PDO::PARAM_INT);
    $query->execute();

    return ['vehicles' => $query->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)), 'filters' => $filters];
}

function saveVehicleDetails(PDO $connection, ?int $id, array $input): int
{
    $modelId = positiveIdentifier($input['vehicle_model_id'] ?? null);
    $categoryId = positiveIdentifier($input['vehicle_category_id'] ?? null);
    $price = submittedString($input, 'daily_price');
    priceInCents($price);
    $model = $modelId ? vehicleModel($connection, $modelId) : false;
    $category = $connection->prepare('SELECT id FROM vehicle_categories WHERE id = :id');
    $category->execute(['id' => $categoryId]);
    $specifications = optionalVehicleSpecifications($input);
    $description = submittedString($input, 'description');
    $version = submittedString($input, 'version');
    $status = submittedString($input, 'status');
    $indicative = array_key_exists('price_is_indicative', $input) ? submittedString($input, 'price_is_indicative') : null;

    if (!$model || !$category->fetch() || mb_strlen($description) > 5000 || mb_strlen($version) > 60 || !in_array($status, ['disponible', 'en_location', 'en_maintenance'], true) || ($indicative !== null && !in_array($indicative, ['0', '1'], true))) {
        throw new DomainException('Vérifiez le modèle, la catégorie et les caractéristiques de la voiture.');
    }

    return databaseTransaction($connection, function () use ($connection, $id, $model, $modelId, $categoryId, $price, $specifications, $description, $version, $status, $indicative): int {
        $previous = null;

        if ($id !== null) {
            $lock = $connection->prepare('SELECT id, price_is_indicative FROM articles WHERE id = :id' . lockSuffix($connection));
            $lock->execute(['id' => $id]);
            $previous = $lock->fetch();

            if (!$previous) {
                throw new DomainException('Voiture introuvable.');
            }
        }

        $parameters = array_merge(['vehicle_model_id' => $modelId, 'vehicle_category_id' => $categoryId, 'daily_price' => $price, 'name' => $model['brand_name'] . ' ' . $model['model_name'], 'version' => $version ?: null, 'description' => $description ?: null, 'status' => $status, 'price_is_indicative' => $indicative === null ? (int) ($previous['price_is_indicative'] ?? 1) : (int) $indicative], $specifications);

        if ($id === null) {
            $category = $connection->prepare('SELECT id FROM categories WHERE name = :name');
            $category->execute(['name' => 'Voitures']);
            $parameters['category_id'] = $category->fetchColumn();

            if (!$parameters['category_id']) {
                throw new DomainException('Appliquez les migrations automobiles avant de continuer.');
            }

            $query = $connection->prepare('INSERT INTO articles (' . implode(', ', array_keys($parameters)) . ') VALUES (:' . implode(', :', array_keys($parameters)) . ')');
        } else {
            $assignments = array_map(fn (string $key): string => $key . ' = :' . $key, array_keys($parameters));
            $parameters['id'] = $id;
            $query = $connection->prepare('UPDATE articles SET ' . implode(', ', $assignments) . ' WHERE id = :id');
        }

        $query->execute($parameters);

        return $id ?? (int) $connection->lastInsertId();
    });
}

function saveReference(PDO $connection, string $kind, ?int $id, string $name, ?int $brandId = null): void
{
    $table = ['brand' => 'vehicle_brands', 'model' => 'vehicle_models', 'category' => 'vehicle_categories'][$kind] ?? null;

    if ($table === null || $name === '' || mb_strlen($name) > ($kind === 'model' ? 80 : 60)) {
        throw new DomainException('Le nom est obligatoire et doit respecter la longueur maximale.');
    }

    $parameters = ['name' => $name];

    if ($kind === 'model') {
        $brand = $connection->prepare('SELECT id FROM vehicle_brands WHERE id = :id');
        $brand->execute(['id' => $brandId]);

        if (!$brand->fetch()) {
            throw new DomainException('Choisissez une marque existante.');
        }

        $parameters['brand_id'] = $brandId;
    }

    try {
        if ($id === null) {
            $query = $connection->prepare('INSERT INTO ' . $table . ' (' . implode(', ', array_keys($parameters)) . ') VALUES (:' . implode(', :', array_keys($parameters)) . ')');
        } else {
            $existing = $connection->prepare('SELECT id FROM ' . $table . ' WHERE id = :id');
            $existing->execute(['id' => $id]);

            if (!$existing->fetch()) {
                throw new DomainException('Référence introuvable.');
            }

            $assignments = array_map(fn (string $key): string => $key . ' = :' . $key, array_keys($parameters));
            $parameters['id'] = $id;
            $query = $connection->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = :id');
        }

        $query->execute($parameters);
    } catch (PDOException $error) {
        if (in_array((string) $error->getCode(), ['23000', '19'], true)) {
            throw new DomainException('Cette référence existe déjà pour cette marque.');
        }

        throw $error;
    }
}
