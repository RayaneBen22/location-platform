## Catalogue photographique — mise à jour du 24 novembre 2026

Les six PNG fournis ont été examinés visuellement et conservés avec leurs noms d'origine dans `assets/images/voitures/`. Le site utilise des copies WebP de qualité 88, sans recadrage ni redimensionnement : 744 184 octets au total au lieu de 11 384 838, soit environ 93 % de réduction.

| Marque et modèle | Image utilisée |
| --- | --- |
| Audi A3 | `audi-a3.webp` |
| BMW Série 3 | `bmw-serie-3.webp` |
| Mercedes-Benz Classe C | `mercedes-classe-c.webp` |
| Peugeot 208 | `peugeot-208.webp` |
| Renault Clio | `renault-clio.webp` |
| Toyota Corolla | `toyota-corolla.webp` |

`app/vehicle-media.php` décrit les correspondances exactes. L'intégration réutilisable sélectionne les véhicules par marque et modèle et ajoute leurs chemins dans `article_images`, sans création d'offre, sans doublon et sans changement de prix, de statut ou de réservation. Les photos fournies passent avant les anciennes photos, qui restent conservées. Les chemins publics sont limités aux six noms autorisés.

L'intégration a été effectuée sur le catalogue local `var/preview.sqlite`, après sauvegarde cohérente `var/catalogue-before-photos-*.sqlite`. La base MySQL configurée n'est pas accessible ou son schéma automobile n'est pas installé ; elle n'a pas été migrée ni modifiée pour cette mission.

Après sauvegarde de sa base et configuration de la connexion, appliquer les mêmes associations à MySQL avec :

```powershell
& C:\xampp\php\php.exe tools\integrate-catalogue.php --configured-database
```

Pour réappliquer au catalogue local : `php tools/integrate-catalogue.php --preview`. Pour reproduire les fichiers optimisés : `php -d extension=gd tools/optimize-catalogue.php`. GD est uniquement requis pour cette dernière opération ; le site peut servir les WebP déjà fournis sans GD.

Les contenus commerciaux et les noms sont nettoyés des anciennes mentions temporaires. Les prix antérieurs sont conservés. Les anciennes caractéristiques fabriquées par le générateur local, non validées par le propriétaire, sont remises à « À préciser » dans ce catalogue local uniquement ; aucune caractéristique spécifique n'est déduite de l'apparence des images. Les photos sont des visuels de présentation fournis par le propriétaire, sans garantie qu'elles montrent le véhicule physiquement remis. Le propriétaire doit confirmer le stock, les caractéristiques et les conditions avant exploitation.

Les cartes ont deux boutons explicites, des icônes séparées, des textes alternatifs et des images en `object-fit: contain`. L'accueil utilise la BMW, et les fiches reprennent la photo de leur propre modèle. La réservation conserve la vérification réelle des dates et l'approbation de l'équipe.

Validation de cette mise à jour : `php tests/catalogue-media.php` (30 contrôles, dont six associations, dimensions, réponses WebP, fiches et absence de mentions temporaires) ; `php tests/run.php` (120 contrôles applicatifs) ; syntaxe PHP/JavaScript et politique des sources. Chrome : six photos chargées et cadrage complet, catalogue et fiche mobile à 375 px, fiche à 768 px et accueil à 1440 px, sans débordement constaté.

# Location Platform

Plateforme de location automobile construite à partir de l'application PHP/PDO existante. Le catalogue public présente uniquement des voitures. Cette branche est une version de développement destinée à une validation humaine ; elle n'est ni fusionnée ni déployée.

Le planning quotidien a été remplacé par une mission complète le 24 novembre 2026. L'automatisation des dix jours est suspendue.

## Fonctionnalités implémentées

- Accueil, navigation, pied de page et interface responsive commune.
- Palette centralisée : blanc cassé `#F8FAFC`, platine `#E2E8F0`, azur `#3B82F6`, cobalt `#1E40AF` et noir bleuté `#0F172A`.
- Catalogue paginé avec recherche textuelle, marque, modèle, catégorie automobile, budget, carburant, transmission, places et disponibilité par dates.
- Fiches avec version, description, caractéristiques, état du parc, prix et galerie des photos disponibles.
- Estimation de durée, disponibilité et total par API, puis confirmation du récapitulatif.
- Demandes de réservation sécurisées, référence unique et protection contre la double soumission.
- Verrou transactionnel du véhicule pour empêcher les demandes concurrentes incompatibles ; prix et nom automobile figés lors de la demande.
- Inscription client, connexion limitée en cas d'échecs répétés, déconnexion par POST, profil et changement de mot de passe.
- Historique et détail des réservations du client connecté ; annulation autorisée avant le jour du départ.
- Tableau de bord agent, gestion des voitures, prix, caractéristiques, visibilité et état du parc.
- Ajout et renommage des marques, modèles et catégories, sans suppression des références.
- Téléversement de photos JPEG/PNG/WebP, affichage et masquage, sans suppression des fichiers historiques.
- Gestion des demandes et des transitions de statut par les agents ; recherche et suspension/réactivation des comptes clients.
- Contrôles serveur des rôles, CSRF sur les actions sensibles, échappement HTML, requêtes préparées et en-têtes de sécurité.
- Sessions strictes, cookie dédié `LPSESSID` HttpOnly/SameSite, expiration après deux heures d'inactivité ; suspension et changement de mot de passe invalidant les accès existants.

