# 🚂 Guide de déploiement EcoRide sur Railway

Guide simple et rapide pour déployer votre projet EcoRide sur Railway.

## 📋 Prérequis

- ✅ Un compte GitHub (pour connecter votre projet)
- ✅ Un compte Railway (gratuit sur [railway.app](https://railway.app))
- ✅ Votre projet pushé sur GitHub

---

## 🚀 Déploiement en 5 étapes

### Étape 1 : Créer un compte et un projet Railway

1. Allez sur [railway.app](https://railway.app)
2. Cliquez sur **"Login"** et connectez-vous avec GitHub
3. Cliquez sur **"New Project"**
4. Sélectionnez **"Deploy from GitHub repo"**
5. Choisissez votre dépôt `ecoride_ecf`

### Étape 2 : Ajouter MySQL

1. Dans votre projet Railway, cliquez sur **"+ New"**
2. Sélectionnez **"Database"** → **"Add MySQL"**
3. Railway créera automatiquement une base MySQL
4. **Important** : Notez les variables d'environnement affichées dans l'onglet **"Variables"** :
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

### Étape 3 : Ajouter MongoDB

1. Toujours dans votre projet, cliquez sur **"+ New"**
2. Sélectionnez **"Database"** → **"Add MongoDB"**
3. Railway créera automatiquement une base MongoDB
4. **Important** : Notez les variables d'environnement affichées :
   - `MONGO_URL` (chaîne de connexion complète)

### Étape 4 : Déployer l'application PHP

1. Railway devrait avoir détecté automatiquement votre Dockerfile
2. Si ce n'est pas le cas, cliquez sur **"+ New"** → **"GitHub Repo"** → sélectionnez votre repo
3. Railway va construire et déployer votre application automatiquement

### Étape 5 : Configurer les variables d'environnement

1. Cliquez sur votre service **"ecoride_ecf"** (l'application PHP)
2. Allez dans l'onglet **"Variables"**
3. Ajoutez les variables suivantes :

#### Variables MySQL (remplacez par les valeurs de votre service MySQL Railway) :

```
DB_HOST=<MYSQLHOST>
DB_NAME=<MYSQLDATABASE>
DB_USER=<MYSQLUSER>
DB_PASS=<MYSQLPASSWORD>
```

**Exemple concret** :

```
DB_HOST=containers-us-west-xxx.railway.app
DB_NAME=railway
DB_USER=root
DB_PASS=votre_mot_de_passe
```

#### Variables MongoDB :

```
MONGO_URL=<MONGO_URL>
```

**Exemple** :

```
MONGO_URL=mongodb://mongo:password@containers-us-west-xxx.railway.app:27017
```

#### Variable d'environnement Docker :

```
DOCKER_ENV=1
```

#### Variable pour Railway (optionnel) :

```
RAILWAY_ENVIRONMENT=production
```

---

## 📊 Importer la base de données MySQL

### Méthode 1 : Via Railway CLI (Recommandé)

1. **Installer Railway CLI** :

   ```bash
   npm i -g @railway/cli
   ```

2. **Se connecter** :

   ```bash
   railway login
   ```

3. **Lier votre projet** :

   ```bash
   railway link
   ```

4. **Importer le fichier SQL** :
   ```bash
   railway connect mysql < ecoride.sql
   ```

### Méthode 2 : Via phpMyAdmin ou un client MySQL

1. Dans Railway, cliquez sur votre service **MySQL**
2. Allez dans l'onglet **"Connect"**
3. Utilisez les informations de connexion fournies
4. Connectez-vous avec un client MySQL (phpMyAdmin, MySQL Workbench, etc.)
5. Importez le fichier `ecoride.sql`

### Méthode 3 : Via le terminal Railway

1. Dans Railway, cliquez sur votre service **MySQL**
2. Allez dans l'onglet **"Data"**
3. Utilisez l'éditeur SQL intégré ou connectez-vous via le terminal

---

## 🔧 Configuration finale

### Vérifier que tout fonctionne

1. Une fois le déploiement terminé, Railway vous donnera une URL (ex: `ecoride-ecf.up.railway.app`)
2. Visitez cette URL dans votre navigateur
3. Vérifiez que l'application se charge correctement

### Configurer un domaine personnalisé (optionnel)

1. Dans votre service PHP, allez dans l'onglet **"Settings"**
2. Cliquez sur **"Generate Domain"** pour obtenir un domaine Railway
3. Ou ajoutez votre propre domaine dans **"Custom Domain"**

---

## 🐛 Dépannage

### L'application ne se connecte pas à MySQL

- ✅ Vérifiez que les variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` sont correctement configurées
- ✅ Vérifiez que le service MySQL est démarré dans Railway
- ✅ Assurez-vous que `DOCKER_ENV=1` est défini

### L'application ne se connecte pas à MongoDB

- ✅ Vérifiez que `MONGO_URL` est correctement configuré
- ✅ Vérifiez que le service MongoDB est démarré
- ✅ Vérifiez les logs de l'application dans Railway

### Erreur 500 ou page blanche

1. Consultez les **logs** de votre service dans Railway (onglet **"Deployments"** → **"View Logs"**)
2. Vérifiez que toutes les variables d'environnement sont définies
3. Vérifiez que la base de données a été importée correctement

### Le build échoue

- ✅ Vérifiez que le Dockerfile est correct
- ✅ Vérifiez que `composer.json` est présent
- ✅ Consultez les logs de build dans Railway

---

## 📝 Notes importantes

### Variables d'environnement Railway

Railway fournit automatiquement des variables d'environnement pour les services liés. Vous pouvez les utiliser directement :

- Pour MySQL : `${{MySQL.MYSQLHOST}}`, `${{MySQL.MYSQLUSER}}`, etc.
- Pour MongoDB : `${{MongoDB.MONGO_URL}}`

### Ports

Railway gère automatiquement les ports. Votre application écoute sur le port 80 en interne, Railway le mappe automatiquement.

### Volumes persistants

Les données MySQL et MongoDB sont automatiquement persistées par Railway. Pas besoin de configurer de volumes manuellement.

### Mises à jour

À chaque push sur votre branche GitHub, Railway redéploiera automatiquement votre application.

---

## ✅ Checklist de déploiement

- [ ] Compte Railway créé
- [ ] Projet Railway créé et connecté à GitHub
- [ ] Service MySQL ajouté et variables notées
- [ ] Service MongoDB ajouté et variables notées
- [ ] Application PHP déployée
- [ ] Variables d'environnement configurées
- [ ] Base de données MySQL importée (`ecoride.sql`)
- [ ] Application accessible via l'URL Railway
- [ ] Test de connexion réussi
- [ ] Test de création de compte réussi

---

## 🎉 C'est terminé !

Votre application EcoRide est maintenant déployée sur Railway et accessible publiquement !
