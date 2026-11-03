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
</body>
</html>