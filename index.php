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
    <link rel="stylesheet" href="assets/site.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Navigation principale">
            <a class="brand" href="index.php">Plateforme de location</a>
            <div class="nav-links">
                <a href="catalogue.php">Catalogue</a>
                <a href="mon-compte.php">Mon compte</a>
            </div>
        </nav>
    </header>
    <main class="page">
        <header class="hero">
            <h1>Nos catégories</h1>
            <a class="button" href="catalogue.php">Voir les articles à louer</a>
        </header>

        <?php if (empty($categories)): ?>
            <p class="notice">Aucune catégorie disponible.</p>
        <?php else: ?>
            <div class="article-grid">
                <?php foreach ($categories as $category): ?>
                    <article class="article-card">
                        <h2>
                            <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <p>
                            <?= htmlspecialchars($category['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
