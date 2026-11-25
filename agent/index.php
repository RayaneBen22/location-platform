<?php
require_once __DIR__ . '/../app/agent.php';
$connection = database();
$vehicleCount = (int) $connection->query('SELECT COUNT(*) FROM articles WHERE vehicle_model_id IS NOT NULL AND is_active = 1')->fetchColumn();
$pendingCount = (int) $connection->query("SELECT COUNT(*) FROM locations WHERE status = 'en_attente'")->fetchColumn();
$clientCount = (int) $connection->query("SELECT COUNT(*) FROM users WHERE role = 'client' AND is_active = 1")->fetchColumn();
$confirmedValue = $connection->query("SELECT COALESCE(SUM(total_price), 0) FROM locations WHERE status IN ('confirmee', 'en_cours', 'terminee')")->fetchColumn();
$recent = $connection->query('SELECT l.id, l.reference_code, l.vehicle_label, l.start_date, l.status, u.name AS client_name FROM locations l INNER JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 8')->fetchAll();
pageHeader('Tableau de bord');
?>
<section class="container page-intro"><p class="eyebrow">ESPACE ÉQUIPE</p><h1>Vue d'ensemble.</h1><p class="lead">Bonjour <?= escape($agent['name']) ?>. Voici ce qui se passe sur votre plateforme.</p></section>
<div class="container section-compact"><?php agentNavigation('index.php'); ?><div class="stats-grid"><div class="stat"><span>Voitures actives</span><strong><?= $vehicleCount ?></strong></div><div class="stat"><span>Demandes à traiter</span><strong><?= $pendingCount ?></strong></div><div class="stat"><span>Clients actifs</span><strong><?= $clientCount ?></strong></div><div class="stat"><span>Montant confirmé, hors annulées</span><strong><?= escape(money($confirmedValue)) ?></strong></div></div>
<div class="section-heading"><h2>Les dernières demandes.</h2><a class="button button-small" href="<?= escape(appUrl('agent/vehicule.php')) ?>">Ajouter une voiture ↗</a></div><p class="field-help">Les montants sont ceux des réservations, sans encaissement de paiement sur le site.</p>
<?php if (!$recent): ?><div class="empty-state"><h3>Votre activité commence ici.</h3><p>Ajoutez les voitures de votre parc pour recevoir vos premières demandes.</p><a class="button" href="<?= escape(appUrl('agent/vehicules.php')) ?>">Gérer mon parc</a></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Référence</th><th>Client</th><th>Voiture</th><th>Départ</th><th>Statut</th></tr></thead><tbody><?php foreach ($recent as $booking): ?><tr><td><a class="text-link" href="<?= escape(appUrl('agent/reservations.php?id=' . (int) $booking['id'])) ?>"><?= escape($booking['reference_code'] ?? '#' . $booking['id']) ?></a></td><td><?= escape($booking['client_name']) ?></td><td><?= escape($booking['vehicle_label'] ?? 'Réservation historique') ?></td><td><?= escape($booking['start_date']) ?></td><td><?= escape(statusLabel($booking['status'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<?php pageFooter(); ?>
