# 🧪 Exemples d'utilisation de l'API REST

## 📋 Comment utiliser l'API dans ton projet

### Exemple 1 : Remplacer les formulaires par des appels API

**Avant** (avec formulaire HTML) :

```html
<form method="POST" action="employe.php">
  <input type="hidden" name="avis_id" value="123" />
  <input type="hidden" name="action" value="valider" />
  <button>Valider</button>
</form>
```

**Après** (avec API REST) :

```javascript
// Dans ajax-avis.js
async function validerAvis(avisId) {
  const response = await fetch(`/api/v1/avis/${avisId}`, {
    method: "PUT",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "valider",
    }),
  });

  const data = await response.json();

  if (data.success) {
    console.log("Avis validé !");
  } else {
    console.error("Erreur:", data.error.message);
  }
}
```

### Exemple 2 : Récupérer la liste des avis

```javascript
async function chargerAvis() {
  const response = await fetch("/api/v1/avis?statut=valide&limit=10");
  const data = await response.json();

  if (data.success) {
    data.data.avis.forEach((avis) => {
      console.log(`Avis ${avis._id}: ${avis.commentaire}`);
    });
  }
}
```

### Exemple 3 : Créer un avis depuis le frontend

```javascript
async function creerAvis(note, commentaire) {
  const response = await fetch("/api/v1/avis", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      note: note,
      commentaire: commentaire,
    }),
  });

  const data = await response.json();

  if (data.success) {
    alert("Avis créé avec succès !");
  } else {
    alert("Erreur: " + data.error.message);
  }
}
```

## 🔄 Migration progressive

Tu peux migrer progressivement :

1. **Phase 1** : Garder les formulaires HTML pour le moment
2. **Phase 2** : Créer l'API REST (fait ✅)
3. **Phase 3** : Modifier les fichiers JavaScript pour utiliser l'API
4. **Phase 4** : Supprimer les anciens fichiers PHP qui géraient les formulaires

## 🎯 Avantages de l'API REST

1. **Réutilisable** : L'API peut être utilisée par :

   - Ton site web actuel
   - Une future application mobile
   - Une autre application web

2. **Standardisée** : Tous les développeurs comprennent comment l'utiliser

3. **Documentée** : La documentation explique tout

4. **Testable** : Tu peux tester l'API indépendamment du frontend
