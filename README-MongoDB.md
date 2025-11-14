# Intégration MongoDB pour les Avis

## Vue d'ensemble

Les avis déposés sur le site sont maintenant stockés dans MongoDB au lieu de MySQL. Cette solution offre une meilleure flexibilité et performance pour la gestion des avis.

## Architecture

- **MySQL** : Continue de stocker les données principales (utilisateurs, covoiturages, réservations)
- **MongoDB** : Stocke uniquement les avis avec leur statut de validation

## Configuration Docker

MongoDB a été ajouté au `docker-compose.yml` avec les paramètres suivants :
- **Image** : mongo:7.0
- **Port** : 27017
- **Utilisateur** : mongodb_user
- **Mot de passe** : mongodb_pass
- **Base de données** : ecoride

## Installation

### 1. Reconstruire les conteneurs Docker

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### 2. Installer les dépendances Composer

Les dépendances MongoDB seront installées automatiquement lors du démarrage du conteneur `app`, ou vous pouvez les installer manuellement :

```bash
docker-compose exec app composer install
```

## Structure des données MongoDB

### Collection : `avis`

Chaque document avis contient :
```json
{
  "_id": ObjectId("..."),
  "user_id": 123,
  "note": 5,
  "commentaire": "Excellent service !",
  "statut": "en attente" | "valide" | "refuse",
  "created_at": ISODate("..."),
  "updated_at": ISODate("..."),
  "validated_by": 456,        // ID de l'employé qui a validé (si validé)
  "validated_at": ISODate("..."), // Date de validation (si validé)
  "rejected_by": 456,          // ID de l'employé qui a refusé (si refusé)
  "rejected_at": ISODate("...")  // Date de refus (si refusé)
}
```

## Fonctionnalités

### Pour les utilisateurs
- **Déposer un avis** : `/pages/deposer_avis.php`
  - L'avis est créé avec le statut "en attente"
  - Nécessite une validation par un employé avant publication

### Pour les employés
- **Valider les avis** : `/pages/employe.php`
  - Onglet "Avis à valider"
  - Actions disponibles : Valider, Refuser, Supprimer
  - Seuls les utilisateurs avec `role_id = 2` peuvent accéder

### Affichage public
- **Page des avis** : `/pages/avis.php`
  - Affiche uniquement les avis avec le statut "valide"
  - Les informations utilisateur sont récupérées depuis MySQL

## Fichiers modifiés

1. **docker-compose.yml** : Ajout du service MongoDB
2. **docker/php/Dockerfile** : Installation de l'extension MongoDB PHP
3. **composer.json** : Ajout de la dépendance `mongodb/mongodb`
4. **lib/mongodb.php** : Nouveau fichier pour la connexion MongoDB
5. **pages/deposer_avis.php** : Utilise MongoDB pour créer les avis
6. **pages/avis.php** : Lit les avis depuis MongoDB
7. **pages/employe.php** : Gère la validation des avis depuis MongoDB

## Gestion des erreurs

Le système gère gracieusement les erreurs :
- Si MongoDB n'est pas disponible, un message d'erreur approprié est affiché
- Les données utilisateur sont toujours récupérées depuis MySQL
- Les erreurs sont loggées dans les logs PHP

## Migration des données existantes (optionnel)

Si vous avez des avis existants dans MySQL, vous pouvez les migrer vers MongoDB avec un script de migration. Contactez l'administrateur pour plus d'informations.

## Vérification

Pour vérifier que MongoDB fonctionne correctement :

1. Vérifier que le conteneur MongoDB est démarré :
   ```bash
   docker-compose ps
   ```

2. Tester la connexion depuis le conteneur PHP :
   ```bash
   docker-compose exec app php -r "require 'vendor/autoload.php'; require 'lib/mongodb.php'; var_dump(getAvisCollection());"
   ```

3. Déposer un avis de test depuis l'interface web

4. Vérifier dans l'espace employé que l'avis apparaît en attente

5. Valider l'avis et vérifier qu'il apparaît sur la page publique

## Support

En cas de problème :
1. Vérifier les logs Docker : `docker-compose logs mongodb`
2. Vérifier les logs PHP : `docker-compose logs app`
3. Vérifier que l'extension MongoDB est installée : `docker-compose exec app php -m | grep mongodb`

