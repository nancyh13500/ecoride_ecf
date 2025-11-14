<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../lib/session.php";
require_once __DIR__ . "/../lib/pdo.php";
require_once __DIR__ . "/../lib/mongodb.php";

// Vérifier si l'utilisateur est connecté et a le rôle employé (role_id = 2)
requireLogin();

if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    $_SESSION['error'] = "Accès refusé. Cette page est réservée aux employés.";
    header('Location: ../index.php');
    exit();
}

$currentUser = $_SESSION['user'];
$success_message = '';
$error_message = '';

// Traitement des actions sur les avis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['avis_id'])) {
        $avis_id = $_POST['avis_id'];
        $action = $_POST['action'];

        try {
            $avisCollection = getAvisCollection();

            if ($avisCollection === null) {
                throw new Exception("MongoDB n'est pas disponible.");
            }

            // Convertir l'ID string en ObjectId MongoDB
            $objectId = new MongoDB\BSON\ObjectId($avis_id);

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
                    $_SESSION['success_message'] = "Avis validé avec succès ! L'avis est maintenant visible sur la page avis.";
                } else {
                    throw new Exception("Aucun avis modifié. L'avis existe-t-il ?");
                }
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
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
                    $_SESSION['success_message'] = "Avis refusé avec succès !";
                } else {
                    throw new Exception("Aucun avis modifié. L'avis existe-t-il ?");
                }
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } elseif ($action === 'supprimer') {
                $result = $avisCollection->deleteOne(['_id' => $objectId]);

                if ($result->getDeletedCount() > 0) {
                    $_SESSION['success_message'] = "Avis supprimé avec succès !";
                } else {
                    throw new Exception("Aucun avis supprimé. L'avis existe-t-il ?");
                }
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur lors de l'opération : " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Récupérer les messages de session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Récupérer tous les avis en attente de validation depuis MongoDB
$avis_en_attente = [];
try {
    $avisCollection = getAvisCollection();

    if ($avisCollection !== null) {
        // Récupérer les avis en attente depuis MongoDB
        $cursor = $avisCollection->find(
            ['statut' => 'en attente'],
            ['sort' => ['created_at' => -1]]
        );

        // Convertir les résultats en tableau et enrichir avec les données utilisateur depuis MySQL
        foreach ($cursor as $avis) {
            $avisArray = [
                '_id' => (string)$avis['_id'],
                'avis_id' => (string)$avis['_id'], // Pour compatibilité avec le template
                'user_id' => $avis['user_id'],
                'note' => $avis['note'],
                'commentaire' => $avis['commentaire'],
                'statut' => $avis['statut'],
                'created_at' => isset($avis['created_at']) ? $avis['created_at']->toDateTime()->format('Y-m-d H:i:s') : ''
            ];

            // Récupérer les informations utilisateur depuis MySQL
            try {
                $userQuery = $pdo->prepare("SELECT nom, prenom, pseudo, email, telephone, photo FROM user WHERE user_id = :user_id LIMIT 1");
                $userQuery->execute(['user_id' => $avis['user_id']]);
                $userData = $userQuery->fetch(PDO::FETCH_ASSOC);

                if ($userData) {
                    $avisArray['nom'] = $userData['nom'];
                    $avisArray['prenom'] = $userData['prenom'];
                    $avisArray['pseudo'] = $userData['pseudo'];
                    $avisArray['email'] = $userData['email'];
                    $avisArray['telephone'] = $userData['telephone'];
                    $avisArray['photo'] = $userData['photo'];
                } else {
                    $avisArray['nom'] = 'Utilisateur';
                    $avisArray['prenom'] = '';
                    $avisArray['pseudo'] = 'Anonyme';
                    $avisArray['email'] = '';
                    $avisArray['telephone'] = '';
                    $avisArray['photo'] = null;
                }
            } catch (PDOException $e) {
                // Si erreur MySQL, utiliser des valeurs par défaut
                $avisArray['nom'] = 'Utilisateur';
                $avisArray['prenom'] = '';
                $avisArray['pseudo'] = 'Anonyme';
                $avisArray['email'] = '';
                $avisArray['telephone'] = '';
                $avisArray['photo'] = null;
            }

            $avis_en_attente[] = $avisArray;
        }
    }
} catch (Exception $e) {
    $avis_en_attente = [];
    error_log("Erreur lors de la récupération des avis en attente : " . $e->getMessage());
}

// Récupérer tous les avis depuis MongoDB
$admin_avis_mongodb = [];
try {
    $avisCollection = getAvisCollection();

    if ($avisCollection !== null) {
        // Récupérer tous les avis depuis MongoDB
        $cursor = $avisCollection->find(
            [],
            ['sort' => ['created_at' => -1]]
        );

        // Convertir les résultats en tableau et enrichir avec les données utilisateur depuis MySQL
        foreach ($cursor as $avis) {
            $avisArray = [
                '_id' => (string)$avis['_id'],
                'avis_id' => (string)$avis['_id'],
                'user_id' => $avis['user_id'],
                'note' => $avis['note'],
                'commentaire' => $avis['commentaire'],
                'statut' => $avis['statut'],
                'created_at' => isset($avis['created_at']) ? $avis['created_at']->toDateTime()->format('Y-m-d H:i:s') : ''
            ];

            // Récupérer les informations utilisateur depuis MySQL
            try {
                $userQuery = $pdo->prepare("SELECT nom, prenom, pseudo, email, telephone, photo FROM user WHERE user_id = :user_id LIMIT 1");
                $userQuery->execute(['user_id' => $avis['user_id']]);
                $userData = $userQuery->fetch(PDO::FETCH_ASSOC);

                if ($userData) {
                    $avisArray['nom'] = $userData['nom'];
                    $avisArray['prenom'] = $userData['prenom'];
                    $avisArray['pseudo'] = $userData['pseudo'];
                    $avisArray['email'] = $userData['email'];
                    $avisArray['telephone'] = $userData['telephone'];
                    $avisArray['photo'] = $userData['photo'];
                } else {
                    $avisArray['nom'] = 'Utilisateur';
                    $avisArray['prenom'] = '';
                    $avisArray['pseudo'] = 'Anonyme';
                    $avisArray['email'] = '';
                    $avisArray['telephone'] = '';
                    $avisArray['photo'] = null;
                }
            } catch (PDOException $e) {
                // Si erreur MySQL, utiliser des valeurs par défaut
                $avisArray['nom'] = 'Utilisateur';
                $avisArray['prenom'] = '';
                $avisArray['pseudo'] = 'Anonyme';
                $avisArray['email'] = '';
                $avisArray['telephone'] = '';
                $avisArray['photo'] = null;
            }

            $admin_avis_mongodb[] = $avisArray;
        }
    }
} catch (Exception $e) {
    $admin_avis_mongodb = [];
    error_log("Erreur lors de la récupération de tous les avis : " . $e->getMessage());
}

// Récupérer tous les covoiturages avec les informations des utilisateurs
$covoiturages = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u_chauffeur.nom as chauffeur_nom, 
               u_chauffeur.prenom as chauffeur_prenom, 
               u_chauffeur.pseudo as chauffeur_pseudo,
               u_chauffeur.email as chauffeur_email,
               u_chauffeur.telephone as chauffeur_telephone,
               v.modele as voiture_modele,
               m.libelle as marque_libelle,
               e.libelle as energie_libelle
        FROM covoiturage c
        LEFT JOIN user u_chauffeur ON c.user_id = u_chauffeur.user_id
        LEFT JOIN voiture v ON c.voiture_id = v.voiture_id
        LEFT JOIN marque m ON v.marque_id = m.marque_id
        LEFT JOIN energie e ON v.energie_id = e.energie_id
        ORDER BY c.date_depart DESC, c.covoiturage_id DESC
    ");
    $stmt->execute();
    $covoiturages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $covoiturages = [];
}

