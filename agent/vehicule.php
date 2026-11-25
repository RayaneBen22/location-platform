<?php

require_once __DIR__ . '/../app/agent.php';
$id = positiveIdentifier($_GET['id'] ?? null);
$existing = $id ? vehicleDetails(database(), $id, true) : false;

if ((isset($_GET['id']) && !$id) || ($id && !$existing)) {
    http_response_code($id ? 404 : 400);
    exit('Voiture introuvable.');
}

$values = $existing ?: ['vehicle_model_id' => '', 'vehicle_category_id' => '', 'version' => '', 'description' => '', 'daily_price' => '', 'fuel_type' => '', 'transmission' => '', 'seats' => '', 'manufacture_year' => '', 'mileage' => '', 'doors' => '', 'power_hp' => '', 'air_conditioning' => '', 'price_is_indicative' => '1', 'status' => 'disponible'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($values) as $key) {
        if (array_key_exists($key, $_POST)) {
            $values[$key] = submittedString($_POST, $key);
        }
    }

    try {
        $savedId = saveVehicleDetails(database(), $id, $_POST);
        flashMessage('Les informations de la voiture ont été enregistrées. Vous pouvez ajouter ses photos.');
        redirectTo('agent/vehicule.php?id=' . $savedId);
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    }
}

$models = vehicleModels(database());
$categories = vehicleCategories(database());
$modelId = (int) ($values['vehicle_model_id'] ?? 0);
$photos = $id ? vehiclePhotos(database(), $id, true) : [];
pageHeader($id ? 'Modifier la voiture' : 'Ajouter une voiture');
?>
<section class="container page-intro"><p class="eyebrow">ESPACE ÉQUIPE</p><h1><?= $id ? 'Les détails font la différence.' : 'Une nouvelle voiture.' ?></h1><p class="lead">Renseignez les informations réelles de votre véhicule.</p></section>
<div class="container section-compact"><?php agentNavigation('vehicules.php'); ?>
<?php if ($existing && $existing['vehicle_model_id'] === null): ?><p class="notice">Entrée historique conservée : <?= escape($existing['name']) ?>. Vérifiez qu’il s’agit réellement d’une voiture avant de la classer.</p><?php endif; ?>
<?php if ($error): ?><p class="notice notice-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
<form class="admin-form panel" action="<?= escape(appUrl('agent/vehicule.php' . ($id ? '?id=' . $id : ''))) ?>" method="post"><?= csrfField() ?><h2>Identité et caractéristiques</h2><div class="field-grid">
<div class="field"><label for="vehicle-model">Marque et modèle</label><select id="vehicle-model" name="vehicle_model_id" required><option value="">Choisir un modèle</option><?php require __DIR__ . '/../app/model-options.php'; ?></select></div>
<div class="field"><label for="vehicle-category">Catégorie</label><select id="vehicle-category" name="vehicle_category_id" required><option value="">Choisir une catégorie</option><?php selectOptions($categories, $values['vehicle_category_id']); ?></select></div>
<div class="field"><label for="version">Version</label><input id="version" name="version" maxlength="60" value="<?= escape($values['version']) ?>" placeholder="Facultative"></div>
<div class="field"><label for="daily-price">Prix par jour (MAD)</label><input id="daily-price" name="daily_price" type="number" min=".01" max="99999999.99" step=".01" value="<?= escape($values['daily_price']) ?>" required></div>
<div class="field"><label for="fuel-type">Carburant</label><select id="fuel-type" name="fuel_type"><option value="">Choisir</option><?php choiceOptions(fuelTypes(), $values['fuel_type'] ?? ''); ?></select></div>
<div class="field"><label for="transmission">Transmission</label><select id="transmission" name="transmission"><option value="">Choisir</option><?php choiceOptions(transmissions(), $values['transmission'] ?? ''); ?></select></div>
<div class="field"><label for="seats">Places</label><input id="seats" name="seats" type="number" min="2" max="9" value="<?= escape($values['seats']) ?>"></div>
<div class="field"><label for="year">Année</label><input id="year" name="manufacture_year" type="number" min="1950" max="<?= (int) date('Y') + 1 ?>" value="<?= escape($values['manufacture_year']) ?>"></div>
<div class="field"><label for="mileage">Kilométrage</label><input id="mileage" name="mileage" type="number" min="0" max="9999999" value="<?= escape($values['mileage']) ?>"></div>
<div class="field"><label for="doors">Portes</label><input id="doors" name="doors" type="number" min="2" max="5" value="<?= escape($values['doors'] ?? '') ?>"></div>
<div class="field"><label for="power">Puissance (ch)</label><input id="power" name="power_hp" type="number" min="1" max="2000" value="<?= escape($values['power_hp'] ?? '') ?>"></div>
<div class="field"><label for="air-conditioning">Climatisation</label><select id="air-conditioning" name="air_conditioning"><option value="">Non renseignée</option><?php choiceOptions(['1' => 'Oui', '0' => 'Non'], (string) ($values['air_conditioning'] ?? '')); ?></select></div>
<div class="field"><label for="indicative-price">Validation du tarif</label><select id="indicative-price" name="price_is_indicative" required><?php choiceOptions(['1' => 'Indicatif, en attente de validation', '0' => 'Validé par le propriétaire'], (string) ($values['price_is_indicative'] ?? '1')); ?></select></div>
<div class="field"><label for="status">Disponibilité du parc</label><select id="status" name="status" required><?php choiceOptions(['disponible' => 'Disponible', 'en_location' => 'Indisponible', 'en_maintenance' => 'En préparation / maintenance'], $values['status']); ?></select></div>
</div><div class="field"><label for="description">Description</label><textarea id="description" name="description" maxlength="5000" rows="5"><?= escape($values['description']) ?></textarea></div><p class="field-help">Les caractéristiques inconnues des anciennes voitures ne sont pas inventées. Laissez les champs facultatifs vides tant que la version et les données réelles ne sont pas confirmées. Validez un tarif uniquement après accord du propriétaire. Masquer une voiture ou changer son état n’annule pas ses réservations existantes.</p><div class="inline-actions"><button class="button" type="submit">Enregistrer la voiture ↗</button><a class="text-link" href="<?= escape(appUrl('agent/vehicules.php')) ?>">Retour au parc</a></div></form>
<?php if ($id && $existing['vehicle_model_id'] !== null): ?><section class="panel admin-form"><h2>Photos du véhicule</h2><p>Utilisez des photos réelles dont vous détenez les droits. 8 photos actives maximum, JPEG, PNG ou WebP, 5 Mo chacune.</p><form action="<?= escape(appUrl('agent/photos.php')) ?>" method="post" enctype="multipart/form-data"><?= csrfField() ?><input type="hidden" name="vehicle_id" value="<?= $id ?>"><input type="hidden" name="action" value="upload"><div class="field"><label for="photo">Ajouter une photo</label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required></div><button class="button button-secondary" type="submit">Téléverser la photo</button></form><div class="gallery-thumbs"><?php foreach ($photos as $photo): ?><div><img src="<?= escape(appUrl('photo.php?id=' . (int) $photo['id'])) ?>" alt="Photo du véhicule" width="110" height="75"><form action="<?= escape(appUrl('agent/photos.php')) ?>" method="post"><?= csrfField() ?><input type="hidden" name="vehicle_id" value="<?= $id ?>"><input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>"><input type="hidden" name="action" value="<?= $photo['is_active'] ? 'hide' : 'show' ?>"><button class="button button-small button-outline" type="submit"><?= $photo['is_active'] ? 'Masquer' : 'Afficher' ?></button></form></div><?php endforeach; ?></div></section><?php endif; ?>
</div>
<?php pageFooter(); ?>
