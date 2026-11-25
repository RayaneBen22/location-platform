<article class="vehicle-card">
    <a class="vehicle-card-image" href="<?= escape($detailsUrl) ?>">
        <img src="<?= escape($vehicle['photo_id'] ? appUrl('photo.php?id=' . (int) $vehicle['photo_id']) : appUrl('assets/car-placeholder.svg')) ?>" alt="Photographie de <?= escape(vehicleLabel($vehicle)) ?>" loading="lazy" decoding="async" width="640" height="440">
        <span class="badge"><?= escape($vehicle['category_name'] ?? 'Automobile') ?></span>
    </a>
    <div class="vehicle-card-body">
        <p class="eyebrow"><?= escape($vehicle['brand_name']) ?></p>
        <h3><a href="<?= escape($detailsUrl) ?>"><?= escape(vehicleLabel($vehicle)) ?></a></h3>
        <?php $cardSpecifications = array_intersect_key(publicVehicleSpecifications($vehicle), array_flip(['Carburant', 'Transmission', 'Places'])); ?>
        <?php if ($cardSpecifications): ?><div class="vehicle-specs">
            <?php foreach ($cardSpecifications as $label => $value): ?><span><strong><?= escape($label) ?></strong> <?= escape($value) ?></span><?php endforeach; ?>
        </div><?php endif; ?>
        <div class="card-bottom"><p><strong><?= escape(money($vehicle['daily_price'])) ?></strong><small>/ jour</small></p><span class="availability"><?= $vehicle['status'] === 'disponible' ? 'Selon vos dates' : escape(statusLabel($vehicle['status'])) ?></span></div>
        <?php if (indicativePriceLabel($vehicle)): ?><p class="price-notice"><?= escape(indicativePriceLabel($vehicle)) ?></p><?php endif; ?>
        <div class="card-actions"><a class="button button-outline" href="<?= escape($detailsUrl) ?>">Voir le véhicule</a><?php if ($vehicle['status'] === 'disponible'): ?><a class="button" href="<?= escape($detailsUrl . '#reservation') ?>" aria-label="Réserver <?= escape(vehicleLabel($vehicle)) ?>">Réserver <span aria-hidden="true">↗</span></a><?php endif; ?></div>
    </div>
</article>
