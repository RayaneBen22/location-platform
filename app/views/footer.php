</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div><a class="brand footer-brand" href="<?= escape(appUrl('index.php')) ?>"><span class="brand-symbol">Lp</span><span>locationplatform</span></a><p>La bonne voiture.<br>La liberté d’aller plus loin.</p></div>
        <div><h2>Explorer</h2><a href="<?= escape(appUrl('catalogue.php')) ?>">Toutes les voitures</a><a href="<?= escape(appUrl('index.php#marques')) ?>">Nos marques</a></div>
        <div><h2>Votre espace</h2><a href="<?= escape(appUrl('mon-compte.php')) ?>">Mes réservations</a><a href="<?= escape(appUrl('inscription.php')) ?>">Créer un compte</a></div>
        <div><h2>En toute transparence</h2><p>Prix affichés en MAD.<br>Aucun paiement en ligne.<br>Chaque demande est confirmée par l’équipe.</p><a href="<?= escape(appUrl('conditions.php')) ?>">Conditions de réservation</a></div>
    </div>
    <div class="container footer-bottom"><span>© <?= date('Y') ?> Location Platform</span><span><a href="https://www.infinityfree.com/" target="_blank" rel="noopener noreferrer">Hébergé sur InfinityFree</a> · <a href="https://github.com/RayaneBen22/location-platform" target="_blank" rel="noopener noreferrer">Code source GitHub</a></span></div>
</footer>
</body>
</html>
