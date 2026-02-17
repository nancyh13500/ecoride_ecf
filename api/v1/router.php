<?php
/**
 * Routeur API REST
 * Gère le routage des requêtes vers les bons endpoints
 */

// Récupérer l'URI de la requête
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Nettoyer l'URI
$uri = parse_url($requestUri, PHP_URL_PATH);
$uri = str_replace('/api/v1', '', $uri);
$uriParts = array_filter(explode('/', trim($uri, '/')));

// Déterminer la ressource (premier segment de l'URI)
$resource = !empty($uriParts) ? array_shift($uriParts) : null;
$resourceId = !empty($uriParts) ? array_shift($uriParts) : null;

// Router vers le bon fichier
switch ($resource) {
    case 'avis':
        require_once __DIR__ . '/avis.php';
        break;
    
    case 'covoiturages':
        // À implémenter plus tard
        http_response_code(501);
        echo json_encode(['error' => 'Endpoint non implémenté']);
        break;
    
    case 'reservations':
        // À implémenter plus tard
        http_response_code(501);
        echo json_encode(['error' => 'Endpoint non implémenté']);
        break;
    
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 404,
                'message' => 'Endpoint non trouvé'
            ]
        ]);
        break;
}


