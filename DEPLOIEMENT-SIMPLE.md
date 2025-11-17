# Guide de déploiement EcoRide - Version Simple

Configuration simplifiée pour déployer depuis GitHub via Portainer.

## ✅ Configuration

- ✅ **Pas de port exposé** : accessible via reverse proxy (SWAG)
- ✅ **Chemins absolus** : données dans `/home/docker/web/ecoride_ecf/`
- ✅ **Code dans l'image** : copié lors du build, pas de problème de permissions
- ✅ **Déploiement depuis Git** : fonctionne directement avec Portainer

## 🚀 Déploiement en 2 étapes

### Étape 1 : Préparer le serveur (une seule fois)

```bash
# Créer les répertoires de données
sudo mkdir -p /home/docker/web/ecoride_ecf/mysql_data
sudo mkdir -p /home/docker/web/ecoride_ecf/mongodb_data

# Permissions MySQL
sudo chown -R 999:999 /home/docker/web/ecoride_ecf/mysql_data
sudo chmod -R 750 /home/docker/web/ecoride_ecf/mysql_data

# Permissions MongoDB
sudo chown -R docker:docker /home/docker/web/ecoride_ecf/mongodb_data
sudo chmod -R 755 /home/docker/web/ecoride_ecf/mongodb_data

# Créer le réseau Docker
docker network create ecoride
```

### Étape 2 : Déployer dans Portainer

1. **Dans Portainer**, allez dans **Stacks** > **Add stack**

2. **Configurez :**
   - **Name** : `ecoride`
   - **Build method** : **Repository**
   - **Repository URL** : `https://github.com/votre-username/ecoride_ecf.git`
   - **Repository reference** : `dev`
   - **Compose path** : `docker-compose.yml`

3. **Cliquez sur "Deploy the stack"**

C'est tout ! Portainer va :
- Cloner le projet depuis GitHub
- Builder l'image avec le code copié dedans
- Installer Composer automatiquement
- Démarrer tous les conteneurs

## 📁 Structure

- **Code** : Copié dans l'image Docker (pas de volume)
- **Données MySQL** : `/home/docker/web/ecoride_ecf/mysql_data`
- **Données MongoDB** : `/home/docker/web/ecoride_ecf/mongodb_data`
- **Fichier SQL** : Monté depuis le repo cloné (`./ecoride.sql`)

## 🔄 Mise à jour

Dans Portainer :
1. Allez dans votre stack `ecoride`
2. Cliquez sur **Editor**
3. Cliquez sur **Pull and redeploy**

## 🌐 Accès via SWAG

Votre configuration SWAG doit pointer vers :
- **Container** : `ecoride_app`
- **Port** : `80`
- **Réseau** : `ecoride` (assurez-vous que SWAG est sur ce réseau)

## ✅ Avantages

- **Simple** : Pas de script d'entrypoint, pas de correction de permissions
- **Fiable** : Le code est dans l'image avec les bonnes permissions
- **Rapide** : Pas de montage de volume, tout est dans l'image
- **Sécurisé** : Pas de port exposé sur l'hôte

