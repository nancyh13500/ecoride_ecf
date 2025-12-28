# 🌐 API REST - Guide Complet

## 📚 Qu'est-ce qu'une API REST ?

Une **API REST** (Representational State Transfer) est une façon standardisée de permettre à différentes applications de communiquer entre elles via HTTP.

### 🔍 Exemple concret

**Sans API REST** (méthode actuelle) :

- Tu cliques sur un bouton → Le navigateur charge une page HTML complète
- Toute la page se recharge

**Avec API REST** :

- Tu cliques sur un bouton → Le navigateur envoie une requête HTTP
- Le serveur répond avec du JSON (données uniquement)
- Pas de rechargement de page, juste les données nécessaires

### 🎯 À quoi ça sert ?

1. **Séparation frontend/backend** : Le frontend (JavaScript) et le backend (PHP) communiquent via l'API
2. **Réutilisabilité** : L'API peut être utilisée par :
   - Une application web (ton site actuel)
   - Une application mobile (Android/iOS)
   - Une autre application web
3. **Standardisation** : Tous les développeurs comprennent comment utiliser ton API
4. **Documentation** : Les autres développeurs savent exactement comment utiliser ton API

## 🏗️ Structure d'une API REST

### Méthodes HTTP

- **GET** : Récupérer des données (lecture)
- **POST** : Créer une nouvelle ressource
- **PUT** : Mettre à jour une ressource complète
- **PATCH** : Mettre à jour partiellement une ressource
- **DELETE** : Supprimer une ressource

### Exemples d'endpoints

```
GET    /api/v1/avis          → Liste tous les avis
GET    /api/v1/avis/123      → Récupère l'avis n°123
POST   /api/v1/avis          → Crée un nouvel avis
PUT    /api/v1/avis/123      → Met à jour l'avis n°123
DELETE /api/v1/avis/123      → Supprime l'avis n°123
```

### Format de réponse JSON

**Succès** :

```json
{
  "success": true,
  "data": {
    "avis_id": 123,
    "note": 5,
    "commentaire": "Excellent service !"
  }
}
```

**Erreur** :

```json
{
  "success": false,
  "error": {
    "code": 404,
    "message": "Avis non trouvé"
  }
}
```

## 📋 Codes HTTP standards

- **200 OK** : Requête réussie
- **201 Created** : Ressource créée avec succès
- **400 Bad Request** : Données invalides
- **401 Unauthorized** : Non authentifié
- **403 Forbidden** : Accès refusé
- **404 Not Found** : Ressource non trouvée
- **500 Internal Server Error** : Erreur serveur

## 🎓 Pourquoi c'est important pour le titre RNCP ?

1. **Compétence technique** : Maîtrise des API modernes
2. **Architecture** : Compréhension de l'architecture client-serveur
3. **Standardisation** : Utilisation des standards de l'industrie
4. **Documentation** : Capacité à documenter son travail

## 🚀 Utilisation dans ton projet

### Avant (sans API REST)

```javascript
// Formulaire HTML qui recharge la page
<form method="POST" action="employe.php">
  <input name="action" value="valider">
  <button>Valider</button>
</form>
```

### Après (avec API REST)

```javascript
// Requête AJAX vers l'API
fetch("/api/v1/avis/123", {
  method: "PUT",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ action: "valider" }),
})
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      // Mettre à jour l'interface
    }
  });
```

## 📖 Documentation de l'API

La documentation explique :

- Quels endpoints existent
- Comment les utiliser
- Quels paramètres envoyer
- Quelles réponses recevoir
- Exemples concrets
