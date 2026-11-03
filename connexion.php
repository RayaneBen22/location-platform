<?php

session_start();

require_once __DIR__ . '/config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $message = 'Veuillez remplir correctement les deux champs.';
    } else {
        $query = $pdo->prepare(
            'SELECT id, name, email, password, role
             FROM users
             WHERE email = :email'
        );

        $query->execute(['email' => $email]);

        $user = $query->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
            ];

            header('Location: mon-compte.php');
            exit;
        } else {
            $message = 'Email ou mot de passe incorrect.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
</head>
<body>
    <h1>Se connecter</h1>
    <?php if ($message !== ''): ?>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

    <form action="connexion.php" method="post">
        <div>
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="username"
                required
            >
        </div>

        <div>
            <label for="password">Mot de passe</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >
        </div>

        <button type="submit">Se connecter</button>
    </form>

    <p><a href="inscription.php">Créer un compte</a></p>
    <p><a href="index.php">Retour à l’accueil</a></p>
</body>
</html>