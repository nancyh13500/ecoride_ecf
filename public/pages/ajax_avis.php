<?php
// Activer la gestion des erreurs pour capturer toutes les exceptions
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher les erreurs directement

// Définir le header JSON dès le début
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . "/../../vendor/autoload.php";
    require_once __DIR__ . "/../../lib/session.php";
    require_once __DIR__ . "/../../lib/pdo.php";
    require_once __DIR__ . "/../../lib/mongodb.php";

    // Vérifier si l'utilisateur est connecté et a le rôle admin (role_id = 1) ou employé (role_id = 2)
    requireLogin();

    if (!isset($_SESSION['user']) || ($_SESSION['user']['role_id'] != 1 && $_SESSION['user']['role_id'] != 2)) {
        echo json_encode([
            'success' => false,
            'message' => "Accès refusé. Cette page est réservée aux administrateurs et aux employés."
        ]);
        exit();
    }

    $currentUser = $_SESSION['user'];

    // Traitement des actions sur les avis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && isset($_POST['avis_id'])) {
            $avis_id = trim($_POST['avis_id']);
            $action = trim($_POST['action']);

            // Validation des paramètres
            if (empty($avis_id) || empty($action)) {
                echo json_encode([
                    'success' => false,
                    'message' => "Paramètres invalides. Action et avis_id ne peuvent pas être vides."
                ]);
                exit();
            }

            try {
                $avisCollection = getAvisCollection();

                if ($avisCollection === null) {
                    throw new Exception("MongoDB n'est pas disponible.");
                }

                // Convertir l'ID string en ObjectId MongoDB
                try {
                    $objectId = new MongoDB\BSON\ObjectId($avis_id);
                } catch (Exception $e) {
                    throw new Exception("ID d'avis invalide : " . $e->getMessage());
                }

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
                        echo json_encode([
                            'success' => true,
                            'message' => "Avis validé avec succès ! L'avis est maintenant visible sur la page avis."
                        ]);
                    } else {
                        throw new Exception("Aucun avis modifié. L'avis existe-t-il ?");
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
                        echo json_encode([
                            'success' => true,
                            'message' => "Avis refusé avec succès !"
                        ]);
                    } else {
                        throw new Exception("Aucun avis modifié. L'avis existe-t-il ?");
                    }
                } elseif ($action === 'supprimer') {
                    $result = $avisCollection->deleteOne(['_id' => $objectId]);

                    if ($result->getDeletedCount() > 0) {
                        echo json_encode([
                            'success' => true,
                            'message' => "Avis supprimé avec succès !"
                        ]);
                    } else {
                        throw new Exception("Aucun avis supprimé. L'avis existe-t-il ?");
                    }
                } else {
                    throw new Exception("Action invalide. Utilisez 'valider', 'refuser' ou 'supprimer'.");
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => "Erreur lors de l'opération : " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Paramètres manquants. Action et avis_id sont requis."
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Méthode non autorisée. Utilisez POST."
        ]);
    }
} catch (Exception $e) {
    // Gestion globale des erreurs non capturées
    echo json_encode([
        'success' => false,
        'message' => "Erreur inattendue : " . $e->getMessage()
    ]);
} catch (Error $e) {
    // Gestion des erreurs fatales PHP 7+
    echo json_encode([
        'success' => false,
        'message' => "Erreur fatale : " . $e->getMessage()
    ]);
}
