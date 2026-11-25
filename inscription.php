<?php
require_once __DIR__ . '/app/bootstrap.php';
if (currentUser()) redirectTo('mon-compte.php');
$message = '';
$values = ['name' => '', 'email' => '', 'phone' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    foreach ($values as $key => $value) $values[$key] = submittedString($_POST, $key);
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';
    if ($values['name'] === '' || mb_strlen($values['name']) > 100 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 255 || ($values['phone'] !== '' && !preg_match('/\A[+0-9 ()-]{6,20}\z/', $values['phone']))) {
        $message = 'Vérifiez votre nom, votre adresse email et votre téléphone.';
    } elseif (strlen($password) < 8 || strlen($password) > 72 || $password !== $confirmation) {
        $message = 'Utilisez 8 à 72 octets et saisissez deux fois le même mot de passe.';
    } elseif (submittedString($_POST, 'accept_terms') !== '1') {
        $message = 'Prenez connaissance des conditions de réservation avant de continuer.';
    } else {
        try {
            $query = database()->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :password, 'client')");
            $query->execute(['name' => $values['name'], 'email' => mb_strtolower($values['email']), 'phone' => $values['phone'] ?: null, 'password' => password_hash($password, PASSWORD_DEFAULT)]);
            flashMessage('Votre compte est créé. Vous pouvez maintenant vous connecter.');
            redirectTo('connexion.php');
        } catch (PDOException $exception) {
            if (!in_array((string) $exception->getCode(), ['23000', '19'], true)) throw $exception;
            $message = 'Impossible de créer ce compte avec ces informations. Essayez de vous connecter.';
        }
    }
}
pageHeader('Créer mon compte');
?>
<div class="container auth-layout"><aside class="auth-aside"><p class="eyebrow">BIENVENUE À BORD</p><h2>De nouvelles<br>routes.<br>Un seul compte.</h2><p>Choisissez votre voiture, réservez vos dates et retrouvez chaque trajet au même endroit.</p><img src="<?= escape(appUrl('assets/car-placeholder.svg')) ?>" alt="" width="900" height="550"></aside><section class="auth-form"><p class="eyebrow">ÇA COMMENCE ICI</p><h1>Créons votre espace.</h1><p>Quelques informations, et la route est à vous.</p>
<?php if ($message): ?><p class="notice notice-error" role="alert"><?= escape($message) ?></p><?php endif; ?>
<form action="<?= escape(appUrl('inscription.php')) ?>" method="post"><?= csrfField() ?>
<div class="field"><label for="name">Nom complet</label><input id="name" name="name" maxlength="100" autocomplete="name" value="<?= escape($values['name']) ?>" required></div>
<div class="field"><label for="email">Adresse email</label><input id="email" name="email" type="email" maxlength="255" autocomplete="email" value="<?= escape($values['email']) ?>" required></div>
<div class="field"><label for="phone">Téléphone <span class="muted">(facultatif)</span></label><input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" value="<?= escape($values['phone']) ?>"></div>
<div class="field-grid"><div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div><div class="field"><label for="password-confirmation">Confirmer</label><input id="password-confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div></div>
<label class="field-help"><input type="checkbox" name="accept_terms" value="1" required> J'ai lu les <a class="text-link" href="<?= escape(appUrl('conditions.php')) ?>">conditions de réservation</a>.</label><button class="button button-wide" type="submit">Créer mon compte <span aria-hidden="true">↗</span></button></form><div class="auth-bottom">Déjà un compte ? <a href="<?= escape(appUrl('connexion.php')) ?>">Se connecter</a></div></section></div>
<?php pageFooter(); ?>
