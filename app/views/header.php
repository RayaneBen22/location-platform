<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Trouvez votre prochaine voiture de location. Comparez les modèles, choisissez vos dates et suivez vos réservations en toute simplicité.">
    <title><?= escape($title) ?> · RayaneCars</title>
    <link rel="icon" href="<?= escape(appUrl('assets/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= escape(appUrl('assets/style.css?v=' . filemtime(dirname(__DIR__, 2) . '/assets/style.css'))) ?>">
    <script src="<?= escape(appUrl('assets/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <div class="container header-inner">
        <?php require __DIR__ . '/brand.php'; ?>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav">Menu <span aria-hidden="true">☰</span></button>
        <nav id="main-nav" class="main-nav" aria-label="Navigation principale">
            <a href="<?= escape(appUrl('index.php')) ?>" <?= $active === 'home' ? 'aria-current="page"' : '' ?>>Accueil</a>
            <a href="<?= escape(appUrl('catalogue.php')) ?>" <?= $active === 'catalogue' ? 'aria-current="page"' : '' ?>>Nos voitures</a>
            <a href="<?= escape(appUrl('index.php#comment-ca-marche')) ?>">Comment ça marche</a>
            <?php if ($user && $user['role'] === 'agent'): ?><a href="<?= escape(appUrl('agent/index.php')) ?>">Administration</a><?php endif; ?>
            <a class="nav-account" href="<?= escape(appUrl($user ? 'mon-compte.php' : 'connexion.php')) ?>"><?= $user ? 'Mon espace' : 'Se connecter' ?> <span aria-hidden="true">↗</span></a>
        </nav>
    </div>
</header>
<main id="main">
<?php if ($flash): ?><div class="container"><p class="notice" role="status"><?= escape($flash) ?></p></div><?php endif; ?>
