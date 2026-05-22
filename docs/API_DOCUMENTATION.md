# 📖 Documentation API REST - EcoRide

## 🌐 Base URL

```
http://localhost:8000/api/v1
```

## 🔐 Authentification

L'API utilise les sessions PHP. L'utilisateur doit être connecté pour la plupart des endpoints.

**Headers requis** :

```
Content-Type: application/json
```

## 📋 Endpoints disponibles

### 1. Avis

#### GET /api/v1/avis

Récupère la liste des avis.

**Paramètres de requête (query parameters)** :

- `statut` (optionnel) : Filtrer par statut (`en attente`, `valide`, `refuse`)
- `limit` (optionnel) : Nombre de résultats (défaut: 50)
- `offset` (optionnel) : Décalage pour la pagination (défaut: 0)

**Exemple de requête** :

```bash
GET /api/v1/avis?statut=valide&limit=10
```

**Réponse succès (200)** :

```json
{
  "success": true,
  "data": {
    "avis": [
      {
        "_id": "507f1f77bcf86cd799439011",
        "user_id": 1,
        "note": 5,
        "commentaire": "Excellent service !",
        "statut": "valide",
        "created_at": "2025-01-15 10:30:00"
      }
    ],
    "total": 1,
    "limit": 10,
    "offset": 0
  },
  "timestamp": "2025-01-15 10:35:00"
}
```

---

#### GET /api/v1/avis/{id}

Récupère un avis spécifique par son ID.

**Paramètres** :

- `id` : ID MongoDB de l'avis

**Exemple de requête** :

```bash
GET /api/v1/avis/507f1f77bcf86cd799439011
```

**Réponse succès (200)** :

```json
{
  "success": true,
  "data": {
    "_id": "507f1f77bcf86cd799439011",
    "user_id": 1,
    "note": 5,
    "commentaire": "Excellent service !",
    "statut": "valide",
    "created_at": "2025-01-15 10:30:00"
  },
  "timestamp": "2025-01-15 10:35:00"
}
```

**Réponse erreur (404)** :

```json
{
  "success": false,
  "error": {
    "code": 404,
    "message": "Avis non trouvé"
  },
  "timestamp": "2025-01-15 10:35:00"
}
```

---

#### POST /api/v1/avis

Crée un nouvel avis.

**Body (JSON)** :

```json
{
  "note": 5,
  "commentaire": "Excellent service de covoiturage !"
}
```

**Validation** :

- `note` : Entre 1 et 5 (obligatoire)
- `commentaire` : Entre 10 et 250 caractères (obligatoire)

**Exemple de requête** :

```bash
POST /api/v1/avis
Content-Type: application/json

{
  "note": 5,
  "commentaire": "Excellent service de covoiturage !"
}
```

**Réponse succès (201)** :

```json
{
  "success": true,
  "data": {
    "_id": "507f1f77bcf86cd799439011",
    "message": "Avis créé avec succès"
  },
  "timestamp": "2025-01-15 10:35:00"
}
```

**Réponse erreur (400)** :

```json
{
  "success": false,
  "error": {
    "code": 400,
    "message": "Le commentaire doit contenir au moins 10 caractères"
  },
  "timestamp": "2025-01-15 10:35:00"
}
```

---

#### PUT /api/v1/avis/{id}

Met à jour un avis (réservé aux employés).

**Paramètres** :

- `id` : ID MongoDB de l'avis

**Body (JSON)** :

```json
{
  "action": "valider"
}
```

**Actions possibles** :

- `"valider"` : Valide l'avis
- `"refuser"` : Refuse l'avis

**Exemple de requête** :

```bash
PUT /api/v1/avis/507f1f77bcf86cd799439011
Content-Type: application/json

{
  "action": "valider"
}
```

**Réponse succès (200)** :

```json
{
  "success": true,
  "data": {
    "message": "Avis validé avec succès"
  },
  "timestamp": "2025-01-15 10:35:00"
}
```

**Réponse erreur (403)** :

```json
{
  "success": false,
  "error": {
    "code": 403,
    "message": "Accès refusé. Réservé aux employés."
  },
  "timestamp": "2025-01-15 10:35:00"
}
```

---

#### DELETE /api/v1/avis/{id}

Supprime un avis (réservé aux employés).

**Paramètres** :

- `id` : ID MongoDB de l'avis

**Exemple de requête** :

```bash
DELETE /api/v1/avis/507f1f77bcf86cd799439011
```

**Réponse succès (200)** :

```json
{
  "success": true,
  "data": {
    "message": "Avis supprimé avec succès"
  },
  "timestamp": "2025-01-15 10:35:00"
}
```

---

## 📊 Codes de statut HTTP

| Code | Signification                              |
| ---- | ------------------------------------------ |
| 200  | OK - Requête réussie                       |
| 201  | Created - Ressource créée                  |
| 400  | Bad Request - Données invalides            |
| 401  | Unauthorized - Non authentifié             |
| 403  | Forbidden - Accès refusé                   |
| 404  | Not Found - Ressource non trouvée          |
| 405  | Method Not Allowed - Méthode non supportée |
| 500  | Internal Server Error - Erreur serveur     |
| 503  | Service Unavailable - Service indisponible |

## 🔧 Exemples d'utilisation avec JavaScript

### Récupérer tous les avis

```javascript
fetch("/api/v1/avis?statut=valide")
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      console.log("Avis:", data.data.avis);
    }
  });
```

### Créer un avis

```javascript
fetch("/api/v1/avis", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    note: 5,
    commentaire: "Excellent service !",
  }),
})
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      console.log("Avis créé:", data.data._id);
    } else {
      console.error("Erreur:", data.error.message);
    }
  });
```

### Valider un avis (employé)

```javascript
fetch("/api/v1/avis/507f1f77bcf86cd799439011", {
  method: "PUT",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({
    action: "valider",
  }),
})
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      console.log("Avis validé !");
    }
  });
```

## 🧪 Tester l'API

### Avec cURL (ligne de commande)

```bash
# Récupérer tous les avis
curl http://localhost:8000/api/v1/avis

# Créer un avis
curl -X POST http://localhost:8000/api/v1/avis \
  -H "Content-Type: application/json" \
  -d '{"note":5,"commentaire":"Excellent service !"}'
```

### Avec Postman

1. Créer une nouvelle requête
2. Choisir la méthode (GET, POST, PUT, DELETE)
3. Entrer l'URL : `http://localhost:8000/api/v1/avis`
4. Ajouter les headers : `Content-Type: application/json`
5. Pour POST/PUT, ajouter le body en JSON
6. Envoyer la requête

## 📝 Notes importantes

- Tous les endpoints nécessitent une authentification (session PHP)
- Les endpoints PUT et DELETE sont réservés aux employés (role_id = 2)
- Les réponses sont toujours au format JSON
- Les timestamps sont au format `Y-m-d H:i:s`

