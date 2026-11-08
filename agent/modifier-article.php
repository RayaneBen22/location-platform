<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../connexion.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'agent') {
    http_response_code(403);
    exit('Accès réservé aux agents.');
}

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Identifiant d’article invalide.');
}

$query = $pdo->prepare(
    'SELECT name, category_id, daily_price FROM articles WHERE id = :id'
);
$query->execute(['id' => $id]);
$article = $query->fetch();

if (!$article) {
    http_response_code(404);
    exit('Article introuvable.');
}

$categories = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name'
)->fetchAll();

$name = $article['name'];
$categoryId = (int) $article['category_id'];
$dailyPrice = $article['daily_price'];
$message = '';

$_SESSION['article_token'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['article_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['article_token'], $token)) {
        http_response_code(403);
        exit('Formulaire invalide. Recharge la page et réessaie.');
    }

    $name = trim(filter_input(INPUT_POST, 'name') ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $dailyPrice = filter_input(INPUT_POST, 'daily_price') ?? '';

    $categoryIds = array_map('intval', array_column($categories, 'id'));

    if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
        $message = 'Le nom doit contenir entre 1 et 150 caractères.';
    } elseif (!in_array($categoryId, $categoryIds, true)) {
        $message = 'Choisis une catégorie valide.';
    } elseif (
        !preg_match('/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/', $dailyPrice)
        || (float) $dailyPrice <= 0
    ) {
        $message = 'Saisis un prix positif avec au maximum deux décimales.';
    } else {
        $update = $pdo->prepare(
            'UPDATE articles
             SET name = :name, category_id = :category_id, daily_price = :daily_price
             WHERE id = :id'
        );

        $update->execute([
            'name' => $name,
            'category_id' => $categoryId,
            'daily_price' => $dailyPrice,
            'id' => $id,
        ]);

        header('Location: index.php', true, 303);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un article</title>
</head>
<body>
    <h1>Modifier un article</h1>

    <?php if ($message !== ''): ?>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="modifier-article.php?id=<?= $id ?>" method="post">
        <input type="hidden" name="article_token"
               value="<?= htmlspecialchars($_SESSION['article_token'], ENT_QUOTES, 'UTF-8') ?>">

        <p>
            <label for="name">Nom de l’article</label>
            <input type="text" id="name" name="name" maxlength="150"
                   value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
        </p>

        <p>
            <label for="category_id">Catégorie</label>
            <select id="category_id" name="category_id" required>
                <option value="">Choisir une catégorie</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"
                        <?= (int) $category['id'] === $categoryId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="daily_price">Prix par jour</label>
            <input type="number" id="daily_price" name="daily_price"
                   min="0.01" max="99999999.99" step="0.01"
                   value="<?= htmlspecialchars($dailyPrice, ENT_QUOTES, 'UTF-8') ?>" required>
        </p>

        <button type="submit">Enregistrer</button>
    </form>

    <p><a href="index.php">Retour aux articles</a></p>
</body>
</html>