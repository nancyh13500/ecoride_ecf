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

### Option 1 : Déploiement via Git (Recommandé)

1. **Accédez à Portainer** et allez dans **Stacks**

2. **Cliquez sur "Add stack"**

3. **Configurez la stack :**

   - **Name** : `ecoride` (ou le nom de votre choix)
   - **Build method** : Sélectionnez **Repository**
   - **Repository URL** : URL de votre dépôt Git
     - Exemple : `https://github.com/votre-username/ecoride_ecf.git`
   - **Repository reference** : `main` ou `master` (selon votre branche principale)
   - **Compose path** : `docker-compose.yml`
   - **Repository authentication** : Si votre repo est privé, configurez les credentials

4. **Important : Configuration du chemin de clonage**

   Par défaut, Portainer clone dans un répertoire temporaire. Pour que le code soit dans `/home/docker/web/ecoride_ecf/`, vous avez deux options :

   **Option A : Cloner manuellement avant**

   ```bash
   cd /home/docker/web/ecoride_ecf
   git clone https://github.com/votre-username/ecoride_ecf.git .
   ```

   Puis dans Portainer, utilisez l'option "Upload" au lieu de "Repository"

   **Option B : Utiliser le répertoire cloné par Portainer**

   Si Portainer clone dans `/data/compose/X/` (où X est l'ID de la stack), vous devrez soit :

   - Modifier le `docker-compose.yml` pour pointer vers ce répertoire
   - Ou créer un lien symbolique : `ln -s /data/compose/X /home/docker/web/ecoride_ecf`

5. **Cliquez sur "Deploy the stack"**

### Option 2 : Upload manuel

1. **Clonez le projet sur le serveur :**

   ```bash
   cd /home/docker/web/ecoride_ecf
   git clone https://github.com/votre-username/ecoride_ecf.git .
   ```

2. **Dans Portainer :**
   - Allez dans **Stacks** > **Add stack**
   - **Name** : `ecoride`
   - **Build method** : Sélectionnez **Upload**
   - **Compose file** : Copiez-collez le contenu de `docker-compose.yml`
   - Cliquez sur **Deploy the stack**

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

### Via Git (si déployé avec Repository)

1. Dans Portainer, allez dans votre stack
2. Cliquez sur **Editor**
3. Cliquez sur **Pull and redeploy** pour récupérer les dernières modifications

### Via Git manuel

```bash
cd /home/docker/web/ecoride_ecf
git pull origin main
# Redémarrez la stack dans Portainer
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
