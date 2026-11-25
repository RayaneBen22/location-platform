<?php

require_once __DIR__ . '/../app/agent.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = positiveIdentifier($_POST['id'] ?? null);

        if (!$id) {
            throw new DomainException('Réservation introuvable.');
        }

        changeBookingStatus(database(), $id, submittedString($_POST, 'status'), (int) $agent['id'], true);
        flashMessage('Le statut de la réservation a été mis à jour.');
        redirectTo('agent/reservations.php');
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    }
}

$status = submittedString($_GET, 'status');
$conditions = [];
$parameters = [];

if (isset(bookingTransitions()[$status])) {
    $conditions[] = 'l.status = :status';
    $parameters['status'] = $status;
}

$selectedId = positiveIdentifier($_GET['id'] ?? null);

if ($selectedId) {
    $conditions[] = 'l.id = :id';
    $parameters['id'] = $selectedId;
}

$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$count = database()->prepare('SELECT COUNT(*) FROM locations l' . $where);
$count->execute($parameters);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / 25));
$page = min(positiveIdentifier($_GET['page'] ?? 1) ?? 1, $pages);
$query = database()->prepare('SELECT l.*, u.name AS client_name, u.email AS client_email, u.phone AS client_phone FROM locations l INNER JOIN users u ON u.id = l.user_id' . $where . ' ORDER BY l.created_at DESC, l.id DESC LIMIT :limit OFFSET :offset');

foreach ($parameters as $key => $value) {
    $query->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}

$query->bindValue(':limit', 25, PDO::PARAM_INT);
$query->bindValue(':offset', ($page - 1) * 25, PDO::PARAM_INT);
$query->execute();
$bookings = $query->fetchAll();
pageHeader('Gestion des réservations');
?>
<section class="container page-intro"><p class="eyebrow">ESPACE ÉQUIPE</p><h1>Les prochains départs.</h1><p class="lead">Examinez les demandes et accompagnez chaque réservation.</p></section>
<div class="container section-compact"><?php agentNavigation('reservations.php'); ?><?php if ($error): ?><p class="notice notice-error" role="alert"><?= escape($error) ?></p><?php endif; ?><form class="inline-actions" action="<?= escape(appUrl('agent/reservations.php')) ?>" method="get"><div class="field"><label for="reservation-status">Filtrer par statut</label><select id="reservation-status" name="status"><option value="">Tous les statuts</option><?php choiceOptions(array_combine(array_keys(bookingTransitions()), array_map('statusLabel', array_keys(bookingTransitions()))), $status); ?></select></div><button class="button button-small button-outline" type="submit">Filtrer</button><a class="text-link" href="<?= escape(appUrl('agent/reservations.php')) ?>">Tout afficher</a></form><p><?= $total ?> réservation(s)</p>
<?php if (!$bookings): ?><div class="empty-state"><h3>Aucune réservation ici.</h3><p>Les prochaines demandes apparaîtront dans cet espace.</p></div><?php endif; ?>
<?php foreach ($bookings as $booking): ?><article class="booking-item"><div class="booking-item-head"><div><p class="eyebrow"><?= escape($booking['reference_code'] ?? 'Historique #' . $booking['id']) ?></p><h2><?= escape($booking['vehicle_label'] ?? 'Réservation historique à vérifier') ?></h2><p><?= escape($booking['client_name']) ?> · <?= escape($booking['client_email']) ?> · <?= escape($booking['client_phone']) ?></p><p>Du <?= escape($booking['start_date']) ?> au <?= escape($booking['end_date']) ?></p></div><span class="badge"><?= escape(statusLabel($booking['status'])) ?></span></div><div class="booking-item-bottom"><strong><?= escape(money($booking['total_price'])) ?> <small>· <?= escape(money($booking['daily_price'])) ?>/jour</small></strong><?php if (bookingTransitions()[$booking['status']] ?? []): ?><form class="inline-actions" action="<?= escape(appUrl('agent/reservations.php')) ?>" method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $booking['id'] ?>"><select name="status" aria-label="Nouveau statut pour <?= escape($booking['reference_code'] ?? $booking['id']) ?>" required><option value="">Changer le statut</option><?php choiceOptions(array_combine(bookingTransitions()[$booking['status']], array_map('statusLabel', bookingTransitions()[$booking['status']])), ''); ?></select><button class="button button-small" type="submit">Enregistrer</button></form><?php endif; ?></div></article><?php endforeach; ?>
<?php if ($pages > 1): ?><nav class="pagination" aria-label="Pages de réservations"><?php for ($number = max(1, $page - 2); $number <= min($pages, $page + 2); $number++): ?><a href="<?= escape(appUrl('agent/reservations.php?' . http_build_query(['status' => $status, 'page' => $number]))) ?>" <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a><?php endfor; ?></nav><?php endif; ?>
</div>
<?php pageFooter(); ?>
