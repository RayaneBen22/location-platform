<?php
require_once __DIR__ . '/app/bootstrap.php';
if (currentUser()) redirectTo('mon-compte.php');
$message = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $email = submittedString($_POST, 'email');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $connection = database();
    if (loginBlocked($connection, $email)) {
        http_response_code(429);
        $message = 'Trop de tentatives. Réessayez dans 15 minutes.';
    } else {
        $query = $connection->prepare('SELECT id, name, email, password, role, is_active, session_version FROM users WHERE email = :email');
        $query->execute(['email' => mb_strtolower($email)]);
        $account = $query->fetch();
        $valid = password_verify($password, $account['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if ($account && $account['is_active'] && $valid && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) $account['id']];
            $_SESSION['last_seen'] = time();
            $_SESSION['auth_version'] = (int) $account['session_version'];
            unset($_SESSION['csrf_token']);
            $destination = $_SESSION['after_login'] ?? 'mon-compte.php';
            unset($_SESSION['after_login']);
            redirectTo(str_starts_with($destination, 'reserver.php?') ? $destination : 'mon-compte.php');
        }
        recordLoginFailure($connection, $email);
        $message = 'Email ou mot de passe incorrect, ou compte indisponible.';
    }
}
pageHeader('Se connecter');
?>
<div class="container auth-layout"><aside class="auth-aside"><p class="eyebrow">BON RETOUR PARMI NOUS</p><h2>La prochaine<br>escapade<br>vous attend.</h2><p>Retrouvez vos réservations et gardez le cap sur votre prochain trajet.</p><img src="<?= escape(appUrl('assets/car-placeholder.svg')) ?>" alt="" width="900" height="550"></aside><section class="auth-form"><p class="eyebrow">VOTRE ESPACE PERSONNEL</p><h1>Heureux de vous revoir.</h1><p>Connectez-vous pour continuer votre route.</p>
<?php if ($message): ?><p class="notice notice-error" role="alert"><?= escape($message) ?></p><?php endif; ?>
<form action="<?= escape(appUrl('connexion.php')) ?>" method="post"><?= csrfField() ?><div class="field"><label for="email">Adresse email</label><input id="email" name="email" type="email" value="<?= escape($email) ?>" maxlength="255" autocomplete="username" required></div><div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required></div><button class="button button-wide" type="submit">Se connecter <span aria-hidden="true">↗</span></button></form><div class="auth-bottom">Première visite ? <a href="<?= escape(appUrl('inscription.php')) ?>">Créer un compte</a></div></section></div>
<?php pageFooter(); ?>