Aucun paiement réel, envoi d'email ou service externe n'est intégré. Aucun véhicule réel ni compte agent n'est fourni par les données initiales.

## Technologies et prérequis vérifiés

PHP 8.3.36, PDO et MariaDB 10.11.16, HTML, CSS et JavaScript natif. Aucun framework ni gestionnaire de dépendances nécessaire.

Extensions applicatives : `pdo_mysql`, `mbstring`, `fileinfo`, `session`. Tests et aperçu : `pdo_sqlite`, `tokenizer`, PHP CGI et `proc_open`. Git pour les branches et commits. Node est utilisé uniquement pour vérifier la syntaxe JavaScript pendant le développement.

Les requêtes applicatives sont testées sur une vraie connexion PDO à MariaDB et sur SQLite. La compatibilité avec d'autres versions de MySQL n'est pas certifiée.

## Installation sur Windows avec XAMPP

### 1. Dossier et branche

Cloner le dépôt ou ouvrir la copie de travail, puis sélectionner :

```powershell
git switch main
```

Copie de travail de cette exécution : `C:\Users\Rayane\Documents\Codex\2026-11-23\bonj\location-platform`. L'installation source `C:\xampp\htdocs\location-plateform` n'a pas été modifiée : ouvrir cette ancienne URL ne montre pas automatiquement cette branche.

### 2. Base de données

Démarrer MariaDB avec XAMPP et choisir un seul des parcours suivants dans phpMyAdmin.

| État de la base | Fichiers à importer, dans cet ordre |
| --- | --- |
| Base neuve et vide | `database/schema.sql`, puis `database/seed.sql` |
| Ancien schéma généraliste | Sauvegarde et essai sur copie, puis migrations `001_vehicle_catalogue.sql`, `002_complete_platform.sql` et `003_vehicle_details.sql` |
| Migration automobile 001 déjà appliquée | Sauvegarde et essai sur copie, puis `002_complete_platform.sql`, puis `003_vehicle_details.sql` |
| Migration complète 002 déjà appliquée | Uniquement `003_vehicle_details.sql` |

Le schéma neuf et le seed ciblent `location_platform`. Les migrations s'importent dans la base explicitement sélectionnée. Ne pas appliquer les migrations après le schéma neuf, ni réimporter le seed sur une base existante. Les migrations sont prévues pour une application unique ; elles ne sont pas idempotentes. Les DDL MariaDB provoquent des validations implicites : une simple transaction n'annule pas une migration interrompue.

Les tables historiques `users`, `categories`, `articles`, `article_images` et `locations` sont conservées. Les trois migrations ajoutent les références automobiles, caractéristiques, catégories dédiées, métadonnées de réservation, champs de sécurité et index. Les entrées non classées restent hors catalogue, sans réaffectation automatique. Les champs inconnus restent inconnus et s'affichent « À préciser ».

Aucune migration n'a été appliquée à la base utilisateur existante pendant cette exécution. Les valeurs des paramètres de connexion existants ont été conservées. Seul leur traitement d'erreur a été adapté pour renvoyer une réponse générique 503.

### 3. Configuration de connexion

Le fichier `config/database.php` est local et ignoré par Git. Pour une nouvelle installation, copier le modèle puis renseigner vos identifiants uniquement dans cette copie :

```powershell
Copy-Item config/database.example.php config/database.php
```

L'application réutilise `config/database.php` si aucune variable de connexion n'est fournie. Les variables d'environnement suivantes permettent de configurer la connexion sans publier de secret :

```powershell
$env:APP_ENV = 'local'
$env:APP_DATABASE_DSN = 'mysql:host=127.0.0.1;dbname=location_platform;charset=utf8mb4'
$env:APP_DATABASE_USER = 'VOTRE_UTILISATEUR_LOCAL'
$env:APP_DATABASE_PASSWORD = 'VOTRE_MOT_DE_PASSE_LOCAL'
```

Remplacer les valeurs uniquement sur votre poste. Ne pas les placer dans Git ni les communiquer dans un rapport. Aucun fichier `.env` n'est chargé automatiquement.

