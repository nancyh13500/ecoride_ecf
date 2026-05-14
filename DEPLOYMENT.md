# Guide de déploiement avec Portainer

## Prérequis

- Portainer installé et accessible
- Accès au serveur Docker où Portainer est installé
- Fichier `ecoride.sql`, `alter_covoiturage.sql` et `create_site_credits.sql` disponibles dans le repository

## Configuration des variables d'environnement

### Option 1 : Variables d'environnement dans Portainer

Lors du déploiement de la stack dans Portainer, configurez les variables d'environnement suivantes si elles sont référencées dans le fichier Compose avec la syntaxe `${VARIABLE}`.

Le fichier `docker-compose.yml` actuel contient déjà les valeurs nécessaires au déploiement. Si vous utilisez ces variables dans Portainer, gardez `MYSQL_PASSWORD` et `DB_PASS` identiques.

```env
# Configuration MySQL
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=ecoride
MYSQL_USER=ecoride_user
MYSQL_PASSWORD=ecoride_pass

# Configuration MongoDB
MONGO_USER=mongodb_user
MONGO_PASS=mongodb_pass
MONGO_DB=ecoride

# Configuration de l'application
DOCKER_ENV=1
DB_HOST=db
DB_NAME=ecoride
DB_USER=ecoride_user
DB_PASS=ecoride_pass
MONGO_HOST=mongodb
MONGO_PORT=27017

# Configuration SMTP si le mailing est activé
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=votre-email@example.com
SMTP_PASSWORD=votre-mot-de-passe
MAIL_FROM=noreply@ecoride.fr
MAIL_FROM_NAME=EcoRide
CONTACT_EMAIL=contact@ecoride.fr
```

### Option 2 : Utiliser un fichier .env (pour développement local)

Créez un fichier `.env` à la racine du projet avec le contenu suivant :

```env
# Configuration de la base de données MySQL
DB_HOST=db
DB_NAME=ecoride
DB_USER=ecoride_user
DB_PASS=ecoride_pass

# Configuration MongoDB
MONGO_HOST=mongodb
MONGO_PORT=27017
MONGO_DB=ecoride
MONGO_USER=mongodb_user
MONGO_PASS=mongodb_pass

# Environnement
# 1 = Docker/Portainer
DOCKER_ENV=1
```

## Déploiement via Portainer

### Méthode 1 : Stack via Git Repository (recommandé pour production)

1. **Connectez-vous à Portainer**
   - Accédez à l'interface web de Portainer
   - Sélectionnez votre environnement Docker

2. **Créer une nouvelle Stack**
   - Menu gauche : **Stacks**
   - Cliquez sur **Add stack**
   - Nom de la stack : `ecoride`

3. **Configuration du Repository**
   - Sélectionnez **Repository**
   - Repository URL : URL de votre repository Git
   - Repository reference : `main` ou `master` (selon votre branche)
   - Compose path : `docker-compose.yml` ou `docker-compose-mail.yml` si vous déployez la configuration avec SMTP
   - Configurez les variables d'environnement dans l'onglet dédié si votre fichier Compose les référence
   - Cliquez sur **Deploy the stack**

### Méthode 2 : Stack via Web Editor

1. **Connectez-vous à Portainer**
2. **Créer une nouvelle Stack**
   - Menu gauche : **Stacks** > **Add stack**
   - Nom de la stack : `ecoride`
   - Sélectionnez **Web editor**
   - Copiez-collez le contenu de `docker-compose.yml` ou `docker-compose-mail.yml` si vous déployez la configuration avec SMTP
   - Configurez les variables d'environnement si votre fichier Compose les référence
   - Cliquez sur **Deploy the stack**

## Vérification du déploiement

1. **Vérifier les conteneurs**
   - Dans Portainer, allez dans **Containers**
   - Vérifiez que tous les conteneurs sont en état "Running" :
     - `ecoride_app`
     - `ecoride_db`
     - `ecoride_mongodb`
     - `ecoride_phpmyadmin`

2. **Vérifier les logs**
   - Cliquez sur chaque conteneur
   - Vérifiez les logs pour détecter d'éventuelles erreurs

3. **Accéder à l'application**
   - Application : `http://votre-serveur:8000`
   - phpMyAdmin : `http://votre-serveur:8080`

## Gestion de la Stack

### Mettre à jour la Stack

1. Allez dans **Stacks** > `ecoride`
2. Cliquez sur **Editor**
3. Modifiez le fichier Compose si nécessaire
4. Cliquez sur **Update the stack**

### Redémarrer les services

1. Allez dans **Stacks** > `ecoride`
2. Cliquez sur **Editor**
3. Cliquez sur **Update the stack** (sans modification)

### Arrêter la Stack

1. Allez dans **Stacks** > `ecoride`
2. Cliquez sur **Stop**

### Supprimer la Stack

1. Allez dans **Stacks** > `ecoride`
2. Cliquez sur **Remove**
3. ⚠️ **Attention** : Cela supprime aussi les volumes (données) sauf si vous les conservez explicitement

## Fichiers à vérifier

- ✅ `lib/config.php` : Configuration de la base de données MySQL
- ✅ `lib/mongodb.php` : Configuration MongoDB
- ✅ `docker-compose.yml` : Configuration Docker Compose
- ✅ `docker-compose-mail.yml` : Configuration Docker Compose avec SMTP
- ✅ `.env` : Variables d'environnement (optionnel, pour développement local)

## Notes importantes pour Portainer

- Le fichier `.env` doit être protégé et ne pas être accessible publiquement
- Assurez-vous que le fichier `.env` est dans le `.gitignore` si vous utilisez Git
- Les variables d'environnement définies dans Portainer ont la priorité sur le fichier `.env`, à condition que le fichier Compose les transmette au conteneur
- Les fichiers `ecoride.sql`, `create_site_credits.sql` et `alter_covoiturage.sql` doivent être présents dans le repository pour l'initialisation automatique de la base de données
- Les données sont persistées dans des volumes Docker nommés (`mysql_data`, `mongodb_data`). Dans Portainer, ils peuvent être préfixés par le nom de la stack, par exemple `ecoride_mysql_data`

## Sauvegarde des données

Les données créées par les conteneurs sont stockées dans des volumes Docker :

- `mysql_data` ou `ecoride_mysql_data` : Base de données MySQL
- `mongodb_data` ou `ecoride_mongodb_data` : Base de données MongoDB

Pour sauvegarder via Portainer :

1. Allez dans **Volumes**
2. Sélectionnez le volume à sauvegarder
3. Utilisez les options de sauvegarde disponibles

## Dépannage

### Les conteneurs ne démarrent pas

- Vérifiez les logs dans Portainer
- Vérifiez que les ports ne sont pas déjà utilisés
- Vérifiez que les fichiers SQL d'initialisation sont accessibles dans le repository

### Erreur de connexion à la base de données

- Vérifiez les variables d'environnement dans Portainer
- Vérifiez que le conteneur `ecoride_db` est en état "Running"
- Consultez les logs du conteneur `ecoride_db`

### L'application ne charge pas

- Vérifiez les logs du conteneur `ecoride_app`
- Vérifiez que le port `8000` est correctement exposé
- Vérifiez les permissions des fichiers