// Récupérer les réservations si la table existe
$reservations = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               u_passager.nom as passager_nom, 
               u_passager.prenom as passager_prenom, 
               u_passager.pseudo as passager_pseudo,
               u_passager.email as passager_email,
               u_passager.telephone as passager_telephone,
               c.lieu_depart, c.lieu_arrivee, c.date_depart, c.prix_personne
        FROM reservations r
        LEFT JOIN user u_passager ON r.user_id = u_passager.user_id
        LEFT JOIN covoiturage c ON r.covoiturage_id = c.covoiturage_id
        ORDER BY r.date_reservation DESC
    ");
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reservations = [];
}

require_once __DIR__ . "/../templates/header.php";
?>

<section class="hero count-section py-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="ps-3 pt-3 mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Espace Employé</li>
            </ol>
            <div class="col text-end me-3 pb-3">
                <a href="user_count.php" class="btn btn-primary btn-sm d-md-inline-block">Retour Mon compte
                </a>
            </div>
        </nav>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h2 class="mb-0">
                            <i class="bi bi-person-badge me-2"></i>
                            Espace Employé - Tableau de bord
                        </h2>
                        <p class="mb-0 mt-2">Bienvenue <?= htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Onglets -->
        <ul class="nav nav-tabs mb-4" id="employeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="avis-tab" data-bs-toggle="tab" data-bs-target="#avis" type="button" role="tab">
                    <i class="bi bi-star me-2"></i>Avis à valider (<?= count($avis_en_attente) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="admin-avis-tab" data-bs-toggle="tab" data-bs-target="#admin_avis_mongodb" type="button" role="tab">
                    <i class="bi bi-database me-2"></i>Tous les avis (<?= count($admin_avis_mongodb) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="covoiturages-tab" data-bs-toggle="tab" data-bs-target="#covoiturages" type="button" role="tab">
                    <i class="bi bi-car-front me-2"></i>Covoiturages (<?= count($covoiturages) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reservations-tab" data-bs-toggle="tab" data-bs-target="#reservations" type="button" role="tab">
                    <i class="bi bi-calendar-check me-2"></i>Réservations (<?= count($reservations) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="employeTabsContent">
            <!-- Onglet Avis -->
            <div class="tab-pane fade show active" id="avis" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="bi bi-star-half me-2"></i>
                            Avis en attente de validation
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($avis_en_attente)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle text-success icon-large-avis"></i>
                                <h5 class="mt-3 text-muted">Aucun avis en attente</h5>
                                <p class="text-muted">Tous les avis ont été traités !</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($avis_en_attente as $avis): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <span class="badge bg-warning">En attente</span>
                                                <small class="text-muted">ID: <?= htmlspecialchars(substr($avis['_id'] ?? $avis['avis_id'] ?? '', 0, 8)) ?></small>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-center mb-3">
                                                    <?php if (!empty($avis['photo'])): ?>
                                                        <img src="data:image/jpeg;base64,<?= base64_encode($avis['photo']) ?>"
                                                            alt="Photo" class="rounded-circle me-3 avatar-photo-employe">
                                                    <?php else: ?>
                                                        <div class="d-flex justify-content-center align-items-center rounded-circle bg-light me-3 avatar-placeholder-employe">
                                                            <i class="bi bi-person-fill text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']) ?></h6>
                                                        <small class="text-muted">@<?= htmlspecialchars($avis['pseudo']) ?></small>
                                                    </div>
                                                </div>

                                                <div class="text-warning mb-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= ($avis['note'] ?? 5)): ?>
                                                            <i class="bi bi-star-fill"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>

                                                <p class="card-text"><?= htmlspecialchars($avis['commentaire']) ?></p>

                                                <div class="mt-3">
                                                    <small class="text-muted">
                                                        <strong>Email:</strong> <?= htmlspecialchars($avis['email']) ?><br>
                                                        <strong>Téléphone:</strong> <?= htmlspecialchars($avis['telephone'] ?: 'Non renseigné') ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <div class="btn-group justify-content-center w-100" role="group">
                                                    <form method="POST" class="d-inline ms-2">
                                                        <input type="hidden" name="avis_id" value="<?= htmlspecialchars($avis['_id'] ?? $avis['avis_id'] ?? '') ?>">
                                                        <input type="hidden" name="action" value="valider">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="bi bi-check-lg me-1"></i>Valider
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline ms-2">
                                                        <input type="hidden" name="avis_id" value="<?= htmlspecialchars($avis['_id'] ?? $avis['avis_id'] ?? '') ?>">
                                                        <input type="hidden" name="action" value="refuser">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-x-lg me-1"></i>Refuser
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline ms-2" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')">
                                                        <input type="hidden" name="avis_id" value="<?= htmlspecialchars($avis['_id'] ?? $avis['avis_id'] ?? '') ?>">
                                                        <input type="hidden" name="action" value="supprimer">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="bi bi-trash me-1"></i>Supprimer
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Onglet Tous les avis -->
            <div class="tab-pane fade" id="admin_avis_mongodb" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-database me-2"></i>
                            Tous les avis
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($admin_avis_mongodb)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-database text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">Aucun avis</h5>
                                <p class="text-muted">Aucun avis n'a été déposé pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark text-center">
                                        <tr>
                                            <th>ID</th>
                                            <th>Utilisateur</th>
                                            <th>Note</th>
                                            <th>Commentaire</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($admin_avis_mongodb as $avis): ?>
                                            <?php
                                            $statut = $avis['statut'] ?? 'en attente';
                                            $badge_class = 'secondary';
                                            $statut_text = 'Inconnu';

                                            switch ($statut) {
                                                case 'valide':
                                                    $badge_class = 'success';
                                                    $statut_text = 'Validé';
                                                    break;
                                                case 'refuse':
                                                    $badge_class = 'danger';
                                                    $statut_text = 'Refusé';
                                                    break;
                                                case 'en attente':
                                                    $badge_class = 'warning';
                                                    $statut_text = 'En attente';
                                                    break;
                                            }
                                            ?>
                                            <tr>
                                                <td class="text-center"><?= htmlspecialchars(substr($avis['_id'] ?? $avis['avis_id'] ?? '', 0, 8)) ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($avis['prenom'] . ' ' . $avis['nom']) ?></strong><br>
                                                        <small class="text-muted">@<?= htmlspecialchars($avis['pseudo']) ?></small><br>
                                                        <small class="text-muted"><?= htmlspecialchars($avis['email']) ?></small>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="text-warning">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= ($avis['note'] ?? 5)): ?>
                                                                <i class="bi bi-star-fill"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <small class="text-muted">(<?= $avis['note'] ?? 5 ?>/5)</small>
                                                </td>
                                                <td>
                                                    <div style="max-width: 300px;">
                                                        <?= htmlspecialchars($avis['commentaire']) ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <?= htmlspecialchars($avis['created_at'] ?? 'N/A') ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?= $badge_class ?>"><?= $statut_text ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <?php if ($statut !== 'valide'): ?>
                                                            <form method="POST" class="d-inline mb-1">
                                                                <input type="hidden" name="avis_id" value="<?= htmlspecialchars($avis['_id'] ?? $avis['avis_id'] ?? '') ?>">
                                                                <input type="hidden" name="action" value="valider">
                                                                <button type="submit" class="btn btn-success btn-sm w-100">
                                                                    <i class="bi bi-check-lg me-1"></i>Valider
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($statut !== 'refuse'): ?>
                                                            <form method="POST" class="d-inline mb-1">
                                                                <input type="hidden" name="avis_id" value="<?= htmlspecialchars($avis['_id'] ?? $avis['avis_id'] ?? '') ?>">
                                                                <input type="hidden" name="action" value="refuser">
                                                                <button type="submit" class="btn btn-danger btn-sm w-100">
                                                                    <i class="bi bi-x-lg me-1"></i>Refuser
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')">
                                                            <input type="hidden" name="avis_id" value="<?= htmlspecialchars($avis['_id'] ?? $avis['avis_id'] ?? '') ?>">
                                                            <input type="hidden" name="action" value="supprimer">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                                                <i class="bi bi-trash me-1"></i>Supprimer
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Onglet Covoiturages -->
            <div class="tab-pane fade" id="covoiturages" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-car-front me-2"></i>
                            Tous les covoiturages
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($covoiturages)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-car-front text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">Aucun covoiturage</h5>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark text-center">
                                        <tr>
                                            <th>ID</th>
                                            <th>Chauffeur</th>
                                            <th>Trajet</th>
                                            <th>Date</th>
                                            <th>Voiture</th>
                                            <th>Crédits</th>
                                            <th>Places</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($covoiturages as $covoiturage): ?>
                                            <tr>
                                                <td><?= $covoiturage['covoiturage_id'] ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($covoiturage['chauffeur_prenom'] . ' ' . $covoiturage['chauffeur_nom']) ?></strong><br>
                                                        <small class="text-muted">@<?= htmlspecialchars($covoiturage['chauffeur_pseudo']) ?></small><br>
                                                        <small class="text-muted"><?= htmlspecialchars($covoiturage['chauffeur_email']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($covoiturage['lieu_depart']) ?></strong><br>
                                                    <i class="bi bi-arrow-down text-muted"></i><br>
                                                    <strong><?= htmlspecialchars($covoiturage['lieu_arrivee']) ?></strong>
                                                </td>
                                                <td><?= date("d/m/Y", strtotime($covoiturage['date_depart'])) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($covoiturage['marque_libelle'] . ' ' . $covoiturage['voiture_modele']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($covoiturage['energie_libelle']) ?></small>
                                                </td>
                                                <td><?= $covoiturage['prix_personne'] ?> crédits</td>
                                                <td><?= $covoiturage['nb_place'] ?></td>
                                                <td>
                                                    <?php
                                                    $statut = $covoiturage['statut'];
                                                    $badge_class = 'secondary';
                                                    $statut_text = 'Inconnu';

                                                    switch ($statut) {
                                                        case 1:
                                                            $badge_class = 'success';
                                                            $statut_text = 'En cours';
                                                            break;
                                                        case 2:
                                                            $badge_class = 'warning';
                                                            $statut_text = 'En attente';
                                                            break;
                                                        case 3:
                                                            $badge_class = 'info';
                                                            $statut_text = 'Terminé';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $badge_class ?>"><?= $statut_text ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Onglet Réservations -->
            <div class="tab-pane fade" id="reservations" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-calendar-check me-2"></i>
                            Réservations
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reservations)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">Aucune réservation</h5>
                                <p class="text-muted">Les réservations apparaîtront ici une fois la table créée.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark text-center">
                                        <tr>
                                            <th>ID Réservation</th>
                                            <th>Passager</th>
                                            <th>Trajet</th>
                                            <th>Date Réservation</th>
                                            <th>Places</th>
                                            <th>Prix Total</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reservations as $reservation): ?>
                                            <tr>
                                                <td><?= $reservation['reservation_id'] ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($reservation['passager_prenom'] . ' ' . $reservation['passager_nom']) ?></strong><br>
                                                        <small class="text-muted">@<?= htmlspecialchars($reservation['passager_pseudo']) ?></small><br>
                                                        <small class="text-muted"><?= htmlspecialchars($reservation['passager_email']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($reservation['lieu_depart']) ?></strong><br>
                                                    <i class="bi bi-arrow-down text-muted"></i><br>
                                                    <strong><?= htmlspecialchars($reservation['lieu_arrivee']) ?></strong><br>
                                                    <small class="text-muted"><?= date("d/m/Y", strtotime($reservation['date_depart'])) ?></small>
                                                </td>
                                                <td><?= date("d/m/Y H:i", strtotime($reservation['date_reservation'])) ?></td>
                                                <td><?= $reservation['nb_places_reservees'] ?></td>
                                                <td><?= $reservation['prix_total'] ?> €</td>
                                                <td>
                                                    <?php
                                                    $statut = $reservation['statut'];
                                                    $badge_class = 'secondary';

                                                    switch ($statut) {
                                                        case 'en_attente':
                                                            $badge_class = 'warning';
                                                            break;
                                                        case 'confirmee':
                                                            $badge_class = 'success';
                                                            break;
                                                        case 'annulee':
                                                            $badge_class = 'danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $badge_class ?>"><?= ucfirst($statut) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>