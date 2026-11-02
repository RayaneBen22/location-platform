<?php

require_once __DIR__ . '/config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) {
        $message = 'Le nom doit contenir entre 1 et 100 caractères.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 255
    ) {
        $message = 'Veuillez saisir un email valide.';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $message = 'Le mot de passe doit contenir entre 8 et 72 octets.';
    } else {
        $query = $pdo->prepare(
            'SELECT id FROM users WHERE email = :email'
        );

        $query->execute(['email' => $email]);

        if ($query->fetch()) {
            $message = 'Cet email est déjà utilisé.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $query = $pdo->prepare(
                'INSERT INTO users (name, email, password, role)
                 VALUES (:name, :email, :password, :role)'
            );

            $query->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => 'client',
            ]);

            $message = 'Compte créé avec succès !';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
</head>
<body>
    <h1>Créer un compte</h1>
    <?php if ($message !== ''): ?>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

    <form action="inscription.php" method="post">
        <div>
            <label for="name">Nom complet</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                required
            >
        </div>

        <div>
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="255"
                required
            >
        </div>

        <div>
            <label for="password">Mot de passe</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >
        </div>

        <button type="submit">Créer mon compte</button>
    </form>

    <p><a href="index.php">Retour à l’accueil</a></p>
</body>
</html>