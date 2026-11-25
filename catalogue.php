<?php
require_once __DIR__ . '/app/bootstrap.php';
$connection = database();
$error = '';
try {
    $results = cataloguePage($connection, $_GET);
} catch (DomainException $exception) {
    $error = $exception->getMessage();
    $results = ['vehicles' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'filters' => []];
}
$filters = $results['filters'];
$brands = $connection->query('SELECT id, name FROM vehicle_brands ORDER BY name')->fetchAll();
$models = $connection->query('SELECT id, brand_id, name FROM vehicle_models ORDER BY name')->fetchAll();
$categories = vehicleCategories($connection);
pageHeader('Nos voitures', 'catalogue');
?>
<section class="container page-intro"><div class="breadcrumbs"><a href="<?= escape(appUrl('index.php')) ?>">Accueil</a><span>/</span><span>Nos voitures</span></div><p class="eyebrow">VOTRE PROCHAIN TRAJET</p><h1>Une voiture qui vous ressemble.</h1><p class="lead">De la ville aux grandes escapades. À vous de choisir.</p></section>
<div class="container catalogue-layout">
<form class="filter-panel" action="<?= escape(appUrl('catalogue.php')) ?>" method="get">
<h2>Affiner votre recherche</h2>
<div class="field"><label for="q">Rechercher</label><input id="q" name="q" type="search" maxlength="100" placeholder="Marque, modèle, version…" value="<?= escape($filters['q'] ?? '') ?>"></div>
<div class="field"><label for="brand">Marque</label><select id="brand" name="brand" data-brand-filter><option value="">Toutes les marques</option><?php selectOptions($brands, $filters['brand'] ?? ''); ?></select></div>
<div class="field"><label for="model">Modèle</label><select id="model" name="model" data-model-filter><option value="">Tous les modèles</option><?php foreach ($models as $model): ?><option data-brand="<?= (int) $model['brand_id'] ?>" value="<?= (int) $model['id'] ?>" <?= ($filters['model'] ?? '') === (string) $model['id'] ? 'selected' : '' ?>><?= escape($model['name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="category">Catégorie</label><select id="category" name="category"><option value="">Toutes les catégories</option><?php selectOptions($categories, $filters['category'] ?? ''); ?></select></div>
<div class="field"><label for="min-price">Budget minimum / jour</label><input id="min-price" name="min_price" type="number" min="0" max="99999999.99" step=".01" placeholder="MAD" value="<?= escape($filters['min_price'] ?? '') ?>"></div>
<div class="field"><label for="max-price">Budget maximum / jour</label><input id="max-price" name="max_price" type="number" min="0" max="99999999.99" step=".01" placeholder="MAD" value="<?= escape($filters['max_price'] ?? '') ?>"></div>
<div class="field"><label for="fuel">Carburant</label><select id="fuel" name="fuel"><option value="">Tous les carburants</option><?php choiceOptions(fuelTypes(), $filters['fuel'] ?? ''); ?></select></div>
<div class="field"><label for="transmission">Transmission</label><select id="transmission" name="transmission"><option value="">Toutes les boîtes</option><?php choiceOptions(transmissions(), $filters['transmission'] ?? ''); ?></select></div>
<div class="field"><label for="seats">Nombre de places</label><select id="seats" name="seats"><option value="">Toutes les capacités</option><?php choiceOptions(array_combine(range(2, 9), array_map(fn ($count) => $count . ' places', range(2, 9))), $filters['seats'] ?? ''); ?></select></div>
<div class="field"><label for="start">Départ</label><input id="start" name="start" type="date" min="<?= date('Y-m-d') ?>" value="<?= escape($filters['start'] ?? '') ?>"></div>
<div class="field"><label for="end">Retour</label><input id="end" name="end" type="date" min="<?= date('Y-m-d') ?>" value="<?= escape($filters['end'] ?? '') ?>"></div>
<div class="field"><label for="sort">Trier par</label><select id="sort" name="sort"><option value="">Marque et modèle</option><?php choiceOptions(['price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant', 'recent' => 'Ajouts récents'], $filters['sort'] ?? ''); ?></select></div>
<div class="filter-buttons"><button class="button button-wide" type="submit">Voir les résultats <span aria-hidden="true">→</span></button><a class="text-link" href="<?= escape(appUrl('catalogue.php')) ?>">Réinitialiser</a></div>
</form>
<section class="catalogue-results" aria-label="Résultats de recherche">
<?php if ($error): ?><p class="notice notice-error" role="alert"><?= escape($error) ?></p><?php endif; ?>
<div class="results-toolbar"><p><strong><?= (int) $results['total'] ?></strong> voiture(s) au catalogue</p><span class="muted">Prix par jour · MAD</span></div>
<div class="vehicle-grid"><?php foreach ($results['vehicles'] as $vehicle): vehicleCard($vehicle, $filters); endforeach; ?><?php if (!$results['vehicles']): ?><div class="empty-state"><span class="empty-symbol" aria-hidden="true">⌕</span><h2>Aucune voiture pour le moment.</h2><p>Essayez d'autres filtres ou revenez découvrir les prochaines voitures de notre catalogue.</p><a class="button button-outline" href="<?= escape(appUrl('catalogue.php')) ?>">Effacer les filtres</a></div><?php endif; ?></div>
<?php if ($results['pages'] > 1): ?><nav class="pagination" aria-label="Pages du catalogue"><?php for ($number = max(1, $results['page'] - 2); $number <= min($results['pages'], $results['page'] + 2); $number++): ?><a href="<?= escape(appUrl('catalogue.php?' . http_build_query(array_merge($filters, ['page' => $number])))) ?>" <?= $number === $results['page'] ? 'aria-current="page"' : '' ?>><?= $number ?></a><?php endfor; ?></nav><?php endif; ?>
</section></div>
<?php pageFooter(); ?>
