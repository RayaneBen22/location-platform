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

$message = '';
$_SESSION['article_token'] ??= bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['article_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['article_token'], $token)) {
        http_response_code(403);
        exit('Formulaire invalide. Recharge la page et réessaie.');
    }

    $name = $_POST['name'] ?? '';
    $name = is_string($name) ? trim($name) : '';
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $dailyPrice = $_POST['daily_price'] ?? '';

    if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
        $message = 'Le nom doit contenir entre 1 et 150 caractères.';
    } elseif (!$categoryId || $categoryId < 1) {
        $message = 'Choisis une catégorie.';
    } elseif (
        !is_string($dailyPrice)
        || !preg_match('/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/', $dailyPrice)
        || (float) $dailyPrice <= 0
    ) {
        $message = 'Saisis un prix positif avec au maximum deux décimales.';
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO articles (category_id, name, daily_price)
             SELECT id, :name, :daily_price
             FROM categories
             WHERE id = :category_id'
        );

        $insert->execute([
            'name' => $name,
            'daily_price' => $dailyPrice,
            'category_id' => $categoryId,
        ]);

        if ($insert->rowCount() === 1) {
            header('Location: index.php', true, 303);
            exit;
        }

        $message = 'Cette catégorie n’existe plus. Choisis-en une autre.';
    }
}

$articles = $pdo->query(
    'SELECT id, name, daily_price, status, is_active
     FROM articles
     ORDER BY name'
)->fetchAll();

$categories = $pdo->query(
    'SELECT id, name FROM categories ORDER BY name'
)->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace agent</title>
</head>
<body>
    <h1>Espace agent</h1>

    <p>
        Bonjour
        <?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?> !
    </p>

    <p>Vous pourrez gérer ici les articles et les réservations.</p>

    <a href="../mon-compte.php">Mon compte</a>

    <h2>Ajouter un article</h2>

    <?php if ($message !== ''): ?>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="index.php" method="post">
        <input type="hidden" name="article_token"
               value="<?= htmlspecialchars($_SESSION['article_token'], ENT_QUOTES, 'UTF-8') ?>">

        <p>
            <label for="name">Nom de l’article</label>
            <input type="text" id="name" name="name" maxlength="150" required>
        </p>

        <p>
            <label for="category_id">Catégorie</label>
            <select id="category_id" name="category_id" required>
                <option value="">Choisir une catégorie</option>

                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>">
                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="daily_price">Prix par jour</label>
            <input type="number" id="daily_price" name="daily_price"
                   min="0.01" max="99999999.99" step="0.01" required>
        </p>

        <button type="submit">Ajouter l’article</button>
    </form>

    <h2>Articles du catalogue</h2>

    <?php if (empty($articles)): ?>
        <p>Aucun article enregistré.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($articles as $article): ?>
                <li>
                    <?= htmlspecialchars($article['name'], ENT_QUOTES, 'UTF-8') ?>
                    —
                    <?= number_format((float) $article['daily_price'], 2, ',', ' ') ?> par jour
                    —
                    <?= htmlspecialchars(str_replace('_', ' ', $article['status']), ENT_QUOTES, 'UTF-8') ?>
                    —
                    <?= $article['is_active'] ? 'Actif' : 'Désactivé' ?>
                    <a href="modifier-article.php?id=<?= (int) $article['id'] ?>">Modifier</a>
                    <form action="changer-activation.php" method="post">
    <input type="hidden" name="article_token"
           value="<?= htmlspecialchars($_SESSION['article_token'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
    <input type="hidden" name="is_active" value="<?= $article['is_active'] ? '0' : '1' ?>">

    <button type="submit">
        <?= $article['is_active'] ? 'Désactiver' : 'Réactiver' ?>
    </button>
</form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>