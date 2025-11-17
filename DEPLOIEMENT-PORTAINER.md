# Guide de déploiement EcoRide avec Portainer

Ce guide explique comment déployer le projet EcoRide sur un serveur via Portainer en utilisant Git.

## Prérequis

- Serveur avec Docker et Portainer installés
- Utilisateur `docker` avec les droits nécessaires
- Accès au dépôt Git du projet
- Réseau Docker `ecoride` créé

## Préparation du serveur

### 1. Créer les répertoires nécessaires

```bash
# Créer le répertoire principal
sudo mkdir -p /home/docker/web/ecoride_ecf
sudo chown -R docker:docker /home/docker/web/ecoride_ecf
sudo chmod -R 755 /home/docker/web/ecoride_ecf

# Créer les répertoires de données
sudo mkdir -p /home/docker/web/ecoride_ecf/mysql_data
sudo mkdir -p /home/docker/web/ecoride_ecf/mongodb_data

# Permissions pour MySQL (UID 999 = utilisateur mysql dans le conteneur)
sudo chown -R 999:999 /home/docker/web/ecoride_ecf/mysql_data
sudo chmod -R 750 /home/docker/web/ecoride_ecf/mysql_data

# Permissions pour MongoDB
sudo chown -R docker:docker /home/docker/web/ecoride_ecf/mongodb_data
sudo chmod -R 755 /home/docker/web/ecoride_ecf/mongodb_data
```

### 2. Créer le réseau Docker

```bash
docker network create ecoride
```

### 3. Vérifier que tout est prêt

```bash
# Vérifier les répertoires
ls -ld /home/docker/web/ecoride_ecf
ls -ld /home/docker/web/ecoride_ecf/mysql_data
ls -ld /home/docker/web/ecoride_ecf/mongodb_data

# Vérifier le réseau
docker network ls | grep ecoride
```

## Déploiement dans Portainer

### ⚠️ IMPORTANT : Cloner le projet d'abord

**Avant de déployer dans Portainer, vous DEVEZ cloner le projet dans `/home/docker/web/ecoride_ecf/`** car tous les chemins dans `docker-compose.yml` sont absolus et pointent vers ce répertoire.

```bash
# Se connecter au serveur en tant qu'utilisateur docker
cd /home/docker/web

# Cloner le projet (remplacez par votre URL Git)
git clone https://github.com/votre-username/ecoride_ecf.git ecoride_ecf

# Vérifier que les fichiers sont présents
ls -la /home/docker/web/ecoride_ecf/
# Vous devriez voir : index.php, docker-compose.yml, lib/, pages/, etc.
```

### Option 1 : Upload manuel (Recommandé)

1. **Dans Portainer :**
   - Allez dans **Stacks** > **Add stack**
   - **Name** : `ecoride`
   - **Build method** : Sélectionnez **Upload**
   - **Compose file** : Ouvrez le fichier `/home/docker/web/ecoride_ecf/docker-compose.yml` sur le serveur et copiez-collez son contenu
   - Cliquez sur **Deploy the stack**

### Option 2 : Déploiement via Git (nécessite configuration supplémentaire)

Si vous voulez utiliser l'option "Repository" de Portainer :

1. **Cloner le projet manuellement d'abord** (voir ci-dessus)

2. **Dans Portainer :**
   - **Build method** : Sélectionnez **Repository**
   - **Repository URL** : URL de votre dépôt Git
   - **Repository reference** : `main` ou `master`
   - **Compose path** : `docker-compose.yml`
   - ⚠️ **Important** : Après le clonage, Portainer va cloner dans un répertoire temporaire (ex: `/data/compose/X/`), mais votre `docker-compose.yml` pointe vers `/home/docker/web/ecoride_ecf/`. Vous devrez copier les fichiers :
     ```bash
     # Après le déploiement, copier les fichiers du répertoire cloné
     cp -r /data/compose/X/* /home/docker/web/ecoride_ecf/
     ```

**Recommandation** : Utilisez l'option "Upload" après avoir cloné manuellement le projet dans `/home/docker/web/ecoride_ecf/`.