### 4. Démarrage

Depuis la racine du dépôt :

```powershell
& C:\xampp\php\php.exe -d upload_max_filesize=5M -d post_max_size=6M -S 127.0.0.1:8089 server_router.php
```

Ouvrir <http://127.0.0.1:8089>. Le routeur est nécessaire pour empêcher l'accès direct aux données, outils, tests et fichiers internes. Le serveur intégré est réservé au développement.

Pour Apache, les protections `.htaccess` nécessitent une configuration autorisant ces directives ; vérifier cette configuration avant utilisation. `.user.ini` définit les limites de téléversement pour les installations CGI compatibles. Avec Apache/module PHP, régler localement les limites à 5 Mo pour les fichiers et 6 Mo pour le POST. Ne pas exposer le dossier sans ses protections.

### 5. Compte agent

L'inscription crée uniquement des clients. Créer votre compte dans l'interface, puis, dans le terminal du propriétaire et avec la même configuration de base :

```powershell
& C:\xampp\php\php.exe tools\promote-agent.php votre-adresse@example.com
```

Se reconnecter et ouvrir `/agent/index.php`. Aucun mot de passe par défaut n'est fourni. Cette commande n'est pas accessible par HTTP. Les agents peuvent gérer le parc et les réservations, et suspendre des clients ; l'interface ne permet ni de promouvoir un compte ni de suspendre d'autres agents.

## Aperçu isolé de démonstration

Pour examiner le design sans modifier une base MySQL :

```powershell
& C:\xampp\php\php.exe tools\demo.php
$env:APP_ENV = 'demo'
$env:APP_DATABASE_DSN = 'sqlite:C:/CHEMIN/DU/DEPOT/var/preview.sqlite'
& C:\xampp\php\php.exe -d upload_max_filesize=5M -d post_max_size=6M -S 127.0.0.1:8088 server_router.php
```

Le générateur refuse de remplacer un aperçu existant. Il initialise un catalogue local puis applique les six visuels de présentation. Aucun compte utilisateur ajouté. Le stock et les caractéristiques doivent être validés par le propriétaire avant exploitation.

Un aperçu a été lancé sur <http://127.0.0.1:8088> pour cette revue ; il reste disponible tant que le processus local fonctionne. SQLite est destiné à l'aperçu et aux tests ; MariaDB reste la base applicative cible.

## Règles de réservation

- Départ à partir d'aujourd'hui et dans les 365 prochains jours ; retour strictement postérieur ; maximum 90 jours.
- Retour exclusif : du 1 au 3 compte pour 2 jours. Deux locations peuvent se suivre à la date de retour.
- Prix journalier lu en base et calcul en centimes, indépendamment de tout montant envoyé par le navigateur. Montants affichés en MAD, sans conversion des anciennes données.
- Les statuts `en_attente`, `confirmee` et `en_cours` bloquent les dates. Les demandes en attente n'expirent pas automatiquement : l'équipe doit les traiter.
- Une demande n'est confirmée qu'après décision d'un agent. Aucun paiement n'est prélevé.
- Le client peut annuler une demande en attente ou confirmée avant le jour du départ.
- Transitions agents : attente vers confirmation/refus/annulation ; confirmation vers départ/annulation ; départ vers terminé. Les états finaux ne se rouvrent pas.
- Le départ doit intervenir pendant la période réservée. Les confirmations vérifient à nouveau le parc et les conflits.
- Masquer une voiture, suspendre un compte ou modifier un tarif ne supprime ni n'annule les anciennes réservations.

## Photos

Maximum 8 photos actives par voiture, 5 Mo par fichier, JPEG/PNG/WebP uniquement. Le serveur vérifie type réel, structure et dimensions ; les noms sont aléatoires. Les fichiers sont conservés dans `var/uploads/`, protégés contre l'accès direct. Seul `photo.php` les sert après contrôle du véhicule et de la visibilité. Masquer conserve le fichier et l'enregistrement.

Les anciens chemins d'image sont conservés. Un chemin historique ne respectant pas le format sûr affiche une illustration ; le propriétaire doit téléverser les photos réelles à nouveau. La première photo active est utilisée sur la carte.

## Tests

Suite isolée SQLite avec vrais formulaires exécutés par PHP CGI :

```powershell
& C:\xampp\php\php.exe tests\run.php
```

Même suite contre un serveur MariaDB de test dédié, distinct de la base utilisateur :

```powershell
$env:TEST_SERVER_DSN = 'mysql:host=127.0.0.1;port=13307;charset=utf8mb4'
$env:TEST_DATABASE_USER = 'UTILISATEUR_DU_SERVEUR_DE_TEST'
$env:TEST_DATABASE_PASSWORD = 'MOT_DE_PASSE_DU_SERVEUR_DE_TEST'
& C:\xampp\php\php.exe tests\run.php
```

