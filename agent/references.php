<?php

require_once __DIR__ . '/../app/agent.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rawId = submittedString($_POST, 'id');
        $id = $rawId !== '' ? positiveIdentifier($rawId) : null;

        if ($rawId !== '' && $id === null) {
            throw new DomainException('Identifiant invalide.');
        }

        saveReference(database(), submittedString($_POST, 'kind'), $id, submittedString($_POST, 'name'), positiveIdentifier($_POST['brand_id'] ?? null));
        flashMessage('La référence a été enregistrée.');
        redirectTo('agent/references.php');
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    }
}

$brands = database()->query('SELECT id, name FROM vehicle_brands ORDER BY name')->fetchAll();
$models = database()->query('SELECT id, brand_id, name FROM vehicle_models ORDER BY brand_id, name')->fetchAll();
$categories = vehicleCategories(database());
pageHeader('Marques, modèles et catégories');
?>
<section class="container page-intro"><p class="eyebrow">ESPACE ÉQUIPE</p><h1>Un catalogue bien organisé.</h1><p class="lead">Marques, modèles et catégories. Chaque voiture trouve sa place.</p></section>
<div class="container section-compact"><?php agentNavigation('references.php'); ?><?php if ($error): ?><p class="notice notice-error" role="alert"><?= escape($error) ?></p><?php endif; ?><p class="field-help">Les références peuvent être ajoutées ou renommées. Aucune suppression n’est proposée pour préserver le parc et son historique. Renommer une référence met à jour l’affichage des voitures associées ; les libellés des réservations restent figés.</p>
<div class="admin-two-columns">
<?php foreach (['brand' => ['Marques', $brands], 'category' => ['Catégories automobiles', $categories]] as $kind => [$heading, $items]): ?>
<section class="panel"><h2><?= escape($heading) ?></h2><form class="reference-item two" action="<?= escape(appUrl('agent/references.php')) ?>" method="post"><?= csrfField() ?><input type="hidden" name="kind" value="<?= escape($kind) ?>"><input name="name" maxlength="60" placeholder="Nouveau nom" aria-label="Nouveau nom pour <?= escape($heading) ?>" required><button class="button button-small" type="submit">Ajouter</button></form>
<?php foreach ($items as $item): ?><form class="reference-item two" action="<?= escape(appUrl('agent/references.php')) ?>" method="post"><?= csrfField() ?><input type="hidden" name="kind" value="<?= escape($kind) ?>"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input name="name" maxlength="60" value="<?= escape($item['name']) ?>" aria-label="Renommer <?= escape($item['name']) ?>" required><button class="button button-small button-outline" type="submit">Enregistrer</button></form><?php endforeach; ?></section>
<?php endforeach; ?>
</div><section class="panel"><h2>Modèles automobiles</h2><form class="reference-item" action="<?= escape(appUrl('agent/references.php')) ?>" method="post"><?= csrfField() ?><input type="hidden" name="kind" value="model"><select name="brand_id" aria-label="Marque du nouveau modèle" required><option value="">Choisir la marque</option><?php selectOptions($brands, ''); ?></select><input name="name" maxlength="80" placeholder="Nouveau modèle" aria-label="Nom du nouveau modèle" required><button class="button button-small" type="submit">Ajouter le modèle</button></form>
<?php foreach ($models as $model): ?><form class="reference-item" action="<?= escape(appUrl('agent/references.php')) ?>" method="post"><?= csrfField() ?><input type="hidden" name="kind" value="model"><input type="hidden" name="id" value="<?= (int) $model['id'] ?>"><select name="brand_id" aria-label="Marque de <?= escape($model['name']) ?>" required><?php selectOptions($brands, $model['brand_id']); ?></select><input name="name" maxlength="80" value="<?= escape($model['name']) ?>" aria-label="Nom du modèle <?= escape($model['name']) ?>" required><button class="button button-small button-outline" type="submit">Enregistrer</button></form><?php endforeach; ?></section>
</div>
<?php pageFooter(); ?>
