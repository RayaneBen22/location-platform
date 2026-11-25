<?php

require_once __DIR__ . '/app/bootstrap.php';
$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$id = positiveIdentifier($input['id'] ?? null);
$start = submittedString($input, 'start');
$end = submittedString($input, 'end');

if (!currentUser()) {
    $_SESSION['after_login'] = 'reserver.php?' . http_build_query(['id' => $id, 'start' => $start, 'end' => $end]);
    redirectTo('connexion.php');
}

$user = requireUser();
$vehicle = $id ? vehicleDetails(database(), $id) : false;

if (!$vehicle) {
    http_response_code(404);
    pageHeader('Voiture introuvable');
    echo '<section class="container section"><h1>Cette voiture ne peut pas être réservée.</h1></section>';
    pageFooter();
    exit;
}

$message = '';
$range = null;
$available = false;

try {
    $range = dateRange($start, $end);
    $available = $vehicle['status'] === 'disponible' && vehicleAvailable(database(), $id, $start, $end);

    if (!$available) {
        $message = 'Cette voiture n’est pas disponible sur ces dates. Choisissez une autre période.';
    }
} catch (DomainException $exception) {
    $message = $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    try {
        $requestKey = submittedString($_POST, 'request_key');

        if (!hash_equals($_SESSION['booking_keys'][$id] ?? '', $requestKey) || $requestKey === '' || submittedString($_POST, 'accept_terms') !== '1') {
            throw new DomainException('Rechargez le formulaire et acceptez les conditions avant de confirmer.');
        }

        $booking = createBooking(database(), (int) $user['id'], $id, $start, $end, $requestKey);
        flashMessage('Demande enregistrée. Notre équipe doit maintenant confirmer votre réservation.');
        redirectTo('reservation.php?id=' . (int) $booking['id']);
    } catch (DomainException $exception) {
        $message = $exception->getMessage();
    }
} else {
    $_SESSION['booking_keys'][$id] = bin2hex(random_bytes(32));
}

pageHeader('Finaliser ma demande');
?>
<section class="container page-intro"><div class="breadcrumbs"><a href="<?= escape(appUrl('voiture.php?id=' . $id)) ?>"><?= escape(vehicleLabel($vehicle)) ?></a><span>/</span><span>Votre réservation</span></div><p class="eyebrow">ENCORE UN PETIT INSTANT</p><h1>Votre prochain trajet<br>prend forme.</h1><p class="lead">Vérifiez les détails, puis envoyez votre demande.</p></section>
<div class="container reservation-layout"><section><div class="panel"><h2>Vos informations</h2><p><strong><?= escape($user['name']) ?></strong><br><?= escape($user['email']) ?><br><?= escape($user['phone']) ?></p><a class="text-link" href="<?= escape(appUrl('mon-compte.php')) ?>">Modifier mon profil →</a></div><div class="panel"><h2>Vos dates</h2><?php if ($message): ?><p class="notice notice-error" role="alert"><?= escape($message) ?></p><?php endif; ?><form action="<?= escape(appUrl('reserver.php')) ?>" method="get"><input type="hidden" name="id" value="<?= $id ?>"><div class="field-grid"><div class="field"><label for="start">Départ</label><input type="date" id="start" name="start" value="<?= escape($start) ?>" min="<?= date('Y-m-d') ?>" required></div><div class="field"><label for="end">Retour</label><input type="date" id="end" name="end" value="<?= escape($end) ?>" min="<?= date('Y-m-d') ?>" required></div></div><button class="button button-outline" type="submit">Actualiser les dates</button></form></div></section>
<aside class="booking-panel"><p class="eyebrow">VOTRE RÉCAPITULATIF</p><h2><?= escape(vehicleLabel($vehicle)) ?></h2><p><?= escape($vehicle['category_name'] ?? 'Automobile') ?></p><?php if (indicativePriceLabel($vehicle)): ?><p class="price-notice"><?= escape(indicativePriceLabel($vehicle)) ?></p><?php endif; ?><div class="booking-summary"><div class="summary-row"><span>Prix journalier</span><strong><?= escape(money($vehicle['daily_price'])) ?></strong></div><?php if ($range): ?><div class="summary-row"><span>Durée</span><strong><?= $range['days'] ?> jour(s)</strong></div><div class="summary-row summary-total"><span>Total</span><span><?= escape(money(decimalAmount(priceInCents((string) $vehicle['daily_price']) * $range['days']))) ?></span></div><?php endif; ?></div>
<form action="<?= escape(appUrl('reserver.php')) ?>" method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="start" value="<?= escape($start) ?>"><input type="hidden" name="end" value="<?= escape($end) ?>"><input type="hidden" name="request_key" value="<?= escape($_SESSION['booking_keys'][$id] ?? '') ?>"><label class="field-help"><input type="checkbox" name="accept_terms" value="1" required> J’ai lu les <a class="text-link" href="<?= escape(appUrl('conditions.php')) ?>">conditions de réservation</a>.</label><p class="field-help">Aucun paiement prélevé. La demande bloque ces dates en attendant la décision de notre équipe.</p><button class="button button-wide" type="submit" <?= !$available ? 'disabled' : '' ?>>Envoyer ma demande <span aria-hidden="true">↗</span></button></form></aside></div>
<?php pageFooter(); ?>
