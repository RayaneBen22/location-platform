<?php

require_once __DIR__ . '/app/bootstrap.php';
$id = positiveIdentifier($_GET['id'] ?? null);
$vehicle = $id ? vehicleDetails(database(), $id) : false;

if (!$vehicle) {
    http_response_code(404);
    pageHeader('Voiture introuvable');
    echo '<section class="container section"><h1>Cette voiture n’est plus au catalogue.</h1><a class="button" href="' . escape(appUrl('catalogue.php')) . '">Explorer les voitures</a></section>';
    pageFooter();
    exit;
}

$photos = vehiclePhotos(database(), $id);
pageHeader(vehicleLabel($vehicle), 'catalogue');
?>
<section class="container page-intro"><div class="breadcrumbs"><a href="<?= escape(appUrl('catalogue.php')) ?>">Nos voitures</a><span>/</span><span><?= escape($vehicle['brand_name']) ?></span></div><p class="eyebrow"><?= escape($vehicle['category_name'] ?? 'Automobile') ?> · <?= escape($vehicle['brand_name']) ?></p><h1><?= escape(vehicleLabel($vehicle)) ?></h1><p class="lead"><?= escape($vehicle['status'] === 'disponible' ? 'Disponibilité selon vos dates' : statusLabel($vehicle['status'])) ?><?php if (isset($vehicle['manufacture_year'])): ?> · <?= escape($vehicle['manufacture_year']) ?><?php endif; ?></p></section>
<div class="container details-grid"><section aria-label="Présentation de la voiture">
    <div class="vehicle-gallery"><img src="<?= escape($photos ? appUrl('photo.php?id=' . (int) $photos[0]['id']) : appUrl('assets/car-placeholder.svg')) ?>" alt="<?= escape($photos ? vehicleLabel($vehicle) : 'Illustration automobile, photo à ajouter') ?>" width="900" height="550"><?php if (!$photos): ?><span class="photo-note">Illustration · Photo à ajouter</span><?php endif; ?></div>
    <?php if (count($photos) > 1): ?><div class="gallery-thumbs"><?php foreach ($photos as $index => $photo): ?><a href="<?= escape(appUrl('photo.php?id=' . (int) $photo['id'])) ?>" aria-label="Ouvrir la photo <?= $index + 1 ?>"><img src="<?= escape(appUrl('photo.php?id=' . (int) $photo['id'])) ?>" alt="<?= escape(vehicleLabel($vehicle)) ?>, vue <?= $index + 1 ?>" width="110" height="75" loading="lazy"></a><?php endforeach; ?></div><?php endif; ?>
    <?php $specifications = publicVehicleSpecifications($vehicle); ?>
    <?php if ($specifications): ?><dl class="spec-grid"><?php foreach ($specifications as $label => $value): ?><div><dt><?= escape($label) ?></dt><dd><?= escape($value) ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
    <h2>Votre véhicule, en détail.</h2><p><?= nl2br(escape($vehicle['description'] ?: 'Les informations complémentaires de cette voiture seront précisées par notre équipe.')) ?></p>
    <?php if ($vehicle['characteristics']): ?><p><?= nl2br(escape($vehicle['characteristics'])) ?></p><?php endif; ?>
</section><aside class="booking-panel" id="reservation"><p class="eyebrow">VOTRE PROCHAINE ESCAPADE</p><h2><?= escape(money($vehicle['daily_price'])) ?> <small>/ jour</small></h2><?php if (indicativePriceLabel($vehicle)): ?><p class="price-notice"><?= escape(indicativePriceLabel($vehicle)) ?></p><?php endif; ?><p>Le total est calculé selon vos dates. Aucun paiement en ligne.</p>
    <?php if ($vehicle['status'] === 'disponible'): ?>
    <form action="<?= escape(appUrl('reserver.php')) ?>" method="get" data-booking-estimate data-vehicle-id="<?= $id ?>" data-availability-url="<?= escape(appUrl('api/disponibilite.php')) ?>"><input type="hidden" name="id" value="<?= $id ?>"><div class="field"><label for="booking-start">Date de départ</label><input id="booking-start" name="start" type="date" min="<?= date('Y-m-d') ?>" value="<?= escape(submittedString($_GET, 'start')) ?>" required></div><div class="field"><label for="booking-end">Date de retour</label><input id="booking-end" name="end" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= escape(submittedString($_GET, 'end')) ?>" required></div><p class="notice" data-estimate-result role="status">Choisissez vos dates pour connaître le total.</p><button class="button button-wide" type="submit">Continuer ma réservation <span aria-hidden="true">↗</span></button></form>
    <?php else: ?><p class="notice">Cette voiture n’est pas réservable actuellement.</p><a class="button button-wide" href="<?= escape(appUrl('catalogue.php')) ?>">Voir d’autres voitures</a><?php endif; ?>
    <p class="field-help">Retour exclusif : une location du 1 au 3 représente 2 jours. La réservation reste soumise à confirmation de l’équipe.</p><a class="text-link" href="<?= escape(appUrl('conditions.php')) ?>">Conditions de réservation →</a>
</aside></div>
<?php pageFooter(); ?>
