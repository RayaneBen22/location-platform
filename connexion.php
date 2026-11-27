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
<div class="container auth-layout login-layout">
    <section class="auth-form" aria-labelledby="login-heading">
        <p class="eyebrow">VOTRE ESPACE PERSONNEL</p>
        <h1 id="login-heading">Heureux de vous revoir.</h1>
        <p>Connectez-vous pour continuer votre route.</p>
        <?php if ($message): ?><p class="notice notice-error" role="alert"><?= escape($message) ?></p><?php endif; ?>
        <form action="<?= escape(appUrl('connexion.php')) ?>" method="post">
            <?= csrfField() ?>
            <div class="field">
                <label for="email">Adresse email</label>
                <input id="email" name="email" type="email" value="<?= escape($email) ?>" maxlength="255" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="password">Mot de passe</label>
                <input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required>
            </div>
            <button class="button button-wide" type="submit">Se connecter <span aria-hidden="true">↗</span></button>
        </form>
        <div class="auth-bottom">Première visite ? <a href="<?= escape(appUrl('inscription.php')) ?>">Créer un compte</a></div>
    </section>
    <aside class="login-panel" aria-labelledby="benefits-heading">
        <p class="eyebrow">POURQUOI VOUS CONNECTER ?</p>
        <h2 id="benefits-heading">Votre espace en un clin d’œil.</h2>
        <p class="login-intro">Connectez-vous à votre compte Rayane Cars et profitez d’une expérience simple, rapide et personnalisée.</p>
        <ul class="login-benefits" role="list">
            <li>
                <svg class="benefit-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <rect x="3.5" y="5" width="17" height="16" rx="3"/>
                    <path d="M7.5 3v4m9-4v4m-13 3h17m-12 5 2 2 4-4"/>
                </svg>
                <div>
                    <h3>Gérez vos réservations</h3>
                    <p>Consultez, modifiez ou annulez vos réservations en toute simplicité.</p>
                </div>
            </li>
            <li>
                <svg class="benefit-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M19 10c0 4.7-7 10.5-7 10.5S5 14.7 5 10a7 7 0 1 1 14 0Z"/>
                    <circle cx="12" cy="10" r="2.5"/>
                </svg>
                <div>
                    <h3>Consultez vos trajets et dates</h3>
                    <p>Retrouvez l’historique de vos locations et vos prochaines escapades.</p>
                </div>
            </li>
            <li>
                <svg class="benefit-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="8" r="3.5"/>
                    <path d="M4.5 20c.6-3.6 3.3-5.6 7.5-5.6s6.9 2 7.5 5.6"/>
                </svg>
                <div>
                    <h3>Accédez rapidement à vos informations</h3>
                    <p>Mettez à jour vos coordonnées et vos préférences en quelques clics.</p>
                </div>
            </li>
        </ul>
    </aside>
</div>
<?php pageFooter(); ?>
