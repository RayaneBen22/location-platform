# 🚗 Location Platform

Un site de location de voitures que je développe en PHP, avec un catalogue, un espace client et un espace de gestion pour les agents.

L’idée est simple : choisir une voiture, vérifier les dates et envoyer une demande de réservation. Côté agent, tout se passe dans un tableau de bord pour gérer le parc et suivre les demandes. 🔑

## ✨ Ce qu’on peut faire

### 🙋 Côté client

- Parcourir le catalogue et consulter les photos et les détails des voitures.
- Filtrer par marque, modèle, catégorie, budget, carburant, boîte de vitesses ou nombre de places.
- Chercher une voiture disponible pour une période donnée.
- Voir le prix estimé du séjour en MAD avant d’envoyer une demande.
- Créer un compte, gérer son profil et retrouver ses réservations.
- Annuler une demande en attente ou confirmée avant le jour du départ.

### 🛠️ Côté agent

- Ajouter et modifier les voitures, leurs caractéristiques et leurs tarifs.
- Gérer les photos, la visibilité et l’état des véhicules.
- Ajouter ou renommer les marques, modèles et catégories.
- Confirmer, refuser et suivre les demandes de réservation.
- Consulter les comptes clients et gérer leur activation.

Le site adapte aussi son affichage au téléphone, à la tablette et à l’ordinateur. 📱

## 💻 La stack

| Technologie | Utilisation |
| --- | --- |
| PHP 8.3 | Logique du site et traitement des formulaires |
| PDO + MariaDB | Accès à la base de données |
| HTML / CSS | Pages, design et affichage responsive |
| JavaScript | Interactions et estimation de réservation |
| SQLite | Aperçu local et tests isolés |

Le projet tourne sans framework et sans dépendances Composer ou npm à installer.
