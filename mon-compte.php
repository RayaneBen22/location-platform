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
</head>
<body>
    <h1>
        Bonjour
        <?= htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8') ?> !
    </h1>

    <p>Bienvenue dans votre espace personnel.</p>

    <a href="index.php">Retour à l’accueil</a>
    <form action="deconnexion.php" method="post">
    <button type="submit">Se déconnecter</button>
</form>
</body>
</html>