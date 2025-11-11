<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/pdo.php';

// Accès réservé aux administrateurs
if (!isset($_SESSION['user']) || ($_SESSION['user']['role_id'] ?? 3) != 1) {
    $_SESSION['error'] = "Accès refusé. Cette action est réservée aux administrateurs.";
    header('Location: /pages/admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/admin.php');
    exit();
}

$action = $_POST['action'] ?? '';
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($userId <= 0) {
    $_SESSION['error'] = "Utilisateur invalide.";
    header('Location: /pages/admin.php');
    exit();
}

try {
    if ($action === 'suspend' || $action === 'activate') {
        // S'assurer que la colonne suspension existe
        try {
            $pdo->query("SELECT suspended FROM user LIMIT 1");
        } catch (PDOException $e) {

            // Créer la colonne si manquante
            $pdo->exec("ALTER TABLE `user` ADD COLUMN `suspended` TINYINT(1) NOT NULL DEFAULT 0 AFTER `role_covoiturage`");
        }

        $status = ($action === 'suspend') ? 1 : 0;

        // Ne pas suspendre les administrateurs
        $stmt = $pdo->prepare("UPDATE user SET suspended = :status WHERE user_id = :id AND role_id != 1");
        $stmt->execute(['status' => $status, 'id' => $userId]);

        $_SESSION['success'] = $status ? 'Utilisateur suspendu.' : 'Utilisateur réactivé.';
    } elseif ($action === 'delete') {

        // Empêcher la suppression de soi-même et des administrateurs
        if ($userId === (int)$_SESSION['user']['user_id']) {
            $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte.";
            header('Location: /pages/admin.php');
            exit();
        }
        // Vérifier le rôle de l'utilisateur ciblé
        $roleStmt = $pdo->prepare('SELECT role_id FROM user WHERE user_id = :id');
        $roleStmt->execute(['id' => $userId]);
        $roleId = (int)($roleStmt->fetchColumn() ?? 0);
        if ($roleId === 1) {
            $_SESSION['error'] = "Impossible de supprimer un administrateur.";
            header('Location: /pages/admin.php');
            exit();
        }

        // Tentative de suppression (peut échouer si des contraintes FK existent)
        try {
            $del = $pdo->prepare('DELETE FROM user WHERE user_id = :id');
            $del->execute(['id' => $userId]);
            $_SESSION['success'] = 'Utilisateur supprimé.';
        } catch (PDOException $e) {
            $_SESSION['error'] = "Suppression impossible (contraintes liées). Vous pouvez suspendre le compte à la place.";
        }
    } else {
        $_SESSION['error'] = "Action invalide.";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Erreur: ' . $e->getMessage();
}

header('Location: /pages/admin.php');
exit();