## Structure des volumes

Tous les volumes utilisent des chemins absolus :

- **Code de l'application** : `/home/docker/web/ecoride_ecf` → `/var/www/html`
- **Données MySQL** : `/home/docker/web/ecoride_ecf/mysql_data` → `/var/lib/mysql`
- **Données MongoDB** : `/home/docker/web/ecoride_ecf/mongodb_data` → `/data/db`
- **Fichier SQL d'initialisation** : `/home/docker/web/ecoride_ecf/ecoride.sql` → `/docker-entrypoint-initdb.d/ecoride.sql`

## Vérification après déploiement

### 1. Vérifier que les conteneurs sont démarrés

Dans Portainer, allez dans **Containers** et vérifiez que tous les conteneurs sont en état "Running" :

- `ecoride_app`
- `ecoride_db`
- `ecoride_mongodb`
- `ecoride_phpmyadmin`

### 2. Vérifier les logs

```bash
# Logs de l'application
docker logs ecoride_app

# Logs de la base de données
docker logs ecoride_db

# Vérifier que Composer a installé les dépendances
docker exec ecoride_app ls -la /var/www/html/vendor
```

### 3. Vérifier que le code est monté

```bash
# Vérifier que les fichiers sont présents
docker exec ecoride_app ls -la /var/www/html

# Vérifier que index.php existe
docker exec ecoride_app test -f /var/www/html/index.php && echo "OK" || echo "Manquant"
```

### 4. Vérifier l'accès via SWAG/Reverse Proxy

Si vous utilisez SWAG, vérifiez que la configuration pointe vers `ecoride_app:80` et que les deux conteneurs sont sur le même réseau.

## Mise à jour du projet

### Mise à jour via Git

```bash
# Sur le serveur, mettre à jour le code
cd /home/docker/web/ecoride_ecf
git pull origin main

# Dans Portainer, redéployer la stack
# Allez dans votre stack > Editor > Update the stack
```

### Mise à jour automatique (optionnel)

Vous pouvez créer un script pour automatiser la mise à jour :

```bash
#!/bin/bash
# /home/docker/web/ecoride_ecf/update.sh

cd /home/docker/web/ecoride_ecf
git pull origin main

# Redémarrer les conteneurs si nécessaire
docker restart ecoride_app
```

Rendez-le exécutable :

```bash
chmod +x /home/docker/web/ecoride_ecf/update.sh
```

## Dépannage

### Le conteneur `ecoride_app` ne démarre pas

- Vérifiez les logs : `docker logs ecoride_app`
- Vérifiez que le répertoire `/home/docker/web/ecoride_ecf` contient les fichiers
- Vérifiez les permissions : `ls -ld /home/docker/web/ecoride_ecf`

### Erreur 403 "Cannot serve directory"

- Vérifiez que `index.php` existe : `docker exec ecoride_app ls -la /var/www/html/index.php`
- Vérifiez les permissions : `docker exec ecoride_app ls -ld /var/www/html`

### Composer ne trouve pas composer.json

- Vérifiez que le fichier existe : `docker exec ecoride_app test -f /var/www/html/composer.json`
- Vérifiez les logs du conteneur pour voir les messages de débogage

### Problème de permissions

```bash
# Donner les permissions à l'utilisateur docker
sudo chown -R docker:docker /home/docker/web/ecoride_ecf
sudo chmod -R 755 /home/docker/web/ecoride_ecf
```

## Notes importantes

- Le fichier `ecoride.sql` doit être présent dans votre dépôt Git à la racine du projet
- Les données MySQL et MongoDB sont persistantes dans `/home/docker/web/ecoride_ecf/mysql_data` et `/home/docker/web/ecoride_ecf/mongodb_data`
- Le code est monté depuis le serveur, donc les modifications sont immédiatement visibles (pas besoin de rebuild)
- Pour installer les dépendances Composer, elles seront installées automatiquement au premier démarrage si `vendor/autoload.php` n'existe pas
