# EcoRide - Configuration Docker

Ce projet utilise Docker Compose pour créer un environnement de développement complet avec MySQL, PHP et Apache.

## Prérequis

- Docker Desktop installé et démarré
- Docker Compose (inclus avec Docker Desktop)

## Structure des services

### Services inclus :

- **MySQL 8.0** : Base de données sur le port 3307 (exposé depuis le conteneur)
- **Apache + PHP 8.2** : Serveur web sur le port 8000 (exposé depuis le conteneur)
- **MongoDB 7.0** : Base de données NoSQL sur le port 27017
- **phpMyAdmin** : Interface d'administration de la base sur le port 8080

## Démarrage rapide

1. **Cloner le projet** (si pas déjà fait)

```bash
git clone <votre-repo>
cd ecoride_ecf
```

2. **Démarrer les services**

```bash
docker-compose up -d
```

3. **Vérifier que tout fonctionne**

```bash
docker-compose ps
```

## Accès aux services

- **Site web** : http://localhost:8000
- **phpMyAdmin** : http://localhost:8080
  - Utilisateur : `root`
  - Mot de passe : `root`
  - Base de données : `ecoride`

## Commandes utiles

### Démarrer/Arrêter

```bash
# Démarrer en arrière-plan
docker-compose up -d

# Arrêter
docker-compose down

# Redémarrer
docker-compose restart
```

### Logs

```bash
# Voir les logs de tous les services
docker-compose logs

# Logs d'un service spécifique
docker-compose logs app
docker-compose logs db
```

### Base de données

```bash
# Se connecter à MySQL
docker-compose exec db mysql -u root -p

# Sauvegarder la base
docker-compose exec db mysqldump -u root -p ecoride > backup.sql

# Restaurer la base
docker-compose exec -T db mysql -u root -p ecoride < backup.sql
```

### Shell

```bash
# Accéder au conteneur web
docker-compose exec app bash

# Accéder au conteneur MySQL
docker-compose exec db bash
```

## Configuration

### Variables d'environnement

Les paramètres de connexion à la base de données sont configurés dans `docker-compose.yml` :

- Base : `ecoride`
- Utilisateur : `ecoride_user`
- Mot de passe : `ecoride_pass`
- Root : `root`

### Fichiers de configuration

- **Apache** : `docker/apache/000-default.conf`
- **PHP** : `docker/php/php.ini`
- **Dockerfile** : `docker/php/Dockerfile`

## Dépannage

### Ports déjà utilisés

Si les ports 8000, 3307, 27017 ou 8080 sont déjà utilisés, modifiez `docker-compose.yml` ou utilisez les variables d'environnement :

```yaml
ports:
  - "${APP_PORT:-8001}:80" # Pour changer le port de l'application
  - "${MYSQL_PORT:-3308}:3306" # Pour changer le port MySQL
  - "${PHPMYADMIN_PORT:-8081}:80" # Pour changer le port phpMyAdmin
```

### Permissions

Si vous avez des problèmes de permissions :

```bash
docker-compose exec app chown -R www-data:www-data /var/www/html
```

### Réinitialiser complètement

```bash
# Arrêter et supprimer tout
docker-compose down -v

# Reconstruire les images
docker-compose build --no-cache

# Redémarrer
docker-compose up -d
```

## Développement

### Modifier la configuration

1. Modifiez les fichiers dans `docker/`
2. Reconstruisez l'image : `docker-compose build app`
3. Redémarrez : `docker-compose restart app`

### Ajouter des extensions PHP

Modifiez `docker/php/Dockerfile` et ajoutez :

```dockerfile
RUN docker-php-ext-install votre_extension
```

## Production

⚠️ **Attention** : Cette configuration est destinée au développement uniquement.
Pour la production, considérez :

- Désactiver l'affichage des erreurs PHP
- Configurer HTTPS
- Restreindre l'accès à phpMyAdmin
- Utiliser des mots de passe forts
- Configurer des sauvegardes automatiques
