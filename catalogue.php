<?php

require_once __DIR__ . '/config/database.php';

$query = $pdo->query(
    'SELECT name, description, daily_price
     FROM articles
     WHERE is_active = 1
     ORDER BY name'
);

$articles = $query->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue</title>
</head>
<body>
    <h1>Nos articles à louer</h1>

    <a href="index.php">Accueil</a>

    <?php if (empty($articles)): ?>
        <p>Aucun article disponible dans le catalogue.</p>
    <?php else: ?>
        <?php foreach ($articles as $article): ?>
            <article>
                <h2>
                    <?= htmlspecialchars($article['name'], ENT_QUOTES, 'UTF-8') ?>
                </h2>

                <p>
                    <?= htmlspecialchars($article['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>

                <p>
                    Prix par jour :
                    <?= number_format((float) $article['daily_price'], 2, ',', ' ') ?>
                </p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>