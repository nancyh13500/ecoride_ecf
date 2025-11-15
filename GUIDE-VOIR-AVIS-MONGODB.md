# Guide : Voir les avis stockés dans MongoDB

## Méthode 1 : Via l'interface web (Recommandé) 🌐

### Pour les employés

1. Connectez-vous avec un compte employé (`role_id = 2`)
2. Allez sur `/pages/employe.php`
3. Onglet "Avis à valider" : voir tous les avis en attente
4. Les avis validés apparaissent sur `/pages/avis.php`

### Pour tous les utilisateurs

- Page publique : `/pages/avis.php` (affiche uniquement les avis validés)

---

## Méthode 2 : Via MongoDB Shell (mongosh) dans Docker 🖥️

### Accéder à MongoDB depuis Docker

```bash
# Se connecter au conteneur MongoDB
docker-compose exec mongodb mongosh -u mongodb_user -p mongodb_pass --authenticationDatabase admin
```

### Commandes MongoDB utiles

```javascript
// Voir toutes les bases de données
show dbs

// Utiliser la base ecoride
use ecoride

// Voir toutes les collections
show collections

// Voir tous les avis
db.avis.find().pretty()

// Voir uniquement les avis en attente
db.avis.find({statut: "en attente"}).pretty()

// Voir uniquement les avis validés
db.avis.find({statut: "valide"}).pretty()

// Compter le nombre d'avis
db.avis.countDocuments()

// Voir un avis spécifique par ID
db.avis.findOne({_id: ObjectId("VOTRE_ID_ICI")})

// Voir les avis triés par date de création (plus récents en premier)
db.avis.find().sort({created_at: -1}).pretty()

// Voir les 5 derniers avis
db.avis.find().sort({created_at: -1}).limit(5).pretty()
```

### Quitter MongoDB Shell

```javascript
exit;
```

---

## Méthode 3 : Via MongoDB Compass (Interface graphique) 🎨

### Installation

1. Téléchargez MongoDB Compass : https://www.mongodb.com/try/download/compass
2. Installez l'application

### Connexion

1. Ouvrez MongoDB Compass
2. Utilisez cette chaîne de connexion :
   ```
   mongodb://mongodb_user:mongodb_pass@localhost:27017/ecoride?authSource=admin
   ```
3. Cliquez sur "Connect"

### Navigation

- Base de données : `ecoride`
- Collection : `avis`
- Vous verrez tous les documents avec une interface graphique

---

## Méthode 4 : Via un script PHP simple 📝

Je vais créer un script PHP pour afficher tous les avis directement.

---

## Méthode 5 : Via la ligne de commande (sans entrer dans mongosh)

```bash
# Voir tous les avis
docker-compose exec mongodb mongosh -u mongodb_user -p mongodb_pass --authenticationDatabase admin ecoride --eval "db.avis.find().pretty()"

# Compter les avis
docker-compose exec mongodb mongosh -u mongodb_user -p mongodb_pass --authenticationDatabase admin ecoride --eval "db.avis.countDocuments()"

# Voir uniquement les avis en attente
docker-compose exec mongodb mongosh -u mongodb_user -p mongodb_pass --authenticationDatabase admin ecoride --eval "db.avis.find({statut: 'en attente'}).pretty()"
```

---

## Structure d'un document avis dans MongoDB

```json
{
  "_id": ObjectId("..."),
  "user_id": 123,
  "note": 5,
  "commentaire": "Excellent service !",
  "statut": "en attente" | "valide" | "refuse",
  "created_at": ISODate("2025-11-14T12:00:00.000Z"),
  "updated_at": ISODate("2025-11-14T12:00:00.000Z"),
  "validated_by": 456,        // Si validé
  "validated_at": ISODate("..."), // Si validé
  "rejected_by": 456,          // Si refusé
  "rejected_at": ISODate("...")  // Si refusé
}
```

---

## Commandes utiles supplémentaires

### Filtrer par note

```javascript
// Avis avec 5 étoiles
db.avis.find({ note: 5 }).pretty();

// Avis avec 4 étoiles ou plus
db.avis.find({ note: { $gte: 4 } }).pretty();
```

### Filtrer par utilisateur

```javascript
// Avis d'un utilisateur spécifique
db.avis.find({ user_id: 123 }).pretty();
```

### Supprimer un avis (⚠️ Attention)

```javascript
// Supprimer un avis par ID
db.avis.deleteOne({ _id: ObjectId("VOTRE_ID_ICI") });

// Supprimer tous les avis refusés
db.avis.deleteMany({ statut: "refuse" });
```

### Statistiques

```javascript
// Nombre d'avis par statut
db.avis.aggregate([{ $group: { _id: "$statut", count: { $sum: 1 } } }]);

// Note moyenne
db.avis.aggregate([{ $group: { _id: null, moyenne: { $avg: "$note" } } }]);

// Note moyenne des avis validés
db.avis.aggregate([
  { $match: { statut: "valide" } },
  { $group: { _id: null, moyenne: { $avg: "$note" } } },
]);
```

---

## 🔍 Vérification rapide

Pour vérifier rapidement si des avis existent :

```bash
docker-compose exec mongodb mongosh -u mongodb_user -p mongodb_pass --authenticationDatabase admin ecoride --eval "print('Nombre d\'avis: ' + db.avis.countDocuments())"
```
