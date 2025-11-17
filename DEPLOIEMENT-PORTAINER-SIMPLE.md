# Guide de déploiement EcoRide avec Portainer - Version Simplifiée

Ce guide explique comment déployer EcoRide directement depuis Git dans Portainer, sans configuration manuelle.

## ✅ Configuration automatique

Le `docker-compose.yml` est configuré pour fonctionner directement avec l'option "Repository" de Portainer :
- ✅ Chemins relatifs pour le code (Portainer clone automatiquement)
- ✅ Chemins absolus pour les données persistantes (MySQL, MongoDB)
- ✅ Pas besoin de cloner manuellement le projet

## 🚀 Déploiement en 3 étapes

### Étape 1 : Préparer le serveur (une seule fois)

```bash
# Se connecter au serveur
ssh docker@votre-serveur

# Créer les répertoires de données (seront créés automatiquement si absents)
sudo mkdir -p /home/docker/web/ecoride_ecf/mysql_data
sudo mkdir -p /home/docker/web/ecoride_ecf/mongodb_data

# Permissions pour MySQL
sudo chown -R 999:999 /home/docker/web/ecoride_ecf/mysql_data
sudo chmod -R 750 /home/docker/web/ecoride_ecf/mysql_data

# Permissions pour MongoDB
sudo chown -R docker:docker /home/docker/web/ecoride_ecf/mongodb_data
sudo chmod -R 755 /home/docker/web/ecoride_ecf/mongodb_data

# Créer le réseau Docker (si pas déjà fait)
docker network create ecoride
```

### Étape 2 : Déployer dans Portainer

1. **Accédez à Portainer** et allez dans **Stacks**

2. **Cliquez sur "Add stack"**

3. **Configurez la stack :**
   - **Name** : `ecoride`
   - **Build method** : Sélectionnez **Repository**
   - **Repository URL** : `https://github.com/votre-username/ecoride_ecf.git`
   - **Repository reference** : `dev` (votre branche)
   - **Compose path** : `docker-compose.yml`
   - **Repository authentication** : Si votre repo est privé, configurez les credentials

4. **Cliquez sur "Deploy the stack"**

Portainer va :
- ✅ Cloner automatiquement le projet depuis Git
- ✅ Builder l'image Docker
- ✅ Démarrer tous les conteneurs
- ✅ Installer les dépendances Composer automatiquement

### Étape 3 : Vérifier le déploiement

Dans Portainer, allez dans **Containers** et vérifiez que tous les conteneurs sont en état "Running" :
- `ecoride_app`
- `ecoride_db`
- `ecoride_mongodb`
- `ecoride_phpmyadmin`

## 📁 Structure des volumes

- **Code de l'application** : Monté depuis le répertoire cloné par Portainer (chemin relatif)
- **Données MySQL** : `/home/docker/web/ecoride_ecf/mysql_data` (chemin absolu)
- **Données MongoDB** : `/home/docker/web/ecoride_ecf/mongodb_data` (chemin absolu)
- **Fichier SQL** : Depuis le repo cloné (chemin relatif)

## 🔄 Mise à jour du projet

Pour mettre à jour le projet après un `git push` :

1. Dans Portainer, allez dans votre stack `ecoride`
2. Cliquez sur **Editor**
3. Cliquez sur **Pull and redeploy**
4. Portainer va automatiquement :
   - Récupérer les dernières modifications depuis Git
   - Rebuilder l'image si nécessaire
   - Redémarrer les conteneurs

## 🐛 Dépannage

### Les conteneurs ne démarrent pas

1. Vérifiez les logs dans Portainer :
   - Allez dans **Containers**
   - Cliquez sur le conteneur concerné
   - Regardez les **Logs**

2. Vérifiez que le réseau existe :
   ```bash
   docker network ls | grep ecoride
   ```
   Si absent, créez-le :
   ```bash
   docker network create ecoride
   ```

### Erreur "Cannot locate specified Dockerfile"

- Vérifiez que le fichier `docker/php/Dockerfile` existe dans votre repo Git
- Vérifiez que vous êtes sur la bonne branche (`dev`)

### Erreur de permissions

```bash
sudo chown -R docker:docker /home/docker/web/ecoride_ecf
sudo chmod -R 755 /home/docker/web/ecoride_ecf
```

### Le code n'apparaît pas dans le conteneur

- Vérifiez que Portainer a bien cloné le projet
- Regardez les logs du conteneur `ecoride_app`
- Vérifiez que le volume est bien monté : `docker inspect ecoride_app | grep Mounts`

## ✅ Avantages de cette configuration

- ✅ **Déploiement automatique** : Pas besoin de cloner manuellement
- ✅ **Mises à jour faciles** : Un clic dans Portainer
- ✅ **Données persistantes** : MySQL et MongoDB dans `/home/docker/web/ecoride_ecf/`
- ✅ **Versioning** : Le code est toujours synchronisé avec Git
- ✅ **Simple** : Fonctionne directement depuis Portainer

## 📝 Notes importantes

- Le projet est cloné automatiquement par Portainer dans un répertoire temporaire
- Les données (MySQL, MongoDB) sont stockées de manière persistante dans `/home/docker/web/ecoride_ecf/`
- Le code est monté depuis le répertoire cloné, donc les modifications Git sont immédiatement visibles après un "Pull and redeploy"
- Les dépendances Composer sont installées automatiquement au premier démarrage

