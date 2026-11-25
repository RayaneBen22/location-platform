<?php

require_once __DIR__ . '/app/bootstrap.php';
$user = requireUser();
$id = positiveIdentifier($_GET['id'] ?? null);
$query = database()->prepare('SELECT l.*, a.vehicle_model_id FROM locations l INNER JOIN articles a ON a.id = l.article_id WHERE l.id = :id AND l.user_id = :user_id');
$query->execute(['id' => $id, 'user_id' => $user['id']]);
$booking = $query->fetch();

if (!$booking) {
    http_response_code(404);
    pageHeader('Réservation introuvable');
    echo '<section class="container section"><h1>Réservation introuvable.</h1></section>';
    pageFooter();
    exit;
}

pageHeader('Ma réservation');
?>
<section class="container page-intro"><p class="eyebrow">VOTRE ESPACE PERSONNEL</p><h1>Votre réservation.</h1><p><?= escape($booking['reference_code'] ?? 'Historique #' . $booking['id']) ?></p></section>
<div class="container prose"><div class="panel"><span class="badge"><?= escape(statusLabel($booking['status'])) ?></span><h2><?= escape($booking['vehicle_label'] ?? 'Réservation historique') ?></h2><div class="summary-row"><span>Départ</span><strong><?= escape($booking['start_date']) ?></strong></div><div class="summary-row"><span>Retour</span><strong><?= escape($booking['end_date']) ?></strong></div><div class="summary-row"><span>Prix journalier réservé</span><strong><?= escape(money($booking['daily_price'])) ?></strong></div><div class="summary-row summary-total"><span>Total de la réservation</span><span><?= escape(money($booking['total_price'])) ?></span></div><p class="field-help">Le statut « En attente » est une demande en cours d’examen. Seul le statut « Confirmée » confirme votre réservation.</p><?php if ($booking['start_date'] > date('Y-m-d') && in_array($booking['status'], ['en_attente', 'confirmee'], true)): ?><form action="<?= escape(appUrl('annuler-reservation.php')) ?>" method="post" data-confirm="Annuler cette réservation ?"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $booking['id'] ?>"><button class="button button-outline" type="submit">Annuler la réservation</button></form><?php endif; ?></div><p><a class="text-link" href="<?= escape(appUrl('mon-compte.php')) ?>">← Revenir à mes réservations</a></p></div>
<?php pageFooter(); ?>