Le DSN de test ne doit contenir aucun `dbname`. La suite crée une nouvelle base au nom aléatoire ; elle n'efface pas de base existante. Le compte doit avoir les droits sur cette nouvelle base. Le serveur isolé utilisé pendant le développement n'est pas un prérequis déjà installé chez un autre utilisateur.

Migrations, contraintes et conservation de données, sans serveur réseau :

```powershell
& C:\xampp\php\php.exe tests\migrations.php
& C:\xampp\php\php.exe tests\source-policy.php
Get-ChildItem -Recurse -Filter '*.php' |
    Where-Object { $_.FullName -notmatch '\\var\\' } |
    ForEach-Object {
        & C:\xampp\php\php.exe -l $_.FullName
        if ($LASTEXITCODE -ne 0) { throw 'Syntaxe PHP invalide' }
    }
node --check assets/app.js
git diff --check
```

Le contrôle des migrations nécessite MariaDB ; son chemin Windows par défaut est `C:/xampp/mysql/bin/mysqld.exe`. Définir `MYSQLD_BINARY` ou `PHP_CGI_BINARY` si vos exécutables sont ailleurs. Les essais utilisent des dossiers uniques sous `var/` et conservent leurs sorties. Ne pas supprimer globalement `var/` : il contient aussi les photos et l'aperçu.

Résultats vérifiés le 24 novembre 2026 : 120 contrôles réussis sur SQLite et 120 sur PDO MariaDB, dont deux processus concurrents de réservation sur chaque moteur ; 8 scénarios MariaDB d'installation/migration/contraintes réussis. Les sources sont contrôlées sans commentaires interdits, et la palette ainsi que les contrastes de base sont vérifiés. Une revue complète de conformité WCAG ou un audit externe de sécurité ne sont pas revendiqués.

## Vérification visuelle

1. Ouvrir l'accueil puis le catalogue : tester les filtres marque/modèle et les budgets.
2. Ouvrir une voiture, saisir des dates et vérifier le total affiché.
3. Créer un compte, se connecter, envoyer une demande et retrouver son détail dans l'espace client.
4. Dans un autre compte, vérifier que la réservation n'est pas accessible.
5. Avec un compte agent, ajouter une voiture réelle, sa photo et modifier sa visibilité.
6. Confirmer une demande ; vérifier le nouveau statut côté client, puis tester l'annulation avant le départ.
7. Examiner les pages à 375, 768 et 1440 pixels. Le menu mobile, les filtres et le devis ont été vérifiés dans Chrome ; aucune simulation de navigateur n'est présentée comme une vérification de Safari ou Firefox.

## Structure

| Dossier ou fichier | Rôle |
| --- | --- |
| Pages PHP à la racine | Accueil, catalogue, comptes, fiches et réservations |
| `agent/` | Tableau de bord, parc, références, photos, demandes et clients |
| `api/disponibilite.php` | Devis et disponibilité pour les dates choisies |
| `app/`, `app/views/` | Accès aux données, validation, sécurité, transactions et présentation commune |
| `assets/` | CSS, JavaScript natif et illustrations |
| `config/database.example.php` | Modèle de connexion ; `config/database.php` reste local et ignoré |
| `database/` | Schéma neuf, références et migrations additives |
| `tools/` | Aperçu isolé et attribution locale du rôle agent |
| `tests/` | Régression, concurrence, CGI, migration et politique de sources |
| `var/` | Photos et données locales ignorées par Git, sauf la protection HTTP |
| `docs/` | État fonctionnel et journal de développement |

## Limites avant exploitation commerciale

Configurer l'exploitant, ses coordonnées et ses véritables conditions contractuelles, les assurances et justificatifs, le retrait du véhicule et la politique de confidentialité. Ajouter les vraies voitures et les photos autorisées. Configurer HTTPS, les sauvegardes et les droits du serveur. Le mécanisme d'email de confirmation, la récupération de mot de passe et les paiements ne sont pas implémentés. Les demandes en attente et la conservation des journaux d'échecs de connexion nécessitent une gestion opérationnelle.

La base utilisateur historique et la configuration Apache existante n'ont pas servi à un déploiement. L'installation neuve et les migrations ont été vérifiées sur des bases isolées, y compris avec PDO MariaDB.

## Synchronisation Git

La version intégrée est disponible sur la branche `main` du dépôt existant :

```powershell
git fetch origin
git switch main
git pull --ff-only origin main
```

La publication du code sur GitHub ne déploie pas le site et ne met pas à jour XAMPP ni la base de données locale.
