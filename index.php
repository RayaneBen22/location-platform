<?php
require_once __DIR__ . '/app/bootstrap.php';
$connection = database();
$featured = cataloguePage($connection, [], 3)['vehicles'];
$brands = $connection->query('SELECT id, name FROM vehicle_brands ORDER BY name')->fetchAll();
pageHeader('Location de voitures au Maroc', 'home');
?>
<div class="container">
<section class="hero">
<div class="hero-copy"><p class="hero-label">LOCATION DE VOITURES AU MAROC</p><h1>Votre location<br>de voiture,<br><em>en toute clarté.</em></h1><p class="lead">Un week-end, un déplacement ou un nouveau départ. Comparez nos modèles, choisissez vos dates et préparez votre prochain trajet.</p><div class="hero-actions"><a class="button" href="<?= escape(appUrl('catalogue.php')) ?>">Découvrir nos voitures <span aria-hidden="true">↗</span></a><a class="text-link" href="#comment-ca-marche">Comment ça marche →</a></div><div class="hero-proof"><span>Prix transparents en MAD</span><span>Suivi de votre réservation</span></div></div>
<div class="hero-art"><div class="art-top"><span>PLUS QU'UNE DESTINATION</span><span>NOTRE SÉLECTION</span></div><img src="<?= escape(appUrl('assets/images/voitures/bmw-serie-3.webp')) ?>" alt="BMW Série 3 bleue, vue trois quarts avant en studio clair" width="1536" height="1024" fetchpriority="high"><div class="art-bottom"><span>À chaque envie,<strong>son prochain trajet.</strong></span><span class="art-arrow" aria-hidden="true">↗</span></div></div>
</section>
<form class="search-panel" action="<?= escape(appUrl('catalogue.php')) ?>" method="get" aria-label="Rechercher une voiture">
<div class="field"><label for="home-brand">Votre marque</label><select id="home-brand" name="brand"><option value="">Toutes les marques</option><?php selectOptions($brands, ''); ?></select></div>
<div class="field"><label for="home-start">Date de départ</label><input id="home-start" name="start" type="date" min="<?= date('Y-m-d') ?>"></div>
<div class="field"><label for="home-end">Date de retour</label><input id="home-end" name="end" type="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
<button class="button button-dark" type="submit">Trouver ma voiture ↗</button>
</form>
<section class="brand-strip" id="marques" aria-label="Marques automobiles"><div class="brand-strip-top"><p class="eyebrow">LES MARQUES QUI VOUS ACCOMPAGNENT</p><p>Comparez les modèles de notre catalogue.</p></div><div class="brand-list"><?php foreach ($brands as $brand): ?><a class="brand-item" href="<?= escape(appUrl('catalogue.php?brand=' . (int) $brand['id'])) ?>"><?= escape($brand['name']) ?></a><?php endforeach; ?></div></section>
<section class="section"><div class="section-heading"><div><p class="eyebrow">À CHAQUE TRAJET, SA VOITURE</p><h2>Des modèles pour<br>chaque trajet.</h2></div><a class="text-link" href="<?= escape(appUrl('catalogue.php')) ?>">Voir le catalogue ↗</a></div><div class="vehicle-grid"><?php foreach ($featured as $vehicle): vehicleCard($vehicle); endforeach; ?><?php if (!$featured): ?><div class="empty-state"><h3>Notre sélection se prépare.</h3><p>Retrouvez prochainement les voitures ajoutées à notre catalogue.</p></div><?php endif; ?></div></section>
</div>
<section class="steps-section section" id="comment-ca-marche"><div class="container"><p class="eyebrow">VOTRE RÉSERVATION, ÉTAPE PAR ÉTAPE</p><h2>Préparez votre départ<br>en trois étapes.</h2><div class="steps-grid">
<div><span class="step-number">01</span><h3>Comparez les voitures</h3><p>Consultez les modèles, leurs informations et le prix journalier. Affinez votre sélection avec les filtres.</p></div>
<div><span class="step-number">02</span><h3>Choisissez vos dates</h3><p>Vérifiez le calendrier et le montant total, puis envoyez votre demande depuis votre compte.</p></div>
<div><span class="step-number">03</span><h3>Suivez la confirmation</h3><p>Retrouvez le statut de votre demande dans votre espace. Notre équipe doit confirmer la réservation.</p></div>
</div></div></section>
<section class="container section"><div class="section-heading"><div><p class="eyebrow">RÉSERVEZ AVEC LES BONNES INFORMATIONS</p><h2>La clarté, à chaque étape.</h2></div></div><div class="benefits-grid"><article class="panel"><h3>Un tarif calculé à l'avance</h3><p>Le prix journalier et le total pour vos dates sont affichés avant votre demande.</p></article><article class="panel"><h3>Le choix selon vos besoins</h3><p>Comparez les modèles et utilisez les filtres pour préparer votre trajet.</p></article><article class="panel"><h3>Un suivi dans votre espace</h3><p>Retrouvez vos demandes, leur statut et les possibilités d'annulation dans votre compte.</p></article></div></section>
<div class="container section-compact"><section class="callout"><div><p class="eyebrow">VOTRE PROCHAIN TRAJET</p><h2>Prêt à choisir votre voiture ?</h2><p>Consultez le catalogue et vérifiez vos dates.</p></div><a class="button button-dark" href="<?= escape(appUrl('catalogue.php')) ?>">Découvrir nos voitures ↗</a></section></div>
<?php pageFooter(); ?>
