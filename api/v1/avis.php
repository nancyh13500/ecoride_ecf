<?php
/**
 * API REST - Gestion des avis
 * 
 * Endpoints disponibles :
 * GET    /api/v1/avis          → Liste tous les avis
 * GET    /api/v1/avis/{id}     → Récupère un avis spécifique
 * POST   /api/v1/avis          → Crée un nouvel avis
 * PUT    /api/v1/avis/{id}     → Met à jour un avis
 * DELETE /api/v1/avis/{id}     → Supprime un avis
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/session.php';
require_once __DIR__ . '/../../lib/pdo.php';
require_once __DIR__ . '/../../lib/mongodb.php';

// Définir le header JSON
header('Content-Type: application/json; charset=utf-8');

// Autoriser les requêtes CORS (pour les applications externes)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Fonction pour envoyer une réponse JSON
function sendResponse($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Fonction pour envoyer une erreur
function sendError($message, $code = 400) {
    sendResponse(false, null, [
        'code' => $code,
        'message' => $message
    ], $code);
}

// Récupérer la méthode HTTP et l'URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', trim($uri, '/'));

// Vérifier que c'est bien une requête API
if (!isset($uriParts[0]) || $uriParts[0] !== 'api' || 
    !isset($uriParts[1]) || $uriParts[1] !== 'v1' ||
    !isset($uriParts[2]) || $uriParts[2] !== 'avis') {
    sendError('Endpoint non trouvé', 404);
}

// Récupérer l'ID si présent (ex: /api/v1/avis/123)
$avisId = isset($uriParts[3]) ? $uriParts[3] : null;

try {
    $avisCollection = getAvisCollection();
    
    if ($avisCollection === null) {
        sendError("MongoDB n'est pas disponible.", 503);
    }

    // GET /api/v1/avis - Liste tous les avis
    if ($method === 'GET' && $avisId === null) {
        requireLogin();
        
        $statut = $_GET['statut'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $filter = [];
        if ($statut) {
            $filter['statut'] = $statut;
        }
        
        $cursor = $avisCollection->find(
            $filter,
            [
                'sort' => ['created_at' => -1],
                'limit' => $limit,
                'skip' => $offset
            ]
        );
        
        $avis = [];
        foreach ($cursor as $doc) {
            $avisItem = [
                '_id' => (string)$doc['_id'],
                'user_id' => $doc['user_id'],
                'note' => $doc['note'],
                'commentaire' => $doc['commentaire'],
                'statut' => $doc['statut'],
                'created_at' => isset($doc['created_at']) ? $doc['created_at']->toDateTime()->format('Y-m-d H:i:s') : null
            ];
            if (isset($doc['covoiturage_id'])) {
                $avisItem['covoiturage_id'] = $doc['covoiturage_id'];
            }
            $avis[] = $avisItem;
        }
        
        sendResponse(true, [
            'avis' => $avis,
            'total' => count($avis),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    // GET /api/v1/avis/{id} - Récupère un avis spécifique
    if ($method === 'GET' && $avisId !== null) {
        requireLogin();
        
        try {
            $objectId = new MongoDB\BSON\ObjectId($avisId);
        } catch (Exception $e) {
            sendError('ID invalide', 400);
        }
        
        $avis = $avisCollection->findOne(['_id' => $objectId]);
        
        if (!$avis) {
            sendError('Avis non trouvé', 404);
        }
        
        $avisData = [
            '_id' => (string)$avis['_id'],
            'user_id' => $avis['user_id'],
            'note' => $avis['note'],
            'commentaire' => $avis['commentaire'],
            'statut' => $avis['statut'],
            'created_at' => isset($avis['created_at']) ? $avis['created_at']->toDateTime()->format('Y-m-d H:i:s') : null
        ];
        if (isset($avis['covoiturage_id'])) {
            $avisData['covoiturage_id'] = $avis['covoiturage_id'];
        }
        sendResponse(true, $avisData);
    }
    
    // POST /api/v1/avis - Crée un nouvel avis
    if ($method === 'POST' && $avisId === null) {
        requireLogin();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $note = isset($input['note']) ? (int)$input['note'] : 0;
        $commentaire = trim($input['commentaire'] ?? '');
        $covoiturage_id = isset($input['covoiturage_id']) && $input['covoiturage_id'] !== '' 
            ? (int)$input['covoiturage_id'] 
            : null;
        
        // Validation
        if ($note < 1 || $note > 5) {
            sendError('La note doit être comprise entre 1 et 5', 400);
        }
        
        if (empty($commentaire) || strlen($commentaire) < 10) {
            sendError('Le commentaire doit contenir au moins 10 caractères', 400);
        }
        
        if (strlen($commentaire) > 250) {
            sendError('Le commentaire ne doit pas dépasser 250 caractères', 400);
        }
        
        $user = $_SESSION['user'];
        
        $avisDocument = [
            'user_id' => (int)$user['user_id'],
            'note' => $note,
            'commentaire' => $commentaire,
            'statut' => 'en attente',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        // Ajouter le covoiturage_id si fourni
        if ($covoiturage_id !== null) {
            $avisDocument['covoiturage_id'] = $covoiturage_id;
        }
        
        $result = $avisCollection->insertOne($avisDocument);
        
        if ($result->getInsertedCount() > 0) {
            sendResponse(true, [
                '_id' => (string)$result->getInsertedId(),
                'message' => 'Avis créé avec succès'
            ], null, 201);
        } else {
            sendError('Erreur lors de la création de l\'avis', 500);
        }
    }
    
    // PUT /api/v1/avis/{id} - Met à jour un avis (pour les employés)
    if ($method === 'PUT' && $avisId !== null) {
        requireLogin();
        
        // Vérifier que l'utilisateur est employé (role_id = 2)
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
            sendError('Accès refusé. Réservé aux employés.', 403);
        }
        
        try {
            $objectId = new MongoDB\BSON\ObjectId($avisId);
        } catch (Exception $e) {
            sendError('ID invalide', 400);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Données JSON invalides', 400);
        }
        
        $action = $input['action'] ?? null;
        $currentUser = $_SESSION['user'];
        
        if ($action === 'valider') {
            $result = $avisCollection->updateOne(
                ['_id' => $objectId],
                [
                    '$set' => [
                        'statut' => 'valide',
                        'updated_at' => new MongoDB\BSON\UTCDateTime(),
                        'validated_by' => (int)$currentUser['user_id'],
                        'validated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() > 0) {
                sendResponse(true, ['message' => 'Avis validé avec succès']);
            } else {
                sendError('Aucun avis modifié', 404);
            }
        } elseif ($action === 'refuser') {
            $result = $avisCollection->updateOne(
                ['_id' => $objectId],
                [
                    '$set' => [
                        'statut' => 'refuse',
                        'updated_at' => new MongoDB\BSON\UTCDateTime(),
                        'rejected_by' => (int)$currentUser['user_id'],
                        'rejected_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() > 0) {
                sendResponse(true, ['message' => 'Avis refusé avec succès']);
            } else {
                sendError('Aucun avis modifié', 404);
            }
        } else {
            sendError('Action invalide. Utilisez "valider" ou "refuser"', 400);
        }
    }
    
    // DELETE /api/v1/avis/{id} - Supprime un avis
    if ($method === 'DELETE' && $avisId !== null) {
        requireLogin();
        
        // Vérifier que l'utilisateur est employé (role_id = 2)
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
            sendError('Accès refusé. Réservé aux employés.', 403);
        }
        
        try {
            $objectId = new MongoDB\BSON\ObjectId($avisId);
        } catch (Exception $e) {
            sendError('ID invalide', 400);
        }
        
        $result = $avisCollection->deleteOne(['_id' => $objectId]);
        
        if ($result->getDeletedCount() > 0) {
            sendResponse(true, ['message' => 'Avis supprimé avec succès']);
        } else {
            sendError('Avis non trouvé', 404);
        }
    }
    
    // Méthode non supportée
    sendError('Méthode non supportée', 405);
    
} catch (Exception $e) {
    sendError('Erreur serveur : ' . $e->getMessage(), 500);
}


