<?php
/**
 * Page d'accueil de l'API REST
 * Affiche la documentation de l'API
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API REST - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .endpoint {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .method {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-weight: bold;
            font-size: 0.875rem;
        }
        .method.get { background: #28a745; color: white; }
        .method.post { background: #007bff; color: white; }
        .method.put { background: #ffc107; color: black; }
        .method.delete { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">🌐 API REST - EcoRide</h1>
        
        <div class="alert alert-info">
            <strong>Base URL :</strong> <code>http://localhost:8000/api/v1</code>
        </div>

        <h2 class="mt-5">📋 Endpoints disponibles</h2>

        <div class="endpoint">
            <div class="d-flex align-items-center mb-2">
                <span class="method get">GET</span>
                <code class="ms-3">/api/v1/avis</code>
            </div>
            <p class="mb-1"><strong>Description :</strong> Récupère la liste des avis</p>
            <p class="mb-1"><strong>Paramètres :</strong> <code>?statut=valide&limit=10</code></p>
            <p class="mb-0"><strong>Authentification :</strong> Requise</p>
        </div>

        <div class="endpoint">
            <div class="d-flex align-items-center mb-2">
                <span class="method get">GET</span>
                <code class="ms-3">/api/v1/avis/{id}</code>
            </div>
            <p class="mb-1"><strong>Description :</strong> Récupère un avis spécifique</p>
            <p class="mb-0"><strong>Authentification :</strong> Requise</p>
        </div>

        <div class="endpoint">
            <div class="d-flex align-items-center mb-2">
                <span class="method post">POST</span>
                <code class="ms-3">/api/v1/avis</code>
            </div>
            <p class="mb-1"><strong>Description :</strong> Crée un nouvel avis</p>
            <p class="mb-1"><strong>Body :</strong> <code>{"note": 5, "commentaire": "..."}</code></p>
            <p class="mb-0"><strong>Authentification :</strong> Requise</p>
        </div>

        <div class="endpoint">
            <div class="d-flex align-items-center mb-2">
                <span class="method put">PUT</span>
                <code class="ms-3">/api/v1/avis/{id}</code>
            </div>
            <p class="mb-1"><strong>Description :</strong> Met à jour un avis (valider/refuser)</p>
            <p class="mb-1"><strong>Body :</strong> <code>{"action": "valider"}</code></p>
            <p class="mb-0"><strong>Authentification :</strong> Requise (Employé uniquement)</p>
        </div>

        <div class="endpoint">
            <div class="d-flex align-items-center mb-2">
                <span class="method delete">DELETE</span>
                <code class="ms-3">/api/v1/avis/{id}</code>
            </div>
            <p class="mb-1"><strong>Description :</strong> Supprime un avis</p>
            <p class="mb-0"><strong>Authentification :</strong> Requise (Employé uniquement)</p>
        </div>

        <h2 class="mt-5">📖 Documentation complète</h2>
        <p>Consulte le fichier <code>docs/API_DOCUMENTATION.md</code> pour la documentation complète avec exemples.</p>

        <h2 class="mt-5">🧪 Tester l'API</h2>
        <div class="card">
            <div class="card-body">
                <h5>Exemple avec JavaScript</h5>
                <pre><code>fetch('/api/v1/avis')
  .then(response => response.json())
  .then(data => console.log(data));</code></pre>
            </div>
        </div>
    </div>
</body>
</html>


