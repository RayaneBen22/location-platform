<?php
require_once __DIR__ . '/app/bootstrap.php';
$user = requireUser();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $name = submittedString($_POST, 'name');
    $phone = submittedString($_POST, 'phone');
    $password = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $currentPassword = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
    if ($name === '' || mb_strlen($name) > 100 || ($phone !== '' && !preg_match('/\A[+0-9 ()-]{6,20}\z/', $phone))) {
        $error = 'Vérifiez votre nom et votre téléphone.';
    } elseif ($password !== '' && (strlen($password) < 8 || strlen($password) > 72)) {
        $error = 'Le nouveau mot de passe doit contenir entre 8 et 72 octets.';
    } else {
        $parameters = ['name' => $name, 'phone' => $phone ?: null, 'id' => $user['id']];
        $assignments = 'name = :name, phone = :phone';
        if ($password !== '') {
            $query = database()->prepare('SELECT password FROM users WHERE id = :id');
            $query->execute(['id' => $user['id']]);
            if (!password_verify($currentPassword, $query->fetchColumn())) {
                $error = 'Le mot de passe actuel est incorrect.';
            } else {
                $assignments .= ', password = :password, session_version = session_version + 1';
                $parameters['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }
        if ($error === '') {
            $query = database()->prepare('UPDATE users SET ' . $assignments . ' WHERE id = :id');
            $query->execute($parameters);
            if ($password !== '') {
                session_regenerate_id(true);
                $_SESSION['auth_version'] = (int) $user['session_version'] + 1;
                unset($_SESSION['csrf_token']);
            }
            flashMessage('Votre profil a été mis à jour.');
            redirectTo('mon-compte.php');
        }
    }
}
$bookings = clientBookings(database(), (int) $user['id']);
pageHeader('Mon espace');
?>
<section class="container page-intro"><p class="eyebrow">VOTRE ESPACE PERSONNEL</p><h1>Bonjour <?= escape($user['name']) ?>.</h1><p class="lead">Vos informations, vos réservations, vos prochains départs.</p></section>
<div class="container profile-grid"><aside><section class="panel"><h2>Mon profil</h2>
<?php if ($error): ?><p class="notice notice-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
<form action="<?= escape(appUrl('mon-compte.php')) ?>" method="post"><?= csrfField() ?><div class="field"><label for="profile-name">Nom complet</label><input id="profile-name" name="name" maxlength="100" value="<?= escape($user['name']) ?>" autocomplete="name" required></div><div class="field"><label for="profile-email">Adresse email</label><input id="profile-email" type="email" value="<?= escape($user['email']) ?>" readonly></div><div class="field"><label for="profile-phone">Téléphone</label><input id="profile-phone" name="phone" type="tel" maxlength="20" value="<?= escape($user['phone']) ?>" autocomplete="tel"></div><details><summary>Changer mon mot de passe</summary><div class="field"><label for="current-password">Mot de passe actuel</label><input id="current-password" name="current_password" type="password" maxlength="72" autocomplete="current-password"></div><div class="field"><label for="new-password">Nouveau mot de passe</label><input id="new-password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password"></div></details><button class="button button-wide" type="submit">Enregistrer</button></form></section><section class="panel"><?php if ($user['role'] === 'agent'): ?><a class="text-link" href="<?= escape(appUrl('agent/index.php')) ?>">Administration →</a><?php endif; ?><form action="<?= escape(appUrl('deconnexion.php')) ?>" method="post"><?= csrfField() ?><button class="button button-outline button-wide" type="submit">Se déconnecter</button></form></section></aside>
<section><div class="section-heading"><h2>Mes réservations <span class="muted">(<?= count($bookings) ?>)</span></h2><a class="text-link" href="<?= escape(appUrl('catalogue.php')) ?>">Un nouveau trajet ↗</a></div>
<?php if (!$bookings): ?><div class="empty-state"><span class="empty-symbol" aria-hidden="true">↗</span><h3>Tout commence par une envie.</h3><p>Vos réservations apparaîtront ici. Trouvez la voiture de votre prochain trajet.</p><a class="button" href="<?= escape(appUrl('catalogue.php')) ?>">Explorer les voitures</a></div><?php endif; ?>
<?php foreach ($bookings as $booking): ?><article class="booking-item"><div class="booking-item-head"><div><p class="eyebrow"><?= escape($booking['reference_code'] ?? 'Historique #' . $booking['id']) ?></p><h3><?= escape($booking['vehicle_label'] ?? 'Réservation historique') ?></h3></div><span class="badge"><?= escape(statusLabel($booking['status'])) ?></span></div><p>Du <?= escape($booking['start_date']) ?> au <?= escape($booking['end_date']) ?></p><div class="booking-item-bottom"><strong><?= escape(money($booking['total_price'])) ?></strong><a class="text-link" href="<?= escape(appUrl('reservation.php?id=' . (int) $booking['id'])) ?>">Voir ma réservation ↗</a></div></article><?php endforeach; ?>
</section></div>
<?php pageFooter(); ?>
