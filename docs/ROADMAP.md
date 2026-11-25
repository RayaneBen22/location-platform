# État des fonctionnalités

Mission complète du 24 novembre 2026, sans planning journalier. L'ancien programme automatique est suspendu. Branche : `codex/catalogue-photos-professionnel`. Les statuts ci-dessous décrivent l'implémentation et ses vérifications techniques ; la validation humaine globale reste en attente.

| Domaine | Réalisation et critères | Vérifications | Statut réel |
| --- | --- | --- | --- |
| Catalogue automobile | Uniquement les entrées avec un modèle automobile, catégories dédiées, prix, caractéristiques et pagination ; historique conservé hors catalogue | Exclusion des objets et accès direct, activation, contraintes SQL, CGI et PDO | Implémenté et testé |
| Interface et design | Navigation commune, accueil, cartes, formulaires, pied de page ; cinq couleurs centralisées, contrastes de base, réduction des animations et styles mobiles | Chrome à 375/768/1440 px, menu mobile, débordements, filtres et devis ; pas de certification WCAG globale | Implémenté et revu sur les pages publiques |
| Marques et modèles | Ajout et renommage des marques, modèles et catégories ; modèles rattachés par clé étrangère, versions par voiture, aucune suppression | Doublons, marque invalide, formulaire agent, injection de nom de table | Implémenté et testé |
| Recherche et filtres | Marque, modèle, budget, catégorie, carburant, transmission, places, texte, dates, tri et pagination ; dates conservées vers la fiche | Combinaisons, valeurs invalides, injection SQL, prix et périodes occupées ; essai Chrome | Implémenté et testé |
| Fiches véhicules | Description, caractéristiques, prix, disponibilité et photos ; illustration explicite si aucune photo réelle | Véhicule masqué ou historique en 404, XSS, photo masquée et type de réponse | Implémenté et testé ; contenu réel à fournir |
| Réservations | Dates et montant recalculés côté serveur, retour exclusif, demande avec référence et clé unique, verrou transactionnel et transitions de statut | Deux écritures simultanées sur SQLite et MariaDB, chevauchements, idempotence, prix falsifiés, confirmation et annulation CGI | Implémenté et testé |
| Espace client | Inscription, connexion, déconnexion, profil, changement de mot de passe, historique, détails et annulation avant départ | CSRF, hachage, droits sur les réservations, suspension, expiration et invalidation des autres sessions | Implémenté et testé |
| Administration | Tableau de bord réel, voitures/prix/états/photos, références, réservations et suspension des clients ; rôle agent existant réutilisé | Contrôle des rôles sur chaque route, formulaires, transitions autorisées et interdiction de suspendre un agent | Implémenté et testé par CGI ; revue visuelle finale propriétaire attendue |
| Sécurité | SQL préparé, XSS, CSRF, sessions et cookies stricts, limitation de connexion, fichiers hors accès direct et messages génériques | 120 contrôles sur chaque moteur, vrais uploads CGI, accès HTTP aux fichiers internes refusé, politique de sources | Protections demandées implémentées et testées ; audit externe non effectué |
| Tests et finalisation | Suites isolées, schéma neuf, migrations depuis le généraliste et la première version automobile, documentation et branche dédiée | 120 SQLite + 120 MariaDB, 8 scénarios de migrations/contraintes ; syntaxe et différences Git | Vérifié techniquement ; validation humaine en attente |

## Conditions de validation finale

- Le propriétaire examine les parcours publics, clients et agents avec ses véritables données.
- La migration est appliquée d'abord à une copie sauvegardée de sa base réelle ; aucune base réelle n'a été migrée automatiquement.
- Les informations légales et pratiques du loueur sont complétées avant exploitation.
- Les six photos de présentation sont intégrées au catalogue local. Le stock et les caractéristiques doivent être confirmés par le propriétaire.
- Les réglages d'hébergement, HTTPS et sauvegardes sont vérifiés dans l'environnement choisi.
- La branche peut ensuite être intégrée explicitement. Aucune fusion, aucun push ni déploiement n'est automatique.

## Hors du périmètre réellement livré

Paiement en ligne, emails, récupération de mot de passe par email, expiration automatique des demandes, certification WCAG complète, audit externe et publication en production ne sont pas implémentés ou effectués. Les contrôles locaux n'attestent pas le fonctionnement d'une configuration Apache/HTTPS distante encore inconnue.

## Finalisation photographique

Six associations marque/modèle intégrées et testées, WebP sans recadrage, cartes à deux boutons, textes commerciaux nettoyés et accueil photographique : implémenté et vérifié sur le catalogue local. 30 contrôles médias et 120 contrôles applicatifs réussis. Base MySQL configurée indisponible ou non migrée : intégration sur cette base en attente via tools/integrate-catalogue.php. Aucun planning journalier.
