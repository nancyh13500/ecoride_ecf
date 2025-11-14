# Guide des Commandes Docker pour EcoRide

## ⚠️ Comprendre `docker-compose down`

### Ce que `docker-compose down` fait :

✅ **SUPPRIME** :
- Les conteneurs Docker (app, db, mongodb, phpmyadmin)
- Les réseaux créés par docker-compose

❌ **NE SUPPRIME PAS** (par défaut) :
- Les volumes (vos données sont préservées !)
  - `mysql_data` : Toutes vos données MySQL (utilisateurs, covoiturages, etc.)
  - `mongodb_data` : Tous vos avis MongoDB
- Les images Docker
- Vos fichiers de code source

### Pourquoi utiliser `docker-compose down` ?

Quand vous modifiez :
- Le `docker-compose.yml` (ajout de services, changement de ports, etc.)
- Le `Dockerfile` (ajout d'extensions PHP, changement de configuration)
- Les dépendances dans `composer.json`

Vous devez **reconstruire** les conteneurs pour que les changements soient pris en compte.

---

## 📋 Commandes selon votre situation

### 1️⃣ **Première installation ou ajout de MongoDB** (votre cas actuel)

```bash
# Arrêter et supprimer les conteneurs (mais garder les données)
docker-compose down

# Reconstruire les images avec les nouvelles modifications
docker-compose build --no-cache

# Démarrer tous les services
docker-compose up -d
```

**Résultat** :
- ✅ Vos données MySQL sont préservées
- ✅ Vos données MongoDB sont préservées (si elles existent déjà)
- ✅ Les nouvelles modifications (MongoDB, extension PHP) sont appliquées

---

### 2️⃣ **Redémarrer simplement les conteneurs** (sans modifications)

```bash
# Arrêter les conteneurs
docker-compose stop

# Redémarrer les conteneurs
docker-compose start

# OU en une seule commande
docker-compose restart
```

**Résultat** :
- ✅ Aucune donnée n'est supprimée
- ✅ Les conteneurs redémarrent avec la même configuration

---

### 3️⃣ **Tout supprimer ET reconstruire** (⚠️ ATTENTION : supprime les données !)

```bash
# Arrêter, supprimer les conteneurs ET les volumes (données supprimées !)
docker-compose down -v

# Reconstruire
docker-compose build --no-cache

# Redémarrer
docker-compose up -d
```

**Résultat** :
- ❌ **TOUTES vos données sont supprimées** (MySQL et MongoDB)
- ⚠️ Utilisez uniquement si vous voulez repartir de zéro

---

### 4️⃣ **Mettre à jour le code PHP uniquement** (sans reconstruire)

```bash
# Redémarrer juste le conteneur app
docker-compose restart app
```

**Résultat** :
- ✅ Les modifications de code PHP sont prises en compte
- ✅ Pas besoin de reconstruire l'image

---

## 🔍 Vérifier l'état de vos conteneurs

```bash
# Voir les conteneurs en cours d'exécution
docker-compose ps

# Voir les logs
docker-compose logs

# Voir les logs d'un service spécifique
docker-compose logs mongodb
docker-compose logs app
```

---

## 🗑️ Nettoyage complet (si nécessaire)

Si vous voulez vraiment tout supprimer et repartir de zéro :

```bash
# Arrêter et supprimer conteneurs + volumes
docker-compose down -v

# Supprimer les images (optionnel)
docker-compose rm -f

# Supprimer les images non utilisées
docker system prune -a
```

⚠️ **ATTENTION** : Cette dernière commande supprime TOUT, y compris vos données !

---

## 📝 Pour votre cas actuel (ajout de MongoDB)

Voici la séquence recommandée :

```bash
# 1. Arrêter les conteneurs (données préservées)
docker-compose down

# 2. Reconstruire avec les nouvelles modifications
docker-compose build --no-cache

# 3. Démarrer tous les services
docker-compose up -d

# 4. Vérifier que tout fonctionne
docker-compose ps

# 5. Voir les logs MongoDB pour vérifier qu'il démarre bien
docker-compose logs mongodb
```

---

## ✅ Vérification après démarrage

1. **Vérifier que MongoDB est démarré** :
   ```bash
   docker-compose exec mongodb mongosh --eval "db.adminCommand('ping')"
   ```

2. **Vérifier que l'extension MongoDB PHP est installée** :
   ```bash
   docker-compose exec app php -m | grep mongodb
   ```
   Devrait afficher : `mongodb`

3. **Vérifier que Composer a installé les dépendances** :
   ```bash
   docker-compose exec app composer show | grep mongodb
   ```
   Devrait afficher : `mongodb/mongodb`

---

## 🆘 En cas de problème

### Les conteneurs ne démarrent pas ?
```bash
# Voir les erreurs
docker-compose logs

# Redémarrer un service spécifique
docker-compose restart mongodb
```

### MongoDB ne se connecte pas ?
```bash
# Vérifier que le conteneur tourne
docker-compose ps mongodb

# Voir les logs MongoDB
docker-compose logs mongodb

# Tester la connexion depuis le conteneur app
docker-compose exec app php -r "require 'vendor/autoload.php'; require 'lib/mongodb.php'; var_dump(getAvisCollection());"
```

---

## 📌 Résumé rapide

| Commande | Supprime conteneurs | Supprime données | Quand l'utiliser |
|----------|---------------------|------------------|------------------|
| `docker-compose stop` | ❌ | ❌ | Pause temporaire |
| `docker-compose down` | ✅ | ❌ | Avant reconstruction |
| `docker-compose down -v` | ✅ | ✅ | Reset complet |
| `docker-compose restart` | ❌ | ❌ | Redémarrer simplement |
| `docker-compose build` | ❌ | ❌ | Reconstruire l'image |

**Pour votre cas (ajout MongoDB)** : Utilisez `docker-compose down` puis `build` puis `up -d` ✅

