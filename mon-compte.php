<?php

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon compte</title>
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
    <main class="page page--compact">
        <div class="panel">
            <h1>
                Bonjour
                <?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?> !
            </h1>

            <p>Bienvenue dans votre espace personnel.</p>

            <a href="index.php">Retour à l’accueil</a>
            <?php if ($_SESSION['user']['role'] === 'agent'): ?>
                <p>
                    <a href="agent/index.php">Accéder à l’espace agent</a>
                </p>
            <?php endif; ?>
            <form action="deconnexion.php" method="post">
                <button type="submit">Se déconnecter</button>
            </form>
        </div>
    </main>
</body>
</html>
