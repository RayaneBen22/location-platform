<?php

require_once __DIR__ . '/config/database.php';

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$categoryId = filter_input(INPUT_GET, 'categorie', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

$sql = 'SELECT articles.name, articles.description, articles.daily_price,
               categories.name AS category_name
        FROM articles
        JOIN categories ON categories.id = articles.category_id
        WHERE articles.is_active = 1';
$params = [];

if ($categoryId) {
    $sql .= ' AND articles.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

$query = $pdo->prepare($sql . ' ORDER BY articles.name');
$query->execute($params);
$articles = $query->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue</title>
    <link rel="stylesheet" href="assets/catalogue.css">
</head>
<body>
    <main class="catalogue">
        <header class="catalogue-header">
            <a href="index.php">← Accueil</a>
            <h1>Nos articles à louer</h1>
        </header>

        <form class="catalogue-filter" method="get" action="catalogue.php">
            <label for="categorie">Catégorie</label>
            <div class="filter-controls">
                <select id="categorie" name="categorie">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) $category['id'] === $categoryId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filtrer</button>
            </div>
        </form>

        <?php if (empty($articles)): ?>
            <p><?= $categoryId ? 'Aucun article dans cette catégorie.' : 'Aucun article disponible dans le catalogue.' ?></p>
        <?php else: ?>
            <div class="article-grid">
                <?php foreach ($articles as $article): ?>
                    <article class="article-card">
                        <p class="article-category">
                            <?= htmlspecialchars($article['category_name'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <h2><?= htmlspecialchars($article['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if ($article['description'] !== null && $article['description'] !== ''): ?>
                            <p><?= htmlspecialchars($article['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <p class="article-price">
                            Prix par jour :
                            <strong><?= number_format((float) $article['daily_price'], 2, ',', ' ') ?></strong>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
