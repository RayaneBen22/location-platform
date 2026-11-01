<?php

require_once __DIR__ . '/config/database.php';

$query = $pdo->query('SELECT id, name, description FROM categories ORDER BY name');

$categories = $query->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plateforme de location</title>
</head>
<body>
    <h1>Nos catégories</h1>

    <?php if (empty($categories)): ?>
        <p>Aucune catégorie disponible.</p>
    <?php else: ?>
        <?php foreach ($categories as $category): ?>
            <article>
                <h2>
                    <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <p>
                    <?= htmlspecialchars($category['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>